<?php
/* Copyright (C) 2001-2002  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2003		Jean-Louis Bergamo		<jlb@j1b.org>
 * Copyright (C) 2004-2020	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2019       Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2021-2023	Frédéric France			<frederic.france@netlgic.fr>
 * Copyright (C) 2021-2023  Waël Almoman            <info@almoman.com>
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
require_once BASE_PATH . '/core/lib/member.lib.php';
require_once BASE_PATH . '/core/lib/company.lib.php';
require_once BASE_PATH . '/core/lib/images.lib.php';
require_once BASE_PATH . '/core/lib/functions2.lib.php';
require_once BASE_PATH . '/categories/class/categorie.class.php';
require_once BASE_PATH . '/compta/bank/class/account.class.php';
require_once BASE_PATH . '/core/class/extrafields.class.php';
require_once BASE_PATH . '/core/class/html.formadmin.class.php';
require_once BASE_PATH . '/core/class/html.formcompany.class.php';
require_once BASE_PATH . '/core/class/html.formfile.class.php';
require_once BASE_PATH . '/core/class/html.formother.class.php';
require_once BASE_PATH . '/product/class/html.formproduct.class.php';

use Canvas;
use Categorie;
use DolEditor;
use DolGraph;
use DoliCore\Base\DolibarrController;
use DoliModules\Adherent\Model\Adherent;
use DoliModules\Adherent\Model\AdherentType;
use DoliModules\Adherent\Model\Subscription;
use DoliModules\Adherent\Statistics\AdherentStats;
use ExtraFields;
use Form;
use FormActions;
use FormAdmin;
use FormCompany;
use FormFile;
use FormMail;
use FormOther;
use FormProduct;
use InfoBox;
use MailmanSpip;
use Societe;
use Translate;

class AdherentController extends DolibarrController
{
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

        $form = new Form($db);

// Load $resultboxes (selectboxlist + boxactivated + boxlista + boxlistb)
        $resultboxes = FormOther::getBoxesArea($user, "2");

        llxHeader('', $langs->trans("Members"), 'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros|DE:Modul_Mitglieder');

        $staticmember = new Adherent($db);
        $statictype = new AdherentType($db);
        $subscriptionstatic = new Subscription($db);

        print load_fiche_titre($langs->trans("MembersArea"), $resultboxes['selectboxlist'], 'members');

        /*
         * Statistics
         */

        $boxgraph = '';
        if ($conf->use_javascript_ajax) {
            $year = date('Y');
            $numberyears = getDolGlobalInt("MAIN_NB_OF_YEAR_IN_MEMBERSHIP_WIDGET_GRAPH");

            $boxgraph .= '<div class="div-table-responsive-no-min">';
            $boxgraph .= '<table class="noborder nohover centpercent">';
            $boxgraph .= '<tr class="liste_titre"><th colspan="2">' . $langs->trans("Statistics") . ($numberyears ? ' (' . ($year - $numberyears) . ' - ' . $year . ')' : '') . '</th></tr>';
            $boxgraph .= '<tr><td class="center" colspan="2">';

            $stats = new AdherentStats($db, 0, $userid);

            // Show array
            $sumMembers = $stats->countMembersByTypeAndStatus($numberyears);
            if (is_array($sumMembers) && !empty($sumMembers)) {
                $total = $sumMembers['total']['members_draft'] + $sumMembers['total']['members_pending'] + $sumMembers['total']['members_uptodate'] + $sumMembers['total']['members_expired'] + $sumMembers['total']['members_excluded'] + $sumMembers['total']['members_resiliated'];
            } else {
                $total = 0;
            }
            foreach (['members_draft', 'members_pending', 'members_uptodate', 'members_expired', 'members_excluded', 'members_resiliated'] as $val) {
                if (empty($sumMembers['total'][$val])) {
                    $sumMembers['total'][$val] = 0;
                }
            }

            $dataseries = [];
            $dataseries[] = [$langs->transnoentitiesnoconv("MembersStatusToValid"), $sumMembers['total']['members_draft']];            // Draft, not yet validated
            $dataseries[] = [$langs->transnoentitiesnoconv("WaitingSubscription"), $sumMembers['total']['members_pending']];
            $dataseries[] = [$langs->transnoentitiesnoconv("UpToDate"), $sumMembers['total']['members_uptodate']];
            $dataseries[] = [$langs->transnoentitiesnoconv("OutOfDate"), $sumMembers['total']['members_expired']];
            $dataseries[] = [$langs->transnoentitiesnoconv("MembersStatusExcluded"), $sumMembers['total']['members_excluded']];
            $dataseries[] = [$langs->transnoentitiesnoconv("MembersStatusResiliated"), $sumMembers['total']['members_resiliated']];

            include DOL_DOCUMENT_ROOT . '/theme/' . $conf->theme . '/theme_vars.inc.php';

            include_once DOL_DOCUMENT_ROOT . '/core/class/dolgraph.class.php';
            $dolgraph = new DolGraph();
            $dolgraph->SetData($dataseries);
            $dolgraph->SetDataColor(['-' . $badgeStatus0, $badgeStatus1, $badgeStatus4, $badgeStatus8, '-' . $badgeStatus8, $badgeStatus6]);
            $dolgraph->setShowLegend(2);
            $dolgraph->setShowPercent(1);
            $dolgraph->SetType(['pie']);
            $dolgraph->setHeight('200');
            $dolgraph->draw('idgraphstatus');
            $boxgraph .= $dolgraph->show($total ? 0 : 1);

            $boxgraph .= '</td></tr>';
            $boxgraph .= '<tr class="liste_total"><td>' . $langs->trans("Total") . '</td><td class="right">';
            $boxgraph .= $total;
            $boxgraph .= '</td></tr>';
            $boxgraph .= '</table>';
            $boxgraph .= '</div>';
            $boxgraph .= '<br>';
        }

// boxes
        print '<div class="clearboth"></div>';
        print '<div class="fichecenter fichecenterbis">';

        print '<div class="twocolumns">';

        print '<div class="firstcolumn fichehalfleft boxhalfleft" id="boxhalfleft">';

        print $boxgraph;

        print $resultboxes['boxlista'];

        print '</div>' . "\n";

        print '<div class="secondcolumn fichehalfright boxhalfright" id="boxhalfright">';

        print $resultboxes['boxlistb'];

        print '</div>' . "\n";

        print '</div>';
        print '</div>';

        $parameters = ['user' => $user];
        $reshook = $hookmanager->executeHooks('dashboardMembers', $parameters, $object); // Note that $action and $object may have been modified by hook

// End of page
        llxFooter();
        $db->close();
    }

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
         * 	Actions
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

        $form = new Form($db);
        $formfile = new FormFile($db);
        $formadmin = new FormAdmin($db);
        $formcompany = new FormCompany($db);

        $title = $langs->trans("Member") . " - " . $langs->trans("Card");
        $help_url = 'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros|DE:Modul_Mitglieder';
        llxHeader('', $title, $help_url);

        $countrynotdefined = $langs->trans("ErrorSetACountryFirst") . ' (' . $langs->trans("SeeAbove") . ')';

        if (is_object($objcanvas) && $objcanvas->displayCanvasExists($action)) {
            // -----------------------------------------
            // When used with CANVAS
            // -----------------------------------------
            if (empty($object->error) && $id) {
                $object = new Adherent($db);
                $result = $object->fetch($id);
                if ($result <= 0) {
                    dol_print_error(null, $object->error);
                }
            }
            $objcanvas->assign_values($action, $object->id, $object->ref); // Set value for templates
            $objcanvas->display_canvas($action); // Show template
        } else {
            // -----------------------------------------
            // When used in standard mode
            // -----------------------------------------

            // Create mode
            if ($action == 'create') {
                $object->canvas = $canvas;
                $object->state_id = GETPOSTINT('state_id');

                // We set country_id, country_code and country for the selected country
                $object->country_id = GETPOSTINT('country_id') ? GETPOSTINT('country_id') : $mysoc->country_id;
                if ($object->country_id) {
                    $tmparray = getCountry($object->country_id, 'all');
                    $object->country_code = $tmparray['code'];
                    $object->country = $tmparray['label'];
                }

                $soc = new Societe($db);
                if (!empty($socid)) {
                    if ($socid > 0) {
                        $soc->fetch($socid);
                    }

                    if (!($soc->id > 0)) {
                        $langs->load("errors");
                        print($langs->trans('ErrorRecordNotFound'));
                        exit;
                    }
                }

                $adht = new AdherentType($db);

                print load_fiche_titre($langs->trans("NewMember"), '', $object->picto);

                if ($conf->use_javascript_ajax) {
                    print "\n" . '<script type="text/javascript">' . "\n";
                    print 'jQuery(document).ready(function () {
						jQuery("#selectcountry_id").change(function() {
							document.formsoc.action.value="create";
							document.formsoc.submit();
						});
						function initfieldrequired() {
							jQuery("#tdcompany").removeClass("fieldrequired");
							jQuery("#tdlastname").removeClass("fieldrequired");
							jQuery("#tdfirstname").removeClass("fieldrequired");
							if (jQuery("#morphy").val() == \'mor\') {
								jQuery("#tdcompany").addClass("fieldrequired");
							}
							if (jQuery("#morphy").val() == \'phy\') {
								jQuery("#tdlastname").addClass("fieldrequired");
								jQuery("#tdfirstname").addClass("fieldrequired");
							}
						}
						jQuery("#morphy").change(function() {
							initfieldrequired();
						});
						initfieldrequired();
					})';
                    print '</script>' . "\n";
                }

                print '<form name="formsoc" action="' . $_SERVER['PHP_SELF'] . '" method="post" enctype="multipart/form-data">';
                print '<input type="hidden" name="token" value="' . newToken() . '">';
                print '<input type="hidden" name="action" value="add">';
                print '<input type="hidden" name="socid" value="' . $socid . '">';
                if ($backtopage) {
                    print '<input type="hidden" name="backtopage" value="' . ($backtopage != '1' ? $backtopage : $_SERVER["HTTP_REFERER"]) . '">';
                }

                print dol_get_fiche_head('');

                print '<table class="border centpercent">';
                print '<tbody>';

                // Login
                if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
                    print '<tr><td><span class="fieldrequired">' . $langs->trans("Login") . ' / ' . $langs->trans("Id") . '</span></td><td><input type="text" name="member_login" class="minwidth300" maxlength="50" value="' . (GETPOSTISSET("member_login") ? GETPOST("member_login", 'alphanohtml', 2) : $object->login) . '" autofocus="autofocus"></td></tr>';
                }

                // Password
                if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
                    require_once DOL_DOCUMENT_ROOT . '/core/lib/security2.lib.php';
                    $generated_password = getRandomPassword(false);
                    print '<tr><td><span class="fieldrequired">' . $langs->trans("Password") . '</span></td><td>';
                    print '<input type="text" class="minwidth300" maxlength="50" name="password" value="' . dol_escape_htmltag($generated_password) . '">';
                    print '</td></tr>';
                }

                // Type
                print '<tr><td class="fieldrequired">' . $langs->trans("MemberType") . '</td><td>';
                $listetype = $adht->liste_array(1);
                print img_picto('', $adht->picto, 'class="pictofixedwidth"');
                if (count($listetype)) {
                    print $form->selectarray("typeid", $listetype, (GETPOSTINT('typeid') ? GETPOSTINT('typeid') : $typeid), (count($listetype) > 1 ? 1 : 0), 0, 0, '', 0, 0, 0, '', '', 1);
                } else {
                    print '<span class="error">' . $langs->trans("NoTypeDefinedGoToSetup") . '</span>';
                }
                print "</td>\n";

                // Morphy
                $morphys = array();
                $morphys["phy"] = $langs->trans("Physical");
                $morphys["mor"] = $langs->trans("Moral");
                print '<tr><td class="fieldrequired">' . $langs->trans("MemberNature") . "</td><td>\n";
                print $form->selectarray("morphy", $morphys, (GETPOST('morphy', 'alpha') ? GETPOST('morphy', 'alpha') : $object->morphy), 1, 0, 0, '', 0, 0, 0, '', '', 1);
                print "</td>\n";

                // Company
                print '<tr><td id="tdcompany">' . $langs->trans("Company") . '</td><td><input type="text" name="societe" class="minwidth300" maxlength="128" value="' . (GETPOSTISSET('societe') ? GETPOST('societe', 'alphanohtml') : $soc->name) . '"></td></tr>';

                // Civility
                print '<tr><td>' . $langs->trans("UserTitle") . '</td><td>';
                print $formcompany->select_civility(GETPOSTINT('civility_id') ? GETPOSTINT('civility_id') : $object->civility_id, 'civility_id', 'maxwidth150', 1) . '</td>';
                print '</tr>';

                // Lastname
                print '<tr><td id="tdlastname">' . $langs->trans("Lastname") . '</td><td><input type="text" name="lastname" class="minwidth300" maxlength="50" value="' . (GETPOSTISSET('lastname') ? GETPOST('lastname', 'alphanohtml') : $object->lastname) . '"></td>';
                print '</tr>';

                // Firstname
                print '<tr><td id="tdfirstname">' . $langs->trans("Firstname") . '</td><td><input type="text" name="firstname" class="minwidth300" maxlength="50" value="' . (GETPOSTISSET('firstname') ? GETPOST('firstname', 'alphanohtml') : $object->firstname) . '"></td>';
                print '</tr>';

                // Gender
                print '<tr><td>' . $langs->trans("Gender") . '</td>';
                print '<td>';
                $arraygender = array('man' => $langs->trans("Genderman"), 'woman' => $langs->trans("Genderwoman"), 'other' => $langs->trans("Genderother"));
                print $form->selectarray('gender', $arraygender, GETPOST('gender', 'alphanohtml'), 1, 0, 0, '', 0, 0, 0, '', '', 1);
                print '</td></tr>';

                // EMail
                print '<tr><td>' . (getDolGlobalString('ADHERENT_MAIL_REQUIRED') ? '<span class="fieldrequired">' : '') . $langs->trans("EMail") . (getDolGlobalString('ADHERENT_MAIL_REQUIRED') ? '</span>' : '') . '</td>';
                print '<td>' . img_picto('', 'object_email') . ' <input type="text" name="member_email" class="minwidth300" maxlength="255" value="' . (GETPOSTISSET('member_email') ? GETPOST('member_email', 'alpha') : $soc->email) . '"></td></tr>';

                // Website
                print '<tr><td>' . $form->editfieldkey('Web', 'member_url', GETPOST('member_url', 'alpha'), $object, 0) . '</td>';
                print '<td>' . img_picto('', 'globe') . ' <input type="text" class="maxwidth500 widthcentpercentminusx" name="member_url" id="member_url" value="' . (GETPOSTISSET('member_url') ? GETPOST('member_url', 'alpha') : $object->url) . '"></td></tr>';

                // Address
                print '<tr><td class="tdtop">' . $langs->trans("Address") . '</td><td>';
                print '<textarea name="address" wrap="soft" class="quatrevingtpercent" rows="2">' . (GETPOSTISSET('address') ? GETPOST('address', 'alphanohtml') : $soc->address) . '</textarea>';
                print '</td></tr>';

                // Zip / Town
                print '<tr><td>' . $langs->trans("Zip") . ' / ' . $langs->trans("Town") . '</td><td>';
                print $formcompany->select_ziptown((GETPOSTISSET('zipcode') ? GETPOST('zipcode', 'alphanohtml') : $soc->zip), 'zipcode', array('town', 'selectcountry_id', 'state_id'), 6);
                print ' ';
                print $formcompany->select_ziptown((GETPOSTISSET('town') ? GETPOST('town', 'alphanohtml') : $soc->town), 'town', array('zipcode', 'selectcountry_id', 'state_id'));
                print '</td></tr>';

                // Country
                if (empty($soc->country_id)) {
                    $soc->country_id = $mysoc->country_id;
                    $soc->country_code = $mysoc->country_code;
                    $soc->state_id = $mysoc->state_id;
                }
                print '<tr><td>' . $langs->trans('Country') . '</td><td>';
                print img_picto('', 'country', 'class="pictofixedwidth"');
                print $form->select_country(GETPOSTISSET('country_id') ? GETPOST('country_id', 'alpha') : $soc->country_id, 'country_id');
                if ($user->admin) {
                    print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
                }
                print '</td></tr>';

                // State
                if (!getDolGlobalString('MEMBER_DISABLE_STATE')) {
                    print '<tr><td>' . $langs->trans('State') . '</td><td>';
                    if ($soc->country_id) {
                        print img_picto('', 'state', 'class="pictofixedwidth"');
                        print $formcompany->select_state(GETPOSTISSET('state_id') ? GETPOSTINT('state_id') : $soc->state_id, $soc->country_code);
                    } else {
                        print $countrynotdefined;
                    }
                    print '</td></tr>';
                }

                // Pro phone
                print '<tr><td>' . $langs->trans("PhonePro") . '</td>';
                print '<td>' . img_picto('', 'object_phoning', 'class="pictofixedwidth"') . '<input type="text" name="phone" size="20" value="' . (GETPOSTISSET('phone') ? GETPOST('phone', 'alpha') : $soc->phone) . '"></td></tr>';

                // Personal phone
                print '<tr><td>' . $langs->trans("PhonePerso") . '</td>';
                print '<td>' . img_picto('', 'object_phoning', 'class="pictofixedwidth"') . '<input type="text" name="phone_perso" size="20" value="' . (GETPOSTISSET('phone_perso') ? GETPOST('phone_perso', 'alpha') : $object->phone_perso) . '"></td></tr>';

                // Mobile phone
                print '<tr><td>' . $langs->trans("PhoneMobile") . '</td>';
                print '<td>' . img_picto('', 'object_phoning_mobile', 'class="pictofixedwidth"') . '<input type="text" name="phone_mobile" size="20" value="' . (GETPOSTISSET('phone_mobile') ? GETPOST('phone_mobile', 'alpha') : $object->phone_mobile) . '"></td></tr>';

                if (isModEnabled('socialnetworks')) {
                    foreach ($socialnetworks as $key => $value) {
                        if (!$value['active']) {
                            break;
                        }
                        $val = (GETPOSTISSET('member_' . $key) ? GETPOST('member_' . $key, 'alpha') : (empty($object->socialnetworks[$key]) ? '' : $object->socialnetworks[$key]));
                        print '<tr><td>' . $langs->trans($value['label']) . '</td><td><input type="text" name="member_' . $key . '" size="40" value="' . $val . '"></td></tr>';
                    }
                }

                // Birth Date
                print "<tr><td>" . $langs->trans("DateOfBirth") . "</td><td>\n";
                print img_picto('', 'object_calendar', 'class="pictofixedwidth"') . $form->selectDate(($object->birth ? $object->birth : -1), 'birth', 0, 0, 1, 'formsoc');
                print "</td></tr>\n";

                // Public profil
                print "<tr><td>";
                $htmltext = $langs->trans("Public", getDolGlobalString('MAIN_INFO_SOCIETE_NOM'), $linkofpubliclist);
                print $form->textwithpicto($langs->trans("MembershipPublic"), $htmltext, 1, 'help', '', 0, 3, 'membershippublic');
                print "</td><td>\n";
                print $form->selectyesno("public", $object->public, 1);
                print "</td></tr>\n";

                // Categories
                if (isModEnabled('category') && $user->hasRight('categorie', 'lire')) {
                    print '<tr><td>' . $form->editfieldkey("Categories", 'memcats', '', $object, 0) . '</td><td>';
                    $cate_arbo = $form->select_all_categories(Categorie::TYPE_MEMBER, null, 'parent', null, null, 1);
                    print img_picto('', 'category') . $form->multiselectarray('memcats', $cate_arbo, GETPOST('memcats', 'array'), null, null, 'quatrevingtpercent widthcentpercentminusx', 0, 0);
                    print "</td></tr>";
                }

                // Other attributes
                include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';

                print '<tbody>';
                print "</table>\n";

                print dol_get_fiche_end();

                print $form->buttonsSaveCancel("AddMember");

                print "</form>\n";
            }

            // Edit mode
            if ($action == 'edit') {
                $res = $object->fetch($id);
                if ($res < 0) {
                    dol_print_error($db, $object->error);
                    exit;
                }
                $res = $object->fetch_optionals();
                if ($res < 0) {
                    dol_print_error($db);
                    exit;
                }

                $adht = new AdherentType($db);
                $adht->fetch($object->typeid);

                // We set country_id, and country_code, country of the chosen country
                $country = GETPOSTINT('country');
                if (!empty($country) || $object->country_id) {
                    $sql = "SELECT rowid, code, label from " . MAIN_DB_PREFIX . "c_country";
                    $sql .= " WHERE rowid = " . (int) (!empty($country) ? $country : $object->country_id);
                    $resql = $db->query($sql);
                    if ($resql) {
                        $obj = $db->fetch_object($resql);
                    } else {
                        dol_print_error($db);
                    }
                    $object->country_id = $obj->rowid;
                    $object->country_code = $obj->code;
                    $object->country = $langs->trans("Country" . $obj->code) ? $langs->trans("Country" . $obj->code) : $obj->label;
                }

                $head = member_prepare_head($object);


                if ($conf->use_javascript_ajax) {
                    print "\n" . '<script type="text/javascript">';
                    print 'jQuery(document).ready(function () {
				jQuery("#selectcountry_id").change(function() {
					document.formsoc.action.value="edit";
					document.formsoc.submit();
				});
				function initfieldrequired() {
					jQuery("#tdcompany").removeClass("fieldrequired");
					jQuery("#tdlastname").removeClass("fieldrequired");
					jQuery("#tdfirstname").removeClass("fieldrequired");
					if (jQuery("#morphy").val() == \'mor\') {
						jQuery("#tdcompany").addClass("fieldrequired");
					}
					if (jQuery("#morphy").val() == \'phy\') {
						jQuery("#tdlastname").addClass("fieldrequired");
						jQuery("#tdfirstname").addClass("fieldrequired");
					}
				}
				jQuery("#morphy").change(function() {
					initfieldrequired();
				});
				initfieldrequired();
			})';
                    print '</script>' . "\n";
                }

                print '<form name="formsoc" action="' . $_SERVER['PHP_SELF'] . '" method="post" enctype="multipart/form-data">';
                print '<input type="hidden" name="token" value="' . newToken() . '" />';
                print '<input type="hidden" name="action" value="update" />';
                print '<input type="hidden" name="rowid" value="' . $id . '" />';
                print '<input type="hidden" name="statut" value="' . $object->statut . '" />';
                if ($backtopage) {
                    print '<input type="hidden" name="backtopage" value="' . ($backtopage != '1' ? $backtopage : $_SERVER["HTTP_REFERER"]) . '">';
                }

                print dol_get_fiche_head($head, 'general', $langs->trans("Member"), 0, 'user');

                print '<table class="border centpercent">';

                // Ref
                print '<tr><td class="titlefieldcreate">' . $langs->trans("Ref") . '</td><td class="valeur">' . $object->ref . '</td></tr>';

                // Login
                if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
                    print '<tr><td><span class="fieldrequired">' . $langs->trans("Login") . ' / ' . $langs->trans("Id") . '</span></td><td><input type="text" name="login" class="minwidth300" maxlength="50" value="' . (GETPOSTISSET("login") ? GETPOST("login", 'alphanohtml', 2) : $object->login) . '"></td></tr>';
                }

                // Password
                if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
                    print '<tr><td class="fieldrequired">' . $langs->trans("Password") . '</td><td><input type="password" name="pass" class="minwidth300" maxlength="50" value="' . dol_escape_htmltag(GETPOSTISSET("pass") ? GETPOST("pass", 'none', 2) : '') . '"></td></tr>';
                }

                // Type
                print '<tr><td class="fieldrequired">' . $langs->trans("Type") . '</td><td>';
                if ($user->hasRight('adherent', 'creer')) {
                    print $form->selectarray("typeid", $adht->liste_array(), (GETPOSTISSET("typeid") ? GETPOSTINT("typeid") : $object->typeid), 0, 0, 0, '', 0, 0, 0, '', '', 1);
                } else {
                    print $adht->getNomUrl(1);
                    print '<input type="hidden" name="typeid" value="' . $object->typeid . '">';
                }
                print "</td></tr>";

                // Morphy
                $morphys["phy"] = $langs->trans("Physical");
                $morphys["mor"] = $langs->trans("Moral");
                print '<tr><td><span class="fieldrequired">' . $langs->trans("MemberNature") . '</span></td><td>';
                print $form->selectarray("morphy", $morphys, (GETPOSTISSET("morphy") ? GETPOST("morphy", 'alpha') : $object->morphy), 0, 0, 0, '', 0, 0, 0, '', '', 1);
                print "</td></tr>";

                // Company
                print '<tr><td id="tdcompany">' . $langs->trans("Company") . '</td><td><input type="text" name="societe" class="minwidth300" maxlength="128" value="' . (GETPOSTISSET("societe") ? GETPOST("societe", 'alphanohtml', 2) : $object->company) . '"></td></tr>';

                // Civility
                print '<tr><td>' . $langs->trans("UserTitle") . '</td><td>';
                print $formcompany->select_civility(GETPOSTISSET("civility_id") ? GETPOST("civility_id", 'alpha') : $object->civility_id, 'civility_id', 'maxwidth150', 1);
                print '</td>';
                print '</tr>';

                // Lastname
                print '<tr><td id="tdlastname">' . $langs->trans("Lastname") . '</td><td><input type="text" name="lastname" class="minwidth300" maxlength="50" value="' . (GETPOSTISSET("lastname") ? GETPOST("lastname", 'alphanohtml', 2) : $object->lastname) . '"></td>';
                print '</tr>';

                // Firstname
                print '<tr><td id="tdfirstname">' . $langs->trans("Firstname") . '</td><td><input type="text" name="firstname" class="minwidth300" maxlength="50" value="' . (GETPOSTISSET("firstname") ? GETPOST("firstname", 'alphanohtml', 3) : $object->firstname) . '"></td>';
                print '</tr>';

                // Gender
                print '<tr><td>' . $langs->trans("Gender") . '</td>';
                print '<td>';
                $arraygender = array('man' => $langs->trans("Genderman"), 'woman' => $langs->trans("Genderwoman"), 'other' => $langs->trans("Genderother"));
                print $form->selectarray('gender', $arraygender, GETPOSTISSET('gender') ? GETPOST('gender', 'alphanohtml') : $object->gender, 1, 0, 0, '', 0, 0, 0, '', '', 1);
                print '</td></tr>';

                // Photo
                print '<tr><td>' . $langs->trans("Photo") . '</td>';
                print '<td class="hideonsmartphone" valign="middle">';
                print $form->showphoto('memberphoto', $object) . "\n";
                if ($caneditfieldmember) {
                    if ($object->photo) {
                        print "<br>\n";
                    }
                    print '<table class="nobordernopadding">';
                    if ($object->photo) {
                        print '<tr><td><input type="checkbox" class="flat photodelete" name="deletephoto" id="photodelete"> ' . $langs->trans("Delete") . '<br><br></td></tr>';
                    }
                    print '<tr><td>' . $langs->trans("PhotoFile") . '</td></tr>';
                    print '<tr><td>';
                    $maxfilesizearray = getMaxFileSizeArray();
                    $maxmin = $maxfilesizearray['maxmin'];
                    if ($maxmin > 0) {
                        print '<input type="hidden" name="MAX_FILE_SIZE" value="' . ($maxmin * 1024) . '">';    // MAX_FILE_SIZE must precede the field type=file
                    }
                    print '<input type="file" class="flat" name="photo" id="photoinput">';
                    print '</td></tr>';
                    print '</table>';
                }
                print '</td></tr>';

                // EMail
                print '<tr><td>' . (getDolGlobalString("ADHERENT_MAIL_REQUIRED") ? '<span class="fieldrequired">' : '') . $langs->trans("EMail") . (getDolGlobalString("ADHERENT_MAIL_REQUIRED") ? '</span>' : '') . '</td>';
                print '<td>' . img_picto('', 'object_email', 'class="pictofixedwidth"') . '<input type="text" name="member_email" class="minwidth300" maxlength="255" value="' . (GETPOSTISSET("member_email") ? GETPOST("member_email", '', 2) : $object->email) . '"></td></tr>';

                // Website
                print '<tr><td>' . $form->editfieldkey('Web', 'member_url', GETPOST('member_url', 'alpha'), $object, 0) . '</td>';
                print '<td>' . img_picto('', 'globe', 'class="pictofixedwidth"') . '<input type="text" name="member_url" id="member_url" class="maxwidth200onsmartphone maxwidth500 widthcentpercentminusx " value="' . (GETPOSTISSET('member_url') ? GETPOST('member_url', 'alpha') : $object->url) . '"></td></tr>';

                // Address
                print '<tr><td>' . $langs->trans("Address") . '</td><td>';
                print '<textarea name="address" wrap="soft" class="quatrevingtpercent" rows="' . ROWS_2 . '">' . (GETPOSTISSET("address") ? GETPOST("address", 'alphanohtml', 2) : $object->address) . '</textarea>';
                print '</td></tr>';

                // Zip / Town
                print '<tr><td>' . $langs->trans("Zip") . ' / ' . $langs->trans("Town") . '</td><td>';
                print $formcompany->select_ziptown((GETPOSTISSET("zipcode") ? GETPOST("zipcode", 'alphanohtml', 2) : $object->zip), 'zipcode', array('town', 'selectcountry_id', 'state_id'), 6);
                print ' ';
                print $formcompany->select_ziptown((GETPOSTISSET("town") ? GETPOST("town", 'alphanohtml', 2) : $object->town), 'town', array('zipcode', 'selectcountry_id', 'state_id'));
                print '</td></tr>';

                // Country
                //$object->country_id=$object->country_id?$object->country_id:$mysoc->country_id;    // In edit mode we don't force to company country if not defined
                print '<tr><td>' . $langs->trans('Country') . '</td><td>';
                print img_picto('', 'country', 'class="pictofixedwidth"');
                print $form->select_country(GETPOSTISSET("country_id") ? GETPOST("country_id", "alpha") : $object->country_id, 'country_id');
                if ($user->admin) {
                    print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
                }
                print '</td></tr>';

                // State
                if (!getDolGlobalString('MEMBER_DISABLE_STATE')) {
                    print '<tr><td>' . $langs->trans('State') . '</td><td>';
                    print img_picto('', 'state', 'class="pictofixedwidth"');
                    print $formcompany->select_state($object->state_id, GETPOSTISSET("country_id") ? GETPOST("country_id", "alpha") : $object->country_id);
                    print '</td></tr>';
                }

                // Pro phone
                print '<tr><td>' . $langs->trans("PhonePro") . '</td>';
                print '<td>' . img_picto('', 'object_phoning', 'class="pictofixedwidth"') . '<input type="text" name="phone" value="' . (GETPOSTISSET("phone") ? GETPOST("phone") : $object->phone) . '"></td></tr>';

                // Personal phone
                print '<tr><td>' . $langs->trans("PhonePerso") . '</td>';
                print '<td>' . img_picto('', 'object_phoning', 'class="pictofixedwidth"') . '<input type="text" name="phone_perso" value="' . (GETPOSTISSET("phone_perso") ? GETPOST("phone_perso") : $object->phone_perso) . '"></td></tr>';

                // Mobile phone
                print '<tr><td>' . $langs->trans("PhoneMobile") . '</td>';
                print '<td>' . img_picto('', 'object_phoning_mobile', 'class="pictofixedwidth"') . '<input type="text" name="phone_mobile" value="' . (GETPOSTISSET("phone_mobile") ? GETPOST("phone_mobile") : $object->phone_mobile) . '"></td></tr>';

                if (isModEnabled('socialnetworks')) {
                    foreach ($socialnetworks as $key => $value) {
                        if (!$value['active']) {
                            break;
                        }
                        print '<tr><td>' . $langs->trans($value['label']) . '</td><td><input type="text" name="' . $key . '" class="minwidth100" value="' . (GETPOSTISSET($key) ? GETPOST($key, 'alphanohtml') : (isset($object->socialnetworks[$key]) ? $object->socialnetworks[$key] : null)) . '"></td></tr>';
                    }
                }

                // Birth Date
                print "<tr><td>" . $langs->trans("DateOfBirth") . "</td><td>\n";
                print img_picto('', 'object_calendar', 'class="pictofixedwidth"') . $form->selectDate(($object->birth ? $object->birth : -1), 'birth', 0, 0, 1, 'formsoc');
                print "</td></tr>\n";

                // Default language
                if (getDolGlobalInt('MAIN_MULTILANGS')) {
                    print '<tr><td>' . $form->editfieldkey('DefaultLang', 'default_lang', '', $object, 0) . '</td><td colspan="3">' . "\n";
                    print img_picto('', 'language', 'class="pictofixedwidth"') . $formadmin->select_language($object->default_lang, 'default_lang', 0, 0, 1);
                    print '</td>';
                    print '</tr>';
                }

                // Public profil
                print "<tr><td>";
                $htmltext = $langs->trans("Public", getDolGlobalString('MAIN_INFO_SOCIETE_NOM'), $linkofpubliclist);
                print $form->textwithpicto($langs->trans("MembershipPublic"), $htmltext, 1, 'help', '', 0, 3, 'membershippublic');
                print "</td><td>\n";
                print $form->selectyesno("public", (GETPOSTISSET("public") ? GETPOST("public", 'alphanohtml', 2) : $object->public), 1);
                print "</td></tr>\n";

                // Categories
                if (isModEnabled('category') && $user->hasRight('categorie', 'lire')) {
                    print '<tr><td>' . $form->editfieldkey("Categories", 'memcats', '', $object, 0) . '</td>';
                    print '<td>';
                    $cate_arbo = $form->select_all_categories(Categorie::TYPE_MEMBER, null, null, null, null, 1);
                    $c = new Categorie($db);
                    $cats = $c->containing($object->id, Categorie::TYPE_MEMBER);
                    $arrayselected = array();
                    if (is_array($cats)) {
                        foreach ($cats as $cat) {
                            $arrayselected[] = $cat->id;
                        }
                    }
                    print $form->multiselectarray('memcats', $cate_arbo, $arrayselected, '', 0, '', 0, '100%');
                    print "</td></tr>";
                }

                // Third party Dolibarr
                if (isModEnabled('societe')) {
                    print '<tr><td>' . $langs->trans("LinkedToDolibarrThirdParty") . '</td><td colspan="2" class="valeur">';
                    if ($object->socid) {
                        $company = new Societe($db);
                        $result = $company->fetch($object->socid);
                        print $company->getNomUrl(1);
                    } else {
                        print $langs->trans("NoThirdPartyAssociatedToMember");
                    }
                    print '</td></tr>';
                }

                // Login Dolibarr
                print '<tr><td>' . $langs->trans("LinkedToDolibarrUser") . '</td><td colspan="2" class="valeur">';
                if ($object->user_id) {
                    $form->form_users($_SERVER['PHP_SELF'] . '?rowid=' . $object->id, $object->user_id, 'none');
                } else {
                    print $langs->trans("NoDolibarrAccess");
                }
                print '</td></tr>';

                // Other attributes. Fields from hook formObjectOptions and Extrafields.
                include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';

                print '</table>';
                print dol_get_fiche_end();

                print $form->buttonsSaveCancel("Save", 'Cancel');

                print '</form>';
            }

            // View
            if ($id > 0 && $action != 'edit') {
                $res = $object->fetch($id);
                if ($res < 0) {
                    dol_print_error($db, $object->error);
                    exit;
                }
                $res = $object->fetch_optionals();
                if ($res < 0) {
                    dol_print_error($db);
                    exit;
                }

                $adht = new AdherentType($db);
                $res = $adht->fetch($object->typeid);
                if ($res < 0) {
                    dol_print_error($db);
                    exit;
                }

                /*
                 * Show tabs
                 */
                $head = member_prepare_head($object);

                print dol_get_fiche_head($head, 'general', $langs->trans("Member"), -1, 'user');

                // Confirm create user
                if ($action == 'create_user') {
                    $login = (GETPOSTISSET('login') ? GETPOST('login', 'alphanohtml') : $object->login);
                    if (empty($login)) {
                        // Full firstname and name separated with a dot : firstname.name
                        include_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
                        $login = dol_buildlogin($object->lastname, $object->firstname);
                    }
                    if (empty($login)) {
                        $login = strtolower(substr($object->firstname, 0, 4)) . strtolower(substr($object->lastname, 0, 4));
                    }

                    // Create a form array
                    $formquestion = array(
                        array('label' => $langs->trans("LoginToCreate"), 'type' => 'text', 'name' => 'login', 'value' => $login)
                    );
                    if (isModEnabled('societe') && $object->socid > 0) {
                        $object->fetch_thirdparty();
                        $formquestion[] = array('label' => $langs->trans("UserWillBe"), 'type' => 'radio', 'name' => 'internalorexternal', 'default' => 'external', 'values' => array('external' => $langs->trans("External") . ' - ' . $langs->trans("LinkedToDolibarrThirdParty") . ' ' . $object->thirdparty->getNomUrl(1, '', 0, 1), 'internal' => $langs->trans("Internal")));
                    }
                    $text = '';
                    if (isModEnabled('societe') && $object->socid <= 0) {
                        $text .= $langs->trans("UserWillBeInternalUser") . '<br>';
                    }
                    $text .= $langs->trans("ConfirmCreateLogin");
                    print $form->formconfirm($_SERVER['PHP_SELF'] . "?rowid=" . $object->id, $langs->trans("CreateDolibarrLogin"), $text, "confirm_create_user", $formquestion, 'yes');
                }

                // Confirm create third party
                if ($action == 'create_thirdparty') {
                    $companyalias = '';
                    $fullname = $object->getFullName($langs);

                    if ($object->morphy == 'mor') {
                        $companyname = $object->company;
                        if (!empty($fullname)) {
                            $companyalias = $fullname;
                        }
                    } else {
                        $companyname = $fullname;
                        if (!empty($object->company)) {
                            $companyalias = $object->company;
                        }
                    }

                    // Create a form array
                    $formquestion = array(
                        array('label' => $langs->trans("NameToCreate"), 'type' => 'text', 'name' => 'companyname', 'value' => $companyname, 'morecss' => 'minwidth300', 'moreattr' => 'maxlength="128"'),
                        array('label' => $langs->trans("AliasNames"), 'type' => 'text', 'name' => 'companyalias', 'value' => $companyalias, 'morecss' => 'minwidth300', 'moreattr' => 'maxlength="128"')
                    );

                    print $form->formconfirm($_SERVER['PHP_SELF'] . "?rowid=" . $object->id, $langs->trans("CreateDolibarrThirdParty"), $langs->trans("ConfirmCreateThirdParty"), "confirm_create_thirdparty", $formquestion, 'yes');
                }

                // Confirm validate member
                if ($action == 'valid') {
                    $langs->load("mails");

                    $adht = new AdherentType($db);
                    $adht->fetch($object->typeid);

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
                    $labeltouse = getDolGlobalString("ADHERENT_EMAIL_TEMPLATE_MEMBER_VALIDATION");

                    if (!empty($labeltouse)) {
                        $arraydefaultmessage = $formmail->getEMailTemplate($db, 'member', $user, $outputlangs, 0, 1, $labeltouse);
                    }

                    if (!empty($labeltouse) && is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0) {
                        $subject = $arraydefaultmessage->topic;
                        $msg = $arraydefaultmessage->content;
                    }

                    $substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
                    complete_substitutions_array($substitutionarray, $outputlangs, $object);
                    $subjecttosend = make_substitutions($subject, $substitutionarray, $outputlangs);
                    $texttosend = make_substitutions(dol_concatdesc($msg, $adht->getMailOnValid()), $substitutionarray, $outputlangs);

                    $tmp = $langs->trans("SendingAnEMailToMember");
                    $tmp .= '<br>' . $langs->trans("MailFrom") . ': <b>' . getDolGlobalString('ADHERENT_MAIL_FROM') . '</b>, ';
                    $tmp .= '<br>' . $langs->trans("MailRecipient") . ': <b>' . $object->email . '</b>';
                    $helpcontent = '';
                    $helpcontent .= '<b>' . $langs->trans("MailFrom") . '</b>: ' . getDolGlobalString('ADHERENT_MAIL_FROM') . '<br>' . "\n";
                    $helpcontent .= '<b>' . $langs->trans("MailRecipient") . '</b>: ' . $object->email . '<br>' . "\n";
                    $helpcontent .= '<b>' . $langs->trans("Subject") . '</b>:<br>' . "\n";
                    $helpcontent .= $subjecttosend . "\n";
                    $helpcontent .= "<br>";
                    $helpcontent .= '<b>' . $langs->trans("Content") . '</b>:<br>';
                    $helpcontent .= dol_htmlentitiesbr($texttosend) . "\n";
                    // @phan-suppress-next-line PhanPluginSuspiciousParamOrder
                    $label = $form->textwithpicto($tmp, $helpcontent, 1, 'help');

                    // Create form popup
                    $formquestion = array();
                    if ($object->email) {
                        $formquestion[] = array('type' => 'checkbox', 'name' => 'send_mail', 'label' => $label, 'value' => (getDolGlobalString('ADHERENT_DEFAULT_SENDINFOBYMAIL') ? true : false));
                    }
                    if (isModEnabled('mailman') && getDolGlobalString('ADHERENT_USE_MAILMAN')) {
                        $formquestion[] = array('type' => 'other', 'label' => $langs->transnoentitiesnoconv("SynchroMailManEnabled"), 'value' => '');
                    }
                    if (isModEnabled('mailman') && getDolGlobalString('ADHERENT_USE_SPIP')) {
                        $formquestion[] = array('type' => 'other', 'label' => $langs->transnoentitiesnoconv("SynchroSpipEnabled"), 'value' => '');
                    }
                    print $form->formconfirm("card.php?rowid=" . $id, $langs->trans("ValidateMember"), $langs->trans("ConfirmValidateMember"), "confirm_valid", $formquestion, 'yes', 1, 220);
                }

                // Confirm resiliate
                if ($action == 'resiliate') {
                    $langs->load("mails");

                    $adht = new AdherentType($db);
                    $adht->fetch($object->typeid);

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
                    $labeltouse = getDolGlobalString('ADHERENT_EMAIL_TEMPLATE_CANCELATION');

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
                    $texttosend = make_substitutions(dol_concatdesc($msg, $adht->getMailOnResiliate()), $substitutionarray, $outputlangs);

                    $tmp = $langs->trans("SendingAnEMailToMember");
                    $tmp .= '<br>(' . $langs->trans("MailFrom") . ': <b>' . getDolGlobalString('ADHERENT_MAIL_FROM') . '</b>, ';
                    $tmp .= $langs->trans("MailRecipient") . ': <b>' . $object->email . '</b>)';
                    $helpcontent = '';
                    $helpcontent .= '<b>' . $langs->trans("MailFrom") . '</b>: ' . getDolGlobalString('ADHERENT_MAIL_FROM') . '<br>' . "\n";
                    $helpcontent .= '<b>' . $langs->trans("MailRecipient") . '</b>: ' . $object->email . '<br>' . "\n";
                    $helpcontent .= '<b>' . $langs->trans("Subject") . '</b>:<br>' . "\n";
                    $helpcontent .= $subjecttosend . "\n";
                    $helpcontent .= "<br>";
                    $helpcontent .= '<b>' . $langs->trans("Content") . '</b>:<br>';
                    $helpcontent .= dol_htmlentitiesbr($texttosend) . "\n";
                    // @phan-suppress-next-line PhanPluginSuspiciousParamOrder
                    $label = $form->textwithpicto($tmp, $helpcontent, 1, 'help');

                    // Create an array
                    $formquestion = array();
                    if ($object->email) {
                        $formquestion[] = array('type' => 'checkbox', 'name' => 'send_mail', 'label' => $label, 'value' => (getDolGlobalString('ADHERENT_DEFAULT_SENDINFOBYMAIL') ? 'true' : 'false'));
                    }
                    if ($backtopage) {
                        $formquestion[] = array('type' => 'hidden', 'name' => 'backtopage', 'value' => ($backtopage != '1' ? $backtopage : $_SERVER["HTTP_REFERER"]));
                    }
                    print $form->formconfirm("card.php?rowid=" . $id, $langs->trans("ResiliateMember"), $langs->trans("ConfirmResiliateMember"), "confirm_resiliate", $formquestion, 'no', 1, 240);
                }

                // Confirm exclude
                if ($action == 'exclude') {
                    $langs->load("mails");

                    $adht = new AdherentType($db);
                    $adht->fetch($object->typeid);

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
                    $labeltouse = getDolGlobalString('ADHERENT_EMAIL_TEMPLATE_EXCLUSION');

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
                    $texttosend = make_substitutions(dol_concatdesc($msg, $adht->getMailOnExclude()), $substitutionarray, $outputlangs);

                    $tmp = $langs->trans("SendingAnEMailToMember");
                    $tmp .= '<br>(' . $langs->trans("MailFrom") . ': <b>' . getDolGlobalString('ADHERENT_MAIL_FROM') . '</b>, ';
                    $tmp .= $langs->trans("MailRecipient") . ': <b>' . $object->email . '</b>)';
                    $helpcontent = '';
                    $helpcontent .= '<b>' . $langs->trans("MailFrom") . '</b>: ' . getDolGlobalString('ADHERENT_MAIL_FROM') . '<br>' . "\n";
                    $helpcontent .= '<b>' . $langs->trans("MailRecipient") . '</b>: ' . $object->email . '<br>' . "\n";
                    $helpcontent .= '<b>' . $langs->trans("Subject") . '</b>:<br>' . "\n";
                    $helpcontent .= $subjecttosend . "\n";
                    $helpcontent .= "<br>";
                    $helpcontent .= '<b>' . $langs->trans("Content") . '</b>:<br>';
                    $helpcontent .= dol_htmlentitiesbr($texttosend) . "\n";
                    // @phan-suppress-next-line PhanPluginSuspiciousParamOrder
                    $label = $form->textwithpicto($tmp, $helpcontent, 1, 'help');

                    // Create an array
                    $formquestion = array();
                    if ($object->email) {
                        $formquestion[] = array('type' => 'checkbox', 'name' => 'send_mail', 'label' => $label, 'value' => (getDolGlobalString('ADHERENT_DEFAULT_SENDINFOBYMAIL') ? 'true' : 'false'));
                    }
                    if ($backtopage) {
                        $formquestion[] = array('type' => 'hidden', 'name' => 'backtopage', 'value' => ($backtopage != '1' ? $backtopage : $_SERVER["HTTP_REFERER"]));
                    }
                    print $form->formconfirm("card.php?rowid=" . $id, $langs->trans("ExcludeMember"), $langs->trans("ConfirmExcludeMember"), "confirm_exclude", $formquestion, 'no', 1, 240);
                }

                // Confirm remove member
                if ($action == 'delete') {
                    $formquestion = array();
                    if ($backtopage) {
                        $formquestion[] = array('type' => 'hidden', 'name' => 'backtopage', 'value' => ($backtopage != '1' ? $backtopage : $_SERVER["HTTP_REFERER"]));
                    }
                    print $form->formconfirm("card.php?rowid=" . $id, $langs->trans("DeleteMember"), $langs->trans("ConfirmDeleteMember"), "confirm_delete", $formquestion, 'no', 1);
                }

                // Confirm add in spip
                if ($action == 'add_spip') {
                    print $form->formconfirm("card.php?rowid=" . $id, $langs->trans('AddIntoSpip'), $langs->trans('AddIntoSpipConfirmation'), 'confirm_add_spip');
                }
                // Confirm removed from spip
                if ($action == 'del_spip') {
                    print $form->formconfirm("card.php?rowid=$id", $langs->trans('DeleteIntoSpip'), $langs->trans('DeleteIntoSpipConfirmation'), 'confirm_del_spip');
                }

                $rowspan = 17;
                if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
                    $rowspan++;
                }
                if (isModEnabled('societe')) {
                    $rowspan++;
                }

                $linkback = '<a href="' . DOL_URL_ROOT . '/adherents/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

                $morehtmlref = '<a href="' . DOL_URL_ROOT . '/adherents/vcard.php?id=' . $object->id . '" class="refid">';
                $morehtmlref .= img_picto($langs->trans("Download") . ' ' . $langs->trans("VCard"), 'vcard.png', 'class="valignmiddle marginleftonly paddingrightonly"');
                $morehtmlref .= '</a>';


                dol_banner_tab($object, 'rowid', $linkback, 1, 'rowid', 'ref', $morehtmlref);

                print '<div class="fichecenter">';
                print '<div class="fichehalfleft">';

                print '<div class="underbanner clearboth"></div>';
                print '<table class="border tableforfield centpercent">';

                // Login
                if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
                    print '<tr><td class="titlefield">' . $langs->trans("Login") . ' / ' . $langs->trans("Id") . '</td><td class="valeur">' . dol_escape_htmltag($object->login) . '</td></tr>';
                }

                // Type
                print '<tr><td class="titlefield">' . $langs->trans("Type") . '</td>';
                print '<td class="valeur">' . $adht->getNomUrl(1) . "</td></tr>\n";

                // Morphy
                print '<tr><td>' . $langs->trans("MemberNature") . '</td>';
                print '<td class="valeur" >' . $object->getmorphylib('', 1) . '</td>';
                print '</tr>';

                // Company
                print '<tr><td>' . $langs->trans("Company") . '</td><td class="valeur">' . dol_escape_htmltag($object->company) . '</td></tr>';

                // Civility
                print '<tr><td>' . $langs->trans("UserTitle") . '</td><td class="valeur">' . $object->getCivilityLabel() . '</td>';
                print '</tr>';

                // Password
                if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
                    print '<tr><td>' . $langs->trans("Password") . '</td><td>';
                    if ($object->pass) {
                        print preg_replace('/./i', '*', $object->pass);
                    } else {
                        if ($user->admin) {
                            print '<!-- ' . $langs->trans("Crypted") . ': ' . $object->pass_indatabase_crypted . ' -->';
                        }
                        print '<span class="opacitymedium">' . $langs->trans("Hidden") . '</span>';
                    }
                    if (!empty($object->pass_indatabase) && empty($object->user_id)) {  // Show warning only for old password still in clear (does not happen anymore)
                        $langs->load("errors");
                        $htmltext = $langs->trans("WarningPasswordSetWithNoAccount");
                        print ' ' . $form->textwithpicto('', $htmltext, 1, 'warning');
                    }
                    print '</td></tr>';
                }

                // Date end subscription
                print '<tr><td>' . $langs->trans("SubscriptionEndDate") . '</td><td class="valeur">';
                if ($object->datefin) {
                    print dol_print_date($object->datefin, 'day');
                    if ($object->hasDelay()) {
                        print " " . img_warning($langs->trans("Late"));
                    }
                } else {
                    if ($object->need_subscription == 0) {
                        print $langs->trans("SubscriptionNotNeeded");
                    } elseif (!$adht->subscription) {
                        print $langs->trans("SubscriptionNotRecorded");
                        if (Adherent::STATUS_VALIDATED == $object->statut) {
                            print " " . img_warning($langs->trans("Late")); // displays delay Pictogram only if not a draft, not excluded and not resiliated
                        }
                    } else {
                        print $langs->trans("SubscriptionNotReceived");
                        if (Adherent::STATUS_VALIDATED == $object->statut) {
                            print " " . img_warning($langs->trans("Late")); // displays delay Pictogram only if not a draft, not excluded and not resiliated
                        }
                    }
                }
                print '</td></tr>';

                print '</table>';

                print '</div>';

                print '<div class="fichehalfright">';
                print '<div class="underbanner clearboth"></div>';

                print '<table class="border tableforfield centpercent">';

                // Tags / Categories
                if (isModEnabled('category') && $user->hasRight('categorie', 'lire')) {
                    print '<tr><td>' . $langs->trans("Categories") . '</td>';
                    print '<td colspan="2">';
                    print $form->showCategories($object->id, Categorie::TYPE_MEMBER, 1);
                    print '</td></tr>';
                }

                // Birth Date
                print '<tr><td class="titlefield">' . $langs->trans("DateOfBirth") . '</td><td class="valeur">' . dol_print_date($object->birth, 'day') . '</td></tr>';

                // Default language
                if (getDolGlobalInt('MAIN_MULTILANGS')) {
                    require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
                    print '<tr><td>' . $langs->trans("DefaultLang") . '</td><td>';
                    //$s=picto_from_langcode($object->default_lang);
                    //print ($s?$s.' ':'');
                    $langs->load("languages");
                    $labellang = ($object->default_lang ? $langs->trans('Language_' . $object->default_lang) : '');
                    print picto_from_langcode($object->default_lang, 'class="paddingrightonly saturatemedium opacitylow"');
                    print $labellang;
                    print '</td></tr>';
                }

                // Public
                print '<tr><td>';
                $htmltext = $langs->trans("Public", getDolGlobalString('MAIN_INFO_SOCIETE_NOM'), $linkofpubliclist);
                print $form->textwithpicto($langs->trans("MembershipPublic"), $htmltext, 1, 'help', '', 0, 3, 'membershippublic');
                print '</td><td class="valeur">' . yn($object->public) . '</td></tr>';

                // Other attributes
                include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

                // Third party Dolibarr
                if (isModEnabled('societe')) {
                    print '<tr><td>';
                    $editenable = $user->hasRight('adherent', 'creer');
                    print $form->editfieldkey('LinkedToDolibarrThirdParty', 'thirdparty', '', $object, $editenable);
                    print '</td><td colspan="2" class="valeur">';
                    if ($action == 'editthirdparty') {
                        $htmlname = 'socid';
                        print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '" name="form' . $htmlname . '">';
                        print '<input type="hidden" name="rowid" value="' . $object->id . '">';
                        print '<input type="hidden" name="action" value="set' . $htmlname . '">';
                        print '<input type="hidden" name="token" value="' . newToken() . '">';
                        print '<table class="nobordernopadding">';
                        print '<tr><td>';
                        print $form->select_company($object->socid, 'socid', '', 1);
                        print '</td>';
                        print '<td class="left"><input type="submit" class="button button-edit" value="' . $langs->trans("Modify") . '"></td>';
                        print '</tr></table></form>';
                    } else {
                        if ($object->socid) {
                            $company = new Societe($db);
                            $result = $company->fetch($object->socid);
                            print $company->getNomUrl(1);

                            // Show link to invoices
                            $tmparray = $company->getOutstandingBills('customer');
                            if (!empty($tmparray['refs'])) {
                                print ' - ' . img_picto($langs->trans("Invoices"), 'bill', 'class="paddingright"') . '<a href="' . DOL_URL_ROOT . '/compta/facture/list.php?socid=' . $object->socid . '">' . $langs->trans("Invoices") . ' (' . count($tmparray['refs']) . ')';
                                // TODO Add alert if warning on at least one invoice late
                                print '</a>';
                            }
                        } else {
                            print '<span class="opacitymedium">' . $langs->trans("NoThirdPartyAssociatedToMember") . '</span>';
                        }
                    }
                    print '</td></tr>';
                }

                // Login Dolibarr - Link to user
                print '<tr><td>';
                $editenable = $user->hasRight('adherent', 'creer') && $user->hasRight('user', 'user', 'creer');
                print $form->editfieldkey('LinkedToDolibarrUser', 'login', '', $object, $editenable);
                print '</td><td colspan="2" class="valeur">';
                if ($action == 'editlogin') {
                    $form->form_users($_SERVER['PHP_SELF'] . '?rowid=' . $object->id, $object->user_id, 'userid', '');
                } else {
                    if ($object->user_id) {
                        $linkeduser = new User($db);
                        $linkeduser->fetch($object->user_id);
                        print $linkeduser->getNomUrl(-1);
                    } else {
                        print '<span class="opacitymedium">' . $langs->trans("NoDolibarrAccess") . '</span>';
                    }
                }
                print '</td></tr>';

                print "</table>\n";

                print "</div></div>\n";
                print '<div class="clearboth"></div>';

                print dol_get_fiche_end();


                /*
                 * Action bar
                 */

                print '<div class="tabsAction">';
                $isinspip = 0;
                $parameters = array();
                $reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
                if (empty($reshook)) {
                    if ($action != 'editlogin' && $action != 'editthirdparty') {
                        // Send
                        if (empty($user->socid)) {
                            if (Adherent::STATUS_VALIDATED == $object->statut) {
                                print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . ((int) $object->id) . '&action=presend&mode=init#formmailbeforetitle">' . $langs->trans('SendMail') . '</a>' . "\n";
                            }
                        }

                        // Send card by email
                        // TODO Remove this to replace with a template
                        /*
                        if ($user->hasRight('adherent', 'creer')) {
                            if (Adherent::STATUS_VALIDATED == $object->statut) {
                                if ($object->email) print '<a class="butAction" href="card.php?rowid='.$object->id.'&action=sendinfo">'.$langs->trans("SendCardByMail")."</a>\n";
                                else print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NoEMail")).'">'.$langs->trans("SendCardByMail")."</a>\n";
                            } else {
                                print '<span class="butActionRefused classfortooltip" title="'.dol_escape_htmltag($langs->trans("ValidateBefore")).'">'.$langs->trans("SendCardByMail")."</span>";
                            }
                        } else {
                            print '<span class="butActionRefused classfortooltip" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("SendCardByMail")."</span>";
                        }*/

                        // Modify
                        if ($user->hasRight('adherent', 'creer')) {
                            print '<a class="butAction" href="card.php?rowid=' . ((int) $object->id) . '&action=edit&token=' . newToken() . '">' . $langs->trans("Modify") . '</a>' . "\n";
                        } else {
                            print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans("Modify") . '</span>' . "\n";
                        }

                        // Validate
                        if (Adherent::STATUS_DRAFT == $object->statut) {
                            if ($user->hasRight('adherent', 'creer')) {
                                print '<a class="butAction" href="card.php?rowid=' . ((int) $object->id) . '&action=valid&token=' . newToken() . '">' . $langs->trans("Validate") . '</a>' . "\n";
                            } else {
                                print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans("Validate") . '</span>' . "\n";
                            }
                        }

                        // Reactivate
                        if (Adherent::STATUS_RESILIATED == $object->statut || Adherent::STATUS_EXCLUDED == $object->statut) {
                            if ($user->hasRight('adherent', 'creer')) {
                                print '<a class="butAction" href="card.php?rowid=' . ((int) $object->id) . '&action=valid">' . $langs->trans("Reenable") . "</a>\n";
                            } else {
                                print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans("Reenable") . '</span>' . "\n";
                            }
                        }

                        // Resiliate
                        if (Adherent::STATUS_VALIDATED == $object->statut) {
                            if ($user->hasRight('adherent', 'supprimer')) {
                                print '<a class="butAction" href="card.php?rowid=' . ((int) $object->id) . '&action=resiliate">' . $langs->trans("Resiliate") . "</a></span>\n";
                            } else {
                                print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans("Resiliate") . '</span>' . "\n";
                            }
                        }

                        // Exclude
                        if (Adherent::STATUS_VALIDATED == $object->statut) {
                            if ($user->hasRight('adherent', 'supprimer')) {
                                print '<a class="butAction" href="card.php?rowid=' . ((int) $object->id) . '&action=exclude">' . $langs->trans("Exclude") . "</a></span>\n";
                            } else {
                                print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans("Exclude") . '</span>' . "\n";
                            }
                        }

                        // Create third party
                        if (isModEnabled('societe') && !$object->socid) {
                            if ($user->hasRight('societe', 'creer')) {
                                if (Adherent::STATUS_DRAFT != $object->statut) {
                                    print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?rowid=' . ((int) $object->id) . '&amp;action=create_thirdparty" title="' . dol_escape_htmltag($langs->trans("CreateDolibarrThirdPartyDesc")) . '">' . $langs->trans("CreateDolibarrThirdParty") . '</a>' . "\n";
                                } else {
                                    print '<a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("ValidateBefore")) . '">' . $langs->trans("CreateDolibarrThirdParty") . '</a>' . "\n";
                                }
                            } else {
                                print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans("CreateDolibarrThirdParty") . '</span>' . "\n";
                            }
                        }

                        // Create user
                        if (!$user->socid && !$object->user_id) {
                            if ($user->hasRight('user', 'user', 'creer')) {
                                if (Adherent::STATUS_DRAFT != $object->statut) {
                                    print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?rowid=' . ((int) $object->id) . '&amp;action=create_user" title="' . dol_escape_htmltag($langs->trans("CreateDolibarrLoginDesc")) . '">' . $langs->trans("CreateDolibarrLogin") . '</a>' . "\n";
                                } else {
                                    print '<a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("ValidateBefore")) . '">' . $langs->trans("CreateDolibarrLogin") . '</a>' . "\n";
                                }
                            } else {
                                print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans("CreateDolibarrLogin") . '</span>' . "\n";
                            }
                        }

                        // Action SPIP
                        if (isModEnabled('mailmanspip') && getDolGlobalString('ADHERENT_USE_SPIP')) {
                            $isinspip = $mailmanspip->is_in_spip($object);

                            if ($isinspip == 1) {
                                print '<a class="butAction" href="card.php?rowid=' . ((int) $object->id) . '&action=del_spip&token=' . newToken() . '">' . $langs->trans("DeleteIntoSpip") . '</a>' . "\n";
                            }
                            if ($isinspip == 0) {
                                print '<a class="butAction" href="card.php?rowid=' . ((int) $object->id) . '&action=add_spip&token=' . newToken() . '">' . $langs->trans("AddIntoSpip") . '</a>' . "\n";
                            }
                        }

                        // Delete
                        if ($user->hasRight('adherent', 'supprimer')) {
                            print '<a class="butActionDelete" href="card.php?rowid=' . ((int) $object->id) . '&action=delete&token=' . newToken() . '">' . $langs->trans("Delete") . '</a>' . "\n";
                        } else {
                            print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans("Delete") . '</span>' . "\n";
                        }
                    }
                }
                print '</div>';

                if ($isinspip == -1) {
                    print '<br><br><span class="error">' . $langs->trans('SPIPConnectionFailed') . ': ' . $mailmanspip->error . '</span>';
                }


                // Select mail models is same action as presend
                if (GETPOST('modelselected')) {
                    $action = 'presend';
                }

                if ($action != 'presend') {
                    print '<div class="fichecenter"><div class="fichehalfleft">';
                    print '<a name="builddoc"></a>'; // ancre

                    // Generated documents
                    $filename = dol_sanitizeFileName($object->ref);
                    $filedir = $conf->adherent->dir_output . '/' . get_exdir(0, 0, 0, 1, $object, 'member');
                    $urlsource = $_SERVER['PHP_SELF'] . '?id=' . $object->id;
                    $genallowed = $user->hasRight('adherent', 'lire');
                    $delallowed = $user->hasRight('adherent', 'creer');

                    print $formfile->showdocuments('member', $filename, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', (empty($object->default_lang) ? '' : $object->default_lang), '', $object);
                    $somethingshown = $formfile->numoffiles;

                    // Show links to link elements
                    //$linktoelem = $form->showLinkToObjectBlock($object, null, array('subscription'));
                    //$somethingshown = $form->showLinkedObjectBlock($object, '');

                    // Show links to link elements
                    /*$linktoelem = $form->showLinkToObjectBlock($object,array('order'));
                     if ($linktoelem) {
                        print ($somethingshown?'':'<br>').$linktoelem;
                    }
                     */

                    // Show online payment link
                    $useonlinepayment = (isModEnabled('paypal') || isModEnabled('stripe') || isModEnabled('paybox'));

                    $parameters = array();
                    $reshook = $hookmanager->executeHooks('doShowOnlinePaymentUrl', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
                    if ($reshook < 0) {
                        setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
                    } else {
                        $useonlinepayment = $reshook;
                    }

                    if ($useonlinepayment) {
                        print '<br>';
                        if (empty($amount)) {   // Take the maximum amount among what the member is supposed to pay / has paid in the past
                            $amount = max($adht->amount, $object->first_subscription_amount, $object->last_subscription_amount);
                        }
                        if (empty($amount)) {
                            $amount = 0;
                        }
                        require_once DOL_DOCUMENT_ROOT . '/core/lib/payments.lib.php';
                        print showOnlinePaymentUrl('membersubscription', $object->ref, $amount);
                    }

                    print '</div><div class="fichehalfright">';

                    $MAX = 10;

                    $morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', DOL_URL_ROOT . '/adherents/agenda.php?id=' . $object->id);

                    // List of actions on element
                    include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
                    $formactions = new FormActions($db);
                    $somethingshown = $formactions->showactions($object, $object->element, $socid, 1, 'listactions', $MAX, '', $morehtmlcenter);

                    print '</div></div>';
                }

                // Presend form
                $modelmail = 'member';
                $defaulttopic = 'CardContent';
                $diroutput = $conf->adherent->dir_output;
                $trackid = 'mem' . $object->id;

                include DOL_DOCUMENT_ROOT . '/core/tpl/card_presend.tpl.php';
            }
        }

// End of page
        llxFooter();
        $db->close();
    }

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

            if (!empty($val['visible'])) {
                $visible = (int) dol_eval($val['visible'], 1);
                $arrayfields[$tableprefix . '.' . $key] = [
                    'label' => $val['label'],
                    'checked' => (($visible < 0) ? 0 : 1),
                    'enabled' => (abs($visible) != 3 && (int) dol_eval($val['enabled'], 1)),
                    'position' => $val['position'],
                    'help' => isset($val['help']) ? $val['help'] : '',
                ];
            }
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

        $form = new Form($db);
        $formother = new FormOther($db);
        $membertypestatic = new AdherentType($db);
        $memberstatic = new Adherent($db);

        $now = dol_now();

// Page Header
        $title = $langs->trans("Members") . " - " . $langs->trans("List");
        $help_url = 'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros|DE:Modul_Mitglieder';
        $morejs = [];
        $morecss = [];


// Build and execute select
// --------------------------------------------------------------------
        if ((!empty($search_categ) && $search_categ > 0) || !empty($catid)) {
            $sql = "SELECT DISTINCT";
        } else {
            $sql = "SELECT";
        }
        $sql .= " d.rowid, d.ref, d.login, d.lastname, d.firstname, d.gender, d.societe as company, d.fk_soc,";
        $sql .= " d.civility, d.datefin, d.address, d.zip, d.town, d.state_id, d.country,";
        $sql .= " d.email, d.phone, d.phone_perso, d.phone_mobile, d.birth, d.public, d.photo,";
        $sql .= " d.fk_adherent_type as type_id, d.morphy, d.statut as status, d.datec as date_creation, d.tms as date_modification,";
        $sql .= " d.note_private, d.note_public, d.import_key,";
        $sql .= " s.nom,";
        $sql .= " " . $db->ifsql("d.societe IS NULL", "s.nom", "d.societe") . " as companyname,";
        $sql .= " t.libelle as type, t.subscription,";
        $sql .= " state.code_departement as state_code, state.nom as state_name";

// Add fields from extrafields
        if (!empty($extrafields->attributes[$object->table_element]['label'])) {
            foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
                $sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef." . $key . " as options_" . $key : '');
            }
        }

// Add fields from hooks
        $parameters = [];
        $reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
        $sql .= $hookmanager->resPrint;
        $sql = preg_replace('/,\s*$/', '', $sql);

        $sqlfields = $sql; // $sql fields to remove for count total

// SQL Alias adherent
        $sql .= " FROM " . MAIN_DB_PREFIX . "adherent as d";  // maybe better to use ad (adh) instead of d
        if (!empty($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . $object->table_element . "_extrafields as ef on (d.rowid = ef.fk_object)";
        }
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_country as country on (country.rowid = d.country)";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_departements as state on (state.rowid = d.state_id)";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s on (s.rowid = d.fk_soc)";

// SQL Alias adherent_type
        $sql .= ", " . MAIN_DB_PREFIX . "adherent_type as t";
        $sql .= " WHERE d.fk_adherent_type = t.rowid";

        if ($catid && empty($search_categ)) {
            $search_categ = $catid;
        }

        $searchCategoryContactList = $search_categ ? [$search_categ] : [];
        $searchCategoryContactOperator = 0;
// Search for tag/category ($searchCategoryContactList is an array of ID)
        if (!empty($searchCategoryContactList)) {
            $searchCategoryContactSqlList = [];
            $listofcategoryid = '';
            foreach ($searchCategoryContactList as $searchCategoryContact) {
                if (intval($searchCategoryContact) == -2) {
                    $searchCategoryContactSqlList[] = "NOT EXISTS (SELECT ck.fk_categorie FROM " . MAIN_DB_PREFIX . "categorie_member as ck WHERE d.rowid = ck.fk_member)";
                } elseif (intval($searchCategoryContact) > 0) {
                    if ($searchCategoryContactOperator == 0) {
                        $searchCategoryContactSqlList[] = " EXISTS (SELECT ck.fk_categorie FROM " . MAIN_DB_PREFIX . "categorie_member as ck WHERE d.rowid = ck.fk_member AND ck.fk_categorie = " . ((int) $searchCategoryContact) . ")";
                    } else {
                        $listofcategoryid .= ($listofcategoryid ? ', ' : '') . ((int) $searchCategoryContact);
                    }
                }
            }
            if ($listofcategoryid) {
                $searchCategoryContactSqlList[] = " EXISTS (SELECT ck.fk_categorie FROM " . MAIN_DB_PREFIX . "categorie_member as ck WHERE d.rowid = ck.fk_member AND ck.fk_categorie IN (" . $db->sanitize($listofcategoryid) . "))";
            }
            if ($searchCategoryContactOperator == 1) {
                if (!empty($searchCategoryContactSqlList)) {
                    $sql .= " AND (" . implode(' OR ', $searchCategoryContactSqlList) . ")";
                }
            } else {
                if (!empty($searchCategoryContactSqlList)) {
                    $sql .= " AND (" . implode(' AND ', $searchCategoryContactSqlList) . ")";
                }
            }
        }

        $sql .= " AND d.entity IN (" . getEntity('adherent') . ")";
        if ($search_all) {
            $sql .= natural_search(array_keys($fieldstosearchall), $search_all);
        }
        if ($search_type > 0) {
            $sql .= " AND t.rowid=" . ((int) $search_type);
        }
        if ($search_filter == 'withoutsubscription') {
            $sql .= " AND (datefin IS NULL)";
        }
        if ($search_filter == 'waitingsubscription') {
            $sql .= " AND (datefin IS NULL AND t.subscription = '1')";
        }
        if ($search_filter == 'uptodate') {
            $sql .= " AND (datefin >= '" . $db->idate($now) . "' OR (datefin IS NULL AND t.subscription = '0'))";
        }
        if ($search_filter == 'outofdate') {
            $sql .= " AND (datefin < '" . $db->idate($now) . "')";
        }
        if ($search_status != '') {
            // Peut valoir un nombre ou liste de nombre separates par virgules
            $sql .= " AND d.statut in (" . $db->sanitize($db->escape($search_status)) . ")";
        }
        if ($search_morphy != '' && $search_morphy != '-1') {
            $sql .= natural_search("d.morphy", $search_morphy);
        }
        if ($search_ref) {
            $sql .= natural_search("d.ref", $search_ref);
        }
        if ($search_civility) {
            $sql .= natural_search("d.civility", $search_civility);
        }
        if ($search_firstname) {
            $sql .= natural_search("d.firstname", $search_firstname);
        }
        if ($search_lastname) {
            $sql .= natural_search(["d.firstname", "d.lastname", "d.societe"], $search_lastname);
        }
        if ($search_gender != '' && $search_gender != '-1') {
            $sql .= natural_search("d.gender", $search_gender);
        }
        if ($search_login) {
            $sql .= natural_search("d.login", $search_login);
        }
        if ($search_company) {
            $sql .= natural_search("s.nom", $search_company);
        }
        if ($search_email) {
            $sql .= natural_search("d.email", $search_email);
        }
        if ($search_address) {
            $sql .= natural_search("d.address", $search_address);
        }
        if ($search_town) {
            $sql .= natural_search("d.town", $search_town);
        }
        if ($search_zip) {
            $sql .= natural_search("d.zip", $search_zip);
        }
        if ($search_state) {
            $sql .= natural_search("state.nom", $search_state);
        }
        if ($search_phone) {
            $sql .= natural_search("d.phone", $search_phone);
        }
        if ($search_phone_perso) {
            $sql .= natural_search("d.phone_perso", $search_phone_perso);
        }
        if ($search_phone_mobile) {
            $sql .= natural_search("d.phone_mobile", $search_phone_mobile);
        }
        if ($search_country) {
            $sql .= " AND d.country IN (" . $db->sanitize($search_country) . ')';
        }
        if ($search_import_key) {
            $sql .= natural_search("d.import_key", $search_import_key);
        }
        if ($search_datec_start) {
            $sql .= " AND d.datec >= '" . $db->idate($search_datec_start) . "'";
        }
        if ($search_datec_end) {
            $sql .= " AND d.datec <= '" . $db->idate($search_datec_end) . "'";
        }
        if ($search_datem_start) {
            $sql .= " AND d.tms >= '" . $db->idate($search_datem_start) . "'";
        }
        if ($search_datem_end) {
            $sql .= " AND d.tms <= '" . $db->idate($search_datem_end) . "'";
        }
// Add where from extra fields
        include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
        $parameters = [];
        $reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
        $sql .= $hookmanager->resPrint;

// Count total nb of records
        $nbtotalofrecords = '';
        if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
            /* The fast and low memory method to get and count full list converts the sql into a sql count */
            $sqlforcount = preg_replace('/^' . preg_quote($sqlfields, '/') . '/', 'SELECT COUNT(*) as nbtotalofrecords', $sql);
            $sqlforcount = preg_replace('/GROUP BY .*$/', '', $sqlforcount);
            $resql = $db->query($sqlforcount);
            if ($resql) {
                $objforcount = $db->fetch_object($resql);
                $nbtotalofrecords = $objforcount->nbtotalofrecords;
            } else {
                dol_print_error($db);
            }

            if (($page * $limit) > $nbtotalofrecords) { // if total resultset is smaller than the paging size (filtering), goto and load page 0
                $page = 0;
                $offset = 0;
            }
            $db->free($resql);
        }
//print $sql;

// Complete request and execute it with limit
        $sql .= $db->order($sortfield, $sortorder);
        if ($limit) {
            $sql .= $db->plimit($limit + 1, $offset);
        }

        $resql = $db->query($sql);
        if (!$resql) {
            dol_print_error($db);
            exit;
        }

        $num = $db->num_rows($resql);


// Direct jump if only one record found
        if ($num == 1 && getDolGlobalString('MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE') && $search_all && !$page) {
            $obj = $db->fetch_object($resql);
            $id = $obj->rowid;
            header("Location: " . DOL_URL_ROOT . '/adherents/card.php?id=' . $id);
            exit;
        }

// Output page
// --------------------------------------------------------------------

        llxHeader('', $title, $help_url, '', 0, 0, $morejs, $morecss, '', 'bodyforlist');   // Can use also classforhorizontalscrolloftabs instead of bodyforlist for no horizontal scroll

        $arrayofselected = is_array($toselect) ? $toselect : [];


        if ($search_type > 0) {
            $membertype = new AdherentType($db);
            $result = $membertype->fetch($search_type);
            $title .= " (" . $membertype->label . ")";
        }

// $parameters
        $param = '';
        if (!empty($mode)) {
            $param .= '&mode=' . urlencode($mode);
        }
        if (!empty($contextpage) && $contextpage != $_SERVER['PHP_SELF']) {
            $param .= '&contextpage=' . urlencode($contextpage);
        }
        if ($limit > 0 && $limit != $conf->liste_limit) {
            $param .= '&limit=' . ((int) $limit);
        }
        if ($optioncss != '') {
            $param .= '&optioncss=' . urlencode($optioncss);
        }
        if ($search_all != "") {
            $param .= "&search_all=" . urlencode($search_all);
        }
        if ($search_ref) {
            $param .= "&search_ref=" . urlencode($search_ref);
        }
        if ($search_civility) {
            $param .= "&search_civility=" . urlencode($search_civility);
        }
        if ($search_firstname) {
            $param .= "&search_firstname=" . urlencode($search_firstname);
        }
        if ($search_lastname) {
            $param .= "&search_lastname=" . urlencode($search_lastname);
        }
        if ($search_gender) {
            $param .= "&search_gender=" . urlencode($search_gender);
        }
        if ($search_login) {
            $param .= "&search_login=" . urlencode($search_login);
        }
        if ($search_email) {
            $param .= "&search_email=" . urlencode($search_email);
        }
        if ($search_categ > 0 || $search_categ == -2) {
            $param .= "&search_categ=" . urlencode((string) ($search_categ));
        }
        if ($search_company) {
            $param .= "&search_company=" . urlencode($search_company);
        }
        if ($search_address != '') {
            $param .= "&search_address=" . urlencode($search_address);
        }
        if ($search_town != '') {
            $param .= "&search_town=" . urlencode($search_town);
        }
        if ($search_zip != '') {
            $param .= "&search_zip=" . urlencode($search_zip);
        }
        if ($search_state != '') {
            $param .= "&search_state=" . urlencode($search_state);
        }
        if ($search_country != '') {
            $param .= "&search_country=" . urlencode($search_country);
        }
        if ($search_phone != '') {
            $param .= "&search_phone=" . urlencode($search_phone);
        }
        if ($search_phone_perso != '') {
            $param .= "&search_phone_perso=" . urlencode($search_phone_perso);
        }
        if ($search_phone_mobile != '') {
            $param .= "&search_phone_mobile=" . urlencode($search_phone_mobile);
        }
        if ($search_filter && $search_filter != '-1') {
            $param .= "&search_filter=" . urlencode($search_filter);
        }
        if ($search_status != "" && $search_status != -3) {
            $param .= "&search_status=" . urlencode($search_status);
        }
        if ($search_import_key != '') {
            $param .= '&search_import_key=' . urlencode($search_import_key);
        }
        if ($search_type > 0) {
            $param .= "&search_type=" . urlencode($search_type);
        }
        if ($search_datec_start) {
            $param .= '&search_datec_start_day=' . dol_print_date($search_datec_start, '%d') . '&search_datec_start_month=' . dol_print_date($search_datec_start, '%m') . '&search_datec_start_year=' . dol_print_date($search_datec_start, '%Y');
        }
        if ($search_datem_end) {
            $param .= '&search_datem_end_day=' . dol_print_date($search_datem_end, '%d') . '&search_datem_end_month=' . dol_print_date($search_datem_end, '%m') . '&search_datem_end_year=' . dol_print_date($search_datem_end, '%Y');
        }

// Add $param from extra fields
        include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_param.tpl.php';

// List of mass actions available
        $arrayofmassactions = [
            //'presend'=>img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
            //'builddoc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
        ];
        if ($user->hasRight('adherent', 'creer')) {
            $arrayofmassactions['close'] = img_picto('', 'close_title', 'class="pictofixedwidth"') . $langs->trans("Resiliate");
        }
        if ($user->hasRight('adherent', 'supprimer')) {
            $arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"') . $langs->trans("Delete");
        }
        if (isModEnabled('category') && $user->hasRight('adherent', 'creer')) {
            $arrayofmassactions['preaffecttag'] = img_picto('', 'category', 'class="pictofixedwidth"') . $langs->trans("AffectTag");
        }
        if ($user->hasRight('adherent', 'creer') && $user->hasRight('user', 'user', 'creer')) {
            $arrayofmassactions['createexternaluser'] = img_picto('', 'user', 'class="pictofixedwidth"') . $langs->trans("CreateExternalUser");
        }
        if ($user->hasRight('adherent', 'creer')) {
            $arrayofmassactions['createsubscription'] = img_picto('', 'payment', 'class="pictofixedwidth"') . $langs->trans("CreateSubscription");
        }
        if (GETPOSTINT('nomassaction') || in_array($massaction, ['presend', 'predelete', 'preaffecttag'])) {
            $arrayofmassactions = [];
        }
        $massactionbutton = $form->selectMassAction('', $arrayofmassactions);

        print '<form method="POST" id="searchFormList" action="' . $_SERVER['PHP_SELF'] . '">' . "\n";
        if ($optioncss != '') {
            print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
        }
        print '<input type="hidden" name="token" value="' . newToken() . '">';
        print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
        print '<input type="hidden" name="action" value="list">';
        print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
        print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
        print '<input type="hidden" name="page" value="' . $page . '">';
        print '<input type="hidden" name="contextpage" value="' . $contextpage . '">';
        print '<input type="hidden" name="page_y" value="">';
        print '<input type="hidden" name="mode" value="' . $mode . '">';


        $newcardbutton = '';
        $newcardbutton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', $_SERVER['PHP_SELF'] . '?mode=common' . preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ((empty($mode) || $mode == 'common') ? 2 : 1), ['morecss' => 'reposition']);
        $newcardbutton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', $_SERVER['PHP_SELF'] . '?mode=kanban' . preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ($mode == 'kanban' ? 2 : 1), ['morecss' => 'reposition']);
        if ($user->hasRight('adherent', 'creer')) {
            $newcardbutton .= dolGetButtonTitleSeparator();
            $newcardbutton .= dolGetButtonTitle($langs->trans('NewMember'), '', 'fa fa-plus-circle', DOL_URL_ROOT . '/adherents/card.php?action=create');
        }

        print_barre_liste($title, $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, $object->picto, 0, $newcardbutton, '', $limit, 0, 0, 1);

        $topicmail = "Information";
        $modelmail = "member";
        $objecttmp = new Adherent($db);
        $trackid = 'mem' . $object->id;
        if ($massaction == 'createsubscription') {
            $tmpmember = new Adherent($db);
            $adht = new AdherentType($db);
            $amount = 0;
            foreach ($toselect as $id) {
                $now = dol_now();
                $tmpmember->fetch($id);
                $res = $adht->fetch($tmpmember->typeid);
                if ($res > 0) {
                    $amounttmp = $adht->amount;
                    if (!empty($tmpmember->last_subscription_amount) && !GETPOSTISSET('newamount') && is_numeric($amounttmp)) {
                        $amounttmp = max($tmpmember->last_subscription_amount, $amount);
                    }
                    $amount = max(0, $amounttmp, $amount);
                } else {
                    $error++;
                }
            }

            $date = dol_print_date(dol_now(), "%d/%m/%Y");
            $formquestion = [
                ['label' => $langs->trans("DateSubscription"), 'type' => 'other', 'value' => $date],
                ['label' => $langs->trans("Amount"), 'type' => 'text', 'value' => price($amount, 0, '', 0), 'name' => 'amount'],
                ['type' => 'separator'],
                ['label' => $langs->trans("MoreActions"), 'type' => 'other', 'value' => $langs->trans("None") . ' ' . img_warning($langs->trans("WarningNoComplementaryActionDone"))],
            ];
            print $form->formconfirm($_SERVER['PHP_SELF'], $langs->trans("ConfirmMassSubsriptionCreation"), $langs->trans("ConfirmMassSubsriptionCreationQuestion", count($toselect)), "createsubscription_confirm", $formquestion, '', 0, 200, 500, 1);
        }
        include DOL_DOCUMENT_ROOT . '/core/tpl/massactions_pre.tpl.php';

        if ($search_all) {
            $setupstring = '';
            foreach ($fieldstosearchall as $key => $val) {
                $fieldstosearchall[$key] = $langs->trans($val);
                $setupstring .= $key . "=" . $val . ";";
            }
            print '<!-- Search done like if MYOBJECT_QUICKSEARCH_ON_FIELDS = ' . $setupstring . ' -->' . "\n";
            print '<div class="divsearchfieldfilter">' . $langs->trans("FilterOnInto", $search_all) . implode(', ', $fieldstosearchall) . '</div>' . "\n";
        }

        $varpage = empty($contextpage) ? $_SERVER['PHP_SELF'] : $contextpage;
        $selectedfields = ($mode != 'kanban' ? $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) : ''); // This also change content of $arrayfields
        $selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

        $moreforfilter = '';
// Filter on categories
        if (isModEnabled('category') && $user->hasRight('categorie', 'lire')) {
            require_once BASE_PATH . '/categories/class/categorie.class.php';
            $moreforfilter .= '<div class="divsearchfield">';
            $moreforfilter .= img_picto($langs->trans('Categories'), 'category', 'class="pictofixedwidth"') . $formother->select_categories(Categorie::TYPE_MEMBER, $search_categ, 'search_categ', 1, $langs->trans("MembersCategoriesShort"));
            $moreforfilter .= '</div>';
        }
        $parameters = [
            'arrayfields' => &$arrayfields,
        ];
        $reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
        if (empty($reshook)) {
            $moreforfilter .= $hookmanager->resPrint;
        } else {
            $moreforfilter = $hookmanager->resPrint;
        }
        if (!empty($moreforfilter)) {
            print '<div class="liste_titre liste_titre_bydiv centpercent">';
            print $moreforfilter;
            $parameters = [];
            $reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
            print $hookmanager->resPrint;
            print '</div>';
        }

        print '<div class="div-table-responsive">';
        print '<table class="tagtable nobottomiftotal liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">' . "\n";

// Fields title search
// --------------------------------------------------------------------
        print '<tr class="liste_titre_filter">';

// Action column
        if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
            print '<td class="liste_titre center maxwidthsearch">';
            $searchpicto = $form->showFilterButtons('left');
            print $searchpicto;
            print '</td>';
        }

// Line numbering
        if (getDolGlobalString('MAIN_SHOW_TECHNICAL_ID')) {
            print '<td class="liste_titre">&nbsp;</td>';
        }

// Ref
        if (!empty($arrayfields['d.ref']['checked'])) {
            print '<td class="liste_titre">';
            print '<input type="text" class="flat maxwidth75imp" name="search_ref" value="' . dol_escape_htmltag($search_ref) . '">';
            print '</td>';
        }

// Civility
        if (!empty($arrayfields['d.civility']['checked'])) {
            print '<td class="liste_titre left">';
            print '<input class="flat maxwidth50imp" type="text" name="search_civility" value="' . dol_escape_htmltag($search_civility) . '"></td>';
        }

// First Name
        if (!empty($arrayfields['d.firstname']['checked'])) {
            print '<td class="liste_titre left">';
            print '<input class="flat maxwidth75imp" type="text" name="search_firstname" value="' . dol_escape_htmltag($search_firstname) . '"></td>';
        }

// Last Name
        if (!empty($arrayfields['d.lastname']['checked'])) {
            print '<td class="liste_titre left">';
            print '<input class="flat maxwidth75imp" type="text" name="search_lastname" value="' . dol_escape_htmltag($search_lastname) . '"></td>';
        }

// Gender
        if (!empty($arrayfields['d.gender']['checked'])) {
            print '<td class="liste_titre">';
            $arraygender = ['man' => $langs->trans("Genderman"), 'woman' => $langs->trans("Genderwoman"), 'other' => $langs->trans("Genderother")];
            print $form->selectarray('search_gender', $arraygender, $search_gender, 1);
            print '</td>';
        }

// Company
        if (!empty($arrayfields['d.company']['checked'])) {
            print '<td class="liste_titre left">';
            print '<input class="flat maxwidth75imp" type="text" name="search_company" value="' . dol_escape_htmltag($search_company) . '"></td>';
        }

// Login
        if (!empty($arrayfields['d.login']['checked'])) {
            print '<td class="liste_titre left">';
            print '<input class="flat maxwidth75imp" type="text" name="search_login" value="' . dol_escape_htmltag($search_login) . '"></td>';
        }

// Nature
        if (!empty($arrayfields['d.morphy']['checked'])) {
            print '<td class="liste_titre center">';
            $arraymorphy = ['mor' => $langs->trans("Moral"), 'phy' => $langs->trans("Physical")];
            print $form->selectarray('search_morphy', $arraymorphy, $search_morphy, 1, 0, 0, '', 0, 0, 0, '', 'maxwidth100');
            print '</td>';
        }

// Member Type
        if (!empty($arrayfields['t.libelle']['checked'])) {
            print '</td>';
        }
        if (!empty($arrayfields['t.libelle']['checked'])) {
            print '<td class="liste_titre">';
            $listetype = $membertypestatic->liste_array();
            // @phan-suppress-next-line PhanPluginSuspiciousParamOrder
            print $form->selectarray("search_type", $listetype, $search_type, 1, 0, 0, '', 0, 32);
            print '</td>';
        }

// Address - Street
        if (!empty($arrayfields['d.address']['checked'])) {
            print '<td class="liste_titre left">';
            print '<input class="flat maxwidth75imp" type="text" name="search_address" value="' . dol_escape_htmltag($search_address) . '"></td>';
        }

// ZIP
        if (!empty($arrayfields['d.zip']['checked'])) {
            print '<td class="liste_titre left">';
            print '<input class="flat maxwidth50imp" type="text" name="search_zip" value="' . dol_escape_htmltag($search_zip) . '"></td>';
        }

// Town/City
        if (!empty($arrayfields['d.town']['checked'])) {
            print '<td class="liste_titre left">';
            print '<input class="flat maxwidth75imp" type="text" name="search_town" value="' . dol_escape_htmltag($search_town) . '"></td>';
        }

// State / County / Departement
        if (!empty($arrayfields['state.nom']['checked'])) {
            print '<td class="liste_titre">';
            print '<input class="flat searchstring maxwidth75imp" type="text" name="search_state" value="' . dol_escape_htmltag($search_state) . '">';
            print '</td>';
        }

// Country
        if (!empty($arrayfields['country.code_iso']['checked'])) {
            print '<td class="liste_titre center">';
            print $form->select_country($search_country, 'search_country', '', 0, 'minwidth100imp maxwidth100');
            print '</td>';
        }

// Phone pro
        if (!empty($arrayfields['d.phone']['checked'])) {
            print '<td class="liste_titre left">';
            print '<input class="flat maxwidth75imp" type="text" name="search_phone" value="' . dol_escape_htmltag($search_phone) . '"></td>';
        }

// Phone perso
        if (!empty($arrayfields['d.phone_perso']['checked'])) {
            print '<td class="liste_titre left">';
            print '<input class="flat maxwidth75imp" type="text" name="search_phone_perso" value="' . dol_escape_htmltag($search_phone_perso) . '"></td>';
        }

// Phone mobile
        if (!empty($arrayfields['d.phone_mobile']['checked'])) {
            print '<td class="liste_titre left">';
            print '<input class="flat maxwidth75imp" type="text" name="search_phone_mobile" value="' . dol_escape_htmltag($search_phone_mobile) . '"></td>';
        }

// Email
        if (!empty($arrayfields['d.email']['checked'])) {
            print '<td class="liste_titre left">';
            print '<input class="flat maxwidth75imp" type="text" name="search_email" value="' . dol_escape_htmltag($search_email) . '"></td>';
        }

// End of subscription date
        if (!empty($arrayfields['d.datefin']['checked'])) {
            print '<td class="liste_titre center">';
            //$selectarray = array('-1'=>'', 'withoutsubscription'=>$langs->trans("WithoutSubscription"), 'uptodate'=>$langs->trans("UpToDate"), 'outofdate'=>$langs->trans("OutOfDate"));
            $selectarray = ['-1' => '', 'waitingsubscription' => $langs->trans("WaitingSubscription"), 'uptodate' => $langs->trans("UpToDate"), 'outofdate' => $langs->trans("OutOfDate")];
            print $form->selectarray('search_filter', $selectarray, $search_filter);
            print '</td>';
        }

// Extra fields
        include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
        $parameters = ['arrayfields' => $arrayfields];
        $reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;

// Date creation
        if (!empty($arrayfields['d.datec']['checked'])) {
            print '<td class="liste_titre">';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_datec_start ? $search_datec_start : -1, 'search_datec_start_', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
            print '</div>';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_datec_end ? $search_datec_end : -1, 'search_datec_end_', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
            print '</div>';
            print '</td>';
        }

// Birthday
        if (!empty($arrayfields['d.birth']['checked'])) {
            print '<td class="liste_titre">';
            print '</td>';
        }

// Date modification
        if (!empty($arrayfields['d.tms']['checked'])) {
            print '<td class="liste_titre">';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_datem_start ? $search_datem_start : -1, 'search_datem_start_', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
            print '</div>';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_datem_end ? $search_datem_end : -1, 'search_datem_end_', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
            print '</div>';
            print '</td>';
        }

// Import Key
        if (!empty($arrayfields['d.import_key']['checked'])) {
            print '<td class="liste_titre center">';
            print '<input class="flat searchstring maxwidth50" type="text" name="search_import_key" value="' . dol_escape_htmltag($search_import_key) . '">';
            print '</td>';
        }

// Status
        if (!empty($arrayfields['d.statut']['checked'])) {
            print '<td class="liste_titre center parentonrightofpage">';
            $liststatus = [
                Adherent::STATUS_DRAFT => $langs->trans("Draft"),
                Adherent::STATUS_VALIDATED => $langs->trans("Validated"),
                Adherent::STATUS_RESILIATED => $langs->trans("MemberStatusResiliatedShort"),
                Adherent::STATUS_EXCLUDED => $langs->trans("MemberStatusExcludedShort"),
            ];
            // @phan-suppress-next-line PhanPluginSuspiciousParamOrder
            print $form->selectarray('search_status', $liststatus, $search_status, -3, 0, 0, '', 0, 0, 0, '', 'search_status width100 onrightofpage');
            print '</td>';
        }

// Action column
        if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
            print '<td class="liste_titre center maxwidthsearch">';
            $searchpicto = $form->showFilterButtons();
            print $searchpicto;
            print '</td>';
        }
        print '</tr>' . "\n";

        $totalarray = [];
        $totalarray['nbfield'] = 0;

// Fields title label
// --------------------------------------------------------------------
        print '<tr class="liste_titre">';
// Action column
        if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
            print_liste_field_titre($selectedfields, $_SERVER['PHP_SELF'], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch actioncolumn ');
            $totalarray['nbfield']++;
        }
        if (getDolGlobalString('MAIN_SHOW_TECHNICAL_ID')) {
            print_liste_field_titre("ID", $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, 'center ');
            $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['d.ref']['checked'])) {
            print_liste_field_titre($arrayfields['d.ref']['label'], $_SERVER['PHP_SELF'], 'd.ref', '', $param, '', $sortfield, $sortorder);
            $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['d.civility']['checked'])) {
            print_liste_field_titre($arrayfields['d.civility']['label'], $_SERVER['PHP_SELF'], 'd.civility', '', $param, '', $sortfield, $sortorder);
            $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['d.firstname']['checked'])) {
            print_liste_field_titre($arrayfields['d.firstname']['label'], $_SERVER['PHP_SELF'], 'd.firstname', '', $param, '', $sortfield, $sortorder);
            $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['d.lastname']['checked'])) {
            print_liste_field_titre($arrayfields['d.lastname']['label'], $_SERVER['PHP_SELF'], 'd.lastname', '', $param, '', $sortfield, $sortorder);
            $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['d.gender']['checked'])) {
            print_liste_field_titre($arrayfields['d.gender']['label'], $_SERVER['PHP_SELF'], 'd.gender', $param, "", "", $sortfield, $sortorder);
            $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['d.company']['checked'])) {
            print_liste_field_titre($arrayfields['d.company']['label'], $_SERVER['PHP_SELF'], 'companyname', '', $param, '', $sortfield, $sortorder);
            $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['d.login']['checked'])) {
            print_liste_field_titre($arrayfields['d.login']['label'], $_SERVER['PHP_SELF'], 'd.login', '', $param, '', $sortfield, $sortorder);
            $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['d.morphy']['checked'])) {
            print_liste_field_titre($arrayfields['d.morphy']['label'], $_SERVER['PHP_SELF'], 'd.morphy', '', $param, '', $sortfield, $sortorder);
            $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['t.libelle']['checked'])) {
            print_liste_field_titre($arrayfields['t.libelle']['label'], $_SERVER['PHP_SELF'], 't.libelle', '', $param, '', $sortfield, $sortorder);
            $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['d.address']['checked'])) {
            print_liste_field_titre($arrayfields['d.address']['label'], $_SERVER['PHP_SELF'], 'd.address', '', $param, '', $sortfield, $sortorder);
            $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['d.zip']['checked'])) {
            print_liste_field_titre($arrayfields['d.zip']['label'], $_SERVER['PHP_SELF'], 'd.zip', '', $param, '', $sortfield, $sortorder);
            $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['d.town']['checked'])) {
            print_liste_field_titre($arrayfields['d.town']['label'], $_SERVER['PHP_SELF'], 'd.town', '', $param, '', $sortfield, $sortorder);
            $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['state.nom']['checked'])) {
            print_liste_field_titre($arrayfields['state.nom']['label'], $_SERVER['PHP_SELF'], "state.nom", "", $param, '', $sortfield, $sortorder);
            $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['country.code_iso']['checked'])) {
            print_liste_field_titre($arrayfields['country.code_iso']['label'], $_SERVER['PHP_SELF'], "country.code_iso", "", $param, '', $sortfield, $sortorder, 'center ');
            $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['d.phone']['checked'])) {
            print_liste_field_titre($arrayfields['d.phone']['label'], $_SERVER['PHP_SELF'], 'd.phone', '', $param, '', $sortfield, $sortorder);
            $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['d.phone_perso']['checked'])) {
            print_liste_field_titre($arrayfields['d.phone_perso']['label'], $_SERVER['PHP_SELF'], 'd.phone_perso', '', $param, '', $sortfield, $sortorder);
            $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['d.phone_mobile']['checked'])) {
            print_liste_field_titre($arrayfields['d.phone_mobile']['label'], $_SERVER['PHP_SELF'], 'd.phone_mobile', '', $param, '', $sortfield, $sortorder);
            $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['d.email']['checked'])) {
            print_liste_field_titre($arrayfields['d.email']['label'], $_SERVER['PHP_SELF'], 'd.email', '', $param, '', $sortfield, $sortorder);
            $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['d.datefin']['checked'])) {
            print_liste_field_titre($arrayfields['d.datefin']['label'], $_SERVER['PHP_SELF'], 'd.datefin,t.subscription', '', $param, '', $sortfield, $sortorder, 'center ');
            $totalarray['nbfield']++;
        }
// Extra fields
        include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_title.tpl.php';

// Hook fields
        $parameters = ['arrayfields' => $arrayfields, 'totalarray' => &$totalarray, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder];
        $reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;

        if (!empty($arrayfields['d.datec']['checked'])) {
            print_liste_field_titre($arrayfields['d.datec']['label'], $_SERVER['PHP_SELF'], "d.datec", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
            $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['d.birth']['checked'])) {
            print_liste_field_titre($arrayfields['d.birth']['label'], $_SERVER['PHP_SELF'], "d.birth", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
            $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['d.tms']['checked'])) {
            print_liste_field_titre($arrayfields['d.tms']['label'], $_SERVER['PHP_SELF'], "d.tms", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
            $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['d.import_key']['checked'])) {
            print_liste_field_titre($arrayfields['d.import_key']['label'], $_SERVER['PHP_SELF'], "d.import_key", "", $param, '', $sortfield, $sortorder, 'center ');
            $totalarray['nbfield']++;
        }
        if (!empty($arrayfields['d.statut']['checked'])) {
            print_liste_field_titre($arrayfields['d.statut']['label'], $_SERVER['PHP_SELF'], "d.statut,t.subscription,d.datefin", "", $param, '', $sortfield, $sortorder, 'center ');
            $totalarray['nbfield']++;
        }
        if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
            print_liste_field_titre($selectedfields, $_SERVER['PHP_SELF'], "", '', '', '', $sortfield, $sortorder, 'maxwidthsearch center ');
            $totalarray['nbfield']++;
        }
        print "</tr>\n";

// Loop on record
// --------------------------------------------------------------------
        $i = 0;
        $savnbfield = $totalarray['nbfield'];
        $totalarray = [];
        $totalarray['nbfield'] = 0;
        $imaxinloop = ($limit ? min($num, $limit) : $num);
        while ($i < $imaxinloop) {
            $obj = $db->fetch_object($resql);
            if (empty($obj)) {
                break; // Should not happen
            }

            $datefin = $db->jdate($obj->datefin);

            $memberstatic->id = $obj->rowid;
            $memberstatic->ref = $obj->ref;
            $memberstatic->civility_id = $obj->civility;
            $memberstatic->login = $obj->login;
            $memberstatic->lastname = $obj->lastname;
            $memberstatic->firstname = $obj->firstname;
            $memberstatic->gender = $obj->gender;
            $memberstatic->statut = $obj->status;
            $memberstatic->status = $obj->status;
            $memberstatic->datefin = $datefin;
            $memberstatic->socid = $obj->fk_soc;
            $memberstatic->photo = $obj->photo;
            $memberstatic->email = $obj->email;
            $memberstatic->morphy = $obj->morphy;
            $memberstatic->note_public = $obj->note_public;
            $memberstatic->note_private = $obj->note_private;
            $memberstatic->need_subscription = $obj->subscription;

            if (!empty($obj->fk_soc)) {
                $memberstatic->fetch_thirdparty();
                if ($memberstatic->thirdparty->id > 0) {
                    $companyname = $memberstatic->thirdparty->name;
                    $companynametoshow = $memberstatic->thirdparty->getNomUrl(1);
                }
            } else {
                $companyname = $obj->company;
                $companynametoshow = $obj->company;
            }
            $memberstatic->company = $companyname;

            $object = $memberstatic;

            if ($mode == 'kanban') {
                if ($i == 0) {
                    print '<tr class="trkanban"><td colspan="' . $savnbfield . '">';
                    print '<div class="box-flex-container kanban">';
                }
                $membertypestatic->id = $obj->type_id;
                $membertypestatic->label = $obj->type;
                $memberstatic->type = $membertypestatic->label;
                $memberstatic->photo = $obj->photo;
                // Output Kanban
                print $memberstatic->getKanbanView('', ['selected' => in_array($object->id, $arrayofselected)]);
                if ($i == (min($num, $limit) - 1)) {
                    print '</div>';
                    print '</td></tr>';
                }
            } else {
                // Show line of result
                $j = 0;
                print '<tr data-rowid="' . $object->id . '" class="oddeven">';

                // Action column
                if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                    print '<td class="nowrap center">';
                    if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
                        $selected = 0;
                        if (in_array($obj->rowid, $arrayofselected)) {
                            $selected = 1;
                        }
                        print '<input id="cb' . $obj->rowid . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $obj->rowid . '"' . ($selected ? ' checked="checked"' : '') . '>';
                    }
                    print '</td>';
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }
                // Technical ID
                if (getDolGlobalString('MAIN_SHOW_TECHNICAL_ID')) {
                    print '<td class="center" data-key="id">' . $obj->rowid . '</td>';
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }
                // Ref
                if (!empty($arrayfields['d.ref']['checked'])) {
                    print "<td>";
                    print $memberstatic->getNomUrl(-1, 0, 'card', 'ref', '', -1, 0, 1);
                    print "</td>\n";
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }
                // Civility
                if (!empty($arrayfields['d.civility']['checked'])) {
                    print "<td>";
                    print $obj->civility;
                    print "</td>\n";
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }
                // Firstname
                if (!empty($arrayfields['d.firstname']['checked'])) {
                    print '<td class="tdoverflowmax150" title="' . dol_escape_htmltag($obj->firstname) . '">';
                    print $memberstatic->getNomUrl(0, 0, 'card', 'firstname');
                    //print $obj->firstname;
                    print "</td>\n";
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }
                // Lastname
                if (!empty($arrayfields['d.lastname']['checked'])) {
                    print '<td class="tdoverflowmax150" title="' . dol_escape_htmltag($obj->lastname) . '">';
                    print $memberstatic->getNomUrl(0, 0, 'card', 'lastname');
                    //print $obj->lastname;
                    print "</td>\n";
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }
                // Gender
                if (!empty($arrayfields['d.gender']['checked'])) {
                    print '<td>';
                    if ($obj->gender) {
                        print $langs->trans("Gender" . $obj->gender);
                    }
                    print '</td>';
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }
                // Company
                if (!empty($arrayfields['d.company']['checked'])) {
                    print '<td class="tdoverflowmax150" title="' . dol_escape_htmltag($companyname) . '">';
                    print $companynametoshow;
                    print "</td>\n";
                }
                // Login
                if (!empty($arrayfields['d.login']['checked'])) {
                    print '<td class="tdoverflowmax150" title="' . dol_escape_htmltag($obj->login) . '">' . $obj->login . "</td>\n";
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }
                // Nature (Moral/Physical)
                if (!empty($arrayfields['d.morphy']['checked'])) {
                    print '<td class="center">';
                    print $memberstatic->getmorphylib('', 2);
                    print "</td>\n";
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }
                // Type label
                if (!empty($arrayfields['t.libelle']['checked'])) {
                    $membertypestatic->id = $obj->type_id;
                    $membertypestatic->label = $obj->type;
                    print '<td class="nowraponall">';
                    print $membertypestatic->getNomUrl(1, 32);
                    print '</td>';
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }
                // Address
                if (!empty($arrayfields['d.address']['checked'])) {
                    print '<td class="nocellnopadd tdoverflowmax200" title="' . dol_escape_htmltag($obj->address) . '">';
                    print $obj->address;
                    print '</td>';
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }
                // Zip
                if (!empty($arrayfields['d.zip']['checked'])) {
                    print '<td class="nocellnopadd">';
                    print $obj->zip;
                    print '</td>';
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }
                // Town
                if (!empty($arrayfields['d.town']['checked'])) {
                    print '<td class="nocellnopadd">';
                    print $obj->town;
                    print '</td>';
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }
                // State / County / Departement
                if (!empty($arrayfields['state.nom']['checked'])) {
                    print "<td>" . $obj->state_name . "</td>\n";
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }
                // Country
                if (!empty($arrayfields['country.code_iso']['checked'])) {
                    $tmparray = getCountry($obj->country, 'all');
                    print '<td class="center tdoverflowmax100" title="' . dol_escape_htmltag($tmparray['label']) . '">';
                    print dol_escape_htmltag($tmparray['label']);
                    print '</td>';
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }
                // Phone pro
                if (!empty($arrayfields['d.phone']['checked'])) {
                    print '<td class="nocellnopadd">';
                    print $obj->phone;
                    print '</td>';
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }
                // Phone perso
                if (!empty($arrayfields['d.phone_perso']['checked'])) {
                    print '<td class="nocellnopadd">';
                    print $obj->phone_perso;
                    print '</td>';
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }
                // Phone mobile
                if (!empty($arrayfields['d.phone_mobile']['checked'])) {
                    print '<td class="nocellnopadd">';
                    print $obj->phone_mobile;
                    print '</td>';
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }
                // EMail
                if (!empty($arrayfields['d.email']['checked'])) {
                    print '<td class="tdoverflowmax150" title="' . dol_escape_htmltag($obj->email) . '">';
                    print dol_print_email($obj->email, 0, 0, 1, 64, 1, 1);
                    print "</td>\n";
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }
                // End of subscription date
                $datefin = $db->jdate($obj->datefin);
                if (!empty($arrayfields['d.datefin']['checked'])) {
                    print '<td class="nowraponall center">';
                    if ($datefin) {
                        print dol_print_date($datefin, 'day');
                        if ($memberstatic->hasDelay()) {
                            $textlate = ' (' . $langs->trans("DateReference") . ' > ' . $langs->trans("DateToday") . ' ' . (ceil($conf->adherent->subscription->warning_delay / 60 / 60 / 24) >= 0 ? '+' : '') . ceil($conf->adherent->subscription->warning_delay / 60 / 60 / 24) . ' ' . $langs->trans("days") . ')';
                            print " " . img_warning($langs->trans("SubscriptionLate") . $textlate);
                        }
                    } else {
                        if (!empty($obj->subscription)) {
                            print '<span class="opacitymedium">' . $langs->trans("SubscriptionNotReceived") . '</span>';
                            if ($obj->status > 0) {
                                print " " . img_warning();
                            }
                        } else {
                            print '&nbsp;';
                        }
                    }
                    print '</td>';
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }
                // Extra fields
                include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_print_fields.tpl.php';
                // Fields from hook
                $parameters = ['arrayfields' => $arrayfields, 'obj' => $obj, 'i' => $i, 'totalarray' => &$totalarray];
                $reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
                print $hookmanager->resPrint;
                // Date creation
                if (!empty($arrayfields['d.datec']['checked'])) {
                    print '<td class="nowrap center">';
                    print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
                    print '</td>';
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }
                // Birth
                if (!empty($arrayfields['d.birth']['checked'])) {
                    print '<td class="nowrap center">';
                    print dol_print_date($db->jdate($obj->birth), 'day', 'tzuser');
                    print '</td>';
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }
                // Date modification
                if (!empty($arrayfields['d.tms']['checked'])) {
                    print '<td class="nowrap center">';
                    print dol_print_date($db->jdate($obj->date_modification), 'dayhour', 'tzuser');
                    print '</td>';
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }
                // Import key
                if (!empty($arrayfields['d.import_key']['checked'])) {
                    print '<td class="tdoverflowmax100 center" title="' . dol_escape_htmltag($obj->import_key) . '">';
                    print dol_escape_htmltag($obj->import_key);
                    print "</td>\n";
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }
                // Status
                if (!empty($arrayfields['d.statut']['checked'])) {
                    print '<td class="nowrap center">';
                    print $memberstatic->LibStatut($obj->status, $obj->subscription, $datefin, 5);
                    print '</td>';
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }
                // Action column
                if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                    print '<td class="center">';
                    if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
                        $selected = 0;
                        if (in_array($obj->rowid, $arrayofselected)) {
                            $selected = 1;
                        }
                        print '<input id="cb' . $obj->rowid . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $obj->rowid . '"' . ($selected ? ' checked="checked"' : '') . '>';
                    }
                    print '</td>';
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }

                print '</tr>' . "\n";
            }
            $i++;
        }

// Show total line
        include DOL_DOCUMENT_ROOT . '/core/tpl/list_print_total.tpl.php';


// If no record found
        if ($num == 0) {
            $colspan = 1;
            foreach ($arrayfields as $key => $val) {
                if (!empty($val['checked'])) {
                    $colspan++;
                }
            }
            print '<tr><td colspan="' . $colspan . '"><span class="opacitymedium">' . $langs->trans("NoRecordFound") . '</span></td></tr>';
        }

        $db->free($resql);

        $parameters = ['arrayfields' => $arrayfields, 'sql' => $sql];
        $reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;

        print '</table>' . "\n";
        print '</div>' . "\n";

        print '</form>' . "\n";

        if (in_array('builddoc', array_keys($arrayofmassactions)) && ($nbtotalofrecords === '' || $nbtotalofrecords)) {
            $hidegeneratedfilelistifempty = 1;
            if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) {
                $hidegeneratedfilelistifempty = 0;
            }

            require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
            $formfile = new FormFile($db);

            // Show list of available documents
            $urlsource = $_SERVER['PHP_SELF'] . '?sortfield=' . $sortfield . '&sortorder=' . $sortorder;
            $urlsource .= str_replace('&amp;', '&', $param);

            $filedir = $diroutputmassaction;
            $genallowed = $permissiontoread;
            $delallowed = $permissiontoadd;

            print $formfile->showdocuments('massfilesarea_' . $object->module, '', $filedir, $urlsource, 0, $delallowed, '', 1, 1, 0, 48, 1, $param, $title, '', '', '', null, $hidegeneratedfilelistifempty);
        }

// End of page
        llxFooter();
        $db->close();
    }

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
         *	Actions
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

        $form = new Form($db);
        $formproduct = new FormProduct($db);

        $help_url = 'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros|DE:Modul_Mitglieder';

        llxHeader('', $langs->trans("MembersTypeSetup"), $help_url);

        $arrayofselected = is_array($toselect) ? $toselect : array();

// List of members type
        if (!$rowid && $action != 'create' && $action != 'edit') {
            //print dol_get_fiche_head('');

            $sql = "SELECT d.rowid, d.libelle as label, d.subscription, d.amount, d.caneditamount, d.vote, d.statut as status, d.morphy, d.duration";
            $sql .= " FROM " . MAIN_DB_PREFIX . "adherent_type as d";
            $sql .= " WHERE d.entity IN (" . getEntity('member_type') . ")";

            $result = $db->query($sql);
            if ($result) {
                $num = $db->num_rows($result);
                $nbtotalofrecords = $num;

                $i = 0;

                $param = '';
                if (!empty($mode)) {
                    $param .= '&mode' . urlencode($mode);
                }
                if (!empty($contextpage) && $contextpage != $_SERVER['PHP_SELF']) {
                    $param .= '&contextpage=' . $contextpage;
                }
                if ($limit > 0 && $limit != $conf->liste_limit) {
                    $param .= '&limit=' . $limit;
                }

                $newcardbutton = '';

                $newcardbutton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', $_SERVER['PHP_SELF'] . '?mode=common' . preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ((empty($mode) || $mode == 'common') ? 2 : 1), array('morecss' => 'reposition'));
                $newcardbutton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', $_SERVER['PHP_SELF'] . '?mode=kanban' . preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ($mode == 'kanban' ? 2 : 1), array('morecss' => 'reposition'));

                if ($user->hasRight('adherent', 'configurer')) {
                    $newcardbutton .= dolGetButtonTitleSeparator();
                    $newcardbutton .= dolGetButtonTitle($langs->trans('NewMemberType'), '', 'fa fa-plus-circle', DOL_URL_ROOT . '/adherents/type.php?action=create');
                }

                print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
                if ($optioncss != '') {
                    print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
                }
                print '<input type="hidden" name="token" value="' . newToken() . '">';
                print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
                print '<input type="hidden" name="action" value="list">';
                print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
                print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
                print '<input type="hidden" name="mode" value="' . $mode . '">';


                print_barre_liste($langs->trans("MembersTypes"), $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, $newcardbutton, '', $limit, 0, 0, 1);

                $moreforfilter = '';

                print '<div class="div-table-responsive">';
                print '<table class="tagtable liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">' . "\n";

                print '<tr class="liste_titre">';
                if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                    print '<th>&nbsp;</th>';
                }
                print '<th>' . $langs->trans("Ref") . '</th>';
                print '<th>' . $langs->trans("Label") . '</th>';
                print '<th class="center">' . $langs->trans("MembersNature") . '</th>';
                print '<th class="center">' . $langs->trans("MembershipDuration") . '</th>';
                print '<th class="center">' . $langs->trans("SubscriptionRequired") . '</th>';
                print '<th class="center">' . $langs->trans("Amount") . '</th>';
                print '<th class="center">' . $langs->trans("CanEditAmountShort") . '</th>';
                print '<th class="center">' . $langs->trans("VoteAllowed") . '</th>';
                print '<th class="center">' . $langs->trans("Status") . '</th>';
                if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                    print '<th>&nbsp;</th>';
                }
                print "</tr>\n";

                $membertype = new AdherentType($db);

                $i = 0;
                $savnbfield = 9;
                /*$savnbfield = $totalarray['nbfield'];
                $totalarray = array();
                $totalarray['nbfield'] = 0;*/

                $imaxinloop = ($limit ? min($num, $limit) : $num);
                while ($i < $imaxinloop) {
                    $objp = $db->fetch_object($result);

                    $membertype->id = $objp->rowid;
                    $membertype->ref = $objp->rowid;
                    $membertype->label = $objp->rowid;
                    $membertype->status = $objp->status;
                    $membertype->subscription = $objp->subscription;
                    $membertype->amount = $objp->amount;
                    $membertype->caneditamount = $objp->caneditamount;

                    if ($mode == 'kanban') {
                        if ($i == 0) {
                            print '<tr class="trkanban"><td colspan="' . $savnbfield . '">';
                            print '<div class="box-flex-container kanban">';
                        }
                        //output kanban
                        $membertype->label = $objp->label;
                        print $membertype->getKanbanView('', array('selected' => in_array($object->id, $arrayofselected)));
                        if ($i == ($imaxinloop - 1)) {
                            print '</div>';
                            print '</td></tr>';
                        }
                    } else {
                        print '<tr class="oddeven">';
                        if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                            if ($user->hasRight('adherent', 'configurer')) {
                                print '<td class="center"><a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=edit&rowid=' . $objp->rowid . '">' . img_edit() . '</a></td>';
                            }
                        }
                        print '<td class="nowraponall">';
                        print $membertype->getNomUrl(1);
                        //<a href="'.$_SERVER['PHP_SELF'].'?rowid='.$objp->rowid.'">'.img_object($langs->trans("ShowType"),'group').' '.$objp->rowid.'</a>
                        print '</td>';
                        print '<td>' . dol_escape_htmltag($objp->label) . '</td>';
                        print '<td class="center">';
                        if ($objp->morphy == 'phy') {
                            print $langs->trans("Physical");
                        } elseif ($objp->morphy == 'mor') {
                            print $langs->trans("Moral");
                        } else {
                            print $langs->trans("MorAndPhy");
                        }
                        print '</td>';
                        print '<td class="center nowrap">';
                        if ($objp->duration) {
                            $duration_value = intval($objp->duration);
                            if ($duration_value > 1) {
                                $dur = array("i" => $langs->trans("Minutes"), "h" => $langs->trans("Hours"), "d" => $langs->trans("Days"), "w" => $langs->trans("Weeks"), "m" => $langs->trans("Months"), "y" => $langs->trans("Years"));
                            } else {
                                $dur = array("i" => $langs->trans("Minute"), "h" => $langs->trans("Hour"), "d" => $langs->trans("Day"), "w" => $langs->trans("Week"), "m" => $langs->trans("Month"), "y" => $langs->trans("Year"));
                            }
                            $unit = preg_replace("/[^a-zA-Z]+/", "", $objp->duration);
                            print max(1, $duration_value) . ' ' . $dur[$unit];
                        }
                        print '</td>';
                        print '<td class="center">' . yn($objp->subscription) . '</td>';
                        print '<td class="center"><span class="amount">' . (is_null($objp->amount) || $objp->amount === '' ? '' : price($objp->amount)) . '</span></td>';
                        print '<td class="center">' . yn($objp->caneditamount) . '</td>';
                        print '<td class="center">' . yn($objp->vote) . '</td>';
                        print '<td class="center">' . $membertype->getLibStatut(5) . '</td>';
                        if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                            if ($user->hasRight('adherent', 'configurer')) {
                                print '<td class="right"><a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=edit&rowid=' . $objp->rowid . '">' . img_edit() . '</a></td>';
                            }
                        }
                        print "</tr>";
                    }
                    $i++;
                }

                // If no record found
                if ($num == 0) {
                    /*$colspan = 1;
                    foreach ($arrayfields as $key => $val) {
                        if (!empty($val['checked'])) {
                            $colspan++;
                        }
                    }*/
                    $colspan = 9;
                    print '<tr><td colspan="' . $colspan . '" class="opacitymedium">' . $langs->trans("NoRecordFound") . '</td></tr>';
                }

                print "</table>";
                print '</div>';

                print '</form>';
            } else {
                dol_print_error($db);
            }
        }

// Creation
        if ($action == 'create') {
            $object = new AdherentType($db);

            print load_fiche_titre($langs->trans("NewMemberType"), '', 'members');

            print '<form action="' . $_SERVER['PHP_SELF'] . '" method="POST">';
            print '<input type="hidden" name="token" value="' . newToken() . '">';
            print '<input type="hidden" name="action" value="add">';

            print dol_get_fiche_head('');

            print '<table class="border centpercent">';
            print '<tbody>';

            print '<tr><td class="titlefieldcreate fieldrequired">' . $langs->trans("Label") . '</td><td><input type="text" class="minwidth200" name="label" autofocus="autofocus"></td></tr>';

            print '<tr><td>' . $langs->trans("Status") . '</td><td>';
            print $form->selectarray('status', array('0' => $langs->trans('ActivityCeased'), '1' => $langs->trans('InActivity')), 1, 0, 0, 0, '', 0, 0, 0, '', 'minwidth100');
            print '</td></tr>';

            // Morphy
            $morphys = array();
            $morphys[""] = $langs->trans("MorAndPhy");
            $morphys["phy"] = $langs->trans("Physical");
            $morphys["mor"] = $langs->trans("Moral");
            print '<tr><td><span>' . $langs->trans("MembersNature") . '</span></td><td>';
            print $form->selectarray("morphy", $morphys, GETPOSTISSET("morphy") ? GETPOST("morphy", 'aZ09') : 'morphy');
            print "</td></tr>";

            print '<tr><td>' . $form->textwithpicto($langs->trans("SubscriptionRequired"), $langs->trans("SubscriptionRequiredDesc")) . '</td><td>';
            print $form->selectyesno("subscription", 1, 1);
            print '</td></tr>';

            print '<tr><td>' . $langs->trans("Amount") . '</td><td>';
            print '<input name="amount" size="5" value="' . (GETPOSTISSET('amount') ? GETPOST('amount') : price($amount)) . '">';
            print '</td></tr>';

            print '<tr><td>' . $form->textwithpicto($langs->trans("CanEditAmountShort"), $langs->transnoentities("CanEditAmount")) . '</td><td>';
            print $form->selectyesno("caneditamount", GETPOSTISSET('caneditamount') ? GETPOST('caneditamount') : 0, 1);
            print '</td></tr>';

            print '<tr><td>' . $langs->trans("VoteAllowed") . '</td><td>';
            print $form->selectyesno("vote", GETPOSTISSET("vote") ? GETPOST('vote', 'aZ09') : 1, 1);
            print '</td></tr>';

            print '<tr><td>' . $langs->trans("Duration") . '</td><td colspan="3">';
            print '<input name="duration_value" size="5" value="' . GETPOST('duraction_unit', 'aZ09') . '"> ';
            print $formproduct->selectMeasuringUnits("duration_unit", "time", GETPOSTISSET("duration_unit") ? GETPOST('duration_unit', 'aZ09') : 'y', 0, 1);
            print '</td></tr>';

            print '<tr><td class="tdtop">' . $langs->trans("Description") . '</td><td>';
            require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
            $doleditor = new DolEditor('comment', (GETPOSTISSET('comment') ? GETPOST('comment', 'restricthtml') : $object->note_public), '', 200, 'dolibarr_notes', '', false, true, isModEnabled('fckeditor'), 15, '90%');
            $doleditor->Create();

            print '<tr><td class="tdtop">' . $langs->trans("WelcomeEMail") . '</td><td>';
            require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
            $doleditor = new DolEditor('mail_valid', GETPOSTISSET('mail_valid') ? GETPOST('mail_valid') : $object->mail_valid, '', 250, 'dolibarr_notes', '', false, true, isModEnabled('fckeditor'), 15, '90%');
            $doleditor->Create();
            print '</td></tr>';

            // Other attributes
            include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';

            print '<tbody>';
            print "</table>\n";

            print dol_get_fiche_end();

            print $form->buttonsSaveCancel();

            print "</form>\n";
        }

// View
        if ($rowid > 0) {
            if ($action != 'edit') {
                $object = new AdherentType($db);
                $object->fetch($rowid);
                $object->fetch_optionals();

                /*
                 * Confirmation deletion
                 */
                if ($action == 'delete') {
                    print $form->formconfirm($_SERVER['PHP_SELF'] . "?rowid=" . $object->id, $langs->trans("DeleteAMemberType"), $langs->trans("ConfirmDeleteMemberType", $object->label), "confirm_delete", '', 0, 1);
                }

                $head = member_type_prepare_head($object);

                print dol_get_fiche_head($head, 'card', $langs->trans("MemberType"), -1, 'group');

                $linkback = '<a href="' . DOL_URL_ROOT . '/adherents/type.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

                dol_banner_tab($object, 'rowid', $linkback);

                print '<div class="fichecenter">';
                print '<div class="underbanner clearboth"></div>';

                print '<table class="tableforfield border centpercent">';

                // Morphy
                print '<tr><td>' . $langs->trans("MembersNature") . '</td><td class="valeur" >' . $object->getmorphylib($object->morphy) . '</td>';
                print '</tr>';

                print '<tr><td>' . $form->textwithpicto($langs->trans("SubscriptionRequired"), $langs->trans("SubscriptionRequiredDesc")) . '</td><td>';
                print yn($object->subscription);
                print '</tr>';

                // Amount
                print '<tr><td class="titlefield">' . $langs->trans("Amount") . '</td><td>';
                print((is_null($object->amount) || $object->amount === '') ? '' : '<span class="amount">' . price($object->amount) . '</span>');
                print '</tr>';

                print '<tr><td>' . $form->textwithpicto($langs->trans("CanEditAmountShort"), $langs->transnoentities("CanEditAmount")) . '</td><td>';
                print yn($object->caneditamount);
                print '</td></tr>';

                print '<tr><td>' . $langs->trans("VoteAllowed") . '</td><td>';
                print yn($object->vote);
                print '</tr>';

                print '<tr><td class="titlefield">' . $langs->trans("Duration") . '</td><td colspan="2">' . $object->duration_value . '&nbsp;';
                if ($object->duration_value > 1) {
                    $dur = array("i" => $langs->trans("Minutes"), "h" => $langs->trans("Hours"), "d" => $langs->trans("Days"), "w" => $langs->trans("Weeks"), "m" => $langs->trans("Months"), "y" => $langs->trans("Years"));
                } elseif ($object->duration_value > 0) {
                    $dur = array("i" => $langs->trans("Minute"), "h" => $langs->trans("Hour"), "d" => $langs->trans("Day"), "w" => $langs->trans("Week"), "m" => $langs->trans("Month"), "y" => $langs->trans("Year"));
                }
                print(!empty($object->duration_unit) && isset($dur[$object->duration_unit]) ? $langs->trans($dur[$object->duration_unit]) : '') . "&nbsp;";
                print '</td></tr>';

                print '<tr><td class="tdtop">' . $langs->trans("Description") . '</td><td>';
                print dol_string_onlythesehtmltags(dol_htmlentitiesbr($object->note_private));
                print "</td></tr>";

                print '<tr><td class="tdtop">' . $langs->trans("WelcomeEMail") . '</td><td>';
                print dol_string_onlythesehtmltags(dol_htmlentitiesbr($object->mail_valid));
                print "</td></tr>";

                // Other attributes
                include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

                print '</table>';
                print '</div>';

                print dol_get_fiche_end();


                /*
                 * Buttons
                 */

                print '<div class="tabsAction">';

                // Edit
                if ($user->hasRight('adherent', 'configurer')) {
                    print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=edit&token=' . newToken() . '&rowid=' . $object->id . '">' . $langs->trans("Modify") . '</a></div>';
                }

                // Add
                if ($object->morphy == 'phy') {
                    $morphy = 'phy';
                } elseif ($object->morphy == 'mor') {
                    $morphy = 'mor';
                } else {
                    $morphy = '';
                }

                if ($user->hasRight('adherent', 'configurer') && !empty($object->status)) {
                    print '<div class="inline-block divButAction"><a class="butAction" href="card.php?action=create&token=' . newToken() . '&typeid=' . $object->id . ($morphy ? '&morphy=' . urlencode($morphy) : '') . '&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?rowid=' . $object->id) . '">' . $langs->trans("AddMember") . '</a></div>';
                } else {
                    print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("NoAddMember")) . '">' . $langs->trans("AddMember") . '</a></div>';
                }

                // Delete
                if ($user->hasRight('adherent', 'configurer')) {
                    print '<div class="inline-block divButAction"><a class="butActionDelete" href="' . $_SERVER['PHP_SELF'] . '?action=delete&token=' . newToken() . '&rowid=' . $object->id . '">' . $langs->trans("DeleteType") . '</a></div>';
                }

                print "</div>";


                // Show list of members (nearly same code than in page list.php)

                $membertypestatic = new AdherentType($db);

                $now = dol_now();

                $sql = "SELECT d.rowid, d.ref, d.entity, d.login, d.firstname, d.lastname, d.societe as company, d.fk_soc,";
                $sql .= " d.datefin,";
                $sql .= " d.email, d.photo, d.fk_adherent_type as type_id, d.morphy, d.statut as status,";
                $sql .= " t.libelle as type, t.subscription, t.amount";

                $sqlfields = $sql; // $sql fields to remove for count total

                $sql .= " FROM " . MAIN_DB_PREFIX . "adherent as d, " . MAIN_DB_PREFIX . "adherent_type as t";
                $sql .= " WHERE d.fk_adherent_type = t.rowid ";
                $sql .= " AND d.entity IN (" . getEntity('adherent') . ")";
                $sql .= " AND t.rowid = " . ((int) $object->id);
                if ($sall) {
                    $sql .= natural_search(array("d.firstname", "d.lastname", "d.societe", "d.email", "d.login", "d.address", "d.town", "d.note_public", "d.note_private"), $sall);
                }
                if ($status != '') {
                    $sql .= natural_search('d.statut', $status, 2);
                }
                if ($action == 'search') {
                    if (GETPOST('search', 'alpha')) {
                        $sql .= natural_search(array("d.firstname", "d.lastname"), GETPOST('search', 'alpha'));
                    }
                }
                if (!empty($search_ref)) {
                    $sql .= natural_search("d.ref", $search_ref);
                }
                if (!empty($search_lastname)) {
                    $sql .= natural_search(array("d.firstname", "d.lastname"), $search_lastname);
                }
                if (!empty($search_login)) {
                    $sql .= natural_search("d.login", $search_login);
                }
                if (!empty($search_email)) {
                    $sql .= natural_search("d.email", $search_email);
                }
                if ($filter == 'uptodate') {
                    $sql .= " AND (datefin >= '" . $db->idate($now) . "') OR t.subscription = 0)";
                }
                if ($filter == 'outofdate') {
                    $sql .= " AND (datefin < '" . $db->idate($now) . "' AND t.subscription = 1)";
                }

                // Count total nb of records
                $nbtotalofrecords = '';
                if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
                    /* The fast and low memory method to get and count full list converts the sql into a sql count */
                    $sqlforcount = preg_replace('/^' . preg_quote($sqlfields, '/') . '/', 'SELECT COUNT(*) as nbtotalofrecords', $sql);
                    $sqlforcount = preg_replace('/GROUP BY .*$/', '', $sqlforcount);
                    $resql = $db->query($sqlforcount);
                    if ($resql) {
                        $objforcount = $db->fetch_object($resql);
                        $nbtotalofrecords = $objforcount->nbtotalofrecords;
                    } else {
                        dol_print_error($db);
                    }

                    if (($page * $limit) > $nbtotalofrecords) { // if total resultset is smaller than the paging size (filtering), goto and load page 0
                        $page = 0;
                        $offset = 0;
                    }
                    $db->free($resql);
                }

                // Complete request and execute it with limit
                $sql .= $db->order($sortfield, $sortorder);
                if ($limit) {
                    $sql .= $db->plimit($limit + 1, $offset);
                }

                $resql = $db->query($sql);
                if ($resql) {
                    $num = $db->num_rows($resql);
                    $i = 0;

                    $titre = $langs->trans("MembersList");
                    if ($status != '') {
                        if ($status == '-1,1') {
                            $titre = $langs->trans("MembersListQualified");
                        } elseif ($status == '-1') {
                            $titre = $langs->trans("MembersListToValid");
                        } elseif ($status == '1' && !$filter) {
                            $titre = $langs->trans("MembersListValid");
                        } elseif ($status == '1' && $filter == 'uptodate') {
                            $titre = $langs->trans("MembersListUpToDate");
                        } elseif ($status == '1' && $filter == 'outofdate') {
                            $titre = $langs->trans("MembersListNotUpToDate");
                        } elseif ($status == '0') {
                            $titre = $langs->trans("MembersListResiliated");
                        } elseif ($status == '-2') {
                            $titre = $langs->trans("MembersListExcluded");
                        }
                    } elseif ($action == 'search') {
                        $titre = $langs->trans("MembersListQualified");
                    }

                    if ($type > 0) {
                        $membertype = new AdherentType($db);
                        $result = $membertype->fetch($type);
                        $titre .= " (" . $membertype->label . ")";
                    }

                    $param = "&rowid=" . urlencode((string) ($object->id));
                    if (!empty($mode)) {
                        $param .= '&mode=' . urlencode($mode);
                    }
                    if (!empty($contextpage) && $contextpage != $_SERVER['PHP_SELF']) {
                        $param .= '&contextpage=' . urlencode($contextpage);
                    }
                    if ($limit > 0 && $limit != $conf->liste_limit) {
                        $param .= '&limit=' . ((int) $limit);
                    }
                    if (!empty($status)) {
                        $param .= "&status=" . urlencode($status);
                    }
                    if (!empty($search_ref)) {
                        $param .= "&search_ref=" . urlencode($search_ref);
                    }
                    if (!empty($search_lastname)) {
                        $param .= "&search_lastname=" . urlencode($search_lastname);
                    }
                    if (!empty($search_firstname)) {
                        $param .= "&search_firstname=" . urlencode($search_firstname);
                    }
                    if (!empty($search_login)) {
                        $param .= "&search_login=" . urlencode($search_login);
                    }
                    if (!empty($search_email)) {
                        $param .= "&search_email=" . urlencode($search_email);
                    }
                    if (!empty($filter)) {
                        $param .= "&filter=" . urlencode($filter);
                    }

                    if ($sall) {
                        print $langs->trans("Filter") . " (" . $langs->trans("Lastname") . ", " . $langs->trans("Firstname") . ", " . $langs->trans("EMail") . ", " . $langs->trans("Address") . " " . $langs->trans("or") . " " . $langs->trans("Town") . "): " . $sall;
                    }

                    print '<form method="POST" id="searchFormList" action="' . $_SERVER['PHP_SELF'] . '" name="formfilter" autocomplete="off">';
                    print '<input type="hidden" name="token" value="' . newToken() . '">';
                    print '<input class="flat" type="hidden" name="rowid" value="' . $object->id . '"></td>';

                    print_barre_liste('', $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'generic', 0, '', '', $limit);

                    $moreforfilter = '';

                    print '<div class="div-table-responsive">';
                    print '<table class="tagtable liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">' . "\n";

                    // Fields title search
                    print '<tr class="liste_titre_filter">';

                    if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                        print '<td class="liste_titre center maxwidthsearch">';
                        $searchpicto = $form->showFilterButtons('left');
                        print $searchpicto;
                        print '</td>';
                    }

                    print '<td class="liste_titre left">';
                    print '<input class="flat maxwidth100" type="text" name="search_ref" value="' . dol_escape_htmltag($search_ref) . '"></td>';

                    print '<td class="liste_titre left">';
                    print '<input class="flat maxwidth100" type="text" name="search_lastname" value="' . dol_escape_htmltag($search_lastname) . '"></td>';

                    print '<td class="liste_titre left">';
                    print '<input class="flat maxwidth100" type="text" name="search_login" value="' . dol_escape_htmltag($search_login) . '"></td>';

                    print '<td class="liste_titre">&nbsp;</td>';

                    print '<td class="liste_titre left">';
                    print '<input class="flat maxwidth100" type="text" name="search_email" value="' . dol_escape_htmltag($search_email) . '"></td>';

                    print '<td class="liste_titre">&nbsp;</td>';

                    print '<td class="liste_titre">&nbsp;</td>';

                    if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                        print '<td class="liste_titre center nowraponall">';
                        print '<input type="image" class="liste_titre" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/search.png" name="button_search" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
                        print '&nbsp; ';
                        print '<input type="image" class="liste_titre" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/searchclear.png" name="button_removefilter" value="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '" title="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '">';
                        print '</td>';
                    }

                    print "</tr>\n";

                    print '<tr class="liste_titre">';
                    if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                        print_liste_field_titre("Action", $_SERVER['PHP_SELF'], "", $param, "", 'width="60" align="center"', $sortfield, $sortorder);
                    }
                    print_liste_field_titre("Ref", $_SERVER['PHP_SELF'], "d.ref", $param, "", "", $sortfield, $sortorder);
                    print_liste_field_titre("NameSlashCompany", $_SERVER['PHP_SELF'], "d.lastname", $param, "", "", $sortfield, $sortorder);
                    print_liste_field_titre("Login", $_SERVER['PHP_SELF'], "d.login", $param, "", "", $sortfield, $sortorder);
                    print_liste_field_titre("MemberNature", $_SERVER['PHP_SELF'], "d.morphy", $param, "", "", $sortfield, $sortorder);
                    print_liste_field_titre("EMail", $_SERVER['PHP_SELF'], "d.email", $param, "", "", $sortfield, $sortorder);
                    print_liste_field_titre("Status", $_SERVER['PHP_SELF'], "d.statut,d.datefin", $param, "", "", $sortfield, $sortorder);
                    print_liste_field_titre("EndSubscription", $_SERVER['PHP_SELF'], "d.datefin", $param, "", 'align="center"', $sortfield, $sortorder);
                    if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                        print_liste_field_titre("Action", $_SERVER['PHP_SELF'], "", $param, "", 'width="60" align="center"', $sortfield, $sortorder);
                    }
                    print "</tr>\n";

                    $adh = new Adherent($db);

                    $imaxinloop = ($limit ? min($num, $limit) : $num);
                    while ($i < $imaxinloop) {
                        $objp = $db->fetch_object($resql);

                        $datefin = $db->jdate($objp->datefin);

                        $adh->id = $objp->rowid;
                        $adh->ref = $objp->ref;
                        $adh->login = $objp->login;
                        $adh->lastname = $objp->lastname;
                        $adh->firstname = $objp->firstname;
                        $adh->datefin = $datefin;
                        $adh->need_subscription = $objp->subscription;
                        $adh->statut = $objp->status;
                        $adh->email = $objp->email;
                        $adh->photo = $objp->photo;

                        print '<tr class="oddeven">';

                        // Actions
                        if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                            print '<td class="center">';
                            if ($user->hasRight('adherent', 'creer')) {
                                print '<a class="editfielda marginleftonly" href="card.php?rowid=' . $objp->rowid . '&action=edit&token=' . newToken() . '&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?rowid=' . $object->id) . '">' . img_edit() . '</a>';
                            }
                            if ($user->hasRight('adherent', 'supprimer')) {
                                print '<a class="marginleftonly" href="card.php?rowid=' . $objp->rowid . '&action=resiliate&token=' . newToken() . '">' . img_picto($langs->trans("Resiliate"), 'disable.png') . '</a>';
                            }
                            print "</td>";
                        }

                        // Ref
                        print "<td>";
                        print $adh->getNomUrl(-1, 0, 'card', 'ref', '', -1, 0, 1);
                        print "</td>\n";

                        // Lastname
                        if ($objp->company != '') {
                            print '<td><a href="card.php?rowid=' . $objp->rowid . '">' . img_object($langs->trans("ShowMember"), "user", 'class="paddingright"') . $adh->getFullName($langs, 0, -1, 20) . ' / ' . dol_trunc($objp->company, 12) . '</a></td>' . "\n";
                        } else {
                            print '<td><a href="card.php?rowid=' . $objp->rowid . '">' . img_object($langs->trans("ShowMember"), "user", 'class="paddingright"') . $adh->getFullName($langs, 0, -1, 32) . '</a></td>' . "\n";
                        }

                        // Login
                        print "<td>" . dol_escape_htmltag($objp->login) . "</td>\n";

                        // Type
                        /*print '<td class="nowrap">';
                        $membertypestatic->id=$objp->type_id;
                        $membertypestatic->label=$objp->type;
                        print $membertypestatic->getNomUrl(1,12);
                        print '</td>';
                        */

                        // Moral/Physique
                        print "<td>" . $adh->getmorphylib($objp->morphy, 1) . "</td>\n";

                        // EMail
                        print "<td>" . dol_print_email($objp->email, 0, 0, 1) . "</td>\n";

                        // Status
                        print '<td class="nowrap">';
                        print $adh->getLibStatut(2);
                        print "</td>";

                        // Date end subscription
                        if ($datefin) {
                            print '<td class="nowrap center">';
                            if ($datefin < dol_now() && $objp->status > 0) {
                                print dol_print_date($datefin, 'day') . " " . img_warning($langs->trans("SubscriptionLate"));
                            } else {
                                print dol_print_date($datefin, 'day');
                            }
                            print '</td>';
                        } else {
                            print '<td class="nowrap center">';
                            if (!empty($objp->subscription)) {
                                print '<span class="opacitymedium">' . $langs->trans("SubscriptionNotReceived") . '</span>';
                                if ($objp->status > 0) {
                                    print " " . img_warning();
                                }
                            } else {
                                print '&nbsp;';
                            }
                            print '</td>';
                        }

                        // Actions
                        if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                            print '<td class="center">';
                            if ($user->hasRight('adherent', 'creer')) {
                                print '<a class="editfielda marginleftonly" href="card.php?rowid=' . $objp->rowid . '&action=edit&token=' . newToken() . '&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?rowid=' . $object->id) . '">' . img_edit() . '</a>';
                            }
                            if ($user->hasRight('adherent', 'supprimer')) {
                                print '<a class="marginleftonly" href="card.php?rowid=' . $objp->rowid . '&action=resiliate&token=' . newToken() . '">' . img_picto($langs->trans("Resiliate"), 'disable.png') . '</a>';
                            }
                            print "</td>";
                        }
                        print "</tr>\n";
                        $i++;
                    }

                    if ($i == 0) {
                        print '<tr><td colspan="9"><span class="opacitymedium">' . $langs->trans("None") . '</span></td></tr>';
                    }

                    print "</table>\n";
                    print '</div>';
                    print '</form>';
                } else {
                    dol_print_error($db);
                }
            }

            /* ************************************************************************** */
            /*                                                                            */
            /* Edition mode                                                               */
            /*                                                                            */
            /* ************************************************************************** */

            if ($action == 'edit') {
                $object = new AdherentType($db);
                $object->fetch($rowid);
                $object->fetch_optionals();

                $head = member_type_prepare_head($object);

                print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '?rowid=' . $object->id . '">';
                print '<input type="hidden" name="token" value="' . newToken() . '">';
                print '<input type="hidden" name="rowid" value="' . $object->id . '">';
                print '<input type="hidden" name="action" value="update">';

                print dol_get_fiche_head($head, 'card', $langs->trans("MemberType"), 0, 'group');

                print '<table class="border centpercent">';

                print '<tr><td class="titlefield">' . $langs->trans("Ref") . '</td><td>' . $object->id . '</td></tr>';

                print '<tr><td class="fieldrequired">' . $langs->trans("Label") . '</td><td><input type="text" class="minwidth300" name="label" value="' . dol_escape_htmltag($object->label) . '"></td></tr>';

                print '<tr><td>' . $langs->trans("Status") . '</td><td>';
                print $form->selectarray('status', array('0' => $langs->trans('ActivityCeased'), '1' => $langs->trans('InActivity')), $object->status, 0, 0, 0, '', 0, 0, 0, '', 'minwidth100');
                print '</td></tr>';

                // Morphy
                $morphys[""] = $langs->trans("MorAndPhy");
                $morphys["phy"] = $langs->trans("Physical");
                $morphys["mor"] = $langs->trans("Moral");
                print '<tr><td><span>' . $langs->trans("MembersNature") . '</span></td><td>';
                print $form->selectarray("morphy", $morphys, GETPOSTISSET("morphy") ? GETPOST("morphy", 'aZ09') : $object->morphy);
                print "</td></tr>";

                print '<tr><td>' . $langs->trans("SubscriptionRequired") . '</td><td>';
                print $form->selectyesno("subscription", $object->subscription, 1);
                print '</td></tr>';

                print '<tr><td>' . $langs->trans("Amount") . '</td><td>';
                print '<input name="amount" size="5" value="';
                print((is_null($object->amount) || $object->amount === '') ? '' : price($object->amount));
                print '">';
                print '</td></tr>';

                print '<tr><td>' . $form->textwithpicto($langs->trans("CanEditAmountShort"), $langs->transnoentities("CanEditAmountDetail")) . '</td><td>';
                print $form->selectyesno("caneditamount", $object->caneditamount, 1);
                print '</td></tr>';

                print '<tr><td>' . $langs->trans("VoteAllowed") . '</td><td>';
                print $form->selectyesno("vote", $object->vote, 1);
                print '</td></tr>';

                print '<tr><td>' . $langs->trans("Duration") . '</td><td colspan="3">';
                print '<input name="duration_value" size="5" value="' . $object->duration_value . '"> ';
                print $formproduct->selectMeasuringUnits("duration_unit", "time", ($object->duration_unit === '' ? 'y' : $object->duration_unit), 0, 1);
                print '</td></tr>';

                print '<tr><td class="tdtop">' . $langs->trans("Description") . '</td><td>';
                require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
                $doleditor = new DolEditor('comment', $object->note_public, '', 220, 'dolibarr_notes', '', false, true, isModEnabled('fckeditor'), 15, '90%');
                $doleditor->Create();
                print "</td></tr>";

                print '<tr><td class="tdtop">' . $langs->trans("WelcomeEMail") . '</td><td>';
                $doleditor = new DolEditor('mail_valid', $object->mail_valid, '', 280, 'dolibarr_notes', '', false, true, isModEnabled('fckeditor'), 15, '90%');
                $doleditor->Create();
                print "</td></tr>";

                // Other attributes
                include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_edit.tpl.php';

                print '</table>';

                print dol_get_fiche_end();

                print $form->buttonsSaveCancel();

                print "</form>";
            }
        }

// End of page
        llxFooter();
        $db->close();
    }
}