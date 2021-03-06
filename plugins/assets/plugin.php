<?php

class assets extends Plugin {

    var $name = 'Assets';
    var $level = 1;

    function assets($db, $site) {
        $this->Plugin($db, $site);

        if (eveKeyManager::getKey($this->site->user->char_apikey_id)
                && eveKeyManager::getKey($this->site->user->char_apikey_id)->hasAccess(CHAR_AssetList)) {
            $this->site->plugins['mainmenu']->addLink('main', 'Assets', '?module=assets', 'assets');
        }

        if (eveKeyManager::getKey($this->site->user->corp_apikey_id)
                && eveKeyManager::getKey($this->site->user->corp_apikey_id)->hasAccess(CORP_AssetList)) {
            $this->site->plugins['mainmenu']->addLink('corp', 'Assets', '?module=assets&corp=1', 'assets');
        }
    }

    function getContent() {
        if (!isset($_GET['p'])) {
            $_GET['p'] = 0;
        }
        if (!isset($_GET['group'])) {
            $_GET['group'] = 0;
        }

        if (isset($_GET['corp'])) {
            if (eveKeyManager::getKey($this->site->user->corp_apikey_id) != null) {
                $al = new eveAssetList(eveKeyManager::getKey($this->site->user->corp_apikey_id));
                $al->load(true);
                $fullAssetList = $al->assets;
            }
        } else {
            if (eveKeyManager::getKey($this->site->user->char_apikey_id) != null) {
                $al = new eveAssetList(eveKeyManager::getKey($this->site->user->char_apikey_id));
                $al->load(true);
                $fullAssetList = $al->assets;
            }
        }

        if (isset($_GET['type']) && ($_GET['type'] == 'find')) {
            $_GET['item'] = trim($_GET['item']);

            $assets = $this->searchAsset($fullAssetList, $_GET['item']);
            usort($assets, 'assetNameSort');

            $assetList = objectToArray($assets, array('DBManager', 'eveDB'));

            return $this->render('find', array('assets' => $assetList, 'search' => $_GET['item'], 'corp' => isset($_GET['corp'])));
        } else if (isset($_GET['type']) && ($_GET['type'] == 'ships')) {
            $this->name .= ': My Ships';
            $ships = $this->searchAssetCategory($fullAssetList, 6);
            usort($ships, 'assetNameSort');
            for ($i = 0; $i < count($ships); $i++) {
                if ($ships[$i]->contents) {
                    usort($ships[$i]->contents, 'assetSlotSort');
                }
            }


            if (count($ships) > 10) {
                $ships = array_chunk($ships, 10);

                $pageCount = count($ships);
                $pageNum = max((int) $_GET['p'], 0);
                $nextPage = min($pageNum + 1, $pageCount);
                $prevPage = max($pageNum - 1, 0);

                $ships = $ships[$pageNum];
            } else {
                $pageCount = 0;
                $pageNum = 0;
                $nextPage = 0;
                $prevPage = 0;
            }

            $shipList = objectToArray($ships, array('DBManager', 'eveDB'));

            return $this->render('ships', array('ships' => $shipList, 'pageCount' => $pageCount,
                        'pageNum' => $pageNum, 'nextPage' => $nextPage, 'prevPage' => $prevPage, 'corp' => isset($_GET['corp'])));
        } else {
            $assets = array();

            $allGroups = $this->getAssetGroups($fullAssetList);
            $groups = array();
            foreach ($allGroups as $g) {
                if (!in_array($g, $groups, true)) {
                    $groups[] = $g;
                }
            }
            usort($groups, 'groupNameSort');

            if ($_GET['group'] > 0) {
                $this->filterAssetGroup($fullAssetList, $_GET['group']);
            }

            foreach ($fullAssetList as $asset) {
                if (!$asset->hide) {
                    if (!empty($asset->locationID)) {
                        if (!isset($assets[(string) $asset->locationID])) {
                            $assets[(string) $asset->locationID] = array();
                            $assets[(string) $asset->locationID]['location'] = $asset->location;
                            $assets[(string) $asset->locationID]['locationId'] = $asset->locationID;
                            $assets[(string) $asset->locationID]['locationName'] = $asset->locationName;
                            $assets[(string) $asset->locationID]['assets'] = array();
                        }
                        if ($asset->contents) {
                            usort($asset->contents, 'assetSlotSort');
                        }
                        $assets[(string) $asset->locationID]['assets'][] = $asset;

                        usort($assets[(string) $asset->locationID]['assets'], 'assetSlotSort');
                    }
                }
            }
            usort($assets, 'assetStationSort');

            foreach ($assets as $k => $v) {
                $ships = array();
                $containers = array();
                $shuttles = array();
                $items = array();

                usort($v['assets'], 'assetNameSort');

                foreach ($v['assets'] as $ass) {
                    if ($ass->item->groupid == 31) {
                        $shuttles[] = $ass;
                    } else if (($ass->item->group) && ($ass->item->group->category) && ($ass->item->group->category->categoryid == 6)) {
                        if ($ass->contents) {
                            usort($ass->contents, 'assetSlotSort');
                        }
                        $ships[] = $ass;
                    } else if ($ass->contents) {
                        usort($ass->contents, 'assetNameSort');
                        $containers[] = $ass;
                    } else {
                        $items[] = $ass;
                    }
                }

                $assets[$k]['assets'] = array_merge($ships, $containers, $shuttles, $items);
            }

            if (count($assets) > 15) {
                $assets = array_chunk($assets, 15);

                $pageCount = count($assets);
                $pageNum = max((int) $_GET['p'], 0);
                $nextPage = min($pageNum + 1, $pageCount);
                $prevPage = max($pageNum - 1, 0);

                $assets = $assets[$pageNum];
            } else {
                $pageCount = 0;
                $pageNum = 0;
                $nextPage = 0;
                $prevPage = 0;
            }

            $assetList = objectToArray($assets, array('DBManager', 'eveDB'));
            $groups = objectToArray($groups);

            return $this->render('assets', array('assets' => $assetList, 'groups' => $groups, 'group' => $_GET['group'],
                        'pageCount' => $pageCount, 'pageNum' => $pageNum, 'nextPage' => $nextPage, 'prevPage' => $prevPage,
                        'corp' => isset($_GET['corp'])));
        }
    }

    function getAssetGroups($ass) {
        $result = array();

        for ($i = 0; $i < count($ass); $i++) {
            if ($ass[$i]->contents) {
                $result = array_merge($result, $this->getAssetGroups($ass[$i]->contents));
            }
            if (!in_array($ass[$i]->item->group, $result)) {
                $result[] = $ass[$i]->item->group;
            }
        }

        return $result;
    }

    function filterAssetGroup($ass, $groupId) {
        $removeCount = 0;
        for ($i = 0; $i < count($ass); $i++) {
            $ass[$i]->hide = false;
            if ($ass[$i]->item->groupid != $groupId && !$ass[$i]->contents) {
                $ass[$i]->hide = true;
                $removeCount++;
            } else if ($ass[$i]->contents) {
                if ($this->filterAssetGroup($ass[$i]->contents, $groupId) && $ass[$i]->item->groupid != $groupId) {
                    $ass[$i]->hide = true;
                    $removeCount++;
                }
            }
        }
        return $removeCount == count($ass);
    }

    function searchAsset($ass, $search) {
        $result = array();

        for ($i = 0; $i < count($ass); $i++) {
            if ($ass[$i]->contents) {
                $result = array_merge($result, $this->searchAsset($ass[$i]->contents, $search));
            }
            if ((stripos($ass[$i]->item->typename, $search) !== false) || (stripos($ass[$i]->locationName, $search) !== false)) {
                array_push($result, $ass[$i]);
            }
        }

        return $result;
    }

    function searchAssetCategory($ass, $search) {
        $result = array();

        for ($i = 0; $i < count($ass); $i++) {
            $ass[$i]->item->getGroup();
            if (($ass[$i]->item->group) && ($ass[$i]->item->group->category) && ($ass[$i]->item->group->category->categoryid == $search)) {
                if (($search <> 6) || (($search == 6) && ($ass[$i]->item->groupid <> 31))) {     // nasty way to filter shuttles from the ships list
                    $result[] = $ass[$i];
                }
            }
            if ($ass[$i]->contents) {
                $result = array_merge($result, $this->searchAssetCategory($ass[$i]->contents, $search));
            }
        }

        return $result;
    }

}

function assetStationSort($a, $b) {
    if ($a['locationName'] == $b['locationName']) {
        return 0;
    }
    return ($a['locationName'] < $b['locationName']) ? -1 : 1;
}

function assetSlotSort($a, $b) {
    if ($a->flagText == $b->flagText) {
        return 0;
    }
    return ($a->flagText < $b->flagText) ? -1 : 1;
}

function assetNameSort($a, $b) {
    if ($a->item->typename == $b->item->typename) {
        return 0;
    }
    return ($a->item->typename < $b->item->typename) ? -1 : 1;
}

function groupNameSort($a, $b) {
    if ($a->groupname == $b->groupname) {
        return 0;
    }
    return ($a->groupname < $b->groupname) ? -1 : 1;
}

?>
