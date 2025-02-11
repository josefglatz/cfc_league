<?php

namespace System25\T3sports\Controller;

use Sys25\RnBase\Backend\Module\BaseModFunc;
use Sys25\RnBase\Backend\Module\IModFunc;
use Sys25\RnBase\Backend\Utility\BackendUtility;
use Sys25\RnBase\Backend\Utility\Tables;
use Sys25\RnBase\Database\Connection;
use Sys25\RnBase\Utility\T3General;
use System25\T3sports\Controller\Profile\ProfileMerger;
use System25\T3sports\Controller\Profile\ShowItem;
use tx_rnbase;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2021 Rene Nitzsche (rene@system25.de)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Die Klasse ermöglicht die Suche von Profilen unabhängig vom Seitenbaum.
 * Das Modul wurde relativ schnell runterprogrammiert und ist daher nicht auf Erweiterbarkeit
 * ausgelegt.
 */
class Profile extends BaseModFunc
{
    public $doc;

    public $MCONF;

    /**
     * Verstecken der Suchergebnisse.
     */
    public $hideResults = false;

    /**
     * Method getFuncId.
     *
     * @return string
     */
    public function getFuncId()
    {
        return 'functicker';
    }

    protected function getContent($template, &$configurations, &$formatter, $formTool)
    {
        global $LANG;
        $content = '';

        $this->doc = $this->getModule()->getDoc();
        $this->formTool = $this->getModule()->getFormTool();

        // Selector-Instanz bereitstellen
        $this->selector = tx_rnbase::makeInstance('tx_cfcleague_selector');
        $this->selector->init($this->getModule()->getDoc(), $this->getModule());

        $data = T3General::_GP('data');
        $this->SEARCH_SETTINGS = BackendUtility::getModuleData([
            'searchterm' => '',
        ], $data, $this->getModule()->getName());

        $content .= $this->doc->section($LANG->getLL('msg_search_person'), $this->createSearchForm($data), 0, 1);

        // Haben wir Daten im Request?
        if (is_array($data)) {
            // Soll ein Profil bearbeitet werden?
            $content .= $this->handleProfileUpdate($data);
            $content .= $this->handleProfileMerge($data);
        }
        // Wir zeigen die Liste an
        if (!$this->hideResults) {
            $searchterm = trim($this->SEARCH_SETTINGS['searchterm']);
            if (strlen($searchterm) && strlen($searchterm) < 3) {
                $content .= $this->doc->section($LANG->getLL('message').':', $LANG->getLL('msg_string_too_short'), 0, 1, IModFunc::ICON_INFO);
            } elseif (strlen($searchterm) >= 3) {
                $profiles = $this->searchProfiles($searchterm);
                if (!empty($profiles)) {
//                    $content .= $this->doc->section($LANG->getLL('msg_found_person'), $this->buildProfileTable($profiles), 0, 1);
                    $content .= $this->buildProfileTable($profiles);
                } else {
                    $content .= $this->doc->section($LANG->getLL('msg_no_person_found'), '', 0, 1, IModFunc::ICON_WARN);
                }
            }
        }

        return $content;
    }

    /**
     * Zusammenführung von zwei Profilen.
     *
     * @param array $data
     */
    protected function handleProfileMerge(&$data)
    {
        global $LANG;
        $profile1 = (int) $data['merge1'];
        $profile2 = (int) $data['merge2'];
        if ($data['merge_profiles']) { // Step 1
            if (!($profile1 && $profile2) || ($profile1 == $profile2)) {
                return $this->doc->section($LANG->getLL('msg_merge_selectprofiles'), '', 0, 1, IModFunc::ICON_FATAL);
            }
            $this->hideResults = true;
            // Beide Profile nochmal anzeigen
            // Das führende Profile muss ausgewählt werden
            $out .= $LANG->getLL('msg_merge_selectprofile');
            $out .= $this->createProfileMergeForm($profile1, $profile2);
            $out = $this->doc->section($LANG->getLL('label_mergehead'), $out, 0, 1);
        } elseif ($data['merge_profiles_do']) { // Step 2
            $leading = intval($data['merge']);

            $merger = tx_rnbase::makeInstance(ProfileMerger::class);
            $merger->merge($leading, $leading == $profile1 ? $profile2 : $profile1);

            $out .= $this->doc->section($LANG->getLL('msg_merge_done'), '', 0, 1, IModFunc::ICON_OK);
        }

        return $out;
    }

    /**
     * Erstellt das Form für den Abgleich zweier Personen.
     * Der Nutzer muss das führende
     * Profil auswählen.
     *
     * @param int $uid1
     * @param int $uid2
     */
    protected function createProfileMergeForm($uid1, $uid2)
    {
        global $LANG;
        $out .= '<div class="row">';

        /* @var $info ShowItem */
        $info = tx_rnbase::makeInstance(ShowItem::class);

        $out .= '<div class="col-xs-6">';
        $out .= $this->formTool->createRadio('data[merge]', $uid1, true);
        $out .= $info->getInfoScreen('tx_cfcleague_profiles', $uid1);
        $out .= $this->formTool->createHidden('data[merge1]', $uid1);

        $out .= '</div>';

        $out .= '<div class="col-xs-6">';
        $out .= $this->formTool->createRadio('data[merge]', $uid2);
        $out .= $info->getInfoScreen('tx_cfcleague_profiles', $uid2);
        $out .= $this->formTool->createHidden('data[merge2]', $uid2);
        $out .= '</div>';

        $out .= '</div>';
        $out .= $this->formTool->createSubmit('data[merge_profiles_do]', $LANG->getLL('label_merge'), $LANG->getLL('msg_merge_confirm'));

        return $out;
    }

    /**
     * Bearbeitet das interne Eingabeformular zu einer Person.
     * Derzeit kann das Geburtsdatum
     * gesetzt werden.
     */
    protected function handleProfileUpdate(&$data)
    {
        global $LANG;
        $out = '';
        // Soll das Edit-Formular gezeigt werden?
        if ($data['edit_profile']) {
            $this->hideResults = true;
            $uids = array_keys($data['edit_profile']);

            $profiles = $this->searchProfiles($data, $uids[0]);
            if (empty($profiles)) {
                $out .= $this->doc->section($LANG->getLL('msg_edit_person'), 'Internal error. Sorry no profile found!', 0, 1, IModFunc::ICON_FATAL);
            } else {
                $out .= $this->doc->section($LANG->getLL('msg_edit_person'), $this->showProfileForm($profiles[0]), 0, 1);
            }
        } elseif ($data['update_profile_do']) { // Wurde der Speichern-Button gedrückt?
            // Das Datum prüfen
            $out .= $this->updateProfiles($data['update_profile']);
        }

        return $out;
    }

    /**
     * Aktualisiert die übergebenen Profile.
     * Derzeit wird nur das Geburtsdatum gespeichert.
     */
    protected function updateProfiles($profiles)
    {
        global $LANG;
        $out = '';
        foreach ($profiles as $uid => $profile) {
            // Zuerst das Datum prüfen und umwandeln
            $date = $profile['birthday'];
            list($day, $month, $year) = explode('.', $date);
            if (!checkdate($month, $day, $year)) {
                $out .= $this->doc->section($LANG->getLL('msg_person_saved'), ' Invalid date -'.$date.'- für UID: '.$uid, 0, 1, IModFunc::ICON_FATAL);
            } else {
                // Das ist eher problematisch. Das Datum sollte in GMT gespeichert werden
                $values = [
                    'birthday' => mktime(0, 0, 0, $month, $day, $year),
                ];
                Connection::getInstance()->doUpdate('tx_cfcleague_profiles', 'uid='.intval($uid), $values);
                $out .= $this->doc->section($LANG->getLL('msg_person_saved'), $LANG->getLL('msg_date_saved').': '.$date, 0, 1, IModFunc::ICON_OK);
            }
        }

        return $out;
    }

    /**
     * Anzeige der Bearbeitungsmaske für ein Profil.
     * Hier kann das Geburtsdatum der Person
     * geändert werden. Es sind auch Werte vor 1970 möglich.
     */
    protected function showProfileForm($profile)
    {
        global $LANG;
        $out = '';
        // Jetzt das Formular anzeigen
        $out .= $profile['last_name'];
        if ($profile['first_name']) {
            $out .= ', '.$profile['first_name'];
        }
        $out .= ' [UID: '.$profile['uid'].'] ';
        $out .= $this->formTool->createTxtInput('data[update_profile]['.$profile['uid'].'][birthday]', date('j.n.Y', $profile['birthday']), 10);
        $out .= ' <input type="submit" name="data[update_profile_do]" value="'.$LANG->getLL('btn_save').'"';

        return $out;
    }

    /**
     * Sucht die Profile mit den übergebenen Parametern.
     * Entweder wird über
     * Vor- und Zuname gesucht, oder man übergibt direkt eine UID.
     */
    protected function searchProfiles($searchterm, $uid = 0)
    {
        $what = 'tx_cfcleague_profiles.uid,tx_cfcleague_profiles.pid,'.'last_name, first_name,birthday, '."t1.short_name as 'team_name', t1.uid as 'team_uid'";

        $from = [
            'tx_cfcleague_profiles '.'LEFT JOIN tx_cfcleague_teams AS t1 ON FIND_IN_SET(tx_cfcleague_profiles.uid, t1.players) ',
            'tx_cfcleague_profiles',
        ];

        $where = '';
        if ($uid) {
            $where .= 'tx_cfcleague_profiles.uid = '.intval($uid).' ';
        } else {
            if (strlen($searchterm)) {
                $where .= "(last_name like '%".$searchterm."%' ";
                $where .= "OR first_name like '%".$searchterm."%') ";
            }
        }
        $orderBy = 'last_name, first_name, tx_cfcleague_profiles.uid';

        $rows = Connection::getInstance()->doSelect($what, $from, [
            'where' => $where,
            'orderby' => $orderBy,
        ]);
        $cnt = count($rows);
        if (!$cnt) {
            return $rows; // Keine Daten gefunden
        }

        // Für jedes Team in dem die Person zugeordnet ist, erhalten wir eine Zeile
        // Diese müssen wir jetzt wieder zusammenfügen
        $lastRow = $rows[0];
        $ret = [];
        for ($i = 0; $i < $cnt; ++$i) {
            if (intval($lastRow['uid']) != intval($rows[$i]['uid'])) {
                // Ein neuer Spieler, also den alten ins Ergebnisarray legen
                $ret[] = $lastRow;
                $lastRow = $rows[$i];
            }
            // Den Verein der aktuellen Row in die Liste der lastRow legen
            if ($rows[$i]['team_uid']) {
                $lastRow['teams'][] = [
                    'team_uid' => $rows[$i]['team_uid'],
                    'team_name' => $rows[$i]['team_name'],
                ];
            }
        }
        // Das letzte Profil noch ins Ergebnisarray legen
        $ret[] = $lastRow;

        return $ret;
    }

    /**
     * Erstellt eine Tabelle mit den gefundenen Personen.
     */
    protected function buildProfileTable(&$profiles)
    {
        global $LANG;

        $out = '';
        $arr = [
            [
                $LANG->getLL('label_merge'),
                'UID',
                $LANG->getLL('label_lastname'),
                $LANG->getLL('label_firstname'),
                $LANG->getLL('label_birthday'),
                $LANG->getLL('label_information'),
                '&nbsp;',
                '&nbsp;',
                '&nbsp;',
                '&nbsp;',
            ],
        ];
        foreach ($profiles as $profile) {
            $row = [];
            $row[] = $this->formTool->createRadio('data[merge1]', $profile['uid']).$this->formTool->createRadio('data[merge2]', $profile['uid']);
            $row[] = $profile['uid'];
            $row[] = $profile['last_name'];
            $row[] = $profile['first_name'] ? $profile['first_name'] : '&nbsp;';
            $row[] = date('j.n.Y', $profile['birthday']).' <input type="submit" name="data[edit_profile]['.$profile['uid'].']" value="'.$LANG->getLL('btn_edit').'"';
            // Die Zusatzinfos zusammenstellen
            $infos = $LANG->getLL('label_page').': '.BackendUtility::getRecordPath($profile['pid'], '', 0).'<br />';
            if (is_array($profile['teams'])) {
                foreach ($profile['teams'] as $team) {
                    $infos .= '&nbsp;Team: '.$team['team_name'];
                    $infos .= $this->formTool->createEditLink('tx_cfcleague_teams', $team['team_uid'], '').'<br />';
                }
            }

            $row[] = $infos;
            $row[] = $this->formTool->createEditLink('tx_cfcleague_profiles', $profile['uid'], '');
            $row[] = $this->formTool->createInfoLink('tx_cfcleague_profiles', $profile['uid'], '');
            $row[] = $this->formTool->createHistoryLink('tx_cfcleague_profiles', $profile['uid']);
            $row[] = $this->formTool->createMoveLink('tx_cfcleague_profiles', $profile['uid'], $profile['pid']);
            $arr[] = $row;
        }

        $tables = tx_rnbase::makeInstance(Tables::class);
        $out .= $tables->buildTable($arr);
        if (count($arr)) {
            // Button für Merge einbauen
            $out .= $this->getModule()
                ->getFormTool()
                ->createSubmit('data[merge_profiles]', $LANG->getLL('label_merge'));
        }

        return $out;
    }

    protected function createSearchForm(&$data)
    {
        global $LANG;
        $out = '<div class="form-inline">';
        $out .= $LANG->getLL('label_searchterm').': ';
        $out .= $this->formTool->createTxtInput('data[searchterm]', $this->SEARCH_SETTINGS['searchterm'], 20, [
            'class' => 'form-control input-sm',
        ]);
        // Den Update-Button einfügen
        $out .= $this->formTool->createSubmit('search', $LANG->getLL('btn_search'));
        $out .= '</div>';

        return $out;
    }
}
