<?php

class kills extends Plugin {

    var $name = 'Kills';
    var $level = 1;

    function kills($db, $site) {
        $this->Plugin($db, $site);

        if (eveKeyManager::getKey($this->site->user->char_apikey_id)
                && eveKeyManager::getKey($this->site->user->char_apikey_id)->hasAccess(CHAR_KillLog)) {
            $this->site->plugins['mainmenu']->addLink('main', 'Kills', '?module=kills', 'kills');
            $this->site->plugins['mainmenu']->addLink('main', 'Deaths', '?module=kills&deaths=1', 'deaths');
        }

        if (eveKeyManager::getKey($this->site->user->corp_apikey_id)
                && eveKeyManager::getKey($this->site->user->corp_apikey_id)->hasAccess(CORP_KillLog)) {
            $this->site->plugins['mainmenu']->addLink('corp', 'Kills', '?module=kills&corp=1', 'kills');
            $this->site->plugins['mainmenu']->addLink('corp', 'Deaths', '?module=kills&deaths=1&corp=1', 'deaths');
        }
    }

    function getContent() {
        if (!isset($_GET['p'])) {
            $_GET['p'] = 0;
        }
        if (!isset($_GET['find'])) {
            $_GET['find'] = '';
        }
        if (!isset($_GET['deathType'])) {
            $_GET['deathType'] = 0;
        }

        if (isset($_GET['corp'])) {
            if (eveKeyManager::getKey($this->site->user->corp_apikey_id) != null) {
                $kl = new eveKillsList(eveKeyManager::getKey($this->site->user->corp_apikey_id));
                $kl->load();
            }
        } else {
            if (eveKeyManager::getKey($this->site->user->char_apikey_id) != null) {
                $kl = new eveKillsList(eveKeyManager::getKey($this->site->user->char_apikey_id));
                $kl->load();
            }
        }

        $killList = $kl->kills;
        $deathList = $kl->deaths;

        if (isset($_GET['deaths'])) {
            $this->name = 'Deaths';
            $deaths = array();
            for ($i = 0; $i < count($deathList); $i++) {
                if ((empty($_GET['find']) || $this->filterKill($deathList[$i], $_GET['find']))
                        && $this->filterDeath($deathList[$i], $_GET['deathType'])) {
                    $deathList[$i]->getDropValues();
                    $deaths[] = objectToArray($deathList[$i], array('DBManager', 'eveDB'));
                }
            }

            if (count($deaths) > 10) {
                $deaths = array_chunk($deaths, 10);

                $pageCount = count($deaths);
                $pageNum = max((int) $_GET['p'], 0);
                $nextPage = min($pageNum + 1, $pageCount);
                $prevPage = max($pageNum - 1, 0);

                $deaths = $deaths[$pageNum];
            } else {
                $pageCount = 0;
                $pageNum = 0;
                $nextPage = 0;
                $prevPage = 0;
            }

            return $this->render('deaths', array('deaths' => $deaths, 'find' => $_GET['find'], 'deathType' => $_GET['deathType'], 'corp' => isset($_GET['corp'],
                        'pageCount' => $pageCount, 'pageNum' => $pageNum, 'nextPage' => $nextPage, 'prevPage' => $prevPage)));
        } else {
            $kills = array();
            for ($i = 0; $i < count($killList); $i++) {
                if (empty($_GET['find']) || $this->filterKill($killList[$i], $_GET['find'])) {
                    $killList[$i]->getDropValues();
                }
                $kills[] = objectToArray($killList[$i], array('DBManager', 'eveDB'));
            }

            if (count($kills) > 10) {
                $kills = array_chunk($kills, 10);

                $pageCount = count($kills);
                $pageNum = max((int) $_GET['p'], 0);
                $nextPage = min($pageNum + 1, $pageCount);
                $prevPage = max($pageNum - 1, 0);

                $kills = $kills[$pageNum];
            } else {
                $pageCount = 0;
                $pageNum = 0;
                $nextPage = 0;
                $prevPage = 0;
            }

            return $this->render('kills', array('kills' => $kills, 'find' => $_GET['find'], 'corp' => isset($_GET['corp'],
                        'pageCount' => $pageCount, 'pageNum' => $pageNum, 'nextPage' => $nextPage, 'prevPage' => $prevPage)));
        }
    }

    function filterKill($kill, $filter) {
        $accept = false;

        if (stripos($kill->victim->characterName, $filter) !== false) {
            $accept = true;
        } else if (stripos($kill->victim->corporationName, $filter) !== false) {
            $accept = true;
        } else if (stripos($kill->victim->allianceName, $filter) !== false) {
            $accept = true;
        } else if ($kill->victim->ship && (stripos($kill->victim->ship->typename, $filter) !== false)) {
            $accept = true;
        } else if (stripos($kill->solarSystem->solarsystemname, $filter) !== false) {
            $accept = true;
        }

        if (!$accept) {
            for ($i = 0; $i < count($kill->attackers); $i++) {
                if (stripos($kill->attackers[$i]->characterName, $filter) !== false) {
                    $accept = true;
                } else if (stripos($kill->attackers[$i]->corporationName, $filter) !== false) {
                    $accept = true;
                } else if (stripos($kill->attackers[$i]->allianceName, $filter) !== false) {
                    $accept = true;
                } else if ($kill->attackers[$i]->ship && (stripos($kill->attackers[$i]->ship->typename, $filter) !== false)) {
                    $accept = true;
                }
            }
        }

        return $accept;
    }

    function filterDeath($death, $deathType) {
        $accept = false;

        if ($deathType == 0) {
            $accept = true;
        } else {
            for ($i = 0; $i < count($death->attackers); $i++) {
                $accept = $deathType == 1 ? $death->attackers[$i]->characterID > 0 : $death->attackers[$i]->characterID == 0;
                if ($accept) {
                    break;
                }
            }
        }

        return $accept;
    }

}

?>