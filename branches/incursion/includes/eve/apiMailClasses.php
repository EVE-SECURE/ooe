<?php

    class eveMailMessage {
        var $messageID = 0;
        var $senderID = 0;
        var $sentDate = 0;
        var $title = '';
        var $toCorpID = 0;
        var $toCharacterIDs = array();
        var $toListID = 0;
        var $read = false;

        var $senderName = '';
        var $toCorpName = '';
        var $toCharacterNames = array();
        var $toListName = '';

        function eveMailMessage($acc, $mail) {
            $this->messageID = (int)$mail['messageID'];
            $this->senderID = (int)$mail['senderID'];
            $this->sentDate = strtotime((string)$mail['sentDate']) + $acc->timeOffset;
            $this->title = (string)$mail['title'];
            $this->toCorpID = (int)$mail['toCorpOrAllianceID'];
            $this->toCharacterIDs = explode(',', (string)$mail['toCharacterIDs']);
            $this->toListID = (int)$mail['toListID'];
            $this->read = (int)$mail['read'] > 0;
            if (!isset($this->read) || empty($this->read)) {
                $this->read = true;
            } else {
                $this->read = false;
            }
        }
    }

    class eveMailMessageBody {
        var $messageID = 0;
        var $message = '';

        function eveMailMessageBody($acc, $mail) {
            $this->messageID = (int)$mail['messageID'];
            $this->message = (string)$mail;
        }
    }

    class eveNotification {
        var $notificationID = 0;
        var $senderID = 0;
        var $sentDate = 0;
        var $typeID = 0;
        var $read = false;

        var $sender = false;

        function eveNotification($acc, $notification) {
            $this->notificationID = (int)$notification['notificationID'];
            $this->senderID = (int)$notification['senderID'];
            $this->sentDate = strtotime((string)$notification['sentDate']) + $acc->timeOffset;
            $this->typeID = (int)$notification['typeID'];
            $this->read = (int)$notification['read'] > 0;
            if (!isset($this->read) || empty($this->read)) {
                $this->read = true;
            } else {
                $this->read = false;
            }

            $this->sender = $acc->db->eveName($this->senderID);
        }
    }

?>
