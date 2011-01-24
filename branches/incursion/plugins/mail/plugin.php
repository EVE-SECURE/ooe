<?php

    class mail extends Plugin {
        var $name = 'Mail';
        var $level = 1;

        function mail($db, $site) {
            $this->Plugin($db, $site);

            $this->site->plugins['mainmenu']->addLink('main', 'Mail', '?module=mail', 'icon94_08');
        }

        function getContent() {
            if (isset($_GET['notifications'])) {
                return $this->getNotifications();
            } else {
                return $this->getMail();
            }
        }

        function getMail() {
            $this->site->character->loadMail();
            $mail = array();
            foreach ($this->site->character->mail as $m) {
                if (isset($_GET['personal'])) {
                    if ($m->toCorpID == 0 && $m->toListID == 0) {
                        $mail[] = objectToArray($m, array('DBManager', 'eveDB'));
                    }
                } else if (isset($_GET['corp'])) {
                    if ($m->toCorpID > 0) {
                        $mail[] = objectToArray($m, array('DBManager', 'eveDB'));
                    }
                } else if (isset($_GET['lists'])) {
                    if ($m->toListID > 0) {
                        $mail[] = objectToArray($m, array('DBManager', 'eveDB'));
                    }
                } else {
                    $mail[] = objectToArray($m, array('DBManager', 'eveDB'));
                }
            }

            return $this->render('mail', array('mail' => $mail));
        }

        function getNotifications() {
            $this->site->character->loadNotifications();
            $notifications = objectToArray($this->site->character->notifications, array('DBManager', 'eveDB'));
            return $this->render('notifications', array('mail' => $notifications));
        }

        function getContentJson() {
            $message = false;
            if (isset($_GET['messageID'])) {
                $this->site->character->loadMail();
                foreach ($this->site->character->mail as $m) {
                    if ($m->messageID == $_GET['messageID']) {
                        $message = objectToArray($this->site->character->getMailMessage($m), array('DBManager', 'eveDB'));
                        $message['headers']['sentDate'] = date('d M Y H:i', $message['headers']['sentDate']);
                    }
                }
            } else if (isset($_GET['notificationID'])) {
                $this->site->character->loadNotifications();
                foreach ($this->site->character->notifications as $m) {
                    if ($m->notificationID == $_GET['notificationID']) {
                        $message = objectToArray($this->site->character->getNotificationText($m), array('DBManager', 'eveDB'));
                        $message['text'] =$this->spiffyNotificationText($message['text']);
                        $message['headers']['sentDate'] = date('d M Y H:i', $message['headers']['sentDate']);
                    }
                }
            }
            return json_encode($message);
        }

        function spiffyNotificationText($text) {
            $lines = explode("\n", $text);
            
            for ($i = 0; $i < count($lines); $i++) {
                $str = $lines[$i];
                $pts = explode(": ", $str);

                if (strpos($pts[0], 'should') !== false) {
                    if (in_array($pts[1], array('null', '0', 'no', 'false'))) {
                        $pts[1] = 'No';
                    } else if (in_array($pts[1], array('1', 'yes', 'true'))) {
                        $pts[1] = 'Yes';
                    }
                } else if (strpos($pts[0], 'Date') !== false) {
                    // doesn't work... wtf are these date stamps
                    //$pts[1] = date('d M Y H:i', substr($pts[1], 0, 10));
                }

                if (substr($pts[0], strlen($pts[0]) - 2) != 'ID') {
                    $pts[0] = ucwords(ereg_replace("([A-Z]|[0-9]+)", " \\0", $pts[0]));
                } else {
                    // based on the type of ID we can look up a proper name, icon, etc
                }

                $lines[$i] = implode(": ", $pts);
            }

            return implode("<br />", $lines);
        }
    }

?>
