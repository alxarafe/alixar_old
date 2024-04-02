<?php

/* Copyright (C) 2003       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2019  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2024       Rafael San José         <rsanjose@alxarafe.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace DoliModules\Adherent\Controller;

global $conf;
global $db;
global $user;
global $hookmanager;
global $user;
global $menumanager;
global $langs;

require BASE_PATH . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT . '/adherents/class/adherentstats.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/dolgraph.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/member.lib.php';

use DoliCore\Base\DolibarrController;

class AdherentStatsController extends DolibarrController
{
    /**
     *      \file       htdocs/adherents/stats/byproperties.php
     *      \ingroup    member
     *      \brief      Page with statistics on members
     */
    public function byproperties()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;
        global $mysoc;

        $graphwidth = 700;
        $mapratio = 0.5;
        $graphheight = round($graphwidth * $mapratio);

        $mode = GETPOST('mode') ? GETPOST('mode') : '';


// Security check
        if ($user->socid > 0) {
            $action = '';
            $socid = $user->socid;
        }
        $result = restrictedArea($user, 'adherent', '', '', 'cotisation');

        $year = dol_print_date(dol_now('gmt'), "%Y", 'gmt');
        $startyear = $year - (!getDolGlobalString('MAIN_STATS_GRAPHS_SHOW_N_YEARS') ? 2 : max(1, min(10, getDolGlobalString('MAIN_STATS_GRAPHS_SHOW_N_YEARS'))));
        $endyear = $year;

// Load translation files required by the page
        $langs->loadLangs(["companies", "members"]);


        /*
         * View
         */

        $memberstatic = new Adherent($db);

        llxHeader('', $langs->trans("MembersStatisticsByProperties"), '', '', 0, 0, ['https://www.google.com/jsapi']);

        $title = $langs->trans("MembersStatisticsByProperties");

        print load_fiche_titre($title, '', $memberstatic->picto);

//dol_mkdir($dir);

        $data = [];

        $sql = "SELECT COUNT(DISTINCT d.rowid) as nb, COUNT(s.rowid) as nbsubscriptions,";
        $sql .= " MAX(d.datevalid) as lastdate, MAX(s.dateadh) as lastsubscriptiondate,";
        $sql .= " d.morphy as code";
        $sql .= " FROM " . MAIN_DB_PREFIX . "adherent as d";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "subscription as s ON s.fk_adherent = d.rowid";
        $sql .= " WHERE d.entity IN (" . getEntity('adherent') . ")";
        $sql .= " AND d.statut <> " . Adherent::STATUS_DRAFT;
        $sql .= " GROUP BY d.morphy";
        $foundphy = $foundmor = 0;

// Define $data array
        dol_syslog("Count member", LOG_DEBUG);
        $resql = $db->query($sql);
        if ($resql) {
            $num = $db->num_rows($resql);
            $i = 0;
            while ($i < $num) {
                $obj = $db->fetch_object($resql);

                if ($obj->code == 'phy') {
                    $foundphy++;
                }
                if ($obj->code == 'mor') {
                    $foundmor++;
                }

                $data[$obj->code] = ['label' => $obj->code, 'nb' => $obj->nb, 'nbsubscriptions' => $obj->nbsubscriptions, 'lastdate' => $db->jdate($obj->lastdate), 'lastsubscriptiondate' => $db->jdate($obj->lastsubscriptiondate)];

                $i++;
            }
            $db->free($resql);
        } else {
            dol_print_error($db);
        }

        $sql = "SELECT COUNT(DISTINCT d.rowid) as nb, COUNT(s.rowid) as nbsubscriptions,";
        $sql .= " MAX(d.datevalid) as lastdate, MAX(s.dateadh) as lastsubscriptiondate,";
        $sql .= " d.morphy as code";
        $sql .= " FROM " . MAIN_DB_PREFIX . "adherent as d";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "subscription as s ON s.fk_adherent = d.rowid";
        $sql .= " WHERE d.entity IN (" . getEntity('adherent') . ")";
        $sql .= " AND d.statut >= 1"; // Active (not excluded=-2, not draft=-1, not resiliated=0)
        $sql .= " GROUP BY d.morphy";
        $foundphy = $foundmor = 0;

// Define $data array
        dol_syslog("Count member still active", LOG_DEBUG);
        $resql = $db->query($sql);
        if ($resql) {
            $num = $db->num_rows($resql);
            $i = 0;
            while ($i < $num) {
                $obj = $db->fetch_object($resql);

                if ($obj->code == 'phy') {
                    $foundphy++;
                }
                if ($obj->code == 'mor') {
                    $foundmor++;
                }

                $data[$obj->code]['nbactive'] = $obj->nb;

                $i++;
            }
            $db->free($resql);
        } else {
            dol_print_error($db);
        }


        $head = member_stats_prepare_head($memberstatic);

        print dol_get_fiche_head($head, 'statsbyproperties', '', -1, '');


// Print title
        if (!count($data)) {
            print '<span class="opacitymedium">' . $langs->trans("NoValidatedMemberYet") . '</span><br>';
            print '<br>';
        } else {
            print '<span class="opacitymedium">' . $langs->trans("MembersByNature") . '</span><br>';
            print '<br>';
        }

// Print array
        print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
        print '<table class="liste centpercent">';
        print '<tr class="liste_titre">';
        print '<td>' . $langs->trans("MemberNature") . '</td>';
        print '<td class="right">' . $langs->trans("NbOfMembers") . ' <span class="opacitymedium">(' . $langs->trans("AllTime") . ')</span></td>';
        print '<td class="right">' . $langs->trans("NbOfActiveMembers") . '</td>';
        print '<td class="center">' . $langs->trans("LastMemberDate") . '</td>';
        print '<td class="right">' . $langs->trans("NbOfSubscriptions") . '</td>';
        print '<td class="center">' . $langs->trans("LatestSubscriptionDate") . '</td>';
        print '</tr>';

        if (!$foundphy) {
            $data[] = ['label' => 'phy', 'nb' => '0', 'nbactive' => '0', 'lastdate' => '', 'lastsubscriptiondate' => ''];
        }
        if (!$foundmor) {
            $data[] = ['label' => 'mor', 'nb' => '0', 'nbactive' => '0', 'lastdate' => '', 'lastsubscriptiondate' => ''];
        }

        foreach ($data as $val) {
            $nb = $val['nb'];
            $nbsubscriptions = isset($val['nbsubscriptions']) ? $val['nbsubscriptions'] : 0;
            $nbactive = $val['nbactive'];

            print '<tr class="oddeven">';
            print '<td>' . $memberstatic->getmorphylib($val['label']) . '</td>';
            print '<td class="right">' . $nb . '</td>';
            print '<td class="right">' . $nbactive . '</td>';
            print '<td class="center">' . dol_print_date($val['lastdate'], 'dayhour') . '</td>';
            print '<td class="right">' . $nbsubscriptions . '</td>';
            print '<td class="center">' . dol_print_date($val['lastsubscriptiondate'], 'dayhour') . '</td>';
            print '</tr>';
        }

        print '</table>';
        print '</div>';

        print dol_get_fiche_end();

// End of page
        llxFooter();
        $db->close();
    }

    /**
     *      \file       htdocs/adherents/stats/geo.php
     *      \ingroup    member
     *      \brief      Page with geographical statistics on members
     */
    public function geo()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;
        global $mysoc;

        $graphwidth = DolGraph::getDefaultGraphSizeForStats('width', 700);
        $mapratio = 0.5;
        $graphheight = round($graphwidth * $mapratio);

        $mode = GETPOST('mode') ? GETPOST('mode') : '';


// Security check
        if ($user->socid > 0) {
            $action = '';
            $socid = $user->socid;
        }
        $result = restrictedArea($user, 'adherent', '', '', 'cotisation');

        $year = dol_print_date(dol_now('gmt'), "%Y", 'gmt');
        $startyear = $year - (!getDolGlobalString('MAIN_STATS_GRAPHS_SHOW_N_YEARS') ? 2 : max(1, min(10, getDolGlobalString('MAIN_STATS_GRAPHS_SHOW_N_YEARS'))));
        $endyear = $year;

// Load translation files required by the page
        $langs->loadLangs(["companies", "members", "banks"]);


        /*
         * View
         */

        $memberstatic = new Adherent($db);

        $arrayjs = ['https://www.google.com/jsapi'];
        if (!empty($conf->dol_use_jmobile)) {
            $arrayjs = [];
        }

        $title = $langs->trans("Statistics");
        if ($mode == 'memberbycountry') {
            $title = $langs->trans("MembersStatisticsByCountries");
        }
        if ($mode == 'memberbystate') {
            $title = $langs->trans("MembersStatisticsByState");
        }
        if ($mode == 'memberbytown') {
            $title = $langs->trans("MembersStatisticsByTown");
        }
        if ($mode == 'memberbyregion') {
            $title = $langs->trans("MembersStatisticsByRegion");
        }

        llxHeader('', $title, '', '', 0, 0, $arrayjs);

        print load_fiche_titre($title, '', $memberstatic->picto);

//dol_mkdir($dir);

        if ($mode) {
            // Define sql
            if ($mode == 'memberbycountry') {
                $label = $langs->trans("Country");
                $tab = 'statscountry';

                $data = [];
                $sql = "SELECT COUNT(DISTINCT d.rowid) as nb, COUNT(s.rowid) as nbsubscriptions, MAX(d.datevalid) as lastdate, MAX(s.dateadh) as lastsubscriptiondate, c.code, c.label";
                $sql .= " FROM " . MAIN_DB_PREFIX . "adherent as d";
                $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_country as c on d.country = c.rowid";
                $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "subscription as s ON s.fk_adherent = d.rowid";
                $sql .= " WHERE d.entity IN (" . getEntity('adherent') . ")";
                $sql .= " AND d.statut <> " . Adherent::STATUS_DRAFT;
                $sql .= " GROUP BY c.label, c.code";
                //print $sql;
            }

            if ($mode == 'memberbystate') {
                $label = $langs->trans("Country");
                $label2 = $langs->trans("State");
                $tab = 'statsstate';

                $data = [];
                $sql = "SELECT COUNT(DISTINCT d.rowid) as nb, COUNT(s.rowid) as nbsubscriptions, MAX(d.datevalid) as lastdate, MAX(s.dateadh) as lastsubscriptiondate, co.code, co.label, c.nom as label2"; //
                $sql .= " FROM " . MAIN_DB_PREFIX . "adherent as d";
                $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_departements as c on d.state_id = c.rowid";
                $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_regions as r on c.fk_region = r.code_region";
                $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_country as co on d.country = co.rowid";
                $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "subscription as s ON s.fk_adherent = d.rowid";
                $sql .= " WHERE d.entity IN (" . getEntity('adherent') . ")";
                $sql .= " AND d.statut <> " . Adherent::STATUS_DRAFT;
                $sql .= " GROUP BY co.label, co.code, c.nom";
                //print $sql;
            }
            if ($mode == 'memberbyregion') { //
                $label = $langs->trans("Country");
                $label2 = $langs->trans("Region"); //département
                $tab = 'statsregion'; //onglet

                $data = []; //tableau de donnée
                $sql = "SELECT COUNT(DISTINCT d.rowid) as nb, COUNT(s.rowid) as nbsubscriptions, MAX(d.datevalid) as lastdate, MAX(s.dateadh) as lastsubscriptiondate, co.code, co.label, r.nom as label2";
                $sql .= " FROM " . MAIN_DB_PREFIX . "adherent as d";
                $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_departements as c on d.state_id = c.rowid";
                $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_regions as r on c.fk_region = r.code_region";
                $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_country as co on d.country = co.rowid";
                $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "subscription as s ON s.fk_adherent = d.rowid";
                $sql .= " WHERE d.entity IN (" . getEntity('adherent') . ")";
                $sql .= " AND d.statut <> " . Adherent::STATUS_DRAFT;
                $sql .= " GROUP BY co.label, co.code, r.nom"; //+
                //print $sql;
            }
            if ($mode == 'memberbytown') {
                $label = $langs->trans("Country");
                $label2 = $langs->trans("Town");
                $tab = 'statstown';

                $data = [];
                $sql = "SELECT COUNT(DISTINCT d.rowid) as nb, COUNT(s.rowid) as nbsubscriptions, MAX(d.datevalid) as lastdate, MAX(s.dateadh) as lastsubscriptiondate, c.code, c.label, d.town as label2";
                $sql .= " FROM " . MAIN_DB_PREFIX . "adherent as d";
                $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_country as c on d.country = c.rowid";
                $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "subscription as s ON s.fk_adherent = d.rowid";
                $sql .= " WHERE d.entity IN (" . getEntity('adherent') . ")";
                $sql .= " AND d.statut <> " . Adherent::STATUS_DRAFT;
                $sql .= " GROUP BY c.label, c.code, d.town";
                //print $sql;
            }

            $langsen = new Translate('', $conf);
            $langsen->setDefaultLang('en_US');
            $langsen->load("dict");
            //print $langsen->trans("Country"."FI");exit;

            // Define $data array
            dol_syslog("Count member", LOG_DEBUG);
            $resql = $db->query($sql);
            if ($resql) {
                $num = $db->num_rows($resql);
                $i = 0;
                while ($i < $num) {
                    $obj = $db->fetch_object($resql);
                    if ($mode == 'memberbycountry') {
                        $data[] = [
                            'label' => (($obj->code && $langs->trans("Country" . $obj->code) != "Country" . $obj->code) ? img_picto('', DOL_URL_ROOT . '/theme/common/flags/' . strtolower($obj->code) . '.png', '', 1) . ' ' . $langs->trans("Country" . $obj->code) : ($obj->label ? $obj->label : '<span class="opacitymedium">' . $langs->trans("Unknown") . '</span>')),
                            'label_en' => (($obj->code && $langsen->transnoentitiesnoconv("Country" . $obj->code) != "Country" . $obj->code) ? $langsen->transnoentitiesnoconv("Country" . $obj->code) : ($obj->label ? $obj->label : '<span class="opacitymedium">' . $langs->trans("Unknown") . '</span>')),
                            'code' => $obj->code,
                            'nb' => $obj->nb,
                            'lastdate' => $db->jdate($obj->lastdate),
                            'lastsubscriptiondate' => $db->jdate($obj->lastsubscriptiondate),
                        ];
                    }
                    if ($mode == 'memberbyregion') { //+
                        $data[] = [
                            'label' => (($obj->code && $langs->trans("Country" . $obj->code) != "Country" . $obj->code) ? img_picto('', DOL_URL_ROOT . '/theme/common/flags/' . strtolower($obj->code) . '.png', '', 1) . ' ' . $langs->trans("Country" . $obj->code) : ($obj->label ? $obj->label : '<span class="opacitymedium">' . $langs->trans("Unknown") . '</span>')),
                            'label_en' => (($obj->code && $langsen->transnoentitiesnoconv("Country" . $obj->code) != "Country" . $obj->code) ? $langsen->transnoentitiesnoconv("Country" . $obj->code) : ($obj->label ? $obj->label : '<span class="opacitymedium">' . $langs->trans("Unknown") . '</span>')),
                            'label2' => ($obj->label2 ? $obj->label2 : '<span class="opacitymedium">' . $langs->trans("Unknown") . '</span>'),
                            'nb' => $obj->nb,
                            'lastdate' => $db->jdate($obj->lastdate),
                            'lastsubscriptiondate' => $db->jdate($obj->lastsubscriptiondate),
                        ];
                    }
                    if ($mode == 'memberbystate') {
                        $data[] = [
                            'label' => (($obj->code && $langs->trans("Country" . $obj->code) != "Country" . $obj->code) ? img_picto('', DOL_URL_ROOT . '/theme/common/flags/' . strtolower($obj->code) . '.png', '', 1) . ' ' . $langs->trans("Country" . $obj->code) : ($obj->label ? $obj->label : '<span class="opacitymedium">' . $langs->trans("Unknown") . '</span>')),
                            'label_en' => (($obj->code && $langsen->transnoentitiesnoconv("Country" . $obj->code) != "Country" . $obj->code) ? $langsen->transnoentitiesnoconv("Country" . $obj->code) : ($obj->label ? $obj->label : '<span class="opacitymedium">' . $langs->trans("Unknown") . '</span>')),
                            'label2' => ($obj->label2 ? $obj->label2 : '<span class="opacitymedium">' . $langs->trans("Unknown") . '</span>'),
                            'nb' => $obj->nb,
                            'lastdate' => $db->jdate($obj->lastdate),
                            'lastsubscriptiondate' => $db->jdate($obj->lastsubscriptiondate),
                        ];
                    }
                    if ($mode == 'memberbytown') {
                        $data[] = [
                            'label' => (($obj->code && $langs->trans("Country" . $obj->code) != "Country" . $obj->code) ? img_picto('', DOL_URL_ROOT . '/theme/common/flags/' . strtolower($obj->code) . '.png', '', 1) . ' ' . $langs->trans("Country" . $obj->code) : ($obj->label ? $obj->label : '<span class="opacitymedium">' . $langs->trans("Unknown") . '</span>')),
                            'label_en' => (($obj->code && $langsen->transnoentitiesnoconv("Country" . $obj->code) != "Country" . $obj->code) ? $langsen->transnoentitiesnoconv("Country" . $obj->code) : ($obj->label ? $obj->label : '<span class="opacitymedium">' . $langs->trans("Unknown") . '</span>')),
                            'label2' => ($obj->label2 ? $obj->label2 : '<span class="opacitymedium">' . $langs->trans("Unknown") . '</span>'),
                            'nb' => $obj->nb,
                            'lastdate' => $db->jdate($obj->lastdate),
                            'lastsubscriptiondate' => $db->jdate($obj->lastsubscriptiondate),
                        ];
                    }

                    $i++;
                }
                $db->free($resql);
            } else {
                dol_print_error($db);
            }
        }


        $head = member_stats_prepare_head($memberstatic);

        print dol_get_fiche_head($head, $tab, '', -1, '');


// Print title
        if ($mode && !count($data)) {
            print $langs->trans("NoValidatedMemberYet") . '<br>';
            print '<br>';
        } else {
            if ($mode == 'memberbycountry') {
                print '<span class="opacitymedium">' . $langs->trans("MembersByCountryDesc") . '</span><br>';
            } elseif ($mode == 'memberbystate') {
                print '<span class="opacitymedium">' . $langs->trans("MembersByStateDesc") . '</span><br>';
            } elseif ($mode == 'memberbytown') {
                print '<span class="opacitymedium">' . $langs->trans("MembersByTownDesc") . '</span><br>';
            } elseif ($mode == 'memberbyregion') {
                print '<span class="opacitymedium">' . $langs->trans("MembersByRegion") . '</span><br>'; //+
            } else {
                print '<span class="opacitymedium">' . $langs->trans("MembersStatisticsDesc") . '</span><br>';
                print '<br>';
                print '<a href="' . $_SERVER['PHP_SELF'] . '?mode=memberbycountry">' . $langs->trans("MembersStatisticsByCountries") . '</a><br>';
                print '<br>';
                print '<a href="' . $_SERVER['PHP_SELF'] . '?mode=memberbystate">' . $langs->trans("MembersStatisticsByState") . '</a><br>';
                print '<br>';
                print '<a href="' . $_SERVER['PHP_SELF'] . '?mode=memberbytown">' . $langs->trans("MembersStatisticsByTown") . '</a><br>';
                print '<br>'; //+
                print '<a href="' . $_SERVER['PHP_SELF'] . '?mode=memberbyregion">' . $langs->trans("MembersStatisticsByRegion") . '</a><br>'; //+
            }
            print '<br>';
        }


// Show graphics
        if (count($arrayjs) && $mode == 'memberbycountry') {
            $color_file = DOL_DOCUMENT_ROOT . '/theme/' . $conf->theme . '/theme_vars.inc.php';
            if (is_readable($color_file)) {
                include $color_file;
            }

            // Assume we've already included the proper headers so just call our script inline
            // More doc: https://developers.google.com/chart/interactive/docs/gallery/geomap?hl=fr-FR
            print "\n<script type='text/javascript'>\n";
            print "google.load('visualization', '1', {'packages': ['geomap']});\n";
            print "google.setOnLoadCallback(drawMap);\n";
            print "function drawMap() {\n\tvar data = new google.visualization.DataTable();\n";

            // Get the total number of rows
            print "\tdata.addRows(" . count($data) . ");\n";
            print "\tdata.addColumn('string', 'Country');\n";
            print "\tdata.addColumn('number', 'Number');\n";

            // loop and dump
            $i = 0;
            foreach ($data as $val) {
                $valcountry = strtoupper($val['code']); // Should be ISO-3166 code (faster)
                //$valcountry=ucfirst($val['label_en']);
                if ($valcountry == 'Great Britain') {
                    $valcountry = 'United Kingdom';
                }    // fix case of uk (when we use labels)
                print "\tdata.setValue(" . $i . ", 0, \"" . $valcountry . "\");\n";
                print "\tdata.setValue(" . $i . ", 1, " . $val['nb'] . ");\n";
                // Google's Geomap only supports up to 400 entries
                if ($i >= 400) {
                    break;
                }
                $i++;
            }

            print "\tvar options = {};\n";
            print "\toptions['dataMode'] = 'regions';\n";
            print "\toptions['showZoomOut'] = false;\n";
            //print "\toptions['zoomOutLabel'] = '".dol_escape_js($langs->transnoentitiesnoconv("Numbers"))."';\n";
            print "\toptions['width'] = " . $graphwidth . ";\n";
            print "\toptions['height'] = " . $graphheight . ";\n";
            print "\toptions['colors'] = [0x" . colorArrayToHex($theme_datacolor[1], 'BBBBBB') . ", 0x" . colorArrayToHex($theme_datacolor[0], '444444') . "];\n";
            print "\tvar container = document.getElementById('" . $mode . "');\n";
            print "\tvar geomap = new google.visualization.GeoMap(container);\n";
            print "\tgeomap.draw(data, options);\n";
            print "}\n";
            print "</script>\n";

            // print the div tag that will contain the map
            print '<div class="center" id="' . $mode . '"></div>' . "\n";
        }

        if ($mode) {
            // Print array
            print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
            print '<table class="liste centpercent">';
            print '<tr class="liste_titre">';
            print '<td>' . $label . '</td>';
            if (isset($label2)) {
                print '<td class="center">' . $label2 . '</td>';
            }
            print '<td class="right">' . $langs->trans("NbOfMembers") . ' <span class="opacitymedium">(' . $langs->trans("AllTime") . ')</span></td>';
            print '<td class="center">' . $langs->trans("LastMemberDate") . '</td>';
            print '<td class="center">' . $langs->trans("LatestSubscriptionDate") . '</td>';
            print '</tr>';

            foreach ($data as $val) {
                $year = isset($val['year']) ? $val['year'] : '';
                print '<tr class="oddeven">';
                print '<td>' . $val['label'] . '</td>';
                if (isset($label2)) {
                    print '<td class="center">' . $val['label2'] . '</td>';
                }
                print '<td class="right">' . $val['nb'] . '</td>';
                print '<td class="center">' . dol_print_date($val['lastdate'], 'dayhour') . '</td>';
                print '<td class="center">' . dol_print_date($val['lastsubscriptiondate'], 'dayhour') . '</td>';
                print '</tr>';
            }

            print '</table>';
            print '</div>';
        }


        print dol_get_fiche_end();

// End of page
        llxFooter();
        $db->close();
    }

    /**
     *      \file       htdocs/adherents/stats/index.php
     *      \ingroup    member
     *      \brief      Page of subscription members statistics
     */
    public function index()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;
        global $mysoc;

        $WIDTH = DolGraph::getDefaultGraphSizeForStats('width');
        $HEIGHT = DolGraph::getDefaultGraphSizeForStats('height');

        $userid = GETPOSTINT('userid');
        if ($userid < 0) {
            $userid = 0;
        }
        $socid = GETPOSTINT('socid');
        if ($socid < 0) {
            $socid = 0;
        }

// Security check
        if ($user->socid > 0) {
            $action = '';
            $socid = $user->socid;
        }
        $result = restrictedArea($user, 'adherent', '', '', 'cotisation');

        $year = dol_print_date(dol_now('gmt'), "%Y", 'gmt');
        $startyear = $year - (!getDolGlobalInt('MAIN_STATS_GRAPHS_SHOW_N_YEARS') ? 2 : max(1, min(10, getDolGlobalInt('MAIN_STATS_GRAPHS_SHOW_N_YEARS'))));
        $endyear = $year;
        if (getDolGlobalString('MEMBER_SUBSCRIPTION_START_AFTER')) {
            $endyear = dol_print_date(dol_time_plus_duree(dol_now('gmt'), (int) substr(getDolGlobalString('MEMBER_SUBSCRIPTION_START_AFTER'), 0, -1), substr(getDolGlobalString('MEMBER_SUBSCRIPTION_START_AFTER'), -1)), "%Y", 'gmt');
        }

// Load translation files required by the page
        $langs->loadLangs(["companies", "members"]);


        /*
         * View
         */

        $memberstatic = new Adherent($db);
        $form = new Form($db);

        $title = $langs->trans("SubscriptionsStatistics");
        llxHeader('', $title);

        print load_fiche_titre($title, '', $memberstatic->picto);

        $dir = $conf->adherent->dir_temp;

        dol_mkdir($dir);

        $stats = new AdherentStats($db, $socid, $userid);

// Build graphic number of object
        $data = $stats->getNbByMonthWithPrevYear($endyear, $startyear);
//var_dump($data);
// $data = array(array('Lib',val1,val2,val3),...)


        $filenamenb = $dir . '/subscriptionsnbinyear-' . $year . '.png';
        $fileurlnb = DOL_URL_ROOT . '/viewimage.php?modulepart=memberstats&file=subscriptionsnbinyear-' . $year . '.png';


        $px1 = new DolGraph();
        $mesg = $px1->isGraphKo();
        if (!$mesg) {
            $px1->SetData($data);
            $i = $startyear;
            $legend = [];
            while ($i <= $endyear) {
                $legend[] = $i;
                $i++;
            }
            $px1->SetLegend($legend);
            $px1->SetMaxValue($px1->GetCeilMaxValue());
            $px1->SetMinValue(min(0, $px1->GetFloorMinValue()));
            $px1->SetWidth($WIDTH);
            $px1->SetHeight($HEIGHT);
            $px1->SetYLabel($langs->trans("NbOfSubscriptions"));
            $px1->SetShading(3);
            $px1->SetHorizTickIncrement(1);
            $px1->mode = 'depth';
            $px1->SetTitle($langs->trans("NbOfSubscriptions"));

            $px1->draw($filenamenb, $fileurlnb);
        }

// Build graphic amount of object
        $data = $stats->getAmountByMonthWithPrevYear($endyear, $startyear);
//var_dump($data);
// $data = array(array('Lib',val1,val2,val3),...)

        $filenameamount = $dir . '/subscriptionsamountinyear-' . $year . '.png';
        $fileurlamount = DOL_URL_ROOT . '/viewimage.php?modulepart=memberstats&file=subscriptionsamountinyear-' . $year . '.png';

        $px2 = new DolGraph();
        $mesg = $px2->isGraphKo();
        if (!$mesg) {
            $px2->SetData($data);
            $i = $startyear;
            while ($i <= $endyear) {
                $legend[] = $i;
                $i++;
            }
            $px2->SetLegend($legend);
            $px2->SetMaxValue($px2->GetCeilMaxValue());
            $px2->SetMinValue(min(0, $px2->GetFloorMinValue()));
            $px2->SetWidth($WIDTH);
            $px2->SetHeight($HEIGHT);
            $px2->SetYLabel($langs->trans("AmountOfSubscriptions"));
            $px2->SetShading(3);
            $px2->SetHorizTickIncrement(1);
            $px2->mode = 'depth';
            $px2->SetTitle($langs->trans("AmountOfSubscriptions"));

            $px2->draw($filenameamount, $fileurlamount);
        }


        $head = member_stats_prepare_head($memberstatic);

        print dol_get_fiche_head($head, 'statssubscription', '', -1, '');


        print '<div class="fichecenter"><div class="fichethirdleft">';

// Show filter box
        /*print '<form name="stats" method="POST" action="'.$_SERVER['PHP_SELF'].'">';
        print '<input type="hidden" name="token" value="'.newToken().'">';

        print '<table class="border centpercent">';
        print '<tr class="liste_titre"><td class="liste_titre" colspan="2">'.$langs->trans("Filter").'</td></tr>';
        print '<tr><td>'.$langs->trans("Member").'</td><td>';
        print img_picto('', 'company', 'class="pictofixedwidth"');
        print $form->select_company($id,'memberid','',1);
        print '</td></tr>';
        print '<tr><td>'.$langs->trans("User").'</td><td>';
        print img_picto('', 'user', 'class="pictofixedwidth"');
        print $form->select_dolusers($userid, 'userid', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'widthcentpercentminusx maxwidth300');
        print '</td></tr>';
        print '<tr><td class="center" colspan="2"><input type="submit" name="submit" class="button small" value="'.$langs->trans("Refresh").'"></td></tr>';
        print '</table>';
        print '</form>';
        print '<br><br>';
        */

// Show array
        $data = $stats->getAllByYear();


        print '<div class="div-table-responsive-no-min">';
        print '<table class="noborder">';
        print '<tr class="liste_titre" height="24">';
        print '<td class="center">' . $langs->trans("Year") . '</td>';
        print '<td class="right">' . $langs->trans("NbOfSubscriptions") . '</td>';
        print '<td class="right">' . $langs->trans("AmountTotal") . '</td>';
        print '<td class="right">' . $langs->trans("AmountAverage") . '</td>';
        print '</tr>';

        $oldyear = 0;
        foreach ($data as $val) {
            $year = $val['year'];
            while ($oldyear > $year + 1) {  // If we have empty year
                $oldyear--;
                print '<tr class="oddeven" height="24">';
                print '<td class="center">';
                //print '<a href="month.php?year='.$oldyear.'&amp;mode='.$mode.'">';
                print $oldyear;
                //print '</a>';
                print '</td>';
                print '<td class="right">0</td>';
                print '<td class="right amount nowraponall">0</td>';
                print '<td class="right amount nowraponall">0</td>';
                print '</tr>';
            }
            print '<tr class="oddeven" height="24">';
            print '<td class="center">';
            print '<a href="' . DOL_URL_ROOT . '/adherents/subscription/list.php?date_select=' . ((int) $year) . '">' . $year . '</a>';
            print '</td>';
            print '<td class="right">' . $val['nb'] . '</td>';
            print '<td class="right amount nowraponall"><span class="amount">' . price(price2num($val['total'], 'MT'), 1) . '</span></td>';
            print '<td class="right amount nowraponall"><span class="amount">' . price(price2num($val['avg'], 'MT'), 1) . '</span></td>';
            print '</tr>';
            $oldyear = $year;
        }

        print '</table>';
        print '</div>';


        print '</div><div class="fichetwothirdright">';


// Show graphs
        print '<table class="border centpercent"><tr class="pair nohover"><td class="center">';
        if ($mesg) {
            print $mesg;
        } else {
            print $px1->show();
            print "<br>\n";
            print $px2->show();
        }
        print '</td></tr></table>';


        print '</div></div>';
        print '<div class="clearboth"></div>';


        print dol_get_fiche_end();

// End of page
        llxFooter();
        $db->close();
    }

}