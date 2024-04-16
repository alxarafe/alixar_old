<?php

/* Copyright (C) 2001-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003	Jean-Louis Bergamo		<jlb@j1b.org>
 * Copyright (C) 2004-2020	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2021	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2005       Brice Davoleau          <brice.davoleau@gmail.com>
 * Copyright (C) 2007       Patrick Raguin  		<patrick.raguin@gmail.com>
 * Copyright (C) 2010       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2013       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2014 	    Henry Florian           <florian.henry@open-concept.pro>
 * Copyright (C) 2015-2016  Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2015-2023	Frédéric France			<frederic.france@netlgic.fr>
 * Copyright (C) 2019       Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2019       Thibault FOUCART        <support@ptibogxiv.net>
 * Copyright (C) 2020		Tobias Sekan		    <tobias.sekan@startmail.com>
 * Copyright (C) 2021       NextGestion 			<contact@nextgestion.com>
 * Copyright (C) 2021-2023  Waël Almoman            <info@almoman.com>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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

// Load Dolibarr environment
require BASE_PATH . '/main.inc.php';
require_once BASE_PATH . '/core/class/extrafields.class.php';
require_once BASE_PATH . '/core/class/ldap.class.php';
require_once BASE_PATH . '/core/class/vcard.class.php';
require_once BASE_PATH . '/core/lib/date.lib.php';
require_once BASE_PATH . '/core/lib/company.lib.php';
require_once BASE_PATH . '/core/lib/files.lib.php';
require_once BASE_PATH . '/core/lib/functions2.lib.php';
require_once BASE_PATH . '/core/lib/images.lib.php';
require_once BASE_PATH . '/core/lib/ldap.lib.php';
require_once BASE_PATH . '/core/lib/member.lib.php';
require_once BASE_PATH . '/core/class/extrafields.class.php';
require_once BASE_PATH . '/partnership/class/partnership.class.php';
require_once BASE_PATH . '/partnership/lib/partnership.lib.php';

use DoliCore\Base\DolibarrController;
use DoliCore\Lib\Fields;
use DoliModules\Adherent\Model\Adherent;
use DoliModules\Adherent\Model\AdherentType;
use DoliModules\Adherent\Model\Subscription;
use ExtraFields;
use MailmanSpip;

class AdherentController extends DolibarrController
{
    /**
     *    \file       htdocs/adherents/agenda.php
     *    \ingroup    member
     *    \brief      Page of members events
     */
    public function agenda()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(array('companies', 'members'));

// Get Parameters
        $id = GETPOSTINT('id') ? GETPOSTINT('id') : GETPOSTINT('rowid');

// Pagination
        $limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
        $sortfield = GETPOST('sortfield', 'aZ09comma');
        $sortorder = GETPOST('sortorder', 'aZ09comma');
        $page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
        if (empty($page) || $page == -1) {
            $page = 0;
        }     // If $page is not defined, or '' or -1
        $offset = $limit * $page;
        $pageprev = $page - 1;
        $pagenext = $page + 1;
        if (!$sortfield) {
            $sortfield = 'a.datep,a.id';
        }
        if (!$sortorder) {
            $sortorder = 'DESC';
        }

        if (GETPOST('actioncode', 'array')) {
            $actioncode = GETPOST('actioncode', 'array', 3);
            if (!count($actioncode)) {
                $actioncode = '0';
            }
        } else {
            $actioncode = GETPOST("actioncode", "alpha", 3) ? GETPOST("actioncode", "alpha", 3) : (GETPOST("actioncode") == '0' ? '0' : getDolGlobalString('AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECT'));
        }
        $search_rowid = GETPOST('search_rowid');
        $search_agenda_label = GETPOST('search_agenda_label');

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
        $objcanvas = null;

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
        $hookmanager->initHooks(array('memberagenda', 'globalcard'));

// Security check
        $result = restrictedArea($user, 'adherent', $id);

// Initialize technical objects
        $object = new Adherent($db);
        $result = $object->fetch($id);
        if ($result > 0) {
            $object->fetch_thirdparty();

            $adht = new AdherentType($db);
            $result = $adht->fetch($object->typeid);
        }

        /*
         *  Actions
         */

        $parameters = array('id' => $id, 'objcanvas' => $objcanvas);
        $reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

        if (empty($reshook)) {
            // Cancel
            if (GETPOST('cancel', 'alpha') && !empty($backtopage)) {
                header("Location: " . $backtopage);
                exit;
            }

            // Purge search criteria
            if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All test are required to be compatible with all browsers
                $actioncode = '';
                $search_rowid = '';
                $search_agenda_label = '';
            }
        }

        /*
         * View
         */

        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Adherent/Views/agenda.php');

        $db->close();
    }

    /**
     *  \file       htdocs/adherents/card.php
     *  \ingroup    member
     *  \brief      Page of a member
     */
    public function card()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(array("companies", "bills", "members", "users", "other", "paypal"));


// Get parameters
        $action = GETPOST('action', 'aZ09');
        $cancel = GETPOST('cancel', 'alpha');
        $backtopage = GETPOST('backtopage', 'alpha');
        $confirm = GETPOST('confirm', 'alpha');
        $rowid = GETPOSTINT('rowid');
        $id = GETPOST('id') ? GETPOSTINT('id') : $rowid;
        $typeid = GETPOSTINT('typeid');
        $userid = GETPOSTINT('userid');
        $socid = GETPOSTINT('socid');
        $ref = GETPOST('ref', 'alpha');

        if (isModEnabled('mailmanspip')) {
            include_once DOL_DOCUMENT_ROOT . '/mailmanspip/class/mailmanspip.class.php';

            $langs->load('mailmanspip');

            $mailmanspip = new MailmanSpip($db);
        }

        $object = new Adherent($db);
        $extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
        $extrafields->fetch_name_optionals_label($object->table_element);

        $socialnetworks = getArrayOfSocialNetworks();

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
        $object->getCanvas($id);
        $canvas = $object->canvas ? $object->canvas : GETPOST("canvas");
        $objcanvas = null;
        if (!empty($canvas)) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/canvas.class.php';
            $objcanvas = new Canvas($db, $action);
            $objcanvas->getCanvas('adherent', 'membercard', $canvas);
        }

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
        $hookmanager->initHooks(array('membercard', 'globalcard'));

// Fetch object
        if ($id > 0 || !empty($ref)) {
            // Load member
            $result = $object->fetch($id, $ref);

            // Define variables to know what current user can do on users
            $canadduser = ($user->admin || $user->hasRight('user', 'user', 'creer'));
            // Define variables to know what current user can do on properties of user linked to edited member
            if ($object->user_id) {
                // $User is the user who edits, $object->user_id is the id of the related user in the edited member
                $caneditfielduser = ((($user->id == $object->user_id) && $user->hasRight('user', 'self', 'creer'))
                    || (($user->id != $object->user_id) && $user->hasRight('user', 'user', 'creer')));
                $caneditpassworduser = ((($user->id == $object->user_id) && $user->hasRight('user', 'self', 'password'))
                    || (($user->id != $object->user_id) && $user->hasRight('user', 'user', 'password')));
            }
        }

// Define variables to determine what the current user can do on the members
        $canaddmember = $user->hasRight('adherent', 'creer');
// Define variables to determine what the current user can do on the properties of a member
        if ($id) {
            $caneditfieldmember = $user->hasRight('adherent', 'creer');
        }

// Security check
        $result = restrictedArea($user, 'adherent', $object->id, '', '', 'socid', 'rowid', 0);

        if (!$user->hasRight('adherent', 'creer') && $action == 'edit') {
            accessforbidden('Not enough permission');
        }

        $linkofpubliclist = DOL_MAIN_URL_ROOT . '/public/members/public_list.php' . ((isModEnabled('multicompany')) ? '?entity=' . $conf->entity : '');


        /*
         *  Actions
         */

        $parameters = array('id' => $id, 'rowid' => $id, 'objcanvas' => $objcanvas, 'confirm' => $confirm);
        $reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

        if (empty($reshook)) {
            $backurlforlist = '/adherents/list.php';

            if (empty($backtopage) || ($cancel && empty($id))) {
                if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
                    if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
                        $backtopage = $backurlforlist;
                    } else {
                        $backtopage = '/adherents/card.php?id=' . ((!empty($id) && $id > 0) ? $id : '__ID__');
                    }
                }
            }

            if ($cancel) {
                if (!empty($backtopageforcancel)) {
                    header("Location: " . $backtopageforcancel);
                    exit;
                } elseif (!empty($backtopage)) {
                    header("Location: " . $backtopage);
                    exit;
                }
                $action = '';
            }

            if ($action == 'setuserid' && ($user->hasRight('user', 'self', 'creer') || $user->hasRight('user', 'user', 'creer'))) {
                $error = 0;
                if (!$user->hasRight('user', 'user', 'creer')) {    // If can edit only itself user, we can link to itself only
                    if ($userid != $user->id && $userid != $object->user_id) {
                        $error++;
                        setEventMessages($langs->trans("ErrorUserPermissionAllowsToLinksToItselfOnly"), null, 'errors');
                    }
                }

                if (!$error) {
                    if ($userid != $object->user_id) {  // If link differs from currently in database
                        $result = $object->setUserId($userid);
                        if ($result < 0) {
                            dol_print_error($object->db, $object->error);
                        }
                        $action = '';
                    }
                }
            }

            if ($action == 'setsocid') {
                $error = 0;
                if (!$error) {
                    if ($socid != $object->socid) { // If link differs from currently in database
                        $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "adherent";
                        $sql .= " WHERE socid = " . ((int) $socid);
                        $sql .= " AND entity = " . $conf->entity;
                        $resql = $db->query($sql);
                        if ($resql) {
                            $obj = $db->fetch_object($resql);
                            if ($obj && $obj->rowid > 0) {
                                $othermember = new Adherent($db);
                                $othermember->fetch($obj->rowid);
                                $thirdparty = new Societe($db);
                                $thirdparty->fetch($socid);
                                $error++;
                                setEventMessages($langs->trans("ErrorMemberIsAlreadyLinkedToThisThirdParty", $othermember->getFullName($langs), $othermember->login, $thirdparty->name), null, 'errors');
                            }
                        }

                        if (!$error) {
                            $result = $object->setThirdPartyId($socid);
                            if ($result < 0) {
                                dol_print_error($object->db, $object->error);
                            }
                            $action = '';
                        }
                    }
                }
            }

            // Create user from a member
            if ($action == 'confirm_create_user' && $confirm == 'yes' && $user->hasRight('user', 'user', 'creer')) {
                if ($result > 0) {
                    // Creation user
                    $nuser = new User($db);
                    $tmpuser = dol_clone($object);
                    if (GETPOST('internalorexternal', 'aZ09') == 'internal') {
                        $tmpuser->fk_soc = 0;
                    }

                    $result = $nuser->create_from_member($tmpuser, GETPOST('login', 'alphanohtml'));

                    if ($result < 0) {
                        $langs->load("errors");
                        setEventMessages($langs->trans($nuser->error), null, 'errors');
                    } else {
                        setEventMessages($langs->trans("NewUserCreated", $nuser->login), null, 'mesgs');
                        $action = '';
                    }
                } else {
                    setEventMessages($object->error, $object->errors, 'errors');
                }
            }

            // Create third party from a member
            if ($action == 'confirm_create_thirdparty' && $confirm == 'yes' && $user->hasRight('societe', 'creer')) {
                if ($result > 0) {
                    // User creation
                    $company = new Societe($db);
                    $result = $company->create_from_member($object, GETPOST('companyname', 'alpha'), GETPOST('companyalias', 'alpha'));

                    if ($result < 0) {
                        $langs->load("errors");
                        setEventMessages($langs->trans($company->error), null, 'errors');
                        setEventMessages($company->error, $company->errors, 'errors');
                    }
                } else {
                    setEventMessages($object->error, $object->errors, 'errors');
                }
            }

            if ($action == 'update' && !$cancel && $user->hasRight('adherent', 'creer')) {
                require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

                $birthdate = '';
                if (GETPOSTINT("birthday") && GETPOSTINT("birthmonth") && GETPOSTINT("birthyear")) {
                    $birthdate = dol_mktime(12, 0, 0, GETPOSTINT("birthmonth"), GETPOSTINT("birthday"), GETPOSTINT("birthyear"));
                }
                $lastname = GETPOST("lastname", 'alphanohtml');
                $firstname = GETPOST("firstname", 'alphanohtml');
                $gender = GETPOST("gender", 'alphanohtml');
                $societe = GETPOST("societe", 'alphanohtml');
                $morphy = GETPOST("morphy", 'alphanohtml');
                $login = GETPOST("login", 'alphanohtml');
                if ($morphy != 'mor' && empty($lastname)) {
                    $error++;
                    $langs->load("errors");
                    setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Lastname")), null, 'errors');
                }
                if ($morphy != 'mor' && (!isset($firstname) || $firstname == '')) {
                    $error++;
                    $langs->load("errors");
                    setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Firstname")), null, 'errors');
                }
                if ($morphy == 'mor' && empty($societe)) {
                    $error++;
                    $langs->load("errors");
                    setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Company")), null, 'errors');
                }
                // Check if the login already exists
                if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
                    if (empty($login)) {
                        $error++;
                        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Login")), null, 'errors');
                    }
                }
                // Create new object
                if ($result > 0 && !$error) {
                    $object->oldcopy = dol_clone($object, 2);

                    // Change values
                    $object->civility_id = trim(GETPOST("civility_id", 'alphanohtml'));
                    $object->firstname   = trim(GETPOST("firstname", 'alphanohtml'));
                    $object->lastname    = trim(GETPOST("lastname", 'alphanohtml'));
                    $object->gender      = trim(GETPOST("gender", 'alphanohtml'));
                    $object->login       = trim(GETPOST("login", 'alphanohtml'));
                    if (GETPOSTISSET('pass')) {
                        $object->pass        = trim(GETPOST("pass", 'none'));   // For password, we must use 'none'
                    }

                    $object->societe     = trim(GETPOST("societe", 'alphanohtml')); // deprecated
                    $object->company     = trim(GETPOST("societe", 'alphanohtml'));

                    $object->address     = trim(GETPOST("address", 'alphanohtml'));
                    $object->zip         = trim(GETPOST("zipcode", 'alphanohtml'));
                    $object->town        = trim(GETPOST("town", 'alphanohtml'));
                    $object->state_id    = GETPOSTINT("state_id");
                    $object->country_id  = GETPOSTINT("country_id");

                    $object->phone       = trim(GETPOST("phone", 'alpha'));
                    $object->phone_perso = trim(GETPOST("phone_perso", 'alpha'));
                    $object->phone_mobile = trim(GETPOST("phone_mobile", 'alpha'));
                    $object->email = preg_replace('/\s+/', '', GETPOST("member_email", 'alpha'));
                    $object->url = trim(GETPOST('member_url', 'custom', 0, FILTER_SANITIZE_URL));
                    $object->socialnetworks = array();
                    foreach ($socialnetworks as $key => $value) {
                        if (GETPOSTISSET($key) && GETPOST($key, 'alphanohtml') != '') {
                            $object->socialnetworks[$key] = trim(GETPOST($key, 'alphanohtml'));
                        }
                    }
                    $object->birth = $birthdate;
                    $object->default_lang = GETPOST('default_lang', 'alpha');
                    $object->typeid = GETPOSTINT("typeid");
                    //$object->note = trim(GETPOST("comment", "restricthtml"));
                    $object->morphy = GETPOST("morphy", 'alpha');

                    if (GETPOST('deletephoto', 'alpha')) {
                        $object->photo = '';
                    } elseif (!empty($_FILES['photo']['name'])) {
                        $object->photo = dol_sanitizeFileName($_FILES['photo']['name']);
                    }

                    // Get status and public property
                    $object->statut = GETPOSTINT("statut");
                    $object->status = GETPOSTINT("statut");
                    $object->public = GETPOSTINT("public");

                    // Fill array 'array_options' with data from add form
                    $ret = $extrafields->setOptionalsFromPost(null, $object, '@GETPOSTISSET');
                    if ($ret < 0) {
                        $error++;
                    }

                    // Check if we need to also synchronize user information
                    $nosyncuser = 0;
                    if ($object->user_id) { // If linked to a user
                        if ($user->id != $object->user_id && !$user->hasRight('user', 'user', 'creer')) {
                            $nosyncuser = 1; // Disable synchronizing
                        }
                    }

                    // Check if we need to also synchronize password information
                    $nosyncuserpass = 1;    // no by default
                    if (GETPOSTISSET('pass')) {
                        if ($object->user_id) { // If member is linked to a user
                            $nosyncuserpass = 0;    // We may try to sync password
                            if ($user->id == $object->user_id) {
                                if (!$user->hasRight('user', 'self', 'password')) {
                                    $nosyncuserpass = 1; // Disable synchronizing
                                }
                            } else {
                                if (!$user->hasRight('user', 'user', 'password')) {
                                    $nosyncuserpass = 1; // Disable synchronizing
                                }
                            }
                        }
                    }

                    if (!$error) {
                        $result = $object->update($user, 0, $nosyncuser, $nosyncuserpass);

                        if ($result >= 0 && !count($object->errors)) {
                            $categories = GETPOST('memcats', 'array');
                            $object->setCategories($categories);

                            // Logo/Photo save
                            $dir = $conf->adherent->dir_output . '/' . get_exdir(0, 0, 0, 1, $object, 'member') . '/photos';
                            $file_OK = is_uploaded_file($_FILES['photo']['tmp_name']);
                            if ($file_OK) {
                                if (GETPOST('deletephoto')) {
                                    require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
                                    $fileimg = $conf->adherent->dir_output . '/' . get_exdir(0, 0, 0, 1, $object, 'member') . '/photos/' . $object->photo;
                                    $dirthumbs = $conf->adherent->dir_output . '/' . get_exdir(0, 0, 0, 1, $object, 'member') . '/photos/thumbs';
                                    dol_delete_file($fileimg);
                                    dol_delete_dir_recursive($dirthumbs);
                                }

                                if (image_format_supported($_FILES['photo']['name']) > 0) {
                                    dol_mkdir($dir);

                                    if (@is_dir($dir)) {
                                        $newfile = $dir . '/' . dol_sanitizeFileName($_FILES['photo']['name']);
                                        if (!dol_move_uploaded_file($_FILES['photo']['tmp_name'], $newfile, 1, 0, $_FILES['photo']['error']) > 0) {
                                            setEventMessages($langs->trans("ErrorFailedToSaveFile"), null, 'errors');
                                        } else {
                                            // Create thumbs
                                            $object->addThumbs($newfile);
                                        }
                                    }
                                } else {
                                    setEventMessages("ErrorBadImageFormat", null, 'errors');
                                }
                            } else {
                                switch ($_FILES['photo']['error']) {
                                    case 1: //uploaded file exceeds the upload_max_filesize directive in php.ini
                                    case 2: //uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the html form
                                        $errors[] = "ErrorFileSizeTooLarge";
                                        break;
                                    case 3: //uploaded file was only partially uploaded
                                        $errors[] = "ErrorFilePartiallyUploaded";
                                        break;
                                }
                            }

                            $rowid = $object->id;
                            $id = $object->id;
                            $action = '';

                            if (!empty($backtopage)) {
                                header("Location: " . $backtopage);
                                exit;
                            }
                        } else {
                            setEventMessages($object->error, $object->errors, 'errors');
                            $action = '';
                        }
                    } else {
                        $action = 'edit';
                    }
                } else {
                    $action = 'edit';
                }
            }

            if ($action == 'add' && $user->hasRight('adherent', 'creer')) {
                if ($canvas) {
                    $object->canvas = $canvas;
                }
                $birthdate = '';
                if (GETPOSTISSET("birthday") && GETPOST("birthday") && GETPOSTISSET("birthmonth") && GETPOST("birthmonth") && GETPOSTISSET("birthyear") && GETPOST("birthyear")) {
                    $birthdate = dol_mktime(12, 0, 0, GETPOSTINT("birthmonth"), GETPOSTINT("birthday"), GETPOSTINT("birthyear"));
                }
                $datesubscription = '';
                if (GETPOSTISSET("reday") && GETPOSTISSET("remonth") && GETPOSTISSET("reyear")) {
                    $datesubscription = dol_mktime(12, 0, 0, GETPOSTINT("remonth"), GETPOSTINT("reday"), GETPOSTINT("reyear"));
                }

                $typeid = GETPOSTINT("typeid");
                $civility_id = GETPOST("civility_id", 'alphanohtml');
                $lastname = GETPOST("lastname", 'alphanohtml');
                $firstname = GETPOST("firstname", 'alphanohtml');
                $gender = GETPOST("gender", 'alphanohtml');
                $societe = GETPOST("societe", 'alphanohtml');
                $address = GETPOST("address", 'alphanohtml');
                $zip = GETPOST("zipcode", 'alphanohtml');
                $town = GETPOST("town", 'alphanohtml');
                $state_id = GETPOSTINT("state_id");
                $country_id = GETPOSTINT("country_id");

                $phone = GETPOST("phone", 'alpha');
                $phone_perso = GETPOST("phone_perso", 'alpha');
                $phone_mobile = GETPOST("phone_mobile", 'alpha');
                $email = preg_replace('/\s+/', '', GETPOST("member_email", 'alpha'));
                $url = trim(GETPOST('url', 'custom', 0, FILTER_SANITIZE_URL));
                $login = GETPOST("member_login", 'alphanohtml');
                $pass = GETPOST("password", 'none');    // For password, we use 'none'
                $photo = GETPOST("photo", 'alphanohtml');
                $morphy = GETPOST("morphy", 'alphanohtml');
                $public = GETPOST("public", 'alphanohtml');

                $userid = GETPOSTINT("userid");
                $socid = GETPOSTINT("socid");
                $default_lang = GETPOST('default_lang', 'alpha');

                $object->civility_id = $civility_id;
                $object->firstname   = $firstname;
                $object->lastname    = $lastname;
                $object->gender      = $gender;
                $object->societe     = $societe; // deprecated
                $object->company     = $societe;
                $object->address     = $address;
                $object->zip         = $zip;
                $object->town        = $town;
                $object->state_id    = $state_id;
                $object->country_id  = $country_id;
                $object->phone       = $phone;
                $object->phone_perso = $phone_perso;
                $object->phone_mobile = $phone_mobile;
                $object->socialnetworks = array();
                if (isModEnabled('socialnetworks')) {
                    foreach ($socialnetworks as $key => $value) {
                        if (GETPOSTISSET($key) && GETPOST($key, 'alphanohtml') != '') {
                            $object->socialnetworks[$key] = GETPOST("member_" . $key, 'alphanohtml');
                        }
                    }
                }

                $object->email       = $email;
                $object->url         = $url;
                $object->login       = $login;
                $object->pass        = $pass;
                $object->birth       = $birthdate;
                $object->photo       = $photo;
                $object->typeid      = $typeid;
                //$object->note        = $comment;
                $object->morphy      = $morphy;
                $object->user_id     = $userid;
                $object->socid = $socid;
                $object->public      = $public;
                $object->default_lang = $default_lang;
                // Fill array 'array_options' with data from add form
                $ret = $extrafields->setOptionalsFromPost(null, $object);
                if ($ret < 0) {
                    $error++;
                }

                // Check parameters
                if (empty($morphy) || $morphy == "-1") {
                    $error++;
                    setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("MemberNature")), null, 'errors');
                }
                // Tests if the login already exists
                if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
                    if (empty($login)) {
                        $error++;
                        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Login")), null, 'errors');
                    } else {
                        $sql = "SELECT login FROM " . MAIN_DB_PREFIX . "adherent WHERE login='" . $db->escape($login) . "'";
                        $result = $db->query($sql);
                        if ($result) {
                            $num = $db->num_rows($result);
                        }
                        if ($num) {
                            $error++;
                            $langs->load("errors");
                            setEventMessages($langs->trans("ErrorLoginAlreadyExists", $login), null, 'errors');
                        }
                    }
                    if (empty($pass)) {
                        $error++;
                        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Password")), null, 'errors');
                    }
                }
                if ($morphy == 'mor' && empty($societe)) {
                    $error++;
                    $langs->load("errors");
                    setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Company")), null, 'errors');
                }
                if ($morphy != 'mor' && empty($lastname)) {
                    $error++;
                    $langs->load("errors");
                    setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Lastname")), null, 'errors');
                }
                if ($morphy != 'mor' && (!isset($firstname) || $firstname == '')) {
                    $error++;
                    $langs->load("errors");
                    setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Firstname")), null, 'errors');
                }
                if (!($typeid > 0)) {   // Keep () before !
                    $error++;
                    setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), null, 'errors');
                }
                if (getDolGlobalString('ADHERENT_MAIL_REQUIRED') && !isValidEmail($email)) {
                    $error++;
                    $langs->load("errors");
                    setEventMessages($langs->trans("ErrorBadEMail", $email), null, 'errors');
                }
                if (!empty($object->url) && !isValidUrl($object->url)) {
                    $langs->load("errors");
                    setEventMessages($langs->trans("ErrorBadUrl", $object->url), null, 'errors');
                }
                $public = 0;
                if (isset($public)) {
                    $public = 1;
                }

                if (!$error) {
                    $db->begin();

                    // Create the member
                    $result = $object->create($user);
                    if ($result > 0) {
                        // Foundation categories
                        $memcats = GETPOST('memcats', 'array');
                        $object->setCategories($memcats);

                        $db->commit();

                        $rowid = $object->id;
                        $id = $object->id;

                        $backtopage = preg_replace('/__ID__/', $id, $backtopage);
                    } else {
                        $db->rollback();

                        $error++;
                        setEventMessages($object->error, $object->errors, 'errors');
                    }

                    // Auto-create thirdparty on member creation
                    if (getDolGlobalString('ADHERENT_DEFAULT_CREATE_THIRDPARTY')) {
                        if ($result > 0) {
                            // Create third party out of a member
                            $company = new Societe($db);
                            $result = $company->create_from_member($object);
                            if ($result < 0) {
                                $langs->load("errors");
                                setEventMessages($langs->trans($company->error), null, 'errors');
                                setEventMessages($company->error, $company->errors, 'errors');
                            }
                        } else {
                            setEventMessages($object->error, $object->errors, 'errors');
                        }
                    }
                }
                $action = ($result < 0 || !$error) ? '' : 'create';

                if (!$error && $backtopage) {
                    header("Location: " . $backtopage);
                    exit;
                }
            }

            if ($user->hasRight('adherent', 'supprimer') && $action == 'confirm_delete' && $confirm == 'yes') {
                $result = $object->delete($user);
                if ($result > 0) {
                    setEventMessages($langs->trans("RecordDeleted"), null, 'errors');
                    if (!empty($backtopage) && !preg_match('/' . preg_quote($_SERVER['PHP_SELF'], '/') . '/', $backtopage)) {
                        header("Location: " . $backtopage);
                        exit;
                    } else {
                        header("Location: list.php");
                        exit;
                    }
                } else {
                    setEventMessages($object->error, null, 'errors');
                }
            }

            if ($user->hasRight('adherent', 'creer') && $action == 'confirm_valid' && $confirm == 'yes') {
                $error = 0;

                $db->begin();

                $adht = new AdherentType($db);
                $adht->fetch($object->typeid);

                $result = $object->validate($user);

                if ($result >= 0 && !count($object->errors)) {
                    // Send confirmation email (according to parameters of member type. Otherwise generic)
                    if ($object->email && GETPOST("send_mail")) {
                        $subject = '';
                        $msg = '';

                        // Send subscription email
                        include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
                        $formmail = new FormMail($db);
                        // Set output language
                        $outputlangs = new Translate('', $conf);
                        $outputlangs->setDefaultLang(empty($object->thirdparty->default_lang) ? $mysoc->default_lang : $object->thirdparty->default_lang);
                        // Load traductions files required by page
                        $outputlangs->loadLangs(array("main", "members", "companies", "install", "other"));
                        // Get email content from template
                        $arraydefaultmessage = null;
                        $labeltouse = getDolGlobalString('ADHERENT_EMAIL_TEMPLATE_MEMBER_VALIDATION');

                        if (!empty($labeltouse)) {
                            $arraydefaultmessage = $formmail->getEMailTemplate($db, 'member', $user, $outputlangs, 0, 1, $labeltouse);
                        }

                        if (!empty($labeltouse) && is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0) {
                            $subject = $arraydefaultmessage->topic;
                            $msg     = $arraydefaultmessage->content;
                        }

                        if (empty($labeltouse) || (int) $labeltouse === -1) {
                            //fallback on the old configuration.
                            $langs->load("errors");
                            setEventMessages('<a href="' . DOL_URL_ROOT . '/adherents/admin/member_emails.php">' . $langs->trans('WarningMandatorySetupNotComplete') . '</a>', null, 'errors');
                            $error++;
                        } else {
                            $substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
                            complete_substitutions_array($substitutionarray, $outputlangs, $object);
                            $subjecttosend = make_substitutions($subject, $substitutionarray, $outputlangs);
                            $texttosend = make_substitutions(dol_concatdesc($msg, $adht->getMailOnValid()), $substitutionarray, $outputlangs);

                            $moreinheader = 'X-Dolibarr-Info: send_an_email by adherents/card.php' . "\r\n";

                            $result = $object->sendEmail($texttosend, $subjecttosend, array(), array(), array(), "", "", 0, -1, '', $moreinheader);
                            if ($result < 0) {
                                $error++;
                                setEventMessages($object->error, $object->errors, 'errors');
                            }
                        }
                    }
                } else {
                    $error++;
                    setEventMessages($object->error, $object->errors, 'errors');
                }

                if (!$error) {
                    $db->commit();
                } else {
                    $db->rollback();
                }
                $action = '';
            }

            if ($user->hasRight('adherent', 'supprimer') && $action == 'confirm_resiliate') {
                $error = 0;

                if ($confirm == 'yes') {
                    $adht = new AdherentType($db);
                    $adht->fetch($object->typeid);

                    $result = $object->resiliate($user);

                    if ($result >= 0 && !count($object->errors)) {
                        if ($object->email && GETPOST("send_mail")) {
                            $subject = '';
                            $msg = '';

                            // Send subscription email
                            include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
                            $formmail = new FormMail($db);
                            // Set output language
                            $outputlangs = new Translate('', $conf);
                            $outputlangs->setDefaultLang(empty($object->thirdparty->default_lang) ? $mysoc->default_lang : $object->thirdparty->default_lang);
                            // Load traductions files required by page
                            $outputlangs->loadLangs(array("main", "members", "companies", "install", "other"));
                            // Get email content from template
                            $arraydefaultmessage = null;
                            $labeltouse = getDolGlobalString('ADHERENT_EMAIL_TEMPLATE_CANCELATION');

                            if (!empty($labeltouse)) {
                                $arraydefaultmessage = $formmail->getEMailTemplate($db, 'member', $user, $outputlangs, 0, 1, $labeltouse);
                            }

                            if (!empty($labeltouse) && is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0) {
                                $subject = $arraydefaultmessage->topic;
                                $msg     = $arraydefaultmessage->content;
                            }

                            if (empty($labeltouse) || (int) $labeltouse === -1) {
                                //fallback on the old configuration.
                                setEventMessages('WarningMandatorySetupNotComplete', null, 'errors');
                                $error++;
                            } else {
                                $substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
                                complete_substitutions_array($substitutionarray, $outputlangs, $object);
                                $subjecttosend = make_substitutions($subject, $substitutionarray, $outputlangs);
                                $texttosend = make_substitutions(dol_concatdesc($msg, $adht->getMailOnResiliate()), $substitutionarray, $outputlangs);

                                $moreinheader = 'X-Dolibarr-Info: send_an_email by adherents/card.php' . "\r\n";

                                $result = $object->sendEmail($texttosend, $subjecttosend, array(), array(), array(), "", "", 0, -1, '', $moreinheader);
                                if ($result < 0) {
                                    $error++;
                                    setEventMessages($object->error, $object->errors, 'errors');
                                }
                            }
                        }
                    } else {
                        $error++;

                        setEventMessages($object->error, $object->errors, 'errors');
                        $action = '';
                    }
                }
                if (!empty($backtopage) && !$error) {
                    header("Location: " . $backtopage);
                    exit;
                }
            }

            if ($user->hasRight('adherent', 'supprimer') && $action == 'confirm_exclude') {
                $error = 0;

                if ($confirm == 'yes') {
                    $adht = new AdherentType($db);
                    $adht->fetch($object->typeid);

                    $result = $object->exclude($user);

                    if ($result >= 0 && !count($object->errors)) {
                        if ($object->email && GETPOST("send_mail")) {
                            $subject = '';
                            $msg = '';

                            // Send subscription email
                            include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
                            $formmail = new FormMail($db);
                            // Set output language
                            $outputlangs = new Translate('', $conf);
                            $outputlangs->setDefaultLang(empty($object->thirdparty->default_lang) ? $mysoc->default_lang : $object->thirdparty->default_lang);
                            // Load traductions files required by page
                            $outputlangs->loadLangs(array("main", "members", "companies", "install", "other"));
                            // Get email content from template
                            $arraydefaultmessage = null;
                            $labeltouse = getDolGlobalString('ADHERENT_EMAIL_TEMPLATE_EXCLUSION');

                            if (!empty($labeltouse)) {
                                $arraydefaultmessage = $formmail->getEMailTemplate($db, 'member', $user, $outputlangs, 0, 1, $labeltouse);
                            }

                            if (!empty($labeltouse) && is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0) {
                                $subject = $arraydefaultmessage->topic;
                                $msg     = $arraydefaultmessage->content;
                            }

                            if (empty($labeltouse) || (int) $labeltouse === -1) {
                                //fallback on the old configuration.
                                setEventMessages('WarningMandatorySetupNotComplete', null, 'errors');
                                $error++;
                            } else {
                                $substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
                                complete_substitutions_array($substitutionarray, $outputlangs, $object);
                                $subjecttosend = make_substitutions($subject, $substitutionarray, $outputlangs);
                                $texttosend = make_substitutions(dol_concatdesc($msg, $adht->getMailOnExclude()), $substitutionarray, $outputlangs);

                                $moreinheader = 'X-Dolibarr-Info: send_an_email by adherents/card.php' . "\r\n";

                                $result = $object->sendEmail($texttosend, $subjecttosend, array(), array(), array(), "", "", 0, -1, '', $moreinheader);
                                if ($result < 0) {
                                    $error++;
                                    setEventMessages($object->error, $object->errors, 'errors');
                                }
                            }
                        }
                    } else {
                        $error++;

                        setEventMessages($object->error, $object->errors, 'errors');
                        $action = '';
                    }
                }
                if (!empty($backtopage) && !$error) {
                    header("Location: " . $backtopage);
                    exit;
                }
            }

            // SPIP Management
            if ($user->hasRight('adherent', 'supprimer') && $action == 'confirm_del_spip' && $confirm == 'yes') {
                if (!count($object->errors)) {
                    if (!$mailmanspip->del_to_spip($object)) {
                        setEventMessages($langs->trans('DeleteIntoSpipError') . ': ' . $mailmanspip->error, null, 'errors');
                    }
                }
            }

            if ($user->hasRight('adherent', 'creer') && $action == 'confirm_add_spip' && $confirm == 'yes') {
                if (!count($object->errors)) {
                    if (!$mailmanspip->add_to_spip($object)) {
                        setEventMessages($langs->trans('AddIntoSpipError') . ': ' . $mailmanspip->error, null, 'errors');
                    }
                }
            }

            // Actions when printing a doc from card
            include DOL_DOCUMENT_ROOT . '/core/actions_printing.inc.php';

            // Actions to build doc
            $upload_dir = $conf->adherent->dir_output;
            $permissiontoadd = $user->hasRight('adherent', 'creer');
            include DOL_DOCUMENT_ROOT . '/core/actions_builddoc.inc.php';

            // Actions to send emails
            $triggersendname = 'MEMBER_SENTBYMAIL';
            $paramname = 'id';
            $mode = 'emailfrommember';
            $trackid = 'mem' . $object->id;
            include DOL_DOCUMENT_ROOT . '/core/actions_sendmails.inc.php';
        }


        /*
         * View
         */

        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Adherent/Views/card.php');

        $db->close();
    }

    /**
     *  \file       htdocs/adherents/document.php
     *  \brief      Tab for documents linked to third party
     *  \ingroup    societe
     */
    public function document()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(array("companies", "members", "other"));


        $id = GETPOSTISSET('id') ? GETPOSTINT('id') : GETPOSTINT('rowid');
        $ref = GETPOST('ref', 'alphanohtml');
        $action = GETPOST('action', 'aZ09');
        $confirm = GETPOST('confirm', 'alpha');

// Get parameters
        $limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
        $sortfield = GETPOST('sortfield', 'aZ09comma');
        $sortorder = GETPOST('sortorder', 'aZ09comma');
        $page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
        if (empty($page) || $page == -1) {
            $page = 0;
        }     // If $page is not defined, or '' or -1
        $offset = $limit * $page;
        $pageprev = $page - 1;
        $pagenext = $page + 1;
        if (!$sortorder) {
            $sortorder = "ASC";
        }
        if (!$sortfield) {
            $sortfield = "name";
        }

        $object = new Adherent($db);
        $membert = new AdherentType($db);
        $result = $object->fetch($id, $ref);
        if ($result < 0) {
            dol_print_error($db);
            exit;
        }
        $upload_dir = $conf->adherent->dir_output . "/" . get_exdir(0, 0, 0, 1, $object, 'member');

// Fetch object
        if ($id > 0 || !empty($ref)) {
            // Load member
            $result = $object->fetch($id, $ref);

            // Define variables to know what current user can do on users
            $canadduser = ($user->admin || $user->hasRight('user', 'user', 'creer'));
            // Define variables to know what current user can do on properties of user linked to edited member
            if ($object->user_id) {
                // $User is the user who edits, $object->user_id is the id of the related user in the edited member
                $caneditfielduser = ((($user->id == $object->user_id) && $user->hasRight('user', 'self', 'creer'))
                    || (($user->id != $object->user_id) && $user->hasRight('user', 'user', 'creer')));
                $caneditpassworduser = ((($user->id == $object->user_id) && $user->hasRight('user', 'self', 'password'))
                    || (($user->id != $object->user_id) && $user->hasRight('user', 'user', 'password')));
            }
        }

// Define variables to determine what the current user can do on the members
        $canaddmember = $user->hasRight('adherent', 'creer');
// Define variables to determine what the current user can do on the properties of a member
        if ($id) {
            $caneditfieldmember = $user->hasRight('adherent', 'creer');
        }

        $permissiontoadd = $canaddmember;

// Security check
        $result = restrictedArea($user, 'adherent', $object->id, '', '', 'socid', 'rowid', 0);


        /*
         * Actions
         */

        include DOL_DOCUMENT_ROOT . '/core/actions_linkedfiles.inc.php';


        /*
         * View
         */

        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Adherent/Views/document.php');

        $db->close();
    }

    /**
     *       \file       htdocs/adherents/index.php
     *       \ingroup    member
     *       \brief      Home page of membership module
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

// Load translation files required by the page
        $langs->loadLangs(["companies", "members"]);

        // $hookmanager = new HookManager($db);

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
        $hookmanager->initHooks(['membersindex']);

// Security check
        $result = restrictedArea($user, 'adherent');

        /*
         * Actions
         */

        $userid = GETPOSTINT('userid');
        if (GETPOST('addbox')) {
            // Add box (when submit is done from a form when ajax disabled)
            require_once DOL_DOCUMENT_ROOT . '/core/class/infobox.class.php';
            $zone = GETPOSTINT('areacode');
            $boxorder = GETPOST('boxorder', 'aZ09');
            $boxorder .= GETPOST('boxcombo', 'aZ09');
            $result = InfoBox::saveboxorder($db, $zone, $boxorder, $userid);
            if ($result > 0) {
                setEventMessages($langs->trans("BoxAdded"), null);
            }
        }

        /*
         * View
         */

        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Adherent/Views/index.php');

        $db->close();
    }

    /**
     *       \file       htdocs/adherents/ldap.php
     *       \ingroup    ldap member
     *       \brief      Page fiche LDAP adherent
     */
    public function ldap()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(array("companies", "members", "ldap", "admin"));

        $id = GETPOSTINT('id');
        $ref = GETPOST('ref', 'alphanohtml');
        $action = GETPOST('action', 'aZ09');

// Protection
        $socid = 0;
        if ($user->socid > 0) {
            $socid = $user->socid;
        }

        $object = new Adherent($db);

// Fetch object
        if ($id > 0 || !empty($ref)) {
            // Load member
            $result = $object->fetch($id, $ref);

            // Define variables to know what current user can do on users
            $canadduser = (!empty($user->admin) || $user->hasRight('user', 'user', 'creer'));
            // Define variables to know what current user can do on properties of user linked to edited member
            if ($object->user_id) {
                // $User is the user who edits, $object->user_id is the id of the related user in the edited member
                $caneditfielduser = ((($user->id == $object->user_id) && $user->hasRight('user', 'self', 'creer'))
                    || (($user->id != $object->user_id) && $user->hasRight('user', 'user', 'creer')));
                $caneditpassworduser = ((($user->id == $object->user_id) && $user->hasRight('user', 'self', 'password'))
                    || (($user->id != $object->user_id) && $user->hasRight('user', 'user', 'password')));
            }
        }

// Define variables to determine what the current user can do on the members
        $canaddmember = $user->hasRight('adherent', 'creer');
// Define variables to determine what the current user can do on the properties of a member
        if ($id) {
            $caneditfieldmember = $user->hasRight('adherent', 'creer');
        }

// Security check
        $result = restrictedArea($user, 'adherent', $object->id, '', '', 'socid', 'rowid', 0);


        /*
         * Actions
         */

        if ($action == 'dolibarr2ldap') {
            $ldap = new Ldap();
            $result = $ldap->connectBind();

            if ($result > 0) {
                $info = $object->_load_ldap_info();
                $dn = $object->_load_ldap_dn($info);
                $olddn = $dn; // We can say that old dn = dn as we force synchro

                $result = $ldap->update($dn, $info, $user, $olddn);
            }

            if ($result >= 0) {
                setEventMessages($langs->trans("MemberSynchronized"), null, 'mesgs');
            } else {
                setEventMessages($ldap->error, $ldap->errors, 'errors');
            }
        }



        /*
         *  View
         */

        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Adherent/Views/ldap.php');

        $db->close();
    }

    /**
     *  \file       htdocs/adherents/list.php
     *  \ingroup    member
     *  \brief      Page to list all members of foundation
     */
    public function list()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(["members", "companies", "categories"]);


// Get parameters
        $action = GETPOST('action', 'aZ09');
        $massaction = GETPOST('massaction', 'alpha');
        $show_files = GETPOSTINT('show_files');
        $confirm = GETPOST('confirm', 'alpha');
        $cancel = GETPOST('cancel', 'alpha');
        $toselect = GETPOST('toselect', 'array');
        $contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'memberslist'; // To manage different context of search
        $backtopage = GETPOST('backtopage', 'alpha');
        $optioncss = GETPOST('optioncss', 'aZ');
        $mode = GETPOST('mode', 'alpha');

// Search fields
        $search = GETPOST("search", 'alpha');
        $search_ref = GETPOST("search_ref", 'alpha');
        $search_lastname = GETPOST("search_lastname", 'alpha');
        $search_firstname = GETPOST("search_firstname", 'alpha');
        $search_gender = GETPOST("search_gender", 'alpha');
        $search_civility = GETPOST("search_civility", 'alpha');
        $search_company = GETPOST('search_company', 'alphanohtml');
        $search_login = GETPOST("search_login", 'alpha');
        $search_address = GETPOST("search_address", 'alpha');
        $search_zip = GETPOST("search_zip", 'alpha');
        $search_town = GETPOST("search_town", 'alpha');
        $search_state = GETPOST("search_state", 'alpha');  // county / departement / federal state
        $search_country = GETPOST("search_country", 'alpha');
        $search_phone = GETPOST("search_phone", 'alpha');
        $search_phone_perso = GETPOST("search_phone_perso", 'alpha');
        $search_phone_mobile = GETPOST("search_phone_mobile", 'alpha');
        $search_type = GETPOST("search_type", 'alpha');
        $search_email = GETPOST("search_email", 'alpha');
        $search_categ = GETPOSTINT("search_categ");
        $search_morphy = GETPOST("search_morphy", 'alpha');
        $search_import_key = trim(GETPOST("search_import_key", 'alpha'));

        $catid = GETPOSTINT("catid");
        $socid = GETPOSTINT('socid');

        $search_filter = GETPOST("search_filter", 'alpha');
        $search_status = GETPOST("search_status", 'intcomma');  // status
        $search_datec_start = dol_mktime(0, 0, 0, GETPOSTINT('search_datec_start_month'), GETPOSTINT('search_datec_start_day'), GETPOSTINT('search_datec_start_year'));
        $search_datec_end = dol_mktime(23, 59, 59, GETPOSTINT('search_datec_end_month'), GETPOSTINT('search_datec_end_day'), GETPOSTINT('search_datec_end_year'));
        $search_datem_start = dol_mktime(0, 0, 0, GETPOSTINT('search_datem_start_month'), GETPOSTINT('search_datem_start_day'), GETPOSTINT('search_datem_start_year'));
        $search_datem_end = dol_mktime(23, 59, 59, GETPOSTINT('search_datem_end_month'), GETPOSTINT('search_datem_end_day'), GETPOSTINT('search_datem_end_year'));

        $filter = GETPOST("filter", 'alpha');
        if ($filter) {
            $search_filter = $filter; // For backward compatibility
        }

        $statut = GETPOST("statut", 'alpha');
        if ($statut != '') {
            $search_status = $statut; // For backward compatibility
        }

        $search_all = trim((GETPOST('search_all', 'alphanohtml') != '') ? GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));

        if ($search_status < -2) {
            $search_status = '';
        }

// Pagination parameters
        $limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
        $sortfield = GETPOST('sortfield', 'aZ09comma');
        $sortorder = GETPOST('sortorder', 'aZ09comma');
        $page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
        if (empty($page) || $page == -1) {
            $page = 0;
        }     // If $page is not defined, or '' or -1
        $offset = $limit * $page;
        $pageprev = $page - 1;
        $pagenext = $page + 1;
        if (!$sortorder) {
            $sortorder = ($filter == 'outofdate' ? "DESC" : "ASC");
        }
        if (!$sortfield) {
            $sortfield = ($filter == 'outofdate' ? "d.datefin" : "d.lastname");
        }

        $object = new Adherent($db);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
        $hookmanager->initHooks(['memberlist']);
        $extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
        $extrafields->fetch_name_optionals_label($object->table_element);

        $search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// List of fields to search into when doing a "search in all"
        $fieldstosearchall = [
            'd.ref' => 'Ref',
            'd.login' => 'Login',
            'd.lastname' => 'Lastname',
            'd.firstname' => 'Firstname',
            'd.societe' => "Company",
            'd.email' => 'EMail',
            'd.address' => 'Address',
            'd.zip' => 'Zip',
            'd.town' => 'Town',
            'd.phone' => "Phone",
            'd.phone_perso' => "PhonePerso",
            'd.phone_mobile' => "PhoneMobile",
            'd.note_public' => 'NotePublic',
            'd.note_private' => 'NotePrivate',
        ];

        $arrayfields = [
            'd.ref' => ['label' => "Ref", 'checked' => 1],
            'd.civility' => ['label' => "Civility", 'checked' => 0],
            'd.lastname' => ['label' => "Lastname", 'checked' => 1],
            'd.firstname' => ['label' => "Firstname", 'checked' => 1],
            'd.gender' => ['label' => "Gender", 'checked' => 0],
            'd.company' => ['label' => "Company", 'checked' => 1, 'position' => 70],
            'd.login' => ['label' => "Login", 'checked' => 1],
            'd.morphy' => ['label' => "MemberNature", 'checked' => 1],
            't.libelle' => ['label' => "Type", 'checked' => 1, 'position' => 55],
            'd.address' => ['label' => "Address", 'checked' => 0],
            'd.zip' => ['label' => "Zip", 'checked' => 0],
            'd.town' => ['label' => "Town", 'checked' => 0],
            'd.phone' => ['label' => "Phone", 'checked' => 0],
            'd.phone_perso' => ['label' => "PhonePerso", 'checked' => 0],
            'd.phone_mobile' => ['label' => "PhoneMobile", 'checked' => 0],
            'd.email' => ['label' => "Email", 'checked' => 1],
            'state.nom' => ['label' => "State", 'checked' => 0, 'position' => 90],
            'country.code_iso' => ['label' => "Country", 'checked' => 0, 'position' => 95],
            /*'d.note_public'=>array('label'=>"NotePublic", 'checked'=>0),
            'd.note_private'=>array('label'=>"NotePrivate", 'checked'=>0),*/
            'd.datefin' => ['label' => "EndSubscription"],
            'd.datec' => ['label' => "DateCreation"],
            'd.birth' => ['label' => "Birthday"],
            'd.tms' => ['label' => "DateModificationShort"],
            'd.statut' => ['label' => "Status"],
            'd.import_key' => ['label' => "ImportId"],
        ];

// Extra fields
        include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_array_fields.tpl.php';

        $object->fields = dol_sort_array($object->fields, 'position');
//$arrayfields['anotherfield'] = array('type'=>'integer', 'label'=>'AnotherField', 'checked'=>1, 'enabled'=>1, 'position'=>90, 'csslist'=>'right');

// Complete array of fields for columns
        $tableprefix = 'd';
        foreach ($object->fields as $key => $val) {
            if (!array_key_exists($tableprefix . '.' . $key, $arrayfields)) {   // Discard record not into $arrayfields
                continue;
            }
            // If $val['visible']==0, then we never show the field

            $arrayfields[$tableprefix . '.' . $key] = Fields::getVisibleField($val);
        }
        $arrayfields = dol_sort_array($arrayfields, 'position');
//var_dump($arrayfields);exit;

// Security check
        $result = restrictedArea($user, 'adherent');


        /*
         * Actions
         */

        if (GETPOST('cancel', 'alpha')) {
            $action = 'list';
            $massaction = '';
        }
        if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
            $massaction = '';
        }

        $parameters = ['socid' => isset($socid) ? $socid : null];
        $reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

        if (empty($reshook)) {
            // Selection of new fields
            include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

            // Purge search criteria
            if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
                $statut = '';
                $filter = '';

                $search = "";
                $search_ref = "";
                $search_lastname = "";
                $search_firstname = "";
                $search_gender = "";
                $search_civility = "";
                $search_login = "";
                $search_company = "";
                $search_type = "";
                $search_email = "";
                $search_address = "";
                $search_zip = "";
                $search_town = "";
                $search_state = "";
                $search_country = '';
                $search_phone = '';
                $search_phone_perso = '';
                $search_phone_mobile = '';
                $search_morphy = "";
                $search_categ = "";
                $search_filter = "";
                $search_status = "";
                $search_import_key = '';
                $catid = "";
                $search_all = "";
                $toselect = [];
                $search_datec_start = '';
                $search_datec_end = '';
                $search_datem_start = '';
                $search_datem_end = '';
                $search_array_options = [];
            }

            // Close
            if ($massaction == 'close' && $user->hasRight('adherent', 'creer')) {
                $tmpmember = new Adherent($db);
                $error = 0;
                $nbclose = 0;

                $db->begin();

                foreach ($toselect as $idtoclose) {
                    $tmpmember->fetch($idtoclose);
                    $result = $tmpmember->resiliate($user);

                    if ($result < 0 && !count($tmpmember->errors)) {
                        setEventMessages($tmpmember->error, $tmpmember->errors, 'errors');
                    } else {
                        if ($result > 0) {
                            $nbclose++;
                        }
                    }
                }

                if (!$error) {
                    setEventMessages($langs->trans("XMembersClosed", $nbclose), null, 'mesgs');

                    $db->commit();
                } else {
                    $db->rollback();
                }
            }

            // Create external user
            if ($massaction == 'createexternaluser' && $user->hasRight('adherent', 'creer') && $user->hasRight('user', 'user', 'creer')) {
                $tmpmember = new Adherent($db);
                $error = 0;
                $nbcreated = 0;

                $db->begin();

                foreach ($toselect as $idtoclose) {
                    $tmpmember->fetch($idtoclose);

                    if (!empty($tmpmember->fk_soc)) {
                        $nuser = new User($db);
                        $tmpuser = dol_clone($tmpmember);

                        $result = $nuser->create_from_member($tmpuser, $tmpmember->login);

                        if ($result < 0 && !count($tmpmember->errors)) {
                            setEventMessages($tmpmember->error, $tmpmember->errors, 'errors');
                        } else {
                            if ($result > 0) {
                                $nbcreated++;
                            }
                        }
                    }
                }

                if (!$error) {
                    setEventMessages($langs->trans("XExternalUserCreated", $nbcreated), null, 'mesgs');

                    $db->commit();
                } else {
                    $db->rollback();
                }
            }

            // Create external user
            if ($action == 'createsubscription_confirm' && $confirm == "yes" && $user->hasRight('adherent', 'creer')) {
                $tmpmember = new Adherent($db);
                $adht = new AdherentType($db);
                $error = 0;
                $nbcreated = 0;
                $now = dol_now();
                $amount = price2num(GETPOST('amount', 'alpha'));
                $db->begin();
                foreach ($toselect as $id) {
                    $res = $tmpmember->fetch($id);
                    if ($res > 0) {
                        $result = $tmpmember->subscription($now, $amount);
                        if ($result < 0) {
                            $error++;
                        } else {
                            $nbcreated++;
                        }
                    } else {
                        $error++;
                    }
                }

                if (!$error) {
                    setEventMessages($langs->trans("XSubsriptionCreated", $nbcreated), null, 'mesgs');
                    $db->commit();
                } else {
                    setEventMessages($langs->trans("XSubsriptionError", $error), null, 'mesgs');
                    $db->rollback();
                }
            }

            // Mass actions
            $objectclass = 'Adherent';
            $objectlabel = 'Members';
            $permissiontoread = $user->hasRight('adherent', 'lire');
            $permissiontodelete = $user->hasRight('adherent', 'supprimer');
            $permissiontoadd = $user->hasRight('adherent', 'creer');
            $uploaddir = $conf->adherent->dir_output;
            include DOL_DOCUMENT_ROOT . '/core/actions_massactions.inc.php';
        }

        /*
         * View
         */

        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Adherent/Views/list.php');

        $db->close();
    }

    /**
     *      \file       htdocs/adherents/note.php
     *      \ingroup    member
     *      \brief      Tab for note of a member
     */
    public function note()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(array("companies", "members", "bills"));


// Get parameters
        $action = GETPOST('action', 'aZ09');
        $id = GETPOSTINT('id');
        $ref = GETPOST('ref', 'alphanohtml');


// Initialize objects
        $object = new Adherent($db);

        $result = $object->fetch($id);
        if ($result > 0) {
            $adht = new AdherentType($db);
            $result = $adht->fetch($object->typeid);
        }


        $permissionnote = $user->hasRight('adherent', 'creer'); // Used by the include of actions_setnotes.inc.php

// Fetch object
        if ($id > 0 || !empty($ref)) {
            // Load member
            $result = $object->fetch($id, $ref);

            // Define variables to know what current user can do on users
            $canadduser = ($user->admin || $user->hasRight('user', 'user', 'creer'));
            // Define variables to know what current user can do on properties of user linked to edited member
            if ($object->user_id) {
                // $User is the user who edits, $object->user_id is the id of the related user in the edited member
                $caneditfielduser = ((($user->id == $object->user_id) && $user->hasRight('user', 'self', 'creer'))
                    || (($user->id != $object->user_id) && $user->hasRight('user', 'user', 'creer')));
                $caneditpassworduser = ((($user->id == $object->user_id) && $user->hasRight('user', 'self', 'password'))
                    || (($user->id != $object->user_id) && $user->hasRight('user', 'user', 'password')));
            }
        }

// Define variables to determine what the current user can do on the members
        $canaddmember = $user->hasRight('adherent', 'creer');
// Define variables to determine what the current user can do on the properties of a member
        if ($id) {
            $caneditfieldmember = $user->hasRight('adherent', 'creer');
        }

        $hookmanager->initHooks(array('membernote'));

// Security check
        $result = restrictedArea($user, 'adherent', $object->id, '', '', 'socid', 'rowid', 0);

        /*
         * Actions
         */
        $parameters = array();
        $reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }
        if (empty($reshook)) {
            include DOL_DOCUMENT_ROOT . '/core/actions_setnotes.inc.php'; // Must be include, not include_once
        }


        /*
         * View
         */

        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Adherent/Views/note.php');

        $title = $langs->trans("Member") . " - " . $langs->trans("Note");

        $help_url = "EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros|DE:Modul_Mitglieder";

        llxHeader("", $title, $help_url);

        $form = new Form($db);

        if ($id) {
            $head = member_prepare_head($object);

            print dol_get_fiche_head($head, 'note', $langs->trans("Member"), -1, 'user');

            print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
            print '<input type="hidden" name="token" value="' . newToken() . '">';

            $linkback = '<a href="' . DOL_URL_ROOT . '/adherents/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

            $morehtmlref = '<a href="' . DOL_URL_ROOT . '/adherents/vcard.php?id=' . $object->id . '" class="refid">';
            $morehtmlref .= img_picto($langs->trans("Download") . ' ' . $langs->trans("VCard"), 'vcard.png', 'class="valignmiddle marginleftonly paddingrightonly"');
            $morehtmlref .= '</a>';

            dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref);

            print '<div class="fichecenter">';

            print '<div class="underbanner clearboth"></div>';
            print '<table class="border centpercent tableforfield">';

            // Login
            if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
                print '<tr><td class="titlefield">' . $langs->trans("Login") . ' / ' . $langs->trans("Id") . '</td><td class="valeur">' . dol_escape_htmltag($object->login) . '</td></tr>';
            }

            // Type
            print '<tr><td>' . $langs->trans("Type") . '</td>';
            print '<td class="valeur">' . $adht->getNomUrl(1) . "</td></tr>\n";

            // Morphy
            print '<tr><td class="titlefield">' . $langs->trans("MemberNature") . '</td>';
            print '<td class="valeur" >' . $object->getmorphylib('', 1) . '</td>';
            print '</tr>';

            // Company
            print '<tr><td>' . $langs->trans("Company") . '</td><td class="valeur">' . dol_escape_htmltag($object->company) . '</td></tr>';

            // Civility
            print '<tr><td>' . $langs->trans("UserTitle") . '</td><td class="valeur">' . $object->getCivilityLabel() . '</td>';
            print '</tr>';

            print "</table>";

            print '</div>';


            $cssclass = 'titlefield';
            $permission = $user->hasRight('adherent', 'creer'); // Used by the include of notes.tpl.php
            include DOL_DOCUMENT_ROOT . '/core/tpl/notes.tpl.php';


            print dol_get_fiche_end();
        }

// End of page
        llxFooter();
        $db->close();
    }

    /**
     *      \file       partnership_card.php
     *      \ingroup    partnership
     *      \brief      Page to create/edit/view partnership
     */
    public function partnership()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(array("companies","members","partnership", "other"));

// Get parameters
        $id = GETPOSTINT('rowid') ? GETPOSTINT('rowid') : GETPOSTINT('id');
        $ref = GETPOST('ref', 'alpha');
        $action = GETPOST('action', 'aZ09');
        $confirm = GETPOST('confirm', 'alpha');
        $cancel = GETPOST('cancel', 'aZ09');
        $contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'partnershipcard'; // To manage different context of search
        $backtopage = GETPOST('backtopage', 'alpha');
        $backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
//$lineid   = GETPOST('lineid', 'int');

        $object = new Adherent($db);
        if ($id > 0) {
            $object->fetch($id);
        }

// Initialize technical objects
        $object         = new Partnership($db);
        $extrafields    = new ExtraFields($db);
        $adht           = new AdherentType($db);
        $diroutputmassaction = $conf->partnership->dir_output . '/temp/massgeneration/' . $user->id;
        $hookmanager->initHooks(array('partnershipthirdparty', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
        $extrafields->fetch_name_optionals_label($object->table_element);

        $search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criteria
        $search_all = GETPOST("search_all", 'alpha');
        $search = array();

        foreach ($object->fields as $key => $val) {
            if (GETPOST('search_' . $key, 'alpha')) {
                $search[$key] = GETPOST('search_' . $key, 'alpha');
            }
        }

// Load object
        include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

        $permissiontoread = $user->hasRight('partnership', 'read');
        $permissiontoadd = $user->hasRight('partnership', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
        $permissiontodelete = $user->hasRight('partnership', 'delete') || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
        $permissionnote = $user->hasRight('partnership', 'write'); // Used by the include of actions_setnotes.inc.php
        $permissiondellink = $user->hasRight('partnership', 'write'); // Used by the include of actions_dellink.inc.php
        $usercanclose = $user->hasRight('partnership', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
        $upload_dir = $conf->partnership->multidir_output[isset($object->entity) ? $object->entity : 1];


        if (getDolGlobalString('PARTNERSHIP_IS_MANAGED_FOR') != 'member') {
            accessforbidden('Partnership module is not activated for members');
        }
        if (!isModEnabled('partnership')) {
            accessforbidden();
        }
        if (empty($permissiontoread)) {
            accessforbidden();
        }
        if ($action == 'edit' && empty($permissiontoadd)) {
            accessforbidden();
        }
        if (($action == 'update' || $action == 'edit') && $object->status != $object::STATUS_DRAFT) {
            accessforbidden();
        }


// Security check
        $result = restrictedArea($user, 'adherent', $id, '', '', 'socid', 'rowid', 0);


        /*
         * Actions
         */

        $parameters = array();
        $reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

        $date_start = dol_mktime(0, 0, 0, GETPOSTINT('date_partnership_startmonth'), GETPOSTINT('date_partnership_startday'), GETPOSTINT('date_partnership_startyear'));
        $date_end = dol_mktime(0, 0, 0, GETPOSTINT('date_partnership_endmonth'), GETPOSTINT('date_partnership_endday'), GETPOSTINT('date_partnership_endyear'));

        if (empty($reshook)) {
            $error = 0;

            $backtopage = dol_buildpath('/partnership/partnership.php', 1) . '?rowid=' . ($id > 0 ? $id : '__ID__');

            // Actions when linking object each other
            include DOL_DOCUMENT_ROOT . '/core/actions_dellink.inc.php';
        }

        $object->fields['fk_member']['visible'] = 0;
        if ($object->id > 0 && $object->status == $object::STATUS_REFUSED && empty($action)) {
            $object->fields['reason_decline_or_cancel']['visible'] = 1;
        }
        $object->fields['note_public']['visible'] = 1;


        /*
         * View
         */

        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Adherent/Views/partnership.php');

        $db->close();
    }

    /**
     *       \file       htdocs/adherents/subscription.php
     *       \ingroup    member
     *       \brief      tab for Adding, editing, deleting a member's memberships
     */
    public function subscription()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

        $langs->loadLangs(array("companies", "bills", "members", "users", "mails", 'other'));

        $action = GETPOST('action', 'aZ09');
        $confirm = GETPOST('confirm', 'alpha');
        $contextpage = GETPOST('contextpage', 'aZ09');
        $optioncss = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')

        $id = GETPOSTINT('rowid') ? GETPOSTINT('rowid') : GETPOSTINT('id');
        $rowid = $id;
        $ref = GETPOST('ref', 'alphanohtml');
        $typeid = GETPOSTINT('typeid');
        $cancel = GETPOST('cancel');

// Load variable for pagination
        $limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
        $sortfield = GETPOST('sortfield', 'aZ09comma');
        $sortorder = GETPOST('sortorder', 'aZ09comma');
        $page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
        if (empty($page) || $page == -1) {
            $page = 0;
        }     // If $page is not defined, or '' or -1
        $offset = $limit * $page;
        $pageprev = $page - 1;
        $pagenext = $page + 1;

// Default sort order (if not yet defined by previous GETPOST)
        if (!$sortfield) {
            $sortfield = "c.rowid";
        }
        if (!$sortorder) {
            $sortorder = "DESC";
        }

        $object = new Adherent($db);
        $extrafields = new ExtraFields($db);
        $adht = new AdherentType($db);

// fetch optionals attributes and labels
        $extrafields->fetch_name_optionals_label($object->table_element);

        $errmsg = '';

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
        $hookmanager->initHooks(array('subscription'));

// PDF
        $hidedetails = (GETPOSTINT('hidedetails') ? GETPOSTINT('hidedetails') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS') ? 1 : 0));
        $hidedesc = (GETPOSTINT('hidedesc') ? GETPOSTINT('hidedesc') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DESC') ? 1 : 0));
        $hideref = (GETPOSTINT('hideref') ? GETPOSTINT('hideref') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_REF') ? 1 : 0));

        $datefrom = 0;
        $dateto = 0;
        $paymentdate = -1;

// Fetch object
        if ($id > 0 || !empty($ref)) {
            // Load member
            $result = $object->fetch($id, $ref);

            // Define variables to know what current user can do on users
            $canadduser = ($user->admin || $user->hasRight("user", "user", "creer"));
            // Define variables to know what current user can do on properties of user linked to edited member
            if ($object->user_id) {
                // $User is the user who edits, $object->user_id is the id of the related user in the edited member
                $caneditfielduser = ((($user->id == $object->user_id) && $user->hasRight("user", "self", "creer"))
                    || (($user->id != $object->user_id) && $user->hasRight("user", "user", "creer")));
                $caneditpassworduser = ((($user->id == $object->user_id) && $user->hasRight("user", "self", "password"))
                    || (($user->id != $object->user_id) && $user->hasRight("user", "user", "password")));
            }
        }

// Define variables to determine what the current user can do on the members
        $canaddmember = $user->hasRight('adherent', 'creer');
// Define variables to determine what the current user can do on the properties of a member
        if ($id) {
            $caneditfieldmember = $user->hasRight('adherent', 'creer');
        }

// Security check
        $result = restrictedArea($user, 'adherent', $object->id, '', '', 'socid', 'rowid', 0);


        /*
         *  Actions
         */

        $parameters = array();
        $reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

// Create third party from a member
        if (empty($reshook) && $action == 'confirm_create_thirdparty' && $confirm == 'yes' && $user->hasRight('societe', 'creer')) {
            if ($result > 0) {
                // Creation of thirdparty
                $company = new Societe($db);
                $result = $company->create_from_member($object, GETPOST('companyname', 'alpha'), GETPOST('companyalias', 'alpha'), GETPOST('customercode', 'alpha'));

                if ($result < 0) {
                    $langs->load("errors");
                    setEventMessages($company->error, $company->errors, 'errors');
                } else {
                    $action = 'addsubscription';
                }
            } else {
                setEventMessages($object->error, $object->errors, 'errors');
            }
        }

        if (empty($reshook) && $action == 'setuserid' && ($user->hasRight('user', 'self', 'creer') || $user->hasRight('user', 'user', 'creer'))) {
            $error = 0;
            if (!$user->hasRight('user', 'user', 'creer')) {    // If can edit only itself user, we can link to itself only
                if (GETPOSTINT("userid") != $user->id && GETPOSTINT("userid") != $object->user_id) {
                    $error++;
                    setEventMessages($langs->trans("ErrorUserPermissionAllowsToLinksToItselfOnly"), null, 'errors');
                }
            }

            if (!$error) {
                if (GETPOSTINT("userid") != $object->user_id) {  // If link differs from currently in database
                    $result = $object->setUserId(GETPOSTINT("userid"));
                    if ($result < 0) {
                        dol_print_error(null, $object->error);
                    }
                    $action = '';
                }
            }
        }

        if (empty($reshook) && $action == 'setsocid') {
            $error = 0;
            if (!$error) {
                if (GETPOSTINT('socid') != $object->fk_soc) {    // If link differs from currently in database
                    $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "adherent";
                    $sql .= " WHERE fk_soc = '" . GETPOSTINT('socid') . "'";
                    $resql = $db->query($sql);
                    if ($resql) {
                        $obj = $db->fetch_object($resql);
                        if ($obj && $obj->rowid > 0) {
                            $othermember = new Adherent($db);
                            $othermember->fetch($obj->rowid);
                            $thirdparty = new Societe($db);
                            $thirdparty->fetch(GETPOSTINT('socid'));
                            $error++;
                            setEventMessages($langs->trans("ErrorMemberIsAlreadyLinkedToThisThirdParty", $othermember->getFullName($langs), $othermember->login, $thirdparty->name), null, 'errors');
                        }
                    }

                    if (!$error) {
                        $result = $object->setThirdPartyId(GETPOSTINT('socid'));
                        if ($result < 0) {
                            dol_print_error(null, $object->error);
                        }
                        $action = '';
                    }
                }
            }
        }

        if ($user->hasRight('adherent', 'cotisation', 'creer') && $action == 'subscription' && !$cancel) {
            $error = 0;

            $langs->load("banks");

            $result = $object->fetch($rowid);
            $result = $adht->fetch($object->typeid);

            // Subscription information
            $datesubscription = 0;
            $datesubend = 0;
            $defaultdelay = !empty($adht->duration_value) ? $adht->duration_value : 1;
            $defaultdelayunit = !empty($adht->duration_unit) ? $adht->duration_unit : 'y';
            $paymentdate = ''; // Do not use 0 here, default value is '' that means not filled where 0 means 1970-01-01
            if (GETPOSTINT("reyear") && GETPOSTINT("remonth") && GETPOSTINT("reday")) {
                $datesubscription = dol_mktime(0, 0, 0, GETPOSTINT("remonth"), GETPOSTINT("reday"), GETPOSTINT("reyear"));
            }
            if (GETPOSTINT("endyear") && GETPOSTINT("endmonth") && GETPOSTINT("endday")) {
                $datesubend = dol_mktime(0, 0, 0, GETPOSTINT("endmonth"), GETPOSTINT("endday"), GETPOSTINT("endyear"));
            }
            if (GETPOSTINT("paymentyear") && GETPOSTINT("paymentmonth") && GETPOSTINT("paymentday")) {
                $paymentdate = dol_mktime(0, 0, 0, GETPOSTINT("paymentmonth"), GETPOSTINT("paymentday"), GETPOSTINT("paymentyear"));
            }
            $amount = price2num(GETPOST("subscription", 'alpha')); // Amount of subscription
            $label = GETPOST("label");

            // Payment information
            $accountid = GETPOSTINT("accountid");
            $operation = GETPOST("operation", "alphanohtml"); // Payment mode
            $num_chq = GETPOST("num_chq", "alphanohtml");
            $emetteur_nom = GETPOST("chqemetteur");
            $emetteur_banque = GETPOST("chqbank");
            $option = GETPOST("paymentsave");
            if (empty($option)) {
                $option = 'none';
            }
            $sendalsoemail = GETPOST("sendmail", 'alpha');

            // Check parameters
            if (!$datesubscription) {
                $error++;
                $langs->load("errors");
                $errmsg = $langs->trans("ErrorBadDateFormat", $langs->transnoentitiesnoconv("DateSubscription"));
                setEventMessages($errmsg, null, 'errors');
                $action = 'addsubscription';
            }
            if (GETPOST('end') && !$datesubend) {
                $error++;
                $langs->load("errors");
                $errmsg = $langs->trans("ErrorBadDateFormat", $langs->transnoentitiesnoconv("DateEndSubscription"));
                setEventMessages($errmsg, null, 'errors');
                $action = 'addsubscription';
            }
            if (!$datesubend) {
                $datesubend = dol_time_plus_duree(dol_time_plus_duree($datesubscription, $defaultdelay, $defaultdelayunit), -1, 'd');
            }
            if (($option == 'bankviainvoice' || $option == 'bankdirect') && !$paymentdate) {
                $error++;
                $errmsg = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("DatePayment"));
                setEventMessages($errmsg, null, 'errors');
                $action = 'addsubscription';
            }

            // Check if a payment is mandatory or not
            if ($adht->subscription) {  // Member type need subscriptions
                if (!is_numeric($amount)) {
                    // If field is '' or not a numeric value
                    $errmsg = $langs->trans("ErrorFieldRequired", $langs->transnoentities("Amount"));
                    setEventMessages($errmsg, null, 'errors');
                    $error++;
                    $action = 'addsubscription';
                } else {
                    // If an amount has been provided, we check also fields that becomes mandatory when amount is not null.
                    if (isModEnabled('bank') && GETPOST("paymentsave") != 'none') {
                        if (GETPOST("subscription")) {
                            if (!GETPOST("label")) {
                                $errmsg = $langs->trans("ErrorFieldRequired", $langs->transnoentities("Label"));
                                setEventMessages($errmsg, null, 'errors');
                                $error++;
                                $action = 'addsubscription';
                            }
                            if (GETPOST("paymentsave") != 'invoiceonly' && !GETPOST("operation")) {
                                $errmsg = $langs->trans("ErrorFieldRequired", $langs->transnoentities("PaymentMode"));
                                setEventMessages($errmsg, null, 'errors');
                                $error++;
                                $action = 'addsubscription';
                            }
                            if (GETPOST("paymentsave") != 'invoiceonly' && !(GETPOSTINT("accountid") > 0)) {
                                $errmsg = $langs->trans("ErrorFieldRequired", $langs->transnoentities("FinancialAccount"));
                                setEventMessages($errmsg, null, 'errors');
                                $error++;
                                $action = 'addsubscription';
                            }
                        } else {
                            if (GETPOSTINT("accountid")) {
                                $errmsg = $langs->trans("ErrorDoNotProvideAccountsIfNullAmount");
                                setEventMessages($errmsg, null, 'errors');
                                $error++;
                                $action = 'addsubscription';
                            }
                        }
                    }
                }
            }

            // Record the subscription then complementary actions
            if (!$error && $action == 'subscription') {
                $db->begin();

                // Create subscription
                $crowid = $object->subscription($datesubscription, $amount, $accountid, $operation, $label, $num_chq, $emetteur_nom, $emetteur_banque, $datesubend);
                if ($crowid <= 0) {
                    $error++;
                    $errmsg = $object->error;
                    setEventMessages($object->error, $object->errors, 'errors');
                }

                if (!$error) {
                    $result = $object->subscriptionComplementaryActions($crowid, $option, $accountid, $datesubscription, $paymentdate, $operation, $label, $amount, $num_chq, $emetteur_nom, $emetteur_banque);
                    if ($result < 0) {
                        $error++;
                        setEventMessages($object->error, $object->errors, 'errors');
                    } else {
                        // If an invoice was created, it is into $object->invoice
                    }
                }

                if (!$error) {
                    $db->commit();
                } else {
                    $db->rollback();
                    $action = 'addsubscription';
                }

                if (!$error) {
                    setEventMessages("SubscriptionRecorded", null, 'mesgs');
                }

                // Send email
                if (!$error) {
                    // Send confirmation Email
                    if ($object->email && $sendalsoemail) {   // $object is 'Adherent'
                        $parameters = array(
                            'datesubscription' => $datesubscription,
                            'amount' => $amount,
                            'ccountid' => $accountid,
                            'operation' => $operation,
                            'label' => $label,
                            'num_chq' => $num_chq,
                            'emetteur_nom' => $emetteur_nom,
                            'emetteur_banque' => $emetteur_banque,
                            'datesubend' => $datesubend
                        );
                        $reshook = $hookmanager->executeHooks('sendMail', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
                        if ($reshook < 0) {
                            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
                        }

                        if (empty($reshook)) {
                            $subject = '';
                            $msg = '';

                            // Send subscription email
                            include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
                            $formmail = new FormMail($db);
                            // Set output language
                            $outputlangs = new Translate('', $conf);
                            $outputlangs->setDefaultLang(empty($object->thirdparty->default_lang) ? $mysoc->default_lang : $object->thirdparty->default_lang);
                            // Load traductions files required by page
                            $outputlangs->loadLangs(array("main", "members"));

                            // Get email content from template
                            $arraydefaultmessage = null;
                            $labeltouse = getDolGlobalString('ADHERENT_EMAIL_TEMPLATE_SUBSCRIPTION');

                            if (!empty($labeltouse)) {
                                $arraydefaultmessage = $formmail->getEMailTemplate($db, 'member', $user, $outputlangs, 0, 1, $labeltouse);
                            }

                            if (!empty($labeltouse) && is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0) {
                                $subject = $arraydefaultmessage->topic;
                                $msg     = $arraydefaultmessage->content;
                            }

                            $substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
                            complete_substitutions_array($substitutionarray, $outputlangs, $object);
                            $subjecttosend = make_substitutions($subject, $substitutionarray, $outputlangs);
                            $texttosend = make_substitutions(dol_concatdesc($msg, $adht->getMailOnSubscription()), $substitutionarray, $outputlangs);

                            // Attach a file ?
                            $file = '';
                            $listofpaths = array();
                            $listofnames = array();
                            $listofmimes = array();
                            if (is_object($object->invoice) && (!is_object($arraydefaultmessage) || intval($arraydefaultmessage->joinfiles))) {
                                $invoicediroutput = $conf->facture->dir_output;
                                $fileparams = dol_most_recent_file($invoicediroutput . '/' . $object->invoice->ref, preg_quote($object->invoice->ref, '/') . '[^\-]+');
                                $file = $fileparams['fullname'];

                                $listofpaths = array($file);
                                $listofnames = array(basename($file));
                                $listofmimes = array(dol_mimetype($file));
                            }

                            $moreinheader = 'X-Dolibarr-Info: send_an_email by adherents/subscription.php' . "\r\n";

                            $result = $object->sendEmail($texttosend, $subjecttosend, $listofpaths, $listofmimes, $listofnames, "", "", 0, -1, '', $moreinheader);
                            if ($result < 0) {
                                $errmsg = $object->error;
                                setEventMessages($object->error, $object->errors, 'errors');
                            } else {
                                setEventMessages($langs->trans("EmailSentToMember", $object->email), null, 'mesgs');
                            }
                        }
                    } else {
                        setEventMessages($langs->trans("NoEmailSentToMember"), null, 'mesgs');
                    }
                }

                // Clean some POST vars
                if (!$error) {
                    $_POST["subscription"] = '';
                    $_POST["accountid"] = '';
                    $_POST["operation"] = '';
                    $_POST["label"] = '';
                    $_POST["num_chq"] = '';
                }
            }
        }



        /*
         * View
         */

        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Adherent/Views/subscription.php');

        $db->close();
    }

    /**
     *      \file       htdocs/adherents/type.php
     *      \ingroup    member
     *      \brief      Member's type setup
     */
    public function type()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->load("members");

        $rowid  = GETPOSTINT('rowid');
        $action = GETPOST('action', 'aZ09');
        $massaction = GETPOST('massaction', 'alpha');
        $cancel = GETPOST('cancel', 'alpha');
        $toselect   = GETPOST('toselect', 'array');
        $contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : str_replace('_', '', basename(dirname(__FILE__)) . basename(__FILE__, '.php')); // To manage different context of search
        $backtopage = GETPOST('backtopage', 'alpha');
        $mode = GETPOST('mode', 'alpha');

        $sall = GETPOST("sall", "alpha");
        $filter = GETPOST("filter", 'alpha');
        $search_ref = GETPOST('search_ref', 'alpha');
        $search_lastname = GETPOST('search_lastname', 'alpha');
        $search_login = GETPOST('search_login', 'alpha');
        $search_email = GETPOST('search_email', 'alpha');
        $type = GETPOST('type', 'intcomma');
        $status = GETPOST('status', 'alpha');
        $optioncss = GETPOST('optioncss', 'alpha');

// Load variable for pagination
        $limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
        $sortfield = GETPOST('sortfield', 'aZ09comma');
        $sortorder = GETPOST('sortorder', 'aZ09comma');
        $page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
        if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
            // If $page is not defined, or '' or -1 or if we click on clear filters
            $page = 0;
        }
        $offset = $limit * $page;
        $pageprev = $page - 1;
        $pagenext = $page + 1;
        if (!$sortorder) {
            $sortorder = "DESC";
        }
        if (!$sortfield) {
            $sortfield = "d.lastname";
        }

        $label = GETPOST("label", "alpha");
        $morphy = GETPOST("morphy", "alpha");
        $status = GETPOSTINT("status");
        $subscription = GETPOSTINT("subscription");
        $amount = GETPOST('amount', 'alpha');
        $duration_value = GETPOSTINT('duration_value');
        $duration_unit = GETPOST('duration_unit', 'alpha');
        $vote = GETPOSTINT("vote");
        $comment = GETPOST("comment", 'restricthtml');
        $mail_valid = GETPOST("mail_valid", 'restricthtml');
        $caneditamount = GETPOSTINT("caneditamount");

// Initialize technical objects
        $object = new AdherentType($db);
        $extrafields = new ExtraFields($db);
        $hookmanager->initHooks(array('membertypecard', 'globalcard'));

// Fetch optionals attributes and labels
        $extrafields->fetch_name_optionals_label($object->table_element);

// Security check
        $result = restrictedArea($user, 'adherent', $rowid, 'adherent_type');


        /*
         *  Actions
         */

        if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
            $search_ref = "";
            $search_lastname = "";
            $search_login = "";
            $search_email = "";
            $type = "";
            $sall = "";
        }

        if (GETPOST('cancel', 'alpha')) {
            $action = 'list';
            $massaction = '';
        }
        if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
            $massaction = '';
        }

        if ($cancel) {
            $action = '';

            if (!empty($backtopage)) {
                header("Location: " . $backtopage);
                exit;
            }
        }

        if ($action == 'add' && $user->hasRight('adherent', 'configurer')) {
            $object->label = trim($label);
            $object->morphy = trim($morphy);
            $object->status = (int) $status;
            $object->subscription = (int) $subscription;
            $object->amount = ($amount == '' ? '' : price2num($amount, 'MT'));
            $object->caneditamount = $caneditamount;
            $object->duration_value = $duration_value;
            $object->duration_unit = $duration_unit;
            $object->note_public = trim($comment);
            $object->note_private = '';
            $object->mail_valid = trim($mail_valid);
            $object->vote = (int) $vote;

            // Fill array 'array_options' with data from add form
            $ret = $extrafields->setOptionalsFromPost(null, $object);
            if ($ret < 0) {
                $error++;
            }

            if (empty($object->label)) {
                $error++;
                setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Label")), null, 'errors');
            } else {
                $sql = "SELECT libelle FROM " . MAIN_DB_PREFIX . "adherent_type WHERE libelle = '" . $db->escape($object->label) . "'";
                $sql .= " WHERE entity IN (" . getEntity('member_type') . ")";
                $result = $db->query($sql);
                $num = null;
                if ($result) {
                    $num = $db->num_rows($result);
                }
                if ($num) {
                    $error++;
                    $langs->load("errors");
                    setEventMessages($langs->trans("ErrorLabelAlreadyExists", $login), null, 'errors');
                }
            }

            if (!$error) {
                $id = $object->create($user);
                if ($id > 0) {
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit;
                } else {
                    setEventMessages($object->error, $object->errors, 'errors');
                    $action = 'create';
                }
            } else {
                $action = 'create';
            }
        }

        if ($action == 'update' && $user->hasRight('adherent', 'configurer')) {
            $object->fetch($rowid);

            $object->oldcopy = dol_clone($object, 2);

            $object->label = trim($label);
            $object->morphy = trim($morphy);
            $object->status = (int) $status;
            $object->subscription = (int) $subscription;
            $object->amount = ($amount == '' ? '' : price2num($amount, 'MT'));
            $object->caneditamount = $caneditamount;
            $object->duration_value = $duration_value;
            $object->duration_unit = $duration_unit;
            $object->note_public = trim($comment);
            $object->note_private = '';
            $object->mail_valid = trim($mail_valid);
            $object->vote = (bool) trim($vote);

            // Fill array 'array_options' with data from add form
            $ret = $extrafields->setOptionalsFromPost(null, $object, '@GETPOSTISSET');
            if ($ret < 0) {
                $error++;
            }

            $ret = $object->update($user);

            if ($ret >= 0 && !count($object->errors)) {
                setEventMessages($langs->trans("MemberTypeModified"), null, 'mesgs');
            } else {
                setEventMessages($object->error, $object->errors, 'errors');
            }

            header("Location: " . $_SERVER['PHP_SELF'] . "?rowid=" . $object->id);
            exit;
        }

        if ($action == 'confirm_delete' && $user->hasRight('adherent', 'configurer')) {
            $object->fetch($rowid);
            $res = $object->delete($user);

            if ($res > 0) {
                setEventMessages($langs->trans("MemberTypeDeleted"), null, 'mesgs');
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                setEventMessages($langs->trans("MemberTypeCanNotBeDeleted"), null, 'errors');
                $action = '';
            }
        }


        /*
         * View
         */

        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Adherent/Views/type.php');

        $db->close();
    }

    /**
     *      \file       htdocs/adherents/type_ldap.php
     *      \ingroup    ldap
     *      \brief      Page fiche LDAP members types
     */
    public function type_ldap()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(array("admin", "members", "ldap"));

        $id = GETPOSTINT('rowid');
        $action = GETPOST('action', 'aZ09');

// Security check
        $result = restrictedArea($user, 'adherent', $id, 'adherent_type');

        $object = new AdherentType($db);
        $object->fetch($id);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
        $hookmanager->initHooks(array('membertypeldapcard', 'globalcard'));

        /*
         * Actions
         */


        $parameters = array();
        $reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

        if (empty($reshook)) {
            if ($action == 'dolibarr2ldap') {
                $ldap = new Ldap();
                $result = $ldap->connectBind();

                if ($result > 0) {
                    $object->listMembersForMemberType('', 1);

                    $info = $object->_load_ldap_info();
                    $dn = $object->_load_ldap_dn($info);
                    $olddn = $dn; // We can say that old dn = dn as we force synchro

                    $result = $ldap->update($dn, $info, $user, $olddn);
                }

                if ($result >= 0) {
                    setEventMessages($langs->trans("MemberTypeSynchronized"), null, 'mesgs');
                } else {
                    setEventMessages($ldap->error, $ldap->errors, 'errors');
                }
            }
        }

        /*
         * View
         */

        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Adherent/Views/type_ldap.php');

        $db->close();
    }

    /**
     *  \file       htdocs/adherents/type_translation.php
     *  \ingroup    product
     *  \brief      Member translation page
     */
    public function type_translation()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(array('members', 'languages'));

        $id = GETPOSTINT('rowid') ? GETPOSTINT('rowid') : GETPOSTINT('id');
        $action = GETPOST('action', 'aZ09');
        $cancel = GETPOST('cancel', 'alpha');
        $ref = GETPOST('ref', 'alphanohtml');

// Security check
        $fieldvalue = (!empty($id) ? $id : (!empty($ref) ? $ref : ''));
        $fieldtype = (!empty($ref) ? 'ref' : 'rowid');
        if ($user->socid) {
            $socid = $user->socid;
        }
// Security check
        $result = restrictedArea($user, 'adherent', $id, 'adherent_type');


        /*
         * Actions
         */

// return to translation display if cancellation
        if ($cancel == $langs->trans("Cancel")) {
            $action = '';
        }

        if ($action == 'delete' && GETPOST('langtodelete', 'alpha')) {
            $object = new AdherentType($db);
            $object->fetch($id);
            $result = $object->delMultiLangs(GETPOST('langtodelete', 'alpha'), $user);
            if ($result > 0) {
                setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
                header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . $id);
                exit;
            }
        }

// Add translation
        if ($action == 'vadd' && $cancel != $langs->trans("Cancel") && $user->hasRight('adherent', 'configurer')) {
            $object = new AdherentType($db);
            $object->fetch($id);
            $current_lang = $langs->getDefaultLang();

            $forcelangprod = GETPOST("forcelangprod", 'aZ09');

            // update of object
            if ($forcelangprod == $current_lang) {
                $object->label       = GETPOST("libelle", 'alphanohtml');
                $object->description = dol_htmlcleanlastbr(GETPOST("desc", 'restricthtml'));
                //$object->other         = dol_htmlcleanlastbr(GETPOST("other", 'restricthtml'));
            } else {
                $object->multilangs[$forcelangprod]["label"] = GETPOST("libelle", 'alphanohtml');
                $object->multilangs[$forcelangprod]["description"] = dol_htmlcleanlastbr(GETPOST("desc", 'restricthtml'));
                //$object->multilangs[$forcelangprod]["other"] = dol_htmlcleanlastbr(GETPOST("other", 'restricthtml'));
            }

            // backup into database
            if ($object->setMultiLangs($user) > 0) {
                $action = '';
            } else {
                $action = 'create';
                setEventMessages($object->error, $object->errors, 'errors');
            }
        }

// Edit translation
        if ($action == 'vedit' && $cancel != $langs->trans("Cancel") && $user->hasRight('adherent', 'configurer')) {
            $object = new AdherentType($db);
            $object->fetch($id);
            $current_lang = $langs->getDefaultLang();

            foreach ($object->multilangs as $key => $value) { // saving new values in the object
                if ($key == $current_lang) {
                    $object->label          = GETPOST("libelle-" . $key, 'alphanohtml');
                    $object->description = dol_htmlcleanlastbr(GETPOST("desc-" . $key, 'restricthtml'));
                    $object->other          = dol_htmlcleanlastbr(GETPOST("other-" . $key, 'restricthtml'));
                } else {
                    $object->multilangs[$key]["label"]          = GETPOST("libelle-" . $key, 'alphanohtml');
                    $object->multilangs[$key]["description"] = dol_htmlcleanlastbr(GETPOST("desc-" . $key, 'restricthtml'));
                    $object->multilangs[$key]["other"]          = dol_htmlcleanlastbr(GETPOST("other-" . $key, 'restricthtml'));
                }
            }

            if ($object->setMultiLangs($user) > 0) {
                $action = '';
            } else {
                $action = 'edit';
                setEventMessages($object->error, $object->errors, 'errors');
            }
        }

// Delete translation
        if ($action == 'vdelete' && $cancel != $langs->trans("Cancel") && $user->hasRight('adherent', 'configurer')) {
            $object = new AdherentType($db);
            $object->fetch($id);
            $langtodelete = GETPOST('langdel', 'alpha');


            if ($object->delMultiLangs($langtodelete, $user) > 0) {
                $action = '';
            } else {
                $action = 'edit';
                setEventMessages($object->error, $object->errors, 'errors');
            }
        }

        $object = new AdherentType($db);
        $result = $object->fetch($id);


        /*
         * View
         */

        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Adherent/Views/type_translation.php');
        $db->close();
    }

    /**
     *      \file       htdocs/adherents/vcard.php
     *      \ingroup    societe
     *      \brief      Vcard tab of a member
     */
    public function vcard()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

        $id = GETPOSTINT('id');
        $ref = GETPOST('ref', 'alphanohtml');

        $object = new Adherent($db);

// Fetch object
        if ($id > 0 || !empty($ref)) {
            // Load member
            $result = $object->fetch($id, $ref);

            // Define variables to know what current user can do on users
            $canadduser = ($user->admin || $user->hasRight('user', 'user', 'creer'));
            // Define variables to know what current user can do on properties of user linked to edited member
            if ($object->user_id) {
                // $User is the user who edits, $object->user_id is the id of the related user in the edited member
                $caneditfielduser = ((($user->id == $object->user_id) && $user->hasRight('user', 'self', 'creer'))
                    || (($user->id != $object->user_id) && $user->hasRight('user', 'user', 'creer')));
                $caneditpassworduser = ((($user->id == $object->user_id) && $user->hasRight('user', 'self', 'password'))
                    || (($user->id != $object->user_id) && $user->hasRight('user', 'user', 'password')));
            }
        }

// Define variables to determine what the current user can do on the members
        $canaddmember = $user->hasRight('adherent', 'creer');
// Define variables to determine what the current user can do on the properties of a member
        if ($id) {
            $caneditfieldmember = $user->hasRight('adherent', 'creer');
        }

// Security check
        $result = restrictedArea($user, 'adherent', $object->id, '', '', 'socid', 'rowid', 0);


        /*
         * Actions
         */

// None

        /*
         * View
         */

        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Adherent/Views/vcard.php');
    }
}
