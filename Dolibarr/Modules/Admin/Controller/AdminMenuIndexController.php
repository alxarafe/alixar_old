<?php

/* Copyright (C) 2007       Patrick Raguin          <patrick.raguin@gmail.com>
 * Copyright (C) 2007-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
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

namespace DoliModules\Admin\Controller;

global $conf;
global $db;
global $user;
global $hookmanager;
global $user;
global $menumanager;
global $langs;
global $mysoc;


// Load Dolibarr environment
require BASE_PATH . '/main.inc.php';

use DoliCore\Base\DolibarrController;

class AdminMenuIndexController extends DolibarrController
{
    /**
     *      \file       htdocs/adherents/type.php
     *      \ingroup    member
     *      \brief      Member's type setup
     */
    public function index($executeActions = true): bool
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;
        global $mysoc;

// Load translation files required by the page
        $langs->loadLangs(["other", "admin"]);

        $dirstandard = [];
        $dirsmartphone = [];
        $dirmenus = array_merge(["/core/menus/"], (array) $conf->modules_parts['menus']);
        foreach ($dirmenus as $dirmenu) {
            $dirstandard[] = $dirmenu . 'standard';
            $dirsmartphone[] = $dirmenu . 'smartphone';
        }

        $action = GETPOST('action', 'aZ09');
        $confirm = GETPOST('confirm', 'alpha');

//$menu_handler_top = getDolGlobalString('MAIN_MENU_STANDARD');
        $menu_handler_top = 'all';
        $menu_handler_top = preg_replace('/(_backoffice\.php|_menu\.php)/i', '', $menu_handler_top);
        $menu_handler_top = preg_replace('/(_frontoffice\.php|_menu\.php)/i', '', $menu_handler_top);

        $menu_handler = $menu_handler_top;

        if (GETPOST("handler_origine")) {
            $menu_handler = GETPOST("handler_origine");
        }
        if (GETPOST("menu_handler")) {
            $menu_handler = GETPOST("menu_handler");
        }

        $menu_handler_to_search = preg_replace('/(_backoffice|_frontoffice|_menu)?(\.php)?/i', '', $menu_handler);

        if (empty($user->admin)) {
            accessforbidden();
        }


        /*
         * Actions
         */

        if ($action == 'up') {
            $current = [];
            $previous = [];

            // Get current position
            $sql = "SELECT m.rowid, m.position, m.type, m.fk_menu";
            $sql .= " FROM " . MAIN_DB_PREFIX . "menu as m";
            $sql .= " WHERE m.rowid = " . GETPOSTINT("menuId");
            dol_syslog("admin/menus/index.php " . $sql);
            $result = $db->query($sql);
            $num = $db->num_rows($result);
            $i = 0;
            while ($i < $num) {
                $obj = $db->fetch_object($result);
                $current['rowid'] = $obj->rowid;
                $current['order'] = $obj->position;
                $current['type'] = $obj->type;
                $current['fk_menu'] = $obj->fk_menu;
                $i++;
            }

            // Menu before
            $sql = "SELECT m.rowid, m.position";
            $sql .= " FROM " . MAIN_DB_PREFIX . "menu as m";
            $sql .= " WHERE (m.position < " . ($current['order']) . " OR (m.position = " . ($current['order']) . " AND rowid < " . GETPOSTINT("menuId") . "))";
            $sql .= " AND m.menu_handler='" . $db->escape($menu_handler_to_search) . "'";
            $sql .= " AND m.entity = " . $conf->entity;
            $sql .= " AND m.type = '" . $db->escape($current['type']) . "'";
            $sql .= " AND m.fk_menu = '" . $db->escape($current['fk_menu']) . "'";
            $sql .= " ORDER BY m.position, m.rowid";
            dol_syslog("admin/menus/index.php " . $sql);
            $result = $db->query($sql);
            $num = $db->num_rows($result);
            $i = 0;
            while ($i < $num) {
                $obj = $db->fetch_object($result);
                $previous['rowid'] = $obj->rowid;
                $previous['order'] = $obj->position;
                $i++;
            }

            $sql = "UPDATE " . MAIN_DB_PREFIX . "menu as m";
            $sql .= " SET m.position = " . ((int) $previous['order']);
            $sql .= " WHERE m.rowid = " . ((int) $current['rowid']); // Up the selected entry
            dol_syslog("admin/menus/index.php " . $sql);
            $db->query($sql);
            $sql = "UPDATE " . MAIN_DB_PREFIX . "menu as m";
            $sql .= " SET m.position = " . ((int) ($current['order'] != $previous['order'] ? $current['order'] : $current['order'] + 1));
            $sql .= " WHERE m.rowid = " . ((int) $previous['rowid']); // Descend celui du dessus
            dol_syslog("admin/menus/index.php " . $sql);
            $db->query($sql);
        } elseif ($action == 'down') {
            $current = [];
            $next = [];

            // Get current position
            $sql = "SELECT m.rowid, m.position, m.type, m.fk_menu";
            $sql .= " FROM " . MAIN_DB_PREFIX . "menu as m";
            $sql .= " WHERE m.rowid = " . GETPOSTINT("menuId");
            dol_syslog("admin/menus/index.php " . $sql);
            $result = $db->query($sql);
            $num = $db->num_rows($result);
            $i = 0;
            while ($i < $num) {
                $obj = $db->fetch_object($result);
                $current['rowid'] = $obj->rowid;
                $current['order'] = $obj->position;
                $current['type'] = $obj->type;
                $current['fk_menu'] = $obj->fk_menu;
                $i++;
            }

            // Menu after
            $sql = "SELECT m.rowid, m.position";
            $sql .= " FROM " . MAIN_DB_PREFIX . "menu as m";
            $sql .= " WHERE (m.position > " . ($current['order']) . " OR (m.position = " . ($current['order']) . " AND rowid > " . GETPOSTINT("menuId") . "))";
            $sql .= " AND m.menu_handler='" . $db->escape($menu_handler_to_search) . "'";
            $sql .= " AND m.entity = " . $conf->entity;
            $sql .= " AND m.type = '" . $db->escape($current['type']) . "'";
            $sql .= " AND m.fk_menu = '" . $db->escape($current['fk_menu']) . "'";
            $sql .= " ORDER BY m.position, m.rowid";
            dol_syslog("admin/menus/index.php " . $sql);
            $result = $db->query($sql);
            $num = $db->num_rows($result);
            $i = 0;
            while ($i < $num) {
                $obj = $db->fetch_object($result);
                $next['rowid'] = $obj->rowid;
                $next['order'] = $obj->position;
                $i++;
            }

            $sql = "UPDATE " . MAIN_DB_PREFIX . "menu as m";
            $sql .= " SET m.position = " . ((int) ($current['order'] != $next['order'] ? $next['order'] : $current['order'] + 1)); // Down the selected entry
            $sql .= " WHERE m.rowid = " . ((int) $current['rowid']);
            dol_syslog("admin/menus/index.php " . $sql);
            $db->query($sql);
            $sql = "UPDATE " . MAIN_DB_PREFIX . "menu as m"; // Up the next entry
            $sql .= " SET m.position = " . ((int) $current['order']);
            $sql .= " WHERE m.rowid = " . ((int) $next['rowid']);
            dol_syslog("admin/menus/index.php " . $sql);
            $db->query($sql);
        } elseif ($action == 'confirm_delete' && $confirm == 'yes') {
            $db->begin();

            $sql = "DELETE FROM " . MAIN_DB_PREFIX . "menu";
            $sql .= " WHERE rowid = " . GETPOSTINT('menuId');
            $resql = $db->query($sql);
            if ($resql) {
                $db->commit();

                setEventMessages($langs->trans("MenuDeleted"), null, 'mesgs');

                header("Location: " . DOL_URL_ROOT . '/admin/menus/index.php?menu_handler=' . $menu_handler);
                exit;
            } else {
                $db->rollback();

                $reload = 0;
                $action = '';
            }
        }

        /*
         * View
         */

        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Admin/Views/admin_menu_index.php');

        $db->close();

        return true;
    }
}
