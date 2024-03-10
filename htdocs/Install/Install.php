<?php

namespace Alixar\Install;

use Alxarafe\Base\BasicController;
use Alxarafe\Base\Globals;
use Alxarafe\DB\DB;
use Alxarafe\Lib\Admin;
use Alxarafe\Lib\Files;
use Alxarafe\Lib\Functions;
use Alxarafe\Lib\HookManager;
use Alxarafe\Lib\Security;
use Alxarafe\LibClass\FormAdmin;
use Exception;
use Modules\UserModule;
use Modules\User\User;

class Install extends BasicController
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
    private function dolibarr_install_syslog($message, $level = LOG_DEBUG)
    {
        if (!defined('LOG_DEBUG')) {
            define('LOG_DEBUG', 6);
        }
        Functions::dol_syslog($message, $level);
    }

    /**
     * Automatically detect Dolibarr's main data root
     *
     * @param string $this ->dolibarr_main_document_root Current main document root
     *
     * @return string
     */
    private function detect_dolibarr_main_data_root($dolibarr_main_document_root)
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
    private function detect_dolibarr_main_url_root()
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

    /**
     * Replaces automatic database login by actual value
     *
     * @param string $force_install_databaserootlogin Login
     *
     * @return string
     */
    private function parse_database_login($force_install_databaserootlogin)
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
    private function parse_database_pass($force_install_databaserootpass)
    {
        return preg_replace('/__SUPERUSERPASSWORD__/', '', $force_install_databaserootpass);
    }

    /**
     *  Create main file. No particular permissions are set by installer.
     *
     * @param string $mainfile Full path name of main file to generate/update
     * @param string $this     ->main_dir Full path name to main.inc.php file
     *
     * @return void
     */
    private function write_main_file($mainfile, $main_dir)
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
     * @param string $this       ->main_dir   Full path name to master.inc.php file
     *
     * @return void
     */
    private function write_master_file($masterfile, $main_dir)
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

    /**
     * Load conf file (file must exists)
     *
     * @param string $dolibarr_main_document_root Root directory of Dolibarr bin files
     *
     * @return  int                                             Return integer <0 if KO, >0 if OK
     */
    private function conf($dolibarr_main_document_root = BASE_PATH)
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

    /**
     *  Save configuration file. No particular permissions are set by installer.
     *
     * @param string $conffile Path to conf file to generate/update
     *
     * @return integer
     */
    private function write_conf_file($conffile)
    {
        $error = 0;

        $key = md5(uniqid(mt_rand(), true)); // Generate random hash

        $fp = fopen("$conffile", "w");
        if ($fp) {
            clearstatcache();

            fwrite($fp, '<?php' . "\n");
            fwrite($fp, '//' . "\n");
            fwrite($fp, '// File generated by Dolibarr installer ' . DOL_VERSION . ' on ' . Functions::dol_print_date(Functions::dol_now(), '') . "\n");
            fwrite($fp, '//' . "\n");
            fwrite($fp, '// Take a look at conf.php.example file for an example of ' . $conffile . ' file' . "\n");
            fwrite($fp, '// and explanations for all possibles parameters.' . "\n");
            fwrite($fp, '//' . "\n");

            fwrite($fp, '$dolibarr_main_url_root=\'' . Functions::dol_escape_php(trim($this->main_url), 1) . '\';');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_main_document_root="' . Functions::dol_escape_php(Functions::dol_sanitizePathName(trim($this->main_dir))) . '";');
            fwrite($fp, "\n");

            fwrite($fp, $this->main_use_alt_dir . '$dolibarr_main_url_root_alt=\'' . Functions::dol_escape_php(trim("/" . $this->main_alt_dir_name), 1) . '\';');
            fwrite($fp, "\n");

            fwrite($fp, $this->main_use_alt_dir . '$dolibarr_main_document_root_alt="' . Functions::dol_escape_php(Functions::dol_sanitizePathName(trim($this->main_dir . "/" . $this->main_alt_dir_name))) . '";');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_main_data_root="' . Functions::dol_escape_php(Functions::dol_sanitizePathName(trim($this->main_data_dir))) . '";');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_main_db_host=\'' . Functions::dol_escape_php(trim($this->db_host), 1) . '\';');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_main_db_port=\'' . ((int) $this->db_port) . '\';');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_main_db_name=\'' . Functions::dol_escape_php(trim($this->db_name), 1) . '\';');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_main_db_prefix=\'' . Functions::dol_escape_php(trim($this->db_prefix), 1) . '\';');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_main_db_user=\'' . Functions::dol_escape_php(trim($this->db_user), 1) . '\';');
            fwrite($fp, "\n");
            fwrite($fp, '$dolibarr_main_db_pass=\'' . Functions::dol_escape_php(trim($this->db_pass), 1) . '\';');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_main_db_type=\'' . Functions::dol_escape_php(trim($this->db_type), 1) . '\';');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_main_db_character_set=\'' . Functions::dol_escape_php(trim($this->db_character_set), 1) . '\';');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_main_db_collation=\'' . Functions::dol_escape_php(trim($this->db_collation), 1) . '\';');
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

            fwrite($fp, '$dolibarr_main_force_https=\'' . Functions::dol_escape_php($this->main_force_https, 1) . '\';');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_main_restrict_os_commands=\'mariadb-dump, mariadb, mysqldump, mysql, pg_dump, pgrestore, clamdscan, clamscan.exe\';');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_nocsrfcheck=\'0\';');
            fwrite($fp, "\n");

            fwrite($fp, '$dolibarr_main_instance_unique_id=\'' . Functions::dol_escape_php($key, 1) . '\';');
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
            fwrite($fp, '$dolibarr_lib_FPDF_PATH="' . Functions::dol_escape_php(Functions::dol_sanitizePathName($force_dolibarr_lib_FPDF_PATH)) . '";');
            fwrite($fp, "\n");
            if (empty($force_dolibarr_lib_TCPDF_PATH)) {
                fwrite($fp, '//');
                $force_dolibarr_lib_TCPDF_PATH = '';
            }
            fwrite($fp, '$dolibarr_lib_TCPDF_PATH="' . Functions::dol_escape_php(Functions::dol_sanitizePathName($force_dolibarr_lib_TCPDF_PATH)) . '";');
            fwrite($fp, "\n");
            if (empty($force_dolibarr_lib_FPDI_PATH)) {
                fwrite($fp, '//');
                $force_dolibarr_lib_FPDI_PATH = '';
            }
            fwrite($fp, '$dolibarr_lib_FPDI_PATH="' . Functions::dol_escape_php(Functions::dol_sanitizePathName($force_dolibarr_lib_FPDI_PATH)) . '";');
            fwrite($fp, "\n");
            if (empty($force_dolibarr_lib_TCPDI_PATH)) {
                fwrite($fp, '//');
                $force_dolibarr_lib_TCPDI_PATH = '';
            }
            fwrite($fp, '$dolibarr_lib_TCPDI_PATH="' . Functions::dol_escape_php(Functions::dol_sanitizePathName($force_dolibarr_lib_TCPDI_PATH)) . '";');
            fwrite($fp, "\n");
            if (empty($force_dolibarr_lib_GEOIP_PATH)) {
                fwrite($fp, '//');
                $force_dolibarr_lib_GEOIP_PATH = '';
            }
            fwrite($fp, '$dolibarr_lib_GEOIP_PATH="' . Functions::dol_escape_php(Functions::dol_sanitizePathName($force_dolibarr_lib_GEOIP_PATH)) . '";');
            fwrite($fp, "\n");
            if (empty($force_dolibarr_lib_NUSOAP_PATH)) {
                fwrite($fp, '//');
                $force_dolibarr_lib_NUSOAP_PATH = '';
            }
            fwrite($fp, '$dolibarr_lib_NUSOAP_PATH="' . Functions::dol_escape_php(Functions::dol_sanitizePathName($force_dolibarr_lib_NUSOAP_PATH)) . '";');
            fwrite($fp, "\n");
            if (empty($force_dolibarr_lib_ODTPHP_PATH)) {
                fwrite($fp, '//');
                $force_dolibarr_lib_ODTPHP_PATH = '';
            }
            fwrite($fp, '$dolibarr_lib_ODTPHP_PATH="' . Functions::dol_escape_php(Functions::dol_sanitizePathName($force_dolibarr_lib_ODTPHP_PATH)) . '";');
            fwrite($fp, "\n");
            if (empty($force_dolibarr_lib_ODTPHP_PATHTOPCLZIP)) {
                fwrite($fp, '//');
                $force_dolibarr_lib_ODTPHP_PATHTOPCLZIP = '';
            }
            fwrite($fp, '$dolibarr_lib_ODTPHP_PATHTOPCLZIP="' . Functions::dol_escape_php(Functions::dol_sanitizePathName($force_dolibarr_lib_ODTPHP_PATHTOPCLZIP)) . '";');
            fwrite($fp, "\n");
            if (empty($force_dolibarr_js_CKEDITOR)) {
                fwrite($fp, '//');
                $force_dolibarr_js_CKEDITOR = '';
            }
            fwrite($fp, '$dolibarr_js_CKEDITOR=\'' . Functions::dol_escape_php($force_dolibarr_js_CKEDITOR, 1) . '\';');
            fwrite($fp, "\n");
            if (empty($force_dolibarr_js_JQUERY)) {
                fwrite($fp, '//');
                $force_dolibarr_js_JQUERY = '';
            }
            fwrite($fp, '$dolibarr_js_JQUERY=\'' . Functions::dol_escape_php($force_dolibarr_js_JQUERY, 1) . '\';');
            fwrite($fp, "\n");
            if (empty($force_dolibarr_js_JQUERY_UI)) {
                fwrite($fp, '//');
                $force_dolibarr_js_JQUERY_UI = '';
            }
            fwrite($fp, '$dolibarr_js_JQUERY_UI=\'' . Functions::dol_escape_php($force_dolibarr_js_JQUERY_UI, 1) . '\';');
            fwrite($fp, "\n");

            // Write params to overwrites default font path
            fwrite($fp, "\n");
            if (empty($force_dolibarr_font_DOL_DEFAULT_TTF)) {
                fwrite($fp, '//');
                $force_dolibarr_font_DOL_DEFAULT_TTF = '';
            }
            fwrite($fp, '$dolibarr_font_DOL_DEFAULT_TTF=\'' . Functions::dol_escape_php($force_dolibarr_font_DOL_DEFAULT_TTF, 1) . '\';');
            fwrite($fp, "\n");
            if (empty($force_dolibarr_font_DOL_DEFAULT_TTF_BOLD)) {
                fwrite($fp, '//');
                $force_dolibarr_font_DOL_DEFAULT_TTF_BOLD = '';
            }
            fwrite($fp, '$dolibarr_font_DOL_DEFAULT_TTF_BOLD=\'' . Functions::dol_escape_php($force_dolibarr_font_DOL_DEFAULT_TTF_BOLD, 1) . '\';');
            fwrite($fp, "\n");

            // Other
            fwrite($fp, '$dolibarr_main_distrib=\'' . Functions::dol_escape_php(trim($this->dolibarr_main_distrib), 1) . '\';');
            fwrite($fp, "\n");

            fclose($fp);

            if (!file_exists("$conffile")) {
                $error++;
            }
        }

        return $error;
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
        if (empty($_GET) || empty($_POST)) {   // We must keep $_GET and $_POST here
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
        $conffile = Globals::getConfFilename();

        clearstatcache();
        if (is_readable($conffile) && filesize($conffile) > 8) {
            $this->syslog("check: conf file '" . $conffile . "' already defined");
            return false;
        }

        // If not, we create it
        $this->syslog("check: we try to create conf file '" . $conffile . "'");

        // First we try by copying example
        if (@copy($conffile . ".example", $conffile)) {
            // Success
            $this->syslog("check: successfully copied file " . $conffile . ".example into " . $conffile);
            return false;
        }

        // If failed, we try to create an empty file
        $this->syslog("check: failed to copy file " . $conffile . ".example into " . $conffile . ". We try to create it.", LOG_WARNING);

        $fp = @fopen($conffile, "w");
        if ($fp) {
            @fwrite($fp, '<?php');
            @fwrite($fp, "\n");
            fclose($fp);
            return false;
        }

        $this->syslog("check: failed to create a new file " . $conffile . " into current dir " . getcwd() . ". Please check permissions.", LOG_ERR);
        $result = [];
        $result['ok'] = false;
        $result['icon'] = 'error';
        $result['text'] = $this->lang->trans('ConfFileDoesNotExistsAndCouldNotBeCreated', 'conf.php');
        return $result;
    }

    public function checkIfWritable()
    {
        $conffile = Globals::getConfFilename();

        $result = [];
        $result['ok'] = false;
        $result['icon'] = 'error';

        if (is_dir($conffile)) {
            $result['text'] = $this->lang->trans('ConfFileMustBeAFileNotADir', $conffile);
            return $result;
        }

        $this->allowInstall = is_writable($conffile);
        if (!$this->allowInstall) {
            $result['text'] = $this->lang->trans('ConfFileIsNotWritable', $conffile);
            return $result;
        }

        return false;
    }

    private function getMigrationScript()
    {
        $dir = static::getDataDir('mysql/migration');   // We use mysql migration scripts whatever is database driver
        $this->dolibarr_install_syslog("Scan sql files for migration files in " . $dir);

        // Get files list of migration file x.y.z-a.b.c.sql into /install/mysql/migration
        $migrationscript = [];
        $handle = opendir($dir);
        if (!is_resource($handle)) {
            $this->errorMigrations = $this->lang->trans("ErrorCanNotReadDir", $dir);
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

    public function next()
    {

        $this->configFilename = Globals::getConfFilename();

        $ok = false;
        $this->errorBadMainDocumentRoot = '';

        // It's a update?
        if (!empty($this->config->main_db_type) && !empty($this->config->main_document_root)) {
            if ($this->config->main_document_root !== BASE_PATH) {
                $this->errorBadMainDocumentRoot = "A $this->configFilename file exists with a dolibarr_main_document_root to $this->config->main_document_root that seems wrong. Try to fix or remove the $this->configFilename file.";
                Functions::dol_syslog($this->errorBadMainDocumentRoot, LOG_WARNING);
            } else {
                // If password is encoded, we decode it
                // TODO: Pending
                if (preg_match('/crypted:/i', $this->config->main_db_pass) || !empty($dolibarr_main_db_encrypted_pass)) {
                    require_once $this->dolibarr_main_document_root . '/core/lib/security.lib.php';
                    if (preg_match('/crypted:/i', $this->config->main_db_pass)) {
                        $dolibarr_main_db_encrypted_pass = preg_replace('/crypted:/i', '', $this->config->main_db_pass); // We need to set this as it is used to know the password was initially encrypted
                        $this->config->main_db_pass = dol_decode($dolibarr_main_db_encrypted_pass);
                    } else {
                        $this->config->main_db_pass = dol_decode($dolibarr_main_db_encrypted_pass);
                    }
                }

                // $conf already created in inc.php
                $this->conf->db->type = $this->config->main_db_type;
                $this->conf->db->host = $this->config->main_db_host;
                $this->conf->db->port = $this->config->main_db_port;
                $this->conf->db->name = $this->config->main_db_name;
                $this->conf->db->user = $this->config->main_db_user;
                $this->conf->db->pass = $this->config->main_db_pass;
                $db = Functions::getDoliDBInstance($this->conf->db->type, $this->conf->db->host, $this->conf->db->user, $this->conf->db->pass, $this->conf->db->name, (int) $this->conf->db->port);
                if (DB::$connected && $db->database_selected) {
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
            $dolibarrversiontoinstallarray = Admin::versiondolibarrarray();
        }

        $this->printVersion = Functions::getDolGlobalString('MAIN_VERSION_LAST_UPGRADE') || Functions::getDolGlobalString('MAIN_VERSION_LAST_INSTALL');

        $foundrecommandedchoice = 0;

        if (empty($dolibarr_main_db_host)) {    // This means install process was not run
            $foundrecommandedchoice = 1; // To show only once
        }

        $button = $this->allowInstall
            ? '<input class="button" type="submit" name="action" value="' . $this->lang->trans("Start") . '">'
            : ($foundrecommandedchoice ? '<span class="warning">' : '') . $this->lang->trans("InstallNotAllowed") . ($foundrecommandedchoice ? '</span>' : '');

        // Show line of first install choice
        $choice = [
            'selected' => true,
            'short' => $this->lang->trans("FreshInstall"),
            'long' => $this->lang->trans("FreshInstallDesc"),
            'active' => $this->allowInstall,
            'button' => $button,
        ];

        if (!isset($this->config->main_db_host) || empty($this->config->main_db_host)) {
            $choice['long'] .= '<br><div class="center"><div class="ok suggestedchoice">' . $this->lang->trans("InstallChoiceSuggested") . '</div></div>';
        }

        $positionkey = ($foundrecommandedchoice ? 999 : 0);
        if ($this->allowInstall) {
            $this->availableChoices[$positionkey] = $choice;
        } else {
            $this->notAvailableChoices[$positionkey] = $choice;
        }

        // Show upgrade lines
        $allowupgrade = true;
        if (empty($this->config->main_db_host)) {    // This means install process was not run
            $allowupgrade = false;
        }
        if (Functions::getDolGlobalInt("MAIN_NOT_INSTALLED")) {
            $allowupgrade = false;
        }
        if (Functions::GETPOST('allowupgrade')) {
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

            $button = $this->lang->trans("NotAvailable");
            if ($allowupgrade) {
                $disabled = false;
                if ($foundrecommandedchoice == 2) {
                    $disabled = true;
                }
                if ($foundrecommandedchoice == 1) {
                    $foundrecommandedchoice = 2;
                }
                if ($disabled) {
                    $button = '<span class="opacitymedium">' . $this->lang->trans("NotYetAvailable") . '</span>';
                } else {
                    // TODO: Pending fix how to pass the version in an action
                    $button = '<a class="button runupgrade" href="upgrade.php?action=upgrade' . ($count < count($migrationscript) ? '_' . $versionto : '') . '&selectlang=' . $this->selectLang . '&versionfrom=' . $versionfrom . '&versionto=' . $versionto . '">' . $this->lang->trans("Start") . '</a>';
                }
            }

            $choice = [
                'selected' => $recommended_choice,
                'short' => $this->lang->trans("Upgrade") . '<br>' . $newversionfrom . $newversionfrombis . ' -> ' . $newversionto,
                'long' => $this->lang->trans("UpgradeDesc"),
                'active' => $this->allowInstall,
                'button' => $button,
            ];

            if ($recommended_choice) {
                $choice['long'] .= '<br><div class="center"><div class="ok suggestedchoice">' . $this->lang->trans("InstallChoiceSuggested") . '</div>';
                if ($count < count($migarray)) {
                    $choice['long'] .= $this->lang->trans('MigrateIsDoneStepByStep', DOL_VERSION);
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

    private function checks($value): bool
    {
        $ok = true;
        if (isset($value['ok'])) {
            $this->checks[] = $value;
            $ok = $value['ok'];
        }
        return $ok;
    }

    private function getDbTypes()
    {
        $options = [];
        $dir = realpath(BASE_PATH . '/../Core/DB/Engines');
        $handle = opendir($dir);
        if (is_resource($handle)) {
            while (($file = readdir($handle)) !== false) {
                if (is_readable($dir . "/" . $file) && substr($file, -10) === 'Engine.php') {
                    $shortName = substr($file, 0, -10);
                    $className = substr($file, 0, -4);
                    if ($className === 'Sqlite3Engine') {
                        continue; // We hide sqlite3 because support can't be complete until sqlite does not manage foreign key creation after table creation (ALTER TABLE child ADD CONSTRAINT not supported)
                    }

                    $class = 'Alxarafe\\DB\\Engines\\' . $className;

                    // Version min of database
                    $note = '(' . $class::LABEL . ' >= ' . $class::VERSIONMIN . ')';

                    if ($file == 'MySqliEngine.php') {
                        $oldname = 'mysqli';
                        $testfunction = 'mysqli_connect';
                    }
                    if ($file == 'PgSqlEngine.php') {
                        $oldname = 'pgsql';
                        $testfunction = 'pg_connect';
                    }

                    $comment = '';
                    if (!function_exists($testfunction)) {
                        $comment = ' - ' . $this->lang->trans("FunctionNotAvailableInThisPHP");
                    }

                    $options[] = [
                        'shortname' => $shortName,
                        'classname' => $className,
                        'min_version' => $note,
                        'comment' => $comment,
                    ];
                }
            }
        }
        return $options;
    }

    private function getDbType()
    {
        $defaultype = !empty($dolibarr_main_db_type) ? $dolibarr_main_db_type : (empty($force_install_type) ? 'mysqli' : $force_install_type);

        $modules = [];
        $nbok = $nbko = 0;
        $option = '';

    }

    /**
     * Code to execute if there is no action defined.
     * During installation, the only case in which there is no defined action is
     * on the direct execution.
     *
     * Views: install/install
     *
     * The install/install view allows to select the language.
     *
     * @return bool
     */
    public function noAction(): bool
    {
        $this->lang->loadLangs(['main', 'admin', 'install']);

        if (isset($this->config->main_url_root)) {
            return $this->actionChecked();
        }

        $this->template = 'install/install';
        $this->nextButton = true;

        $form = new FormAdmin(null);
        $this->noReadableConfig = $this->lang->trans('NoReadableConfFileSoStartInstall');
        $this->defaultLanguage = $this->lang->trans('DefaultLanguage');
        $this->htmlComboLanguages = $form->select_language('auto', 'selectlang', 1, 0, 0, 1);

        return true;
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
    public function actionChecked(): bool
    {
        $this->template = 'install/checked';

        $this->checks = [];
        $ok = true;
        $ok = $ok && $this->checks($this->checkBrowser());
        $ok = $ok && $this->checks($this->checkMinPhp());
        $ok = $ok && $this->checks($this->checkMaxPhp());
        $ok = $ok && $this->checks($this->checkGetPostSupport());
        $ok = $ok && $this->checks($this->checkSessionId());
        $ok = $ok && $this->checks($this->checkMbStringExtension());
        $ok = $ok && $this->checks($this->checkJsonExtension());
        $ok = $ok && $this->checks($this->checkGdExtension());
        $ok = $ok && $this->checks($this->checkCurlExtension());
        $ok = $ok && $this->checks($this->checkCalendarExtension());
        $ok = $ok && $this->checks($this->checkXmlExtension());
        $ok = $ok && $this->checks($this->checkUtfExtension());
        $ok = $ok && $this->checks($this->checkIntlExtension());
        $ok = $ok && $this->checks($this->checkImapExtension());
        $ok = $ok && $this->checks($this->checkZipExtension());
        $ok = $ok && $this->checks($this->checkMemory());

        if (!$ok) {
            $this->checks[] = [
                'icon' => 'error',
                'text' => $this->lang->trans('ErrorGoBackAndCorrectParameters'),
            ];
            return $ok;
        }

        $ok = $this->checks($this->checkConfFile());

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

        $ok = $ok && $this->checks($this->checkIfWritable());
        if (!$ok) {
            $this->checks[] = [
                'icon' => 'error',
                'text' => $this->lang->trans('ErrorGoBackAndCorrectParameters'),
            ];
            return false;
        }

        $this->next();

        $this->miscellaneousChecks = $this->lang->trans('MiscellaneousChecks');
        $this->badMainDocumentRoot = '';
        if ($this->errorBadMainDocumentRoot) {
            $this->badMainDocumentRoot = $this->lang->trans($this->errorBadMainDocumentRoot);
        }
        $this->versionLastUpgradeMessage = $this->lang->trans("VersionLastUpgrade");
        $this->versionLastUpgrade = '';
        if (Functions::getDolGlobalString('MAIN_VERSION_LAST_UPGRADE') || Functions::getDolGlobalString('MAIN_VERSION_LAST_INSTALL')) {
            $this->versionLastUpgrade = (!Functions::getDolGlobalString('MAIN_VERSION_LAST_UPGRADE')
                ? $this->conf->global->MAIN_VERSION_LAST_INSTALL
                : $this->conf->global->MAIN_VERSION_LAST_UPGRADE
            );
        }
        $this->versionProgramMessage = $this->lang->trans("VersionProgram");
        $this->chooseYourSetupMode = $this->lang->trans("ChooseYourSetupMode");
        $this->showNotAvailableOptions = $this->lang->trans('ShowNotAvailableOptions');
        $this->warningUpdates = Functions::dol_escape_js($this->lang->transnoentitiesnoconv('WarningUpdates'), 0, 1);

        return true;
    }

    /**
     * Shows a form with all the application configuration options.
     *
     * Views: install/start
     *
     * @return true
     */
    public function actionStart()
    {
        $this->template = 'install/start';
        $this->nextButton = true;
        $this->nextButtonJs = 'return jscheckparam();';

        $this->dolibarr_install_syslog("- fileconf: entering fileconf.php page");

        /**
         * TODO: It could be implemented using a file to force configuration.
         *
         * // Now we load forced values from install.forced.php file.
         * $useforcedwizard = false;
         * $forcedfile = "./install.forced.php";
         * if ($conffile == "/etc/dolibarr/conf.php") {
         *    $forcedfile = "/etc/dolibarr/install.forced.php"; // Must be after inc.php
         * }
         * if (@file_exists($forcedfile)) {
         *    $useforcedwizard = true;
         *    include_once $forcedfile;
         * }
         */

        session_start(); // To be able to keep info into session (used for not losing pass during navigation. pass must not transit through parameters)

        $this->subtitle = $this->lang->trans("ConfigurationFile");

        if (empty($this->config->main_document_root)) {
            $this->config->main_document_root = BASE_PATH;
        }

        if (!empty($force_install_main_data_root)) {
            $this->config->main_data_root = $force_install_main_data_root;
        }

        if (empty($this->config->main_data_root)) {
            $this->config->main_data_root = Functions::GETPOSTISSET('main_data_dir') ? Functions::GETPOST('main_data_dir') : $this->detect_dolibarr_main_data_root($this->config->main_document_root);
        }

        if (empty($this->config->main_url_root)) {
            $this->config->main_url_root = Functions::GETPOSTISSET('main_url') ? Functions::GETPOST('main_url') : $this->detect_dolibarr_main_url_root();
        }

        if (empty($this->config->main_db_type)) {
            $this->config->main_db_type = 'mysqli';
        }

        if (!isset($this->config->main_db_host)) {
            $this->config->main_db_host = "localhost";
        }

        $this->db_types = $this->getDbTypes();

        // If $force_install_databasepass is on, we don't want to set password, we just show '***'. Real value will be extracted from the forced install file at step1.
        $autofill = ((!empty($_SESSION['dol_save_pass'])) ? $_SESSION['dol_save_pass'] : str_pad('', strlen($force_install_databasepass ?? ''), '*'));
        if (!empty($dolibarr_main_prod) && empty($_SESSION['dol_save_pass'])) {    // So value can't be found if install page still accessible
            $autofill = '';
        }
        $this->autofill = Functions::dol_escape_htmltag($autofill);

        $this->install_port = !empty($force_install_port) ? $force_install_port : $this->config->main_db_port;
        $this->install_prefix = !empty($force_install_prefix) ? $force_install_prefix : (!empty($this->config->main_db_prefix) ? $this->config->main_db_prefix : Globals::DEFAULT_DB_PREFIX);

        $this->install_createdatabase = $force_install_createdatabase ?? '';
        $this->install_noedit = ($force_install_noedit ?? '') == 2 && $force_install_createdatabase !== null;

        $this->force_install_databaserootlogin = $this->parse_database_login($force_install_databaserootlogin ?? '');
        $this->force_install_databaserootpass = $this->parse_database_pass($force_install_databaserootpass ?? '');

        $this->install_databaserootlogin = (!empty($force_install_databaserootlogin)) ? $force_install_databaserootlogin : (Functions::GETPOSTISSET('db_user_root') ? Functions::GETPOST('db_user_root') : (isset($this->db_user_root) ? $this->db_user_root : ''));

        // If $force_install_databaserootpass is on, we don't want to set password here, we just show '***'. Real value will be extracted from the forced install file at step1.
        $autofill_pass_root = ((!empty($force_install_databaserootpass)) ? str_pad('', strlen($force_install_databaserootpass), '*') : (isset($this->db_pass_root) ? $this->db_pass_root : ''));
        if (!empty($dolibarr_main_prod)) {
            $autofill_pass_root = '';
        }

        // Do not autofill password if instance is a production instance
        if (
            !empty($_SERVER["SERVER_NAME"]) && !in_array(
                $_SERVER["SERVER_NAME"],
                ['127.0.0.1', 'localhost', 'localhostgit']
            )
        ) {
            $autofill_pass_root = '';
        }    // Do not autofill password for remote access
        $this->autofill_pass_root = Functions::dol_escape_htmltag($autofill_pass_root);

        $this->force_install_noedit = $force_install_noedit ?? false;
        $this->force_install_database = $force_install_database ?? false;
        $this->webPagesDirectory = $this->lang->trans("WebPagesDirectory");

        return true;
    }

    private function actionConfig()
    {
        $this->template = 'install/step1';
        $this->nextButton = true;

        $action = Functions::GETPOST('action', 'aZ09') ? Functions::GETPOST('action', 'aZ09') : (empty($argv[1]) ? '' : $argv[1]);
        $setuplang = Functions::GETPOST('selectlang', 'aZ09', 3) ? Functions::GETPOST('selectlang', 'aZ09', 3) : (empty($argv[2]) ? 'auto' : $argv[2]);
        $this->lang->setDefaultLang($setuplang);

        $this->lang->loadLangs(["admin", "install", "errors"]);

        // Dolibarr pages directory
        $this->main_dir = Functions::GETPOST('main_dir') ? Functions::GETPOST('main_dir') : (empty($argv[3]) ? '' : $argv[3]);
        // Directory for generated documents (invoices, orders, ecm, etc...)
        $this->main_data_dir = Functions::GETPOST('main_data_dir') ? Functions::GETPOST('main_data_dir') : (empty($argv[4]) ? ($this->main_dir . '/documents') : $argv[4]);
        // Dolibarr root URL
        $this->main_url = Functions::GETPOST('main_url') ? Functions::GETPOST('main_url') : (empty($argv[5]) ? '' : $argv[5]);
        // Database login information
        $userroot = Functions::GETPOST('db_user_root', 'alpha') ? Functions::GETPOST('db_user_root', 'alpha') : (empty($argv[6]) ? '' : $argv[6]);
        $passroot = Functions::GETPOST('db_pass_root', 'none') ? Functions::GETPOST('db_pass_root', 'none') : (empty($argv[7]) ? '' : $argv[7]);
        // Database server
        $this->db_type = Functions::GETPOST('db_type', 'aZ09') ? Functions::GETPOST('db_type', 'aZ09') : (empty($argv[8]) ? '' : $argv[8]);
        $this->db_host = Functions::GETPOST('db_host', 'alpha') ? Functions::GETPOST('db_host', 'alpha') : (empty($argv[9]) ? '' : $argv[9]);
        $this->db_name = Functions::GETPOST('db_name', 'aZ09') ? Functions::GETPOST('db_name', 'aZ09') : (empty($argv[10]) ? '' : $argv[10]);
        $this->db_user = Functions::GETPOST('db_user', 'alpha') ? Functions::GETPOST('db_user', 'alpha') : (empty($argv[11]) ? '' : $argv[11]);
        $this->db_pass = Functions::GETPOST('db_pass', 'none') ? Functions::GETPOST('db_pass', 'none') : (empty($argv[12]) ? '' : $argv[12]);
        $this->db_port = Functions::GETPOST('db_port', 'int') ? Functions::GETPOST('db_port', 'int') : (empty($argv[13]) ? '' : $argv[13]);
        $this->db_prefix = Functions::GETPOST('db_prefix', 'aZ09') ? Functions::GETPOST('db_prefix', 'aZ09') : (empty($argv[14]) ? '' : $argv[14]);
        $this->db_create_database = Functions::GETPOST('db_create_database', 'alpha') ? Functions::GETPOST('db_create_database', 'alpha') : (empty($argv[15]) ? '' : $argv[15]);
        $this->db_create_user = Functions::GETPOST('db_create_user', 'alpha') ? Functions::GETPOST('db_create_user', 'alpha') : (empty($argv[16]) ? '' : $argv[16]);
        // Force https
        $this->main_force_https = ((Functions::GETPOST("main_force_https", 'alpha') && (Functions::GETPOST("main_force_https", 'alpha') == "on" || Functions::GETPOST("main_force_https", 'alpha') == 1)) ? '1' : '0');
        // Use alternative directory
        $this->main_use_alt_dir = ((Functions::GETPOST("main_use_alt_dir", 'alpha') == '' || (Functions::GETPOST("main_use_alt_dir", 'alpha') == "on" || Functions::GETPOST("main_use_alt_dir", 'alpha') == 1)) ? '' : '//');
        // Alternative root directory name
        $this->main_alt_dir_name = ((Functions::GETPOST("main_alt_dir_name", 'alpha') && Functions::GETPOST("main_alt_dir_name", 'alpha') != '') ? Functions::GETPOST("main_alt_dir_name", 'alpha') : 'custom');

        $this->dolibarr_main_distrib = 'standard';

        session_start(); // To be able to keep info into session (used for not losing password during navigation. The password must not transit through parameters)

        // Save a flag to tell to restore input value if we go back
        $_SESSION['dol_save_pass'] = $this->db_pass;
        //$_SESSION['dol_save_passroot']=$passroot;

        $conffile = Globals::getConfFilename();

        // Now we load forced values from install.forced.php file.
        /*
         * TODO: It could be implemented using a file to force configuration.
         *
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

        $this->dolibarr_install_syslog("--- step1: entering step1.php page");

        // Test if we can run a first install process
        if (!is_writable($conffile)) {
            $this->template = 'install/step1-error';
            $this->errorMessage = $this->lang->trans("ConfFileIsNotWritable", $conffile);
            return false;
        }

        $errors = [];

        // Check parameters
        $is_sqlite = false;

        if (empty($this->db_type)) {
            $errors[] = $this->lang->trans("ErrorFieldRequired", $this->lang->transnoentities("DatabaseType"));
        } else {
            $is_sqlite = ($this->db_type === 'sqlite' || $this->db_type === 'sqlite3');
        }

        if (empty($this->db_host) && !$is_sqlite) {
            $errors[] = $this->lang->trans("ErrorFieldRequired", $this->lang->transnoentities("Server"));
        }

        if (empty($this->db_name)) {
            $errors[] = $this->lang->trans("ErrorFieldRequired", $this->lang->transnoentities("DatabaseName"));
        }

        if (empty($this->db_user) && !$is_sqlite) {
            $errors[] = $this->lang->trans("ErrorFieldRequired", $this->lang->transnoentities("Login"));
        }

        if (!empty($this->db_port) && !is_numeric($this->db_port)) {
            $errors[] = $this->lang->trans("ErrorBadValueForParameter", $this->db_port, $this->lang->transnoentities("Port"));
        }

        if (!empty($this->db_prefix) && !preg_match('/^[a-z0-9]+_$/i', $this->db_prefix)) {
            $errors[] = $this->lang->trans("ErrorBadValueForParameter", $this->db_prefix, $this->lang->transnoentities("DatabasePrefix"));
        }

        $this->main_dir = Functions::dol_sanitizePathName($this->main_dir);
        $this->main_data_dir = Functions::dol_sanitizePathName($this->main_data_dir);

        if (!filter_var($this->main_url, FILTER_VALIDATE_URL)) {
            $errors[] = $this->lang->trans("ErrorBadValueForParameter", $this->main_url, $this->lang->transnoentitiesnoconv("URLRoot"));
        }

        // Remove last / into dans main_dir
        if (substr($this->main_dir, Functions::dol_strlen($this->main_dir) - 1) == "/") {
            $this->main_dir = substr($this->main_dir, 0, Functions::dol_strlen($this->main_dir) - 1);
        }

        // Remove last / into dans main_url
        if (!empty($this->main_url) && substr($this->main_url, Functions::dol_strlen($this->main_url) - 1) == "/") {
            $this->main_url = substr($this->main_url, 0, Functions::dol_strlen($this->main_url) - 1);
        }

        $enginesDir = realpath(BASE_PATH . '/../Core/DB/Engines') . '/';
        if (!Files::dol_is_dir($enginesDir)) {
            $errors[] = $this->lang->trans("ErrorBadValueForParameter", $this->main_dir, $this->lang->transnoentitiesnoconv("WebPagesDirectory"));
        }

        $this->errors = $errors;
        $error = count($errors) > 0;

        // Test database connection
        if (!$error) {
            // If we require database or user creation we need to connect as root, so we need root login credentials
            if (!empty($this->db_create_database) && !$userroot) {
                print '
        <div class="error">' . $this->lang->trans("YouAskDatabaseCreationSoDolibarrNeedToConnect", $this->db_name) . '</div>
        ';
                print '<br>';
                print $this->lang->trans("BecauseConnectionFailedParametersMayBeWrong") . '<br><br>';
                print $this->lang->trans("ErrorGoBackAndCorrectParameters");
                $error++;
            }
            if (!empty($this->db_create_user) && !$userroot) {
                print '
        <div class="error">' . $this->lang->trans("YouAskLoginCreationSoDolibarrNeedToConnect", $this->db_user) . '</div>
        ';
                print '<br>';
                print $this->lang->trans("BecauseConnectionFailedParametersMayBeWrong") . '<br><br>';
                print $this->lang->trans("ErrorGoBackAndCorrectParameters");
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

                $db = Functions::getDoliDBInstance($this->db_type, $this->db_host, $userroot, $passroot, $databasefortest, (int) $this->db_port);

                Functions::dol_syslog("databasefortest=" . $databasefortest . " connected=" . DB::$connected . " database_selected=" . $db->database_selected, LOG_DEBUG);
                //print "databasefortest=".$databasefortest." connected=".DB::$connected." database_selected=".$db->database_selected;

                if (empty($this->db_create_database) && DB::$connected && !$db->database_selected) {
                    print '
        <div class="error">' . $this->lang->trans("ErrorConnectedButDatabaseNotFound", $this->db_name) . '</div>
        ';
                    print '<br>';
                    if (!DB::$connected) {
                        print $this->lang->trans("IfDatabaseNotExistsGoBackAndUncheckCreate") . '<br><br>';
                    }
                    print $this->lang->trans("ErrorGoBackAndCorrectParameters");
                    $error++;
                } elseif ($db->error && !(!empty($this->db_create_database) && DB::$connected)) {
                    // Note: you may experience error here with message "No such file or directory" when mysql was installed for the first time but not yet launched.
                    if ($db->error == "No such file or directory") {
                        print '
        <div class="error">' . $this->lang->trans("ErrorToConnectToMysqlCheckInstance") . '</div>
        ';
                    } else {
                        print '
        <div class="error">' . $db->error . '</div>
        ';
                    }
                    if (!DB::$connected) {
                        print $this->lang->trans("BecauseConnectionFailedParametersMayBeWrong") . '<br><br>';
                    }
                    //print '<a href="#" onClick="javascript: history.back();">';
                    print $this->lang->trans("ErrorGoBackAndCorrectParameters");
                    //print '</a>';
                    $error++;
                }
            }

            // If we need simple access
            if (!$error && (empty($this->db_create_database) && empty($this->db_create_user))) {
                $db = Functions::getDoliDBInstance($this->db_type, $this->db_host, $this->db_user, $this->db_pass, $this->db_name, (int) $this->db_port);

                if ($db->error) {
                    print '
        <div class="error">' . $db->error . '</div>
        ';
                    if (!DB::$connected) {
                        print $this->lang->trans("BecauseConnectionFailedParametersMayBeWrong") . '<br><br>';
                    }
                    //print '<a href="#" onClick="javascript: history.back();">';
                    print $this->lang->trans("ErrorGoBackAndCorrectParameters");
                    //print '</a>';
                    $error++;
                }
            }
        } else {
            if (isset($db)) {
                print $db->lasterror();
            }
            if (isset($db) && !DB::$connected) {
                print '<br>' . $this->lang->trans("BecauseConnectionFailedParametersMayBeWrong") . '<br><br>';
            }
            print $this->lang->trans("ErrorGoBackAndCorrectParameters");
            $error++;
        }

        if (!$error && DB::$connected) {
            if (!empty($this->db_create_database)) {
                $result = $db->select_db($this->db_name);
                if ($result) {
                    print '<div class="error">' . $this->lang->trans("ErrorDatabaseAlreadyExists", $this->db_name) . '</div>';
                    print $this->lang->trans("IfDatabaseExistsGoBackAndCheckCreate") . '<br><br>';
                    print $this->lang->trans("ErrorGoBackAndCorrectParameters");
                    $error++;
                }
            }
        }

        // Define $defaultCharacterSet and $defaultDBSortingCollation
        if (!$error && DB::$connected) {
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
            $this->dolibarr_install_syslog("step1: db_character_set=" . $this->db_character_set . " db_collation=" . $this->db_collation);
        }

        // Create config file
        if (!$error && DB::$connected) {
            umask(0);
            if (is_array($_POST)) {
                foreach ($_POST as $key => $value) {
                    if (!preg_match('/^db_pass/i', $key)) {
                        $this->dolibarr_install_syslog("step1: choice for " . $key . " = " . $value);
                    }
                }
            }

            // Show title of step
            print '<h3><img class="valignmiddle inline-block paddingright" src="Resources/img/gear.svg" width="20" alt="Configuration"> ' . $this->lang->trans("ConfigurationFile") . '</h3>';
            print '<table cellspacing="0" width="100%" cellpadding="1" border="0">';

            // Check parameter main_dir
            if (!$error) {
                if (!is_dir($this->main_dir)) {
                    $this->dolibarr_install_syslog("step1: directory '" . $this->main_dir . "' is unavailable or can't be accessed");

                    print "<tr><td>";
                    print $this->lang->trans("ErrorDirDoesNotExists", $this->main_dir) . '<br>';
                    print $this->lang->trans("ErrorWrongValueForParameter", $this->lang->transnoentitiesnoconv("WebPagesDirectory")) . '<br>';
                    print $this->lang->trans("ErrorGoBackAndCorrectParameters") . '<br><br>';
                    print '</td><td>';
                    print $this->lang->trans("Error");
                    print "</td></tr>";
                    $error++;
                }
            }

            if (!$error) {
                $this->dolibarr_install_syslog("step1: directory '" . $this->main_dir . "' exists");
            }


            // Create subdirectory main_data_dir
            if (!$error) {
                // Create directory for documents
                if (!is_dir($this->main_data_dir)) {
                    Functions::dol_mkdir($this->main_data_dir);
                }

                if (!is_dir($this->main_data_dir)) {
                    print "<tr><td>" . $this->lang->trans("ErrorDirDoesNotExists", $this->main_data_dir);
                    print ' ' . $this->lang->trans("YouMustCreateItAndAllowServerToWrite");
                    print '</td><td>';
                    print '<span class="error">' . $this->lang->trans("Error") . '</span>';
                    print "</td></tr>";
                    print '<tr><td colspan="2"><br>' . $this->lang->trans("CorrectProblemAndReloadPage", $_SERVER['PHP_SELF'] . '?testget=ok') . '</td></tr>';
                    $error++;
                } else {
                    // Create .htaccess file in document directory
                    $pathhtaccess = $this->main_data_dir . '/.htaccess';
                    if (!file_exists($pathhtaccess)) {
                        $this->dolibarr_install_syslog("step1: .htaccess file did not exist, we created it in '" . $this->main_data_dir . "'");
                        $handlehtaccess = @fopen($pathhtaccess, 'w');
                        if ($handlehtaccess) {
                            fwrite($handlehtaccess, 'Order allow,deny' . "\n");
                            fwrite($handlehtaccess, 'Deny from all' . "\n");

                            fclose($handlehtaccess);
                            $this->dolibarr_install_syslog("step1: .htaccess file created");
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
                            $this->dolibarr_install_syslog("step1: directory '" . $dir[$i] . "' exists");
                        } else {
                            if (Functions::dol_mkdir($dir[$i]) < 0) {
                                $this->errors[] = $this->lang->trans('ErrorFailToCreateDir', $dir[$i]);
                            } else {
                                $this->dolibarr_install_syslog("step1: directory '" . $dir[$i] . "' created");
                            }
                        }
                    }

                    // Copy directory medias
                    $srcroot = $this->main_dir . '/install/medias';
                    $destroot = $this->main_data_dir . '/medias';
                    Files::dolCopyDir($srcroot, $destroot, 0, 0);

                    if ($error) {
                        print "<tr><td>" . $this->lang->trans("ErrorDirDoesNotExists", $this->main_data_dir);
                        print ' ' . $this->lang->trans("YouMustCreateItAndAllowServerToWrite");
                        print '</td><td>';
                        print '<span class="error">' . $this->lang->trans("Error") . '</span>';
                        print "</td></tr>";
                        print '<tr><td colspan="2"><br>' . $this->lang->trans("CorrectProblemAndReloadPage", $_SERVER['PHP_SELF'] . '?testget=ok') . '</td></tr>';
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

                            Functions::dol_mkdir($dirodt);
                            $result = Files::dol_copy($src, $dest, 0, 0);
                            if ($result < 0) {
                                $this->errors[] = $this->lang->trans('ErrorFailToCopyFile', $src, $dest);
                            }
                        }
                    }
                }
            }

            // Table prefix
            $this->db_prefix = (!empty($this->db_prefix) ? $this->db_prefix : 'llx_');

            // Write conf file on disk
            if (!$error) {
                // Save old conf file on disk
                if (file_exists("$conffile")) {
                    // We must ignore errors as an existing old file may already exist and not be replaceable or
                    // the installer (like for ubuntu) may not have permission to create another file than conf.php.
                    // Also no other process must be able to read file or we expose the new file, so content with password.
                    @Files::dol_copy($conffile, $conffile . '.old', '0400');
                }

                $configFileError = $this->write_conf_file($conffile);
                if ($configFileError === 0) {
                    include $conffile; // force config reload, do not put include_once
                    $this->conf($this->main_dir);

                    print "<tr><td>";
                    print $this->lang->trans("SaveConfigurationFile");
                    print ' <strong>' . $conffile . '</strong>';
                    print "</td><td>";
                    print '<img src="Resources/img/ok.png" alt="Ok">';
                    print "</td></tr>";
                }

                $error += $configFileError;
            }

            // Create database and admin user database
            if (!$error) {
                // We reload configuration file
                $this->conf();

                print '<tr><td>';
                print $this->lang->trans("ConfFileReload");
                print '</td>';
                print '<td><img src="Resources/img/ok.png" alt="Ok"></td></tr>';

                // Create database user if requested
                if (isset($this->db_create_user) && ($this->db_create_user == "1" || $this->db_create_user == "on")) {
                    $this->dolibarr_install_syslog("step1: create database user: " . $this->config->main_db_user);

                    //print $this->db_host." , ".$this->db_name." , ".$this->db_user." , ".$this->db_port;
                    $databasefortest = $this->db_name;
                    if ($this->db_type == 'mysql' || $this->db_type == 'mysqli') {
                        $databasefortest = 'mysql';
                    } elseif ($this->db_type == 'pgsql') {
                        $databasefortest = 'postgres';
                    } elseif ($this->db_type == 'mssql') {
                        $databasefortest = 'master';
                    }

                    // Check database connection

                    $db = Functions::getDoliDBInstance($this->db_type, $this->db_host, $userroot, $passroot, $databasefortest, (int) $this->db_port);

                    if ($db->error) {
                        print '<div class="error">' . $db->error . '</div>';
                        $error++;
                    }

                    if (!$error) {
                        if (DB::$connected) {
                            $resultbis = 1;

                            if (empty($dolibarr_main_db_pass)) {
                                $this->dolibarr_install_syslog("step1: failed to create user, password is empty", LOG_ERR);
                                print '<tr><td>';
                                print $this->lang->trans("UserCreation") . ' : ';
                                print $dolibarr_main_db_user;
                                print '</td>';
                                print '<td>' . $this->lang->trans("Error") . ": A password for database user is mandatory.</td></tr>";
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
                                    print $this->lang->trans("UserCreation") . ' : ';
                                    print $dolibarr_main_db_user;
                                    print '</td>';
                                    print '<td><img src="Resources/img/ok.png" alt="Ok"></td></tr>';
                                } else {
                                    if (
                                        $db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS'
                                        || $db->errno() == 'DB_ERROR_KEY_NAME_ALREADY_EXISTS'
                                        || $db->errno() == 'DB_ERROR_USER_ALREADY_EXISTS'
                                    ) {
                                        $this->dolibarr_install_syslog("step1: user already exists");
                                        print '<tr><td>';
                                        print $this->lang->trans("UserCreation") . ' : ';
                                        print $dolibarr_main_db_user;
                                        print '</td>';
                                        print '<td>' . $this->lang->trans("LoginAlreadyExists") . '</td></tr>';
                                    } else {
                                        $this->dolibarr_install_syslog("step1: failed to create user", LOG_ERR);
                                        print '<tr><td>';
                                        print $this->lang->trans("UserCreation") . ' : ';
                                        print $dolibarr_main_db_user;
                                        print '</td>';
                                        print '<td>' . $this->lang->trans("Error") . ': ' . $db->errno() . ' ' . $db->error() . ($db->error ? '. ' . $db->error : '') . "</td></tr>";
                                    }
                                }
                            }

                            dd(['1 $db->close()' => $db]);
                            $db->close();
                        } else {
                            print '<tr><td>';
                            print $this->lang->trans("UserCreation") . ' : ';
                            print $dolibarr_main_db_user;
                            print '</td>';
                            print '<td><img src="Resources/img/error.png" alt="Error"></td>';
                            print '</tr>';

                            // warning message due to connection failure
                            print '<tr><td colspan="2"><br>';
                            print $this->lang->trans("YouAskDatabaseCreationSoDolibarrNeedToConnect", $dolibarr_main_db_user, $dolibarr_main_db_host, $userroot);
                            print '<br>';
                            print $this->lang->trans("BecauseConnectionFailedParametersMayBeWrong") . '<br><br>';
                            print $this->lang->trans("ErrorGoBackAndCorrectParameters") . '<br><br>';
                            print '</td></tr>';

                            $error++;
                        }
                    }
                }   // end of user account creation

                // If database creation was asked, we create it
                if (!$error && (isset($this->db_create_database) && ($this->db_create_database == "1" || $this->db_create_database == "on"))) {
                    $this->dolibarr_install_syslog("step1: create database: " . $dolibarr_main_db_name . " " . $dolibarr_main_db_character_set . " " . $dolibarr_main_db_collation . " " . $dolibarr_main_db_user);
                    $newdb = Functions::getDoliDBInstance($this->db_type, $this->db_host, $userroot, $passroot, '', (int) $this->db_port);
                    //print 'eee'.$this->db_type." ".$this->db_host." ".$userroot." ".$passroot." ".$this->db_port." ".$newdb->connected." ".$newdb->forcecharset;exit;

                    if ($newdb->connected()) {
                        if ($newdb->DDLCreateDb($dolibarr_main_db_name, $dolibarr_main_db_character_set, $dolibarr_main_db_collation, $dolibarr_main_db_user)) {
                            print '<tr><td>';
                            print $this->lang->trans("DatabaseCreation") . " (" . $this->lang->trans("User") . " " . $userroot . ") : ";
                            print $dolibarr_main_db_name;
                            print '</td>';
                            print '<td><img src="Resources/img/ok.png" alt="Ok"></td></tr>';

                            $newdb->select_db($dolibarr_main_db_name);
                            $check1 = $newdb->getDefaultCharacterSetDatabase();
                            $check2 = $newdb->getDefaultCollationDatabase();
                            $this->dolibarr_install_syslog('step1: new database is using charset=' . $check1 . ' collation=' . $check2);

                            // If values differs, we save conf file again
                            //if ($check1 != $dolibarr_main_db_character_set) $this->dolibarr_install_syslog('step1: value for character_set is not the one asked for database creation', LOG_WARNING);
                            //if ($check2 != $dolibarr_main_db_collation)     $this->dolibarr_install_syslog('step1: value for collation is not the one asked for database creation', LOG_WARNING);
                        } else {
                            // warning message
                            print '<tr><td colspan="2"><br>';
                            print $this->lang->trans("ErrorFailedToCreateDatabase", $dolibarr_main_db_name) . '<br>';
                            print $newdb->lasterror() . '<br>';
                            print $this->lang->trans("IfDatabaseExistsGoBackAndCheckCreate");
                            print '<br>';
                            print '</td></tr>';

                            $this->dolibarr_install_syslog('step1: failed to create database ' . $dolibarr_main_db_name . ' ' . $newdb->lasterrno() . ' ' . $newdb->lasterror(), LOG_ERR);
                            $error++;
                        }

                        $newdb = null;
                        DB::disconnect();
                    } else {
                        print '<tr><td>';
                        print $this->lang->trans("DatabaseCreation") . " (" . $this->lang->trans("User") . " " . $userroot . ") : ";
                        print $dolibarr_main_db_name;
                        print '</td>';
                        print '<td><img src="Resources/img/error.png" alt="Error"></td>';
                        print '</tr>';

                        // warning message
                        print '<tr><td colspan="2"><br>';
                        print $this->lang->trans("YouAskDatabaseCreationSoDolibarrNeedToConnect", $dolibarr_main_db_user, $dolibarr_main_db_host, $userroot);
                        print '<br>';
                        print $this->lang->trans("BecauseConnectionFailedParametersMayBeWrong") . '<br><br>';
                        print $this->lang->trans("ErrorGoBackAndCorrectParameters") . '<br><br>';
                        print '</td></tr>';

                        $error++;
                    }
                }   // end of create database

                // We test access with dolibarr database user (not admin)
                if (!$error) {
                    $this->dolibarr_install_syslog("step1: connection type=" . $this->db_type . " on host=" . $this->db_host . " port=" . $this->db_port . " user=" . $this->db_user . " name=" . $this->db_name);
                    //print "connection de type=".$this->db_type." sur host=".$this->db_host." port=".$this->db_port." user=".$this->db_user." name=".$this->db_name;

                    DB::disconnect();
                    $db = Functions::getDoliDBInstance(
                        $this->db_type,
                        $this->db_host,
                        $this->db_user,
                        $this->db_pass,
                        $this->db_name,
                        (int) $this->db_port
                    );

                    if (DB::$connected) {
                        $this->dolibarr_install_syslog("step1: connection to server by user " . $this->db_user . " ok");
                        print "<tr><td>";
                        print $this->lang->trans("ServerConnection") . " (" . $this->lang->trans("User") . " " . $this->db_user . ") : ";
                        print $this->db_host;
                        print "</td><td>";
                        print '<img src="Resources/img/ok.png" alt="Ok">';
                        print "</td></tr>";

                        // server access ok, basic access ok
                        if ($db->database_selected) {
                            $this->dolibarr_install_syslog("step1: connection to database " . $this->db_name . " by user " . $this->db_user . " ok");
                            print "<tr><td>";
                            print $this->lang->trans("DatabaseConnection") . " (" . $this->lang->trans("User") . " " . $this->db_user . ") : ";
                            print $this->db_name;
                            print "</td><td>";
                            print '<img src="Resources/img/ok.png" alt="Ok">';
                            print "</td></tr>";

                            $error = 0;
                        } else {
                            $this->dolibarr_install_syslog("step1: connection to database " . $this->db_name . " by user " . $this->db_user . " failed", LOG_ERR);
                            print "<tr><td>";
                            print $this->lang->trans("DatabaseConnection") . " (" . $this->lang->trans("User") . " " . $this->db_user . ") : ";
                            print $this->db_name;
                            print '</td><td>';
                            print '<img src="Resources/img/error.png" alt="Error">';
                            print "</td></tr>";

                            // warning message
                            print '<tr><td colspan="2"><br>';
                            print $this->lang->trans('CheckThatDatabasenameIsCorrect', $this->config->main_db_name) . '<br>';
                            print $this->lang->trans('IfAlreadyExistsCheckOption') . '<br>';
                            print $this->lang->trans("ErrorGoBackAndCorrectParameters") . '<br><br>';
                            print '</td></tr>';

                            $error++;
                        }
                    } else {
                        $this->dolibarr_install_syslog("step1: connection to server by user " . $this->db_user . " failed", LOG_ERR);
                        print "<tr><td>";
                        print $this->lang->trans("ServerConnection") . " (" . $this->lang->trans("User") . " " . $this->db_user . ") : ";
                        print $this->db_host;
                        print '</td><td>';
                        print '<img src="Resources/img/error.png" alt="Error">';
                        print "</td></tr>";

                        // warning message
                        print '<tr><td colspan="2"><br>';
                        print $this->lang->trans("ErrorConnection", $this->db_host, $this->db_name, $this->db_user);
                        print $this->lang->trans('IfLoginDoesNotExistsCheckCreateUser') . '<br>';
                        print $this->lang->trans("ErrorGoBackAndCorrectParameters") . '<br><br>';
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
        $this->dolibarr_install_syslog("Exit " . $ret);

        $this->dolibarr_install_syslog("--- step1: end");

// Return code if ran from command line
        if ($ret) {
            exit($ret);
        }


        return true;
    }

    public function actionStep2()
    {
        $step = 2;
        $ok = 0;
        $ok = 0;

        $this->template = 'install/step2';
        $this->subtitle = $this->lang->trans("CreateDatabaseObjects");
        $this->nextButton = true;

        $this->errors = [];

// This page can be long. We increase the time allowed. / Cette page peut etre longue. On augmente le delai autorise.
// Only works if you are not in safe_mode. / Ne fonctionne que si on est pas en safe_mode.

        $err = error_reporting();
        error_reporting(0);      // Disable all errors
//error_reporting(E_ALL);
        @set_time_limit(1800);   // Need 1800 on some very slow OS like Windows 7/64
        error_reporting($err);

        $action = Functions::GETPOST('action', 'aZ09') ? Functions::GETPOST('action', 'aZ09') : (empty($argv[1]) ? '' : $argv[1]);
        $setuplang = Functions::GETPOST('selectlang', 'aZ09', 3) ? Functions::GETPOST('selectlang', 'aZ09', 3) : (empty($argv[2]) ? 'auto' : $argv[2]);

        $conffile = Globals::getConfFilename();
        $conf = Globals::getConfig();
        $this->conf = Globals::getConf();
        $db = Globals::getDb();

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
//if (empty($choix)) Functions::dol_print_error(null,'Database type '.$conf->main_db_type.' not supported into step2.php page');


// Now we load forced values from install.forced.php file.

        $useforcedwizard = false;
        /*
         * TODO: It could be implemented using a file to force configuration.
         *
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

        $this->dolibarr_install_syslog("--- step2: entering step2.php page");

        // Test if we can run a first install process
        $this->fatalError = false;
        if (!is_writable($conffile)) {
            $this->fatalError = $this->lang->trans("ConfFileIsNotWritable", $conffile);
            exit;
        }

        if ($action == "set" || true) {
            $error = 0;

            //$db = Functions::getDoliDBInstance($conf->main_db_type, $conf->main_db_host, $conf->main_db_user, $conf->main_db_pass, $conf->main_db_name, (int) $conf->main_db_port);

            if (DB::$connected) {
                $this->connectionMessage = $this->lang->trans("ServerConnection") . ": " . $conf->main_db_host;
                $this->connectionResult = '<img src="Resources/img/ok.png" alt="Ok">';
                $ok = 1;
            } else {
                $this->connectionMessage = 'Failed to connect to server: ' . $conf->main_db_host;
                $this->connectionResult = '<img src="Resources/img/error.png" alt="Error">';
            }

            if ($ok) {
                if ($db->database_selected) {
                    $this->dolibarr_install_syslog("step2: successful connection to database: " . $conf->main_db_name);
                } else {
                    $this->dolibarr_install_syslog("step2: failed connection to database :" . $conf->main_db_name, LOG_ERR);
                    $this->errors[] = [
                        'text' => 'Failed to select database '.$conf->main_db_name,
                        'icon'=>'<img src="Resources/img/error.png" alt="Error">',
                    ];
                }
            }

            // Display version / Affiche version
            if ($ok) {
                $version = $db->getVersion();
                $versionarray = $db->getVersionArray();

                $this->databaseVersionMessage = $this->lang->trans("DatabaseVersion");
                $this->databaseVersionVersion = $version;

                $this->databaseNameMessage = $this->lang->trans("DatabaseName");
                $this->databaseNameName = $db->database_name;
            }

            $requestnb = 0;

            // To disable some code, so you can call step2 with url like
            // http://localhost/dolibarrnew/install/step2.php?action=set&token='.newToken().'&createtables=0&createkeys=0&createfunctions=0&createdata=llx_20_c_departements
            $createtables = Functions::GETPOSTISSET('createtables') ? Functions::GETPOST('createtables') : 1;
            $createkeys = Functions::GETPOSTISSET('createkeys') ? Functions::GETPOST('createkeys') : 1;
            $createfunctions = Functions::GETPOSTISSET('createfunctions') ? Functions::GETPOST('createfunction') : 1;
            $createdata = Functions::GETPOSTISSET('createdata') ? Functions::GETPOST('createdata') : 1;


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
                $dir = static::getDataDir('mysql/tables');

                $ok = 0;
                $handle = opendir($dir);
                $this->dolibarr_install_syslog("step2: open tables directory " . $dir . " handle=" . $handle);
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
                    $name = substr($file, 0, Functions::dol_strlen($file) - 4);
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
                        if ($conf->main_db_type == 'mysql' || $conf->main_db_type == 'mysqli') {    // For Mysql 5.5+, we must replace type=innodb with ENGINE=innodb
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

                        $this->dolibarr_install_syslog("step2: request: " . $buffer);
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
                                $this->errors[] = [
                                    'text' => $this->lang->trans("CreateTableAndPrimaryKey", $name) .'<br>'.$this->lang->trans("Request") . ' ' . $requestnb . ': ' . $buffer . ' <br>Executed query : ' . $db->lastquery,
                                    'icon'=>'<span class="error">' . $this->lang->trans("ErrorSQL") . " " . $db->errno() . " " . $db->error() . '</span>',
                                ];
                                $error++;
                            }
                        }
                    } else {
                        $this->errors[] = [
                            'text' =>$this->lang->trans("CreateTableAndPrimaryKey", $name),
                            'icon'=>'<span class="error">' . $this->lang->trans("Error") . ' Failed to open file ' . $dir . $file . '</span>',
                        ];
                        $error++;
                        $this->dolibarr_install_syslog("step2: failed to open file " . $dir . $file, LOG_ERR);
                    }
                }

                if ($tablefound) {
                    if ($error == 0) {
                        $this->errors[] = [
                            'text' =>$this->lang->trans("TablesAndPrimaryKeysCreation"),
                            'icon'=>'<img src="Resources/img/ok.png" alt="Ok">',
                        ];
                        $ok = 1;
                    }
                } else {
                    $this->errors[] = [
                        'text' =>$this->lang->trans("ErrorFailedToFindSomeFiles", $dir),
                        'icon'=>'<img src="Resources/img/error.png" alt="Error">',
                    ];
                    $this->dolibarr_install_syslog("step2: failed to find files to create database in directory " . $dir, LOG_ERR);
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
                $dir = static::getDataDir('mysql/tables');

                $okkeys = 0;
                $handle = opendir($dir);
                $this->dolibarr_install_syslog("step2: open keys directory " . $dir . " handle=" . $handle);
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
                    $name = substr($file, 0, Functions::dol_strlen($file) - 4);
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
                                    && Admin::versioncompare($versioncommande, $versionarray) <= 0
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

                                $this->dolibarr_install_syslog("step2: request: " . $buffer);
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
                                        print "<tr><td>" . $this->lang->trans("CreateOtherKeysForTable", $name);
                                        print "<br>\n" . $this->lang->trans("Request") . ' ' . $requestnb . ' : ' . $db->lastqueryerror();
                                        print "\n</td>";
                                        print '<td><span class="error">' . $this->lang->trans("ErrorSQL") . " " . $db->errno() . " " . $db->error() . '</span></td></tr>';
                                        $error++;
                                    }
                                }
                            }
                        }
                    } else {
                        print "<tr><td>" . $this->lang->trans("CreateOtherKeysForTable", $name);
                        print "</td>";
                        print '<td><span class="error">' . $this->lang->trans("Error") . " Failed to open file " . $dir . $file . "</span></td></tr>";
                        $error++;
                        $this->dolibarr_install_syslog("step2: failed to open file " . $dir . $file, LOG_ERR);
                    }
                }

                if ($tablefound && $error == 0) {
                    print '<tr><td>';
                    print $this->lang->trans("OtherKeysCreation") . '</td><td><img src="../theme/eldy/img/tick.png" alt="Ok"></td></tr>';
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
                $dir = static::getDataDir($dir);

                // Creation of data
                $file = "functions.sql";
                if (file_exists($dir . $file)) {
                    $fp = fopen($dir . $file, "r");
                    $this->dolibarr_install_syslog("step2: open function file " . $dir . $file . " handle=" . $fp);
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
                            $this->dolibarr_install_syslog("step2: request: " . $buffer);
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

                                    print "<tr><td>" . $this->lang->trans("FunctionsCreation");
                                    print "<br>\n" . $this->lang->trans("Request") . ' ' . $requestnb . ' : ' . $buffer;
                                    print "\n</td>";
                                    print '<td><span class="error">' . $this->lang->trans("ErrorSQL") . " " . $db->errno() . " " . $db->error() . '</span></td></tr>';
                                    $error++;
                                }
                            }
                        }
                    }

                    print "<tr><td>" . $this->lang->trans("FunctionsCreation") . "</td>";
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
                $dir = static::getDataDir("mysql/data/");

                // Insert data
                $handle = opendir($dir);
                $this->dolibarr_install_syslog("step2: open directory data " . $dir . " handle=" . $handle);
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
                    $name = substr($file, 0, Functions::dol_strlen($file) - 4);
                    $fp = fopen($dir . $file, "r");
                    $this->dolibarr_install_syslog("step2: open data file " . $dir . $file . " handle=" . $fp);
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

                        $this->dolibarr_install_syslog("step2: found " . $linefound . " records, defined " . count($arrayofrequests) . " group(s).");

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
                                    print '<span class="error">' . $this->lang->trans("ErrorSQL") . " : " . $db->lasterrno() . " - " . $db->lastqueryerror() . " - " . $db->lasterror() . "</span><br>";
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

                print "<tr><td>" . $this->lang->trans("ReferenceDataLoading") . "</td>";
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
        $this->dolibarr_install_syslog("Exit " . $ret);

        $this->dolibarr_install_syslog("- step2: end");

// Force here a value we need after because master.inc.php is not loaded into step2.
// This code must be similar with the one into main.inc.php

        $this->conf->file->instance_unique_id = (empty($conf->main_instance_unique_id) ? (empty($dolibarr_main_cookie_cryptkey) ? '' : $dolibarr_main_cookie_cryptkey) : $conf->main_instance_unique_id); // Unique id of instance

        $hash_unique_id = Security::dol_hash('dolibarr' . $this->conf->file->instance_unique_id, 'sha256');   // Note: if the global salt changes, this hash changes too so ping may be counted twice. We don't mind. It is for statistics purpose only.

        $out = '<input type="checkbox" name="dolibarrpingno" id="dolibarrpingno"' . ((Functions::getDolGlobalString('MAIN_FIRST_PING_OK_ID') == 'disabled') ? '' : ' value="checked"') . '> ';
        $out .= '<label for="dolibarrpingno">' . $this->lang->trans("MakeAnonymousPing") . '</label>';

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

    public function actionStep4()
    {
        $setuplang = Functions::GETPOST('selectlang', 'aZ09', 3) ? Functions::GETPOST('selectlang', 'aZ09', 3) : (empty($argv[1]) ? 'auto' : $argv[1]);
        $this->lang->setDefaultLang($setuplang);

        $this->lang->loadLangs(["admin", "install"]);

        $conffile = Globals::getConfFilename();

        // Now we load forced value from install.forced.php file.
        $useforcedwizard = false;
        /*
        $forcedfile = "./install.forced.php";
        if ($conffile == "/etc/dolibarr/conf.php") {
            $forcedfile = "/etc/dolibarr/install.forced.php";
        }
        if (@file_exists($forcedfile)) {
            $useforcedwizard = true;
            include_once $forcedfile;
        }
        */

        $this->dolibarr_install_syslog("--- step4: entering step4.php page");

        $error = 0;
        $ok = 0;

        $this->template = 'install/step4';
        $this->subtitle = $this->lang->trans("AdminAccountCreation");
        $this->nextButton = true;

        // Test if we can run a first install process
        if (!is_writable($conffile)) {
            print $this->lang->trans("ConfFileIsNotWritable", $conffiletoshow);
            //pFooter(1, $setuplang, 'jscheckparam');
            exit;
        }

        print '<h3><img class="valignmiddle inline-block paddingright" src="../theme/common/octicons/build/svg/key.svg" width="20" alt="Database"> ' . $this->lang->trans("DolibarrAdminLogin") . '</h3>';


        //$db = getDoliDBInstance($this->db_type, $this->db_host, $this->db_user, $this->db_pass, $this->db_name, (int) $this->db_port);
        $db = Globals::getDb();

        $this->login_value = (Functions::GETPOSTISSET(" login")
                ? Functions::GETPOST("login", 'alpha')
                : (isset($force_install_dolibarrlogin) ?
                    $force_install_dolibarrlogin : '')) . '"' . (@$force_install_noedit == 2 &&
            $force_install_dolibarrlogin !== null ? ' disabled' : '');

        if (isset($_GET["error"]) && $_GET["error"] == 1) {
            print '<br>';
            print '<div class="error">' . $this->lang->trans("PasswordsMismatch") . '</div>';
            $error = 0; // We show button
        }

        if (isset($_GET["error"]) && $_GET["error"] == 2) {
            print '<br>';
            print '<div class="error">';
            print $this->lang->trans("PleaseTypePassword");
            print '</div>';
            $error = 0; // We show button
        }

        if (isset($_GET["error"]) && $_GET["error"] == 3) {
            print '<br>';
            print '<div class="error">' . $this->lang->trans("PleaseTypeALogin") . '</div>';
            $error = 0; // We show button
        }

        $ret = 0;
        if ($error && isset($argv[1])) {
            $ret = 1;
        }
        $this->dolibarr_install_syslog("Exit " . $ret);

        $this->dolibarr_install_syslog("--- step4: end");

        //pFooter($error, $setuplang);

        DB::disconnect();

// Return code if ran from command line
        if ($ret) {
            exit($ret);
        }

        return true;
    }

    public function actionStep5()
    {
        $conf = Globals::getConf();
        $config = Globals::getConfig();

        define('ALLOWED_IF_UPGRADE_UNLOCK_FOUND', 1);

        $versionfrom = Functions::GETPOST("versionfrom", 'alpha', 3) ? Functions::GETPOST("versionfrom", 'alpha', 3) : (empty($argv[1]) ? '' : $argv[1]);
        $versionto = Functions::GETPOST("versionto", 'alpha', 3) ? Functions::GETPOST("versionto", 'alpha', 3) : (empty($argv[2]) ? '' : $argv[2]);
        $setuplang = Functions::GETPOST('selectlang', 'aZ09', 3) ? Functions::GETPOST('selectlang', 'aZ09', 3) : (empty($argv[3]) ? 'auto' : $argv[3]);
        $this->lang->setDefaultLang($setuplang);
        $action = Functions::GETPOST('action', 'alpha') ? Functions::GETPOST('action', 'alpha') : (empty($argv[4]) ? '' : $argv[4]);

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

        $this->lang->loadLangs(["admin", "install"]);

        $login = Functions::GETPOST('login', 'alpha') ? Functions::GETPOST('login', 'alpha') : (empty($argv[5]) ? '' : $argv[5]);
        $pass = Functions::GETPOST('pass', 'alpha') ? Functions::GETPOST('pass', 'alpha') : (empty($argv[6]) ? '' : $argv[6]);
        $pass_verif = Functions::GETPOST('pass_verif', 'alpha') ? Functions::GETPOST('pass_verif', 'alpha') : (empty($argv[7]) ? '' : $argv[7]);
        $force_install_lockinstall = (int) (!empty($force_install_lockinstall) ? $force_install_lockinstall : (Functions::GETPOST('installlock', 'aZ09') ? Functions::GETPOST('installlock', 'aZ09') : (empty($argv[8]) ? '' : $argv[8])));

        $success = 0;

        $conffile = Globals::getConfFilename();

        $useforcedwizard = false;
        /*
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
        */

        $this->dolibarr_install_syslog("--- step5: entering step5.php page " . $versionfrom . " " . $versionto);

        $error = 0;

        /*
         *	Actions
         */


        // TODO: Check this!
        $action = 'set';

        // If install, check password and password_verification used to create admin account
        // TODO: Pending check correct password
        /*
        if ($action == "set") {
            if ($pass != $pass_verif) {
                header("Location: step4.php?error=1&selectlang=$setuplang" . (isset($login) ? '&login=' . $login : ''));
                exit;
            }

            if (Functions::dol_strlen(trim($pass)) == 0) {
                header("Location: step4.php?error=2&selectlang=$setuplang" . (isset($login) ? '&login=' . $login : ''));
                exit;
            }

            if (Functions::dol_strlen(trim($login)) == 0) {
                header("Location: step4.php?error=3&selectlang=$setuplang" . (isset($login) ? '&login=' . $login : ''));
                exit;
            }
        }
        */

        $this->template = 'install/step5';
        $this->subtitle = $this->lang->trans("SetupEnd");
        $this->nextButton = true;

        // Test if we can run a first install process
        if (empty($versionfrom) && empty($versionto) && !is_writable($conffile)) {
            print $this->lang->trans("ConfFileIsNotWritable", $conffiletoshow);
            //pFooter(1, $setuplang, 'jscheckparam');
            exit;
        }

        if ($action == "set" || empty($action) || preg_match('/upgrade/i', $action)) {
            $error = 0;

            // If password is encoded, we decode it
            if ((!empty($dolibarr_main_db_pass) && preg_match('/crypted:/i', $dolibarr_main_db_pass)) || !empty($dolibarr_main_db_encrypted_pass)) {
                require_once BASE_PATH . '/core/lib/security.lib.php';
                if (!empty($dolibarr_main_db_pass) && preg_match('/crypted:/i', $dolibarr_main_db_pass)) {
                    $dolibarr_main_db_pass = preg_replace('/crypted:/i', '', $dolibarr_main_db_pass);
                    $dolibarr_main_db_pass = dol_decode($dolibarr_main_db_pass);
                    $dolibarr_main_db_encrypted_pass = $dolibarr_main_db_pass; // We need to set this as it is used to know the password was initially encrypted
                } else {
                    $dolibarr_main_db_pass = dol_decode($dolibarr_main_db_encrypted_pass);
                }
            }

            /*
            $this->db_type = $dolibarr_main_db_type;
            $this->db_host = $dolibarr_main_db_host;
            $this->db_port = $dolibarr_main_db_port;
            $this->db_name = $dolibarr_main_db_name;
            $this->db_user = $dolibarr_main_db_user;
            $this->db_pass = $dolibarr_main_db_pass;
            $this->db_dolibarr_main_db_encryption = isset($dolibarr_main_db_encryption) ? $dolibarr_main_db_encryption : '';
            $this->db_dolibarr_main_db_cryptkey = isset($dolibarr_main_db_cryptkey) ? $dolibarr_main_db_cryptkey : '';

            $db = getDoliDBInstance($this->db_type, $this->db_host, $this->db_user, $this->db_pass, $this->db_name, (int) $this->db_port);
            */
            $db = Globals::getDb();

            // Create the global $hookmanager object
            // include_once BASE_PATH . '/core/class/hookmanager.class.php';
            $hookmanager = new HookManager($db);

            $ok = 0;

            // If first install
            if ($action == "set") {
                // Active module user

                $this->dolibarr_install_syslog('step5: load module user ' . BASE_PATH . 'Modules/UserModule.php', LOG_INFO);
                $objMod = new UserModule($db);
                $result = $objMod->init();
                if (!$result) {
                    print "ERROR: failed to init module file = " . BASE_PATH . 'Modules/UserModule.php';
                }

                if (DB::$connected) {
                    $conf->setValues($db);
                    // Reset forced setup after the setValues
                    if (defined('SYSLOG_FILE')) {
                        $conf->global->SYSLOG_FILE = constant('SYSLOG_FILE');
                    }
                    $conf->global->MAIN_ENABLE_LOG_TO_HTML = 1;

                    // Create admin user
                    // include_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

                    // Set default encryption to yes, generate a salt and set default encryption algorithm (but only if there is no user yet into database)
                    $sql = "SELECT u.rowid, u.pass, u.pass_crypted";
                    $sql .= " FROM " . $config->main_db_prefix . "user as u";
                    $resql = $db->query($sql);
                    if ($resql) {
                        $numrows = $db->num_rows($resql);
                        if ($numrows == 0) {
                            // Define default setup for password encryption
                            Admin::dolibarr_set_const($db, "DATABASE_PWD_ENCRYPTED", "1", 'chaine', 0, '', $conf->entity);
                            Admin::dolibarr_set_const($db, "MAIN_SECURITY_SALT", Functions::dol_print_date(Functions::dol_now(), 'dayhourlog'), 'chaine', 0, '', 0); // All entities
                            if (function_exists('password_hash')) {
                                Admin::dolibarr_set_const($db, "MAIN_SECURITY_HASH_ALGO", 'password_hash', 'chaine', 0, '', 0); // All entities
                            } else {
                                Admin::dolibarr_set_const($db, "MAIN_SECURITY_HASH_ALGO", 'sha1md5', 'chaine', 0, '', 0); // All entities
                            }
                        }

                        $this->dolibarr_install_syslog('step5: DATABASE_PWD_ENCRYPTED = ' . Functions::getDolGlobalString('DATABASE_PWD_ENCRYPTED') . ' MAIN_SECURITY_HASH_ALGO = ' . Functions::getDolGlobalString('MAIN_SECURITY_HASH_ALGO'), LOG_INFO);
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
                        print $this->lang->trans("AdminLoginCreatedSuccessfuly", $login) . "<br>";
                        $success = 1;
                    } else {
                        if ($result == -6) {    //login or email already exists
                            $this->dolibarr_install_syslog('step5: AdminLoginAlreadyExists', LOG_WARNING);
                            print '<br><div class="warning">' . $newuser->error . "</div><br>";
                            $success = 1;
                        } else {
                            $this->dolibarr_install_syslog('step5: FailedToCreateAdminLogin ' . $newuser->error, LOG_ERR);
                            Functions::setEventMessages($this->lang->trans("FailedToCreateAdminLogin") . ' ' . $newuser->error, null, 'errors');
                            //header("Location: step4.php?error=3&selectlang=$setuplang".(isset($login) ? '&login='.$login : ''));
                            print '<br><div class="error">' . $this->lang->trans("FailedToCreateAdminLogin") . ': ' . $newuser->error . '</div><br><br>';
                            print $this->lang->trans("ErrorGoBackAndCorrectParameters") . '<br><br>';
                            $success = 1;
                        }
                    }

                    if ($success) {
                        // Insert MAIN_VERSION_FIRST_INSTALL in a dedicated transaction. So if it fails (when first install was already done), we can do other following requests.
                        $db->begin();
                        $this->dolibarr_install_syslog('step5: set MAIN_VERSION_FIRST_INSTALL const to ' . $targetversion, LOG_DEBUG);
                        $resql = $db->query("INSERT INTO " . $config->main_db_prefix . "const(name, value, type, visible, note, entity) values(" . $db->encrypt('MAIN_VERSION_FIRST_INSTALL') . ", " . $db->encrypt($targetversion) . ", 'chaine', 0, 'Dolibarr version when first install', 0)");
                        if ($resql) {
                            $conf->global->MAIN_VERSION_FIRST_INSTALL = $targetversion;
                            $db->commit();
                        } else {
                            //if (! $resql) Functions::dol_print_error($db,'Error in setup program');      // We ignore errors. Key may already exists
                            $db->commit();
                        }

                        $db->begin();

                        $this->dolibarr_install_syslog('step5: set MAIN_VERSION_LAST_INSTALL const to ' . $targetversion, LOG_DEBUG);
                        $resql = $db->query("DELETE FROM " . $config->main_db_prefix . "const WHERE " . $db->decrypt('name') . " = 'MAIN_VERSION_LAST_INSTALL'");
                        if (!$resql) {
                            Functions::dol_print_error($db, 'Error in setup program');
                        }
                        $resql = $db->query("INSERT INTO " . $config->main_db_prefix . "const(name,value,type,visible,note,entity) values(" . $db->encrypt('MAIN_VERSION_LAST_INSTALL') . ", " . $db->encrypt($targetversion) . ", 'chaine', 0, 'Dolibarr version when last install', 0)");
                        if (!$resql) {
                            Functions::dol_print_error($db, 'Error in setup program');
                        }
                        $conf->global->MAIN_VERSION_LAST_INSTALL = $targetversion;

                        if ($useforcedwizard) {
                            $this->dolibarr_install_syslog('step5: set MAIN_REMOVE_INSTALL_WARNING const to 1', LOG_DEBUG);
                            $resql = $db->query("DELETE FROM " . $config->main_db_prefix . "const WHERE " . $db->decrypt('name') . " = 'MAIN_REMOVE_INSTALL_WARNING'");
                            if (!$resql) {
                                Functions::dol_print_error($db, 'Error in setup program');
                            }
                            // The install.lock file is created few lines later if version is last one or if option MAIN_ALWAYS_CREATE_LOCK_AFTER_LAST_UPGRADE is on
                            /* No need to enable this
                            $resql = $db->query("INSERT INTO ".$config->main_db_prefix."const(name,value,type,visible,note,entity) values(".$db->encrypt('MAIN_REMOVE_INSTALL_WARNING').", ".$db->encrypt(1).", 'chaine', 1, 'Disable install warnings', 0)");
                            if (!$resql) {
                                Functions::dol_print_error($db, 'Error in setup program');
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
                            Functions::dol_syslog("Scan directory " . $dir . " for module descriptor files (modXXX.class.php)");
                            $handle = @opendir($dir);
                            if (is_resource($handle)) {
                                while (($file = readdir($handle)) !== false) {
                                    if (is_readable($dir . $file) && substr($file, 0, 3) == 'mod' && substr($file, Functions::dol_strlen($file) - 10) == '.class.php') {
                                        $modName = substr($file, 0, Functions::dol_strlen($file) - 10);
                                        if ($modName) {
                                            if (!empty($modNameLoaded[$modName])) {   // In cache of already loaded modules ?
                                                $mesg = "Error: Module " . $modName . " was found twice: Into " . $modNameLoaded[$modName] . " and " . $dir . ". You probably have an old file on your disk.<br>";
                                                Functions::setEventMessages($mesg, null, 'warnings');
                                                Functions::dol_syslog($mesg, LOG_ERR);
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
                                                Functions::dol_syslog("Failed to load " . $dir . $file . " " . $e->getMessage(), LOG_ERR);
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
                                //print $this->lang->trans("ActivateModule", $modtoactivatenew).'<br>';

                                $file = $modtoactivatenew . '.class.php';
                                $this->dolibarr_install_syslog('step5: activate module file=' . $file);
                                $res = dol_include_once("/core/modules/" . $file);

                                $res = activateModule($modtoactivatenew, 1);
                                if (!empty($res['errors'])) {
                                    print 'ERROR: failed to activateModule() file=' . $file;
                                }
                            }
                            //print '<br>';
                        }

                        // Now delete the flag that say installation is not complete
                        $this->dolibarr_install_syslog('step5: remove MAIN_NOT_INSTALLED const');
                        $resql = $db->query("DELETE FROM " . $config->main_db_prefix . "const WHERE " . $db->decrypt('name') . " = 'MAIN_NOT_INSTALLED'");
                        if (!$resql) {
                            Functions::dol_print_error($db, 'Error in setup program');
                        }

                        // May fail if parameter already defined
                        $this->dolibarr_install_syslog('step5: set the default language');
                        $resql = $db->query("INSERT INTO " . $config->main_db_prefix . "const(name,value,type,visible,note,entity) VALUES (" . $db->encrypt('MAIN_LANG_DEFAULT') . ", " . $db->encrypt($setuplang) . ", 'chaine', 0, 'Default language', 1)");
                        //if (! $resql) Functions::dol_print_error($db,'Error in setup program');

                        $db->commit();
                    }
                } else {
                    print $this->lang->trans("ErrorFailedToConnect") . "<br>";
                }
            } elseif (empty($action) || preg_match('/upgrade/i', $action)) {
                // If upgrade
                if (DB::$connected) {
                    $conf->setValues($db);
                    // Reset forced setup after the setValues
                    if (defined('SYSLOG_FILE')) {
                        $conf->global->SYSLOG_FILE = constant('SYSLOG_FILE');
                    }
                    $conf->global->MAIN_ENABLE_LOG_TO_HTML = 1;

                    // Define if we need to update the MAIN_VERSION_LAST_UPGRADE value in database
                    $tagdatabase = false;
                    if (!Functions::getDolGlobalString('MAIN_VERSION_LAST_UPGRADE')) {
                        $tagdatabase = true; // We don't know what it was before, so now we consider we at the chosen version.
                    } else {
                        $mainversionlastupgradearray = preg_split('/[.-]/', $conf->global->MAIN_VERSION_LAST_UPGRADE);
                        $targetversionarray = preg_split('/[.-]/', $targetversion);
                        if (versioncompare($targetversionarray, $mainversionlastupgradearray) > 0) {
                            $tagdatabase = true;
                        }
                    }

                    if ($tagdatabase) {
                        $this->dolibarr_install_syslog('step5: set MAIN_VERSION_LAST_UPGRADE const to value ' . $targetversion);
                        $resql = $db->query("DELETE FROM " . $config->main_db_prefix . "const WHERE " . $db->decrypt('name') . " = 'MAIN_VERSION_LAST_UPGRADE'");
                        if (!$resql) {
                            Functions::dol_print_error($db, 'Error in setup program');
                        }
                        $resql = $db->query("INSERT INTO " . $config->main_db_prefix . "const(name, value, type, visible, note, entity) VALUES (" . $db->encrypt('MAIN_VERSION_LAST_UPGRADE') . ", " . $db->encrypt($targetversion) . ", 'chaine', 0, 'Dolibarr version for last upgrade', 0)");
                        if (!$resql) {
                            Functions::dol_print_error($db, 'Error in setup program');
                        }
                        $conf->global->MAIN_VERSION_LAST_UPGRADE = $targetversion;
                    } else {
                        $this->dolibarr_install_syslog('step5: we run an upgrade to version ' . $targetversion . ' but database was already upgraded to ' . Functions::getDolGlobalString('MAIN_VERSION_LAST_UPGRADE') . '. We keep MAIN_VERSION_LAST_UPGRADE as it is.');

                        // Force the delete of the flag that say installation is not complete
                        $this->dolibarr_install_syslog('step5: remove MAIN_NOT_INSTALLED const after upgrade process (should not exists but this is a security)');
                        $resql = $db->query("DELETE FROM " . $config->main_db_prefix . "const WHERE " . $db->decrypt('name') . " = 'MAIN_NOT_INSTALLED'");
                        if (!$resql) {
                            Functions::dol_print_error($db, 'Error in setup program');
                        }
                    }
                } else {
                    print $this->lang->trans("ErrorFailedToConnect") . "<br>";
                }
            } else {
                Functions::dol_print_error(null, 'step5.php: unknown choice of action');
            }

            DB::disconnect();
        }


// Create lock file

// If first install
        if ($action == "set") {
            if ($success) {
                if (!Functions::getDolGlobalString('MAIN_VERSION_LAST_UPGRADE') || ($conf->global->MAIN_VERSION_LAST_UPGRADE == DOL_VERSION)) {
                    // Install is finished (database is on same version than files)
                    print '<br>' . $this->lang->trans("SystemIsInstalled") . "<br>";

                    // Create install.lock file
                    // No need for the moment to create it automatically, creation by web assistant means permissions are given
                    // to the web user, it is better to show a warning to say to create it manually with correct user/permission (not erasable by a web process)
                    $createlock = 0;
                    if (!empty($force_install_lockinstall) || Functions::getDolGlobalString('MAIN_ALWAYS_CREATE_LOCK_AFTER_LAST_UPGRADE')) {
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
                        print '<div class="warning">' . $this->lang->trans("WarningRemoveInstallDir") . "</div>";
                    }

                    print "<br>";

                    print $this->lang->trans("YouNeedToPersonalizeSetup") . "<br><br><br>";

                    print '<div class="center">&gt; <a href="../admin/index.php?mainmenu=home&leftmenu=setup' . (isset($login) ? '&username=' . urlencode($login) : '') . '">';
                    print '<span class="fas fa-external-link-alt"></span> ' . $this->lang->trans("GoToSetupArea");
                    print '</a></div><br>';
                } else {
                    // If here MAIN_VERSION_LAST_UPGRADE is not empty
                    print $this->lang->trans("VersionLastUpgrade") . ': <b><span class="ok">' . Functions::getDolGlobalString('MAIN_VERSION_LAST_UPGRADE') . '</span></b><br>';
                    print $this->lang->trans("VersionProgram") . ': <b><span class="ok">' . DOL_VERSION . '</span></b><br>';
                    print $this->lang->trans("MigrationNotFinished") . '<br>';
                    print "<br>";

                    print '<div class="center"><a href="' . $dolibarr_main_url_root . '/install/index.php">';
                    print '<span class="fas fa-link-alt"></span> ' . $this->lang->trans("GoToUpgradePage");
                    print '</a></div>';
                }
            }
        } elseif (empty($action) || preg_match('/upgrade/i', $action)) {
            // If upgrade
            if (!Functions::getDolGlobalString('MAIN_VERSION_LAST_UPGRADE') || ($conf->global->MAIN_VERSION_LAST_UPGRADE == DOL_VERSION)) {
                // Upgrade is finished (database is on the same version than files)
                print '<img class="valignmiddle inline-block paddingright" src="../theme/common/octicons/build/svg/checklist.svg" width="20" alt="Configuration">';
                print ' <span class="valignmiddle">' . $this->lang->trans("SystemIsUpgraded") . "</span><br>";

                // Create install.lock file if it does not exists.
                // Note: it should always exists. A better solution to allow upgrade will be to add an upgrade.unlock file
                $createlock = 0;
                if (!empty($force_install_lockinstall) || Functions::getDolGlobalString('MAIN_ALWAYS_CREATE_LOCK_AFTER_LAST_UPGRADE')) {
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
                    print '<br><div class="warning">' . $this->lang->trans("WarningRemoveInstallDir") . "</div>";
                }

                // Delete the upgrade.unlock file it it exists
                $unlockupgradefile = DOL_DATA_ROOT . '/upgrade.unlock';
                dol_delete_file($unlockupgradefile, 0, 0, 0, null, false, 0);

                print "<br>";

                $morehtml = '<br><div class="center"><a href="../index.php?mainmenu=home' . (isset($login) ? '&username=' . urlencode($login) : '') . '">';
                $morehtml .= '<span class="fas fa-link-alt"></span> ' . $this->lang->trans("GoToDolibarr") . '...';
                $morehtml .= '</a></div><br>';
            } else {
                // If here MAIN_VERSION_LAST_UPGRADE is not empty
                print $this->lang->trans("VersionLastUpgrade") . ': <b><span class="ok">' . Functions::getDolGlobalString('MAIN_VERSION_LAST_UPGRADE') . '</span></b><br>';
                print $this->lang->trans("VersionProgram") . ': <b><span class="ok">' . DOL_VERSION . '</span></b>';

                print "<br>";

                $morehtml = '<br><div class="center"><a href="../install/index.php">';
                $morehtml .= '<span class="fas fa-link-alt"></span> ' . $this->lang->trans("GoToUpgradePage");
                $morehtml .= '</a></div>';
            }
        } else {
            Functions::dol_print_error(null, 'step5.php: unknown choice of action=' . $action . ' in create lock file seaction');
        }

// Clear cache files
        clearstatcache();

        $ret = 0;
        if ($error && isset($argv[1])) {
            $ret = 1;
        }
        $this->dolibarr_install_syslog("Exit " . $ret);

        $this->dolibarr_install_syslog("--- step5: Dolibarr setup finished");

        //pFooter(1, $setuplang, '', 0, $morehtml);

// Return code if ran from command line
        if ($ret) {
            exit($ret);
        }

        return true;
    }

    public function checkAction(): bool
    {
        if (parent::checkAction()) {
            return true;
        }

        switch (htmlentities($this->action)) {
            case 'checked':
                return $this->actionChecked();
            case $this->lang->trans("Start"):
                return $this->actionStart();
            case 'config':
                return $this->actionConfig();
            case 'step2':
                return $this->actionStep2();
            case 'step4':
                return $this->actionStep4();
            case 'step5':
                return $this->actionStep5();
            default:
                $this->syslog("The action $this->action is not defined!");
        }

        return false;
    }

    public function body()
    {
        $this->selectLang = filter_input(INPUT_POST, 'selectlang');
        if (empty($this->selectLang)) {
            $this->selectLang = $this->lang->getDefaultLang();
        }
        $this->lang->setDefaultLang($this->selectLang);
        $this->lang->loadLangs(['main', 'admin', 'install', 'errors']);

        return parent::body(); // Launch checkAction
    }

}