<?php

    class mail extends Plugin {
        var $name = 'Mail';
        var $level = 1;

        function mail($db, $site) {
            $this->Plugin($db, $site);

            $this->site->plugins['mainmenu']->addLink('main', 'Mail', '?module=mail', 'icon94_08');
        }

        function getContent() {
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
                } else if (isset($_GET['notifications'])) {
                } else {
                    $mail[] = objectToArray($m, array('DBManager', 'eveDB'));
                }
            }

            $message = false;
            if (isset($_GET['view'])) {
                foreach ($this->site->character->mail as $m) {
                    if ($m->messageID == $_GET['view']) {
                        $message = objectToArray($this->site->character->getMailMessage($m), array('DBManager', 'eveDB'));
                    }
                }
            }

            return $this->render('mail', array('mail' => $mail, 'message' => $message));
        }

        function getContentJson() {
            $this->site->character->loadMail();
            $message = false;
            if (isset($_GET['view'])) {
                foreach ($this->site->character->mail as $m) {
                    if ($m->messageID == $_GET['view']) {
                        $message = objectToArray($this->site->character->getMailMessage($m), array('DBManager', 'eveDB'));
                        $message['headers']['sentDate'] = date('d M Y H:i', $message['headers']['sentDate']);
                    }
                }
            }
            return json_encode($message);
        }
    }

?>
