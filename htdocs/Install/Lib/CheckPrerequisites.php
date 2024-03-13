<?php

namespace Alixar\Install\Lib;

use Alxarafe\Base\Config;
use Alxarafe\Base\Globals;
use Alxarafe\DB\DB;
use Alxarafe\Lib\Admin;
use Alxarafe\Lib\Functions;
use Alxarafe\LibClass\Lang;

/**
 * Class CheckPrerequisites
 *
 * Perform a system check to see if you can continue with the installation or update.
 *
 * @package Alixar\Install\Lib
 */
abstract class CheckPrerequisites
{
    /**
     * Contains an array with the results of the checks carried out
     *
     * @var array
     */
    public static $checks = [];

    /**
     * Contains an array with the messages that must be passed to the view template.
     *
     * @var array
     */
    public static $messages = [];

    private static function checks($value): bool
    {
        $ok = true;
        if (isset($value['ok'])) {
            static::$checks[] = $value;
            $ok = $value['ok'];
        }
        return $ok;
    }

    /**
     * Check if the browse is modern enough for the application to work correctly.
     *
     * @return array|false
     */
    private static function checkBrowser()
    {
        $useragent = $_SERVER['HTTP_USER_AGENT'];
        if (empty($useragent)) {
            return false;
        }

        $browser = Functions::getBrowserInfo($_SERVER["HTTP_USER_AGENT"]);
        $warning = $browser['browsername'] == 'ie' && $browser['browserversion'];
        if (!$warning) {
            return false;
        }

        $result = [];
        $result['ok'] = true;
        $result['icon'] = 'warning';
        $result['text'] = Lang::_("WarningBrowserTooOld");
        return $result;
    }

    /**
     * Checks if the PHP version is sufficient to run the application.
     * Checking is unnecessary because it is controlled by Composer.
     *
     * @return array
     */
    private static function checkMinPhp(): array
    {
        $arrayphpminversionerror = [7, 0, 0];
        $arrayphpminversionwarning = [7, 1, 0];

        $result = [];
        $result['ok'] = true;

        if (Admin::versioncompare(Admin::versionphparray(), $arrayphpminversionerror) < 0) {        // Minimum to use (error if lower)
            $result['ok'] = false;
            $result['icon'] = 'error';
            $result['text'] = Lang::_("ErrorPHPVersionTooLow", Admin::versiontostring($arrayphpminversionerror));
        } elseif (Admin::versioncompare(Admin::versionphparray(), $arrayphpminversionwarning) < 0) {    // Minimum supported (warning if lower)
            $result['icon'] = 'warning';
            $result['text'] = Lang::_("ErrorPHPVersionTooLow", Admin::versiontostring($arrayphpminversionwarning));
        } else {
            $result['icon'] = 'ok';
            $result['text'] = Lang::_("PHPVersion") . " " . Admin::versiontostring(Admin::versionphparray());
        }

        if (empty($force_install_nophpinfo)) {
            $result['text'] .= ' (<a href="phpinfo.php" target="_blank" rel="noopener noreferrer">' . Lang::_("MoreInformation") . '</a>)';
        }

        return $result;
    }

    /**
     * Check if the PHP version is too modern and has not been verified
     * for the application to run.
     *
     * @return false|array
     */
    private static function checkMaxPhp()
    {
        $arrayphpmaxversionwarning = [8, 2, 0];
        if (Admin::versioncompare(Admin::versionphparray(), $arrayphpmaxversionwarning) > 0 && Admin::versioncompare(Admin::versionphparray(), $arrayphpmaxversionwarning) < 3) {        // Maximum to use (warning if higher)
            $result = [];
            $result['ok'] = false;
            $result['icon'] = 'error';
            $result['text'] = Lang::_("ErrorPHPVersionTooHigh", Admin::versiontostring($arrayphpmaxversionwarning));
            return $result;
        }

        return false;
    }

    /**
     * Check if the use of POST and GET is enabled.
     *
     * @return array
     */
    private static function checkGetPostSupport(): array
    {
        $result = [];
        $result['ok'] = true;
        if (empty($_GET) || empty($_POST)) {   // We must keep $_GET and $_POST here
            $result['icon'] = 'warning';
            $result['text'] = Lang::_("PHPSupportPOSTGETKo") . ' (<a href="' . Functions::dol_escape_htmltag($_SERVER["PHP_SELF"]) . '?testget=ok">' . Lang::_("Recheck") . '</a>)';
        } else {
            $result['icon'] = 'ok';
            $result['text'] = Lang::_("PHPSupportPOSTGETOk");
        }
        return $result;
    }

    /**
     * Check if function session_id exists.
     *
     * @return array
     */
    private static function checkSessionId(): array
    {
        $result = [];
        $result['ok'] = function_exists("session_id");
        if ($result['ok']) {
            $result['icon'] = 'ok';
            $result['text'] = Lang::_("PHPSupportSessions");
        } else {
            $result['icon'] = 'error';
            $result['text'] = Lang::_("ErrorPHPDoesNotSupportSessions");
        }
        return $result;
    }

    /**
     * Check if mbstring extension is loaded.
     *
     * @return array
     */
    private static function checkMbStringExtension(): array
    {
        $result = [];
        $result['ok'] = extension_loaded("mbstring");
        if (!$result['ok']) {
            $result['icon'] = 'error';
            $result['text'] = Lang::_("ErrorPHPDoesNotSupport", "MBString");
        } else {
            $result['icon'] = 'ok';
            $result['text'] = Lang::_("PHPSupport", "MBString");
        }
        return $result;
    }

    /**
     * Check if json extension is loaded.
     *
     * @return array
     */
    private static function checkJsonExtension(): array
    {
        $result = [];
        $result['ok'] = extension_loaded("json");
        if (!$result['ok']) {
            $result['icon'] = 'error';
            $result['text'] = Lang::_("ErrorPHPDoesNotSupport", "JSON");
        } else {
            $result['icon'] = 'ok';
            $result['text'] = Lang::_("PHPSupport", "JSON");
        }
        return $result;
    }

    /**
     * Check if function imagecreate exists.
     *
     * @return array
     */
    private static function checkGdExtension(): array
    {
        $result = [];
        $result['ok'] = true;
        if (!function_exists("imagecreate")) {
            $result['icon'] = 'warning';
            $result['text'] = Lang::_("ErrorPHPDoesNotSupport", "GD");
        } else {
            $result['icon'] = 'ok';
            $result['text'] = Lang::_("PHPSupport", "GD");
        }
        return $result;
    }

    /**
     * Check if function curl_init exists.
     *
     * @return array
     */
    private static function checkCurlExtension(): array
    {
        $result = [];
        $result['ok'] = true;
        if (!function_exists("curl_init")) {
            $result['icon'] = 'warning';
            $result['text'] = Lang::_("ErrorPHPDoesNotSupport", "Curl");
        } else {
            $result['icon'] = 'ok';
            $result['text'] = Lang::_("PHPSupport", "Curl");
        }
        return $result;
    }

    /**
     * Check if function easter_date exists.
     *
     * @return array
     */
    private static function checkCalendarExtension(): array
    {
        $result = [];
        $result['ok'] = function_exists("easter_date");
        if (!$result['ok']) {
            $result['icon'] = 'error';
            $result['text'] = Lang::_("ErrorPHPDoesNotSupport", "Calendar");
        } else {
            $result['icon'] = 'ok';
            $result['text'] = Lang::_("PHPSupport", "Calendar");
        }
        return $result;
    }

    /**
     * Check if function simplexml_load_string exists.
     *
     * @return array
     */
    private static function checkXmlExtension(): array
    {
        $result = [];
        $result['ok'] = function_exists("simplexml_load_string");
        if (!$result['ok']) {
            $result['icon'] = 'error';
            $result['text'] = Lang::_("ErrorPHPDoesNotSupport", "Xml");
        } else {
            $result['icon'] = 'ok';
            $result['text'] = Lang::_("PHPSupport", "Xml");
        }
        return $result;
    }

    /**
     * Check if function utf8_encode exists.
     *
     * @return array
     */
    private static function checkUtfExtension(): array
    {
        $result = [];
        $result['ok'] = function_exists("utf8_encode");
        if (!$result['ok']) {
            $result['icon'] = 'error';
            $result['text'] = Lang::_("ErrorPHPDoesNotSupport", "UTF8");
        } else {
            $result['icon'] = 'ok';
            $result['text'] = Lang::_("PHPSupport", "UTF8");
        }
        return $result;
    }

    /**
     * Check if function locale_get_primary_language exists.
     *
     * @return false|array
     */
    private static function checkIntlExtension()
    {
        if (empty($_SERVER["SERVER_ADMIN"]) || $_SERVER["SERVER_ADMIN"] != 'doliwamp@localhost') {
            $result = [];
            $result['ok'] = function_exists("locale_get_primary_language") && function_exists("locale_get_region");
            if (!$result['ok']) {
                $result['icon'] = 'error';
                $result['text'] = Lang::_("ErrorPHPDoesNotSupport", "Intl");
            } else {
                $result['icon'] = 'ok';
                $result['text'] = Lang::_("PHPSupport", "Intl");
            }
            return $result;
        }

        return false;
    }

    /**
     * Check if function imap_open exists.
     *
     * @return array|false
     */
    private static function checkImapExtension()
    {
        if (PHP_VERSION_ID > 80300) {
            return false;
        }

        $result = [];
        $result['ok'] = function_exists("imap_open");
        if (!$result['ok']) {
            $result['icon'] = 'error';
            $result['text'] = Lang::_("ErrorPHPDoesNotSupport", "IMAP");
        } else {
            $result['icon'] = 'ok';
            $result['text'] = Lang::_("PHPSupport", "IMAP");
        }
        return $result;
    }

    /**
     * Check if ZipArchive function exists.
     *
     * @return array
     */
    private static function checkZipExtension(): array
    {
        $result = [];
        $result['ok'] = class_exists('ZipArchive');
        if (!$result['ok']) {
            $result['icon'] = 'error';
            $result['text'] = Lang::_("ErrorPHPDoesNotSupport", "ZIP");
        } else {
            $result['icon'] = 'ok';
            $result['text'] = Lang::_("PHPSupport", "ZIP");
        }
        return $result;
    }

    /**
     * Checks if the amount of memory allocated is sufficient for the application.
     *
     * @return array|false
     */
    private static function checkMemory()
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
            $result['text'] = Lang::_("PHPMemoryOK", $memmaxorig, $memrequiredorig);
        } else {
            $result['icon'] = 'warning';
            $result['text'] = Lang::_("PHPMemoryTooLow", $memmaxorig, $memrequiredorig);
        }
        return $result;
    }

    private static function checkConfFile()
    {
        $conffile = Config::getDolibarrConfigFilename();

        clearstatcache();
        if (is_readable($conffile) && filesize($conffile) > 8) {
            Functions::syslog("check: conf file '" . $conffile . "' already defined");
            return false;
        }

        // If not, we create it
        Functions::syslog("check: we try to create conf file '" . $conffile . "'");

        // First we try by copying example
        if (@copy($conffile . ".example", $conffile)) {
            // Success
            Functions::syslog("check: successfully copied file " . $conffile . ".example into " . $conffile);
            return false;
        }

        // If failed, we try to create an empty file
        Functions::syslog("check: failed to copy file " . $conffile . ".example into " . $conffile . ". We try to create it.", LOG_WARNING);

        $fp = @fopen($conffile, "w");
        if ($fp) {
            @fwrite($fp, '<?php');
            @fwrite($fp, "\n");
            fclose($fp);
            return false;
        }

        Functions::syslog("check: failed to create a new file " . $conffile . " into current dir " . getcwd() . ". Please check permissions.", LOG_ERR);
        $result = [];
        $result['ok'] = false;
        $result['icon'] = 'error';
        $result['text'] = Lang::_('ConfFileDoesNotExistsAndCouldNotBeCreated', 'conf.php');
        return $result;
    }

    private static function checkIfWritable()
    {
        $conffile = Config::getDolibarrConfigFilename();

        $result = [];
        $result['ok'] = false;
        $result['icon'] = 'error';

        if (is_dir($conffile)) {
            $result['text'] = Lang::_('ConfFileMustBeAFileNotADir', $conffile);
            return $result;
        }

        $allowInstall = is_writable($conffile);
        if (!$allowInstall) {
            $result['text'] = Lang::_('ConfFileIsNotWritable', $conffile);
            return $result;
        }

        return false;
    }

    private static function getDataDir(string $dir): string
    {
        $dir = trim($dir, ' /');
        $fullDir = realpath(BASE_PATH . '/../dolibarr_htdocs/install/' . $dir);
        if (!file_exists(BASE_PATH)) {
            $fullDir = realpath(BASE_PATH . '/Install/' . $dir);
        }
        if ($fullDir === false) {
            $fullDir = '/Install/' . $dir;
        }
        return $fullDir . '/';
    }

    private static function getMigrationScript()
    {
        $dir = static::getDataDir('mysql/migration');   // We use mysql migration scripts whatever is database driver
        // dolibarr_install_syslog("Scan sql files for migration files in " . $dir);

        // Get files list of migration file x.y.z-a.b.c.sql into /install/mysql/migration
        $migrationscript = [];
        $handle = opendir($dir);
        if (!is_resource($handle)) {
            $errorMigrations = Lang::_("ErrorCanNotReadDir", $dir);
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
        return Functions::dol_sort_array($migrationscript, 'from', 'asc', 1);
    }

    private static function next()
    {
        $ok = false;
        $errorBadMainDocumentRoot = '';

        $config = Config::loadConfig();
        $conf = Globals::getConf();

        // It's a update?
        if (!empty($config->DB->DB_CONNECTION) && !empty($config->MAIN->APP_BASEPATH)) {
            if ($config->MAIN->APP_BASEPATH !== BASE_PATH) {
                $errorBadMainDocumentRoot = "A config file exists with a incorrect BASEPATH. Try to fix or remove the config file.";
                Functions::dol_syslog($errorBadMainDocumentRoot, LOG_WARNING);
            } else {
                // If password is encoded, we decode it
                // TODO: Pending
                if (preg_match('/crypted:/i', $config->DB->DB_PASSWORD) || !empty($dolibarr_main_db_encrypted_pass)) {
                    if (preg_match('/crypted:/i', $config->DB->DB_PASSWORD)) {
                        $dolibarr_main_db_encrypted_pass = preg_replace('/crypted:/i', '', $config->DB->DB_PASSWORD); // We need to set this as it is used to know the password was initially encrypted
                    }
                    $config->DB->DB_PASSWORD = dol_decode($dolibarr_main_db_encrypted_pass);
                }

                $db = Globals::getDb();
                if (DB::$connected && $db->database_selected) {
                    $ok = true;
                }
            }
        }

        $availableChoices = [];
        $notAvailableChoices = [];

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
            $dolibarrversiontoinstallarray = Admin::versiondolibarrarray();
        }

        $printVersion = Functions::getDolGlobalString('MAIN_VERSION_LAST_UPGRADE') || Functions::getDolGlobalString('MAIN_VERSION_LAST_INSTALL');

        $foundrecommandedchoice = 0;

        if (empty($dolibarr_main_db_host)) {    // This means install process was not run
            $foundrecommandedchoice = 1; // To show only once
        }

        $allowInstall = is_writable(Config::getConfigFilename());

        $button = $allowInstall
            ? '<button name="action" type="submit" value="start">' . Lang::_("Start") . '</button>'
            : ($foundrecommandedchoice ? '<span class="warning">' : '') . Lang::_("InstallNotAllowed") . ($foundrecommandedchoice ? '</span>' : '');

        // Show line of first install choice
        $choice = [
            'selected' => true,
            'short' => Lang::_("FreshInstall"),
            'long' => Lang::_("FreshInstallDesc"),
            'active' => $allowInstall,
            'button' => $button,
        ];

        if (!isset($config->DB->DB_HOST) || empty($config->DB->DB_HOST)) {
            $choice['long'] .= '<br><div class="center"><div class="ok suggestedchoice">' . Lang::_("InstallChoiceSuggested") . '</div></div>';
        }

        $positionkey = ($foundrecommandedchoice ? 999 : 0);
        if ($allowInstall) {
            $availableChoices[$positionkey] = $choice;
        } else {
            $notAvailableChoices[$positionkey] = $choice;
        }

        // Show upgrade lines
        $allowupgrade = true;
        if (empty($config->DB->DB_HOST)) {    // This means install process was not run
            $allowupgrade = false;
        }
        if (Functions::getDolGlobalInt("MAIN_NOT_INSTALLED")) {
            $allowupgrade = false;
        }
        if (Functions::GETPOST('allowupgrade')) {
            $allowupgrade = true;
        }

        $errorMigrations = false;
        $migrationscript = static::getMigrationScript();

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
                $newversionfrombis = ' ' . Lang::_("or") . ' ' . $versionto;
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

            $button = Lang::_("NotAvailable");
            if ($allowupgrade) {
                $disabled = false;
                if ($foundrecommandedchoice == 2) {
                    $disabled = true;
                }
                if ($foundrecommandedchoice == 1) {
                    $foundrecommandedchoice = 2;
                }
                if ($disabled) {
                    $button = '<span class="opacitymedium">' . Lang::_("NotYetAvailable") . '</span>';
                } else {
                    // TODO: Pending fix how to pass the version in an action
                    $lang = Globals::getLang();
                    $button = '<a class="button runupgrade" href="upgrade.php?action=upgrade' . ($count < count($migrationscript) ? '_' . $versionto : '') . '&selectlang=' . $lang->getDefaultLang() . '&versionfrom=' . $versionfrom . '&versionto=' . $versionto . '">' . Lang::_("Start") . '</a>';
                }
            }

            $choice = [
                'selected' => $recommended_choice,
                'short' => Lang::_("Upgrade") . '<br>' . $newversionfrom . $newversionfrombis . ' -> ' . $newversionto,
                'long' => Lang::_("UpgradeDesc"),
                'active' => $allowInstall,
                'button' => $button,
            ];

            if ($recommended_choice) {
                $choice['long'] .= '<br><div class="center"><div class="ok suggestedchoice">' . Lang::_("InstallChoiceSuggested") . '</div>';
                if ($count < count($migarray)) {
                    $choice['long'] .= Lang::_('MigrateIsDoneStepByStep', DOL_VERSION);
                }
                $choice['long'] .= '</div>';
            }

            if ($allowupgrade) {
                $availableChoices[$count] = $choice;
            } else {
                $notAvailableChoices[$count] = $choice;
            }
        }

        // If there is no choice at all, we show all of them.
        if (empty($availableChoices)) {
            $availableChoices = $notAvailableChoices;
            $notAvailableChoices = [];
        }

        // Array of install choices
        krsort($availableChoices, SORT_NATURAL);

        static::$messages = [];

        $badMainDocumentRoot = '';
        if ($errorBadMainDocumentRoot) {
            $badMainDocumentRoot = Lang::_($errorBadMainDocumentRoot);
        }
        $versionLastUpgradeMessage = Lang::_("VersionLastUpgrade");
        $versionLastUpgrade = '';
        if (Functions::getDolGlobalString('MAIN_VERSION_LAST_UPGRADE') || Functions::getDolGlobalString('MAIN_VERSION_LAST_INSTALL')) {
            $versionLastUpgrade = (!Functions::getDolGlobalString('MAIN_VERSION_LAST_UPGRADE')
                ? Functions::getDolGlobalString('MAIN_VERSION_LAST_INSTALL')
                : Functions::getDolGlobalString('MAIN_VERSION_LAST_UPGRADE')
            );
        }
        $versionProgramMessage = Lang::_("VersionProgram");
        $chooseYourSetupMode = Lang::_("ChooseYourSetupMode");
        $showNotAvailableOptions = Lang::_('ShowNotAvailableOptions');

        $lang = Globals::getLang();
        $warningUpdates = Functions::dol_escape_js($lang->transnoentitiesnoconv('WarningUpdates'), 0, 1);

        return [
            'availableChoices' => $availableChoices,
            'notAvailableChoices' => $notAvailableChoices,
            'badMainDocumentRoot' => $badMainDocumentRoot,
            'versionLastUpgrade' => $versionLastUpgrade,
            'versionLastUpdateMessage' => $versionLastUpgradeMessage,
            'versionProgramMessage' => $versionProgramMessage,
            'chooseYourSetupMode' => $chooseYourSetupMode,
            'showNotAvailableOptions' => $showNotAvailableOptions,
            'warningUpdates' => $warningUpdates,
        ];
    }

    private function nextNext()
    {
        /*
    // Obtiene el nombre del archivo de configuración
        $this->configFilename = Config::getDolibarrConfigFilename();
        $this->errorBadMainDocumentRoot = '';

    // Verifica si la configuración de la base de datos y la ruta del documento principal son correctas
        if (!empty($this->config->main_db_type) && !empty($this->config->main_document_root) && $this->config->main_document_root !== BASE_PATH) {
            $this->errorBadMainDocumentRoot = "El archivo $this->configFilename contiene una ruta de documento principal incorrecta: $this->config->main_document_root. Intente corregir o eliminar el archivo $this->configFilename.";
            Functions::dol_syslog($this->errorBadMainDocumentRoot, LOG_WARNING);
        } else {
            // Decodifica la contraseña si está codificada
            if (preg_match('/crypted:/i', $config->DB->DB_PASSWORD) || !empty($dolibarr_main_db_encrypted_pass)) {
                require_once $this->dolibarr_main_document_root . '/core/lib/security.lib.php';
                $config->DB->DB_PASSWORD = dol_decode(preg_replace('/crypted:/i', '', $config->DB->DB_PASSWORD) ?? $dolibarr_main_db_encrypted_pass);
            }

            // Establece la configuración de la base de datos
            $this->conf->db = (array)$this->config;
            $db = Functions::getDoliDBInstance($this->conf->db);

            // Verifica la conexión a la base de datos
            if (DB::$connected && $db->database_selected) {
                $ok = true;
            }
        }

    // Prepara opciones de instalación y actualización
        $this->prepareInstallationChoices($ok);
        $this->prepareUpgradeChoices($ok);
        */
    }

    /**
     * Perform a prerequisite check
     *
     * Views: install/checked
     *
     * Performs a check of the prerequisites, to verify that the installation can be carried out and
     * informing if there is anything to correct.
     * Shows a list with all the options available or not, for installing or updating the application.
     *
     * @return bool
     */
    public static function getErrors()
    {
        static::$checks = [];
        $ok = true;
        $ok = $ok && static::checks(static::checkBrowser());
        $ok = $ok && static::checks(static::checkMinPhp());
        $ok = $ok && static::checks(static::checkMaxPhp());
        $ok = $ok && static::checks(static::checkGetPostSupport());
        $ok = $ok && static::checks(static::checkSessionId());
        $ok = $ok && static::checks(static::checkMbStringExtension());
        $ok = $ok && static::checks(static::checkJsonExtension());
        $ok = $ok && static::checks(static::checkGdExtension());
        $ok = $ok && static::checks(static::checkCurlExtension());
        $ok = $ok && static::checks(static::checkCalendarExtension());
        $ok = $ok && static::checks(static::checkXmlExtension());
        $ok = $ok && static::checks(static::checkUtfExtension());
        $ok = $ok && static::checks(static::checkIntlExtension());
        $ok = $ok && static::checks(static::checkImapExtension());
        $ok = $ok && static::checks(static::checkZipExtension());
        $ok = $ok && static::checks(static::checkMemory());

        if (!$ok) {
            static::$checks[] = [
                'icon' => 'error',
                'text' => Lang::_('ErrorGoBackAndCorrectParameters'),
            ];
            return $ok;
        }

        $ok = static::checks(static::checkConfFile());

        $conffile = Config::getDolibarrConfigFilename();
        if (!file_exists($conffile)) {
            $text = Lang::_('YouMustCreateWithPermission', $conffile);
            $text .= '<br><br>';
            $text .= '<span class="opacitymedium">' . Lang::_("CorrectProblemAndReloadPage", $_SERVER['PHP_SELF'] . '?testget=ok') . '</span>';

            static::$checks[] = [
                'icon' => 'error',
                'text' => $text,
            ];

            return false;
        }

        $ok = $ok && static::checks(static::checkIfWritable());
        if (!$ok) {
            static::$checks[] = [
                'icon' => 'error',
                'text' => Lang::_('ErrorGoBackAndCorrectParameters'),
            ];
            return false;
        }

        static::$messages = static::next();

        return true;
    }

}