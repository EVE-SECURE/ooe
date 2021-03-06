<?php

class manufacture extends Plugin {

    var $name = 'Manufacture & Research';
    var $level = 1;

    function manufacture($db, $site) {
        $this->Plugin($db, $site);

        if (eveKeyManager::getKey($this->site->user->char_apikey_id)
                && eveKeyManager::getKey($this->site->user->char_apikey_id)->hasAccess(CHAR_IndustryJobs)) {
            $this->site->plugins['mainmenu']->addLink('main', 'Manufacture &amp; Research', '?module=manufacture', 'industry');
        }

        if (eveKeyManager::getKey($this->site->user->corp_apikey_id)
                && eveKeyManager::getKey($this->site->user->corp_apikey_id)->hasAccess(CORP_IndustryJobs)) {
            $this->site->plugins['mainmenu']->addLink('corp', 'Manufacture &amp; Research', '?module=manufacture&corp=1', 'industry');
        }
    }

    function getContent() {
        if (!isset($_GET['p'])) {
            $_GET['p'] = 0;
        }
        if (!isset($_GET['activity'])) {
            $_GET['activity'] = 0;
        }
        if (!isset($_GET['complete'])) {
            $_GET['complete'] = 0;
        }

        if (isset($_GET['corp'])) {
            if (eveKeyManager::getKey($this->site->user->corp_apikey_id) != null) {
                $il = new eveIndustryJobList(eveKeyManager::getKey($this->site->user->corp_apikey_id));
                $il->load();
            }
        } else {
            if (eveKeyManager::getKey($this->site->user->char_apikey_id) != null) {
                $il = new eveIndustryJobList(eveKeyManager::getKey($this->site->user->char_apikey_id));
                $il->load();
            }
        }
        $industryList = $il->industryJobs;

        $jobs = array();
        $activities = array();

        foreach ($industryList as $job) {
            if (!isset($activities[$job->activityID])) {
                $activities[$job->activityID] = $job->activity->activityname;
            }

            if (($_GET['activity'] == 0) || ($_GET['activity'] == $job->activityID)) {
                if (($_GET['complete'] > 0) || (($_GET['complete'] == 0) && ($job->completed == 0))) {
                    $jobs[] = objectToArray($job, array('DBManager', 'eveDB'));
                }
            }
        }
        asort($activities);

        if (count($jobs) > 50) {
            $jobs = array_chunk($jobs, 50);

            $pageCount = count($jobs);
            $pageNum = max((int) $_GET['p'], 0);
            $nextPage = min($pageNum + 1, $pageCount);
            $prevPage = max($pageNum - 1, 0);

            $jobs = $jobs[$pageNum];
        } else {
            $pageCount = 0;
            $pageNum = 0;
            $nextPage = 0;
            $prevPage = 0;
        }

        return $this->render('jobs', array('jobs' => $jobs, 'activities' => $activities, 'activity' => $_GET['activity'], 'complete' => $_GET['complete'],
                    'pageCount' => $pageCount, 'pageNum' => $pageNum, 'nextPage' => $nextPage, 'prevPage' => $prevPage, 'corp' => isset($_GET['corp'])));
    }

}

?>
