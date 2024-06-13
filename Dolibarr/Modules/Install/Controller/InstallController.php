<?php

/* Copyright (C) 2004-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004       Eric Seigne             <eric.seigne@ryxeo.com>
 * Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2004       Sebastien DiCintio      <sdicintio@ressource-toi.org>
 * Copyright (C) 2005       Marc Barilley / Ocebo   <marc@ocebo.com>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2013-2014  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2014       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2015       Cedric GROSS            <c.gross@kreiz-it.fr>
 * Copyright (C) 2015-2016  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2024		MDW                     <mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Rafael San José         <rsanjose@alxarafe.com>
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

namespace DoliModules\Install\Controller;

use DoliCore\Base\Config;
use DoliCore\Base\DolibarrViewController;
use DoliCore\Form\FormAdmin;
use DoliModules\Install\Lib\Check;
use DoliModules\Install\Lib\Status;
use PDO;
use stdClass;

require_once BASE_PATH . '/../Dolibarr/Lib/Admin.php';
require_once BASE_PATH . '/install/inc.php';

class InstallController extends DolibarrViewController
{
    /**
     * Set to true if we can continue with the installation
     *
     * @var bool
     */
    public bool $allow_install;

    /**
     * True if we want to force the use of https (recommended).
     *
     * @var bool
     */
    public bool $force_https;

    /**
     * It contains the html code that shows a select with the languages.
     *
     * @var string
     */
    public $selectLanguages;

    /**
     * It contains the html code that shows a select with the themes.
     *
     * @var string
     */
    public $selectThemes;

    /**
     * Contains the view subtitle
     *
     * @var string
     */
    public $subtitle;

    /**
     * Indicates whether to show the 'next' button.
     *
     * @var bool
     */
    public $nextButton;

    /**
     * Contains the JS code to be executed when the 'next' button is pressed
     *
     * @var string
     */
    public $nextButtonJs;

    /**
     * Multipurpose variable to send information to the view.
     *
     * @var stdClass
     */
    public $vars;

    /**
     * Code that is executed before the action is executed.
     *
     * @return bool
     */
    public function beforeAction(): bool
    {
        dump($this->action);
        $this->vars = new stdClass();

        $https = $this->config->main->url ?? 'https';
        $this->force_https = (substr($https, 4, 1) === 's');

        return parent::beforeAction();
    }

    /**
     * Code that runs when "Refresh" button is pressed to set the
     * language and theme.
     *
     * @return bool
     */
    public function doRefresh(): bool
    {
        $this->config->main->language = getIfIsset('language', $this->config->main->language);
        $this->config->main->theme = getIfIsset('theme', $this->config->main->theme);

        Config::setMainConfig([
            'language' => $this->config->main->language,
            'theme' => $this->config->main->theme,
        ]);
        Config::saveConfig();

        return $this->doIndex();
    }

    /**
     * Perform a needs check for the application to determine if it meets all the
     * essential requirements.
     *
     * @return bool
     */
    public function doIndex(): bool
    {
        if (!isset($this->config->main->language)) {
            $this->config->main->language = 'auto';
        }

        Config::setMainConfig([
            'language' => getIfIsset('language', $this->config->main->language),
            'theme' => getIfIsset('theme', $this->config->main->theme ?? 'eldy'),
        ]);

        $form = new FormAdmin(null);
        $this->selectLanguages = $form->select_language($this->config->main->language, 'language', 1, 0, 0, 1);
        $this->selectThemes = $form->select_theme($this->config->main->theme);

        $this->langs->setDefaultLang($this->config->main->language);
        $this->langs->loadLangs(['main', 'admin', 'install']);

        $this->template = 'install/checked';

        $checks = Check::all();

        $value = $this->checkConfFile();
        if ($value['status'] !== Status::OK) {
            $checks[] = $value;
        }

        $conffile = Config::getDolibarrConfigFilename();

        if (!file_exists($conffile)) {
            $text = $this->langs->trans('YouMustCreateWithPermission', $conffile);
            $text .= '<br><br>';
            $text .= '<span class="opacitymedium">' . $this->langs->trans("CorrectProblemAndReloadPage", $_SERVER['PHP_SELF'] . '?testget=ok') . '</span>';

            $checks[] = [
                'status' => Status::FAIL,
                'text' => $text,
            ];

            return static::setIcons($checks);
        }

        $value = $this->checkIfWritable();
        if ($value['status'] !== Status::OK) {
            $checks[] = $value;

            return static::setIcons($checks);
        }

        /**
         * TODO: It is necessary to review what the next method does.
         */
        $value = $this->next();

        return static::setIcons($checks);
    }

    /**
     * Ensures that the configuration file exists and can be read.
     *
     * @return array
     */
    private function checkConfFile(): array
    {
        $config_filename = Config::getDolibarrConfigFilename();

        clearstatcache();
        if (is_readable($config_filename) && filesize($config_filename) > 8) {
            $this->syslog("check: conf file '" . $config_filename . "' already defined");
            return ['status' => Status::OK];
        }

        // If not, we create it
        $this->syslog("check: we try to create conf file '" . $config_filename . "'");

        // First we try by copying example
        if (@copy($config_filename . ".example", $config_filename)) {
            // Success
            $this->syslog("check: successfully copied file " . $config_filename . ".example into " . $config_filename);
            return ['status' => Status::OK];
        }

        // If failed, we try to create an empty file
        $this->syslog("check: failed to copy file " . $config_filename . ".example into " . $config_filename . ". We try to create it.", LOG_WARNING);

        $fp = @fopen($config_filename, "w");
        if ($fp) {
            @fwrite($fp, '<?php');
            @fwrite($fp, "\n");
            if (fclose($fp)) {
                return ['status' => Status::OK];
            }
        }

        $this->syslog("check: failed to create a new file " . $config_filename . " into current dir " . getcwd() . ". Please check permissions.", LOG_ERR);
        return [
            'status' => Status::FAIL,
            'text' => $this->langs->trans('ConfFileDoesNotExistsAndCouldNotBeCreated', 'conf.php')
        ];
    }

    /**
     * Returns an array with the checks carried out, indicating the
     * name of the action, if there is an error and the icon to display.
     *
     * @param array $checks
     * @return bool
     */
    private function setIcons(array $checks): bool
    {
        $ok = true;
        $this->vars->checks = [];
        foreach ($checks as $check) {
            if (!isset($check['text'])) {
                continue;
            }
            $value = [];
            $value['text'] = $check['text'];
            switch ($check['status']) {
                case Status::OK:
                    $value['ok'] = true;
                    $value['icon'] = 'tick';
                    break;
                case Status::WARNING:
                    $value['ok'] = true;
                    $value['icon'] = 'warning';
                    break;
                case Status::FAIL:
                    $value['ok'] = false;
                    $value['icon'] = 'error';
                    $ok = false;
            }
            $this->vars->checks[] = $value;
        }

        if (!$ok) {
            $this->vars->checks[] = [
                'ok' => false,
                'icon' => 'error',
                'text' => $this->langs->trans('ErrorGoBackAndCorrectParameters'),
            ];
        }

        return $ok;
    }

    /**
     * Checks if the configuration file can be edited.
     *
     * @return array
     */
    private function checkIfWritable()
    {
        $config_filename = Config::getDolibarrConfigFilename();

        if (is_dir($config_filename)) {
            return [
                'status' => Status::FAIL,
                'text' => $this->langs->trans('ConfFileMustBeAFileNotADir', $config_filename),
            ];
        }

        $this->allow_install = is_writable($config_filename);
        if (!$this->allow_install) {
            return [
                'status' => Status::FAIL,
                'text' => $this->langs->trans('ConfFileIsNotWritable', $config_filename),
            ];
        }

        return [
            'status' => Status::OK,
        ];
    }

    /**
     * Fill in the information about the options available to install and/or update.
     * TODO: This method needs major refactoring
     *
     * @return void
     * @throws \Exception
     */
    private function next()
    {
        $configFilename = Config::getDolibarrConfigFilename();
        $conf = Config::getConf();
        $config = Config::getConfig($conf);

        $ok = false;
        if (!empty($config->main_db_type) && !empty($config->main_document_root)) {
            $this->errorBadMainDocumentRoot = '';
            if ($config->main_document_root !== BASE_PATH) {
                $this->errorBadMainDocumentRoot = "A $configFilename file exists with a dolibarr_main_document_root to $config->main_document_root that seems wrong. Try to fix or remove the $configFilename file.";
                dol_syslog($this->errorBadMainDocumentRoot, LOG_WARNING);
            } else {
                // If password is encoded, we decode it
                // TODO: Pending
                if (preg_match('/crypted:/i', $config->main_db_pass) || !empty($dolibarr_main_db_encrypted_pass)) {
                    require_once $this->dolibarr_main_document_root . '/core/lib/security.lib.php';
                    if (preg_match('/crypted:/i', $config->main_db_pass)) {
                        $dolibarr_main_db_encrypted_pass = preg_replace('/crypted:/i', '', $config->main_db_pass); // We need to set this as it is used to know the password was initially encrypted
                        $config->main_db_pass = dol_decode($dolibarr_main_db_encrypted_pass);
                    } else {
                        $config->main_db_pass = dol_decode($dolibarr_main_db_encrypted_pass);
                    }
                }

                // $conf already created in inc.php
                $this->conf->db->type = $config->main_db_type;
                $this->conf->db->host = $config->main_db_host;
                $this->conf->db->port = $config->main_db_port;
                $this->conf->db->name = $config->main_db_name;
                $this->conf->db->user = $config->main_db_user;
                $this->conf->db->pass = $config->main_db_pass;
                $db = getDoliDBInstance($this->conf->db->type, $this->conf->db->host, $this->conf->db->user, $this->conf->db->pass, $this->conf->db->name, (int)$this->conf->db->port);
                if ($db->connected && $db->database_selected) {
                    $ok = true;
                }
            }
        }

        $this->vars->availableChoices = [];
        $this->vars->notAvailableChoices = [];

        // If database access is available, we set more variables
        // TODO: Pending
        if ($ok) {
            if (empty($dolibarr_main_db_encryption)) {
                $dolibarr_main_db_encryption = 0;
            }
            $this->conf->db->dolibarr_main_db_encryption = $dolibarr_main_db_encryption;
            if (empty($dolibarr_main_db_cryptkey)) {
                $dolibarr_main_db_cryptkey = '';
            }
            $this->conf->db->dolibarr_main_db_cryptkey = $dolibarr_main_db_cryptkey;

            $this->conf->setValues($db);
            // Reset forced setup after the setValues
            if (defined('SYSLOG_FILE')) {
                $this->conf->global->SYSLOG_FILE = constant('SYSLOG_FILE');
            }
            $this->conf->global->MAIN_ENABLE_LOG_TO_HTML = 1;

            // Current version is $this->conf->global->MAIN_VERSION_LAST_UPGRADE
            // Version to install is DOL_VERSION
            $dolibarrlastupgradeversionarray = preg_split('/[\.-]/', isset($this->conf->global->MAIN_VERSION_LAST_UPGRADE) ? $this->conf->global->MAIN_VERSION_LAST_UPGRADE : (isset($this->conf->global->MAIN_VERSION_LAST_INSTALL) ? $this->conf->global->MAIN_VERSION_LAST_INSTALL : ''));
            $dolibarrversiontoinstallarray = versiondolibarrarray();
        }

        $this->vars->printVersion = getDolGlobalString('MAIN_VERSION_LAST_UPGRADE') || getDolGlobalString('MAIN_VERSION_LAST_INSTALL');

        $foundrecommandedchoice = 0;

        if (empty($dolibarr_main_db_host)) {    // This means install process was not run
            $foundrecommandedchoice = 1; // To show only once
        }

        /*
        $button = $this->allow_install
            ? '<input class="button" type="submit" name="action" value="' . $this->langs->trans("Start") . '">'
            : ($foundrecommandedchoice ? '<span class="warning">' : '') . $this->langs->trans("InstallNotAllowed") . ($foundrecommandedchoice ? '</span>' : '');
        */

        // TODO: We have to see how we can use the action, and that the text is displayed in the correct language
        $button = $this->allow_install
            ? '<input class="button" type="submit" name="action" value="start">' . $this->langs->trans("Start")
            : ($foundrecommandedchoice ? '<span class="warning">' : '') . $this->langs->trans("InstallNotAllowed") . ($foundrecommandedchoice ? '</span>' : '');

        // Show line of first install choice
        $choice = [
            'selected' => true,
            'short' => $this->langs->trans("FreshInstall"),
            'long' => $this->langs->trans("FreshInstallDesc"),
            'active' => $this->allow_install,
            'button' => $button,
        ];

        if (!isset($config->main_db_host) || empty($config->main_db_host)) {
            $choice['long'] .= '<br><div class="center"><div class="ok suggestedchoice">' . $this->langs->trans("InstallChoiceSuggested") . '</div></div>';
        }

        $this->vars->availableChoices[] = $choice;

        $positionkey = ($foundrecommandedchoice ? 999 : 0);
        if ($this->allow_install) {
            $available_choices[$positionkey] = $choice;
        } else {
            $notavailable_choices[$positionkey] = $choice;
        }

        // Show upgrade lines
        $allowupgrade = true;
        if (empty($config->main_db_host)) {    // This means install process was not run
            $allowupgrade = false;
        }
        if (getDolGlobalInt("MAIN_NOT_INSTALLED")) {
            $allowupgrade = false;
        }
        if (GETPOST('allowupgrade')) {
            $allowupgrade = true;
        }

        $this->vars->errorMigrations = false;
        $migrationscript = $this->getMigrationScript();

        $count = 0;
        foreach ($migrationscript as $migarray) {
            $choice = '';

            $count++;
            $recommended_choice = false;
            $version = DOL_VERSION;
            $versionfrom = $migarray['from'];
            $versionto = $migarray['to'];
            $versionarray = preg_split('/[\.-]/', $version);
            $dolibarrversionfromarray = preg_split('/[\.-]/', $versionfrom);
            $dolibarrversiontoarray = preg_split('/[\.-]/', $versionto);
            // Define string newversionxxx that are used for text to show
            $newversionfrom = preg_replace('/(\.[0-9]+)$/i', '.*', $versionfrom);
            $newversionto = preg_replace('/(\.[0-9]+)$/i', '.*', $versionto);
            $newversionfrombis = '';
            if (versioncompare($dolibarrversiontoarray, $versionarray) < -2) {  // From x.y.z -> x.y.z+1
                $newversionfrombis = ' ' . $this->langs->trans("or") . ' ' . $versionto;
            }

            if ($ok) {
                if (count($dolibarrlastupgradeversionarray) >= 2) { // If database access is available and last upgrade version is known
                    // Now we check if this is the first qualified choice
                    if (
                        $allowupgrade && empty($foundrecommandedchoice) &&
                        (versioncompare($dolibarrversiontoarray, $dolibarrlastupgradeversionarray) > 0 || versioncompare($dolibarrversiontoarray, $versionarray) < -2)
                    ) {
                        $foundrecommandedchoice = 1; // To show only once
                        $recommended_choice = true;
                    }
                } else {
                    // We cannot recommend a choice.
                    // A version of install may be known, but we need last upgrade.
                }
            }

            $button = $this->langs->trans("NotAvailable");
            if ($allowupgrade) {
                $disabled = false;
                if ($foundrecommandedchoice == 2) {
                    $disabled = true;
                }
                if ($foundrecommandedchoice == 1) {
                    $foundrecommandedchoice = 2;
                }
                if ($disabled) {
                    $button = '<span class="opacitymedium">' . $this->langs->trans("NotYetAvailable") . '</span>';
                } else {
                    // TODO: Pending fix how to pass the version in an action
                    $button = '<a class="button runupgrade" href="upgrade.php?action=upgrade' . ($count < count($migrationscript) ? '_' . $versionto : '') . '&selectlang=' . $this->vars->language . '&versionfrom=' . $versionfrom . '&versionto=' . $versionto . '">' . $this->langs->trans("Start") . '</a>';
                }
            }

            $choice = [
                'selected' => $recommended_choice,
                'short' => $this->langs->trans("Upgrade") . '<br>' . $newversionfrom . $newversionfrombis . ' -> ' . $newversionto,
                'long' => $this->langs->trans("UpgradeDesc"),
                'active' => $this->allow_install,
                'button' => $button,
            ];

            if ($recommended_choice) {
                $choice['long'] .= '<br><div class="center"><div class="ok suggestedchoice">' . $this->langs->trans("InstallChoiceSuggested") . '</div>';
                if ($count < count($migarray)) {
                    $choice['long'] .= $this->langs->trans('MigrateIsDoneStepByStep', DOL_VERSION);
                }
                $choice['long'] .= '</div>';
            }

            if ($allowupgrade) {
                $this->vars->availableChoices[$count] = $choice;
            } else {
                $this->vars->notAvailableChoices[$count] = $choice;
            }
        }

        // If there is no choice at all, we show all of them.
        if (empty($this->vars->availableChoices)) {
            $this->vars->availableChoices = $this->vars->notAvailableChoices;
            $this->vars->notAvailableChoices = [];
        }

        // Array of install choices
        krsort($this->vars->availableChoices, SORT_NATURAL);
    }

    /**
     * Gets an array with the SQL scripts for updating the database.
     * TODO: This method needs major refactoring
     *
     * @return array|mixed[]
     */
    private function getMigrationScript()
    {
        $dir = BASE_PATH . "/../Dolibarr/Modules/Install/mysql/migration/";   // We use mysql migration scripts whatever is database driver
        dolibarr_install_syslog("Scan sql files for migration files in " . $dir);

        // Get files list of migration file x.y.z-a.b.c.sql into /install/mysql/migration
        $migrationscript = [];
        $handle = opendir($dir);
        if (!is_resource($handle)) {
            $this->vars->errorMigrations = $this->langs->trans("ErrorCanNotReadDir", $dir);
            return [];
        }

        $versiontousetoqualifyscript = preg_replace('/-.*/', '', DOL_VERSION);
        while (($file = readdir($handle)) !== false) {
            $reg = [];
            if (preg_match('/^(\d+\.\d+\.\d+)-(\d+\.\d+\.\d+)\.sql$/i', $file, $reg)) {
                //var_dump(DOL_VERSION." ".$reg[2]." ".$versiontousetoqualifyscript." ".version_compare($versiontousetoqualifyscript, $reg[2]));
                if (!empty($reg[2]) && version_compare($versiontousetoqualifyscript, $reg[2]) >= 0) {
                    $migrationscript[] = ['from' => $reg[1], 'to' => $reg[2]];
                }
            }
        }
        return dol_sort_array($migrationscript, 'from', 'asc', 1);
    }

    public function checkDatabase()
    {
        if (isset($_POST['return'])) {
            return $this->doConfig();
        }

        $ok = 0;

        $this->template = 'install/db_update';
        $this->subtitle = $this->langs->trans("CreateDatabaseObjects");
        $this->nextButton = true;

// This page can be long. We increase the time allowed. / Cette page peut etre longue. On augmente le delai autorise.
// Only works if you are not in safe_mode. / Ne fonctionne que si on est pas en safe_mode.

        $err = error_reporting();
        error_reporting(0);      // Disable all errors
//error_reporting(E_ALL);
        @set_time_limit(1800);   // Need 1800 on some very slow OS like Windows 7/64
        error_reporting($err);

        $action = GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : (empty($argv[1]) ? '' : $argv[1]);
        $setuplang = $this->config->main->language;

        $conffile = Config::getDolibarrConfigFilename();
        //$conf = Config::getConfig();
        //$this->conf = Config::getConf();

        /**
         * El valor de "$this->config->db->type" puede ser 'mysqli' o 'pgsql'
         * 1: 'mysqli'
         * 2: 'pgsql'
         * 3: No usado
         * 4: No usado
         * 5: No usado
         */

        // Now we load forced values from install.forced.php file.
        /*
        $useforcedwizard = false;
        $forcedfile = "./install.forced.php";
        if ($conffile == "/etc/dolibarr/conf.php") {
            $forcedfile = "/etc/dolibarr/install.forced.php";
        }
        if (@file_exists($forcedfile)) {
            $useforcedwizard = true;
            include_once $forcedfile;
            // test for travis
            if (!empty($argv[1]) && $argv[1] == "set") {
                $action = "set";
            }
        }
        */

        dolibarr_install_syslog("--- step2: entering step2.php page");

        $this->vars->config_read_only = !is_writable($conffile);

        // Test if we can run a first install process
        if (!is_writable($conffile)) {
            $this->vars->config_filename = $conffile;
            return false;
        }

        $error = 0;
        $db = Config::getDb();

        $checks = [];

        if ($db->connected) {
            $text = $this->langs->trans('ServerConnection') . ': <strong>' . $this->config->db->host . '</strong>';
            $status = Status::OK;
        } else {
            $text = 'Failed to connect to server: ' . $this->config->db->host;
            $status = Status::FAIL;
        }
        $checks[] = [
            'status' => $status,
            'text' => $text,
        ];

        $ok = ($status === Status::OK);

        if ($ok) {
            if ($db->database_selected) {
                dolibarr_install_syslog("step2: successful connection to database: " . $this->config->db->name);
            } else {
                dolibarr_install_syslog("step2: failed connection to database :" . $this->config->db->name, LOG_ERR);
                $checks[] = [
                    'status' => Status::FAIL,
                    'text' => 'Failed to select database ' . $this->config->db->name,
                ];
            }

            // Display version / Affiche version
            $version = $db->getVersion();
            $versionarray = $db->getVersionArray();

            $checks[] = [
                'text' => $this->langs->trans('DatabaseVersion') . ': <strong>' . $version . '</strong>',
            ];
            //print '<td class="right">'.join('.',$versionarray).'</td></tr>';

            $checks[] = [
                'text' => $this->langs->trans('DatabaseName') . ': <strong>' . $db->database_name . '</strong>',
            ];
            //print '<td class="right">'.join('.',$versionarray).'</td></tr>';
        }

        $requestnb = 0;

        // To disable some code, so you can call step2 with url like
        // http://localhost/dolibarrnew/install/step2.php?action=set&token='.newToken().'&createtables=0&createkeys=0&createfunctions=0&createdata=llx_20_c_departements
        $createtables = GETPOSTISSET('createtables') ? GETPOST('createtables') : 1;
        $createkeys = GETPOSTISSET('createkeys') ? GETPOST('createkeys') : 1;
        $createfunctions = GETPOSTISSET('createfunctions') ? GETPOST('createfunction') : 1;
        $createdata = GETPOSTISSET('createdata') ? GETPOST('createdata') : 1;


        // To say that SQL we pass to query are already escaped for mysql, so we need to unescape them
        if (property_exists($db, 'unescapeslashquot')) {
            $db->unescapeslashquot = true;
        }

        /**************************************************************************************
         *
         * Load files tables/*.sql (not the *.key.sql). Files with '-xxx' in name are excluded (they will be loaded during activation of module 'xxx').
         * To do before the files *.key.sql
         *
         ***************************************************************************************/
        if ($ok && $createtables) {
            // We always choose in mysql directory (Conversion is done by driver to translate SQL syntax)
            $dir = realpath(BASE_PATH . '/../Dolibarr/Modules/Install/mysql/tables/') . DIRECTORY_SEPARATOR;

            $ok = 0;
            $handle = opendir($dir);
            dolibarr_install_syslog("step2: open tables directory " . $dir . " handle=" . $handle);
            $tablefound = 0;
            $tabledata = [];
            if (is_resource($handle)) {
                while (($file = readdir($handle)) !== false) {
                    if (preg_match('/\.sql$/i', $file) && preg_match('/^llx_/i', $file) && !preg_match('/\.key\.sql$/i', $file) && !preg_match('/\-/', $file)) {
                        $tablefound++;
                        $tabledata[] = $file;
                    }
                }
                closedir($handle);
            }

            // Sort list of sql files on alphabetical order (load order is important)
            sort($tabledata);
            foreach ($tabledata as $file) {
                $name = substr($file, 0, dol_strlen($file) - 4);
                $buffer = '';
                $fp = fopen($dir . $file, "r");
                if ($fp) {
                    while (!feof($fp)) {
                        $buf = fgets($fp, 4096);
                        if (substr($buf, 0, 2) != '--') {
                            $buf = preg_replace('/--(.+)*/', '', $buf);
                            $buffer .= $buf;
                        }
                    }
                    fclose($fp);

                    $buffer = trim($buffer);
                    if ($this->config->db->type == 'mysql' || $this->config->db->type == 'mysqli') {    // For Mysql 5.5+, we must replace type=innodb with ENGINE=innodb
                        $buffer = preg_replace('/type=innodb/i', 'ENGINE=innodb', $buffer);
                    } else {
                        // Keyword ENGINE is MySQL-specific, so scrub it for
                        // other database types (mssql, pgsql)
                        $buffer = preg_replace('/type=innodb/i', '', $buffer);
                        $buffer = preg_replace('/ENGINE=innodb/i', '', $buffer);
                    }

                    // Replace the prefix tables
                    if ($this->config->db->prefix != 'llx_') {
                        $buffer = preg_replace('/llx_/i', $this->config->db->prefix, $buffer);
                    }

                    //print "<tr><td>Creation of table $name/td>";
                    $requestnb++;

                    dolibarr_install_syslog("step2: request: " . $buffer);
                    $resql = $db->query($buffer, 0, 'dml');
                    if ($resql) {
                        // print "<td>OK request ==== $buffer</td></tr>";
                        $db->free($resql);
                    } else {
                        if (
                            $db->errno() == 'DB_ERROR_TABLE_ALREADY_EXISTS' ||
                            $db->errno() == 'DB_ERROR_TABLE_OR_KEY_ALREADY_EXISTS'
                        ) {
                            //print "<td>already existing</td></tr>";
                        } else {
                            print "<tr><td>" . $this->langs->trans("CreateTableAndPrimaryKey", $name);
                            print "<br>\n" . $this->langs->trans("Request") . ' ' . $requestnb . ' : ' . $buffer . ' <br>Executed query : ' . $db->lastquery;
                            print "\n</td>";
                            print '<td><span class="error">' . $this->langs->trans("ErrorSQL") . " " . $db->errno() . " " . $db->error() . '</span></td></tr>';
                            $error++;
                        }
                    }
                } else {
                    print "<tr><td>" . $this->langs->trans("CreateTableAndPrimaryKey", $name);
                    print "</td>";
                    print '<td><span class="error">' . $this->langs->trans("Error") . ' Failed to open file ' . $dir . $file . '</span></td></tr>';
                    $error++;
                    dolibarr_install_syslog("step2: failed to open file " . $dir . $file, LOG_ERR);
                }
            }

            if ($tablefound) {
                if ($error == 0) {
                    print '<tr><td>';
                    print $this->langs->trans("TablesAndPrimaryKeysCreation") . '</td><td><img src="../theme/eldy/img/ok.png" alt="Ok"></td></tr>';
                    $ok = 1;
                }
            } else {
                //print '<tr><td>' . $this->langs->trans("ErrorFailedToFindSomeFiles", $dir) . '</td><td><img src="../theme/eldy/img/error.png" alt="Error"></td></tr>';
                print '<tr><td>' . $this->langs->trans("FileIntegritySomeFilesWereRemovedOrModified", $dir) . '</td><td><img src="../theme/eldy/img/error.png" alt="Error"></td></tr>';
                dolibarr_install_syslog("step2: failed to find files to create database in directory " . $dir, LOG_ERR);
            }
        }


        /***************************************************************************************
         *
         * Load files tables/*.key.sql. Files with '-xxx' in name are excluded (they will be loaded during activation of module 'xxx').
         * To do after the files *.sql
         *
         ***************************************************************************************/
        if ($ok && $createkeys) {
            // We always choose in mysql directory (Conversion is done by driver to translate SQL syntax)
            $dir = realpath(BASE_PATH . '/../Dolibarr/Modules/Install/mysql/tables/') . DIRECTORY_SEPARATOR;

            $okkeys = 0;
            $handle = opendir($dir);
            dolibarr_install_syslog("step2: open keys directory " . $dir . " handle=" . $handle);
            $tablefound = 0;
            $tabledata = [];
            if (is_resource($handle)) {
                while (($file = readdir($handle)) !== false) {
                    if (preg_match('/\.sql$/i', $file) && preg_match('/^llx_/i', $file) && preg_match('/\.key\.sql$/i', $file) && !preg_match('/\-/', $file)) {
                        $tablefound++;
                        $tabledata[] = $file;
                    }
                }
                closedir($handle);
            }

            // Sort list of sql files on alphabetical order (load order is important)
            sort($tabledata);
            foreach ($tabledata as $file) {
                $name = substr($file, 0, dol_strlen($file) - 4);
                //print "<tr><td>Creation of table $name</td>";
                $buffer = '';
                $fp = fopen($dir . $file, "r");
                if ($fp) {
                    while (!feof($fp)) {
                        $buf = fgets($fp, 4096);

                        // Special case of lines allowed for some version only
                        // MySQL
                        if ($choix == 1 && preg_match('/^--\sV([0-9\.]+)/i', $buf, $reg)) {
                            $versioncommande = explode('.', $reg[1]);
                            //var_dump($versioncommande);
                            //var_dump($versionarray);
                            if (
                                count($versioncommande) && count($versionarray)
                                && versioncompare($versioncommande, $versionarray) <= 0
                            ) {
                                // Version qualified, delete SQL comments
                                $buf = preg_replace('/^--\sV([0-9\.]+)/i', '', $buf);
                                //print "Ligne $i qualifiee par version: ".$buf.'<br>';
                            }
                        }
                        // PGSQL
                        if ($choix == 2 && preg_match('/^--\sPOSTGRESQL\sV([0-9\.]+)/i', $buf, $reg)) {
                            $versioncommande = explode('.', $reg[1]);
                            //var_dump($versioncommande);
                            //var_dump($versionarray);
                            if (
                                count($versioncommande) && count($versionarray)
                                && versioncompare($versioncommande, $versionarray) <= 0
                            ) {
                                // Version qualified, delete SQL comments
                                $buf = preg_replace('/^--\sPOSTGRESQL\sV([0-9\.]+)/i', '', $buf);
                                //print "Ligne $i qualifiee par version: ".$buf.'<br>';
                            }
                        }

                        // Add line if no comment
                        if (!preg_match('/^--/i', $buf)) {
                            $buffer .= $buf;
                        }
                    }
                    fclose($fp);

                    // If several requests, we loop on each
                    $listesql = explode(';', $buffer);
                    foreach ($listesql as $req) {
                        $buffer = trim($req);
                        if ($buffer) {
                            // Replace the prefix tables
                            if ($this->config->db->prefix != 'llx_') {
                                $buffer = preg_replace('/llx_/i', $this->config->db->prefix, $buffer);
                            }

                            //print "<tr><td>Creation of keys and table index $name: '$buffer'</td>";
                            $requestnb++;

                            dolibarr_install_syslog("step2: request: " . $buffer);
                            $resql = $db->query($buffer, 0, 'dml');
                            if ($resql) {
                                //print "<td>OK request ==== $buffer</td></tr>";
                                $db->free($resql);
                            } else {
                                if (
                                    $db->errno() == 'DB_ERROR_KEY_NAME_ALREADY_EXISTS' ||
                                    $db->errno() == 'DB_ERROR_CANNOT_CREATE' ||
                                    $db->errno() == 'DB_ERROR_PRIMARY_KEY_ALREADY_EXISTS' ||
                                    $db->errno() == 'DB_ERROR_TABLE_OR_KEY_ALREADY_EXISTS' ||
                                    preg_match('/duplicate key name/i', $db->error())
                                ) {
                                    //print "<td>Deja existante</td></tr>";
                                    $key_exists = 1;
                                } else {
                                    print "<tr><td>" . $this->langs->trans("CreateOtherKeysForTable", $name);
                                    print "<br>\n" . $this->langs->trans("Request") . ' ' . $requestnb . ' : ' . $db->lastqueryerror();
                                    print "\n</td>";
                                    print '<td><span class="error">' . $this->langs->trans("ErrorSQL") . " " . $db->errno() . " " . $db->error() . '</span></td></tr>';
                                    $error++;
                                }
                            }
                        }
                    }
                } else {
                    print "<tr><td>" . $this->langs->trans("CreateOtherKeysForTable", $name);
                    print "</td>";
                    print '<td><span class="error">' . $this->langs->trans("Error") . " Failed to open file " . $dir . $file . "</span></td></tr>";
                    $error++;
                    dolibarr_install_syslog("step2: failed to open file " . $dir . $file, LOG_ERR);
                }
            }

            if ($tablefound && $error == 0) {
                print '<tr><td>';
                print $this->langs->trans("OtherKeysCreation") . '</td><td><img src="../theme/eldy/img/ok.png" alt="Ok"></td></tr>';
                $okkeys = 1;
            }
        }


        /***************************************************************************************
         *
         * Load the file 'functions.sql'
         *
         ***************************************************************************************/
        if ($ok && $createfunctions) {
            // For this file, we use a directory according to database type
            switch ($this->config->db->db_type) {
                case 'mysqli':
                    $dir = "mysql/functions/";
                    break;
                case 'pgsql':
                    $dir = "pgsql/functions/";
                    break;
                case 'mssql':
                    $dir = "mssql/functions/";
                    break;
                case 'sqlite3':
                    $dir = "sqlite3/functions/";
                    break;
            }
            $dir = realpath(BASE_PATH . '/../Dolibarr/Modules/Install/' . $dir) . DIRECTORY_SEPARATOR;

            // Creation of data
            $file = "functions.sql";
            if (file_exists($dir . $file)) {
                $fp = fopen($dir . $file, "r");
                dolibarr_install_syslog("step2: open function file " . $dir . $file . " handle=" . $fp);
                if ($fp) {
                    $buffer = '';
                    while (!feof($fp)) {
                        $buf = fgets($fp, 4096);
                        if (substr($buf, 0, 2) != '--') {
                            $buffer .= $buf . "§";
                        }
                    }
                    fclose($fp);
                }
                //$buffer=preg_replace('/;\';/',";'§",$buffer);

                // If several requests, we loop on each of them
                $listesql = explode('§', $buffer);
                foreach ($listesql as $buffer) {
                    $buffer = trim($buffer);
                    if ($buffer) {
                        // Replace the prefix in table names
                        if ($this->config->db->prefix != 'llx_') {
                            $buffer = preg_replace('/llx_/i', $this->config->db->prefix, $buffer);
                        }
                        dolibarr_install_syslog("step2: request: " . $buffer);
                        print "<!-- Insert line : " . $buffer . "<br>-->\n";
                        $resql = $db->query($buffer, 0, 'dml');
                        if ($resql) {
                            $ok = 1;
                            $db->free($resql);
                        } else {
                            if (
                                $db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS'
                                || $db->errno() == 'DB_ERROR_KEY_NAME_ALREADY_EXISTS'
                            ) {
                                //print "Insert line : ".$buffer."<br>\n";
                            } else {
                                $ok = 0;

                                $text = $this->langs->trans("FunctionsCreation")
                                    . "\n" . $this->langs->trans("Request") . ' ' . $requestnb . ' : ' . $buffer
                                    . "\n" . '<span class="error">' . $this->langs->trans("ErrorSQL") . " " . $db->errno() . " " . $db->error() . '</span>';
                                $checks[] = [
                                    'text' => $text,
                                    'status' => Status::FAIL,
                                ];
                                $error++;
                            }
                        }
                    }
                }

                $checks[] = [
                    'text' => $this->langs->trans("FunctionsCreation"),
                    'status' => $ok ? Status::OK : Status::FAIL,
                ];
            }
        }


        /***************************************************************************************
         *
         * Load files data/*.sql. Files with '-xxx' in name are excluded (they will be loaded during activation of module 'xxx').
         *
         ***************************************************************************************/
        if ($ok && $createdata) {
            // We always choose in mysql directory (Conversion is done by driver to translate SQL syntax)
            $dir = "mysql/data/";

            // Insert data
            $handle = opendir($dir);
            dolibarr_install_syslog("step2: open directory data " . $dir . " handle=" . $handle);
            $tablefound = 0;
            $tabledata = [];
            if (is_resource($handle)) {
                while (($file = readdir($handle)) !== false) {
                    if (preg_match('/\.sql$/i', $file) && preg_match('/^llx_/i', $file) && !preg_match('/\-/', $file)) {
                        if (preg_match('/^llx_accounting_account_/', $file)) {
                            continue; // We discard data file of chart of account. This will be loaded when a chart is selected.
                        }

                        //print 'x'.$file.'-'.$createdata.'<br>';
                        if (is_numeric($createdata) || preg_match('/' . preg_quote($createdata) . '/i', $file)) {
                            $tablefound++;
                            $tabledata[] = $file;
                        }
                    }
                }
                closedir($handle);
            }

            // Sort list of data files on alphabetical order (load order is important)
            sort($tabledata);
            foreach ($tabledata as $file) {
                $name = substr($file, 0, dol_strlen($file) - 4);
                $fp = fopen($dir . $file, "r");
                dolibarr_install_syslog("step2: open data file " . $dir . $file . " handle=" . $fp);
                if ($fp) {
                    $arrayofrequests = [];
                    $linefound = 0;
                    $linegroup = 0;
                    $sizeofgroup = 1; // Grouping request to have 1 query for several requests does not works with mysql, so we use 1.

                    // Load all requests
                    while (!feof($fp)) {
                        $buffer = fgets($fp, 4096);
                        $buffer = trim($buffer);
                        if ($buffer) {
                            if (substr($buffer, 0, 2) == '--') {
                                continue;
                            }

                            if ($linefound && ($linefound % $sizeofgroup) == 0) {
                                $linegroup++;
                            }
                            if (empty($arrayofrequests[$linegroup])) {
                                $arrayofrequests[$linegroup] = $buffer;
                            } else {
                                $arrayofrequests[$linegroup] .= " " . $buffer;
                            }

                            $linefound++;
                        }
                    }
                    fclose($fp);

                    dolibarr_install_syslog("step2: found " . $linefound . " records, defined " . count($arrayofrequests) . " group(s).");

                    $okallfile = 1;
                    $db->begin();

                    // We loop on each requests of file
                    foreach ($arrayofrequests as $buffer) {
                        // Replace the tables prefixes
                        if ($this->config->db->prefix != 'llx_') {
                            $buffer = preg_replace('/llx_/i', $this->config->db->prefix, $buffer);
                        }

                        //dolibarr_install_syslog("step2: request: " . $buffer);
                        $resql = $db->query($buffer, 1);
                        if ($resql) {
                            //$db->free($resql);     // Not required as request we launch here does not return memory needs.
                        } else {
                            if ($db->lasterrno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
                                //print "<tr><td>Insertion ligne : $buffer</td><td>";
                            } else {
                                $ok = 0;
                                $okallfile = 0;
                                print '<span class="error">' . $this->langs->trans("ErrorSQL") . " : " . $db->lasterrno() . " - " . $db->lastqueryerror() . " - " . $db->lasterror() . "</span><br>";
                            }
                        }
                    }

                    if ($okallfile) {
                        $db->commit();
                    } else {
                        $db->rollback();
                    }
                }
            }

            print "<tr><td>" . $this->langs->trans("ReferenceDataLoading") . "</td>";
            if ($ok) {
                print '<td><img src="../theme/eldy/img/ok.png" alt="Ok"></td></tr>';
            } else {
                print '<td><img src="../theme/eldy/img/error.png" alt="Error"></td></tr>';
                $ok = 1; // Data loading are not blocking errors
            }
        }
        print '</table>';

        $ret = 0;
        if (!$ok && isset($argv[1])) {
            $ret = 1;
        }
        dolibarr_install_syslog("Exit " . $ret);

        dolibarr_install_syslog("- step2: end");

// Force here a value we need after because master.inc.php is not loaded into step2.
// This code must be similar with the one into main.inc.php

        $hash_unique_id = dol_hash('dolibarr' . $this->config->main->unique_id, 'sha256');   // Note: if the global salt changes, this hash changes too so ping may be counted twice. We don't mind. It is for statistics purpose only.

        $out = '<input type="checkbox" name="dolibarrpingno" id="dolibarrpingno"' . ((getDolGlobalString('MAIN_FIRST_PING_OK_ID') == 'disabled') ? '' : ' value="checked" checked="true"') . '> ';
        $out .= '<label for="dolibarrpingno">' . $this->langs->trans("MakeAnonymousPing") . '</label>';

        $out .= '<!-- Add js script to manage the uncheck of option to not send the ping -->';
        $out .= '<script type="text/javascript">';
        $out .= 'jQuery(document).ready(function(){';
        $out .= '  document.cookie = "DOLINSTALLNOPING_' . $hash_unique_id . '=0; path=/"' . "\n";
        $out .= '  jQuery("#dolibarrpingno").click(function() {';
        $out .= '    if (! $(this).is(\':checked\')) {';
        $out .= '      console.log("We uncheck anonymous ping");';
        $out .= '      document.cookie = "DOLINSTALLNOPING_' . $hash_unique_id . '=1; path=/"' . "\n";
        $out .= '    } else {' . "\n";
        $out .= '      console.log("We check anonymous ping");';
        $out .= '      document.cookie = "DOLINSTALLNOPING_' . $hash_unique_id . '=0; path=/"' . "\n";
        $out .= '    }' . "\n";
        $out .= '  });';
        $out .= '});';
        $out .= '</script>';


        print $out;

        return static::setIcons($checks);
    }

    public function doConfig(): bool
    {
        $this->langs->setDefaultLang('auto');
        $this->langs->loadLangs(['main', 'admin', 'install', 'errors']);

        /**
         * "main_dir"
         * "main_data_dir"
         * "main_url"
         * "db_name"
         * "db_type"
         * "db_host"
         * "db_port"
         * "db_prefix"
         * "db_user"
         * "db_pass"
         * "db_create_user"
         * "db_create_database"
         *      "db_user_root"
         *      "db_pass_root"
         */

        $this->template = 'install/db_udpate';
        $this->nextButton = true;
        $this->vars->errors = [];

        session_start(); // To be able to keep info into session (used for not losing password during navigation. The password must not transit through parameters)

        $oldConf = $this->config;
        $this->refreshConfigFromPost();

        // $this->dolibarr_main_distrib = 'standard';

        if ($_POST['main_force_https'] ?? 'on' === 'off') {
            $this->config->main->url = 'http' . substr($this->config->main->url, 5);
        }

        $this->vars->create_database = ($_POST['db_create_database'] ?? 'off') === 'on';
        $this->vars->create_user = ($_POST['db_create_user'] ?? 'off') === 'on';
        $superuser = $this->vars->create_database || $this->vars->create_user;

        if ($superuser) {
            $this->vars->root_user = getIfIsset('db_user_root', $this->vars->root_user);
            $this->vars->root_pass = getIfIsset('db_pass_root', $this->vars->root_pass);

            $db = getDoliDBInstance(
                $this->config->db->type,
                $this->config->db->host,
                $this->vars->root_user,
                $this->vars->root_pass,
                'User an database creation',
                (int)$this->config->db->port
            );

            if ($this->vars->create_database) {
                $result = $db->DDLCreateDb(
                    $this->config->db->name,
                    $this->config->db->charset,
                    $this->config->db->collation,
                    $this->config->db->user
                );

                if (!$result) {
                    $this->vars->errors[] = $this->langs->trans("IfDatabaseExistsGoBackAndCheckCreate");
                    return $this->doStart();
                }
            }

            if ($this->vars->create_user) {
                $result = $db->DDLCreateUser(
                    $this->config->db->host,
                    $this->config->db->user,
                    $this->config->db->pass,
                    $this->config->db->name
                );

                if ($result !== 1) {
                    $this->vars->errors[] = $this->langs->trans("IfLoginDoesNotExistsCheckCreateUser");
                    return $this->doStart();
                }
            }
        }

        $db = getDoliDBInstance(
            $this->config->db->type,
            $this->config->db->host,
            $this->config->db->user,
            $this->config->db->pass,
            $this->config->db->name,
            (int)$this->config->db->port
        );

        if (!$db->ok) {
            $this->vars->errors[] = $this->langs->trans("ErrorConnection", $this->config->db->host, $this->config->db->name, $this->config->db->user);
            return $this->doStart();
        }

        return $this->write_conf_file() && Config::saveConfig() && $this->checkDatabase();
    }

    /**
     * Complete all configuration values, as received by POST.
     *
     * @return void
     */
    private function refreshConfigFromPost()
    {
        /**
         * config:
         *      "main"
         *          "base_path"
         *          "base_url"
         *          "data_path"
         *          "alt_base_path": array
         *          "alt_base_url": array:1
         *          "theme": "eldy"
         *      "db"
         *          "host"
         *          "port"
         *          "name"
         *          "user"
         *          "pass"
         *          "type"
         *          "prefix": "alx_"
         *          "charset": "utf8"
         *          "collation": "utf8_general_ci"
         *          "encryption": 0
         *          "cryptkey": ""
         */

        if (!isset($this->config->main->unique_id)) {
            $this->config->main->unique_id = md5(uniqid(mt_rand(), true));
        }

        if (!isset($this->config->main->documents)) {
            $this->config->main->documents = Config::getDataDir($this->config->main->path);
        }

        $this->config->main->path = getIfIsset('base_path', $this->config->main->path);
        $this->config->main->url = getIfIsset('base_url', $this->config->main->url);
        $this->config->main->documents = getIfIsset('data_path', $this->config->main->documents);

        if ($this->force_https) {
            str_replace('http://', 'https://', $this->config->main->url);
        } else {
            str_replace('https://', 'http://', $this->config->main->url);
        }

        if (!isset($this->config->db)) {
            $this->config->db = new stdClass();
        }

        $this->config->db->name = getIfIsset('db_name', $this->config->db->name ?? 'alixar');
        $this->config->db->type = getIfIsset('db_type', $this->config->db->type ?? '');
        $this->config->db->host = getIfIsset('db_host', $this->config->db->host ?? '');
        $this->config->db->port = getIfIsset('db_port', $this->config->db->port ?? '');
        $this->config->db->prefix = getIfIsset('db_prefix', $this->config->db->prefix ?? Config::DEFAULT_DB_PREFIX);

        $this->config->db->user = getIfIsset('db_user', $this->config->db->user ?? '');
        $this->config->db->pass = getIfIsset('db_pass', $this->config->db->pass ?? '');

        $this->config->db->charset = 'utf8';
        $this->config->db->collation = 'utf8_general_ci';
    }

    /**
     * Allows to configure database access parameters.
     *
     * @return true
     */
    public function doStart(): bool
    {
        $this->langs->setDefaultLang('auto');
        $this->langs->loadLangs(['main', 'admin', 'install']);

        $this->template = 'install/start';
        $this->nextButton = true;

        /**
         * There may be a file called install.forced.php with predefined parameters for the
         * installation. In that case, these parameters cannot be modified. Used for guided
         * installations.
         *
         * At the moment, we prefer not to implement this option.
         *
         * The best place to implement it is when creating the conf.php file, because it is
         * not even necessary to install the program.
         */

        /*
        // Now we load forced values from install.forced.php file.
        $useforcedwizard = false;
        $forcedfile = "./install.forced.php";
        if ($conffile == "/etc/dolibarr/conf.php") {
            $forcedfile = "/etc/dolibarr/install.forced.php"; // Must be after inc.php
        }
        if (@file_exists($forcedfile)) {
            $useforcedwizard = true;
            include_once $forcedfile;
        }
        */

        dolibarr_install_syslog("- fileconf: entering fileconf.php page");

        /**
         * You can force preselected values of the config step of Dolibarr by adding a file
         * install.forced.php into directory htdocs/install (This is the case with some wizard
         * installer like DoliWamp, DoliMamp or DoliBuntu).
         * We first init "forced values" to nothing.
         *
         * $install_noedit empty if no block:
         *   1 = To block vars specific to distrib
         *   2 = To block all technical parameters
         */

        $this->vars->install_noedit = '';

        $this->vars->db_types = $this->getDbTypes();

        $this->refreshConfigFromPost();

        /**
         * If it is active it is 'on', if it is not active it is 'off'.
         * But if it's the first time, it will be 'null', and we use it as 'on'.
         */
        $this->vars->force_https = ($_POST['main_force_https'] ?? 'on') === 'on';
        $this->vars->create_database = ($_POST['db_create_database'] ?? 'off') === 'on';
        $this->vars->create_user = ($_POST['db_create_user'] ?? 'off') === 'on';

        $this->vars->root_user = getIfIsset('db_user_root', $this->vars->root_user ?? '');
        $this->vars->root_pass = getIfIsset('db_pass_root', $this->vars->root_pass ?? '');

        // session_start(); // To be able to keep info into session (used for not losing pass during navigation. pass must not transit through parameters)

        $this->subtitle = $this->langs->trans("ConfigurationFile");

        $this->nextButtonJs = 'return jscheckparam();';

        return true;
    }

    /**
     * Return an array with DB drivers availables (It only includes MySQL and PostgreSQL)
     *
     * @return array
     */
    private function getDbTypes()
    {
        $drivers = PDO::getAvailableDrivers();
        foreach ($drivers as $driver) {
            switch ($driver) {
                case 'mysql':
                    $result[] = [
                        'shortname' => 'MySQL/MariaDB',
                        'classname' => 'mysqli',
                        'min_version' => '',
                        'comment' => '',
                    ];
                    break;
                case 'pgsql':
                    $result[] = [
                        'shortname' => 'PostgreSQL',
                        'classname' => 'pgsql',
                        'min_version' => '',
                        'comment' => '',
                    ];
                    break;
            }
        }
        return $result;
    }

    /**
     *  Save configuration file. No particular permissions are set by installer.
     *
     * @param string $conffile Path to conf file to generate/update
     *
     * @return integer
     */
    function write_conf_file()
    {
        $error = 0;

        $configFilename = Config::getDolibarrConfigFilename();
        $key = $this->config->main->unique_id;
        if (empty($key)) {
            $key = md5(uniqid(mt_rand(), true)); // Generate random hash
        }

        // $datetime = dol_print_date(dol_now(), '');
        $datetime = date('Y-m-d H:i:s');

        $fp = fopen($configFilename, "w");
        if ($fp) {
            clearstatcache();

            fwrite($fp, '<?php' . "\n");
            fwrite($fp, '//' . "\n");
            fwrite($fp, '// File generated by Alixar installer ' . DOL_VERSION . ' on ' . $datetime . "\n");
            fwrite($fp, '//' . "\n");
            fwrite($fp, '// Take a look at conf.php.example file for an example of ' . $configFilename . ' file' . "\n");
            fwrite($fp, '// and explanations for all possibles parameters.' . "\n");
            fwrite($fp, '//' . "\n");

            fwrite($fp, '$dolibarr_main_url_root=\'' . dol_escape_php(trim($this->config->main->url), 1) . '\';');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_main_document_root="' . dol_escape_php(dol_sanitizePathName(trim($this->config->main->path))) . '";');
            fwrite($fp, "\n");

            /*
            fwrite($fp, $this->main_use_alt_dir . '$dolibarr_main_url_root_alt=\'' . dol_escape_php(trim("/" . reset($this->config->main->alt_base_path)), 1) . '\';');
            fwrite($fp, "\n");

            fwrite($fp, $this->main_use_alt_dir . '$dolibarr_main_document_root_alt="' . dol_escape_php(dol_sanitizePathName(reset(trim($this->config->main->url . "/" . $this->config->main->alt_base_url)))) . '";');
            fwrite($fp, "\n");
            */

            fwrite($fp, '$dolibarr_main_data_root="' . dol_escape_php(dol_sanitizePathName(trim($this->config->main->data_path))) . '";');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_main_db_host=\'' . dol_escape_php(trim($this->config->db->host), 1) . '\';');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_main_db_port=\'' . ((int)$this->config->db->port) . '\';');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_main_db_name=\'' . dol_escape_php(trim($this->config->db->name), 1) . '\';');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_main_db_prefix=\'' . dol_escape_php(trim($this->config->db->prefix), 1) . '\';');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_main_db_user=\'' . dol_escape_php(trim($this->config->db->user), 1) . '\';');
            fwrite($fp, "\n");
            fwrite($fp, '$dolibarr_main_db_pass=\'' . dol_escape_php(trim($this->config->db->pass), 1) . '\';');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_main_db_type=\'' . dol_escape_php(trim($this->config->db->type), 1) . '\';');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_main_db_character_set=\'' . dol_escape_php(trim($this->config->db->charset), 1) . '\';');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_main_db_collation=\'' . dol_escape_php(trim($this->config->db->collation), 1) . '\';');
            fwrite($fp, "\n");

            // Authentication
            fwrite($fp, '// Authentication settings');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_main_authentication=\'dolibarr\';');
            fwrite($fp, "\n\n");

            fwrite($fp, '//$dolibarr_main_demo=\'autologin,autopass\';');
            fwrite($fp, "\n");

            fwrite($fp, '// Security settings');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_main_prod=\'0\';');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_main_force_https=\'' . dol_escape_php($this->config->security->force_https, 1) . '\';');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_main_restrict_os_commands=\'mariadb-dump, mariadb, mysqldump, mysql, pg_dump, pgrestore, clamdscan, clamscan.exe\';');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_nocsrfcheck=\'0\';');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_main_instance_unique_id=\'' . dol_escape_php($key, 1) . '\';');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_mailing_limit_sendbyweb=\'0\';');
            fwrite($fp, "\n");
            fwrite($fp, '$dolibarr_mailing_limit_sendbycli=\'0\';');
            fwrite($fp, "\n");

            fclose($fp);

            if (!file_exists("$configFilename")) {
                return false;
            }
        }

        return true;
    }
}
