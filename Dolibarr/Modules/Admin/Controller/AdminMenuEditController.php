<?php

/* Copyright (C) 2007       Patrick Raguin          <patrick.raguin@gmail.com>
 * Copyright (C) 2007-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2009-2011  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2016       Meziane Sof             <virtualsof@yahoo.fr>
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
use DoliCore\Lib\Menu;
use DoliModules\Adherent\Model\AdherentType;
use DoliCore\Lib\ExtraFields;

class AdminMenuEditController extends DolibarrController
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

        $cancel = GETPOST('cancel', 'alphanohtml'); // We click on a Cancel button
        $confirm = GETPOST('confirm');

        if (!$user->admin) {
            accessforbidden();
        }

        $dirstandard = [];
        $dirsmartphone = [];
        $dirmenus = array_merge(["/core/menus/"], (array) $conf->modules_parts['menus']);
        foreach ($dirmenus as $dirmenu) {
            $dirstandard[] = $dirmenu . 'standard';
            $dirsmartphone[] = $dirmenu . 'smartphone';
        }

        $action = GETPOST('action', 'aZ09');

        $menu_handler_top = getDolGlobalString('MAIN_MENU_STANDARD');
        $menu_handler_smartphone = getDolGlobalString('MAIN_MENU_SMARTPHONE');
        $menu_handler_top = preg_replace('/_backoffice.php/i', '', $menu_handler_top);
        $menu_handler_top = preg_replace('/_frontoffice.php/i', '', $menu_handler_top);
        $menu_handler_smartphone = preg_replace('/_backoffice.php/i', '', $menu_handler_smartphone);
        $menu_handler_smartphone = preg_replace('/_frontoffice.php/i', '', $menu_handler_smartphone);

        $menu_handler = $menu_handler_top;

        if (GETPOST("handler_origine")) {
            $menu_handler = GETPOST("handler_origine");
        }
        if (GETPOST("menu_handler")) {
            $menu_handler = GETPOST("menu_handler");
        }


        /*
         * Actions
         */

        if ($action == 'add') {
            if ($cancel) {
                header("Location: " . DOL_URL_ROOT . "/admin/menus/index.php?menu_handler=" . $menu_handler);
                exit;
            }

            $leftmenu = '';
            $mainmenu = '';
            if (GETPOST('menuIdParent', 'alphanohtml') && !is_numeric(GETPOST('menuIdParent', 'alphanohtml'))) {
                $tmp = explode('&', GETPOST('menuIdParent', 'alphanohtml'));
                foreach ($tmp as $s) {
                    if (preg_match('/fk_mainmenu=/', $s)) {
                        $mainmenu = preg_replace('/fk_mainmenu=/', '', $s);
                    }
                    if (preg_match('/fk_leftmenu=/', $s)) {
                        $leftmenu = preg_replace('/fk_leftmenu=/', '', $s);
                    }
                }
            }

            $langs->load("errors");

            $error = 0;
            if (!$error && !GETPOST('menu_handler')) {
                setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("MenuHandler")), null, 'errors');
                $action = 'create';
                $error++;
            }
            if (!$error && !GETPOST('type')) {
                setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Position")), null, 'errors');
                $action = 'create';
                $error++;
            }
            if (!$error && !GETPOST('url')) {
                setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("URL")), null, 'errors');
                $action = 'create';
                $error++;
            }
            if (!$error && !GETPOST('titre')) {
                setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Title")), null, 'errors');
                $action = 'create';
                $error++;
            }
            if (!$error && GETPOST('menuIdParent', 'alphanohtml') && GETPOST('type') == 'top') {
                setEventMessages($langs->trans("ErrorTopMenuMustHaveAParentWithId0"), null, 'errors');
                $action = 'create';
                $error++;
            }
            if (!$error && !GETPOST('menuIdParent', 'alphanohtml') && GETPOST('type') == 'left') {
                setEventMessages($langs->trans("ErrorLeftMenuMustHaveAParentId"), null, 'errors');
                $action = 'create';
                $error++;
            }

            if (!$error) {
                $menu = new Menubase($db);
                $menu->menu_handler = preg_replace('/_menu$/', '', GETPOST('menu_handler', 'aZ09'));
                $menu->type = (string) GETPOST('type', 'alphanohtml');
                $menu->title = (string) GETPOST('titre', 'alphanohtml');
                $menu->prefix = (string) GETPOST('picto', 'restricthtmlallowclass');
                $menu->url = (string) GETPOST('url', 'alphanohtml');
                $menu->langs = (string) GETPOST('langs', 'alphanohtml');
                $menu->position = GETPOSTINT('position');
                $menu->enabled = (string) GETPOST('enabled', 'alphanohtml');
                $menu->perms = (string) GETPOST('perms', 'alphanohtml');
                $menu->target = (string) GETPOST('target', 'alphanohtml');
                $menu->user = (string) GETPOST('user', 'alphanohtml');
                $menu->mainmenu = (string) GETPOST('propertymainmenu', 'alphanohtml');
                if (is_numeric(GETPOST('menuIdParent', 'alphanohtml'))) {
                    $menu->fk_menu = (int) GETPOST('menuIdParent', 'alphanohtml');
                } else {
                    if (GETPOST('type', 'alphanohtml') == 'top') {
                        $menu->fk_menu = 0;
                    } else {
                        $menu->fk_menu = -1;
                    }
                    $menu->fk_mainmenu = $mainmenu;
                    $menu->fk_leftmenu = $leftmenu;
                }

                $result = $menu->create($user);
                if ($result > 0) {
                    header("Location: " . DOL_URL_ROOT . "/admin/menus/index.php?menu_handler=" . GETPOST('menu_handler', 'aZ09'));
                    exit;
                } else {
                    $action = 'create';
                    setEventMessages($menu->error, $menu->errors, 'errors');
                }
            }
        }

        if ($action == 'update') {
            if (!$cancel) {
                $leftmenu = '';
                $mainmenu = '';
                if (GETPOST('menuIdParent', 'alphanohtml') && !is_numeric(GETPOST('menuIdParent', 'alphanohtml'))) {
                    $tmp = explode('&', GETPOST('menuIdParent', 'alphanohtml'));
                    foreach ($tmp as $s) {
                        if (preg_match('/fk_mainmenu=/', $s)) {
                            $mainmenu = preg_replace('/fk_mainmenu=/', '', $s);
                        }
                        if (preg_match('/fk_leftmenu=/', $s)) {
                            $leftmenu = preg_replace('/fk_leftmenu=/', '', $s);
                        }
                    }
                }

                $error = 0;
                if (!$error && !GETPOST('url')) {
                    setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("URL")), null, 'errors');
                    $action = 'create';
                    $error++;
                }

                if (!$error) {
                    $menu = new Menubase($db);
                    $result = $menu->fetch(GETPOSTINT('menuId'));
                    if ($result > 0) {
                        $menu->title = (string) GETPOST('titre', 'alphanohtml');
                        $menu->prefix = (string) GETPOST('picto', 'restricthtmlallowclass');
                        $menu->leftmenu = (string) GETPOST('leftmenu', 'aZ09');
                        $menu->url = (string) GETPOST('url', 'alphanohtml');
                        $menu->langs = (string) GETPOST('langs', 'alphanohtml');
                        $menu->position = GETPOSTINT('position');
                        $menu->enabled = (string) GETPOST('enabled', 'alphanohtml');
                        $menu->perms = (string) GETPOST('perms', 'alphanohtml');
                        $menu->target = (string) GETPOST('target', 'alphanohtml');
                        $menu->user = (string) GETPOST('user', 'alphanohtml');
                        $menu->mainmenu = (string) GETPOST('propertymainmenu', 'alphanohtml');
                        if (is_numeric(GETPOST('menuIdParent', 'alphanohtml'))) {
                            $menu->fk_menu = (int) GETPOST('menuIdParent', 'alphanohtml');
                        } else {
                            if (GETPOST('type', 'alphanohtml') == 'top') {
                                $menu->fk_menu = 0;
                            } else {
                                $menu->fk_menu = -1;
                            }
                            $menu->fk_mainmenu = $mainmenu;
                            $menu->fk_leftmenu = $leftmenu;
                        }

                        $result = $menu->update($user);
                        if ($result > 0) {
                            setEventMessages($langs->trans("RecordModifiedSuccessfully"), null, 'mesgs');
                        } else {
                            setEventMessages($menu->error, $menu->errors, 'errors');
                        }
                    } else {
                        setEventMessages($menu->error, $menu->errors, 'errors');
                    }

                    $action = "edit";

                    header("Location: " . DOL_URL_ROOT . "/admin/menus/index.php?menu_handler=" . $menu_handler);
                    exit;
                } else {
                    $action = 'edit';
                }
            } else {
                header("Location: " . DOL_URL_ROOT . "/admin/menus/index.php?menu_handler=" . $menu_handler);
                exit;
            }
        }


        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Admin/Views/admin_menu_edit.php');

        $db->close();

//        $this->template = '/page/adherent/type_list';
//
        return true;
    }
}
