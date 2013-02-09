<?php

class eveAccount {

    var $apiKey = '';
    var $characters = array();
    var $characterList = array();
    var $accountStatus = null;
    var $error = false;
    var $timeOffset = 0;

    function eveAccount($apiKey) {
        $this->apiKey = $apiKey;

//        if ($autoLoad) {
//            $this->getCharacters();
//        }
    }

//    function getCharacters() {
//        $charData = new apiRequest('account/Characters.xml.aspx', array($this->userId, $this->apiKey));
//        if ($charData->data) {
//            if ($charData->data->error)
//                $this->error = array('code' => (int) $charData->data->error['code'], 'message' => (string) $charData->data->error);
//
//            if (!$this->error) {
//                foreach ($charData->data->result->rowset->row as $char) {
//                    $char = new eveCharacter($this, $char);
//                    $this->characters[] = $char;
//                }
//            }
//
//            if (!$this->error && count($this->characters) == 0) {
//                $this->error = array('code' => 1, 'message' => 'No characters (WTF?)!');
//            }
//        }
//    }

    function getAccountStatus() {
        if (!$this->apiKey->isCorpKey() && $this->apiKey->hasAccess(CHAR_AccountStatus)) {

            $accData = new apiRequest('account/AccountStatus.xml.aspx', $this->apiKey);

            if (!$accData->data) {
                return;
            }

            if ($accData->data->error) {
                apiError('account/AccountStatus.xml.aspx', $accData->data->error);
                $this->error = (string) $accData->data->error;
            } else {
                $this->accountStatus = new eveAccountStatus($accData->data->result);
            }
        } else if ($this->apiKey->isCorpKey()) {
            $this->error = 'No account status available for corporation key.';
        } else {
            $this->error = 'Key does not provide access to account status.';
        }
    }

//    function checkFullAccess() {
//        $balanceTest = new apiRequest('char/AccountBalance.xml.aspx', array($this->userId, $this->apiKey, $this->characters[0]->characterID));
//        if ($balanceTest->data->error) {
//            $this->error = array('code' => (int) $balanceTest->data->error['code'], 'message' => (string) $balanceTest->data->error);
//        }
//    }
}

class eveAccountStatus {

    var $userID = 0;
    var $paidUntil = 0;
    var $createDate = 0;
    var $logonCount = 0;
    var $logonMinutes = 0;

    function eveAccountStatus($accStatus) {
        $this->userID = (int) $accStatus->userID;
        $this->paidUntil = eveTimeOffset::getOffsetTime($accStatus->paidUntil);
        $this->createDate = eveTimeOffset::getOffsetTime($accStatus->createDate);
        $this->logonCount = (int) $accStatus->logonCount;
        $this->logonMinutes = (int) $accStatus->logonMinutes;
    }

}

?>