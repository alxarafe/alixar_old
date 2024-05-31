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

require_once BASE_PATH . '/../Dolibarr/Lib/Admin.php';
require_once BASE_PATH . '/install/inc.php';

class InstallController extends DolibarrViewController
{
    /**
     * @var bool
     */
    public $allowInstall;
    /**
     * @var mixed
     */
    public $selectLang;
    /**
     * @var array
     */
    public $availableChoices;
    /**
     * @var false
     */
    public $errorMigrations;

    public $checks;
    public $nextButton;
    public $nextButtonJs;
    public $htmlComboLanguages;
    public $notAvailableChoices;
    public $printVersion;
    public $force_install_noedit;
    public $force_install_mainforcehttps;
    public $install_createdatabase;
    public $install_noedit;
    public $autofill;
    public $install_databaserootlogin;

    /**
     *  Create main file. No particular permissions are set by installer.
     *
     * @param string $mainfile Full path name of main file to generate/update
     * @param string $this ->main_dir Full path name to main.inc.php file
     *
     * @return void
     */
    function write_main_file($mainfile, $main_dir)
    {
        $fp = @fopen("$mainfile", "w");
        if ($fp) {
            clearstatcache();
            fwrite($fp, '<?php' . "\n");
            fwrite($fp, "// Wrapper to include main into htdocs\n");
            fwrite($fp, "include_once '" . $main_dir . "/main.inc.php';\n");
            fclose($fp);
        }
    }

    /**
     *  Create master file. No particular permissions are set by installer.
     *
     * @param string $masterfile Full path name of master file to generate/update
     * @param string $this ->main_dir   Full path name to master.inc.php file
     *
     * @return void
     */
    function write_master_file($masterfile, $main_dir)
    {
        $fp = @fopen("$masterfile", "w");
        if ($fp) {
            clearstatcache();
            fwrite($fp, '<?php' . "\n");
            fwrite($fp, "// Wrapper to include master into htdocs\n");
            fwrite($fp, "include_once '" . $main_dir . "/master.inc.php';\n");
            fclose($fp);
        }
    }

    public function _checkAction(): bool
    {
        if (parent::checkAction()) {
            return true;
        }

        switch (htmlentities($this->action)) {
            case 'checked':
                return $this->actionChecked();
            case 'config':
                return $this->actionConfig();
            case 'step2':
                return $this->actionStep2();
            case $this->langs->trans("Start"):
                return $this->actionStart();
            default:
                $this->syslog("The action $this->action is not defined!");
        }

        return false;
    }

    public function _actionStep2()
    {
        $step = 2;
        $ok = 0;

        $this->template = 'install/step2';
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
        $setuplang = GETPOST('selectlang', 'aZ09', 3) ? GETPOST('selectlang', 'aZ09', 3) : (empty($argv[2]) ? 'auto' : $argv[2]);

        $conffile = Config::getDolibarrConfigFilename();
        $conf = Globals::getConfig();
        $this->conf = Globals::getConf();

// Choice of DBMS
        $choix = 0;
        if ($conf->main_db_type == "MySqliEngine") {
            $choix = 1;
        }
        if ($conf->main_db_type == "PgSqlEngine") {
            $choix = 2;
        }
        if ($conf->main_db_type == "mssql") {
            $choix = 3;
        }
        if ($conf->main_db_type == "sqlite") {
            $choix = 4;
        }
        if ($conf->main_db_type == "Sqlite3Engine") {
            $choix = 5;
        }
//if (empty($choix)) dol_print_error(null,'Database type '.$conf->main_db_type.' not supported into step2.php page');


// Now we load forced values from install.forced.php file.

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

        dolibarr_install_syslog("--- step2: entering step2.php page");


        /*
         *  View
         */

// Test if we can run a first install process
        if (!is_writable($conffile)) {
            print $this->langs->trans("ConfFileIsNotWritable", $conffiletoshow);
            pFooter(1, $setuplang, 'jscheckparam');
            exit;
        }

        if ($action == "set") {
            print '<h3><img class="valignmiddle inline-block paddingright" src="../' . $this->config->main->theme . '/img/svg/database.svg" width="20" alt="Database"> ' . $this->langs->trans("Database") . '</h3>';

            print '<table cellspacing="0" style="padding: 4px 4px 4px 0" border="0" width="100%">';
            $error = 0;

            $db = getDoliDBInstance($conf->db->type, $conf->db->host, $conf->db->user, $conf->db->pass, $conf->db->name, (int)$conf->db->port);

            if ($db->connected) {
                print "<tr><td>";
                print $this->langs->trans("ServerConnection") . " : " . $conf->db->host . '</td><td><img src="../theme/eldy/img/ok.png" alt="Ok"></td></tr>';
                $ok = 1;
            } else {
                print "<tr><td>Failed to connect to server : " . $conf->db->host . '</td><td><img src="../theme/eldy/img/error.png" alt="Error"></td></tr>';
            }

            if ($ok) {
                if ($db->database_selected) {
                    dolibarr_install_syslog("step2: successful connection to database: " . $conf->db->name);
                } else {
                    dolibarr_install_syslog("step2: failed connection to database :" . $conf->db->name, LOG_ERR);
                    print "<tr><td>Failed to select database " . $conf->db->name . '</td><td><img src="../theme/eldy/img/error.png" alt="Error"></td></tr>';
                    $ok = 0;
                }
            }


            // Display version / Affiche version
            if ($ok) {
                $version = $db->getVersion();
                $versionarray = $db->getVersionArray();
                print '<tr><td>' . $this->langs->trans("DatabaseVersion") . '</td>';
                print '<td>' . $version . '</td></tr>';
                //print '<td class="right">'.join('.',$versionarray).'</td></tr>';

                print '<tr><td>' . $this->langs->trans("DatabaseName") . '</td>';
                print '<td>' . $db->database_name . '</td></tr>';
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
                $dir = "mysql/tables/";

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
                        if ($conf->db->type == 'mysql' || $conf->db->type == 'mysqli') {    // For Mysql 5.5+, we must replace type=innodb with ENGINE=innodb
                            $buffer = preg_replace('/type=innodb/i', 'ENGINE=innodb', $buffer);
                        } else {
                            // Keyword ENGINE is MySQL-specific, so scrub it for
                            // other database types (mssql, pgsql)
                            $buffer = preg_replace('/type=innodb/i', '', $buffer);
                            $buffer = preg_replace('/ENGINE=innodb/i', '', $buffer);
                        }

                        // Replace the prefix tables
                        if ($conf->main_db_prefix != 'llx_') {
                            $buffer = preg_replace('/llx_/i', $conf->main_db_prefix, $buffer);
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
                    print '<tr><td>' . $this->langs->trans("ErrorFailedToFindSomeFiles", $dir) . '</td><td><img src="../theme/eldy/img/error.png" alt="Error"></td></tr>';
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
                $dir = "mysql/tables/";

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
                                if ($conf->main_db_prefix != 'llx_') {
                                    $buffer = preg_replace('/llx_/i', $conf->main_db_prefix, $buffer);
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
                if ($choix == 1) {
                    $dir = "mysql/functions/";
                } elseif ($choix == 2) {
                    $dir = "pgsql/functions/";
                } elseif ($choix == 3) {
                    $dir = "mssql/functions/";
                } elseif ($choix == 4) {
                    $dir = "sqlite3/functions/";
                }

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
                            if ($conf->main_db_prefix != 'llx_') {
                                $buffer = preg_replace('/llx_/i', $conf->main_db_prefix, $buffer);
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

                                    print "<tr><td>" . $this->langs->trans("FunctionsCreation");
                                    print "<br>\n" . $this->langs->trans("Request") . ' ' . $requestnb . ' : ' . $buffer;
                                    print "\n</td>";
                                    print '<td><span class="error">' . $this->langs->trans("ErrorSQL") . " " . $db->errno() . " " . $db->error() . '</span></td></tr>';
                                    $error++;
                                }
                            }
                        }
                    }

                    print "<tr><td>" . $this->langs->trans("FunctionsCreation") . "</td>";
                    if ($ok) {
                        print '<td><img src="../theme/eldy/img/ok.png" alt="Ok"></td></tr>';
                    } else {
                        print '<td><img src="../theme/eldy/img/error.png" alt="Error"></td></tr>';
                        $ok = 1;
                    }
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
                            if ($conf->main_db_prefix != 'llx_') {
                                $buffer = preg_replace('/llx_/i', $conf->main_db_prefix, $buffer);
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
        } else {
            print 'Parameter action=set not defined';
        }


        $ret = 0;
        if (!$ok && isset($argv[1])) {
            $ret = 1;
        }
        dolibarr_install_syslog("Exit " . $ret);

        dolibarr_install_syslog("- step2: end");

// Force here a value we need after because master.inc.php is not loaded into step2.
// This code must be similar with the one into main.inc.php

        $this->conf->file->instance_unique_id = (empty($conf->main_instance_unique_id) ? (empty($dolibarr_main_cookie_cryptkey) ? '' : $dolibarr_main_cookie_cryptkey) : $conf->main_instance_unique_id); // Unique id of instance

        $hash_unique_id = Security::dol_hash('dolibarr' . $this->conf->file->instance_unique_id, 'sha256');   // Note: if the global salt changes, this hash changes too so ping may be counted twice. We don't mind. It is for statistics purpose only.

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

        return true;
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
         * $force_install_noedit empty if no block:
         *   1 = To block vars specific to distrib
         *   2 = To block all technical parameters
         */

        $this->force_install_noedit = '';

        $this->db_types = $this->getDbTypes();

        $this->refreshConfigFromPost();

        /**
         * If it is active it is 'on', if it is not active it is 'off'.
         * But if it's the first time, it will be 'null', and we use it as 'on'.
         */
        $this->force_install_mainforcehttps = ($_POST['main_force_https'] ?? 'on') === 'on';
        $this->install_createdatabase = ($_POST['db_create_database'] ?? 'off') === 'on';
        $this->force_install_createuser = ($_POST['db_create_user'] ?? 'off') === 'on';

        $this->db_user_root = getIfIsset('db_user_root', $this->db_user_root ?? '');
        $this->db_pass_root = getIfIsset('db_pass_root', $this->db_pass_root ?? '');

        session_start(); // To be able to keep info into session (used for not losing pass during navigation. pass must not transit through parameters)

        $this->subtitle = $this->langs->trans("ConfigurationFile");

        $this->nextButtonJs = 'return jscheckparam();';

        return true;
    }

    private function getDbTypes()
    {
        $drivers = PDO::getAvailableDrivers();
        foreach ($drivers as $driver) {
            switch ($driver) {
                case 'mysql':
                    $result[] = [
                        'shortname' => 'MySQL/MariaDB',
                        'classname' => $driver,
                        'min_version' => '',
                        'comment' => '',
                    ];
                    break;
                case 'pgsql':
                    $result[] = [
                        'shortname' => 'PostgreSQL',
                        'classname' => $driver,
                        'min_version' => '',
                        'comment' => '',
                    ];
                    break;
            }
        }
        return $result;
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
         *          "collation": "utf8_unicode_ci"
         *          "encryption": 0
         *          "cryptkey": ""
         */

        $this->config->main->base_path = getIfIsset('main_dir', $this->config->main->base_path);
        $this->config->main->data_path = getIfIsset('main_data_dir', $this->config->main->data_path);
        $this->config->main->base_url = getIfIsset('main_url', $this->config->main->base_url);

        $this->config->db->name = getIfIsset('db_name', $this->config->db->name);
        $this->config->db->type = getIfIsset('db_type', $this->config->db->type);
        $this->config->db->host = getIfIsset('db_host', $this->config->db->host);
        $this->config->db->port = getIfIsset('db_port', $this->config->db->port);
        $this->config->db->prefix = getIfIsset('db_prefix', $this->config->db->prefix);

        $this->config->db->user = getIfIsset('db_user', $this->config->db->user);
        $this->config->db->pass = getIfIsset('db_pass', $this->config->db->pass);
    }

    /**
     * Replaces automatic database login by actual value
     *
     * @param string $force_install_databaserootlogin Login
     *
     * @return string
     */
    function parse_database_login($force_install_databaserootlogin)
    {
        return preg_replace('/__SUPERUSERLOGIN__/', 'root', $force_install_databaserootlogin);
    }

    /**
     * Replaces automatic database password by actual value
     *
     * @param string $force_install_databaserootpass Password
     *
     * @return string
     */
    function parse_database_pass($force_install_databaserootpass)
    {
        return preg_replace('/__SUPERUSERPASSWORD__/', '', $force_install_databaserootpass);
    }

    /**
     * Automatically detect Dolibarr's main data root
     *
     * @param string $this ->dolibarr_main_document_root Current main document root
     *
     * @return string
     */
    function detect_dolibarr_main_data_root($dolibarr_main_document_root)
    {
        $dolibarr_main_data_root = preg_replace("/\/htdocs$/", "", $dolibarr_main_document_root);
        $dolibarr_main_data_root .= "/documents";
        return $dolibarr_main_data_root;
    }

    /**
     * Automatically detect Dolibarr's main URL root
     *
     * @return string
     */
    function detect_dolibarr_main_url_root()
    {
        // If defined (Ie: Apache with Linux)
        if (isset($_SERVER["SCRIPT_URI"])) {
            $dolibarr_main_url_root = $_SERVER["SCRIPT_URI"];
        } elseif (isset($_SERVER["SERVER_URL"]) && isset($_SERVER["DOCUMENT_URI"])) {
            // If defined (Ie: Apache with Caudium)
            $dolibarr_main_url_root = $_SERVER["SERVER_URL"] . $_SERVER["DOCUMENT_URI"];
        } else {
            // If SCRIPT_URI, SERVER_URL, DOCUMENT_URI not defined (Ie: Apache 2.0.44 for Windows)
            $proto = ((!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == 'on') || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? 'https' : 'http';
            if (!empty($_SERVER["HTTP_HOST"])) {
                $serverport = $_SERVER["HTTP_HOST"];
            } elseif (!empty($_SERVER["SERVER_NAME"])) {
                $serverport = $_SERVER["SERVER_NAME"];
            } else {
                $serverport = 'localhost';
            }
            $dolibarr_main_url_root = $proto . "://" . $serverport . $_SERVER["SCRIPT_NAME"];
        }
        // Clean proposed URL
        // We assume /install to be under /htdocs, so we get the parent path of the current URL
        $dolibarr_main_url_root = dirname(dirname($dolibarr_main_url_root));

        return $dolibarr_main_url_root;
    }

    public function _body()
    {
        $this->selectLang = filter_input(INPUT_POST, 'selectlang');
        if (empty($this->selectLang)) {
            $this->selectLang = $this->lang->getDefaultLang();
        }
        $this->lang->setDefaultLang($this->selectLang);
        $this->lang->loadLangs(['main', 'admin', 'install', 'errors']);

        return parent::body();
    }

    /**
     * Main installation/update screen that allows to select the language
     * if it was not previously selected.
     *
     * @return bool
     */
    public function doIndex(): bool
    {
        $this->langs->setDefaultLang('auto');
        $this->langs->loadLangs(['main', 'admin', 'install']);

        if (isset($this->config->main_url_root)) {
            return $this->doChecked();
        }

        $this->template = 'install/install';
        $this->nextButton = true;

        $form = new FormAdmin(null);
        $this->htmlComboLanguages = $form->select_language('auto', 'selectlang', 1, 0, 0, 1);

        return true;
    }

    /**
     * Perform a needs check for the application to determine if it meets all the
     * essential requirements.
     *
     * @return bool
     */
    public function doChecked(): bool
    {
        $this->selectLang = $_POST['selectlang'];
        $this->langs->setDefaultLang('auto');
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
        $conffile = Config::getDolibarrConfigFilename();

        clearstatcache();
        if (is_readable($conffile) && filesize($conffile) > 8) {
            $this->syslog("check: conf file '" . $conffile . "' already defined");
            return ['status' => Status::OK];
        }

        // If not, we create it
        $this->syslog("check: we try to create conf file '" . $conffile . "'");

        // First we try by copying example
        if (@copy($conffile . ".example", $conffile)) {
            // Success
            $this->syslog("check: successfully copied file " . $conffile . ".example into " . $conffile);
            return ['status' => Status::OK];
        }

        // If failed, we try to create an empty file
        $this->syslog("check: failed to copy file " . $conffile . ".example into " . $conffile . ". We try to create it.", LOG_WARNING);

        $fp = @fopen($conffile, "w");
        if ($fp) {
            @fwrite($fp, '<?php');
            @fwrite($fp, "\n");
            if (fclose($fp)) {
                return ['status' => Status::OK];
            }
        }

        $this->syslog("check: failed to create a new file " . $conffile . " into current dir " . getcwd() . ". Please check permissions.", LOG_ERR);
        return [
            'status' => Status::FAIL,
            'text' => $this->langs->trans('ConfFileDoesNotExistsAndCouldNotBeCreated', 'conf.php')
        ];
    }

    private function setIcons(array $checks): bool
    {
        $ok = true;
        $this->checks = [];
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
            $this->checks[] = $value;
        }

        if (!$ok) {
            $this->checks[] = [
                'ok' => false,
                'icon' => 'error',
                'text' => $this->langs->trans('ErrorGoBackAndCorrectParameters'),
            ];
        }

        return $ok;
    }

    public function checkIfWritable()
    {
        $conffile = Config::getDolibarrConfigFilename();

        if (is_dir($conffile)) {
            return [
                'status' => Status::FAIL,
                'text' => $this->langs->trans('ConfFileMustBeAFileNotADir', $conffile),
            ];
        }

        $this->allowInstall = is_writable($conffile);
        if (!$this->allowInstall) {
            return [
                'status' => Status::FAIL,
                'text' => $this->langs->trans('ConfFileIsNotWritable', $conffile),
            ];
        }

        return [
            'status' => Status::OK,
        ];
    }

    public function next()
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

        $this->availableChoices = [];
        $this->notAvailableChoices = [];

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

        $this->printVersion = getDolGlobalString('MAIN_VERSION_LAST_UPGRADE') || getDolGlobalString('MAIN_VERSION_LAST_INSTALL');

        $foundrecommandedchoice = 0;

        if (empty($dolibarr_main_db_host)) {    // This means install process was not run
            $foundrecommandedchoice = 1; // To show only once
        }

        /*
        $button = $this->allowInstall
            ? '<input class="button" type="submit" name="action" value="' . $this->langs->trans("Start") . '">'
            : ($foundrecommandedchoice ? '<span class="warning">' : '') . $this->langs->trans("InstallNotAllowed") . ($foundrecommandedchoice ? '</span>' : '');
        */

        // TODO: We have to see how we can use the action, and that the text is displayed in the correct language
        $button = $this->allowInstall
            ? '<input class="button" type="submit" name="action" value="start">' . $this->langs->trans("Start")
            : ($foundrecommandedchoice ? '<span class="warning">' : '') . $this->langs->trans("InstallNotAllowed") . ($foundrecommandedchoice ? '</span>' : '');

        // Show line of first install choice
        $choice = [
            'selected' => true,
            'short' => $this->langs->trans("FreshInstall"),
            'long' => $this->langs->trans("FreshInstallDesc"),
            'active' => $this->allowInstall,
            'button' => $button,
        ];

        if (!isset($config->main_db_host) || empty($config->main_db_host)) {
            $choice['long'] .= '<br><div class="center"><div class="ok suggestedchoice">' . $this->langs->trans("InstallChoiceSuggested") . '</div></div>';
        }

        $this->availableChoices[] = $choice;

        $positionkey = ($foundrecommandedchoice ? 999 : 0);
        if ($this->allowInstall) {
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

        $this->errorMigrations = false;
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
                    $button = '<a class="button runupgrade" href="upgrade.php?action=upgrade' . ($count < count($migrationscript) ? '_' . $versionto : '') . '&selectlang=' . $this->selectLang . '&versionfrom=' . $versionfrom . '&versionto=' . $versionto . '">' . $this->langs->trans("Start") . '</a>';
                }
            }

            $choice = [
                'selected' => $recommended_choice,
                'short' => $this->langs->trans("Upgrade") . '<br>' . $newversionfrom . $newversionfrombis . ' -> ' . $newversionto,
                'long' => $this->langs->trans("UpgradeDesc"),
                'active' => $this->allowInstall,
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
                $this->availableChoices[$count] = $choice;
            } else {
                $this->notAvailableChoices[$count] = $choice;
            }
        }

        // If there is no choice at all, we show all of them.
        if (empty($this->availableChoices)) {
            $this->availableChoices = $this->notAvailableChoices;
            $this->notAvailableChoices = [];
        }

        // Array of install choices
        krsort($this->availableChoices, SORT_NATURAL);
    }

    private function getMigrationScript()
    {
        $dir = BASE_PATH . "/../Dolibarr/Modules/Install/mysql/migration/";   // We use mysql migration scripts whatever is database driver
        dolibarr_install_syslog("Scan sql files for migration files in " . $dir);

        // Get files list of migration file x.y.z-a.b.c.sql into /install/mysql/migration
        $migrationscript = [];
        $handle = opendir($dir);
        if (!is_resource($handle)) {
            $this->errorMigrations = $this->langs->trans("ErrorCanNotReadDir", $dir);
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

        $this->template = 'install/step1';
        $this->nextButton = true;

        $oldConf = $this->config;
        $this->refreshConfigFromPost();

        $this->dolibarr_main_distrib = 'standard';

        session_start(); // To be able to keep info into session (used for not losing password during navigation. The password must not transit through parameters)

        // Save a flag to tell to restore input value if we go back
        $_SESSION['dol_save_pass'] = $this->db_pass;
        //$_SESSION['dol_save_passroot']=$passroot;

        $conffile = Config::getDolibarrConfigFilename();

        /*
        // Now we load forced values from install.forced.php file.
        $useforcedwizard = false;
        $forcedfile = "./install.forced.php";
        if ($conffile == "/etc/dolibarr/conf.php") {
            $forcedfile = "/etc/dolibarr/install.forced.php";
        }
        if (@file_exists($forcedfile)) {
            $useforcedwizard = true;
            include_once $forcedfile;
            // If forced install is enabled, replace the post values. These are empty because form fields are disabled.
            if ($force_install_noedit) {
                $this->main_dir = detect_dolibarr_main_document_root();
                if (!empty($argv[3])) {
                    $this->main_dir = $argv[3]; // override when executing the script in command line
                }
                if (!empty($force_install_main_data_root)) {
                    $this->main_data_dir = $force_install_main_data_root;
                } else {
                    $this->main_data_dir = detect_dolibarr_main_data_root($this->main_dir);
                }
                if (!empty($argv[4])) {
                    $this->main_data_dir = $argv[4]; // override when executing the script in command line
                }
                $this->main_url = detect_dolibarr_main_url_root();
                if (!empty($argv[5])) {
                    $this->main_url = $argv[5]; // override when executing the script in command line
                }

                if (!empty($force_install_databaserootlogin)) {
                    $userroot = parse_database_login($force_install_databaserootlogin);
                }
                if (!empty($argv[6])) {
                    $userroot = $argv[6]; // override when executing the script in command line
                }
                if (!empty($force_install_databaserootpass)) {
                    $passroot = parse_database_pass($force_install_databaserootpass);
                }
                if (!empty($argv[7])) {
                    $passroot = $argv[7]; // override when executing the script in command line
                }
            }
            if ($force_install_noedit == 2) {
                if (!empty($force_install_type)) {
                    $this->db_type = $force_install_type;
                }
                if (!empty($force_install_dbserver)) {
                    $this->db_host = $force_install_dbserver;
                }
                if (!empty($force_install_database)) {
                    $this->db_name = $force_install_database;
                }
                if (!empty($force_install_databaselogin)) {
                    $this->db_user = $force_install_databaselogin;
                }
                if (!empty($force_install_databasepass)) {
                    $this->db_pass = $force_install_databasepass;
                }
                if (!empty($force_install_port)) {
                    $this->db_port = $force_install_port;
                }
                if (!empty($force_install_prefix)) {
                    $this->db_prefix = $force_install_prefix;
                }
                if (!empty($force_install_createdatabase)) {
                    $this->db_create_database = $force_install_createdatabase;
                }
                if (!empty($force_install_createuser)) {
                    $this->db_create_user = $force_install_createuser;
                }
                if (!empty($force_install_mainforcehttps)) {
                    $this->main_force_https = $force_install_mainforcehttps;
                }
            }

            if (!empty($force_install_distrib)) {
                $this->dolibarr_main_distrib = $force_install_distrib;
            }
        }
        */

        dolibarr_install_syslog("--- step1: entering step1.php page");

        // Test if we can run a first install process
        if (!is_writable($conffile)) {
            $this->template = 'install/step1-error';
            $this->errorMessage = $this->langs->trans("ConfFileIsNotWritable", $conffile);
            return false;
        }

        $errors = [];

        // Check parameters
        $is_sqlite = false;

        if (empty($this->db_type)) {
            $errors[] = $this->langs->trans("ErrorFieldRequired", $this->langs->transnoentities("DatabaseType"));
        } else {
            $is_sqlite = ($this->db_type === 'sqlite' || $this->db_type === 'sqlite3');
        }

        if (empty($this->db_host) && !$is_sqlite) {
            $errors[] = $this->langs->trans("ErrorFieldRequired", $this->langs->transnoentities("Server"));
        }

        if (empty($this->db_name)) {
            $errors[] = $this->langs->trans("ErrorFieldRequired", $this->langs->transnoentities("DatabaseName"));
        }

        if (empty($this->db_user) && !$is_sqlite) {
            $errors[] = $this->langs->trans("ErrorFieldRequired", $this->langs->transnoentities("Login"));
        }

        if (!empty($this->db_port) && !is_numeric($this->db_port)) {
            $errors[] = $this->langs->trans("ErrorBadValueForParameter", $this->db_port, $this->langs->transnoentities("Port"));
        }

        if (!empty($this->db_prefix) && !preg_match('/^[a-z0-9]+_$/i', $this->db_prefix)) {
            $errors[] = $this->langs->trans("ErrorBadValueForParameter", $this->db_prefix, $this->langs->transnoentities("DatabasePrefix"));
        }

        $this->main_dir = dol_sanitizePathName($this->main_dir);
        $this->main_data_dir = dol_sanitizePathName($this->main_data_dir);

        if (!filter_var($this->main_url, FILTER_VALIDATE_URL)) {
            $errors[] = $this->langs->trans("ErrorBadValueForParameter", $this->main_url, $this->langs->transnoentitiesnoconv("URLRoot"));
        }

        // Remove last / into dans main_dir
        if (substr($this->main_dir, dol_strlen($this->main_dir) - 1) == "/") {
            $this->main_dir = substr($this->main_dir, 0, dol_strlen($this->main_dir) - 1);
        }

        // Remove last / into dans main_url
        if (!empty($this->main_url) && substr($this->main_url, dol_strlen($this->main_url) - 1) == "/") {
            $this->main_url = substr($this->main_url, 0, dol_strlen($this->main_url) - 1);
        }

        $enginesDir = realpath(BASE_PATH . '/../Core/DB/Engines') . '/';
        if (!Files::dol_is_dir($enginesDir)) {
            $errors[] = $this->langs->trans("ErrorBadValueForParameter", $this->main_dir, $this->langs->transnoentitiesnoconv("WebPagesDirectory"));
        }

        $this->errors = $errors;
        $error = count($errors) > 0;

        // Test database connection
        if (!$error) {
            $result = @include_once $enginesDir . $this->db_type . '.php';

            if ($result) {
                // If we require database or user creation we need to connect as root, so we need root login credentials
                if (!empty($this->db_create_database) && !$userroot) {
                    print '
        <div class="error">' . $this->langs->trans("YouAskDatabaseCreationSoDolibarrNeedToConnect", $this->db_name) . '</div>
        ';
                    print '<br>';
                    print $this->langs->trans("BecauseConnectionFailedParametersMayBeWrong") . '<br><br>';
                    print $this->langs->trans("ErrorGoBackAndCorrectParameters");
                    $error++;
                }
                if (!empty($this->db_create_user) && !$userroot) {
                    print '
        <div class="error">' . $this->langs->trans("YouAskLoginCreationSoDolibarrNeedToConnect", $this->db_user) . '</div>
        ';
                    print '<br>';
                    print $this->langs->trans("BecauseConnectionFailedParametersMayBeWrong") . '<br><br>';
                    print $this->langs->trans("ErrorGoBackAndCorrectParameters");
                    $error++;
                }

                // If we need root access
                if (!$error && (!empty($this->db_create_database) || !empty($this->db_create_user))) {
                    $databasefortest = $this->db_name;
                    if (!empty($this->db_create_database)) {
                        if ($this->db_type == 'mysql' || $this->db_type == 'mysqli') {
                            $databasefortest = 'mysql';
                        } elseif ($this->db_type == 'pgsql') {
                            $databasefortest = 'postgres';
                        } else {
                            $databasefortest = 'master';
                        }
                    }

                    $db = getDoliDBInstance($this->db_type, $this->db_host, $userroot, $passroot, $databasefortest, (int)$this->db_port);

                    dol_syslog("databasefortest=" . $databasefortest . " connected=" . $db->connected . " database_selected=" . $db->database_selected, LOG_DEBUG);
                    //print "databasefortest=".$databasefortest." connected=".$db->connected." database_selected=".$db->database_selected;

                    if (empty($this->db_create_database) && $db->connected && !$db->database_selected) {
                        print '
        <div class="error">' . $this->langs->trans("ErrorConnectedButDatabaseNotFound", $this->db_name) . '</div>
        ';
                        print '<br>';
                        if (!$db->connected) {
                            print $this->langs->trans("IfDatabaseNotExistsGoBackAndUncheckCreate") . '<br><br>';
                        }
                        print $this->langs->trans("ErrorGoBackAndCorrectParameters");
                        $error++;
                    } elseif ($db->error && !(!empty($this->db_create_database) && $db->connected)) {
                        // Note: you may experience error here with message "No such file or directory" when mysql was installed for the first time but not yet launched.
                        if ($db->error == "No such file or directory") {
                            print '
        <div class="error">' . $this->langs->trans("ErrorToConnectToMysqlCheckInstance") . '</div>
        ';
                        } else {
                            print '
        <div class="error">' . $db->error . '</div>
        ';
                        }
                        if (!$db->connected) {
                            print $this->langs->trans("BecauseConnectionFailedParametersMayBeWrong") . '<br><br>';
                        }
                        //print '<a href="#" onClick="javascript: history.back();">';
                        print $this->langs->trans("ErrorGoBackAndCorrectParameters");
                        //print '</a>';
                        $error++;
                    }
                }

                // If we need simple access
                if (!$error && (empty($this->db_create_database) && empty($this->db_create_user))) {
                    $db = getDoliDBInstance($this->db_type, $this->db_host, $this->db_user, $this->db_pass, $this->db_name, (int)$this->db_port);

                    if ($db->error) {
                        print '
        <div class="error">' . $db->error . '</div>
        ';
                        if (!$db->connected) {
                            print $this->langs->trans("BecauseConnectionFailedParametersMayBeWrong") . '<br><br>';
                        }
                        //print '<a href="#" onClick="javascript: history.back();">';
                        print $this->langs->trans("ErrorGoBackAndCorrectParameters");
                        //print '</a>';
                        $error++;
                    }
                }
            } else {
                print "<br>\nFailed to include_once(\"" . $enginesDir . $this->db_type . ".class.php\")<br>\n";
                print '
        <div class="error">' . $this->langs->trans(
                        "ErrorWrongValueForParameter",
                        $this->langs->transnoentities("WebPagesDirectory")) . '
        </div>
        ';
                //print '<a href="#" onClick="javascript: history.back();">';
                print $this->langs->trans("ErrorGoBackAndCorrectParameters");
                //print '</a>';
                $error++;
            }
        } else {
            if (isset($db)) {
                print $db->lasterror();
            }
            if (isset($db) && !$db->connected) {
                print '<br>' . $this->langs->trans("BecauseConnectionFailedParametersMayBeWrong") . '<br><br>';
            }
            print $this->langs->trans("ErrorGoBackAndCorrectParameters");
            $error++;
        }

        if (!$error && $db->connected) {
            if (!empty($this->db_create_database)) {
                $result = $db->select_db($this->db_name);
                if ($result) {
                    print '<div class="error">' . $this->langs->trans("ErrorDatabaseAlreadyExists", $this->db_name) . '</div>';
                    print $this->langs->trans("IfDatabaseExistsGoBackAndCheckCreate") . '<br><br>';
                    print $this->langs->trans("ErrorGoBackAndCorrectParameters");
                    $error++;
                }
            }
        }

// Define $defaultCharacterSet and $defaultDBSortingCollation
        if (!$error && $db->connected) {
            if (!empty($this->db_create_database)) {    // If we create database, we force default value
                // Default values come from the database handler

                $defaultCharacterSet = $db->forcecharset;
                $defaultDBSortingCollation = $db->forcecollate;
            } else { // If already created, we take current value
                $defaultCharacterSet = $db->getDefaultCharacterSetDatabase();
                $defaultDBSortingCollation = $db->getDefaultCollationDatabase();
            }

            // It seems some PHP driver mysqli does not support utf8mb3
            if ($defaultCharacterSet == 'utf8mb3' || $defaultDBSortingCollation == 'utf8mb3_unicode_ci') {
                $defaultCharacterSet = 'utf8';
                $defaultDBSortingCollation = 'utf8_unicode_ci';
            }
            // Force to avoid utf8mb4 because index on field char 255 reach limit of 767 char for indexes (example with mysql 5.6.34 = mariadb 10.0.29)
            // TODO Remove this when utf8mb4 is supported
            if ($defaultCharacterSet == 'utf8mb4' || $defaultDBSortingCollation == 'utf8mb4_unicode_ci') {
                $defaultCharacterSet = 'utf8';
                $defaultDBSortingCollation = 'utf8_unicode_ci';
            }

            print '<input type="hidden" name="dolibarr_main_db_character_set" value="' . $defaultCharacterSet . '">';
            print '<input type="hidden" name="dolibarr_main_db_collation" value="' . $defaultDBSortingCollation . '">';
            $this->db_character_set = $defaultCharacterSet;
            $this->db_collation = $defaultDBSortingCollation;
            dolibarr_install_syslog("step1: db_character_set=" . $this->db_character_set . " db_collation=" . $this->db_collation);
        }

// Create config file
        if (!$error && $db->connected) {
            umask(0);
            if (is_array($_POST)) {
                foreach ($_POST as $key => $value) {
                    if (!preg_match('/^db_pass/i', $key)) {
                        dolibarr_install_syslog("step1: choice for " . $key . " = " . $value);
                    }
                }
            }

            // Show title of step
            print '<h3><img class="valignmiddle inline-block paddingright" src="' . $this->config->file->main_url . '/' . $this->config->main->theme . '/img/gear.svg" width="20" alt="Configuration"> ' . $this->langs->trans("ConfigurationFile") . '</h3>';
            print '<table cellspacing="0" width="100%" cellpadding="1" border="0">';

            // Check parameter main_dir
            if (!$error) {
                if (!is_dir($this->main_dir)) {
                    dolibarr_install_syslog("step1: directory '" . $this->main_dir . "' is unavailable or can't be accessed");

                    print "<tr><td>";
                    print $this->langs->trans("ErrorDirDoesNotExists", $this->main_dir) . '<br>';
                    print $this->langs->trans("ErrorWrongValueForParameter", $this->langs->transnoentitiesnoconv("WebPagesDirectory")) . '<br>';
                    print $this->langs->trans("ErrorGoBackAndCorrectParameters") . '<br><br>';
                    print '</td><td>';
                    print $this->langs->trans("Error");
                    print "</td></tr>";
                    $error++;
                }
            }

            if (!$error) {
                dolibarr_install_syslog("step1: directory '" . $this->main_dir . "' exists");
            }


            // Create subdirectory main_data_dir
            if (!$error) {
                // Create directory for documents
                if (!is_dir($this->main_data_dir)) {
                    dol_mkdir($this->main_data_dir);
                }

                if (!is_dir($this->main_data_dir)) {
                    print "<tr><td>" . $this->langs->trans("ErrorDirDoesNotExists", $this->main_data_dir);
                    print ' ' . $this->langs->trans("YouMustCreateItAndAllowServerToWrite");
                    print '</td><td>';
                    print '<span class="error">' . $this->langs->trans("Error") . '</span>';
                    print "</td></tr>";
                    print '<tr><td colspan="2"><br>' . $this->langs->trans("CorrectProblemAndReloadPage", $_SERVER['PHP_SELF'] . '?testget=ok') . '</td></tr>';
                    $error++;
                } else {
                    // Create .htaccess file in document directory
                    $pathhtaccess = $this->main_data_dir . '/.htaccess';
                    if (!file_exists($pathhtaccess)) {
                        dolibarr_install_syslog("step1: .htaccess file did not exist, we created it in '" . $this->main_data_dir . "'");
                        $handlehtaccess = @fopen($pathhtaccess, 'w');
                        if ($handlehtaccess) {
                            fwrite($handlehtaccess, 'Order allow,deny' . "\n");
                            fwrite($handlehtaccess, 'Deny from all' . "\n");

                            fclose($handlehtaccess);
                            dolibarr_install_syslog("step1: .htaccess file created");
                        }
                    }

                    // Documents are stored above the web pages root to prevent being downloaded without authentication
                    $dir = [];
                    $dir[] = $this->main_data_dir . "/mycompany";
                    $dir[] = $this->main_data_dir . "/medias";
                    $dir[] = $this->main_data_dir . "/users";
                    $dir[] = $this->main_data_dir . "/facture";
                    $dir[] = $this->main_data_dir . "/propale";
                    $dir[] = $this->main_data_dir . "/ficheinter";
                    $dir[] = $this->main_data_dir . "/produit";
                    $dir[] = $this->main_data_dir . "/doctemplates";

                    // Loop on each directory of dir [] to create them if they do not exist
                    $num = count($dir);
                    for ($i = 0; $i < $num; $i++) {
                        if (is_dir($dir[$i])) {
                            dolibarr_install_syslog("step1: directory '" . $dir[$i] . "' exists");
                        } else {
                            if (dol_mkdir($dir[$i]) < 0) {
                                $this->errors[] = $this->langs->trans('ErrorFailToCreateDir', $dir[$i]);
                            } else {
                                dolibarr_install_syslog("step1: directory '" . $dir[$i] . "' created");
                            }
                        }
                    }

                    // Copy directory medias
                    $srcroot = $this->main_dir . '/install/medias';
                    $destroot = $this->main_data_dir . '/medias';
                    Files::dolCopyDir($srcroot, $destroot, 0, 0);

                    if ($error) {
                        print "<tr><td>" . $this->langs->trans("ErrorDirDoesNotExists", $this->main_data_dir);
                        print ' ' . $this->langs->trans("YouMustCreateItAndAllowServerToWrite");
                        print '</td><td>';
                        print '<span class="error">' . $this->langs->trans("Error") . '</span>';
                        print "</td></tr>";
                        print '<tr><td colspan="2"><br>' . $this->langs->trans("CorrectProblemAndReloadPage", $_SERVER['PHP_SELF'] . '?testget=ok') . '</td></tr>';
                    } else {
                        //ODT templates
                        $srcroot = $this->main_dir . '/Install/DocTemplates';
                        $destroot = $this->main_data_dir . '/doctemplates';
                        $docs = [
                            'contracts' => 'contract',
                            'invoices' => 'invoice',
                            'orders' => 'order',
                            'products' => 'product',
                            'projects' => 'project',
                            'proposals' => 'proposal',
                            'shipments' => 'shipment',
                            'supplier_proposals' => 'supplier_proposal',
                            'tasks' => 'task_summary',
                            'thirdparties' => 'thirdparty',
                            'usergroups' => 'usergroups',
                            'users' => 'user',
                        ];
                        foreach ($docs as $cursordir => $cursorfile) {
                            $src = $srcroot . '/' . $cursordir . '/template_' . $cursorfile . '.odt';
                            $dirodt = $destroot . '/' . $cursordir;
                            $dest = $dirodt . '/template_' . $cursorfile . '.odt';

                            dol_mkdir($dirodt);
                            $result = Files::dol_copy($src, $dest, 0, 0);
                            if ($result < 0) {
                                $this->errors[] = $this->langs->trans('ErrorFailToCopyFile', $src, $dest);
                            }
                        }
                    }
                }
            }

            // Table prefix
            $this->main_db_prefix = (!empty($this->db_prefix) ? $this->db_prefix : 'llx_');

            // Write conf file on disk
            if (!$error) {
                // Save old conf file on disk
                if (file_exists("$conffile")) {
                    // We must ignore errors as an existing old file may already exist and not be replaceable or
                    // the installer (like for ubuntu) may not have permission to create another file than conf.php.
                    // Also no other process must be able to read file or we expose the new file, so content with password.
                    @Files::dol_copy($conffile, $conffile . '.old', '0400');
                }

                $error += $this->write_conf_file($conffile);
            }

            // Create database and admin user database
            if (!$error) {
                // We reload configuration file
                $this->conf();

                print '<tr><td>';
                print $this->langs->trans("ConfFileReload");
                print '</td>';
                print '<td><img src="' . $this->config->file->main_url . '/Templates/theme/' . $this->config->main->theme . '/img/ok.png" alt="Ok"></td></tr>';

                // Create database user if requested
                if (isset($this->db_create_user) && ($this->db_create_user == "1" || $this->db_create_user == "on")) {
                    dolibarr_install_syslog("step1: create database user: " . $dolibarr_main_db_user);

                    //print $this->conf->db->host." , ".$this->conf->db->name." , ".$this->conf->db->user." , ".$this->conf->db->port;
                    $databasefortest = $this->conf->db->name;
                    if ($this->conf->db->type == 'mysql' || $this->conf->db->type == 'mysqli') {
                        $databasefortest = 'mysql';
                    } elseif ($this->conf->db->type == 'pgsql') {
                        $databasefortest = 'postgres';
                    } elseif ($this->conf->db->type == 'mssql') {
                        $databasefortest = 'master';
                    }

                    // Check database connection

                    $db = getDoliDBInstance($this->conf->db->type, $this->conf->db->host, $userroot, $passroot, $databasefortest, (int)$this->conf->db->port);

                    if ($db->error) {
                        print '<div class="error">' . $db->error . '</div>';
                        $error++;
                    }

                    if (!$error) {
                        if ($db->connected) {
                            $resultbis = 1;

                            if (empty($dolibarr_main_db_pass)) {
                                dolibarr_install_syslog("step1: failed to create user, password is empty", LOG_ERR);
                                print '<tr><td>';
                                print $this->langs->trans("UserCreation") . ' : ';
                                print $dolibarr_main_db_user;
                                print '</td>';
                                print '<td>' . $this->langs->trans("Error") . ": A password for database user is mandatory.</td></tr>";
                            } else {
                                // Create user
                                $result = $db->DDLCreateUser($dolibarr_main_db_host, $dolibarr_main_db_user, $dolibarr_main_db_pass, $dolibarr_main_db_name);

                                // Create user bis
                                if ($databasefortest == 'mysql') {
                                    if (!in_array($dolibarr_main_db_host, ['127.0.0.1', '::1', 'localhost', 'localhost.local'])) {
                                        $resultbis = $db->DDLCreateUser('%', $dolibarr_main_db_user, $dolibarr_main_db_pass, $dolibarr_main_db_name);
                                    }
                                }

                                if ($result > 0 && $resultbis > 0) {
                                    print '<tr><td>';
                                    print $this->langs->trans("UserCreation") . ' : ';
                                    print $dolibarr_main_db_user;
                                    print '</td>';
                                    print '<td><img src="' . $this->config->file->main_url . '/Templates/theme/' . $this->config->main->theme . '/img/ok.png" alt="Ok"></td></tr>';
                                } else {
                                    if (
                                        $db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS'
                                        || $db->errno() == 'DB_ERROR_KEY_NAME_ALREADY_EXISTS'
                                        || $db->errno() == 'DB_ERROR_USER_ALREADY_EXISTS'
                                    ) {
                                        dolibarr_install_syslog("step1: user already exists");
                                        print '<tr><td>';
                                        print $this->langs->trans("UserCreation") . ' : ';
                                        print $dolibarr_main_db_user;
                                        print '</td>';
                                        print '<td>' . $this->langs->trans("LoginAlreadyExists") . '</td></tr>';
                                    } else {
                                        dolibarr_install_syslog("step1: failed to create user", LOG_ERR);
                                        print '<tr><td>';
                                        print $this->langs->trans("UserCreation") . ' : ';
                                        print $dolibarr_main_db_user;
                                        print '</td>';
                                        print '<td>' . $this->langs->trans("Error") . ': ' . $db->errno() . ' ' . $db->error() . ($db->error ? '. ' . $db->error : '') . "</td></tr>";
                                    }
                                }
                            }

                            $db->close();
                        } else {
                            print '<tr><td>';
                            print $this->langs->trans("UserCreation") . ' : ';
                            print $dolibarr_main_db_user;
                            print '</td>';
                            print '<td><img src="' . $this->config->file->main_url . '/Templates/theme/' . $this->config->main->theme . '/img/error..png" alt="Error"></td>';
                            print '</tr>';

                            // warning message due to connection failure
                            print '<tr><td colspan="2"><br>';
                            print $this->langs->trans("YouAskDatabaseCreationSoDolibarrNeedToConnect", $dolibarr_main_db_user, $dolibarr_main_db_host, $userroot);
                            print '<br>';
                            print $this->langs->trans("BecauseConnectionFailedParametersMayBeWrong") . '<br><br>';
                            print $this->langs->trans("ErrorGoBackAndCorrectParameters") . '<br><br>';
                            print '</td></tr>';

                            $error++;
                        }
                    }
                }   // end of user account creation


                // If database creation was asked, we create it
                if (!$error && (isset($this->db_create_database) && ($this->db_create_database == "1" || $this->db_create_database == "on"))) {
                    dolibarr_install_syslog("step1: create database: " . $dolibarr_main_db_name . " " . $dolibarr_main_db_character_set . " " . $dolibarr_main_db_collation . " " . $dolibarr_main_db_user);
                    $newdb = getDoliDBInstance($this->conf->db->type, $this->conf->db->host, $userroot, $passroot, '', (int)$this->conf->db->port);
                    //print 'eee'.$this->conf->db->type." ".$this->conf->db->host." ".$userroot." ".$passroot." ".$this->conf->db->port." ".$newdb->connected." ".$newdb->forcecharset;exit;

                    if ($newdb->connected) {
                        $result = $newdb->DDLCreateDb($dolibarr_main_db_name, $dolibarr_main_db_character_set, $dolibarr_main_db_collation, $dolibarr_main_db_user);

                        if ($result) {
                            print '<tr><td>';
                            print $this->langs->trans("DatabaseCreation") . " (" . $this->langs->trans("User") . " " . $userroot . ") : ";
                            print $dolibarr_main_db_name;
                            print '</td>';
                            print '<td><img src="' . $this->config->file->main_url . '/Templates/theme/' . $this->config->main->theme . '/img/ok.png" alt="Ok"></td></tr>';

                            $newdb->select_db($dolibarr_main_db_name);
                            $check1 = $newdb->getDefaultCharacterSetDatabase();
                            $check2 = $newdb->getDefaultCollationDatabase();
                            dolibarr_install_syslog('step1: new database is using charset=' . $check1 . ' collation=' . $check2);

                            // If values differs, we save conf file again
                            //if ($check1 != $dolibarr_main_db_character_set) dolibarr_install_syslog('step1: value for character_set is not the one asked for database creation', LOG_WARNING);
                            //if ($check2 != $dolibarr_main_db_collation)     dolibarr_install_syslog('step1: value for collation is not the one asked for database creation', LOG_WARNING);
                        } else {
                            // warning message
                            print '<tr><td colspan="2"><br>';
                            print $this->langs->trans("ErrorFailedToCreateDatabase", $dolibarr_main_db_name) . '<br>';
                            print $newdb->lasterror() . '<br>';
                            print $this->langs->trans("IfDatabaseExistsGoBackAndCheckCreate");
                            print '<br>';
                            print '</td></tr>';

                            dolibarr_install_syslog('step1: failed to create database ' . $dolibarr_main_db_name . ' ' . $newdb->lasterrno() . ' ' . $newdb->lasterror(), LOG_ERR);
                            $error++;
                        }
                        $newdb->close();
                    } else {
                        print '<tr><td>';
                        print $this->langs->trans("DatabaseCreation") . " (" . $this->langs->trans("User") . " " . $userroot . ") : ";
                        print $dolibarr_main_db_name;
                        print '</td>';
                        print '<td><img src="' . $this->config->file->main_url . '/Templates/theme/' . $this->config->main->theme . '/img/error..png" alt="Error"></td>';
                        print '</tr>';

                        // warning message
                        print '<tr><td colspan="2"><br>';
                        print $this->langs->trans("YouAskDatabaseCreationSoDolibarrNeedToConnect", $dolibarr_main_db_user, $dolibarr_main_db_host, $userroot);
                        print '<br>';
                        print $this->langs->trans("BecauseConnectionFailedParametersMayBeWrong") . '<br><br>';
                        print $this->langs->trans("ErrorGoBackAndCorrectParameters") . '<br><br>';
                        print '</td></tr>';

                        $error++;
                    }
                }   // end of create database


                // We test access with dolibarr database user (not admin)
                if (!$error) {
                    dolibarr_install_syslog("step1: connection type=" . $this->conf->db->type . " on host=" . $this->conf->db->host . " port=" . $this->conf->db->port . " user=" . $this->conf->db->user . " name=" . $this->conf->db->name);
                    //print "connection de type=".$this->conf->db->type." sur host=".$this->conf->db->host." port=".$this->conf->db->port." user=".$this->conf->db->user." name=".$this->conf->db->name;

                    $db = getDoliDBInstance($this->conf->db->type, $this->conf->db->host, $this->conf->db->user, $this->conf->db->pass, $this->conf->db->name, (int)$this->conf->db->port);

                    if ($db->connected) {
                        dolibarr_install_syslog("step1: connection to server by user " . $this->conf->db->user . " ok");
                        print "<tr><td>";
                        print $this->langs->trans("ServerConnection") . " (" . $this->langs->trans("User") . " " . $this->conf->db->user . ") : ";
                        print $this->db_host;
                        print "</td><td>";
                        print '<img src="' . $this->config->file->main_url . '/Templates/theme/' . $this->config->main->theme . '/img/ok.png" alt="Ok">';
                        print "</td></tr>";

                        // server access ok, basic access ok
                        if ($db->database_selected) {
                            dolibarr_install_syslog("step1: connection to database " . $this->conf->db->name . " by user " . $this->conf->db->user . " ok");
                            print "<tr><td>";
                            print $this->langs->trans("DatabaseConnection") . " (" . $this->langs->trans("User") . " " . $this->conf->db->user . ") : ";
                            print $this->db_name;
                            print "</td><td>";
                            print '<img src="' . $this->config->file->main_url . '/Templates/theme/' . $this->config->main->theme . '/img/ok.png" alt="Ok">';
                            print "</td></tr>";

                            $error = 0;
                        } else {
                            dolibarr_install_syslog("step1: connection to database " . $this->conf->db->name . " by user " . $this->conf->db->user . " failed", LOG_ERR);
                            print "<tr><td>";
                            print $this->langs->trans("DatabaseConnection") . " (" . $this->langs->trans("User") . " " . $this->conf->db->user . ") : ";
                            print $this->db_name;
                            print '</td><td>';
                            print '<img src="' . $this->config->file->main_url . '/Templates/theme/' . $this->config->main->theme . '/img/error..png" alt="Error">';
                            print "</td></tr>";

                            // warning message
                            print '<tr><td colspan="2"><br>';
                            print $this->langs->trans('CheckThatDatabasenameIsCorrect', $dolibarr_main_db_name) . '<br>';
                            print $this->langs->trans('IfAlreadyExistsCheckOption') . '<br>';
                            print $this->langs->trans("ErrorGoBackAndCorrectParameters") . '<br><br>';
                            print '</td></tr>';

                            $error++;
                        }
                    } else {
                        dolibarr_install_syslog("step1: connection to server by user " . $this->conf->db->user . " failed", LOG_ERR);
                        print "<tr><td>";
                        print $this->langs->trans("ServerConnection") . " (" . $this->langs->trans("User") . " " . $this->conf->db->user . ") : ";
                        print $this->db_host;
                        print '</td><td>';
                        print '<img src="' . $this->config->file->main_url . '/Templates/theme/' . $this->config->main->theme . '/img/error..png" alt="Error">';
                        print "</td></tr>";

                        // warning message
                        print '<tr><td colspan="2"><br>';
                        print $this->langs->trans("ErrorConnection", $this->conf->db->host, $this->conf->db->name, $this->conf->db->user);
                        print $this->langs->trans('IfLoginDoesNotExistsCheckCreateUser') . '<br>';
                        print $this->langs->trans("ErrorGoBackAndCorrectParameters") . '<br><br>';
                        print '</td></tr>';

                        $error++;
                    }
                }
            }

            print '</table>';
        }

        $ret = 0;
        if ($error && isset($argv[1])) {
            $ret = 1;
        }
        dolibarr_install_syslog("Exit " . $ret);

        dolibarr_install_syslog("--- step1: end");

// Return code if ran from command line
        if ($ret) {
            exit($ret);
        }


        return true;
    }

    /**
     *  Save configuration file. No particular permissions are set by installer.
     *
     * @param string $conffile Path to conf file to generate/update
     *
     * @return integer
     */
    function write_conf_file($conffile)
    {
        $error = 0;

        $config = Globals::getConfig();

        $key = md5(uniqid(mt_rand(), true)); // Generate random hash

        $fp = fopen("$conffile", "w");
        if ($fp) {
            clearstatcache();

            fwrite($fp, '<?php' . "\n");
            fwrite($fp, '//' . "\n");
            fwrite($fp, '// File generated by Dolibarr installer ' . DOL_VERSION . ' on ' . dol_print_date(dol_now(), '') . "\n");
            fwrite($fp, '//' . "\n");
            fwrite($fp, '// Take a look at conf.php.example file for an example of ' . $conffile . ' file' . "\n");
            fwrite($fp, '// and explanations for all possibles parameters.' . "\n");
            fwrite($fp, '//' . "\n");

            fwrite($fp, '$dolibarr_main_url_root=\'' . dol_escape_php(trim($this->main_url), 1) . '\';');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_main_document_root="' . dol_escape_php(dol_sanitizePathName(trim($this->main_dir))) . '";');
            fwrite($fp, "\n");

            fwrite($fp, $this->main_use_alt_dir . '$dolibarr_main_url_root_alt=\'' . dol_escape_php(trim("/" . $this->main_alt_dir_name), 1) . '\';');
            fwrite($fp, "\n");

            fwrite($fp, $this->main_use_alt_dir . '$dolibarr_main_document_root_alt="' . dol_escape_php(dol_sanitizePathName(trim($this->main_dir . "/" . $this->main_alt_dir_name))) . '";');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_main_data_root="' . dol_escape_php(dol_sanitizePathName(trim($this->main_data_dir))) . '";');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_main_db_host=\'' . dol_escape_php(trim($this->db_host), 1) . '\';');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_main_db_port=\'' . ((int)$this->db_port) . '\';');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_main_db_name=\'' . dol_escape_php(trim($this->db_name), 1) . '\';');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_main_db_prefix=\'' . dol_escape_php(trim($this->main_db_prefix), 1) . '\';');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_main_db_user=\'' . dol_escape_php(trim($this->db_user), 1) . '\';');
            fwrite($fp, "\n");
            fwrite($fp, '$dolibarr_main_db_pass=\'' . dol_escape_php(trim($this->db_pass), 1) . '\';');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_main_db_type=\'' . dol_escape_php(trim($this->db_type), 1) . '\';');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_main_db_character_set=\'' . dol_escape_php(trim($this->db_character_set), 1) . '\';');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_main_db_collation=\'' . dol_escape_php(trim($this->db_collation), 1) . '\';');
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

            fwrite($fp, '$dolibarr_main_force_https=\'' . dol_escape_php($this->main_force_https, 1) . '\';');
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

            // Write params to overwrites default lib path
            fwrite($fp, "\n");
            if (empty($force_dolibarr_lib_FPDF_PATH)) {
                fwrite($fp, '//');
                $force_dolibarr_lib_FPDF_PATH = '';
            }
            fwrite($fp, '$dolibarr_lib_FPDF_PATH="' . dol_escape_php(dol_sanitizePathName($force_dolibarr_lib_FPDF_PATH)) . '";');
            fwrite($fp, "\n");
            if (empty($force_dolibarr_lib_TCPDF_PATH)) {
                fwrite($fp, '//');
                $force_dolibarr_lib_TCPDF_PATH = '';
            }
            fwrite($fp, '$dolibarr_lib_TCPDF_PATH="' . dol_escape_php(dol_sanitizePathName($force_dolibarr_lib_TCPDF_PATH)) . '";');
            fwrite($fp, "\n");
            if (empty($force_dolibarr_lib_FPDI_PATH)) {
                fwrite($fp, '//');
                $force_dolibarr_lib_FPDI_PATH = '';
            }
            fwrite($fp, '$dolibarr_lib_FPDI_PATH="' . dol_escape_php(dol_sanitizePathName($force_dolibarr_lib_FPDI_PATH)) . '";');
            fwrite($fp, "\n");
            if (empty($force_dolibarr_lib_TCPDI_PATH)) {
                fwrite($fp, '//');
                $force_dolibarr_lib_TCPDI_PATH = '';
            }
            fwrite($fp, '$dolibarr_lib_TCPDI_PATH="' . dol_escape_php(dol_sanitizePathName($force_dolibarr_lib_TCPDI_PATH)) . '";');
            fwrite($fp, "\n");
            if (empty($force_dolibarr_lib_GEOIP_PATH)) {
                fwrite($fp, '//');
                $force_dolibarr_lib_GEOIP_PATH = '';
            }
            fwrite($fp, '$dolibarr_lib_GEOIP_PATH="' . dol_escape_php(dol_sanitizePathName($force_dolibarr_lib_GEOIP_PATH)) . '";');
            fwrite($fp, "\n");
            if (empty($force_dolibarr_lib_NUSOAP_PATH)) {
                fwrite($fp, '//');
                $force_dolibarr_lib_NUSOAP_PATH = '';
            }
            fwrite($fp, '$dolibarr_lib_NUSOAP_PATH="' . dol_escape_php(dol_sanitizePathName($force_dolibarr_lib_NUSOAP_PATH)) . '";');
            fwrite($fp, "\n");
            if (empty($force_dolibarr_lib_ODTPHP_PATH)) {
                fwrite($fp, '//');
                $force_dolibarr_lib_ODTPHP_PATH = '';
            }
            fwrite($fp, '$dolibarr_lib_ODTPHP_PATH="' . dol_escape_php(dol_sanitizePathName($force_dolibarr_lib_ODTPHP_PATH)) . '";');
            fwrite($fp, "\n");
            if (empty($force_dolibarr_lib_ODTPHP_PATHTOPCLZIP)) {
                fwrite($fp, '//');
                $force_dolibarr_lib_ODTPHP_PATHTOPCLZIP = '';
            }
            fwrite($fp, '$dolibarr_lib_ODTPHP_PATHTOPCLZIP="' . dol_escape_php(dol_sanitizePathName($force_dolibarr_lib_ODTPHP_PATHTOPCLZIP)) . '";');
            fwrite($fp, "\n");
            if (empty($force_dolibarr_js_CKEDITOR)) {
                fwrite($fp, '//');
                $force_dolibarr_js_CKEDITOR = '';
            }
            fwrite($fp, '$dolibarr_js_CKEDITOR=\'' . dol_escape_php($force_dolibarr_js_CKEDITOR, 1) . '\';');
            fwrite($fp, "\n");
            if (empty($force_dolibarr_js_JQUERY)) {
                fwrite($fp, '//');
                $force_dolibarr_js_JQUERY = '';
            }
            fwrite($fp, '$dolibarr_js_JQUERY=\'' . dol_escape_php($force_dolibarr_js_JQUERY, 1) . '\';');
            fwrite($fp, "\n");
            if (empty($force_dolibarr_js_JQUERY_UI)) {
                fwrite($fp, '//');
                $force_dolibarr_js_JQUERY_UI = '';
            }
            fwrite($fp, '$dolibarr_js_JQUERY_UI=\'' . dol_escape_php($force_dolibarr_js_JQUERY_UI, 1) . '\';');
            fwrite($fp, "\n");

            // Write params to overwrites default font path
            fwrite($fp, "\n");
            if (empty($force_dolibarr_font_DOL_DEFAULT_TTF)) {
                fwrite($fp, '//');
                $force_dolibarr_font_DOL_DEFAULT_TTF = '';
            }
            fwrite($fp, '$dolibarr_font_DOL_DEFAULT_TTF=\'' . dol_escape_php($force_dolibarr_font_DOL_DEFAULT_TTF, 1) . '\';');
            fwrite($fp, "\n");
            if (empty($force_dolibarr_font_DOL_DEFAULT_TTF_BOLD)) {
                fwrite($fp, '//');
                $force_dolibarr_font_DOL_DEFAULT_TTF_BOLD = '';
            }
            fwrite($fp, '$dolibarr_font_DOL_DEFAULT_TTF_BOLD=\'' . dol_escape_php($force_dolibarr_font_DOL_DEFAULT_TTF_BOLD, 1) . '\';');
            fwrite($fp, "\n");

            // Other
            fwrite($fp, '$dolibarr_main_distrib=\'' . dol_escape_php(trim($this->dolibarr_main_distrib), 1) . '\';');
            fwrite($fp, "\n");

            fclose($fp);

            if (file_exists("$conffile")) {
                include $conffile; // force config reload, do not put include_once
                $this->conf($this->main_dir);

                print "<tr><td>";
                print $this->langs->trans("SaveConfigurationFile");
                print ' <strong>' . $conffile . '</strong>';
                print "</td><td>";
                print '<img src="' . $this->config->file->main_url . '/' . $this->config->main->theme . '/img/svg/ok.png" alt="Ok">';
                print "</td></tr>";
            } else {
                $error++;
            }
        }

        return $error;
    }

    /**
     * Load conf file (file must exists)
     *
     * @param string $dolibarr_main_document_root Root directory of Dolibarr bin files
     *
     * @return  int                                             Return integer <0 if KO, >0 if OK
     */
    function conf($dolibarr_main_document_root = BASE_PATH)
    {
        $this->conf = Globals::getConf();
        $this->conf->db->type = trim($this->db_type);
        $this->conf->db->host = trim($this->db_host);
        $this->conf->db->port = trim($this->db_port);
        $this->conf->db->name = trim($this->db_name);
        $this->conf->db->user = trim($this->db_user);
        $this->conf->db->pass = (empty($this->db_pass) ? '' : trim($this->db_pass));

        if (empty($character_set_client)) {
            $character_set_client = "UTF-8";
        }
        $this->conf->file->character_set_client = strtoupper($character_set_client);
        // Unique id of instance
        $this->conf->file->instance_unique_id = empty($dolibarr_main_instance_unique_id) ? (empty($dolibarr_main_cookie_cryptkey) ? '' : $dolibarr_main_cookie_cryptkey) : $dolibarr_main_instance_unique_id;
        if (empty($dolibarr_main_db_character_set)) {
            $dolibarr_main_db_character_set = ($this->conf->db->type == 'MySqliEngine' ? 'utf8' : '');
        }
        $this->conf->db->character_set = $dolibarr_main_db_character_set;
        if (empty($dolibarr_main_db_collation)) {
            $dolibarr_main_db_collation = ($this->conf->db->type == 'MySqliEngine' ? 'utf8_unicode_ci' : '');
        }
        $this->conf->db->dolibarr_main_db_collation = $dolibarr_main_db_collation;
        if (empty($dolibarr_main_db_encryption)) {
            $dolibarr_main_db_encryption = 0;
        }
        $this->conf->db->dolibarr_main_db_encryption = $dolibarr_main_db_encryption;
        if (empty($dolibarr_main_db_cryptkey)) {
            $dolibarr_main_db_cryptkey = '';
        }
        $this->conf->db->dolibarr_main_db_cryptkey = $dolibarr_main_db_cryptkey;

        // Force usage of log file for install and upgrades
        $this->conf->modules['syslog'] = 'syslog';
        $this->conf->global->SYSLOG_LEVEL = constant('LOG_DEBUG');
        if (!defined('SYSLOG_HANDLERS')) {
            define('SYSLOG_HANDLERS', '["mod_syslog_file"]');
        }
        if (!defined('SYSLOG_FILE')) {  // To avoid warning on systems with constant already defined
            if (@is_writable('/tmp')) {
                define('SYSLOG_FILE', '/tmp/dolibarr_install.log');
            } elseif (!empty($_ENV["TMP"]) && @is_writable($_ENV["TMP"])) {
                define('SYSLOG_FILE', $_ENV["TMP"] . '/dolibarr_install.log');
            } elseif (!empty($_ENV["TEMP"]) && @is_writable($_ENV["TEMP"])) {
                define('SYSLOG_FILE', $_ENV["TEMP"] . '/dolibarr_install.log');
            } elseif (@is_writable('../../../../') && @file_exists('../../../../startdoliwamp.bat')) {
                define('SYSLOG_FILE', '../../../../dolibarr_install.log'); // For DoliWamp
            } elseif (@is_writable('../../')) {
                define('SYSLOG_FILE', '../../dolibarr_install.log'); // For others
            }
            //print 'SYSLOG_FILE='.SYSLOG_FILE;exit;
        }
        if (defined('SYSLOG_FILE')) {
            $this->conf->global->SYSLOG_FILE = constant('SYSLOG_FILE');
        }
        if (!defined('SYSLOG_FILE_NO_ERROR')) {
            define('SYSLOG_FILE_NO_ERROR', 1);
        }

        /**
         * TODO: Pending
         *
         * // We init log handler for install
         * $handlers = array('mod_syslog_file');
         * foreach ($handlers as $handler) {
         * $file = BASE_PATH . '/core/modules/syslog/' . $handler . '.php';
         * if (!file_exists($file)) {
         * throw new Exception('Missing log handler file ' . $handler . '.php');
         * }
         *
         * require_once $file;
         * $loghandlerinstance = new $handler();
         * if (!$loghandlerinstance instanceof LogHandlerInterface) {
         * throw new Exception('Log handler does not extend LogHandlerInterface');
         * }
         *
         * if (empty($this->conf->loghandlers[$handler])) {
         * $this->conf->loghandlers[$handler] = $loghandlerinstance;
         * }
         * }
         */

        return 1;
    }

    private function _checkBrowser()
    {
        $useragent = $_SERVER['HTTP_USER_AGENT'];
        if (empty($useragent)) {
            return false;
        }

        $tmp = getBrowserInfo($_SERVER["HTTP_USER_AGENT"]);
        $browsername = $tmp['browsername'];
        $browserversion = $tmp['browserversion'];
        if ($browsername == 'ie' && $browserversion < 7) {
            $result = [];
            $result['ok'] = true;
            $result['icon'] = 'warning';
            $result['text'] = $this->langs->trans("WarningBrowserTooOld");
            return $result;
        }

        return false;
    }

    private function _checkMinPhp()
    {
        $arrayphpminversionerror = [7, 0, 0];
        $arrayphpminversionwarning = [7, 1, 0];

        $result = [];
        $result['ok'] = true;

        if (versioncompare(versionphparray(), $arrayphpminversionerror) < 0) {        // Minimum to use (error if lower)
            $result['ok'] = false;
            $result['icon'] = 'error';
            $result['text'] = $this->langs->trans("ErrorPHPVersionTooLow", versiontostring($arrayphpminversionerror));
        } elseif (versioncompare(versionphparray(), $arrayphpminversionwarning) < 0) {    // Minimum supported (warning if lower)
            $result['icon'] = 'warning';
            $result['text'] = $this->langs->trans("ErrorPHPVersionTooLow", versiontostring($arrayphpminversionwarning));
        } else {
            $result['icon'] = 'ok';
            $result['text'] = $this->langs->trans("PHPVersion") . " " . versiontostring(versionphparray());
        }

        if (empty($force_install_nophpinfo)) {
            $result['text'] .= ' (<a href="phpinfo.php" target="_blank" rel="noopener noreferrer">' . $this->langs->trans("MoreInformation") . '</a>)';
        }

        return $result;
    }

    private function _checkMaxPhp()
    {
        $arrayphpmaxversionwarning = [8, 2, 0];
        if (versioncompare(versionphparray(), $arrayphpmaxversionwarning) > 0 && versioncompare(versionphparray(), $arrayphpmaxversionwarning) < 3) {        // Maximum to use (warning if higher)
            $result = [];
            $result['ok'] = false;
            $result['icon'] = 'error';
            $result['text'] = $this->langs->trans("ErrorPHPVersionTooHigh", versiontostring($arrayphpmaxversionwarning));
            return $result;
        }

        return false;
    }

    private function _checkGetPostSupport()
    {
        $result = [];
        $result['ok'] = true;
        if (empty($_GET) || empty($_POST)) {   // We must keep $_GET and $_POST here
            $result['icon'] = 'warning';
            $result['text'] = $this->langs->trans("PHPSupportPOSTGETKo") . ' (<a href="' . dol_escape_htmltag($_SERVER["PHP_SELF"]) . '?testget=ok">' . $this->langs->trans("Recheck") . '</a>)';
        } else {
            $result['icon'] = 'ok';
            $result['text'] = $this->langs->trans("PHPSupportPOSTGETOk");
        }
        return $result;
    }

    private function _checkSessionId()
    {
        $result = [];
        $result['ok'] = function_exists("session_id");
        if ($result['ok']) {
            $result['icon'] = 'ok';
            $result['text'] = $this->langs->trans("PHPSupportSessions");
        } else {
            $result['icon'] = 'error';
            $result['text'] = $this->langs->trans("ErrorPHPDoesNotSupportSessions");
        }
        return $result;
    }

    private function _checkMbStringExtension()
    {
        $result = [];
        $result['ok'] = extension_loaded("mbstring");
        if (!$result['ok']) {
            $result['icon'] = 'error';
            $result['text'] = $this->langs->trans("ErrorPHPDoesNotSupport", "MBString");
        } else {
            $result['icon'] = 'ok';
            $result['text'] = $this->langs->trans("PHPSupport", "MBString");
        }
        return $result;
    }

    private function _checkJsonExtension()
    {
        $result = [];
        $result['ok'] = extension_loaded("json");
        if (!$result['ok']) {
            $result['icon'] = 'error';
            $result['text'] = $this->langs->trans("ErrorPHPDoesNotSupport", "JSON");
        } else {
            $result['icon'] = 'ok';
            $result['text'] = $this->langs->trans("PHPSupport", "JSON");
        }
        return $result;
    }

    private function _checkGdExtension()
    {
        $result = [];
        $result['ok'] = true;
        if (!function_exists("imagecreate")) {
            $result['icon'] = 'warning';
            $result['text'] = $this->langs->trans("ErrorPHPDoesNotSupport", "GD");
        } else {
            $result['icon'] = 'ok';
            $result['text'] = $this->langs->trans("PHPSupport", "GD");
        }
        return $result;
    }

    private function _checkCurlExtension()
    {
        $result = [];
        $result['ok'] = true;
        if (!function_exists("curl_init")) {
            $result['icon'] = 'warning';
            $result['text'] = $this->langs->trans("ErrorPHPDoesNotSupport", "Curl");
        } else {
            $result['icon'] = 'ok';
            $result['text'] = $this->langs->trans("PHPSupport", "Curl");
        }
        return $result;
    }

    private function _checkCalendarExtension()
    {
        $result = [];
        $result['ok'] = function_exists("easter_date");
        if (!$result['ok']) {
            $result['icon'] = 'error';
            $result['text'] = $this->langs->trans("ErrorPHPDoesNotSupport", "Calendar");
        } else {
            $result['icon'] = 'ok';
            $result['text'] = $this->langs->trans("PHPSupport", "Calendar");
        }
        return $result;
    }

    private function _checkXmlExtension()
    {
        $result = [];
        $result['ok'] = function_exists("simplexml_load_string");
        if (!$result['ok']) {
            $result['icon'] = 'error';
            $result['text'] = $this->langs->trans("ErrorPHPDoesNotSupport", "Xml");
        } else {
            $result['icon'] = 'ok';
            $result['text'] = $this->langs->trans("PHPSupport", "Xml");
        }
        return $result;
    }

    private function _checkUtfExtension()
    {
        $result = [];
        $result['ok'] = function_exists("utf8_encode");
        if (!$result['ok']) {
            $result['icon'] = 'error';
            $result['text'] = $this->langs->trans("ErrorPHPDoesNotSupport", "UTF8");
        } else {
            $result['icon'] = 'ok';
            $result['text'] = $this->langs->trans("PHPSupport", "UTF8");
        }
        return $result;
    }

    private function _checkIntlExtension()
    {
        if (empty($_SERVER["SERVER_ADMIN"]) || $_SERVER["SERVER_ADMIN"] != 'doliwamp@localhost') {
            $result = [];
            $result['ok'] = function_exists("locale_get_primary_language") && function_exists("locale_get_region");
            if (!$result['ok']) {
                $result['icon'] = 'error';
                $result['text'] = $this->langs->trans("ErrorPHPDoesNotSupport", "Intl");
            } else {
                $result['icon'] = 'ok';
                $result['text'] = $this->langs->trans("PHPSupport", "Intl");
            }
            return $result;
        }

        return false;
    }

    private function _checkImapExtension()
    {
        if (PHP_VERSION_ID > 80300) {
            return false;
        }

        $result = [];
        $result['ok'] = function_exists("imap_open");
        if (!$result['ok']) {
            $result['icon'] = 'error';
            $result['text'] = $this->langs->trans("ErrorPHPDoesNotSupport", "IMAP");
        } else {
            $result['icon'] = 'ok';
            $result['text'] = $this->langs->trans("PHPSupport", "IMAP");
        }
        return $result;
    }

    private function _checkZipExtension()
    {
        $result = [];
        $result['ok'] = class_exists('ZipArchive');
        if (!$result['ok']) {
            $result['icon'] = 'error';
            $result['text'] = $this->langs->trans("ErrorPHPDoesNotSupport", "ZIP");
        } else {
            $result['icon'] = 'ok';
            $result['text'] = $this->langs->trans("PHPSupport", "ZIP");
        }
        return $result;
    }

    private function _checkMemory()
    {
        $memmaxorig = @ini_get("memory_limit");
        if (empty($memmaxorig)) {
            return false;
        }

        $memmax = $memmaxorig;
        $memrequiredorig = '64M';
        $memrequired = 64 * 1024 * 1024;
        preg_match('/([0-9]+)([a-zA-Z]*)/i', $memmax, $reg);
        if ($reg[2]) {
            if (strtoupper($reg[2]) == 'G') {
                $memmax = $reg[1] * 1024 * 1024 * 1024;
            }
            if (strtoupper($reg[2]) == 'M') {
                $memmax = $reg[1] * 1024 * 1024;
            }
            if (strtoupper($reg[2]) == 'K') {
                $memmax = $reg[1] * 1024;
            }
        }

        $result = [];
        $result['ok'] = $memmax >= $memrequired || $memmax == -1;
        if ($result['ok']) {
            $result['icon'] = 'ok';
            $result['text'] = $this->langs->trans("PHPMemoryOK", $memmaxorig, $memrequiredorig);
        } else {
            $result['icon'] = 'warning';
            $result['text'] = $this->langs->trans("PHPMemoryTooLow", $memmaxorig, $memrequiredorig);
        }
        return $result;
    }

    private function _checkConfFile()
    {
        $result = false;
        $conffile = Config::getDolibarrConfigFilename();

        clearstatcache();
        if (is_readable($conffile) && filesize($conffile) > 8) {
            $this->syslog("check: conf file '" . $conffile . "' already defined");
            return $result;
        }

        // If not, we create it
        $this->syslog("check: we try to create conf file '" . $conffile . "'");

        // First we try by copying example
        if (@copy($conffile . ".example", $conffile)) {
            // Success
            $this->syslog("check: successfully copied file " . $conffile . ".example into " . $conffile);
        } else {
            // If failed, we try to create an empty file
            $this->syslog("check: failed to copy file " . $conffile . ".example into " . $conffile . ". We try to create it.", LOG_WARNING);

            $fp = @fopen($conffile, "w");
            if ($fp) {
                @fwrite($fp, '<?php');
                @fwrite($fp, "\n");
                fclose($fp);
            } else {
                $this->syslog("check: failed to create a new file " . $conffile . " into current dir " . getcwd() . ". Please check permissions.", LOG_ERR);
                $result = [];
                $result['ok'] = false;
                $result['icon'] = 'error';
                $result['text'] = $this->langs->trans('ConfFileDoesNotExistsAndCouldNotBeCreated', 'conf.php');
            }
        }
        return $result;
    }

    private function _getDbType()
    {
        $defaultype = !empty($dolibarr_main_db_type) ? $dolibarr_main_db_type : (empty($force_install_type) ? 'mysqli' : $force_install_type);

        $modules = [];
        $nbok = $nbko = 0;
        $option = '';
    }
}
