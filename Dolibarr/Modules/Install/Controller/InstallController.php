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
use DoliCore\Base\DolibarrNoLoginController;
use DoliCore\Form\FormAdmin;

include_once realpath(BASE_PATH . '/../Dolibarr/Modules/Install/Include/inc.php');

define('ALLOWED_IF_UPGRADE_UNLOCK_FOUND', 1);

class InstallController extends DolibarrNoLoginController
{
    const DEFAULT_DATABASE_NAME = 'alixar';
    const DEFAULT_DATABASE_PREFIX = 'alx_';

    public function index()
    {
        global $langs;
        global $conf;

        $err = 0;

        $conffile = Config::getDolibarrConfigFilename();

        // If the config file exists and is filled, we're not on first install so we skip the language selection page
        if (file_exists($conffile) && isset($dolibarr_main_url_root)) {
            return $this->check(true);
        }

        if (!isset($conf)) {
            $conf = Config::getConf();
        }

        if (!isset($langs)) {
            $langs = Config::getLangs();
            $langs->setDefaultLang('auto');
        }

        $langs->load("admin");

        /*
         * View
         */

        $formadmin = new FormAdmin(null); // Note: $db does not exist yet but we don't need it, so we put ''.

        pHeader("", "check"); // Next step = check


        if (!is_readable($conffile)) {
            print '<br>';
            print '<span class="opacitymedium">' . $langs->trans("NoReadableConfFileSoStartInstall") . '</span>';
        }


// Ask installation language
        print '<br><br><div class="center">';
        print '<table>';

        print '<tr>';
        print '<td>' . $langs->trans("DefaultLanguage") . ' : </td><td>';
        print $formadmin->select_language('auto', 'selectlang', 1, 0, 0, 1);
        print '</td>';
        print '</tr>';

        print '</table></div>';


//print '<br><br><span class="opacitymedium">'.$langs->trans("SomeTranslationAreUncomplete").'</span>';

// If there's no error, we display the next step button
        if ($err == 0) {
            pFooter(0);
        }
    }

    public function check($testget = false)
    {
        global $langs;
        global $conf;

        $conffile = Config::getDolibarrConfigFilename();
        $conffiletoshow = $conffile;

        if (!isset($conf)) {
            $conf = Config::getConf();
        }

        if (!isset($langs)) {
            $langs = Config::getLangs($conf);
            $langs->setDefaultLang('auto');
        }

        $err = 0;
        $allowinstall = 0;
        $allowupgrade = false;
        $checksok = 1;

        $setuplang = GETPOST("selectlang", 'aZ09', 3) ? GETPOST("selectlang", 'aZ09', 3) : $langs->getDefaultLang();
        $langs->setDefaultLang($setuplang);

        $langs->load("install");

// Now we load forced/pre-set values from install.forced.php file.
        $useforcedwizard = false;
        $forcedfile = "./install.forced.php";
        if ($conffile == "/etc/dolibarr/conf.php") {
            $forcedfile = "/etc/dolibarr/install.forced.php";
        }
        if (@file_exists($forcedfile)) {
            $useforcedwizard = true;
            include_once $forcedfile;
        }

        dolibarr_install_syslog("- check: Dolibarr install/upgrade process started");


        /*
         *  View
         */

        pHeader('', ''); // No next step for navigation buttons. Next step is defined by click on links.


//print "<br>\n";
//print $langs->trans("InstallEasy")."<br><br>\n";

        print '<h3><img class="valignmiddle inline-block paddingright" src="../theme/common/octicons/build/svg/gear.svg" width="20" alt="Database"> ';
        print '<span class="inline-block">' . $langs->trans("MiscellaneousChecks") . "</span></h3>\n";

// Check browser
        $useragent = $_SERVER['HTTP_USER_AGENT'];
        if (!empty($useragent)) {
            $tmp = getBrowserInfo($_SERVER["HTTP_USER_AGENT"]);
            $browserversion = $tmp['browserversion'];
            $browsername = $tmp['browsername'];
            if ($browsername == 'ie' && $browserversion < 7) {
                print '<img src="../theme/eldy/img/warning.png" alt="Error" class="valignmiddle"> ' . $langs->trans("WarningBrowserTooOld") . "<br>\n";
            }
        }


// Check PHP version min
        $arrayphpminversionerror = [7, 0, 0];
        $arrayphpminversionwarning = [7, 1, 0];
        if (versioncompare(versionphparray(), $arrayphpminversionerror) < 0) {        // Minimum to use (error if lower)
            print '<img src="../theme/eldy/img/error.png" alt="Error" class="valignmiddle"> ' . $langs->trans("ErrorPHPVersionTooLow", versiontostring($arrayphpminversionerror));
            $checksok = 0; // 0=error, 1=warning
        } elseif (versioncompare(versionphparray(), $arrayphpminversionwarning) < 0) {    // Minimum supported (warning if lower)
            print '<img src="../theme/eldy/img/warning.png" alt="Error" class="valignmiddle"> ' . $langs->trans("ErrorPHPVersionTooLow", versiontostring($arrayphpminversionwarning));
            $checksok = 1; // 0=error, 1=warning
        } else {
            print '<img src="../theme/eldy/img/tick.png" alt="Ok" class="valignmiddle"> ' . $langs->trans("PHPVersion") . " " . versiontostring(versionphparray());
        }
        if (empty($force_install_nophpinfo)) {
            print ' (<a href="phpinfo.php" target="_blank" rel="noopener noreferrer">' . $langs->trans("MoreInformation") . '</a>)';
        }
        print "<br>\n";

// Check PHP version max
        $arrayphpmaxversionwarning = [8, 2, 0];
        if (versioncompare(versionphparray(), $arrayphpmaxversionwarning) > 0 && versioncompare(versionphparray(), $arrayphpmaxversionwarning) < 3) {        // Maximum to use (warning if higher)
            print '<img src="../theme/eldy/img/error.png" alt="Error" class="valignmiddle"> ' . $langs->trans("ErrorPHPVersionTooHigh", versiontostring($arrayphpmaxversionwarning));
            $checksok = 1; // 0=error, 1=warning
            print "<br>\n";
        }


// Check PHP support for $_GET and $_POST
        if (!isset($_GET["testget"]) && !isset($_POST["testpost"])) {   // We must keep $_GET and $_POST here
            print '<img src="../theme/eldy/img/warning.png" alt="Warning" class="valignmiddle"> ' . $langs->trans("PHPSupportPOSTGETKo");
            print ' (<a href="' . dol_escape_htmltag($_SERVER['PHP_SELF']) . '?testget=ok">' . $langs->trans("Recheck") . '</a>)';
            print "<br>\n";
            $checksok = 0;
        } else {
            print '<img src="../theme/eldy/img/tick.png" alt="Ok" class="valignmiddle"> ' . $langs->trans("PHPSupportPOSTGETOk") . "<br>\n";
        }


// Check if session_id is enabled
        if (!function_exists("session_id")) {
            print '<img src="../theme/eldy/img/error.png" alt="Error" class="valignmiddle"> ' . $langs->trans("ErrorPHPDoesNotSupportSessions") . "<br>\n";
            $checksok = 0;
        } else {
            print '<img src="../theme/eldy/img/tick.png" alt="Ok" class="valignmiddle"> ' . $langs->trans("PHPSupportSessions") . "<br>\n";
        }


// Check for mbstring extension
        if (!extension_loaded("mbstring")) {
            $langs->load("errors");
            print '<img src="../theme/eldy/img/warning.png" alt="Error" class="valignmiddle"> ' . $langs->trans("ErrorPHPDoesNotSupport", "MBString") . "<br>\n";
            // $checksok = 0; // If ko, just warning. So check must still be 1 (otherwise no way to install)
        } else {
            print '<img src="../theme/eldy/img/tick.png" alt="Ok" class="valignmiddle"> ' . $langs->trans("PHPSupport", "MBString") . "<br>\n";
        }

// Check for json extension
        if (!extension_loaded("json")) {
            $langs->load("errors");
            print '<img src="../theme/eldy/img/warning.png" alt="Error" class="valignmiddle"> ' . $langs->trans("ErrorPHPDoesNotSupport", "JSON") . "<br>\n";
            // $checksok = 0; // If ko, just warning. So check must still be 1 (otherwise no way to install)
        } else {
            print '<img src="../theme/eldy/img/tick.png" alt="Ok" class="valignmiddle"> ' . $langs->trans("PHPSupport", "JSON") . "<br>\n";
        }

// Check if GD is supported (we need GD for image conversion)
        if (!function_exists("imagecreate")) {
            $langs->load("errors");
            print '<img src="../theme/eldy/img/warning.png" alt="Error" class="valignmiddle"> ' . $langs->trans("ErrorPHPDoesNotSupport", "GD") . "<br>\n";
            // $checksok = 0;       // If ko, just warning. So check must still be 1 (otherwise no way to install)
        } else {
            print '<img src="../theme/eldy/img/tick.png" alt="Ok" class="valignmiddle"> ' . $langs->trans("PHPSupport", "GD") . "<br>\n";
        }

// Check if Curl is supported
        if (!function_exists("curl_init")) {
            $langs->load("errors");
            print '<img src="../theme/eldy/img/warning.png" alt="Error" class="valignmiddle"> ' . $langs->trans("ErrorPHPDoesNotSupport", "Curl") . "<br>\n";
            // $checksok = 0;       // If ko, just warning. So check must still be 1 (otherwise no way to install)
        } else {
            print '<img src="../theme/eldy/img/tick.png" alt="Ok" class="valignmiddle"> ' . $langs->trans("PHPSupport", "Curl") . "<br>\n";
        }

// Check if PHP calendar extension is available
        if (!function_exists("easter_date")) {
            print '<img src="../theme/eldy/img/warning.png" alt="Error" class="valignmiddle"> ' . $langs->trans("ErrorPHPDoesNotSupport", "Calendar") . "<br>\n";
        } else {
            print '<img src="../theme/eldy/img/tick.png" alt="Ok" class="valignmiddle"> ' . $langs->trans("PHPSupport", "Calendar") . "<br>\n";
        }

// Check if Xml is supported
        if (!function_exists("simplexml_load_string")) {
            $langs->load("errors");
            print '<img src="../theme/eldy/img/warning.png" alt="Error" class="valignmiddle"> ' . $langs->trans("ErrorPHPDoesNotSupport", "Xml") . "<br>\n";
            // $checksok = 0;       // If ko, just warning. So check must still be 1 (otherwise no way to install)
        } else {
            print '<img src="../theme/eldy/img/tick.png" alt="Ok" class="valignmiddle"> ' . $langs->trans("PHPSupport", "Xml") . "<br>\n";
        }

// Check if UTF8 is supported
        if (!function_exists("utf8_encode")) {
            $langs->load("errors");
            print '<img src="../theme/eldy/img/warning.png" alt="Error" class="valignmiddle"> ' . $langs->trans("ErrorPHPDoesNotSupport", "UTF8") . "<br>\n";
            // $checksok = 0; // If ko, just warning. So check must still be 1 (otherwise no way to install)
        } else {
            print '<img src="../theme/eldy/img/tick.png" alt="Ok" class="valignmiddle"> ' . $langs->trans("PHPSupport", "UTF8") . "<br>\n";
        }

// Check if intl methods are supported if install is not from DoliWamp. TODO Why ?
        if (empty($_SERVER["SERVER_ADMIN"]) || $_SERVER["SERVER_ADMIN"] != 'doliwamp@localhost') {
            if (!function_exists("locale_get_primary_language") || !function_exists("locale_get_region")) {
                $langs->load("errors");
                print '<img src="../theme/eldy/img/warning.png" alt="Error" class="valignmiddle"> ' . $langs->trans("ErrorPHPDoesNotSupport", "Intl") . "<br>\n";
                // $checksok = 0;       // If ko, just warning. So check must still be 1 (otherwise no way to install)
            } else {
                print '<img src="../theme/eldy/img/tick.png" alt="Ok" class="valignmiddle"> ' . $langs->trans("PHPSupport", "Intl") . "<br>\n";
            }
        }

// Check if Imap is supported
        if (PHP_VERSION_ID <= 80300) {
            if (!function_exists("imap_open")) {
                $langs->load("errors");
                print '<img src="../theme/eldy/img/warning.png" alt="Error" class="valignmiddle"> ' . $langs->trans("ErrorPHPDoesNotSupport", "IMAP") . "<br>\n";
                // $checksok = 0;       // If ko, just warning. So check must still be 1 (otherwise no way to install)
            } else {
                print '<img src="../theme/eldy/img/tick.png" alt="Ok" class="valignmiddle"> ' . $langs->trans("PHPSupport", "IMAP") . "<br>\n";
            }
        }

// Check if Zip is supported
        if (!class_exists('ZipArchive')) {
            $langs->load("errors");
            print '<img src="../theme/eldy/img/warning.png" alt="Error" class="valignmiddle"> ' . $langs->trans("ErrorPHPDoesNotSupport", "ZIP") . "<br>\n";
            // $checksok = 0;       // If ko, just warning. So check must still be 1 (otherwise no way to install)
        } else {
            print '<img src="../theme/eldy/img/tick.png" alt="Ok" class="valignmiddle"> ' . $langs->trans("PHPSupport", "ZIP") . "<br>\n";
        }

// Check memory
        $memrequiredorig = '64M';
        $memrequired = 64 * 1024 * 1024;
        $memmaxorig = @ini_get("memory_limit");
        $memmax = @ini_get("memory_limit");
        if ($memmaxorig != '') {
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
            if ($memmax >= $memrequired || $memmax == -1) {
                print '<img src="../theme/eldy/img/tick.png" alt="Ok" class="valignmiddle"> ' . $langs->trans("PHPMemoryOK", $memmaxorig, $memrequiredorig) . "<br>\n";
            } else {
                print '<img src="../theme/eldy/img/warning.png" alt="Warning" class="valignmiddle"> ' . $langs->trans("PHPMemoryTooLow", $memmaxorig, $memrequiredorig) . "<br>\n";
            }
        }


// If that config file is present and filled
        clearstatcache();
        if (is_readable($conffile) && filesize($conffile) > 8) {
            dolibarr_install_syslog("check: conf file '" . $conffile . "' already defined");
            $confexists = 1;
            include_once $conffile;

            $databaseok = 1;
            if ($databaseok) {
                // Already installed for all parts (config and database). We can propose upgrade.
                $allowupgrade = true;
            } else {
                $allowupgrade = false;
            }
        } else {
            // If not, we create it
            dolibarr_install_syslog("check: we try to create conf file '" . $conffile . "'");
            $confexists = 0;

            // First we try by copying example
            if (@copy($conffile . ".example", $conffile)) {
                // Success
                dolibarr_install_syslog("check: successfully copied file " . $conffile . ".example into " . $conffile);
            } else {
                // If failed, we try to create an empty file
                dolibarr_install_syslog("check: failed to copy file " . $conffile . ".example into " . $conffile . ". We try to create it.", LOG_WARNING);

                $fp = @fopen($conffile, "w");
                if ($fp) {
                    @fwrite($fp, '<?php');
                    @fwrite($fp, "\n");
                    fclose($fp);
                } else {
                    dolibarr_install_syslog("check: failed to create a new file " . $conffile . " into current dir " . getcwd() . ". Please check permissions.", LOG_ERR);
                }
            }

            // First install: no upgrade necessary/required
            $allowupgrade = false;
        }

// File is missing and cannot be created
        if (!file_exists($conffile)) {
            print '<img src="../theme/eldy/img/error.png" alt="Error" class="valignmiddle"> ' . $langs->trans("ConfFileDoesNotExistsAndCouldNotBeCreated", $conffiletoshow);
            print '<br><br><div class="error">';
            print $langs->trans("YouMustCreateWithPermission", $conffiletoshow);
            print '</div><br><br>' . "\n";

            print '<span class="opacitymedium">' . $langs->trans("CorrectProblemAndReloadPage", $_SERVER['PHP_SELF'] . '?testget=ok') . '</span>';
            $err++;
        } else {
            if (dol_is_dir($conffile)) {
                print '<img src="../theme/eldy/img/error.png" alt="Warning"> ' . $langs->trans("ConfFileMustBeAFileNotADir", $conffiletoshow);

                $allowinstall = 0;
            } elseif (!is_writable($conffile)) {
                // File exists but cannot be modified
                if ($confexists) {
                    print '<img src="../theme/eldy/img/tick.png" alt="Ok"> ' . $langs->trans("ConfFileExists", $conffiletoshow);
                } else {
                    print '<img src="../theme/eldy/img/tick.png" alt="Ok"> ' . $langs->trans("ConfFileCouldBeCreated", $conffiletoshow);
                }
                print "<br>";
                print '<img src="../theme/eldy/img/tick.png" alt="Warning"> ' . $langs->trans("ConfFileIsNotWritable", $conffiletoshow);
                print "<br>\n";

                $allowinstall = 0;
            } else {
                // File exists and can be modified
                if ($confexists) {
                    print '<img src="../theme/eldy/img/tick.png" alt="Ok"> ' . $langs->trans("ConfFileExists", $conffiletoshow);
                } else {
                    print '<img src="../theme/eldy/img/tick.png" alt="Ok"> ' . $langs->trans("ConfFileCouldBeCreated", $conffiletoshow);
                }
                print "<br>";
                print '<img src="../theme/eldy/img/tick.png" alt="Ok"> ' . $langs->trans("ConfFileIsWritable", $conffiletoshow);
                print "<br>\n";

                $allowinstall = 1;
            }
            print "<br>\n";

            // Requirements met/all ok: display the next step button
            if ($checksok) {
                $ok = 0;

                // Try to create db connection
                if (file_exists($conffile)) {
                    include_once $conffile;
                    if (!empty($dolibarr_main_db_type) && !empty($dolibarr_main_document_root)) {
                        if (!file_exists($dolibarr_main_document_root . "/core/lib/admin.lib.php")) {
                            print '<span class="error">A ' . $conffiletoshow . ' file exists with a dolibarr_main_document_root to ' . $dolibarr_main_document_root . ' that seems wrong. Try to fix or remove the ' . $conffiletoshow . ' file.</span><br>' . "\n";
                            dol_syslog("A '" . $conffiletoshow . "' file exists with a dolibarr_main_document_root to " . $dolibarr_main_document_root . " that seems wrong. Try to fix or remove the '" . $conffiletoshow . "' file.", LOG_WARNING);
                        } else {
                            require_once $dolibarr_main_document_root . '/core/lib/admin.lib.php';

                            // If password is encoded, we decode it
                            if (preg_match('/crypted:/i', $dolibarr_main_db_pass) || !empty($dolibarr_main_db_encrypted_pass)) {
                                require_once $dolibarr_main_document_root . '/core/lib/security.lib.php';
                                if (preg_match('/crypted:/i', $dolibarr_main_db_pass)) {
                                    $dolibarr_main_db_encrypted_pass = preg_replace('/crypted:/i', '', $dolibarr_main_db_pass); // We need to set this as it is used to know the password was initially encrypted
                                    $dolibarr_main_db_pass = dol_decode($dolibarr_main_db_encrypted_pass);
                                } else {
                                    $dolibarr_main_db_pass = dol_decode($dolibarr_main_db_encrypted_pass);
                                }
                            }

                            // $conf already created in inc.php
                            $conf->db->type = $dolibarr_main_db_type;
                            $conf->db->host = $dolibarr_main_db_host;
                            $conf->db->port = $dolibarr_main_db_port;
                            $conf->db->name = $dolibarr_main_db_name;
                            $conf->db->user = $dolibarr_main_db_user;
                            $conf->db->pass = $dolibarr_main_db_pass;
                            $db = getDoliDBInstance($conf->db->type, $conf->db->host, $conf->db->user, $conf->db->pass, $conf->db->name, (int) $conf->db->port);
                            if ($db->connected && $db->database_selected) {
                                $ok = true;
                            }
                        }
                    }
                }

                // If database access is available, we set more variables
                if ($ok) {
                    if (empty($dolibarr_main_db_encryption)) {
                        $dolibarr_main_db_encryption = 0;
                    }
                    $conf->db->dolibarr_main_db_encryption = $dolibarr_main_db_encryption;
                    if (empty($dolibarr_main_db_cryptkey)) {
                        $dolibarr_main_db_cryptkey = '';
                    }
                    $conf->db->dolibarr_main_db_cryptkey = $dolibarr_main_db_cryptkey;

                    $conf->setValues($db);
                    // Reset forced setup after the setValues
                    if (defined('SYSLOG_FILE')) {
                        $conf->global->SYSLOG_FILE = constant('SYSLOG_FILE');
                    }
                    $conf->global->MAIN_ENABLE_LOG_TO_HTML = 1;

                    // Current version is $conf->global->MAIN_VERSION_LAST_UPGRADE
                    // Version to install is DOL_VERSION
                    $dolibarrlastupgradeversionarray = preg_split('/[\.-]/', isset($conf->global->MAIN_VERSION_LAST_UPGRADE) ? $conf->global->MAIN_VERSION_LAST_UPGRADE : (isset($conf->global->MAIN_VERSION_LAST_INSTALL) ? $conf->global->MAIN_VERSION_LAST_INSTALL : ''));
                    $dolibarrversiontoinstallarray = versiondolibarrarray();
                }

                // Show title
                if (getDolGlobalString('MAIN_VERSION_LAST_UPGRADE') || getDolGlobalString('MAIN_VERSION_LAST_INSTALL')) {
                    print $langs->trans("VersionLastUpgrade") . ': <b><span class="ok">' . (!getDolGlobalString('MAIN_VERSION_LAST_UPGRADE') ? $conf->global->MAIN_VERSION_LAST_INSTALL : $conf->global->MAIN_VERSION_LAST_UPGRADE) . '</span></b> - ';
                    print $langs->trans("VersionProgram") . ': <b><span class="ok">' . DOL_VERSION . '</span></b>';
                    //print ' '.img_warning($langs->trans("RunningUpdateProcessMayBeRequired"));
                    print '<br>';
                    print '<br>';
                } else {
                    print "<br>\n";
                }

                //print $langs->trans("InstallEasy")." ";
                print '<h3><span class="soustitre">' . $langs->trans("ChooseYourSetupMode") . '</span></h3>';

                $foundrecommandedchoice = 0;

                $available_choices = [];
                $notavailable_choices = [];

                if (empty($dolibarr_main_db_host)) {    // This means install process was not run
                    $foundrecommandedchoice = 1; // To show only once
                }

                // Show line of first install choice
                $choice = '<tr class="trlineforchoice' . ($foundrecommandedchoice ? ' choiceselected' : '') . '">' . "\n";
                $choice .= '<td class="nowrap center"><b>' . $langs->trans("FreshInstall") . '</b>';
                $choice .= '</td>';
                $choice .= '<td class="listofchoicesdesc">';
                $choice .= $langs->trans("FreshInstallDesc");
                if (empty($dolibarr_main_db_host)) {    // This means install process was not run
                    $choice .= '<br>';
                    //print $langs->trans("InstallChoiceRecommanded",DOL_VERSION,$conf->global->MAIN_VERSION_LAST_UPGRADE);
                    $choice .= '<div class="center"><div class="ok suggestedchoice">' . $langs->trans("InstallChoiceSuggested") . '</div></div>';
                    // <img src="../theme/eldy/img/tick.png" alt="Ok" class="valignmiddle"> ';
                }

                $choice .= '</td>';
                $choice .= '<td class="center">';
                if ($allowinstall) {
                    $choice .= '<a class="button" href="fileconf.php?selectlang=' . $setuplang . '">' . $langs->trans("Start") . '</a>';
                } else {
                    $choice .= ($foundrecommandedchoice ? '<span class="warning">' : '') . $langs->trans("InstallNotAllowed") . ($foundrecommandedchoice ? '</span>' : '');
                }
                $choice .= '</td>' . "\n";
                $choice .= '</tr>' . "\n";

                $positionkey = ($foundrecommandedchoice ? 999 : 0);
                if ($allowinstall) {
                    $available_choices[$positionkey] = $choice;
                } else {
                    $notavailable_choices[$positionkey] = $choice;
                }

                // Show upgrade lines
                $allowupgrade = true;
                if (empty($dolibarr_main_db_host)) {    // This means install process was not run
                    $allowupgrade = false;
                }
                if (getDolGlobalInt("MAIN_NOT_INSTALLED")) {
                    $allowupgrade = false;
                }
                if (GETPOST('allowupgrade')) {
                    $allowupgrade = true;
                }

                $dir = DOL_DOCUMENT_ROOT . "/install/mysql/migration/";   // We use mysql migration scripts whatever is database driver
                dolibarr_install_syslog("Scan sql files for migration files in " . $dir);

                // Get files list of migration file x.y.z-a.b.c.sql into /install/mysql/migration
                $migrationscript = [];
                $handle = opendir($dir);
                if (is_resource($handle)) {
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
                    $migrationscript = dol_sort_array($migrationscript, 'from', 'asc', 1);
                } else {
                    print '<div class="error">' . $langs->trans("ErrorCanNotReadDir", $dir) . '</div>';
                }

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
                        $newversionfrombis = ' ' . $langs->trans("or") . ' ' . $versionto;
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

                    $choice .= "\n" . '<!-- choice ' . $count . ' -->' . "\n";
                    $choice .= '<tr' . ($recommended_choice ? ' class="choiceselected"' : '') . '>';
                    $choice .= '<td class="nowrap center"><b>' . $langs->trans("Upgrade") . '<br>' . $newversionfrom . $newversionfrombis . ' -> ' . $newversionto . '</b></td>';
                    $choice .= '<td class="listofchoicesdesc">';
                    $choice .= $langs->trans("UpgradeDesc");

                    if ($recommended_choice) {
                        $choice .= '<br>';
                        //print $langs->trans("InstallChoiceRecommanded",DOL_VERSION,$conf->global->MAIN_VERSION_LAST_UPGRADE);
                        $choice .= '<div class="center">';
                        $choice .= '<div class="ok suggestedchoice">' . $langs->trans("InstallChoiceSuggested") . '</div>';
                        if ($count < count($migarray)) {    // There are other choices after
                            print $langs->trans("MigrateIsDoneStepByStep", DOL_VERSION);
                        }
                        $choice .= '</div>';
                    }

                    $choice .= '</td>';
                    $choice .= '<td class="center">';
                    if ($allowupgrade) {
                        $disabled = false;
                        if ($foundrecommandedchoice == 2) {
                            $disabled = true;
                        }
                        if ($foundrecommandedchoice == 1) {
                            $foundrecommandedchoice = 2;
                        }
                        if ($disabled) {
                            $choice .= '<span class="opacitymedium">' . $langs->trans("NotYetAvailable") . '</span>';
                        } else {
                            $choice .= '<a class="button runupgrade" href="upgrade.php?action=upgrade' . ($count < count($migrationscript) ? '_' . $versionto : '') . '&amp;selectlang=' . $setuplang . '&amp;versionfrom=' . $versionfrom . '&amp;versionto=' . $versionto . '">' . $langs->trans("Start") . '</a>';
                        }
                    } else {
                        $choice .= $langs->trans("NotAvailable");
                    }
                    $choice .= '</td>';
                    $choice .= '</tr>' . "\n";

                    if ($allowupgrade) {
                        $available_choices[$count] = $choice;
                    } else {
                        $notavailable_choices[$count] = $choice;
                    }
                }

                // If there is no choice at all, we show all of them.
                if (empty($available_choices)) {
                    $available_choices = $notavailable_choices;
                    $notavailable_choices = [];
                }

                // Array of install choices
                krsort($available_choices, SORT_NATURAL);
                print"\n";
                print '<table width="100%" class="listofchoices">';
                foreach ($available_choices as $choice) {
                    print $choice;
                }

                print '</table>' . "\n";

                if (count($notavailable_choices)) {
                    print '<br><div id="AShowChoices" style="opacity: 0.5">';
                    print '> ' . $langs->trans('ShowNotAvailableOptions') . '...';
                    print '</div>';

                    print '<div id="navail_choices" style="display:none">';
                    print "<br>\n";
                    print '<table width="100%" class="listofchoices">';
                    foreach ($notavailable_choices as $choice) {
                        print $choice;
                    }

                    print '</table>' . "\n";
                    print '</div>';
                }
            }
        }

        print '<script type="text/javascript">

$("div#AShowChoices").click(function() {

    $("div#navail_choices").toggle();

    if ($("div#navail_choices").css("display") == "none") {
        $(this).text("> ' . $langs->trans('ShowNotAvailableOptions') . '...");
    } else {
        $(this).text("' . $langs->trans('HideNotAvailableOptions') . '...");
    }

});

/*
$(".runupgrade").click(function() {
	return confirm("' . dol_escape_js($langs->transnoentitiesnoconv("WarningUpgrade"), 0, 1) . '");
});
*/

</script>';

        dolibarr_install_syslog("- check: end");
        pFooter(1); // Never display next button
    }

    public function fileconf()
    {
        global $langs;
        global $conf;

        $conffile = Config::getDolibarrConfigFilename();
        $conffiletoshow = $conffile;

        if (!isset($conf)) {
            $conf = Config::getConf();
        }

        if (!isset($langs)) {
            $langs = Config::getLangs($conf);
            $langs->setDefaultLang('auto');
        }

        $err = 0;

        $setuplang = GETPOST("selectlang", 'alpha', 3) ? GETPOST("selectlang", 'alpha', 3) : (GETPOST('lang', 'alpha', 1) ? GETPOST('lang', 'alpha', 1) : 'auto');
        $langs->setDefaultLang($setuplang);

        $langs->loadLangs(["install", "errors"]);

        dolibarr_install_syslog("- fileconf: entering fileconf.php page");

// You can force preselected values of the config step of Dolibarr by adding a file
// install.forced.php into directory htdocs/install (This is the case with some wizard
// installer like DoliWamp, DoliMamp or DoliBuntu).
// We first init "forced values" to nothing.
        if (!isset($force_install_noedit)) {
            $force_install_noedit = ''; // 1=To block vars specific to distrib, 2 to block all technical parameters
        }
        if (!isset($force_install_type)) {
            $force_install_type = '';
        }
        if (!isset($force_install_dbserver)) {
            $force_install_dbserver = '';
        }
        if (!isset($force_install_port)) {
            $force_install_port = '';
        }
        if (!isset($force_install_database)) {
            $force_install_database = '';
        }
        if (!isset($force_install_prefix)) {
            $force_install_prefix = '';
        }
        if (!isset($force_install_createdatabase)) {
            $force_install_createdatabase = '';
        }
        if (!isset($force_install_databaselogin)) {
            $force_install_databaselogin = '';
        }
        if (!isset($force_install_databasepass)) {
            $force_install_databasepass = '';
        }
        if (!isset($force_install_databaserootlogin)) {
            $force_install_databaserootlogin = '';
        }
        if (!isset($force_install_databaserootpass)) {
            $force_install_databaserootpass = '';
        }
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


        /*
         *  View
         */

        session_start(); // To be able to keep info into session (used for not losing pass during navigation. pass must not transit through parameters)

        pHeader($langs->trans("ConfigurationFile"), "step1", "set", "", (empty($force_dolibarr_js_JQUERY) ? '' : $force_dolibarr_js_JQUERY . '/'), 'main-inside-bis');

// Test if we can run a first install process
        if (!is_writable($conffile)) {
            print $langs->trans("ConfFileIsNotWritable", $conffiletoshow);
            dolibarr_install_syslog("fileconf: config file is not writable", LOG_WARNING);
            dolibarr_install_syslog("- fileconf: end");
            pFooter(1, $setuplang, 'jscheckparam');
            exit;
        }

        if (!empty($force_install_message)) {
            print '<div><br>' . $langs->trans($force_install_message) . '</div>';

            /*print '<script type="text/javascript">';
            print ' jQuery(document).ready(function() {
                        jQuery("#linktoshowtechnicalparam").click(function() {
                            jQuery(".hidewhenedit").hide();
                            jQuery(".hidewhennoedit").show();
                        });';
                        if ($force_install_noedit) print 'jQuery(".hidewhennoedit").hide();';
            print '});';
            print '</script>';

            print '<br><a href="#" id="linktoshowtechnicalparam" class="hidewhenedit">'.$langs->trans("ShowEditTechnicalParameters").'</a><br>';
            */
        }

        ?>
        <div>


            <table class="nobordernopadding<?php if ($force_install_noedit) {
                print ' hidewhennoedit';
            } ?>">

                <tr>
                    <td colspan="3" class="label">
                        <h3>
                            <img class="valignmiddle inline-block paddingright" src="../theme/common/octicons/build/svg/globe.svg" width="20" alt="webserver"> <?php echo $langs->trans("WebServer"); ?>
                        </h3>
                    </td>
                </tr>

                <!-- Documents root $dolibarr_main_document_root -->
                <tr>
                    <td class="label">
                        <label for="main_dir"><b><?php print $langs->trans("WebPagesDirectory"); ?></b></label></td>
                    <?php
                    if (empty($dolibarr_main_document_root)) {
                        $dolibarr_main_document_root = GETPOSTISSET('main_dir') ? GETPOST('main_dir') : DOL_DOCUMENT_ROOT;
                    }
                    ?>
                    <td class="label">
                        <input type="text"
                               class="minwidth300"
                               id="main_dir"
                               name="main_dir"
                               value="<?php print $dolibarr_main_document_root ?>"
                            <?php
                            if (!empty($force_install_noedit)) {
                                print ' disabled';
                            }
                            ?>
                        >
                    </td>
                    <td class="comment"><?php
                        print '<span class="opacitymedium">' . $langs->trans("WithNoSlashAtTheEnd") . "</span><br>";
                        print $langs->trans("Examples") . ":<br>";
                        ?>
                        <ul>
                            <li>/var/www/dolibarr/htdocs</li>
                            <li>C:/wwwroot/dolibarr/htdocs</li>
                        </ul>
                    </td>
                </tr>

                <!-- Documents URL $dolibarr_main_data_root -->
                <tr>
                    <td class="label">
                        <label for="main_data_dir"><b><?php print $langs->trans("DocumentsDirectory"); ?></b></label>
                    </td>
                    <?php
                    if (!empty($force_install_main_data_root)) {
                        $dolibarr_main_data_root = @$force_install_main_data_root;
                    }
                    if (empty($dolibarr_main_data_root)) {
                        $dolibarr_main_data_root = GETPOSTISSET('main_data_dir') ? GETPOST('main_data_dir') : detect_dolibarr_main_data_root($dolibarr_main_document_root);
                    }
                    ?>
                    <td class="label">
                        <input type="text"
                               class="minwidth300"
                               id="main_data_dir"
                               name="main_data_dir"
                               value="<?php print $dolibarr_main_data_root ?>"
                            <?php if (!empty($force_install_noedit)) {
                                print ' disabled';
                            } ?>
                        >
                    </td>
                    <td class="comment"><?php
                        print '<span class="opacitymedium">' . $langs->trans("WithNoSlashAtTheEnd") . "</span><br>";
                        print $langs->trans("DirectoryRecommendation") . "<br>";
                        print $langs->trans("Examples") . ":<br>";
                        ?>
                        <ul>
                            <li>/var/lib/dolibarr/documents</li>
                            <li>C:/My Documents/dolibarr/documents</li>
                        </ul>
                    </td>
                </tr>

                <!-- Root URL $dolibarr_main_url_root -->
                <?php
                if (empty($dolibarr_main_url_root)) {
                    $dolibarr_main_url_root = GETPOSTISSET('main_url') ? GETPOST('main_url') : DOL_URL_ROOT;
                }
                ?>
                <tr>
                    <td class="label"><label for="main_url"><b><?php echo $langs->trans("URLRoot"); ?></b></label>
                    </td>
                    <td class="label">
                        <input type="text"
                               class="minwidth300"
                               id="main_url"
                               name="main_url"
                               value="<?php print $dolibarr_main_url_root; ?> "
                            <?php if (!empty($force_install_noedit)) {
                                print ' disabled';
                            }
                            ?>
                        >
                    </td>
                    <td class="comment"><?php print $langs->trans("Examples") . ":<br>"; ?>
                        <ul>
                            <li>http://localhost/</li>
                            <li>http://www.myserver.com:8180/dolibarr</li>
                            <li>https://www.myvirtualfordolibarr.com/</li>
                        </ul>
                    </td>
                </tr>

                <?php
                if (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == 'on') {   // Enabled if the installation process is "https://"
                    ?>
                    <tr>
                        <td class="label">
                            <label for="main_force_https"><?php echo $langs->trans("ForceHttps"); ?></label></td>
                        <td class="label">
                            <input type="checkbox"
                                   id="main_force_https"
                                   name="main_force_https"
                                <?php if (!empty($force_install_mainforcehttps)) {
                                    print ' checked';
                                } ?>
                                <?php if ($force_install_noedit == 2 && $force_install_mainforcehttps !== null) {
                                    print ' disabled';
                                } ?>
                            >
                        </td>
                        <td class="comment"><?php echo $langs->trans("CheckToForceHttps"); ?>
                        </td>

                    </tr>
                    <?php
                }
                ?>

                <!-- Dolibarr database -->

                <tr>
                    <td colspan="3" class="label"><br>
                        <h3>
                            <img class="valignmiddle inline-block paddingright" src="../theme/common/octicons/build/svg/database.svg" width="20" alt="webserver"> <?php echo $langs->trans("DolibarrDatabase"); ?>
                        </h3>
                    </td>
                </tr>

                <tr>
                    <td class="label"><label for="db_name"><b><?php echo $langs->trans("DatabaseName"); ?></b></label>
                    </td>
                    <td class="label">
                        <input type="text"
                               id="db_name"
                               name="db_name"
                               value="<?php echo (!empty($dolibarr_main_db_name)) ? $dolibarr_main_db_name : ($force_install_database ? $force_install_database : self::DEFAULT_DATABASE_NAME); ?>"
                            <?php if ($force_install_noedit == 2 && $force_install_database !== null) {
                                print ' disabled';
                            } ?>
                        >
                    </td>
                    <td class="comment"><?php echo $langs->trans("DatabaseName"); ?></td>
                </tr>


                <?php
                if (!isset($dolibarr_main_db_host)) {
                    $dolibarr_main_db_host = "localhost";
                }
                ?>
                <tr>
                    <!-- Driver type -->
                    <td class="label"><label for="db_type"><b><?php echo $langs->trans("DriverType"); ?></b></label>
                    </td>

                    <td class="label">
                        <?php

                        $defaultype = !empty($dolibarr_main_db_type) ? $dolibarr_main_db_type : (empty($force_install_type) ? 'mysqli' : $force_install_type);

                        $modules = [];
                        $nbok = $nbko = 0;
                        $option = '';

                        // Scan les drivers
                        $dir = DOL_DOCUMENT_ROOT . '/core/db';
                        $handle = opendir($dir);
                        if (is_resource($handle)) {
                            while (($file = readdir($handle)) !== false) {
                                if (is_readable($dir . "/" . $file) && preg_match('/^(.*)\.class\.php$/i', $file, $reg)) {
                                    $type = $reg[1];
                                    if ($type === 'DoliDB') {
                                        continue; // Skip abstract class
                                    }
                                    $class = 'DoliDB' . ucfirst($type);
                                    include_once $dir . "/" . $file;

                                    if ($type == 'sqlite') {
                                        continue; // We hide sqlite because support can't be complete until sqlite does not manage foreign key creation after table creation (ALTER TABLE child ADD CONSTRAINT not supported)
                                    }
                                    if ($type == 'sqlite3') {
                                        continue; // We hide sqlite3 because support can't be complete until sqlite does not manage foreign key creation after table creation (ALTER TABLE child ADD CONSTRAINT not supported)
                                    }

                                    // Version min of database
                                    $note = '(' . $class::LABEL . ' >= ' . $class::VERSIONMIN . ')';

                                    // Switch to mysql if mysqli is not present
                                    if ($defaultype == 'mysqli' && !function_exists('mysqli_connect')) {
                                        $defaultype = 'mysql';
                                    }

                                    // Show line into list
                                    if ($type == 'mysql') {
                                        $testfunction = 'mysql_connect';
                                        $testclass = '';
                                    }
                                    if ($type == 'mysqli') {
                                        $testfunction = 'mysqli_connect';
                                        $testclass = '';
                                    }
                                    if ($type == 'pgsql') {
                                        $testfunction = 'pg_connect';
                                        $testclass = '';
                                    }
                                    if ($type == 'mssql') {
                                        $testfunction = 'mssql_connect';
                                        $testclass = '';
                                    }
                                    if ($type == 'sqlite') {
                                        $testfunction = '';
                                        $testclass = 'PDO';
                                    }
                                    if ($type == 'sqlite3') {
                                        $testfunction = '';
                                        $testclass = 'SQLite3';
                                    }
                                    $option .= '<option value="' . $type . '"' . ($defaultype == $type ? ' selected' : '');
                                    if ($testfunction && !function_exists($testfunction)) {
                                        $option .= ' disabled';
                                    }
                                    if ($testclass && !class_exists($testclass)) {
                                        $option .= ' disabled';
                                    }
                                    $option .= '>';
                                    $option .= $type . '&nbsp; &nbsp;';
                                    if ($note) {
                                        $option .= ' ' . $note;
                                    }
                                    // Deprecated and experimental
                                    if ($type == 'mysql') {
                                        $option .= ' ' . $langs->trans("Deprecated");
                                    } elseif ($type == 'mssql') {
                                        $option .= ' ' . $langs->trans("VersionExperimental");
                                    } elseif ($type == 'sqlite') {
                                        $option .= ' ' . $langs->trans("VersionExperimental");
                                    } elseif ($type == 'sqlite3') {
                                        $option .= ' ' . $langs->trans("VersionExperimental");
                                    } elseif (!function_exists($testfunction)) {
                                        // No available
                                        $option .= ' - ' . $langs->trans("FunctionNotAvailableInThisPHP");
                                    }
                                    $option .= '</option>';
                                }
                            }
                        }
                        ?>
                        <select id="db_type"
                                name="db_type"
                            <?php if ($force_install_noedit == 2 && $force_install_type !== null) {
                                print ' disabled';
                            } ?>
                        >
                            <?php print $option; ?>
                        </select>

                    </td>
                    <td class="comment"><?php echo $langs->trans("DatabaseType"); ?></td>

                </tr>

                <tr class="hidesqlite">
                    <td class="label"><label for="db_host"><b><?php echo $langs->trans("DatabaseServer"); ?></b></label>
                    </td>
                    <td class="label">
                        <input type="text"
                               id="db_host"
                               name="db_host"
                               value="<?php print(!empty($force_install_dbserver) ? $force_install_dbserver : (!empty($dolibarr_main_db_host) ? $dolibarr_main_db_host : 'localhost')); ?>"
                            <?php if ($force_install_noedit == 2 && $force_install_dbserver !== null) {
                                print ' disabled';
                            } ?>
                        >
                    </td>
                    <td class="comment"><?php echo $langs->trans("ServerAddressDescription"); ?>
                    </td>

                </tr>

                <tr class="hidesqlite">
                    <td class="label"><label for="db_port"><?php echo $langs->trans("Port"); ?></label></td>
                    <td class="label">
                        <input type="text"
                               name="db_port"
                               id="db_port"
                               value="<?php print (!empty($force_install_port)) ? $force_install_port : ($dolibarr_main_db_port ?? ''); ?>"
                            <?php if ($force_install_noedit == 2 && $force_install_port !== null) {
                                print ' disabled';
                            } ?>
                        >
                    </td>
                    <td class="comment"><?php echo $langs->trans("ServerPortDescription"); ?>
                    </td>

                </tr>

                <tr class="hidesqlite">
                    <td class="label"><label for="db_prefix"><?php echo $langs->trans("DatabasePrefix"); ?></label></td>
                    <td class="label">
                        <input type="text"
                               id="db_prefix"
                               name="db_prefix"
                               value="<?php echo(!empty($force_install_prefix) ? $force_install_prefix : (!empty($dolibarr_main_db_prefix) ? $dolibarr_main_db_prefix : self::DEFAULT_DATABASE_PREFIX)); ?>"
                            <?php if ($force_install_noedit == 2 && $force_install_prefix !== null) {
                                print ' disabled';
                            } ?>
                        >
                    </td>
                    <td class="comment"><?php echo $langs->trans("DatabasePrefixDescription"); ?></td>
                </tr>

                <tr class="hidesqlite">
                    <td class="label">
                        <label for="db_create_database"><?php echo $langs->trans("CreateDatabase"); ?></label></td>
                    <td class="label">
                        <input type="checkbox"
                               id="db_create_database"
                               name="db_create_database"
                               value="on"
                            <?php
                            $checked = 0;
                            if ($force_install_createdatabase) {
                                $checked = 1;
                                print ' checked';
                            } ?>
                            <?php if ($force_install_noedit == 2 && $force_install_createdatabase !== null) {
                                print ' disabled';
                            } ?>
                        >
                    </td>
                    <td class="comment">
                        <?php echo $langs->trans("CheckToCreateDatabase"); ?>
                    </td>
                </tr>

                <tr class="hidesqlite">
                    <td class="label"><label for="db_user"><b><?php echo $langs->trans("Login"); ?></b></label></td>
                    <td class="label">
                        <input type="text"
                               id="db_user"
                               name="db_user"
                               value="<?php print (!empty($force_install_databaselogin)) ? $force_install_databaselogin : ($dolibarr_main_db_user ?? ''); ?>"
                            <?php if ($force_install_noedit == 2 && $force_install_databaselogin !== null) {
                                print ' disabled';
                            } ?>
                        >
                    </td>
                    <td class="comment"><?php echo $langs->trans("AdminLogin"); ?></td>
                </tr>

                <tr class="hidesqlite">
                    <td class="label"><label for="db_pass"><b><?php echo $langs->trans("Password"); ?></b></label></td>
                    <td class="label">
                        <input type="password" class="text-security"
                               id="db_pass" autocomplete="off"
                               name="db_pass"
                               value="<?php
                               // If $force_install_databasepass is on, we don't want to set password, we just show '***'. Real value will be extracted from the forced install file at step1.
                               // @phan-suppress-next-line PhanParamSuspiciousOrder
                               $autofill = ((!empty($_SESSION['dol_save_pass'])) ? $_SESSION['dol_save_pass'] : str_pad('', strlen($force_install_databasepass), '*'));
                               if (!empty($dolibarr_main_prod) && empty($_SESSION['dol_save_pass'])) {    // So value can't be found if install page still accessible
                                    $autofill = '';
                               }
                               print dol_escape_htmltag($autofill);
                               ?>"
                            <?php if ($force_install_noedit == 2 && $force_install_databasepass !== null) {
                                print ' disabled';
                            } ?>
                        >
                    </td>
                    <td class="comment"><?php echo $langs->trans("AdminPassword"); ?></td>
                </tr>

                <tr class="hidesqlite">
                    <td class="label"><label for="db_create_user"><?php echo $langs->trans("CreateUser"); ?></label>
                    </td>
                    <td class="label">
                        <input type="checkbox"
                               id="db_create_user"
                               name="db_create_user"
                               value="on"
                            <?php
                            $checked = 0;
                            if (!empty($force_install_createuser)) {
                                $checked = 1;
                                print ' checked';
                            } ?>
                            <?php if ($force_install_noedit == 2 && $force_install_createuser !== null) {
                                print ' disabled';
                            } ?>
                        >
                    </td>
                    <td class="comment">
                        <?php echo $langs->trans("CheckToCreateUser"); ?>
                    </td>
                </tr>


                <!-- Super access -->
                <?php
                $force_install_databaserootlogin = parse_database_login($force_install_databaserootlogin);
                $force_install_databaserootpass = parse_database_pass($force_install_databaserootpass);
                ?>
                <tr class="hidesqlite hideroot">
                    <td colspan="3" class="label"><br>
                        <h3>
                            <img class="valignmiddle inline-block paddingright" src="../theme/common/octicons/build/svg/shield.svg" width="20" alt="webserver"> <?php echo $langs->trans("DatabaseSuperUserAccess"); ?>
                        </h3>
                    </td>
                </tr>

                <tr class="hidesqlite hideroot">
                    <td class="label"><label for="db_user_root"><b><?php echo $langs->trans("Login"); ?></b></label>
                    </td>
                    <td class="label">
                        <input type="text"
                               id="db_user_root"
                               name="db_user_root"
                               class="needroot"
                               value="<?php print (!empty($force_install_databaserootlogin)) ? $force_install_databaserootlogin : (GETPOSTISSET('db_user_root') ? GETPOST('db_user_root') : (isset($db_user_root) ? $db_user_root : '')); ?>"
                            <?php if ($force_install_noedit > 0 && !empty($force_install_databaserootlogin)) {
                                print ' disabled';
                            } ?>
                        >
                    </td>
                    <td class="comment"><?php echo $langs->trans("DatabaseRootLoginDescription"); ?>
                        <!--
        <?php echo '<br>' . $langs->trans("Examples") . ':<br>' ?>
        <ul>
            <li>root (Mysql)</li>
            <li>postgres (PostgreSql)</li>
        </ul>
        </td>
         -->

                </tr>
                <tr class="hidesqlite hideroot">
                    <td class="label"><label for="db_pass_root"><b><?php echo $langs->trans("Password"); ?></b></label>
                    </td>
                    <td class="label">
                        <input type="password"
                               autocomplete="off"
                               id="db_pass_root"
                               name="db_pass_root"
                               class="needroot text-security"
                               value="<?php
                               // If $force_install_databaserootpass is on, we don't want to set password here, we just show '***'. Real value will be extracted from the forced install file at step1.
                               // @phan-suppress-next-line PhanParamSuspiciousOrder
                               $autofill = ((!empty($force_install_databaserootpass)) ? str_pad('', strlen($force_install_databaserootpass), '*') : (isset($db_pass_root) ? $db_pass_root : ''));
                               if (!empty($dolibarr_main_prod)) {
                                    $autofill = '';
                               }
                               // Do not autofill password if instance is a production instance
                               if (
                                   !empty($_SERVER["SERVER_NAME"]) && !in_array(
                                       $_SERVER["SERVER_NAME"],
                                       ['127.0.0.1', 'localhost', 'localhostgit']
                                   )
                               ) {
                                    $autofill = '';
                               }    // Do not autofill password for remote access
                               print dol_escape_htmltag($autofill);
                               ?>"
                            <?php if ($force_install_noedit > 0 && !empty($force_install_databaserootpass)) {
                                print ' disabled'; /* May be removed by javascript*/
                            } ?>
                        >
                    </td>
                    <td class="comment"><?php echo $langs->trans("KeepEmptyIfNoPassword"); ?>
                    </td>
                </tr>

            </table>
        </div>

        <script type="text/javascript">
            function init_needroot() {
                console.log("init_needroot force_install_noedit=<?php echo $force_install_noedit?>");
                console.log(jQuery("#db_create_database").is(":checked"));
                console.log(jQuery("#db_create_user").is(":checked"));

                if (jQuery("#db_create_database").is(":checked") || jQuery("#db_create_user").is(":checked")) {
                    console.log("init_needroot show root section");
                    jQuery(".hideroot").show();
                    <?php
                    if (empty($force_install_noedit)) { ?>
                    jQuery(".needroot").removeAttr('disabled');
                    <?php } ?>
                } else {
                    console.log("init_needroot hide root section");
                    jQuery(".hideroot").hide();
                    jQuery(".needroot").prop('disabled', true);
                }
            }

            function checkDatabaseName(databasename) {
                if (databasename.match(/[;\.]/)) {
                    return false;
                }
                return true;
            }

            function jscheckparam() {
                console.log("Click on jscheckparam");

                var ok = true;

                if (document.forminstall.main_dir.value == '') {
                    ok = false;
                    alert('<?php echo dol_escape_js($langs->transnoentities("ErrorFieldRequired", $langs->transnoentitiesnoconv("WebPagesDirectory"))); ?>');
                } else if (document.forminstall.main_data_dir.value == '') {
                    ok = false;
                    alert('<?php echo dol_escape_js($langs->transnoentities("ErrorFieldRequired", $langs->transnoentitiesnoconv("DocumentsDirectory"))); ?>');
                } else if (document.forminstall.main_url.value == '') {
                    ok = false;
                    alert('<?php echo dol_escape_js($langs->transnoentities("ErrorFieldRequired", $langs->transnoentitiesnoconv("URLRoot"))); ?>');
                } else if (document.forminstall.db_host.value == '') {
                    ok = false;
                    alert('<?php echo dol_escape_js($langs->transnoentities("ErrorFieldRequired", $langs->transnoentitiesnoconv("Server"))); ?>');
                } else if (document.forminstall.db_name.value == '') {
                    ok = false;
                    alert('<?php echo dol_escape_js($langs->transnoentities("ErrorFieldRequired", $langs->transnoentitiesnoconv("DatabaseName"))); ?>');
                } else if (!checkDatabaseName(document.forminstall.db_name.value)) {
                    ok = false;
                    alert('<?php echo dol_escape_js($langs->transnoentities("ErrorFieldCanNotContainSpecialCharacters", $langs->transnoentitiesnoconv("DatabaseName"))); ?>');
                }
                // If create database asked
                else if (document.forminstall.db_create_database.checked == true && (document.forminstall.db_user_root.value == '')) {
                    ok = false;
                    alert('<?php echo dol_escape_js($langs->transnoentities("YouAskToCreateDatabaseSoRootRequired")); ?>');
                    init_needroot();
                }
                // If create user asked
                else if (document.forminstall.db_create_user.checked == true && (document.forminstall.db_user_root.value == '')) {
                    ok = false;
                    alert('<?php echo dol_escape_js($langs->transnoentities("YouAskToCreateDatabaseUserSoRootRequired")); ?>');
                    init_needroot();
                }

                return ok;
            }


            jQuery(document).ready(function () { // TODO Test $( window ).load(function() to see if the init_needroot work better after a back

                var dbtype = jQuery("#db_type");

                dbtype.change(function () {
                    if (dbtype.val() == 'sqlite' || dbtype.val() == 'sqlite3') {
                        jQuery(".hidesqlite").hide();
                    } else {
                        jQuery(".hidesqlite").show();
                    }

                    // Automatically set default database ports and admin user
                    if (dbtype.val() == 'mysql' || dbtype.val() == 'mysqli') {
                        jQuery("#db_port").val(3306);
                        jQuery("#db_user_root").val('root');
                    } else if (dbtype.val() == 'pgsql') {
                        jQuery("#db_port").val(5432);
                        jQuery("#db_user_root").val('postgres');
                    } else if (dbtype.val() == 'mssql') {
                        jQuery("#db_port").val(1433);
                        jQuery("#db_user_root").val('sa');
                    }

                });

                jQuery("#db_create_database").click(function () {
                    console.log("click on db_create_database");
                    init_needroot();
                });
                jQuery("#db_create_user").click(function () {
                    console.log("click on db_create_user");
                    init_needroot();
                });
                <?php if ($force_install_noedit == 2 && empty($force_install_databasepass)) { ?>
                jQuery("#db_pass").focus();
                <?php } ?>

                init_needroot();
            });
        </script>


        <?php

// $db->close();    Not database connection yet

        dolibarr_install_syslog("- fileconf: end");
        pFooter($err, $setuplang, 'jscheckparam');
    }

    public function step1()
    {
        global $langs;
        global $conf;
        global $conffiletoshowshort;

        $conffile = Config::getDolibarrConfigFilename();
        $conffiletoshow = $conffile;

        if (!isset($conf)) {
            $conf = Config::getConf();
        }

        if (!isset($langs)) {
            $langs = Config::getLangs($conf);
            $langs->setDefaultLang('auto');
        }

        $action = GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : (empty($argv[1]) ? '' : $argv[1]);
        $setuplang = GETPOST('selectlang', 'aZ09', 3) ? GETPOST('selectlang', 'aZ09', 3) : (empty($argv[2]) ? 'auto' : $argv[2]);
        $langs->setDefaultLang($setuplang);

        $langs->loadLangs(["admin", "install", "errors"]);

// Dolibarr pages directory
        $main_dir = GETPOST('main_dir') ? GETPOST('main_dir') : (empty($argv[3]) ? '' : $argv[3]);
// Directory for generated documents (invoices, orders, ecm, etc...)
        $main_data_dir = GETPOST('main_data_dir') ? GETPOST('main_data_dir') : (empty($argv[4]) ? ($main_dir . '/documents') : $argv[4]);
// Dolibarr root URL
        $main_url = GETPOST('main_url') ? GETPOST('main_url') : (empty($argv[5]) ? '' : $argv[5]);
// Database login information
        $userroot = GETPOST('db_user_root', 'alpha') ? GETPOST('db_user_root', 'alpha') : (empty($argv[6]) ? '' : $argv[6]);
        $passroot = GETPOST('db_pass_root', 'none') ? GETPOST('db_pass_root', 'none') : (empty($argv[7]) ? '' : $argv[7]);
// Database server
        $db_type = GETPOST('db_type', 'aZ09') ? GETPOST('db_type', 'aZ09') : (empty($argv[8]) ? '' : $argv[8]);
        $db_host = GETPOST('db_host', 'alpha') ? GETPOST('db_host', 'alpha') : (empty($argv[9]) ? '' : $argv[9]);
        $db_name = GETPOST('db_name', 'aZ09') ? GETPOST('db_name', 'aZ09') : (empty($argv[10]) ? '' : $argv[10]);
        $db_user = GETPOST('db_user', 'alpha') ? GETPOST('db_user', 'alpha') : (empty($argv[11]) ? '' : $argv[11]);
        $db_pass = GETPOST('db_pass', 'none') ? GETPOST('db_pass', 'none') : (empty($argv[12]) ? '' : $argv[12]);
        $db_port = GETPOSTINT('db_port') ? GETPOSTINT('db_port') : (empty($argv[13]) ? '' : $argv[13]);
        $db_prefix = GETPOST('db_prefix', 'aZ09') ? GETPOST('db_prefix', 'aZ09') : (empty($argv[14]) ? '' : $argv[14]);
        $db_create_database = GETPOST('db_create_database', 'alpha') ? GETPOST('db_create_database', 'alpha') : (empty($argv[15]) ? '' : $argv[15]);
        $db_create_user = GETPOST('db_create_user', 'alpha') ? GETPOST('db_create_user', 'alpha') : (empty($argv[16]) ? '' : $argv[16]);
// Force https
        $main_force_https = ((GETPOST("main_force_https", 'alpha') && (GETPOST("main_force_https", 'alpha') == "on" || GETPOST("main_force_https", 'alpha') == 1)) ? '1' : '0');
// Use alternative directory
        $main_use_alt_dir = ((GETPOST("main_use_alt_dir", 'alpha') == '' || (GETPOST("main_use_alt_dir", 'alpha') == "on" || GETPOST("main_use_alt_dir", 'alpha') == 1)) ? '' : '//');
// Alternative root directory name
        $main_alt_dir_name = ((GETPOST("main_alt_dir_name", 'alpha') && GETPOST("main_alt_dir_name", 'alpha') != '') ? GETPOST("main_alt_dir_name", 'alpha') : 'custom');

        $dolibarr_main_distrib = 'standard';

        $dolibarr_main_document_root = BASE_PATH;

        session_start(); // To be able to keep info into session (used for not losing password during navigation. The password must not transit through parameters)

// Save a flag to tell to restore input value if we go back
        $_SESSION['dol_save_pass'] = $db_pass;
//$_SESSION['dol_save_passroot']=$passroot;

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
                $main_dir = detect_dolibarr_main_document_root();
                if (!empty($argv[3])) {
                    $main_dir = $argv[3]; // override when executing the script in command line
                }
                if (!empty($force_install_main_data_root)) {
                    $main_data_dir = $force_install_main_data_root;
                } else {
                    $main_data_dir = detect_dolibarr_main_data_root($main_dir);
                }
                if (!empty($argv[4])) {
                    $main_data_dir = $argv[4]; // override when executing the script in command line
                }
                $main_url = detect_dolibarr_main_url_root();
                if (!empty($argv[5])) {
                    $main_url = $argv[5]; // override when executing the script in command line
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
                    $db_type = $force_install_type;
                }
                if (!empty($force_install_dbserver)) {
                    $db_host = $force_install_dbserver;
                }
                if (!empty($force_install_database)) {
                    $db_name = $force_install_database;
                }
                if (!empty($force_install_databaselogin)) {
                    $db_user = $force_install_databaselogin;
                }
                if (!empty($force_install_databasepass)) {
                    $db_pass = $force_install_databasepass;
                }
                if (!empty($force_install_port)) {
                    $db_port = $force_install_port;
                }
                if (!empty($force_install_prefix)) {
                    $db_prefix = $force_install_prefix;
                }
                if (!empty($force_install_createdatabase)) {
                    $db_create_database = $force_install_createdatabase;
                }
                if (!empty($force_install_createuser)) {
                    $db_create_user = $force_install_createuser;
                }
                if (!empty($force_install_mainforcehttps)) {
                    $main_force_https = $force_install_mainforcehttps;
                }
            }

            if (!empty($force_install_distrib)) {
                $dolibarr_main_distrib = $force_install_distrib;
            }
        }


        $error = 0;


        /*
         *  View
         */

        dolibarr_install_syslog("--- step1: entering step1.php page");

        pHeader($langs->trans("ConfigurationFile"), "step2");

// Test if we can run a first install process
        if (!is_writable($conffile)) {
            print $langs->trans("ConfFileIsNotWritable", $conffiletoshow);
            pFooter(1, $setuplang, 'jscheckparam');
            exit;
        }


// Check parameters
        $is_sqlite = false;
        if (empty($db_type)) {
            print '<div class="error">' . $langs->trans("ErrorFieldRequired", $langs->transnoentities("DatabaseType")) . '</div>';
            $error++;
        } else {
            $is_sqlite = ($db_type === 'sqlite' || $db_type === 'sqlite3');
        }
        if (empty($db_host) && !$is_sqlite) {
            print '<div class="error">' . $langs->trans("ErrorFieldRequired", $langs->transnoentities("Server")) . '</div>';
            $error++;
        }
        if (empty($db_name)) {
            print '<div class="error">' . $langs->trans("ErrorFieldRequired", $langs->transnoentities("DatabaseName")) . '</div>';
            $error++;
        }
        if (empty($db_user) && !$is_sqlite) {
            print '<div class="error">' . $langs->trans("ErrorFieldRequired", $langs->transnoentities("Login")) . '</div>';
            $error++;
        }
        if (!empty($db_port) && !is_numeric($db_port)) {
            print '<div class="error">' . $langs->trans("ErrorBadValueForParameter", $db_port, $langs->transnoentities("Port")) . '</div>';
            $error++;
        }
        if (!empty($db_prefix) && !preg_match('/^[a-z0-9]+_$/i', $db_prefix)) {
            print '<div class="error">' . $langs->trans("ErrorBadValueForParameter", $db_prefix, $langs->transnoentities("DatabasePrefix")) . '</div>';
            $error++;
        }

        $main_dir = dol_sanitizePathName($main_dir);
        $main_data_dir = dol_sanitizePathName($main_data_dir);

        if (!filter_var($main_url, FILTER_VALIDATE_URL)) {
            print '<div class="error">' . $langs->trans("ErrorBadValueForParameter", $main_url, $langs->transnoentitiesnoconv("URLRoot")) . '</div>';
            print '<br>';
            print $langs->trans("ErrorGoBackAndCorrectParameters");
            $error++;
        }

// Remove last / into dans main_dir
        if (substr($main_dir, dol_strlen($main_dir) - 1) == "/") {
            $main_dir = substr($main_dir, 0, dol_strlen($main_dir) - 1);
        }

// Remove last / into dans main_url
        if (!empty($main_url) && substr($main_url, dol_strlen($main_url) - 1) == "/") {
            $main_url = substr($main_url, 0, dol_strlen($main_url) - 1);
        }

        if (!dol_is_dir($main_dir . '/core/db/')) {
            print '<div class="error">' . $langs->trans("ErrorBadValueForParameter", $main_dir, $langs->transnoentitiesnoconv("WebPagesDirectory")) . '</div>';
            print '<br>';
            //print $langs->trans("BecauseConnectionFailedParametersMayBeWrong").'<br><br>';
            print $langs->trans("ErrorGoBackAndCorrectParameters");
            $error++;
        }

// Test database connection
        if (!$error) {
            $result = @include_once $main_dir . "/core/db/" . $db_type . '.class.php';
            if ($result) {
                // If we require database or user creation we need to connect as root, so we need root login credentials
                if (!empty($db_create_database) && !$userroot) {
                    print '<div class="error">' . $langs->trans("YouAskDatabaseCreationSoDolibarrNeedToConnect", $db_name) . '</div>';
                    print '<br>';
                    print $langs->trans("BecauseConnectionFailedParametersMayBeWrong") . '<br><br>';
                    print $langs->trans("ErrorGoBackAndCorrectParameters");
                    $error++;
                }
                if (!empty($db_create_user) && !$userroot) {
                    print '<div class="error">' . $langs->trans("YouAskLoginCreationSoDolibarrNeedToConnect", $db_user) . '</div>';
                    print '<br>';
                    print $langs->trans("BecauseConnectionFailedParametersMayBeWrong") . '<br><br>';
                    print $langs->trans("ErrorGoBackAndCorrectParameters");
                    $error++;
                }

                // If we need root access
                if (!$error && (!empty($db_create_database) || !empty($db_create_user))) {
                    $databasefortest = $db_name;
                    if (!empty($db_create_database)) {
                        if ($db_type == 'mysql' || $db_type == 'mysqli') {
                            $databasefortest = 'mysql';
                        } elseif ($db_type == 'pgsql') {
                            $databasefortest = 'postgres';
                        } else {
                            $databasefortest = 'master';
                        }
                    }

                    $db = getDoliDBInstance($db_type, $db_host, $userroot, $passroot, $databasefortest, (int) $db_port);

                    dol_syslog("databasefortest=" . $databasefortest . " connected=" . $db->connected . " database_selected=" . $db->database_selected, LOG_DEBUG);
                    //print "databasefortest=".$databasefortest." connected=".$db->connected." database_selected=".$db->database_selected;

                    if (empty($db_create_database) && $db->connected && !$db->database_selected) {
                        print '<div class="error">' . $langs->trans("ErrorConnectedButDatabaseNotFound", $db_name) . '</div>';
                        print '<br>';
                        if (!$db->connected) {
                            print $langs->trans("IfDatabaseNotExistsGoBackAndUncheckCreate") . '<br><br>';
                        }
                        print $langs->trans("ErrorGoBackAndCorrectParameters");
                        $error++;
                    } elseif ($db->error && !(!empty($db_create_database) && $db->connected)) {
                        // Note: you may experience error here with message "No such file or directory" when mysql was installed for the first time but not yet launched.
                        if ($db->error == "No such file or directory") {
                            print '<div class="error">' . $langs->trans("ErrorToConnectToMysqlCheckInstance") . '</div>';
                        } else {
                            print '<div class="error">' . $db->error . '</div>';
                        }
                        if (!$db->connected) {
                            print $langs->trans("BecauseConnectionFailedParametersMayBeWrong") . '<br><br>';
                        }
                        //print '<a href="#" onClick="javascript: history.back();">';
                        print $langs->trans("ErrorGoBackAndCorrectParameters");
                        //print '</a>';
                        $error++;
                    }
                }

                // If we need simple access
                if (!$error && (empty($db_create_database) && empty($db_create_user))) {
                    $db = getDoliDBInstance($db_type, $db_host, $db_user, $db_pass, $db_name, (int) $db_port);

                    if ($db->error) {
                        print '<div class="error">' . $db->error . '</div>';
                        if (!$db->connected) {
                            print $langs->trans("BecauseConnectionFailedParametersMayBeWrong") . '<br><br>';
                        }
                        //print '<a href="#" onClick="javascript: history.back();">';
                        print $langs->trans("ErrorGoBackAndCorrectParameters");
                        //print '</a>';
                        $error++;
                    }
                }
            } else {
                print "<br>\nFailed to include_once(\"" . $main_dir . "/core/db/" . $db_type . ".class.php\")<br>\n";
                print '<div class="error">' . $langs->trans("ErrorWrongValueForParameter", $langs->transnoentities("WebPagesDirectory")) . '</div>';
                //print '<a href="#" onClick="javascript: history.back();">';
                print $langs->trans("ErrorGoBackAndCorrectParameters");
                //print '</a>';
                $error++;
            }
        } else {
            if (isset($db)) {
                print $db->lasterror();
            }
            if (isset($db) && !$db->connected) {
                print '<br>' . $langs->trans("BecauseConnectionFailedParametersMayBeWrong") . '<br><br>';
            }
            print $langs->trans("ErrorGoBackAndCorrectParameters");
            $error++;
        }

        if (!$error && $db->connected) {
            if (!empty($db_create_database)) {
                $result = $db->select_db($db_name);
                if ($result) {
                    print '<div class="error">' . $langs->trans("ErrorDatabaseAlreadyExists", $db_name) . '</div>';
                    print $langs->trans("IfDatabaseExistsGoBackAndCheckCreate") . '<br><br>';
                    print $langs->trans("ErrorGoBackAndCorrectParameters");
                    $error++;
                }
            }
        }

// Define $defaultCharacterSet and $defaultDBSortingCollation
        if (!$error && $db->connected) {
            if (!empty($db_create_database)) {    // If we create database, we force default value
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
            $db_character_set = $defaultCharacterSet;
            $db_collation = $defaultDBSortingCollation;
            dolibarr_install_syslog("step1: db_character_set=" . $db_character_set . " db_collation=" . $db_collation);
        }


// Create config file
        if (!$error && $db->connected && $action == "set") {
            umask(0);
            if (is_array($_POST)) {
                foreach ($_POST as $key => $value) {
                    if (!preg_match('/^db_pass/i', $key)) {
                        dolibarr_install_syslog("step1: choice for " . $key . " = " . $value);
                    }
                }
            }

            // Show title of step
            print '<h3><img class="valignmiddle inline-block paddingright" src="../theme/common/octicons/build/svg/gear.svg" width="20" alt="Configuration"> ' . $langs->trans("ConfigurationFile") . '</h3>';
            print '<table cellspacing="0" width="100%" cellpadding="1" border="0">';

            // Check parameter main_dir
            if (!$error) {
                if (!is_dir($main_dir)) {
                    dolibarr_install_syslog("step1: directory '" . $main_dir . "' is unavailable or can't be accessed");

                    print "<tr><td>";
                    print $langs->trans("ErrorDirDoesNotExists", $main_dir) . '<br>';
                    print $langs->trans("ErrorWrongValueForParameter", $langs->transnoentitiesnoconv("WebPagesDirectory")) . '<br>';
                    print $langs->trans("ErrorGoBackAndCorrectParameters") . '<br><br>';
                    print '</td><td>';
                    print $langs->trans("Error");
                    print "</td></tr>";
                    $error++;
                }
            }

            if (!$error) {
                dolibarr_install_syslog("step1: directory '" . $main_dir . "' exists");
            }


            // Create subdirectory main_data_dir
            if (!$error) {
                // Create directory for documents
                if (!is_dir($main_data_dir)) {
                    dol_mkdir($main_data_dir);
                }

                if (!is_dir($main_data_dir)) {
                    print "<tr><td>" . $langs->trans("ErrorDirDoesNotExists", $main_data_dir);
                    print ' ' . $langs->trans("YouMustCreateItAndAllowServerToWrite");
                    print '</td><td>';
                    print '<span class="error">' . $langs->trans("Error") . '</span>';
                    print "</td></tr>";
                    print '<tr><td colspan="2"><br>' . $langs->trans("CorrectProblemAndReloadPage", $_SERVER['PHP_SELF'] . '?testget=ok') . '</td></tr>';
                    $error++;
                } else {
                    // Create .htaccess file in document directory
                    $pathhtaccess = $main_data_dir . '/.htaccess';
                    if (!file_exists($pathhtaccess)) {
                        dolibarr_install_syslog("step1: .htaccess file did not exist, we created it in '" . $main_data_dir . "'");
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
                    $dir[] = $main_data_dir . "/mycompany";
                    $dir[] = $main_data_dir . "/medias";
                    $dir[] = $main_data_dir . "/users";
                    $dir[] = $main_data_dir . "/facture";
                    $dir[] = $main_data_dir . "/propale";
                    $dir[] = $main_data_dir . "/ficheinter";
                    $dir[] = $main_data_dir . "/produit";
                    $dir[] = $main_data_dir . "/doctemplates";

                    // Loop on each directory of dir [] to create them if they do not exist
                    $num = count($dir);
                    for ($i = 0; $i < $num; $i++) {
                        if (is_dir($dir[$i])) {
                            dolibarr_install_syslog("step1: directory '" . $dir[$i] . "' exists");
                        } else {
                            if (dol_mkdir($dir[$i]) < 0) {
                                print "<tr><td>";
                                print "Failed to create directory: " . $dir[$i];
                                print '</td><td>';
                                print $langs->trans("Error");
                                print "</td></tr>";
                                $error++;
                            } else {
                                dolibarr_install_syslog("step1: directory '" . $dir[$i] . "' created");
                            }
                        }
                    }

                    require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

                    // Copy directory medias
                    $srcroot = $main_dir . '/install/medias';
                    $destroot = $main_data_dir . '/medias';
                    dolCopyDir($srcroot, $destroot, 0, 0);

                    if ($error) {
                        print "<tr><td>" . $langs->trans("ErrorDirDoesNotExists", $main_data_dir);
                        print ' ' . $langs->trans("YouMustCreateItAndAllowServerToWrite");
                        print '</td><td>';
                        print '<span class="error">' . $langs->trans("Error") . '</span>';
                        print "</td></tr>";
                        print '<tr><td colspan="2"><br>' . $langs->trans("CorrectProblemAndReloadPage", $_SERVER['PHP_SELF'] . '?testget=ok') . '</td></tr>';
                    } else {
                        //ODT templates
                        $srcroot = $main_dir . '/install/doctemplates';
                        $destroot = $main_data_dir . '/doctemplates';
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
                            $result = dol_copy($src, $dest, 0, 0);
                            if ($result < 0) {
                                print '<tr><td colspan="2"><br>' . $langs->trans('ErrorFailToCopyFile', $src, $dest) . '</td></tr>';
                            }
                        }
                    }
                }
            }

            // Table prefix
            $main_db_prefix = (!empty($db_prefix) ? $db_prefix : static::DEFAULT_DATABASE_PREFIX);

            // Write conf file on disk
            if (!$error) {
                // Save old conf file on disk
                if (file_exists("$conffile")) {
                    // We must ignore errors as an existing old file may already exist and not be replaceable or
                    // the installer (like for ubuntu) may not have permission to create another file than conf.php.
                    // Also no other process must be able to read file or we expose the new file, so content with password.
                    @dol_copy($conffile, $conffile . '.old', '0400');
                }

                $error = 0;

                $key = md5(uniqid(mt_rand(), true)); // Generate random hash

                $fp = fopen($conffile, "w");
                if ($fp) {
                    clearstatcache();

                    fwrite($fp, '<?php' . "\n");
                    fwrite($fp, '//' . "\n");
                    fwrite($fp, '// File generated by Dolibarr installer ' . DOL_VERSION . ' on ' . dol_print_date(dol_now(), '') . "\n");
                    fwrite($fp, '//' . "\n");
                    fwrite($fp, '// Take a look at conf.php.example file for an example of ' . $conffiletoshowshort . ' file' . "\n");
                    fwrite($fp, '// and explanations for all possibles parameters.' . "\n");
                    fwrite($fp, '//' . "\n");

                    fwrite($fp, '$dolibarr_main_url_root=\'' . dol_escape_php(trim($main_url), 1) . '\';');
                    fwrite($fp, "\n");

                    fwrite($fp, '$dolibarr_main_document_root="' . dol_escape_php(dol_sanitizePathName(trim($main_dir))) . '";');
                    fwrite($fp, "\n");

                    fwrite($fp, $main_use_alt_dir . '$dolibarr_main_url_root_alt=\'' . dol_escape_php(trim("/" . $main_alt_dir_name), 1) . '\';');
                    fwrite($fp, "\n");

                    fwrite($fp, $main_use_alt_dir . '$dolibarr_main_document_root_alt="' . dol_escape_php(dol_sanitizePathName(trim($main_dir . "/" . $main_alt_dir_name))) . '";');
                    fwrite($fp, "\n");

                    fwrite($fp, '$dolibarr_main_data_root="' . dol_escape_php(dol_sanitizePathName(trim($main_data_dir))) . '";');
                    fwrite($fp, "\n");

                    fwrite($fp, '$dolibarr_main_db_host=\'' . dol_escape_php(trim($db_host), 1) . '\';');
                    fwrite($fp, "\n");

                    fwrite($fp, '$dolibarr_main_db_port=\'' . ((int) $db_port) . '\';');
                    fwrite($fp, "\n");

                    fwrite($fp, '$dolibarr_main_db_name=\'' . dol_escape_php(trim($db_name), 1) . '\';');
                    fwrite($fp, "\n");

                    fwrite($fp, '$dolibarr_main_db_prefix=\'' . dol_escape_php(trim($main_db_prefix), 1) . '\';');
                    fwrite($fp, "\n");

                    fwrite($fp, '$dolibarr_main_db_user=\'' . dol_escape_php(trim($db_user), 1) . '\';');
                    fwrite($fp, "\n");
                    fwrite($fp, '$dolibarr_main_db_pass=\'' . dol_escape_php(trim($db_pass), 1) . '\';');
                    fwrite($fp, "\n");

                    fwrite($fp, '$dolibarr_main_db_type=\'' . dol_escape_php(trim($db_type), 1) . '\';');
                    fwrite($fp, "\n");

                    fwrite($fp, '$dolibarr_main_db_character_set=\'' . dol_escape_php(trim($db_character_set), 1) . '\';');
                    fwrite($fp, "\n");

                    fwrite($fp, '$dolibarr_main_db_collation=\'' . dol_escape_php(trim($db_collation), 1) . '\';');
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

                    fwrite($fp, '$dolibarr_main_force_https=\'' . dol_escape_php($main_force_https, 1) . '\';');
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
                        $force_dolibarr_lib_FPDF_PATH = '';  // @phan-suppress-current-line PhanPluginRedundantAssignment
                    }
                    fwrite($fp, '$dolibarr_lib_FPDF_PATH="' . dol_escape_php(dol_sanitizePathName($force_dolibarr_lib_FPDF_PATH)) . '";');
                    fwrite($fp, "\n");
                    if (empty($force_dolibarr_lib_TCPDF_PATH)) {
                        fwrite($fp, '//');
                        $force_dolibarr_lib_TCPDF_PATH = '';  // @phan-suppress-current-line PhanPluginRedundantAssignment
                    }
                    fwrite($fp, '$dolibarr_lib_TCPDF_PATH="' . dol_escape_php(dol_sanitizePathName($force_dolibarr_lib_TCPDF_PATH)) . '";');
                    fwrite($fp, "\n");
                    if (empty($force_dolibarr_lib_FPDI_PATH)) {
                        fwrite($fp, '//');
                        $force_dolibarr_lib_FPDI_PATH = '';  // @phan-suppress-current-line PhanPluginRedundantAssignment
                    }
                    fwrite($fp, '$dolibarr_lib_FPDI_PATH="' . dol_escape_php(dol_sanitizePathName($force_dolibarr_lib_FPDI_PATH)) . '";');
                    fwrite($fp, "\n");
                    if (empty($force_dolibarr_lib_TCPDI_PATH)) {
                        fwrite($fp, '//');
                        $force_dolibarr_lib_TCPDI_PATH = '';  // @phan-suppress-current-line PhanPluginRedundantAssignment
                    }
                    fwrite($fp, '$dolibarr_lib_TCPDI_PATH="' . dol_escape_php(dol_sanitizePathName($force_dolibarr_lib_TCPDI_PATH)) . '";');
                    fwrite($fp, "\n");
                    if (empty($force_dolibarr_lib_GEOIP_PATH)) {
                        fwrite($fp, '//');
                        $force_dolibarr_lib_GEOIP_PATH = '';  // @phan-suppress-current-line PhanPluginRedundantAssignment
                    }
                    fwrite($fp, '$dolibarr_lib_GEOIP_PATH="' . dol_escape_php(dol_sanitizePathName($force_dolibarr_lib_GEOIP_PATH)) . '";');
                    fwrite($fp, "\n");
                    if (empty($force_dolibarr_lib_NUSOAP_PATH)) {
                        fwrite($fp, '//');
                        $force_dolibarr_lib_NUSOAP_PATH = '';  // @phan-suppress-current-line PhanPluginRedundantAssignment
                    }
                    fwrite($fp, '$dolibarr_lib_NUSOAP_PATH="' . dol_escape_php(dol_sanitizePathName($force_dolibarr_lib_NUSOAP_PATH)) . '";');
                    fwrite($fp, "\n");
                    if (empty($force_dolibarr_lib_ODTPHP_PATH)) {
                        fwrite($fp, '//');
                        $force_dolibarr_lib_ODTPHP_PATH = '';  // @phan-suppress-current-line PhanPluginRedundantAssignment
                    }
                    fwrite($fp, '$dolibarr_lib_ODTPHP_PATH="' . dol_escape_php(dol_sanitizePathName($force_dolibarr_lib_ODTPHP_PATH)) . '";');
                    fwrite($fp, "\n");
                    if (empty($force_dolibarr_lib_ODTPHP_PATHTOPCLZIP)) {
                        fwrite($fp, '//');
                        $force_dolibarr_lib_ODTPHP_PATHTOPCLZIP = '';  // @phan-suppress-current-line PhanPluginRedundantAssignment
                    }
                    fwrite($fp, '$dolibarr_lib_ODTPHP_PATHTOPCLZIP="' . dol_escape_php(dol_sanitizePathName($force_dolibarr_lib_ODTPHP_PATHTOPCLZIP)) . '";');
                    fwrite($fp, "\n");
                    if (empty($force_dolibarr_js_CKEDITOR)) {
                        fwrite($fp, '//');
                        $force_dolibarr_js_CKEDITOR = '';  // @phan-suppress-current-line PhanPluginRedundantAssignment
                    }
                    fwrite($fp, '$dolibarr_js_CKEDITOR=\'' . dol_escape_php($force_dolibarr_js_CKEDITOR, 1) . '\';');
                    fwrite($fp, "\n");
                    if (empty($force_dolibarr_js_JQUERY)) {
                        fwrite($fp, '//');
                        $force_dolibarr_js_JQUERY = '';  // @phan-suppress-current-line PhanPluginRedundantAssignment
                    }
                    fwrite($fp, '$dolibarr_js_JQUERY=\'' . dol_escape_php($force_dolibarr_js_JQUERY, 1) . '\';');
                    fwrite($fp, "\n");
                    if (empty($force_dolibarr_js_JQUERY_UI)) {
                        fwrite($fp, '//');
                        $force_dolibarr_js_JQUERY_UI = '';  // @phan-suppress-current-line PhanPluginRedundantAssignment
                    }
                    fwrite($fp, '$dolibarr_js_JQUERY_UI=\'' . dol_escape_php($force_dolibarr_js_JQUERY_UI, 1) . '\';');
                    fwrite($fp, "\n");

                    // Write params to overwrites default font path
                    fwrite($fp, "\n");
                    if (empty($force_dolibarr_font_DOL_DEFAULT_TTF)) {
                        fwrite($fp, '//');
                        $force_dolibarr_font_DOL_DEFAULT_TTF = '';  // @phan-suppress-current-line PhanPluginRedundantAssignment
                    }
                    fwrite($fp, '$dolibarr_font_DOL_DEFAULT_TTF=\'' . dol_escape_php($force_dolibarr_font_DOL_DEFAULT_TTF, 1) . '\';');
                    fwrite($fp, "\n");
                    if (empty($force_dolibarr_font_DOL_DEFAULT_TTF_BOLD)) {
                        fwrite($fp, '//');
                        $force_dolibarr_font_DOL_DEFAULT_TTF_BOLD = '';  // @phan-suppress-current-line PhanPluginRedundantAssignment
                    }
                    fwrite($fp, '$dolibarr_font_DOL_DEFAULT_TTF_BOLD=\'' . dol_escape_php($force_dolibarr_font_DOL_DEFAULT_TTF_BOLD, 1) . '\';');
                    fwrite($fp, "\n");

                    // Other
                    fwrite($fp, '$dolibarr_main_distrib=\'' . dol_escape_php(trim($dolibarr_main_distrib), 1) . '\';');
                    fwrite($fp, "\n");

                    fclose($fp);

                    if (file_exists("$conffile")) {
                        include $conffile; // force config reload, do not put include_once
                        conf($dolibarr_main_document_root);

                        print "<tr><td>";
                        print $langs->trans("SaveConfigurationFile");
                        print ' <strong>' . $conffile . '</strong>';
                        print "</td><td>";
                        print '<img src="../theme/eldy/img/tick.png" alt="Ok">';
                        print "</td></tr>";
                    } else {
                        $error++;
                    }
                }
            }

            // Create database and admin user database
            if (!$error) {
                // We reload configuration file
                conf($dolibarr_main_document_root);

                print '<tr><td>';
                print $langs->trans("ConfFileReload");
                print '</td>';
                print '<td><img src="../theme/eldy/img/tick.png" alt="Ok"></td></tr>';

                // Create database user if requested
                if (isset($db_create_user) && ($db_create_user == "1" || $db_create_user == "on")) {
                    dolibarr_install_syslog("step1: create database user: " . $dolibarr_main_db_user);

                    //print $conf->db->host." , ".$conf->db->name." , ".$conf->db->user." , ".$conf->db->port;
                    $databasefortest = $conf->db->name;
                    if ($conf->db->type == 'mysql' || $conf->db->type == 'mysqli') {
                        $databasefortest = 'mysql';
                    } elseif ($conf->db->type == 'pgsql') {
                        $databasefortest = 'postgres';
                    } elseif ($conf->db->type == 'mssql') {
                        $databasefortest = 'master';
                    }

                    // Check database connection

                    $db = getDoliDBInstance($conf->db->type, $conf->db->host, $userroot, $passroot, $databasefortest, (int) $conf->db->port);

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
                                print $langs->trans("UserCreation") . ' : ';
                                print $dolibarr_main_db_user;
                                print '</td>';
                                print '<td>' . $langs->trans("Error") . ": A password for database user is mandatory.</td></tr>";
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
                                    print $langs->trans("UserCreation") . ' : ';
                                    print $dolibarr_main_db_user;
                                    print '</td>';
                                    print '<td><img src="../theme/eldy/img/tick.png" alt="Ok"></td></tr>';
                                } else {
                                    if (
                                        $db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS'
                                        || $db->errno() == 'DB_ERROR_KEY_NAME_ALREADY_EXISTS'
                                        || $db->errno() == 'DB_ERROR_USER_ALREADY_EXISTS'
                                    ) {
                                        dolibarr_install_syslog("step1: user already exists");
                                        print '<tr><td>';
                                        print $langs->trans("UserCreation") . ' : ';
                                        print $dolibarr_main_db_user;
                                        print '</td>';
                                        print '<td>' . $langs->trans("LoginAlreadyExists") . '</td></tr>';
                                    } else {
                                        dolibarr_install_syslog("step1: failed to create user", LOG_ERR);
                                        print '<tr><td>';
                                        print $langs->trans("UserCreation") . ' : ';
                                        print $dolibarr_main_db_user;
                                        print '</td>';
                                        print '<td>' . $langs->trans("Error") . ': ' . $db->errno() . ' ' . $db->error() . ($db->error ? '. ' . $db->error : '') . "</td></tr>";
                                    }
                                }
                            }

                            $db->close();
                        } else {
                            print '<tr><td>';
                            print $langs->trans("UserCreation") . ' : ';
                            print $dolibarr_main_db_user;
                            print '</td>';
                            print '<td><img src="../theme/eldy/img/error.png" alt="Error"></td>';
                            print '</tr>';

                            // warning message due to connection failure
                            print '<tr><td colspan="2"><br>';
                            print $langs->trans("YouAskDatabaseCreationSoDolibarrNeedToConnect", $dolibarr_main_db_user, $dolibarr_main_db_host, $userroot);
                            print '<br>';
                            print $langs->trans("BecauseConnectionFailedParametersMayBeWrong") . '<br><br>';
                            print $langs->trans("ErrorGoBackAndCorrectParameters") . '<br><br>';
                            print '</td></tr>';

                            $error++;
                        }
                    }
                }   // end of user account creation

                $conf = Config::getConf(true);

                // If database creation was asked, we create it
                if (!$error && (isset($db_create_database) && ($db_create_database == "1" || $db_create_database == "on"))) {
                    dolibarr_install_syslog("step1: create database: " . $dolibarr_main_db_name . " " . $dolibarr_main_db_character_set . " " . $dolibarr_main_db_collation . " " . $dolibarr_main_db_user);
                    $newdb = getDoliDBInstance($conf->db->type, $conf->db->host, $userroot, $passroot, '', (int) $conf->db->port);
                    //print 'eee'.$conf->db->type." ".$conf->db->host." ".$userroot." ".$passroot." ".$conf->db->port." ".$newdb->connected." ".$newdb->forcecharset;exit;

                    if ($newdb->connected) {
                        $result = $newdb->DDLCreateDb($dolibarr_main_db_name, $dolibarr_main_db_character_set, $dolibarr_main_db_collation, $dolibarr_main_db_user);

                        if ($result) {
                            print '<tr><td>';
                            print $langs->trans("DatabaseCreation") . " (" . $langs->trans("User") . " " . $userroot . ") : ";
                            print $dolibarr_main_db_name;
                            print '</td>';
                            print '<td><img src="../theme/eldy/img/tick.png" alt="Ok"></td></tr>';

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
                            print $langs->trans("ErrorFailedToCreateDatabase", $dolibarr_main_db_name) . '<br>';
                            print $newdb->lasterror() . '<br>';
                            print $langs->trans("IfDatabaseExistsGoBackAndCheckCreate");
                            print '<br>';
                            print '</td></tr>';

                            dolibarr_install_syslog('step1: failed to create database ' . $dolibarr_main_db_name . ' ' . $newdb->lasterrno() . ' ' . $newdb->lasterror(), LOG_ERR);
                            $error++;
                        }
                        $newdb->close();
                    } else {
                        print '<tr><td>';
                        print $langs->trans("DatabaseCreation") . " (" . $langs->trans("User") . " " . $userroot . ") : ";
                        print $dolibarr_main_db_name;
                        print '</td>';
                        print '<td><img src="../theme/eldy/img/error.png" alt="Error"></td>';
                        print '</tr>';

                        // warning message
                        print '<tr><td colspan="2"><br>';
                        print $langs->trans("YouAskDatabaseCreationSoDolibarrNeedToConnect", $dolibarr_main_db_user, $dolibarr_main_db_host, $userroot);
                        print '<br>';
                        print $langs->trans("BecauseConnectionFailedParametersMayBeWrong") . '<br><br>';
                        print $langs->trans("ErrorGoBackAndCorrectParameters") . '<br><br>';
                        print '</td></tr>';

                        $error++;
                    }
                }   // end of create database

                // We test access with dolibarr database user (not admin)
                if (!$error) {
                    dolibarr_install_syslog("step1: connection type=" . $conf->db->type . " on host=" . $conf->db->host . " port=" . $conf->db->port . " user=" . $conf->db->user . " name=" . $conf->db->name);
                    //print "connection de type=".$conf->db->type." sur host=".$conf->db->host." port=".$conf->db->port." user=".$conf->db->user." name=".$conf->db->name;

                    $db = getDoliDBInstance($conf->db->type, $conf->db->host, $conf->db->user, $conf->db->pass, $conf->db->name, (int) $conf->db->port);

                    if ($db->connected) {
                        dolibarr_install_syslog("step1: connection to server by user " . $conf->db->user . " ok");
                        print "<tr><td>";
                        print $langs->trans("ServerConnection") . " (" . $langs->trans("User") . " " . $conf->db->user . ") : ";
                        print $dolibarr_main_db_host;
                        print "</td><td>";
                        print '<img src="../theme/eldy/img/tick.png" alt="Ok">';
                        print "</td></tr>";

                        // server access ok, basic access ok
                        if ($db->database_selected) {
                            dolibarr_install_syslog("step1: connection to database " . $conf->db->name . " by user " . $conf->db->user . " ok");
                            print "<tr><td>";
                            print $langs->trans("DatabaseConnection") . " (" . $langs->trans("User") . " " . $conf->db->user . ") : ";
                            print $dolibarr_main_db_name;
                            print "</td><td>";
                            print '<img src="../theme/eldy/img/tick.png" alt="Ok">';
                            print "</td></tr>";

                            $error = 0;
                        } else {
                            dolibarr_install_syslog("step1: connection to database " . $conf->db->name . " by user " . $conf->db->user . " failed", LOG_ERR);
                            print "<tr><td>";
                            print $langs->trans("DatabaseConnection") . " (" . $langs->trans("User") . " " . $conf->db->user . ") : ";
                            print $dolibarr_main_db_name;
                            print '</td><td>';
                            print '<img src="../theme/eldy/img/error.png" alt="Error">';
                            print "</td></tr>";

                            // warning message
                            print '<tr><td colspan="2"><br>';
                            print $langs->trans('CheckThatDatabasenameIsCorrect', $dolibarr_main_db_name) . '<br>';
                            print $langs->trans('IfAlreadyExistsCheckOption') . '<br>';
                            print $langs->trans("ErrorGoBackAndCorrectParameters") . '<br><br>';
                            print '</td></tr>';

                            $error++;
                        }
                    } else {
                        dolibarr_install_syslog("step1: connection to server by user " . $conf->db->user . " failed", LOG_ERR);
                        print "<tr><td>";
                        print $langs->trans("ServerConnection") . " (" . $langs->trans("User") . " " . $conf->db->user . ") : ";
                        print $dolibarr_main_db_host;
                        print '</td><td>';
                        print '<img src="../theme/eldy/img/error.png" alt="Error">';
                        print "</td></tr>";

                        // warning message
                        print '<tr><td colspan="2"><br>';
                        print $langs->trans("ErrorConnection", $conf->db->host, $conf->db->name, $conf->db->user);
                        print $langs->trans('IfLoginDoesNotExistsCheckCreateUser') . '<br>';
                        print $langs->trans("ErrorGoBackAndCorrectParameters") . '<br><br>';
                        print '</td></tr>';

                        $error++;
                    }
                }
            }

            print '</table>';
        }

        ?>

        <script type="text/javascript">
            function jsinfo() {
                ok = true;

                //alert('<?php echo dol_escape_js($langs->transnoentities("NextStepMightLastALongTime")); ?>');

                document.getElementById('nextbutton').style.visibility = "hidden";
                document.getElementById('pleasewait').style.visibility = "visible";

                return ok;
            }
        </script>

        <?php

        $ret = 0;
        if ($error && isset($argv[1])) {
            $ret = 1;
        }
        dolibarr_install_syslog("Exit " . $ret);

        dolibarr_install_syslog("--- step1: end");

        pFooter($error ? 1 : 0, $setuplang, 'jsinfo', 1);

// Return code if ran from command line
        if ($ret) {
            exit($ret);
        }
    }

    public function step2()
    {
        global $langs;


        $conffile = Config::getDolibarrConfigFilename();
        $conffiletoshow = $conffile;

        $conf = Config::getConf(true);

        if (!isset($langs)) {
            $langs = Config::getLangs($conf);
            $langs->setDefaultLang('auto');
        }

        $step = 2;
        $ok = 0;

        $dolibarr_main_db_type = $conf->db->type;
        $dolibarr_main_db_prefix = $conf->db->prefix;


// This page can be long. We increase the time allowed. / Cette page peut etre longue. On augmente le delai autorise.
// Only works if you are not in safe_mode. / Ne fonctionne que si on est pas en safe_mode.

        $err = error_reporting();
        error_reporting(0);      // Disable all errors
//error_reporting(E_ALL);
        @set_time_limit(1800);   // Need 1800 on some very slow OS like Windows 7/64
        error_reporting($err);

        $action = GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : (empty($argv[1]) ? '' : $argv[1]);
        $setuplang = GETPOST('selectlang', 'aZ09', 3) ? GETPOST('selectlang', 'aZ09', 3) : (empty($argv[2]) ? 'auto' : $argv[2]);
        $langs->setDefaultLang($setuplang);

        $langs->loadLangs(["admin", "install"]);


// Choice of DBMS

        $choix = 0;
        if ($dolibarr_main_db_type == "mysqli") {
            $choix = 1;
        }
        if ($dolibarr_main_db_type == "pgsql") {
            $choix = 2;
        }
        if ($dolibarr_main_db_type == "mssql") {
            $choix = 3;
        }
        if ($dolibarr_main_db_type == "sqlite") {
            $choix = 4;
        }
        if ($dolibarr_main_db_type == "sqlite3") {
            $choix = 5;
        }
//if (empty($choix)) dol_print_error(null,'Database type '.$dolibarr_main_db_type.' not supported into step2.php page');


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

        pHeader($langs->trans("CreateDatabaseObjects"), "step4");

// Test if we can run a first install process
        if (!is_writable($conffile)) {
            print $langs->trans("ConfFileIsNotWritable", $conffiletoshow);
            pFooter(1, $setuplang, 'jscheckparam');
            exit;
        }

        if ($action == "set") {
            print '<h3><img class="valignmiddle inline-block paddingright" src="../theme/common/octicons/build/svg/database.svg" width="20" alt="Database"> ' . $langs->trans("Database") . '</h3>';

            print '<table cellspacing="0" style="padding: 4px 4px 4px 0" border="0" width="100%">';
            $error = 0;

            $db = getDoliDBInstance($conf->db->type, $conf->db->host, $conf->db->user, $conf->db->pass, $conf->db->name, (int) $conf->db->port);

            if ($db->connected) {
                print "<tr><td>";
                print $langs->trans("ServerConnection") . " : " . $conf->db->host . '</td><td><img src="../theme/eldy/img/tick.png" alt="Ok"></td></tr>';
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
                print '<tr><td>' . $langs->trans("DatabaseVersion") . '</td>';
                print '<td>' . $version . '</td></tr>';
                //print '<td class="right">'.join('.',$versionarray).'</td></tr>';

                print '<tr><td>' . $langs->trans("DatabaseName") . '</td>';
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
                        if ($dolibarr_main_db_prefix != 'llx_') {
                            $buffer = preg_replace('/llx_/i', $dolibarr_main_db_prefix, $buffer);
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
                                print "<tr><td>" . $langs->trans("CreateTableAndPrimaryKey", $name);
                                print "<br>\n" . $langs->trans("Request") . ' ' . $requestnb . ' : ' . $buffer . ' <br>Executed query : ' . $db->lastquery;
                                print "\n</td>";
                                print '<td><span class="error">' . $langs->trans("ErrorSQL") . " " . $db->errno() . " " . $db->error() . '</span></td></tr>';
                                $error++;
                            }
                        }
                    } else {
                        print "<tr><td>" . $langs->trans("CreateTableAndPrimaryKey", $name);
                        print "</td>";
                        print '<td><span class="error">' . $langs->trans("Error") . ' Failed to open file ' . $dir . $file . '</span></td></tr>';
                        $error++;
                        dolibarr_install_syslog("step2: failed to open file " . $dir . $file, LOG_ERR);
                    }
                }

                if ($tablefound) {
                    if ($error == 0) {
                        print '<tr><td>';
                        print $langs->trans("TablesAndPrimaryKeysCreation") . '</td><td><img src="../theme/eldy/img/tick.png" alt="Ok"></td></tr>';
                        $ok = 1;
                    }
                } else {
                    print '<tr><td>' . $langs->trans("ErrorFailedToFindSomeFiles", $dir) . '</td><td><img src="../theme/eldy/img/error.png" alt="Error"></td></tr>';
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
                                if ($dolibarr_main_db_prefix != 'llx_') {
                                    $buffer = preg_replace('/llx_/i', $dolibarr_main_db_prefix, $buffer);
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
                                        print "<tr><td>" . $langs->trans("CreateOtherKeysForTable", $name);
                                        print "<br>\n" . $langs->trans("Request") . ' ' . $requestnb . ' : ' . $db->lastqueryerror();
                                        print "\n</td>";
                                        print '<td><span class="error">' . $langs->trans("ErrorSQL") . " " . $db->errno() . " " . $db->error() . '</span></td></tr>';
                                        $error++;
                                    }
                                }
                            }
                        }
                    } else {
                        print "<tr><td>" . $langs->trans("CreateOtherKeysForTable", $name);
                        print "</td>";
                        print '<td><span class="error">' . $langs->trans("Error") . " Failed to open file " . $dir . $file . "</span></td></tr>";
                        $error++;
                        dolibarr_install_syslog("step2: failed to open file " . $dir . $file, LOG_ERR);
                    }
                }

                if ($tablefound && $error == 0) {
                    print '<tr><td>';
                    print $langs->trans("OtherKeysCreation") . '</td><td><img src="../theme/eldy/img/tick.png" alt="Ok"></td></tr>';
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
                            if ($dolibarr_main_db_prefix != 'llx_') {
                                $buffer = preg_replace('/llx_/i', $dolibarr_main_db_prefix, $buffer);
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

                                    print "<tr><td>" . $langs->trans("FunctionsCreation");
                                    print "<br>\n" . $langs->trans("Request") . ' ' . $requestnb . ' : ' . $buffer;
                                    print "\n</td>";
                                    print '<td><span class="error">' . $langs->trans("ErrorSQL") . " " . $db->errno() . " " . $db->error() . '</span></td></tr>';
                                    $error++;
                                }
                            }
                        }
                    }

                    print "<tr><td>" . $langs->trans("FunctionsCreation") . "</td>";
                    if ($ok) {
                        print '<td><img src="../theme/eldy/img/tick.png" alt="Ok"></td></tr>';
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
                            if ($dolibarr_main_db_prefix != 'llx_') {
                                $buffer = preg_replace('/llx_/i', $dolibarr_main_db_prefix, $buffer);
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
                                    print '<span class="error">' . $langs->trans("ErrorSQL") . " : " . $db->lasterrno() . " - " . $db->lastqueryerror() . " - " . $db->lasterror() . "</span><br>";
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

                print "<tr><td>" . $langs->trans("ReferenceDataLoading") . "</td>";
                if ($ok) {
                    print '<td><img src="../theme/eldy/img/tick.png" alt="Ok"></td></tr>';
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

        $conf->file->instance_unique_id = (empty($dolibarr_main_instance_unique_id) ? (empty($dolibarr_main_cookie_cryptkey) ? '' : $dolibarr_main_cookie_cryptkey) : $dolibarr_main_instance_unique_id); // Unique id of instance

        $hash_unique_id = dol_hash('dolibarr' . $conf->file->instance_unique_id, 'sha256');   // Note: if the global salt changes, this hash changes too so ping may be counted twice. We don't mind. It is for statistics purpose only.

        $out = '<input type="checkbox" name="dolibarrpingno" id="dolibarrpingno"' . ((getDolGlobalString('MAIN_FIRST_PING_OK_ID') == 'disabled') ? '' : ' value="checked" checked="true"') . '> ';
        $out .= '<label for="dolibarrpingno">' . $langs->trans("MakeAnonymousPing") . '</label>';

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

        pFooter($ok ? 0 : 1, $setuplang);

        if (isset($db) && is_object($db)) {
            $db->close();
        }

// Return code if ran from command line
        if ($ret) {
            exit($ret);
        }
    }

    public function step4()
    {
        global $langs;

        $conffile = Config::getDolibarrConfigFilename();
        $conffiletoshow = $conffile;

        if (!isset($conf)) {
            $conf = Config::getConf();
        }

        if (!isset($langs)) {
            $langs = Config::getLangs($conf);
            $langs->setDefaultLang('auto');
        }

        $setuplang = GETPOST('selectlang', 'aZ09', 3) ? GETPOST('selectlang', 'aZ09', 3) : (empty($argv[1]) ? 'auto' : $argv[1]);
        $langs->setDefaultLang($setuplang);

        $langs->loadLangs(["admin", "install"]);

// Now we load forced value from install.forced.php file.
        $useforcedwizard = false;
        $forcedfile = "./install.forced.php";
        if ($conffile == "/etc/dolibarr/conf.php") {
            $forcedfile = "/etc/dolibarr/install.forced.php";
        }
        if (@file_exists($forcedfile)) {
            $useforcedwizard = true;
            include_once $forcedfile;
        }

        dolibarr_install_syslog("--- step4: entering step4.php page");

        $error = 0;
        $ok = 0;


        /*
         *  View
         */

        pHeader($langs->trans("AdminAccountCreation"), "step5");

// Test if we can run a first install process
        if (!is_writable($conffile)) {
            print $langs->trans("ConfFileIsNotWritable", $conffiletoshow);
            pFooter(1, $setuplang, 'jscheckparam');
            exit;
        }


        print '<h3><img class="valignmiddle inline-block paddingright" src="../theme/common/octicons/build/svg/key.svg" width="20" alt="Database"> ' . $langs->trans("DolibarrAdminLogin") . '</h3>';

        print $langs->trans("LastStepDesc") . '<br><br>';


        print '<table cellspacing="0" cellpadding="2">';

        $db = getDoliDBInstance($conf->db->type, $conf->db->host, $conf->db->user, $conf->db->pass, $conf->db->name, (int) $conf->db->port);

        if ($db->ok) {
            print '<tr><td><label for="login">' . $langs->trans("Login") . ' :</label></td><td>';
            print '<input id="login" name="login" type="text" value="' . (GETPOSTISSET("login") ? GETPOST("login", 'alpha') : (isset($force_install_dolibarrlogin) ? $force_install_dolibarrlogin : '')) . '"' . (@$force_install_noedit == 2 && $force_install_dolibarrlogin !== null ? ' disabled' : '') . ' autofocus></td></tr>';
            print '<tr><td><label for="pass">' . $langs->trans("Password") . ' :</label></td><td>';
            print '<input type="password" id="pass" name="pass" autocomplete="new-password" minlength="8"></td></tr>';
            print '<tr><td><label for="pass_verif">' . $langs->trans("PasswordRetype") . ' :</label></td><td>';
            print '<input type="password" id="pass_verif" name="pass_verif" autocomplete="new-password" minlength="8"></td></tr>';
            print '</table>';

            if (isset($_GET["error"]) && $_GET["error"] == 1) {
                print '<br>';
                print '<div class="error">' . $langs->trans("PasswordsMismatch") . '</div>';
                $error = 0; // We show button
            }

            if (isset($_GET["error"]) && $_GET["error"] == 2) {
                print '<br>';
                print '<div class="error">';
                print $langs->trans("PleaseTypePassword");
                print '</div>';
                $error = 0; // We show button
            }

            if (isset($_GET["error"]) && $_GET["error"] == 3) {
                print '<br>';
                print '<div class="error">' . $langs->trans("PleaseTypeALogin") . '</div>';
                $error = 0; // We show button
            }
        }

        $ret = 0;
        if ($error && isset($argv[1])) {
            $ret = 1;
        }
        dolibarr_install_syslog("Exit " . $ret);

        dolibarr_install_syslog("--- step4: end");

        pFooter($error, $setuplang);

        $db->close();

// Return code if ran from command line
        if ($ret) {
            exit($ret);
        }
    }

    public function step5()
    {
        global $langs, $db;

        $conffile = Config::getDolibarrConfigFilename();
        $conffiletoshow = $conffile;

        if (!isset($conf)) {
            $conf = Config::getConf();
        }

        if (!isset($langs)) {
            $langs = Config::getLangs($conf);
            $langs->setDefaultLang('auto');
        }

        $versionfrom = GETPOST("versionfrom", 'alpha', 3) ? GETPOST("versionfrom", 'alpha', 3) : (empty($argv[1]) ? '' : $argv[1]);
        $versionto = GETPOST("versionto", 'alpha', 3) ? GETPOST("versionto", 'alpha', 3) : (empty($argv[2]) ? '' : $argv[2]);
        $setuplang = GETPOST('selectlang', 'aZ09', 3) ? GETPOST('selectlang', 'aZ09', 3) : (empty($argv[3]) ? 'auto' : $argv[3]);
        $langs->setDefaultLang($setuplang);
        $action = GETPOST('action', 'alpha') ? GETPOST('action', 'alpha') : (empty($argv[4]) ? '' : $argv[4]);

// Define targetversion used to update MAIN_VERSION_LAST_INSTALL for first install
// or MAIN_VERSION_LAST_UPGRADE for upgrade.
        $targetversion = DOL_VERSION; // If it's latest upgrade
        if (!empty($action) && preg_match('/upgrade/i', $action)) {
            // If it's an old upgrade
            $tmp = explode('_', $action, 2);
            if ($tmp[0] == 'upgrade') {
                if (!empty($tmp[1])) {
                    $targetversion = $tmp[1]; // if $action = 'upgrade_6.0.0-beta', we use '6.0.0-beta'
                } else {
                    $targetversion = DOL_VERSION; // if $action = 'upgrade', we use DOL_VERSION
                }
            }
        }

        $langs->loadLangs(["admin", "install"]);

        $login = GETPOST('login', 'alpha') ? GETPOST('login', 'alpha') : (empty($argv[5]) ? '' : $argv[5]);
        $pass = GETPOST('pass', 'alpha') ? GETPOST('pass', 'alpha') : (empty($argv[6]) ? '' : $argv[6]);
        $pass_verif = GETPOST('pass_verif', 'alpha') ? GETPOST('pass_verif', 'alpha') : (empty($argv[7]) ? '' : $argv[7]);
        $force_install_lockinstall = (int) (!empty($force_install_lockinstall) ? $force_install_lockinstall : (GETPOST('installlock', 'aZ09') ? GETPOST('installlock', 'aZ09') : (empty($argv[8]) ? '' : $argv[8])));

        $success = 0;

        $useforcedwizard = false;
        $forcedfile = "./install.forced.php";
        if ($conffile == "/etc/dolibarr/conf.php") {
            $forcedfile = "/etc/dolibarr/install.forced.php";
        }
        if (@file_exists($forcedfile)) {
            $useforcedwizard = true;
            include_once $forcedfile;
            // If forced install is enabled, replace post values. These are empty because form fields are disabled.
            if ($force_install_noedit == 2) {
                if (!empty($force_install_dolibarrlogin)) {
                    $login = $force_install_dolibarrlogin;
                }
            }
        }

        dolibarr_install_syslog("--- step5: entering step5.php page " . $versionfrom . " " . $versionto);

        $error = 0;

        /*
         *  Actions
         */

// If install, check password and password_verification used to create admin account
        if ($action == "set") {
            if ($pass != $pass_verif) {
                header("Location: step4.php?error=1&selectlang=$setuplang" . (isset($login) ? '&login=' . $login : ''));
                exit;
            }

            if (dol_strlen(trim($pass)) == 0) {
                header("Location: step4.php?error=2&selectlang=$setuplang" . (isset($login) ? '&login=' . $login : ''));
                exit;
            }

            if (dol_strlen(trim($login)) == 0) {
                header("Location: step4.php?error=3&selectlang=$setuplang" . (isset($login) ? '&login=' . $login : ''));
                exit;
            }
        }


        /*
         *  View
         */

        $morehtml = '';

        pHeader($langs->trans("SetupEnd"), "step5", 'set', '', '', 'main-inside main-inside-borderbottom');
        print '<br>';

// Test if we can run a first install process
        if (empty($versionfrom) && empty($versionto) && !is_writable($conffile)) {
            print $langs->trans("ConfFileIsNotWritable", $conffiletoshow);
            pFooter(1, $setuplang, 'jscheckparam');
            exit;
        }

// Ensure $modulesdir is set and array
        if (!isset($modulesdir) || !is_array($modulesdir)) {
            $modulesdir = [];
        }

        if ($action == "set" || empty($action) || preg_match('/upgrade/i', $action)) {
            $error = 0;

            // If password is encoded, we decode it
            if ((!empty($dolibarr_main_db_pass) && preg_match('/crypted:/i', $dolibarr_main_db_pass)) || !empty($dolibarr_main_db_encrypted_pass)) {
                require_once $dolibarr_main_document_root . '/core/lib/security.lib.php';
                if (!empty($dolibarr_main_db_pass) && preg_match('/crypted:/i', $dolibarr_main_db_pass)) {
                    $dolibarr_main_db_pass = preg_replace('/crypted:/i', '', $dolibarr_main_db_pass);
                    $dolibarr_main_db_pass = dol_decode($dolibarr_main_db_pass);
                    $dolibarr_main_db_encrypted_pass = $dolibarr_main_db_pass; // We need to set this as it is used to know the password was initially encrypted
                } else {
                    $dolibarr_main_db_pass = dol_decode($dolibarr_main_db_encrypted_pass);
                }
            }

            $db = getDoliDBInstance($conf->db->type, $conf->db->host, $conf->db->user, $conf->db->pass, $conf->db->name, (int) $conf->db->port);

            // Create the global $hookmanager object
            include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
            $hookmanager = new HookManager($db);

            $ok = 0;

            // If first install
            if ($action == "set") {
                // Active module user
                $modName = 'modUser';
                $file = $modName . ".class.php";
                dolibarr_install_syslog('step5: load module user ' . DOL_DOCUMENT_ROOT . "/core/modules/" . $file, LOG_INFO);
                include_once DOL_DOCUMENT_ROOT . "/core/modules/" . $file;
                $objMod = new $modName($db);
                $result = $objMod->init();
                if (!$result) {
                    print "ERROR: failed to init module file = " . $file;
                }

                if ($db->connected) {
                    $conf->setValues($db);
                    // Reset forced setup after the setValues
                    if (defined('SYSLOG_FILE')) {
                        $conf->global->SYSLOG_FILE = constant('SYSLOG_FILE');
                    }
                    $conf->global->MAIN_ENABLE_LOG_TO_HTML = 1;

                    // Create admin user

                    // Set default encryption to yes, generate a salt and set default encryption algorithm (but only if there is no user yet into database)
                    $sql = "SELECT u.rowid, u.pass, u.pass_crypted";
                    $sql .= " FROM " . MAIN_DB_PREFIX . "user as u";
                    $resql = $db->query($sql);
                    if ($resql) {
                        $numrows = $db->num_rows($resql);
                        if ($numrows == 0) {
                            // Define default setup for password encryption
                            dolibarr_set_const($db, "DATABASE_PWD_ENCRYPTED", "1", 'chaine', 0, '', $conf->entity);
                            dolibarr_set_const($db, "MAIN_SECURITY_SALT", dol_print_date(dol_now(), 'dayhourlog'), 'chaine', 0, '', 0); // All entities
                            if (function_exists('password_hash')) {
                                dolibarr_set_const($db, "MAIN_SECURITY_HASH_ALGO", 'password_hash', 'chaine', 0, '', 0); // All entities
                            } else {
                                dolibarr_set_const($db, "MAIN_SECURITY_HASH_ALGO", 'sha1md5', 'chaine', 0, '', 0); // All entities
                            }
                        }

                        dolibarr_install_syslog('step5: DATABASE_PWD_ENCRYPTED = ' . getDolGlobalString('DATABASE_PWD_ENCRYPTED') . ' MAIN_SECURITY_HASH_ALGO = ' . getDolGlobalString('MAIN_SECURITY_HASH_ALGO'), LOG_INFO);
                    }

                    // Create user used to create the admin user
                    $createuser = new User($db);
                    $createuser->id = 0;
                    $createuser->admin = 1;

                    // Set admin user
                    $newuser = new User($db);
                    $newuser->lastname = 'SuperAdmin';
                    $newuser->firstname = '';
                    $newuser->login = $login;
                    $newuser->pass = $pass;
                    $newuser->admin = 1;
                    $newuser->entity = 0;

                    $conf->global->USER_MAIL_REQUIRED = 0;          // Force global option to be sure to create a new user with no email
                    $conf->global->USER_PASSWORD_GENERATED = '';    // To not use any rule for password validation

                    $result = $newuser->create($createuser, 1);
                    if ($result > 0) {
                        print $langs->trans("AdminLoginCreatedSuccessfuly", $login) . "<br>";
                        $success = 1;
                    } else {
                        if ($result == -6) {    //login or email already exists
                            dolibarr_install_syslog('step5: AdminLoginAlreadyExists', LOG_WARNING);
                            print '<br><div class="warning">' . $newuser->error . "</div><br>";
                            $success = 1;
                        } else {
                            dolibarr_install_syslog('step5: FailedToCreateAdminLogin ' . $newuser->error, LOG_ERR);
                            setEventMessages($langs->trans("FailedToCreateAdminLogin") . ' ' . $newuser->error, null, 'errors');
                            //header("Location: step4.php?error=3&selectlang=$setuplang".(isset($login) ? '&login='.$login : ''));
                            print '<br><div class="error">' . $langs->trans("FailedToCreateAdminLogin") . ': ' . $newuser->error . '</div><br><br>';
                            print $langs->trans("ErrorGoBackAndCorrectParameters") . '<br><br>';
                        }
                    }

                    if ($success) {
                        // Insert MAIN_VERSION_FIRST_INSTALL in a dedicated transaction. So if it fails (when first install was already done), we can do other following requests.
                        $db->begin();
                        dolibarr_install_syslog('step5: set MAIN_VERSION_FIRST_INSTALL const to ' . $targetversion, LOG_DEBUG);
                        $resql = $db->query("INSERT INTO " . MAIN_DB_PREFIX . "const(name, value, type, visible, note, entity) values(" . $db->encrypt('MAIN_VERSION_FIRST_INSTALL') . ", " . $db->encrypt($targetversion) . ", 'chaine', 0, 'Dolibarr version when first install', 0)");
                        if ($resql) {
                            $conf->global->MAIN_VERSION_FIRST_INSTALL = $targetversion;
                            $db->commit();
                        } else {
                            //if (! $resql) dol_print_error($db,'Error in setup program');      // We ignore errors. Key may already exists
                            $db->commit();
                        }

                        $db->begin();

                        dolibarr_install_syslog('step5: set MAIN_VERSION_LAST_INSTALL const to ' . $targetversion, LOG_DEBUG);
                        $resql = $db->query("DELETE FROM " . MAIN_DB_PREFIX . "const WHERE " . $db->decrypt('name') . " = 'MAIN_VERSION_LAST_INSTALL'");
                        if (!$resql) {
                            dol_print_error($db, 'Error in setup program');
                        }
                        $resql = $db->query("INSERT INTO " . MAIN_DB_PREFIX . "const(name,value,type,visible,note,entity) values(" . $db->encrypt('MAIN_VERSION_LAST_INSTALL') . ", " . $db->encrypt($targetversion) . ", 'chaine', 0, 'Dolibarr version when last install', 0)");
                        if (!$resql) {
                            dol_print_error($db, 'Error in setup program');
                        }
                        $conf->global->MAIN_VERSION_LAST_INSTALL = $targetversion;

                        if ($useforcedwizard) {
                            dolibarr_install_syslog('step5: set MAIN_REMOVE_INSTALL_WARNING const to 1', LOG_DEBUG);
                            $resql = $db->query("DELETE FROM " . MAIN_DB_PREFIX . "const WHERE " . $db->decrypt('name') . " = 'MAIN_REMOVE_INSTALL_WARNING'");
                            if (!$resql) {
                                dol_print_error($db, 'Error in setup program');
                            }
                            // The install.lock file is created few lines later if version is last one or if option MAIN_ALWAYS_CREATE_LOCK_AFTER_LAST_UPGRADE is on
                            /* No need to enable this
                            $resql = $db->query("INSERT INTO ".MAIN_DB_PREFIX."const(name,value,type,visible,note,entity) values(".$db->encrypt('MAIN_REMOVE_INSTALL_WARNING').", ".$db->encrypt(1).", 'chaine', 1, 'Disable install warnings', 0)");
                            if (!$resql) {
                                dol_print_error($db, 'Error in setup program');
                            }
                            $conf->global->MAIN_REMOVE_INSTALL_WARNING = 1;
                            */
                        }

                        // List of modules to enable
                        $tmparray = [];

                        // If we ask to force some modules to be enabled
                        if (!empty($force_install_module)) {
                            if (!defined('DOL_DOCUMENT_ROOT') && !empty($dolibarr_main_document_root)) {
                                define('DOL_DOCUMENT_ROOT', $dolibarr_main_document_root);
                            }

                            $tmparray = explode(',', $force_install_module);
                        }

                        $modNameLoaded = [];

                        // Search modules dirs
                        $modulesdir[] = BASE_PATH . '/core/modules/';

                        foreach ($modulesdir as $dir) {
                            // Load modules attributes in arrays (name, numero, orders) from dir directory
                            //print $dir."\n<br>";
                            dol_syslog("Scan directory " . $dir . " for module descriptor files (modXXX.class.php)");
                            $handle = @opendir($dir);
                            if (is_resource($handle)) {
                                while (($file = readdir($handle)) !== false) {
                                    if (is_readable($dir . $file) && substr($file, 0, 3) == 'mod' && substr($file, dol_strlen($file) - 10) == '.class.php') {
                                        $modName = substr($file, 0, dol_strlen($file) - 10);
                                        if ($modName) {
                                            if (!empty($modNameLoaded[$modName])) {   // In cache of already loaded modules ?
                                                $mesg = "Error: Module " . $modName . " was found twice: Into " . $modNameLoaded[$modName] . " and " . $dir . ". You probably have an old file on your disk.<br>";
                                                setEventMessages($mesg, null, 'warnings');
                                                dol_syslog($mesg, LOG_ERR);
                                                continue;
                                            }

                                            try {
                                                $res = include_once $dir . $file; // A class already exists in a different file will send a non catchable fatal error.
                                                if (class_exists($modName)) {
                                                    $objMod = new $modName($db);
                                                    $modNameLoaded[$modName] = $dir;
                                                    if (!empty($objMod->enabled_bydefault) && !in_array($file, $tmparray)) {
                                                        $tmparray[] = $file;
                                                    }
                                                }
                                            } catch (Exception $e) {
                                                dol_syslog("Failed to load " . $dir . $file . " " . $e->getMessage(), LOG_ERR);
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        // Loop on each modules to activate it
                        if (!empty($tmparray)) {
                            foreach ($tmparray as $modtoactivate) {
                                $modtoactivatenew = preg_replace('/\.class\.php$/i', '', $modtoactivate);
                                //print $langs->trans("ActivateModule", $modtoactivatenew).'<br>';

                                $file = $modtoactivatenew . '.class.php';
                                dolibarr_install_syslog('step5: activate module file=' . $file);
                                $res = dol_include_once("/core/modules/" . $file);

                                $res = activateModule($modtoactivatenew, 1);
                                if (!empty($res['errors'])) {
                                    print 'ERROR: failed to activateModule() file=' . $file;
                                }
                            }
                            //print '<br>';
                        }

                        // Now delete the flag that say installation is not complete
                        dolibarr_install_syslog('step5: remove MAIN_NOT_INSTALLED const');
                        $resql = $db->query("DELETE FROM " . MAIN_DB_PREFIX . "const WHERE " . $db->decrypt('name') . " = 'MAIN_NOT_INSTALLED'");
                        if (!$resql) {
                            dol_print_error($db, 'Error in setup program');
                        }

                        // May fail if parameter already defined
                        dolibarr_install_syslog('step5: set the default language');
                        $resql = $db->query("INSERT INTO " . MAIN_DB_PREFIX . "const(name,value,type,visible,note,entity) VALUES (" . $db->encrypt('MAIN_LANG_DEFAULT') . ", " . $db->encrypt($setuplang) . ", 'chaine', 0, 'Default language', 1)");
                        //if (! $resql) dol_print_error($db,'Error in setup program');

                        $db->commit();
                    }
                } else {
                    print $langs->trans("ErrorFailedToConnect") . "<br>";
                }
            } elseif (empty($action) || preg_match('/upgrade/i', $action)) {
                // If upgrade
                if ($db->connected) {
                    $conf->setValues($db);
                    // Reset forced setup after the setValues
                    if (defined('SYSLOG_FILE')) {
                        $conf->global->SYSLOG_FILE = constant('SYSLOG_FILE');
                    }
                    $conf->global->MAIN_ENABLE_LOG_TO_HTML = 1;

                    // Define if we need to update the MAIN_VERSION_LAST_UPGRADE value in database
                    $tagdatabase = false;
                    if (!getDolGlobalString('MAIN_VERSION_LAST_UPGRADE')) {
                        $tagdatabase = true; // We don't know what it was before, so now we consider we at the chosen version.
                    } else {
                        $mainversionlastupgradearray = preg_split('/[.-]/', $conf->global->MAIN_VERSION_LAST_UPGRADE);
                        $targetversionarray = preg_split('/[.-]/', $targetversion);
                        if (versioncompare($targetversionarray, $mainversionlastupgradearray) > 0) {
                            $tagdatabase = true;
                        }
                    }

                    if ($tagdatabase) {
                        dolibarr_install_syslog('step5: set MAIN_VERSION_LAST_UPGRADE const to value ' . $targetversion);
                        $resql = $db->query("DELETE FROM " . MAIN_DB_PREFIX . "const WHERE " . $db->decrypt('name') . " = 'MAIN_VERSION_LAST_UPGRADE'");
                        if (!$resql) {
                            dol_print_error($db, 'Error in setup program');
                        }
                        $resql = $db->query("INSERT INTO " . MAIN_DB_PREFIX . "const(name, value, type, visible, note, entity) VALUES (" . $db->encrypt('MAIN_VERSION_LAST_UPGRADE') . ", " . $db->encrypt($targetversion) . ", 'chaine', 0, 'Dolibarr version for last upgrade', 0)");
                        if (!$resql) {
                            dol_print_error($db, 'Error in setup program');
                        }
                        $conf->global->MAIN_VERSION_LAST_UPGRADE = $targetversion;
                    } else {
                        dolibarr_install_syslog('step5: we run an upgrade to version ' . $targetversion . ' but database was already upgraded to ' . getDolGlobalString('MAIN_VERSION_LAST_UPGRADE') . '. We keep MAIN_VERSION_LAST_UPGRADE as it is.');

                        // Force the delete of the flag that say installation is not complete
                        dolibarr_install_syslog('step5: remove MAIN_NOT_INSTALLED const after upgrade process (should not exists but this is a security)');
                        $resql = $db->query("DELETE FROM " . MAIN_DB_PREFIX . "const WHERE " . $db->decrypt('name') . " = 'MAIN_NOT_INSTALLED'");
                        if (!$resql) {
                            dol_print_error($db, 'Error in setup program');
                        }
                    }
                } else {
                    print $langs->trans("ErrorFailedToConnect") . "<br>";
                }
            } else {
                dol_print_error(null, 'step5.php: unknown choice of action');
            }

            $db->close();
        }


// Create lock file

// If first install
        if ($action == "set") {
            if ($success) {
                if (!getDolGlobalString('MAIN_VERSION_LAST_UPGRADE') || ($conf->global->MAIN_VERSION_LAST_UPGRADE == DOL_VERSION)) {
                    // Install is finished (database is on same version than files)
                    print '<br>' . $langs->trans("SystemIsInstalled") . "<br>";

                    // Create install.lock file
                    // No need for the moment to create it automatically, creation by web assistant means permissions are given
                    // to the web user, it is better to show a warning to say to create it manually with correct user/permission (not erasable by a web process)
                    $createlock = 0;
                    if (!empty($force_install_lockinstall) || getDolGlobalString('MAIN_ALWAYS_CREATE_LOCK_AFTER_LAST_UPGRADE')) {
                        // Install is finished, we create the "install.lock" file, so install won't be possible anymore.
                        // TODO Upgrade will be still be possible if a file "upgrade.unlock" is present
                        $lockfile = DOL_DATA_ROOT . '/install.lock';
                        $fp = @fopen($lockfile, "w");
                        if ($fp) {
                            if (empty($force_install_lockinstall) || $force_install_lockinstall == 1) {
                                $force_install_lockinstall = '444'; // For backward compatibility
                            }
                            fwrite($fp, "This is a lock file to prevent use of install or upgrade pages (set with permission " . $force_install_lockinstall . ")");
                            fclose($fp);
                            dolChmod($lockfile, $force_install_lockinstall);

                            $createlock = 1;
                        }
                    }
                    if (empty($createlock)) {
                        print '<div class="warning">' . $langs->trans("WarningRemoveInstallDir") . "</div>";
                    }

                    print "<br>";

                    print $langs->trans("YouNeedToPersonalizeSetup") . "<br><br><br>";

                    print '<div class="center">&gt; <a href="../admin/index.php?mainmenu=home&leftmenu=setup' . (isset($login) ? '&username=' . urlencode($login) : '') . '">';
                    print '<span class="fas fa-external-link-alt"></span> ' . $langs->trans("GoToSetupArea");
                    print '</a></div><br>';
                } else {
                    // If here MAIN_VERSION_LAST_UPGRADE is not empty
                    print $langs->trans("VersionLastUpgrade") . ': <b><span class="ok">' . getDolGlobalString('MAIN_VERSION_LAST_UPGRADE') . '</span></b><br>';
                    print $langs->trans("VersionProgram") . ': <b><span class="ok">' . DOL_VERSION . '</span></b><br>';
                    print $langs->trans("MigrationNotFinished") . '<br>';
                    print "<br>";

                    print '<div class="center"><a href="' . $dolibarr_main_url_root . '/install/index.php">';
                    print '<span class="fas fa-link-alt"></span> ' . $langs->trans("GoToUpgradePage");
                    print '</a></div>';
                }
            }
        } elseif (empty($action) || preg_match('/upgrade/i', $action)) {
            // If upgrade
            if (!getDolGlobalString('MAIN_VERSION_LAST_UPGRADE') || ($conf->global->MAIN_VERSION_LAST_UPGRADE == DOL_VERSION)) {
                // Upgrade is finished (database is on the same version than files)
                print '<img class="valignmiddle inline-block paddingright" src="../theme/common/octicons/build/svg/checklist.svg" width="20" alt="Configuration">';
                print ' <span class="valignmiddle">' . $langs->trans("SystemIsUpgraded") . "</span><br>";

                // Create install.lock file if it does not exists.
                // Note: it should always exists. A better solution to allow upgrade will be to add an upgrade.unlock file
                $createlock = 0;
                if (!empty($force_install_lockinstall) || getDolGlobalString('MAIN_ALWAYS_CREATE_LOCK_AFTER_LAST_UPGRADE')) {
                    // Upgrade is finished, we modify the lock file
                    $lockfile = DOL_DATA_ROOT . '/install.lock';
                    $fp = @fopen($lockfile, "w");
                    if ($fp) {
                        if (empty($force_install_lockinstall) || $force_install_lockinstall == 1) {
                            $force_install_lockinstall = '444'; // For backward compatibility
                        }
                        fwrite($fp, "This is a lock file to prevent use of install or upgrade pages (set with permission " . $force_install_lockinstall . ")");
                        fclose($fp);
                        dolChmod($lockfile, $force_install_lockinstall);

                        $createlock = 1;
                    }
                }
                if (empty($createlock)) {
                    print '<br><div class="warning">' . $langs->trans("WarningRemoveInstallDir") . "</div>";
                }

                // Delete the upgrade.unlock file it it exists
                $unlockupgradefile = DOL_DATA_ROOT . '/upgrade.unlock';
                dol_delete_file($unlockupgradefile, 0, 0, 0, null, false, 0);

                print "<br>";

                $morehtml = '<br><div class="center"><a href="../index.php?mainmenu=home' . (isset($login) ? '&username=' . urlencode($login) : '') . '">';
                $morehtml .= '<span class="fas fa-link-alt"></span> ' . $langs->trans("GoToDolibarr") . '...';
                $morehtml .= '</a></div><br>';
            } else {
                // If here MAIN_VERSION_LAST_UPGRADE is not empty
                print $langs->trans("VersionLastUpgrade") . ': <b><span class="ok">' . getDolGlobalString('MAIN_VERSION_LAST_UPGRADE') . '</span></b><br>';
                print $langs->trans("VersionProgram") . ': <b><span class="ok">' . DOL_VERSION . '</span></b>';

                print "<br>";

                $morehtml = '<br><div class="center"><a href="../install/index.php">';
                $morehtml .= '<span class="fas fa-link-alt"></span> ' . $langs->trans("GoToUpgradePage");
                $morehtml .= '</a></div>';
            }
        } else {
            dol_print_error(null, 'step5.php: unknown choice of action=' . $action . ' in create lock file seaction');
        }

// Clear cache files
        clearstatcache();

        $ret = 0;
        if ($error && isset($argv[1])) {
            $ret = 1;
        }
        dolibarr_install_syslog("Exit " . $ret);

        dolibarr_install_syslog("--- step5: Dolibarr setup finished");

        pFooter(1, $setuplang, '', 0, $morehtml);

// Return code if ran from command line
        if ($ret) {
            exit($ret);
        }
    }
}

