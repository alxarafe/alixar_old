<?php

/* Copyright (C) 2001-2003  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2022  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2014       Marcos García           <marcosgdf@gmail.com>
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

namespace DoliModules\BookMarks\Controller;

global $conf;
global $db;
global $user;
global $hookmanager;
global $user;
global $menumanager;
global $langs;
global $mysoc;

use DoliCore\Base\DolibarrController;


/**
 *    \file       htdocs/bookmarks/card.php
 *    \ingroup    bookmark
 *    \brief      Page display/creation of bookmarks
 */

use DoliCore\Form\Form;
use DoliCore\Model\Bookmark;

// Load Dolibarr environment
require BASE_PATH . '/main.inc.php';

class BookMarksCardController extends DolibarrController
{

    public function index(bool $executeActions = true): bool
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;


// Load translation files required by the page
        $langs->loadLangs(['bookmarks', 'other']);

// Get Parameters
        $id = GETPOSTINT("id");
        $action = GETPOST("action", "alpha");
        $title = (string) GETPOST("title", "alpha");
        $url = (string) GETPOST("url", "alpha");
        $urlsource = GETPOST("urlsource", "alpha");
        $target = GETPOSTINT("target");
        $userid = GETPOSTINT("userid");
        $position = GETPOSTINT("position");
        $backtopage = GETPOST('backtopage', 'alpha');

// Initialize Objects
        $object = new Bookmark($db);
        if ($id > 0) {
            $object->fetch($id);
        }

// Security check
        restrictedArea($user, 'bookmark', $object);

        $permissiontoread = $user->hasRight('bookmark', 'lire');
        $permissiontoadd = $user->hasRight('bookmark', 'creer');
        $permissiontodelete = $user->hasRight('bookmark', 'supprimer');


        /*
         * Actions
         */

        if ($action == 'add' || $action == 'addproduct' || $action == 'update') {
            if ($action == 'update') {
                $invertedaction = 'edit';
            } else {
                $invertedaction = 'create';
            }

            $error = 0;

            if (GETPOST('cancel', 'alpha')) {
                if (empty($backtopage)) {
                    $backtopage = ($urlsource ? $urlsource : ((!empty($url) && !preg_match('/^http/i', $url)) ? $url : DOL_URL_ROOT . '/bookmarks/list.php'));
                }
                header("Location: " . $backtopage);
                exit;
            }

            if ($action == 'update') {
                $object->fetch(GETPOSTINT("id"));
            }
            // Check if null because user not admin can't set an user and send empty value here.
            if (!empty($userid)) {
                $object->fk_user = $userid;
            }
            $object->title = $title;
            $object->url = $url;
            $object->target = $target;
            $object->position = $position;

            if (!$title) {
                $error++;
                setEventMessages($langs->transnoentities("ErrorFieldRequired", $langs->trans("BookmarkTitle")), null, 'errors');
            }

            if (!$url) {
                $error++;
                setEventMessages($langs->transnoentities("ErrorFieldRequired", $langs->trans("UrlOrLink")), null, 'errors');
            }

            if (!$error) {
                $object->favicon = 'none';

                if ($action == 'update') {
                    $res = $object->update();
                } else {
                    $res = $object->create();
                }

                if ($res > 0) {
                    if (empty($backtopage)) {
                        $backtopage = ($urlsource ? $urlsource : ((!empty($url) && !preg_match('/^http/i', $url)) ? $url : DOL_URL_ROOT . '/bookmarks/list.php'));
                    }
                    header("Location: " . $backtopage);
                    exit;
                } else {
                    if ($object->errno == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
                        $langs->load("errors");
                        setEventMessages($langs->transnoentities("WarningBookmarkAlreadyExists"), null, 'warnings');
                    } else {
                        setEventMessages($object->error, $object->errors, 'errors');
                    }
                    $action = $invertedaction;
                }
            } else {
                $action = $invertedaction;
            }
        }

        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/BookMarks/Views/card.php');

        $db->close();

        return true;
    }
}
