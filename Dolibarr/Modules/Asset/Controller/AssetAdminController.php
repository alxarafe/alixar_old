<?php

/* Copyright (C) 2001-2002  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2003		Jean-Louis Bergamo		<jlb@j1b.org>
 * Copyright (C) 2004-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2014		Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2015		Jean-François Ferry		<jfefe@aternatik.fr>
 * Copyright (C) 2018       Alexandre Spangaro      <aspangaro@open-dsi.fr>
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

namespace DoliModules\Asset\Controller;

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
require_once BASE_PATH . '/../Dolibarr/Lib/Asset.php';
require_once BASE_PATH . '/../Dolibarr/Lib/Admin.php';

use DoliCore\Base\Controller\DolibarrController;
use DoliCore\Lib\ExtraFields;
use DoliCore\Form\Form;

class AssetAdminController extends DolibarrController
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

        $this->setup();

        return true;
    }

    /**
     *  \file       htdocs/asset/admin/asset_extrafields.php
     *  \ingroup    asset
     *  \brief      Page to setup extra fields of asset
     */
    public function asset_extrafields()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(array("assets", "admin", "companies"));

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
        $elementtype = 'asset'; //Must be the $table_element of the class that manage extrafield

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
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Asset/Views/admin_asset_extrafields.php');

        $db->close();
        return true;
    }

    /**
     *  \file       htdocs/asset/admin/assetmodel_extrafields.php
     *  \ingroup    asset
     *  \brief      Page to setup extra fields of asset model
     */
    public function assetmodel_extrafields()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(array("assets", "admin", "companies"));

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
        $elementtype = 'asset_model'; //Must be the $table_element of the class that manage extrafield

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
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Asset/Views/admin_assetmodel_extrafields.php');

        $db->close();
        return true;
    }

    /**
     * \file    htdocs/asset/admin/setup.php
     * \ingroup asset
     * \brief   Asset setup page.
     */
    public function setup()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

        global $langs, $user;

// Load translation files required by the page
        $langs->loadLangs(array("admin", "assets"));

// Access control
        if (!$user->admin) {
            accessforbidden();
        }

// Parameters
        $action = GETPOST('action', 'aZ09');
        $backtopage = GETPOST('backtopage', 'alpha');

        $value = GETPOST('value', 'alpha');
        $label = GETPOST('label', 'alpha');
        $scandir = GETPOST('scan_dir', 'alpha');
        $type = 'asset';

        $arrayofparameters = array(
            'ASSET_ACCOUNTANCY_CATEGORY' => array('type' => 'accountancy_category', 'enabled' => 1),
            'ASSET_DEPRECIATION_DURATION_PER_YEAR' => array('type' => 'string', 'css' => 'minwidth200', 'enabled' => 1),
            //'ASSET_MYPARAM2'=>array('type'=>'textarea','enabled'=>1),
            //'ASSET_MYPARAM3'=>array('type'=>'category:'.Categorie::TYPE_CUSTOMER, 'enabled'=>1),
            //'ASSET_MYPARAM4'=>array('type'=>'emailtemplate:thirdparty', 'enabled'=>1),
            //'ASSET_MYPARAM5'=>array('type'=>'yesno', 'enabled'=>1),
            //'ASSET_MYPARAM5'=>array('type'=>'thirdparty_type', 'enabled'=>1),
            //'ASSET_MYPARAM6'=>array('type'=>'securekey', 'enabled'=>1),
            //'ASSET_MYPARAM7'=>array('type'=>'product', 'enabled'=>1),
        );

        $error = 0;
        $setupnotempty = 0;

        $dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

        $moduledir = 'asset';
        $myTmpObjects = array();
        $myTmpObjects['asset'] = array('label' => 'Asset', 'includerefgeneration' => 1, 'includedocgeneration' => 0, 'class' => 'Asset');

        $tmpobjectkey = GETPOST('object', 'aZ09');
        if ($tmpobjectkey && !array_key_exists($tmpobjectkey, $myTmpObjects)) {
            accessforbidden('Bad value for object. Hack attempt ?');
        }


        /*
         * Actions
         */

        include DOL_DOCUMENT_ROOT . '/core/actions_setmoduleoptions.inc.php';

        if ($action == 'updateMask') {
            $maskconst = GETPOST('maskconst', 'alpha');
            $mask = GETPOST('mask', 'alpha');

            if ($maskconst && preg_match('/_MASK$/', $maskconst)) {
                $res = dolibarr_set_const($db, $maskconst, $mask, 'chaine', 0, '', $conf->entity);
                if (!($res > 0)) {
                    $error++;
                }
            }

            if (!$error) {
                setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
            } else {
                setEventMessages($langs->trans("Error"), null, 'errors');
            }
        } elseif ($action == 'specimen' && $tmpobjectkey) {
            $modele = GETPOST('module', 'alpha');

            $className = $myTmpObjects[$tmpobjectkey]['class'];
            $tmpobject = new $className($db);
            $tmpobject->initAsSpecimen();

            // Search template files
            $file = '';
            $classname = '';
            $dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
            foreach ($dirmodels as $reldir) {
                $file = dol_buildpath($reldir . "core/modules/asset/doc/pdf_" . $modele . "_" . strtolower($tmpobjectkey) . ".modules.php", 0);
                if (file_exists($file)) {
                    $classname = "pdf_" . $modele;
                    break;
                }
            }

            if ($classname !== '') {
                require_once $file;

                $module = new $classname($db);

                if ($module->write_file($tmpobject, $langs) > 0) {
                    header("Location: " . DOL_URL_ROOT . "/document.php?modulepart=" . strtolower($tmpobjectkey) . "&file=SPECIMEN.pdf");
                    return;
                } else {
                    setEventMessages($module->error, null, 'errors');
                    dol_syslog($module->error, LOG_ERR);
                }
            } else {
                setEventMessages($langs->trans("ErrorModuleNotFound"), null, 'errors');
                dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
            }
        } elseif ($action == 'setmod') {
            // TODO Check if numbering module chosen can be activated by calling method canBeActivated
            if (!empty($tmpobjectkey)) {
                $constforval = 'ASSET_' . strtoupper($tmpobjectkey) . "_ADDON";
                dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity);
            }
        } elseif ($action == 'set') {
            // Activate a model
            $ret = addDocumentModel($value, $type, $label, $scandir);
        } elseif ($action == 'del') {
            $ret = delDocumentModel($value, $type);
            if ($ret > 0) {
                if (!empty($tmpobjectkey)) {
                    $constforval = 'ASSET_' . strtoupper($tmpobjectkey) . '_ADDON_PDF';
                    if (getDolGlobalString($constforval) == "$value") {
                        dolibarr_del_const($db, $constforval, $conf->entity);
                    }
                }
            }
        } elseif ($action == 'setdoc') {
            // Set or unset default model
            if (!empty($tmpobjectkey)) {
                $constforval = 'ASSET_' . strtoupper($tmpobjectkey) . '_ADDON_PDF';
                if (dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity)) {
                    // The constant that was read before the new set
                    // We therefore requires a variable to have a coherent view
                    $conf->global->$constforval = $value;
                }

                // We disable/enable the document template (into llx_document_model table)
                $ret = delDocumentModel($value, $type);
                if ($ret > 0) {
                    $ret = addDocumentModel($value, $type, $label, $scandir);
                }
            }
        } elseif ($action == 'unsetdoc') {
            if (!empty($tmpobjectkey)) {
                $constforval = 'ASSET_' . strtoupper($tmpobjectkey) . '_ADDON_PDF';
                dolibarr_del_const($db, $constforval, $conf->entity);
            }
        }

        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Asset/Views/admin_setup.php');

        $db->close();
        return true;
    }
}
