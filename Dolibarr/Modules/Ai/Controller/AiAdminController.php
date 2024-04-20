<?php

/* Copyright (C) 2004-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2022       Alice Adminson          <aadminson@example.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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

namespace DoliModules\Ai\Controller;

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
require_once BASE_PATH . '/ai/lib/ai.lib.php';
require_once BASE_PATH . '/core/lib/admin.lib.php';

use DoliCore\Base\DolibarrController;
use DoliCore\Form\Form;
use FormSetup;

class AiAdminController extends DolibarrController
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
    }

    /**
     * \file    ai/admin/setup.php
     * \ingroup ai
     * \brief   Ai setup page.
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

        $langs->loadLangs(["admin"]);

// Parameters
        $action = GETPOST('action', 'aZ09');
        $backtopage = GETPOST('backtopage', 'alpha');
        $modulepart = GETPOST('modulepart', 'aZ09');    // Used by actions_setmoduleoptions.inc.php

        if (empty($action)) {
            $action = 'edit';
        }

        $value = GETPOST('value', 'alpha');
        $label = GETPOST('label', 'alpha');
        $scandir = GETPOST('scan_dir', 'alpha');
        $type = 'myobject';

        $error = 0;
        $setupnotempty = 0;

// Access control
        if (!$user->admin) {
            accessforbidden();
        }


// Set this to 1 to use the factory to manage constants. Warning, the generated module will be compatible with version v15+ only
        $useFormSetup = 1;

        if (!class_exists('FormSetup')) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/html.formsetup.class.php';
        }

        $formSetup = new FormSetup($db);

// List all available IA
        $arrayofia = ['chatgpt'];

        foreach ($arrayofia as $ia) {
            // Setup conf AI_PUBLIC_INTERFACE_TOPIC
            /*$item = $formSetup->newItem('AI_API_'.strtoupper($ia).'_ENDPOINT');   // Name of constant must end with _KEY so it is encrypted when saved into database.
            $item->defaultFieldValue = '';
            $item->cssClass = 'minwidth500';*/

            $item = $formSetup->newItem('AI_API_' . strtoupper($ia) . '_KEY');  // Name of constant must end with _KEY so it is encrypted when saved into database.
            $item->defaultFieldValue = '';
            $item->cssClass = 'minwidth500';
        }

        $setupnotempty = +count($formSetup->items);


        $dirmodels = array_merge(['/'], (array) $conf->modules_parts['models']);


        /*
         * Actions
         */

        include DOL_DOCUMENT_ROOT . '/core/actions_setmoduleoptions.inc.php';

        $action = 'edit';

        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Ai/Views/setup.php');

        $db->close();
        return true;
    }

    /**
     * \file    ai/admin/custom_prompt.php
     * \ingroup ai
     * \brief   Ai other custom page.
     */
    public function custom_prompt()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

        $langs->loadLangs(["admin"]);

// Parameters
        $action = GETPOST('action', 'aZ09');
        $backtopage = GETPOST('backtopage', 'alpha');
        $modulepart = GETPOST('modulepart', 'aZ09');    // Used by actions_setmoduleoptions.inc.php

        if (empty($action)) {
            $action = 'edit';
        }

        $error = 0;
        $setupnotempty = 0;

// Access control
        if (!$user->admin) {
            accessforbidden();
        }


// Set this to 1 to use the factory to manage constants. Warning, the generated module will be compatible with version v15+ only
        $useFormSetup = 1;

        if (!class_exists('FormSetup')) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/html.formsetup.class.php';
        }

        $formSetup = new FormSetup($db);

// Setup conf AI_PROMPT
        $item = $formSetup->newItem('AI_CONFIGURATIONS_PROMPT');
        $item->defaultFieldValue = '';

        $setupnotempty += count($formSetup->items);

        $dirmodels = array_merge(['/'], (array) $conf->modules_parts['models']);

// List of AI features
        $arrayofaifeatures = [
            'textgeneration' => ['label' => 'TextGeneration', 'picto' => '', 'status' => 'development'],
            'imagegeneration' => ['label' => 'ImageGeneration', 'picto' => '', 'status' => 'notused'],
            'videogeneration' => ['label' => 'VideoGeneration', 'picto' => '', 'status' => 'notused'],
            'transcription' => ['label' => 'Transcription', 'picto' => '', 'status' => 'notused'],
            'translation' => ['label' => 'Translation', 'picto' => '', 'status' => 'notused'],
            'audiotext' => ['label' => 'AudioText', 'picto' => '', 'status' => 'notused'],
        ];


        /*
         * Actions
         */

        $functioncode = GETPOST('functioncode', 'alpha');
        $pre_prompt = GETPOST('prePrompt');
        $post_prompt = GETPOST('postPrompt');
// get all configs in const AI

        $currentConfigurationsJson = getDolGlobalString('AI_CONFIGURATIONS_PROMPT');
        $currentConfigurations = json_decode($currentConfigurationsJson, true);

        if ($action == 'update' && GETPOST('cancel')) {
            $action = 'edit';
        }
        if ($action == 'update' && !GETPOST('cancel')) {
            $error = 0;
            if (empty($functioncode)) {
                $error++;
                setEventMessages($langs->trans('ErrorInputRequired'), null, 'errors');
            }
            if (!is_array($currentConfigurations)) {
                $currentConfigurations = [];
            }

            if (empty($functioncode) || (empty($pre_prompt) && empty($post_prompt))) {
                if (isset($currentConfigurations[$functioncode])) {
                    unset($currentConfigurations[$functioncode]);
                }
            } else {
                $currentConfigurations[$functioncode] = [
                    'prePrompt' => $pre_prompt,
                    'postPrompt' => $post_prompt,
                ];
            }

            $newConfigurationsJson = json_encode($currentConfigurations, JSON_UNESCAPED_UNICODE);
            $result = dolibarr_set_const($db, 'AI_CONFIGURATIONS_PROMPT', $newConfigurationsJson, 'chaine', 0, '', $conf->entity);
            if (!$error) {
                if ($result) {
                    header("Location: " . $_SERVER['PHP_SELF']);
                    setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
                    exit;
                } else {
                    setEventMessages($langs->trans("ErrorUpdating"), null, 'errors');
                }
            }

            $action = 'edit';
        }

        if ($action == 'updatePrompts') {
            $key = GETPOST('key', 'alpha');

            $currentConfigurations[$key] = [
                'prePrompt' => $pre_prompt,
                'postPrompt' => $post_prompt,
            ];

            $newConfigurationsJson = json_encode($currentConfigurations, JSON_UNESCAPED_UNICODE);
            $result = dolibarr_set_const($db, 'AI_CONFIGURATIONS_PROMPT', $newConfigurationsJson, 'chaine', 0, '', $conf->entity);
            if (!$error) {
                $action = '';
                if ($result) {
                    header("Location: " . $_SERVER['PHP_SELF']);
                    setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
                    exit;
                } else {
                    setEventMessages($langs->trans("ErrorUpdating"), null, 'errors');
                }
            }
        }

        if ($action == 'confirm_deleteproperty' && GETPOST('confirm') == 'yes') {
            $key = GETPOST('key', 'alpha');

            if (isset($currentConfigurations[$key])) {
                unset($currentConfigurations[$key]);

                $newConfigurationsJson = json_encode($currentConfigurations, JSON_UNESCAPED_UNICODE);
                $res = dolibarr_set_const($db, 'AI_CONFIGURATIONS_PROMPT', $newConfigurationsJson, 'chaine', 0, '', $conf->entity);
                if ($res) {
                    header("Location: " . $_SERVER['PHP_SELF']);
                    setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
                    exit;
                } else {
                    setEventMessages($langs->trans("NoRecordDeleted"), null, 'errors');
                }
            }
        }

        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Ai/Views/custom_prompt.php');

        $db->close();
        return true;
    }
}
