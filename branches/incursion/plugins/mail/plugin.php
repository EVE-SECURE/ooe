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

            $mail = objectToArray($this->site->character->mail, array('DBManager', 'eveDB'));

            $message = false;
            if (isset($_GET['view'])) {
                foreach ($this->site->character->mail as $m) {
                    if ($m->messageID == $_GET['view']) {
                        $message = $this->site->character->getMailMessage($m);
                    }
                }
            }

            return $this->render('mail', array('mail' => $mail, 'message' => $message));
        }
    }

?>
