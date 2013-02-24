<?php

require_once('curl.class.php');
require_once('api/keys.php');
require_once('api/account.php');
require_once('api/assets.php');
require_once('api/marketOrders.php');
require_once('api/marketTransactions.php');
require_once('api/industryJobs.php');
require_once('api/journal.php');
require_once('api/kills.php');
require_once('api/skills.php');
require_once('api/certificates.php');
require_once('api/mail.php');
require_once('api/character.php');
require_once('api/corporation.php');
require_once('api/starbase.php');
require_once('apidb.php');
require_once('apimarket.php');
require_once('apiConstants.php');

$cacheDelays = array(
    101, 103, 115, 116, 117, 119
);

$GLOBALS['EVEAPI_ERRORS'] = array();

class eveTimeOffset {

    static $offset = 0;
    static $eveTime = 0;

    // converts a GTM time string to local (user-defined) time
    static function getOffsetTime($strTime) {
        return strtotime((string) $strTime) + self::$offset;
    }

}

eveTimeOffset::$eveTime = time() - date('Z');

class apiError {

    var $errorCode = 0;
    var $errorText = '';
    var $method = '';

    function apiError($errorCode, $errorText, $method) {
        $this->errorCode = $errorCode;
        $this->errorText = $errorText;
        $this->method = $method;
    }

}

class apiStats {

    static $requests = array();
    static $errors = array();
    static $cacheRequests = 0;
    static $liveRequests = 0;

    static function addRequest($method, $requestTime, $cached, $expires) {
        self::$requests[] = array(
            'method' => $method,
            'time' => $requestTime,
            'cache' => $cached,
            'cacheUntil' => $expires
        );
        if ($cached) {
            self::$cacheRequests++;
        } else {
            self::$liveRequests++;
        }
    }

    static function addError($error) {
        self::$errors[] = $error;
    }

}

class apiRequest {

    var $data = false;
    var $error;

    function apiRequest($method, $apiKey = null, $forCharacter = false, $extraParams = array()) {
        $result = false;

        $start = microtime(true);

        $http = new cURL();
        $http->setOption('CURLOPT_USERAGENT', 'Out of Eve (shrimp@shrimpworks.za.net)');
        $http->setOption('CURLOPT_TIMEOUT', 45);

        $apiUrl = $GLOBALS['config']['eve']['api_url'];
        $fetchMethod = $GLOBALS['config']['eve']['method'];

        $params = array();

        if (isset($apiKey)) {
            $params['keyID'] = $apiKey->keyID;
            $params['vCode'] = $apiKey->vCode;
            if ($forCharacter) {
                $params['characterID'] = $forCharacter->characterID;
            }
        }

        if (!empty($extraParams)) {
            $params = array_merge($params, $extraParams);
        }

        $cacheTimeAdd = $GLOBALS['config']['eve']['cache_time_add'];

        $cacheSum = md5($method . implode('.', $params));
        $cacheFile = $GLOBALS['config']['eve']['cache_dir'] . $cacheSum;

        $cacheResult = $this->checkCache($cacheFile);

        if (!$cacheResult) {
            if (strtoupper($fetchMethod == 'GET')) {
                $apiResponse = $http->get($apiUrl . '/' . $method . $this->queryString($params));
            } else {
                $apiResponse = $http->post($apiUrl . '/' . $method, $params);
            }
            $httpResponse = $http->getInfo();

            /**
             * Ensure we received no HTTP errors, and we received actual data
             */
            if (($httpResponse['http_code'] >= 200) && ($httpResponse['http_code'] <= 300) && (!empty($apiResponse))) {
                try {
                    $result = new SimpleXMLElement($apiResponse);
                } catch (Exception $e) {
                    $result = false;
                    $this->error = new apiError($e->getCode(), $e->getMessage(), $method);
                }

                /**
                 * Loading of results from the API was successful
                 */
                if ($result) {
                    /**
                     * Received an error from the API, try to fall back to cached data which may work...
                     */
                    if (isset($result->error) && !isset($cacheResult->error)) {
                        $this->error = new apiError((int) $result->error['code'], (string) $result->error, $method);
                        $cacheResult = $this->checkCache($cacheFile, true);
                        if ($cacheResult) {
                            if (in_array($this->error->errorCode, $GLOBALS['cacheDelays'])) {
                                $cacheResult->cachedUntil = (string) $result->cachedUntil;
                                $this->saveCache($cacheFile, $cacheResult->asXML(), strtotime($cacheResult->cachedUntil) + date('Z') + $cacheTimeAdd);
                            }
                            $result = $cacheResult;
                        }
                    } else {
                        /**
                         * Everything went well, save the result to cache if needed.
                         */
                        if (array_key_exists($method, $GLOBALS['config']['eve']['cache_override'])) {
                            $this->saveCache($cacheFile, $apiResponse, time() + $GLOBALS['config']['eve']['cache_override'][$method]);
                        } else if (isset($result->cachedUntil)) {
                            $this->saveCache($cacheFile, $apiResponse, strtotime($result->cachedUntil) + date('Z') + $cacheTimeAdd);
                        }
                    }
                }
            } else {
                $this->error = new apiError(1, 'HTTP error: ' + $httpResponse['http_code'], $method);
            }
        } else {
            $result = $cacheResult;
        }

        apiStats::addRequest(
                $method, microtime(time) - $start, $result == $cacheResult, isset($result->cachedUntil) ? strtotime($result->cachedUntil) + date('Z') + $cacheTimeAdd : 0);

        if ($this->error) {
            apiStats::addError($this->error);
        }

        $this->data = $result;
    }

    function queryString($params) {
        $res = '?';
        foreach ($params as $key => $value) {
            $res .= $key . '=' . urlencode($value) . '&';
        }
        return substr($res, 0, -1);
    }

    function checkCache($cacheFile, $force = false) {
        $res = false;
        if (file_exists($cacheFile)) {
            if ($force || (time() <= filemtime($cacheFile))) {
                $cachedResponse = file_get_contents($cacheFile);
                if (!empty($cachedResponse)) {
                    $res = new SimpleXMLElement($cachedResponse);
                }
            }
        }

        return $res;
    }

    function saveCache($cacheFile, $cacheContent, $cachedUntil) {
        file_put_contents($cacheFile, $cacheContent);
        touch($cacheFile, $cachedUntil);
    }

    function clearOldCache() {
        // maximum cache age is 7 days - one week
        $maxAge = 3600 * 24 * 7;

        $files = scandir($GLOBALS['config']['eve']['cache_dir']);
        foreach ($files as $file) {
            $file = $GLOBALS['config']['eve']['cache_dir'] . $file;
            if (is_file($file)) {
                if (time() - (filemtime($file)) > $maxAge) {
                    unlink($file);
                }
            }
        }
    }

}

?>