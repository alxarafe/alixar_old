<?php

namespace Alixar\Install;

use Alxarafe\Base\BasicController;
use Alxarafe\Base\Globals;
use Alxarafe\Lib\Admin;
use Alxarafe\Lib\Functions;
use Alxarafe\LibClass\FormAdmin;

class Install extends BasicController
{
    public function __construct()
    {
        parent::__construct();

        $this->lang->loadLangs(['main', 'admin', 'install', 'errors']);
    }

    private function syslog($message, $level = LOG_DEBUG)
    {
        if (!defined('LOG_DEBUG')) {
            define('LOG_DEBUG', 6);
        }
        Functions::dol_syslog($message, $level);
    }

    /**
     * Log function for install pages
     *
     * @param string $message Message
     * @param int    $level   Level of log
     *
     * @return  void
     */
    function dolibarr_install_syslog($message, $level = LOG_DEBUG)
    {
        if (!defined('LOG_DEBUG')) {
            define('LOG_DEBUG', 6);
        }
        Functions::dol_syslog($message, $level);
    }

    public function noAction(): bool
    {
        $this->template = 'install/install';

        $form = new FormAdmin(null);
        $this->htmlComboLanguages = $form->select_language('auto', 'selectlang', 1, 0, 0, 1);

        return true;
    }

    private function checkBrowser()
    {
        $useragent = $_SERVER['HTTP_USER_AGENT'];
        if (empty($useragent)) {
            return false;
        }

        $tmp = Functions::getBrowserInfo($_SERVER["HTTP_USER_AGENT"]);
        $browsername = $tmp['browsername'];
        $browserversion = $tmp['browserversion'];
        if ($browsername == 'ie' && $browserversion < 7) {
            $result = [];
            $result['ok'] = true;
            $result['icon'] = 'warning';
            $result['text'] = $this->lang->trans("WarningBrowserTooOld");
            return $result;
        }

        return false;
    }

    private function checkMinPhp()
    {
        $arrayphpminversionerror = [7, 0, 0];
        $arrayphpminversionwarning = [7, 1, 0];

        $result = [];
        $result['ok'] = true;

        if (Admin::versioncompare(Admin::versionphparray(), $arrayphpminversionerror) < 0) {        // Minimum to use (error if lower)
            $result['ok'] = false;
            $result['icon'] = 'error';
            $result['text'] = $this->lang->trans("ErrorPHPVersionTooLow", Admin::versiontostring($arrayphpminversionerror));
        } elseif (Admin::versioncompare(Admin::versionphparray(), $arrayphpminversionwarning) < 0) {    // Minimum supported (warning if lower)
            $result['icon'] = 'warning';
            $result['text'] = $this->lang->trans("ErrorPHPVersionTooLow", Admin::versiontostring($arrayphpminversionwarning));
        } else {
            $result['icon'] = 'ok';
            $result['text'] = $this->lang->trans("PHPVersion") . " " . Admin::versiontostring(Admin::versionphparray());
        }

        if (empty($force_install_nophpinfo)) {
            $result['text'] .= ' (<a href="phpinfo.php" target="_blank" rel="noopener noreferrer">' . $this->lang->trans("MoreInformation") . '</a>)';
        }

        return $result;
    }

    private function checkMaxPhp()
    {
        $arrayphpmaxversionwarning = [8, 2, 0];
        if (Admin::versioncompare(Admin::versionphparray(), $arrayphpmaxversionwarning) > 0 && Admin::versioncompare(Admin::versionphparray(), $arrayphpmaxversionwarning) < 3) {        // Maximum to use (warning if higher)
            $result = [];
            $result['ok'] = false;
            $result['icon'] = 'error';
            $result['text'] = $this->lang->trans("ErrorPHPVersionTooHigh", Admin::versiontostring($arrayphpmaxversionwarning));
            return $result;
        }

        return false;
    }

    private function checkGetPostSupport()
    {
        $result = [];
        $result['ok'] = true;
        if (!isset($_GET["testget"]) && !isset($_POST["testpost"])) {   // We must keep $_GET and $_POST here
            $result['icon'] = 'warning';
            $result['text'] = $this->lang->trans("PHPSupportPOSTGETKo") . ' (<a href="' . Functions::dol_escape_htmltag($_SERVER["PHP_SELF"]) . '?testget=ok">' . $this->lang->trans("Recheck") . '</a>)';
        } else {
            $result['icon'] = 'ok';
            $result['text'] = $this->lang->trans("PHPSupportPOSTGETOk");
        }
        return $result;
    }

    private function checkSessionId()
    {
        $result = [];
        $result['ok'] = function_exists("session_id");
        if ($result['ok']) {
            $result['icon'] = 'ok';
            $result['text'] = $this->lang->trans("PHPSupportSessions");
        } else {
            $result['icon'] = 'error';
            $result['text'] = $this->lang->trans("ErrorPHPDoesNotSupportSessions");
        }
        return $result;
    }

    private function checkMbStringExtension()
    {
        $result = [];
        $result['ok'] = extension_loaded("mbstring");
        if (!$result['ok']) {
            $result['icon'] = 'error';
            $result['text'] = $this->lang->trans("ErrorPHPDoesNotSupport", "MBString");
        } else {
            $result['icon'] = 'ok';
            $result['text'] = $this->lang->trans("PHPSupport", "MBString");
        }
        return $result;
    }

    private function checkJsonExtension()
    {
        $result = [];
        $result['ok'] = extension_loaded("json");
        if (!$result['ok']) {
            $result['icon'] = 'error';
            $result['text'] = $this->lang->trans("ErrorPHPDoesNotSupport", "JSON");
        } else {
            $result['icon'] = 'ok';
            $result['text'] = $this->lang->trans("PHPSupport", "JSON");
        }
        return $result;
    }

    private function checkGdExtension()
    {
        $result = [];
        $result['ok'] = true;
        if (!function_exists("imagecreate")) {
            $result['icon'] = 'warning';
            $result['text'] = $this->lang->trans("ErrorPHPDoesNotSupport", "GD");
        } else {
            $result['icon'] = 'ok';
            $result['text'] = $this->lang->trans("PHPSupport", "GD");
        }
        return $result;
    }

    private function checkCurlExtension()
    {
        $result = [];
        $result['ok'] = true;
        if (!function_exists("curl_init")) {
            $result['icon'] = 'warning';
            $result['text'] = $this->lang->trans("ErrorPHPDoesNotSupport", "Curl");
        } else {
            $result['icon'] = 'ok';
            $result['text'] = $this->lang->trans("PHPSupport", "Curl");
        }
        return $result;
    }

    private function checkCalendarExtension()
    {
        $result = [];
        $result['ok'] = function_exists("easter_date");
        if (!$result['ok']) {
            $result['icon'] = 'error';
            $result['text'] = $this->lang->trans("ErrorPHPDoesNotSupport", "Calendar");
        } else {
            $result['icon'] = 'ok';
            $result['text'] = $this->lang->trans("PHPSupport", "Calendar");
        }
        return $result;
    }

    private function checkXmlExtension()
    {
        $result = [];
        $result['ok'] = function_exists("simplexml_load_string");
        if (!$result['ok']) {
            $result['icon'] = 'error';
            $result['text'] = $this->lang->trans("ErrorPHPDoesNotSupport", "Xml");
        } else {
            $result['icon'] = 'ok';
            $result['text'] = $this->lang->trans("PHPSupport", "Xml");
        }
        return $result;
    }

    private function checkUtfExtension()
    {
        $result = [];
        $result['ok'] = function_exists("utf8_encode");
        if (!$result['ok']) {
            $result['icon'] = 'error';
            $result['text'] = $this->lang->trans("ErrorPHPDoesNotSupport", "UTF8");
        } else {
            $result['icon'] = 'ok';
            $result['text'] = $this->lang->trans("PHPSupport", "UTF8");
        }
        return $result;
    }

    private function checkIntlExtension()
    {
        if (empty($_SERVER["SERVER_ADMIN"]) || $_SERVER["SERVER_ADMIN"] != 'doliwamp@localhost') {
            $result = [];
            $result['ok'] = function_exists("locale_get_primary_language") && function_exists("locale_get_region");
            if (!$result['ok']) {
                $result['icon'] = 'error';
                $result['text'] = $this->lang->trans("ErrorPHPDoesNotSupport", "Intl");
            } else {
                $result['icon'] = 'ok';
                $result['text'] = $this->lang->trans("PHPSupport", "Intl");
            }
            return $result;
        }

        return false;
    }

    private function checkImapExtension()
    {
        if (PHP_VERSION_ID > 80300) {
            return false;
        }

        $result = [];
        $result['ok'] = function_exists("imap_open");
        if (!$result['ok']) {
            $result['icon'] = 'error';
            $result['text'] = $this->lang->trans("ErrorPHPDoesNotSupport", "IMAP");
        } else {
            $result['icon'] = 'ok';
            $result['text'] = $this->lang->trans("PHPSupport", "IMAP");
        }
        return $result;
    }

    private function checkZipExtension()
    {
        $result = [];
        $result['ok'] = class_exists('ZipArchive');
        if (!$result['ok']) {
            $result['icon'] = 'error';
            $result['text'] = $this->lang->trans("ErrorPHPDoesNotSupport", "ZIP");
        } else {
            $result['icon'] = 'ok';
            $result['text'] = $this->lang->trans("PHPSupport", "ZIP");
        }
        return $result;
    }

    private function checkMemory()
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
            $result['text'] = $this->lang->trans("PHPMemoryOK", $memmaxorig, $memrequiredorig);
        } else {
            $result['icon'] = 'warning';
            $result['text'] = $this->lang->trans("PHPMemoryTooLow", $memmaxorig, $memrequiredorig);
        }
        return $result;
    }

    private function checkConfFile()
    {
        $result = false;
        $conffile = Globals::getConfFilename();

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
                $result['text'] = $this->lang->trans('ConfFileDoesNotExistsAndCouldNotBeCreated', 'conf.php');
            }
        }
        return $result;
    }

    public function checkIfWritable()
    {
        $conffile = Globals::getConfFilename();

        if (is_dir($conffile)) {
            $result['ok'] = false;
            $result['icon'] = 'error';
            $result['text'] = $this->lang->trans('ConfFileMustBeAFileNotADir', $conffile);
            return $result;
        }

        if (!is_writable($conffile)) {
            $result['ok'] = true;
            $result['icon'] = 'warning';
            $result['text'] = $this->lang->trans('ConfFileIsNotWritable', $conffile);
            return $result;
        }

        return false;
    }

    public function next()
    {

        $configFilename = Globals::getConfFilename();
        $conf = Globals::getConf();
        if (!empty($conf)) {
            $config = $conf::getConfig();
        }

        $ok = false;
        if (!empty($config->main_db_type) && !empty($config->main_document_root)) {
            $this->errorBadMainDocumentRoot = '';
            if ($config->main_document_root !== BASE_PATH) {
                $this->errorBadMainDocumentRoot = "A $configFilename file exists with a dolibarr_main_document_root to $config->main_document_root that seems wrong. Try to fix or remove the $configFilename file.";
                dol_syslog($this->errorBadMainDocumentRoot, LOG_WARNING);
            } else {
                // If password is encoded, we decode it
                // TODO: Pending
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

        // If database access is available, we set more variables
        // TODO: Pending
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

        $this->printVersion =Functions::getDolGlobalString('MAIN_VERSION_LAST_UPGRADE') || Functions::getDolGlobalString('MAIN_VERSION_LAST_INSTALL');

        $foundrecommandedchoice = 0;

        $available_choices = [];
        $notavailable_choices = [];

        if (empty($dolibarr_main_db_host)) {    // This means install process was not run
            $foundrecommandedchoice = 1; // To show only once
        }

        // Show line of first install choice
        $choice = '<tr class="trlineforchoice' . ($foundrecommandedchoice ? ' choiceselected' : '') . '">' . "\n";
        $choice .= '<td class="nowrap center"><b>' . $this->lang->trans("FreshInstall") . '</b>';
        $choice .= '</td>';
        $choice .= '<td class="listofchoicesdesc">';
        $choice .= $this->lang->trans("FreshInstallDesc");
        if (empty($dolibarr_main_db_host)) {    // This means install process was not run
            $choice .= '<br>';
            //print $this->lang->trans("InstallChoiceRecommanded",DOL_VERSION,$conf->global->MAIN_VERSION_LAST_UPGRADE);
            $choice .= '<div class="center"><div class="ok suggestedchoice">' . $this->lang->trans("InstallChoiceSuggested") . '</div></div>';
            // <img src="../theme/eldy/img/tick.png" alt="Ok" class="valignmiddle"> ';
        }

        $allowinstall = isset($allowinstall) && $allowinstall;

        $choice .= '</td>';
        $choice .= '<td class="center">';
        if ($allowinstall) {
            $choice .= '<a class="button" href="fileconf.php?selectlang=' . $setuplang . '">' . $this->lang->trans("Start") . '</a>';
        } else {
            $choice .= ($foundrecommandedchoice ? '<span class="warning">' : '') . $this->lang->trans("InstallNotAllowed") . ($foundrecommandedchoice ? '</span>' : '');
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
        if (Functions::getDolGlobalInt("MAIN_NOT_INSTALLED")) {
            $allowupgrade = false;
        }
        if (Functions::GETPOST('allowupgrade')) {
            $allowupgrade = true;
        }

        $dir = BASE_PATH . "/Install/mysql/migration/";   // We use mysql migration scripts whatever is database driver
        $this->dolibarr_install_syslog("Scan sql files for migration files in " . $dir);

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
            $migrationscript = Functions::dol_sort_array($migrationscript, 'from', 'asc', 1);
        } else {
            print '<div class="error">' . $this->lang->trans("ErrorCanNotReadDir", $dir) . '</div>';
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
            if (Admin::versioncompare($dolibarrversiontoarray, $versionarray) < -2) {  // From x.y.z -> x.y.z+1
                $newversionfrombis = ' ' . $this->lang->trans("or") . ' ' . $versionto;
            }

            if ($ok) {
                if (count($dolibarrlastupgradeversionarray) >= 2) { // If database access is available and last upgrade version is known
                    // Now we check if this is the first qualified choice
                    if (
                        $allowupgrade && empty($foundrecommandedchoice) &&
                        (Admin::versioncompare($dolibarrversiontoarray, $dolibarrlastupgradeversionarray) > 0 || Admin::versioncompare($dolibarrversiontoarray, $versionarray) < -2)
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
            $choice .= '<td class="nowrap center"><b>' . $this->lang->trans("Upgrade") . '<br>' . $newversionfrom . $newversionfrombis . ' -> ' . $newversionto . '</b></td>';
            $choice .= '<td class="listofchoicesdesc">';
            $choice .= $this->lang->trans("UpgradeDesc");

            if ($recommended_choice) {
                $choice .= '<br>';
                //print $this->lang->trans("InstallChoiceRecommanded",DOL_VERSION,$conf->global->MAIN_VERSION_LAST_UPGRADE);
                $choice .= '<div class="center">';
                $choice .= '<div class="ok suggestedchoice">' . $this->lang->trans("InstallChoiceSuggested") . '</div>';
                if ($count < count($migarray)) {    // There are other choices after
                    print $this->lang->trans("MigrateIsDoneStepByStep", DOL_VERSION);
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
                    $choice .= '<span class="opacitymedium">' . $this->lang->trans("NotYetAvailable") . '</span>';
                } else {
                    $choice .= '<a class="button runupgrade" href="upgrade.php?action=upgrade' . ($count < count($migrationscript) ? '_' . $versionto : '') . '&amp;selectlang=' . $setuplang . '&amp;versionfrom=' . $versionfrom . '&amp;versionto=' . $versionto . '">' . $this->lang->trans("Start") . '</a>';
                }
            } else {
                $choice .= $this->lang->trans("NotAvailable");
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
            print '> ' . $this->lang->trans('ShowNotAvailableOptions') . '...';
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

    public function actionChecked(): bool
    {
        $ok = true;

        $this->template = 'install/checked';

        $this->checks = [];
        $value = $this->checkBrowser();
        if ($value) {
            $this->checks[] = $value;
            $ok = $ok && $value['ok'];
        }
        $value = $this->checkMinPhp();
        if ($value) {
            $this->checks[] = $value;
            $ok = $ok && $value['ok'];
        }
        $value = $this->checkMaxPhp();
        if ($value) {
            $this->checks[] = $value;
            $ok = $ok && $value['ok'];
        }
        $value = $this->checkGetPostSupport();
        if ($value) {
            $this->checks[] = $value;
            $ok = $ok && $value['ok'];
        }
        $value = $this->checkSessionId();
        if ($value) {
            $this->checks[] = $value;
            $ok = $ok && $value['ok'];
        }
        $value = $this->checkMbStringExtension();
        if ($value) {
            $this->checks[] = $value;
            $ok = $ok && $value['ok'];
        }
        $value = $this->checkJsonExtension();
        if ($value) {
            $this->checks[] = $value;
            $ok = $ok && $value['ok'];
        }
        $value = $this->checkGdExtension();
        if ($value) {
            $this->checks[] = $value;
            $ok = $ok && $value['ok'];
        }
        $value = $this->checkCurlExtension();
        if ($value) {
            $this->checks[] = $value;
            $ok = $ok && $value['ok'];
        }
        $value = $this->checkCalendarExtension();
        if ($value) {
            $this->checks[] = $value;
            $ok = $ok && $value['ok'];
        }
        $value = $this->checkXmlExtension();
        if ($value) {
            $this->checks[] = $value;
            $ok = $ok && $value['ok'];
        }
        $value = $this->checkUtfExtension();
        if ($value) {
            $this->checks[] = $value;
            $ok = $ok && $value['ok'];
        }
        $value = $this->checkIntlExtension();
        if ($value) {
            $this->checks[] = $value;
            $ok = $ok && $value['ok'];
        }
        $value = $this->checkImapExtension();
        if ($value) {
            $this->checks[] = $value;
            $ok = $ok && $value['ok'];
        }
        $value = $this->checkZipExtension();
        if ($value) {
            $this->checks[] = $value;
            $ok = $ok && $value['ok'];
        }
        $value = $this->checkMemory();
        if ($value) {
            $this->checks[] = $value;
            $ok = $ok && $value['ok'];
        }

        if (!$ok) {
            $this->checks[] = [
                'icon' => 'error',
                'text' => $this->lang->trans('ErrorGoBackAndCorrectParameters'),
            ];
            return $ok;
        }

        $value = $this->checkConfFile();
        if ($value) {
            $this->checks[] = $value;
            $ok = $ok && $value['ok'];
        }

        $conffile = Globals::getConfFilename();
        if (!file_exists($conffile)) {
            $text = $this->lang->trans('YouMustCreateWithPermission', $conffile);
            $text .= '<br><br>';
            $text .= '<span class="opacitymedium">' . $this->lang->trans("CorrectProblemAndReloadPage", $_SERVER['PHP_SELF'] . '?testget=ok') . '</span>';

            $this->checks[] = [
                'icon' => 'error',
                'text' => $text,
            ];

            return false;
        }

        $value = $this->checkIfWritable();
        if ($value) {
            $this->checks[] = $value;
            $ok = $ok && $value['ok'];
        }

        if (!$ok) {
            $this->checks[] = [
                'icon' => 'error',
                'text' => $this->lang->trans('ErrorGoBackAndCorrectParameters'),
            ];
            return $ok;
        }

        $value = $this->next();
        if ($value) {
            $this->checks[] = $value;
            $ok = $ok && $value['ok'];
        }

        return $ok;
    }

    public function checkAction(): bool
    {
        if (parent::checkAction()) {
            return true;
        }

        switch ($this->action) {
            case 'checked':
                return $this->actionChecked();
            default:
                $this->syslog("The action $this->action is not defined!");
        }

        return false;
    }

    public function body()
    {
        return parent::body();
    }

}