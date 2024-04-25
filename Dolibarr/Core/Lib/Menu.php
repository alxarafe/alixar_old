<?php

/* Copyright (C) 2024      Rafael San José      <rsanjose@alxarafe.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace DoliCore\Lib;

abstract class Menu
{
    public static function loadMenu()
    {

        global $langs, $user, $conf; // To export to dol_eval function
        global $mainmenu, $leftmenu; // To export to dol_eval function
        global $db;

        $type_user = empty($user->socid) ? 0 : 1;

        $sql = "SELECT m.rowid, m.type, m.module, m.fk_menu, m.fk_mainmenu, m.fk_leftmenu, m.url, m.titre,";
        $sql .= " m.prefix, m.langs, m.perms, m.enabled, m.target, m.mainmenu, m.leftmenu, m.position";
        $sql .= " FROM " . $db->prefix() . "menu as m";
        $sql .= " WHERE m.entity IN (0," . $conf->entity . ")";
        if ($type_user == 0) {
            $sql .= " AND m.usertype IN (0,2)";
        }
        if ($type_user == 1) {
            $sql .= " AND m.usertype IN (1,2)";
        }
        $sql .= " ORDER BY m.type DESC, m.position, m.rowid";

        //dol_syslog(get_class($this)."::menuLoad mymainmenu=".$mymainmenu." myleftmenu=".$myleftmenu." type_user=".$type_user." menu_handler=".$menu_handler." tabMenu size=".count($tabMenu), LOG_DEBUG);
        $resql = $db->query($sql);
        if ($resql) {
            $numa = $db->num_rows($resql);

            $a = 0;
            $b = 0;
            while ($a < $numa) {
                //$objm = $db->fetch_object($resql);
                $menu = $db->fetch_array($resql);

                // Define $right
                $perms = true;
                if (isset($menu['perms'])) {
                    $tmpcond = $menu['perms'];
                    if ($leftmenu == 'all') {
                        $tmpcond = preg_replace('/\$leftmenu\s*==\s*["\'a-zA-Z_]+/', '1==1', $tmpcond); // Force the part of condition on leftmenu to true
                    }
                    $perms = verifCond($tmpcond);
                    //print "verifCond rowid=".$menu['rowid']." ".$tmpcond.":".$perms."<br>\n";
                }

                // Define $enabled
                $enabled = true;
                if (isset($menu['enabled'])) {
                    $tmpcond = $menu['enabled'];
                    if ($leftmenu == 'all') {
                        $tmpcond = preg_replace('/\$leftmenu\s*==\s*["\'a-zA-Z_]+/', '1==1', $tmpcond); // Force the part of condition on leftmenu to true
                    }
                    $enabled = verifCond($tmpcond);
                    //var_dump($menu['type'].' - '.$menu['titre'].' - '.$menu['enabled'].' => '.$enabled);
                }

                // Define $title
                if ($enabled) {
                    $title = $langs->trans($menu['titre']); // If $menu['titre'] start with $, a dol_eval is done.
                    //var_dump($title.'-'.$menu['titre']);
                    if ($title == $menu['titre']) {   // Translation not found
                        if (!empty($menu['langs'])) {    // If there is a dedicated translation file
                            //print 'Load file '.$menu['langs'].'<br>';
                            $langs->load($menu['langs']);
                        }

                        $substitarray = ['__LOGIN__' => $user->login, '__USER_ID__' => $user->id, '__USER_SUPERVISOR_ID__' => $user->fk_user];
                        $menu['titre'] = make_substitutions($menu['titre'], $substitarray);

                        if (preg_match("/\//", $menu['titre'])) { // To manage translation when title is string1/string2
                            $tab_titre = explode("/", $menu['titre']);
                            $title = $langs->trans($tab_titre[0]) . "/" . $langs->trans($tab_titre[1]);
                        } elseif (preg_match('/\|\|/', $menu['titre'])) {
                            // To manage different translation (Title||AltTitle@ConditionForAltTitle)
                            $tab_title = explode("||", $menu['titre']);
                            $alt_title = explode("@", $tab_title[1]);
                            $title_enabled = verifCond($alt_title[1]);
                            $title = ($title_enabled ? $langs->trans($alt_title[0]) : $langs->trans($tab_title[0]));
                        } else {
                            $title = $langs->trans($menu['titre']);
                        }
                    }
                    //$tmp4=microtime(true);
                    //print '>>> 3 '.($tmp4 - $tmp3).'<br>';

                    // We complete tabMenu
                    $tabMenu[$b]['rowid'] = $menu['rowid'];
                    $tabMenu[$b]['module'] = $menu['module'];
                    $tabMenu[$b]['fk_menu'] = $menu['fk_menu'];
                    $tabMenu[$b]['url'] = $menu['url'];
                    if (!preg_match("/^(http:\/\/|https:\/\/)/i", $tabMenu[$b]['url'])) {
                        if (preg_match('/\?/', $tabMenu[$b]['url'])) {
                            $tabMenu[$b]['url'] .= '&amp;idmenu=' . $menu['rowid'];
                        } else {
                            $tabMenu[$b]['url'] .= '?idmenu=' . $menu['rowid'];
                        }
                    }
                    $tabMenu[$b]['titre'] = $title;
                    $tabMenu[$b]['prefix'] = $menu['prefix'];
                    $tabMenu[$b]['target'] = $menu['target'];
                    $tabMenu[$b]['mainmenu'] = $menu['mainmenu'];
                    $tabMenu[$b]['leftmenu'] = $menu['leftmenu'];
                    $tabMenu[$b]['perms'] = $perms;
                    $tabMenu[$b]['langs'] = $menu['langs']; // Note that this should not be used, lang file should be already loaded.
                    $tabMenu[$b]['enabled'] = $enabled;
                    $tabMenu[$b]['type'] = $menu['type'];
                    $tabMenu[$b]['fk_mainmenu'] = $menu['fk_mainmenu'];
                    $tabMenu[$b]['fk_leftmenu'] = $menu['fk_leftmenu'];
                    $tabMenu[$b]['position'] = (int) $menu['position'];

                    $b++;
                }

                $a++;
            }
            $db->free($resql);

            // Currently $tabMenu is sorted on position.
            // If a child have a position lower that its parent, we can make a loop to fix this here, but we prefer to show a warning
            // into the leftMenuCharger later to avoid useless operations.

            return $tabMenu;
        } else {
            dol_print_error($db);
            return [];
        }
    }
}
