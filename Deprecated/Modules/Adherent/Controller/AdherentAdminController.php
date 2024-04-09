<?php

/* Copyright (C) 2001-2003  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2003       Jean-Louis Bergamo      <jlb@j1b.org>
 * Copyright (C) 2004-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004       Sebastien Di Cintio     <sdicintio@ressource-toi.org>
 * Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2012  Juanjo Menent		    <jmenent@2byte.es>
 * Copyright (C) 2012       J. Fernando Lagrange    <fernando@demo-tic.org>
 * Copyright (C) 2013		Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2020-2021  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2023		Waël Almoman		    <info@almoman.com>
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
require_once DOL_DOCUMENT_ROOT . '/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/member.lib.php';

use DoliCore\Base\DolibarrController;

class AdherentAdminController extends DolibarrController
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
        global $mysoc;

        $this->member();
    }

    /**
     *      \file       htdocs/adherents/admin/member.php
     *      \ingroup    member
     *      \brief      Page to setup the module Foundation
     */
    public function member()
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
        $langs->loadLangs(array("admin", "members"));

        if (!$user->admin) {
            accessforbidden();
        }


        $choices = array('yesno', 'texte', 'chaine');

        $value = GETPOST('value', 'alpha');
        $label = GETPOST('label', 'alpha');
        $scandir = GETPOST('scandir', 'alpha');
        $type = 'member';

        $action = GETPOST('action', 'aZ09');
        $modulepart = GETPOST('modulepart', 'aZ09');


        /*
         * Actions
         */

        include DOL_DOCUMENT_ROOT . '/core/actions_setmoduleoptions.inc.php';

        if ($action == 'set_default') {
            $ret = addDocumentModel($value, $type, $label, $scandir);
            $res = true;
        } elseif ($action == 'del_default') {
            $ret = delDocumentModel($value, $type);
            if ($ret > 0) {
                if (getDolGlobalString('MEMBER_ADDON_PDF_ODT') == "$value") {
                    dolibarr_del_const($db, 'MEMBER_ADDON_PDF_ODT', $conf->entity);
                }
            }
            $res = true;
        } elseif ($action == 'setdoc') {
            // Set default model
            if (dolibarr_set_const($db, "MEMBER_ADDON_PDF_ODT", $value, 'chaine', 0, '', $conf->entity)) {
                // The constant that was read ahead of the new set
                // we therefore go through a variable to have a consistent display
                $conf->global->MEMBER_ADDON_PDF_ODT = $value;
            }

            // We activate the model
            $ret = delDocumentModel($value, $type);
            if ($ret > 0) {
                $ret = addDocumentModel($value, $type, $label, $scandir);
            }
            $res = true;
        } elseif (preg_match('/set_([a-z0-9_\-]+)/i', $action, $reg)) {
            $code = $reg[1];
            if (dolibarr_set_const($db, $code, 1, 'chaine', 0, '', $conf->entity) > 0) {
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                dol_print_error($db);
            }
        } elseif (preg_match('/del_([a-z0-9_\-]+)/i', $action, $reg)) {
            $code = $reg[1];
            if (dolibarr_del_const($db, $code, $conf->entity) > 0) {
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                dol_print_error($db);
            }
        } elseif ($action == 'updatemainoptions') {
            $db->begin();
            $res1 = $res2 = $res3 = $res4 = $res5 = $res6 = $res7 = $res8 = $res9 = 0;
            $res1 = dolibarr_set_const($db, 'ADHERENT_LOGIN_NOT_REQUIRED', GETPOST('ADHERENT_LOGIN_NOT_REQUIRED', 'alpha') ? 0 : 1, 'chaine', 0, '', $conf->entity);
            $res2 = dolibarr_set_const($db, 'ADHERENT_MAIL_REQUIRED', GETPOST('ADHERENT_MAIL_REQUIRED', 'alpha'), 'chaine', 0, '', $conf->entity);
            $res3 = dolibarr_set_const($db, 'ADHERENT_DEFAULT_SENDINFOBYMAIL', GETPOST('ADHERENT_DEFAULT_SENDINFOBYMAIL', 'alpha'), 'chaine', 0, '', $conf->entity);
            $res3 = dolibarr_set_const($db, 'ADHERENT_CREATE_EXTERNAL_USER_LOGIN', GETPOST('ADHERENT_CREATE_EXTERNAL_USER_LOGIN', 'alpha'), 'chaine', 0, '', $conf->entity);
            $res4 = dolibarr_set_const($db, 'ADHERENT_BANK_USE', GETPOST('ADHERENT_BANK_USE', 'alpha'), 'chaine', 0, '', $conf->entity);
            $res7 = dolibarr_set_const($db, 'MEMBER_PUBLIC_ENABLED', GETPOST('MEMBER_PUBLIC_ENABLED', 'alpha'), 'chaine', 0, '', $conf->entity);
            $res8 = dolibarr_set_const($db, 'MEMBER_SUBSCRIPTION_START_FIRST_DAY_OF', GETPOST('MEMBER_SUBSCRIPTION_START_FIRST_DAY_OF', 'alpha'), 'chaine', 0, '', $conf->entity);
            $res9 = dolibarr_set_const($db, 'MEMBER_SUBSCRIPTION_START_AFTER', GETPOST('MEMBER_SUBSCRIPTION_START_AFTER', 'alpha'), 'chaine', 0, '', $conf->entity);
            // Use vat for invoice creation
            if (isModEnabled('invoice')) {
                $res4 = dolibarr_set_const($db, 'ADHERENT_VAT_FOR_SUBSCRIPTIONS', GETPOST('ADHERENT_VAT_FOR_SUBSCRIPTIONS', 'alpha'), 'chaine', 0, '', $conf->entity);
                $res5 = dolibarr_set_const($db, 'ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS', GETPOST('ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS', 'alpha'), 'chaine', 0, '', $conf->entity);
                if (isModEnabled("product") || isModEnabled("service")) {
                    $res6 = dolibarr_set_const($db, 'ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS', GETPOST('ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS', 'alpha'), 'chaine', 0, '', $conf->entity);
                }
            }
            if ($res1 < 0 || $res2 < 0 || $res3 < 0 || $res4 < 0 || $res5 < 0 || $res6 < 0 || $res7 < 0) {
                setEventMessages('ErrorFailedToSaveData', null, 'errors');
                $db->rollback();
            } else {
                setEventMessages('RecordModifiedSuccessfully', null, 'mesgs');
                $db->commit();
            }
        } elseif ($action == 'updatememberscards') {
            $db->begin();
            $res1 = $res2 = $res3 = $res4 = 0;
            $res1 = dolibarr_set_const($db, 'ADHERENT_CARD_TYPE', GETPOST('ADHERENT_CARD_TYPE'), 'chaine', 0, '', $conf->entity);
            $res2 = dolibarr_set_const($db, 'ADHERENT_CARD_HEADER_TEXT', GETPOST('ADHERENT_CARD_HEADER_TEXT', 'alpha'), 'chaine', 0, '', $conf->entity);
            $res3 = dolibarr_set_const($db, 'ADHERENT_CARD_TEXT', GETPOST('ADHERENT_CARD_TEXT', 'alpha'), 'chaine', 0, '', $conf->entity);
            $res3 = dolibarr_set_const($db, 'ADHERENT_CARD_TEXT_RIGHT', GETPOST('ADHERENT_CARD_TEXT_RIGHT', 'alpha'), 'chaine', 0, '', $conf->entity);
            $res4 = dolibarr_set_const($db, 'ADHERENT_CARD_FOOTER_TEXT', GETPOST('ADHERENT_CARD_FOOTER_TEXT', 'alpha'), 'chaine', 0, '', $conf->entity);

            if ($res1 < 0 || $res2 < 0 || $res3 < 0 || $res4 < 0) {
                setEventMessages('ErrorFailedToSaveDate', null, 'errors');
                $db->rollback();
            } else {
                setEventMessages('RecordModifiedSuccessfully', null, 'mesgs');
                $db->commit();
            }
        } elseif ($action == 'updatememberstickets') {
            $db->begin();
            $res1 = $res2 = 0;
            $res1 = dolibarr_set_const($db, 'ADHERENT_ETIQUETTE_TYPE', GETPOST('ADHERENT_ETIQUETTE_TYPE'), 'chaine', 0, '', $conf->entity);
            $res2 = dolibarr_set_const($db, 'ADHERENT_ETIQUETTE_TEXT', GETPOST('ADHERENT_ETIQUETTE_TEXT', 'alpha'), 'chaine', 0, '', $conf->entity);

            if ($res1 < 0 || $res2 < 0) {
                setEventMessages('ErrorFailedToSaveDate', null, 'errors');
                $db->rollback();
            } else {
                setEventMessages('RecordModifiedSuccessfully', null, 'mesgs');
                $db->commit();
            }
        } elseif ($action == 'setcodemember') {
            $result = dolibarr_set_const($db, "MEMBER_CODEMEMBER_ADDON", $value, 'chaine', 0, '', $conf->entity);
            if ($result <= 0) {
                dol_print_error($db);
            }
        } elseif ($action == 'update' || $action == 'add') {
            // Action to update or add a constant
            $constname = GETPOST('constname', 'alpha');
            $constvalue = (GETPOST('constvalue_' . $constname) ? GETPOST('constvalue_' . $constname) : GETPOST('constvalue'));


            if (($constname == 'ADHERENT_CARD_TYPE' || $constname == 'ADHERENT_ETIQUETTE_TYPE' || $constname == 'ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS') && $constvalue == -1) {
                $constvalue = '';
            }
            if ($constname == 'ADHERENT_LOGIN_NOT_REQUIRED') { // Invert choice
                if ($constvalue) {
                    $constvalue = 0;
                } else {
                    $constvalue = 1;
                }
            }

            $consttype = GETPOST('consttype', 'alpha');
            $constnote = GETPOST('constnote');
            $res = dolibarr_set_const($db, $constname, $constvalue, $choices[$consttype], 0, $constnote, $conf->entity);

            if (!($res > 0)) {
                $error++;
            }

            if (!$error) {
                setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
            } else {
                setEventMessages($langs->trans("Error"), null, 'errors');
            }
        }

// Action to enable of a submodule of the adherent module
        if ($action == 'set') {
            $result = dolibarr_set_const($db, GETPOST('name', 'alpha'), GETPOST('value'), '', 0, '', $conf->entity);
            if ($result < 0) {
                print $db->error();
            }
        }

// Action to disable a submodule of the adherent module
        if ($action == 'unset') {
            $result = dolibarr_del_const($db, GETPOST('name', 'alpha'), $conf->entity);
            if ($result < 0) {
                print $db->error();
            }
        }

        /*
         * View
         */

        require_once realpath(BASE_PATH . '/../Deprecated/Modules/Adherent/Views/admin_member.php');

        $db->close();
    }

    /**
     *      \file       htdocs/adherents/admin/member_emails.php
     *      \ingroup    member
     *      \brief      Page to setup the module Foundation
     */
    public function member_emails()
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
        $langs->loadLangs(array("admin", "members"));

        if (!$user->admin) {
            accessforbidden();
        }


        $oldtypetonewone = array('texte' => 'text', 'chaine' => 'string'); // old type to new ones

        $action = GETPOST('action', 'aZ09');

        $error = 0;

        $helptext = '*' . $langs->trans("FollowingConstantsWillBeSubstituted") . '<br>';
        $helptext .= '__DOL_MAIN_URL_ROOT__, __ID__, __FIRSTNAME__, __LASTNAME__, __FULLNAME__, __LOGIN__, __PASSWORD__, ';
        $helptext .= '__COMPANY__, __ADDRESS__, __ZIP__, __TOWN__, __COUNTRY__, __EMAIL__, __BIRTH__, __PHOTO__, __TYPE__, ';
//$helptext.='__YEAR__, __MONTH__, __DAY__';    // Not supported

// Editing global variables not related to a specific theme
        $constantes = array(
            'MEMBER_REMINDER_EMAIL' => array('type' => 'yesno', 'label' => $langs->trans('MEMBER_REMINDER_EMAIL', $langs->transnoentities("Module2300Name"))),
            'ADHERENT_EMAIL_TEMPLATE_REMIND_EXPIRATION'     => array('type' => 'emailtemplate:member','label' => ''),
            'ADHERENT_EMAIL_TEMPLATE_AUTOREGISTER'          => array('type' => 'emailtemplate:member','label' => ''),
            'ADHERENT_EMAIL_TEMPLATE_MEMBER_VALIDATION'     => array('type' => 'emailtemplate:member','label' => ''),
            'ADHERENT_EMAIL_TEMPLATE_SUBSCRIPTION'          => array('type' => 'emailtemplate:member','label' => ''),
            'ADHERENT_EMAIL_TEMPLATE_CANCELATION'           => array('type' => 'emailtemplate:member','label' => ''),
            'ADHERENT_EMAIL_TEMPLATE_EXCLUSION'             => array('type' => 'emailtemplate:member','label' => ''),
            'ADHERENT_MAIL_FROM'                            => array('type' => 'string','label' => ''),
            'ADHERENT_CC_MAIL_FROM'                         => array('type' => 'string','label' => ''),
            'ADHERENT_AUTOREGISTER_NOTIF_MAIL_SUBJECT'      => array('type' => 'string','label' => ''),
            'ADHERENT_AUTOREGISTER_NOTIF_MAIL'              => array('type' => 'html', 'tooltip' => $helptext,'label' => '')
        );



        /*
         * Actions
         */

//
        if ($action == 'updateall') {
            $db->begin();

            $res = 0;
            foreach ($constantes as $constname => $value) {
                $constvalue = (GETPOSTISSET('constvalue_' . $constname) ? GETPOST('constvalue_' . $constname, 'alphanohtml') : GETPOST('constvalue'));
                $consttype = (GETPOSTISSET('consttype_' . $constname) ? GETPOST('consttype_' . $constname, 'alphanohtml') : GETPOST('consttype'));
                $constnote = (GETPOSTISSET('constnote_' . $constname) ? GETPOST('constnote_' . $constname, 'restricthtml') : GETPOST('constnote'));

                $typetouse = empty($oldtypetonewone[$consttype]) ? $consttype : $oldtypetonewone[$consttype];
                $constvalue = preg_replace('/:member$/', '', $constvalue);

                $res = dolibarr_set_const($db, $constname, $constvalue, $consttype, 0, $constnote, $conf->entity);
                if ($res <= 0) {
                    $error++;
                    $action = 'list';
                }
            }

            if ($error > 0) {
                setEventMessages('ErrorFailedToSaveDate', null, 'errors');
                $db->rollback();
            } else {
                setEventMessages('RecordModifiedSuccessfully', null, 'mesgs');
                $db->commit();
            }
        }

// Action to update or add a constant
        if ($action == 'update' || $action == 'add') {
            $constlineid = GETPOSTINT('rowid');
            $constname = GETPOST('constname', 'alpha');

            $constvalue = (GETPOSTISSET('constvalue_' . $constname) ? GETPOST('constvalue_' . $constname, 'alphanohtml') : GETPOST('constvalue'));
            $consttype = (GETPOSTISSET('consttype_' . $constname) ? GETPOST('consttype_' . $constname, 'alphanohtml') : GETPOST('consttype'));
            $constnote = (GETPOSTISSET('constnote_' . $constname) ? GETPOST('constnote_' . $constname, 'restricthtml') : GETPOST('constnote'));

            $typetouse = empty($oldtypetonewone[$consttype]) ? $consttype : $oldtypetonewone[$consttype];
            $constvalue = preg_replace('/:member$/', '', $constvalue);

            $res = dolibarr_set_const($db, $constname, $constvalue, $typetouse, 0, $constnote, $conf->entity);

            if (!($res > 0)) {
                $error++;
            }

            if (!$error) {
                setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
            } else {
                setEventMessages($langs->trans("Error"), null, 'errors');
            }
        }

        /*
         * View
         */

        require_once realpath(BASE_PATH . '/../Deprecated/Modules/Adherent/Views/admin_member_emails.php');

        $db->close();
    }

    /**
     *      \file       htdocs/adherents/admin/member_extrafields.php
     *      \ingroup    member
     *      \brief      Page to setup extra fields of members
     */
    public function member_extrafields()
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
        $langs->loadLangs(array("admin", "members"));

        $extrafields = new ExtraFields($db);
        $form = new Form($db);

// List of supported format
        $tmptype2label = ExtraFields::$type2label;
        $type2label = array('');
        foreach ($tmptype2label as $key => $val) {
            $type2label[$key] = $langs->transnoentitiesnoconv($val);
        }

        $action = GETPOST('action', 'aZ09');
        $attrname = GETPOST('attrname', 'alpha');
        $elementtype = 'adherent'; //Must be the $table_element of the class that manage extrafield

        if (!$user->admin) {
            accessforbidden();
        }


        /*
         * Actions
         */

        require DOL_DOCUMENT_ROOT . '/core/actions_extrafields.inc.php';

        /*
         * View
         */

        require_once realpath(BASE_PATH . '/../Deprecated/Modules/Adherent/Views/admin_member_extrafields.php');

        $db->close();
    }

    /**
     *      \file       htdocs/adherents/admin/member_type_extrafields.php
     *      \ingroup    member
     *      \brief      Page to setup extra fields of members
     */
    public function member_type_extrafields()
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
        $langs->loadLangs(array("admin", "members"));

        $extrafields = new ExtraFields($db);
        $form = new Form($db);

// List of supported format
        $tmptype2label = ExtraFields::$type2label;
        $type2label = array('');
        foreach ($tmptype2label as $key => $val) {
            $type2label[$key] = $langs->transnoentitiesnoconv($val);
        }

        $action = GETPOST('action', 'aZ09');
        $attrname = GETPOST('attrname', 'alpha');
        $elementtype = 'adherent_type'; //Must be the $table_element of the class that manage extrafield

        if (!$user->admin) {
            accessforbidden();
        }


        /*
         * Actions
         */

        require DOL_DOCUMENT_ROOT . '/core/actions_extrafields.inc.php';

        /*
         * View
         */

        require_once realpath(BASE_PATH . '/../Deprecated/Modules/Adherent/Views/admin_member_type_extrafields.php');

        $db->close();
    }

    /**
     *      \file       htdocs/adherents/admin/website.php
     *      \ingroup    member
     *      \brief      File of main public page for member module
     */
    public function website()
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
        $langs->loadLangs(array("admin", "members"));

        $action = GETPOST('action', 'aZ09');

        if (!$user->admin) {
            accessforbidden();
        }

        $error = 0;


        /*
         * Actions
         */

        if ($action == 'setMEMBER_ENABLE_PUBLIC') {
            if (GETPOST('value')) {
                dolibarr_set_const($db, 'MEMBER_ENABLE_PUBLIC', 1, 'chaine', 0, '', $conf->entity);
            } else {
                dolibarr_set_const($db, 'MEMBER_ENABLE_PUBLIC', 0, 'chaine', 0, '', $conf->entity);
            }
        }

        if ($action == 'update') {
            $public = GETPOST('MEMBER_ENABLE_PUBLIC');
            $amount = price2num(GETPOST('MEMBER_NEWFORM_AMOUNT'), 'MT', 2);
            $minamount = GETPOST('MEMBER_MIN_AMOUNT');
            $publiccounters = GETPOST('MEMBER_COUNTERS_ARE_PUBLIC');
            $showtable = GETPOST('MEMBER_SHOW_TABLE');
            $showvoteallowed = GETPOST('MEMBER_SHOW_VOTE_ALLOWED');
            $payonline = GETPOST('MEMBER_NEWFORM_PAYONLINE');
            $forcetype = GETPOSTINT('MEMBER_NEWFORM_FORCETYPE');
            $forcemorphy = GETPOST('MEMBER_NEWFORM_FORCEMORPHY', 'aZ09');

            $res = dolibarr_set_const($db, "MEMBER_ENABLE_PUBLIC", $public, 'chaine', 0, '', $conf->entity);
            $res = dolibarr_set_const($db, "MEMBER_NEWFORM_AMOUNT", $amount, 'chaine', 0, '', $conf->entity);
            $res = dolibarr_set_const($db, "MEMBER_MIN_AMOUNT", $minamount, 'chaine', 0, '', $conf->entity);
            $res = dolibarr_set_const($db, "MEMBER_COUNTERS_ARE_PUBLIC", $publiccounters, 'chaine', 0, '', $conf->entity);
            $res = dolibarr_set_const($db, "MEMBER_SKIP_TABLE", !$showtable, 'chaine', 0, '', $conf->entity); // Logic is reversed for retrocompatibility: "skip -> show"
            $res = dolibarr_set_const($db, "MEMBER_HIDE_VOTE_ALLOWED", !$showvoteallowed, 'chaine', 0, '', $conf->entity); // Logic is reversed for retrocompatibility: "hide -> show"
            $res = dolibarr_set_const($db, "MEMBER_NEWFORM_PAYONLINE", $payonline, 'chaine', 0, '', $conf->entity);
            if ($forcetype < 0) {
                $res = dolibarr_del_const($db, "MEMBER_NEWFORM_FORCETYPE", $conf->entity);
            } else {
                $res = dolibarr_set_const($db, "MEMBER_NEWFORM_FORCETYPE", $forcetype, 'chaine', 0, '', $conf->entity);
            }
            if ($forcemorphy == '-1') {
                $res = dolibarr_del_const($db, "MEMBER_NEWFORM_FORCEMORPHY", $conf->entity);
            } else {
                $res = dolibarr_set_const($db, "MEMBER_NEWFORM_FORCEMORPHY", $forcemorphy, 'chaine', 0, '', $conf->entity);
            }

            if (!($res > 0)) {
                $error++;
            }

            if (!$error) {
                setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
            } else {
                setEventMessages($langs->trans("Error"), null, 'errors');
            }
        }


        /*
         * View
         */

        require_once realpath(BASE_PATH . '/../Deprecated/Modules/Adherent/Views/admin_website.php');

        $db->close();
    }

}