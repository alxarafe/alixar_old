<?php

namespace Alixar\Update;

use Alixar\Update\Lib\CheckPrerequisites;
use Alxarafe\Base\BasicController;
use Alxarafe\Base\Globals;
use Alxarafe\DB\DB;
use Alxarafe\Lib\Admin;
use Alxarafe\Lib\Files;
use Alxarafe\Lib\Functions;
use Alxarafe\Lib\HookManager;
use Alxarafe\Lib\Security;
use Alxarafe\LibClass\FormAdmin;
use Alxarafe\LibClass\Lang;
use Exception;
use Modules\UserModule;
use Modules\User\User;

class Update extends BasicController
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
                        $comment = ' - ' . Lang::_("FunctionNotAvailableInThisPHP");
                    }

                    $options[] = [
                        'name' => $oldname,
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

        $this->template = 'update/install';
        $this->buttons = [
            [
                'value' => 'checked',
                'text' => Lang::_('NextStep') . ' ->',
            ],
        ];

        $form = new FormAdmin(null);
        $this->noReadableConfig = Lang::_('NoReadableConfFileSoStartInstall');
        $this->defaultLanguage = Lang::_('DefaultLanguage');
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
        $this->template = 'update/checked';
        $this->ok = CheckPrerequisites::getErrors();

        $this->buttons = [];
        if (!$this->ok) {
            $this->buttons = [
                [
                    'value' => 'checked',
                    'text' => Lang::_('retry'),
                ],
            ];
        }

        $this->miscellaneousChecks = Lang::_('MiscellaneousChecks');

        $this->checks = CheckPrerequisites::$checks;
        foreach (CheckPrerequisites::$messages as $field => $value) {
            $this->{$field} = $value;
        }

        return true;
    }

    private function getStartPostData()
    {
        $this->main_dir = Functions::FilterInputPost('main_dir', $this->config->MAIN->APP_BASEPATH);
        if (empty($this->main_dir)) {
            $this->main_dir = BASE_PATH;
        }

        $this->main_data_dir = Functions::FilterInputPost('main_data_dir', $this->config->MAIN->APP_BASEPATH_DOCUMENTS);
        if (empty($this->main_data_dir)) {
            $this->main_data_dir = $this->detect_dolibarr_main_data_root($this->main_dir);
        }
        if (!empty($force_install_main_data_root)) {
            $this->main_data_dir = $force_install_main_data_root;
        }

        $this->main_url = Functions::FilterInputPost('main_url', $this->config->MAIN->APP_URL);
        if (empty($this->main_url)) {
            $this->main_url = $this->detect_dolibarr_main_url_root();
        }

        $this->db_types = $this->getDbTypes();

        $this->db_name = Functions::FilterInputPost('db_name', $this->config->DB->DB_DATABASE);
        $this->db_type = Functions::FilterInputPost('db_type', $this->config->DB->DB_CONNECTION);
        $this->db_host = Functions::FilterInputPost('db_host', $this->config->DB->DB_HOST);
        $this->db_port = Functions::FilterInputPost('db_port', $this->config->DB->DB_PORT);
        $this->db_prefix = Functions::FilterInputPost('db_prefix', $this->config->DB->DB_PREFIX ?? Globals::DEFAULT_DB_PREFIX);
        $this->db_user = Functions::FilterInputPost('db_user', $this->config->DB->DB_USERNAME);
        $this->db_pass = Functions::FilterInputPost('db_pass', $this->config->DB->DB_PASSWORD);

        if (empty($this->db_type)) {
            $this->db_type = 'mysqli';
        }

        if (!isset($this->config->main_db_host)) {
            $this->config->main_db_host = "localhost";
        }

        // If $force_install_databasepass is on, we don't want to set password, we just show '***'. Real value will be extracted from the forced install file at step1.
        $autofill = ((!empty($_SESSION['dol_save_pass'])) ? $_SESSION['dol_save_pass'] : str_pad('', strlen($force_install_databasepass ?? ''), '*'));
        if (!empty($dolibarr_main_prod) && empty($_SESSION['dol_save_pass'])) {    // So value can't be found if install page still accessible
            $autofill = '';
        }
        $this->autofill = Functions::dol_escape_htmltag($autofill);

        $this->install_port = !empty($force_install_port) ? $force_install_port : $this->config->main_db_port ?? '';
        $this->install_prefix = !empty($force_install_prefix) ? $force_install_prefix : (!empty($this->config->main_db_prefix) ? $this->config->main_db_prefix : Globals::DEFAULT_DB_PREFIX);

        $this->db_create = $force_db_create ?? '';
        $this->install_noedit = ($force_install_noedit ?? '') == 2 && $force_db_create !== null;

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
        $this->webPagesDirectory = Lang::_("WebPagesDirectory");

        $this->db_create_user = !empty(filter_input(INPUT_POST, 'db_create_user'));
        $this->db_create_database = !empty(filter_input(INPUT_POST, 'db_create_database'));
        $this->db_user_root = Functions::FilterInputPost('db_user_root', $this->db_user_root ?? $this->db_user);
        $this->db_pass_root = Functions::FilterInputPost('db_pass_pass', $this->db_pass_root ?? $this->db_pass);
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
        $this->template = 'update/start';
        $this->buttons = [
            [
                'value' => 'config',
                'text' => Lang::_('NextStep') . ' ->',
                'js' => 'return jscheckparam();',
            ],
        ];

        $this->dolibarr_install_syslog("- fileconf: entering fileconf.php page");

        session_start(); // To be able to keep info into session (used for not losing pass during navigation. pass must not transit through parameters)

        $this->subtitle = Lang::_("ConfigurationFile");

        static::getStartPostData();


        return true;
    }

    private function createDatabaseUser($db)
    {
        // Create user
        $result = $db->DDLCreateUser($this->db_host, $this->db_user, $this->db_pass, $this->db_name);

        // Create user bis
        $resultbis = $result;
        if ($this->db_type === 'mysql') {
            if (!in_array($this->db_host, ['127.0.0.1', '::1', 'localhost', 'localhost.local'])) {
                $resultbis = $db->DDLCreateUser('%', $this->db_user, $this->db_pass, $this->db_name);
            }
        }

        print '<tr><td>';
        print Lang::_("UserCreation") . ' : ';
        print $this->db_user;
        print '</td>';

        $ok = $result > 0 && $resultbis > 0;
        if ($ok) {
            print '<td><img src="Resources/img/ok.png" alt="Ok"></td></tr>';
            return true;
        }

        $alreadyExists = [
            'DB_ERROR_RECORD_ALREADY_EXISTS',
            'DB_ERROR_KEY_NAME_ALREADY_EXISTS',
            'DB_ERROR_USER_ALREADY_EXISTS',
        ];

        if (in_array($db->errno(), $alreadyExists)) {
            $this->dolibarr_install_syslog("step1: user already exists");
            print '<td>' . Lang::_("LoginAlreadyExists") . '</td></tr>';
            return true;
        }

        $this->dolibarr_install_syslog("step1: failed to create user", LOG_ERR);
        print '<td>' . Lang::_("Error") . ': ' . $db->errno() . ' ' . $db->error() . ($db->error ? '. ' . $db->error : '') . "</td></tr>";
        return false;
    }

    private function createDatabase($db)
    {
        if ($db->DDLCreateDb($this->db_name, $this->config->DB->DB_CHARSET, $this->config->DB->DB_COLLATION, $this->db_user)) {
            print '<tr><td>';
            print Lang::_("DatabaseCreation") . " (" . Lang::_("User") . " " . $this->db_user_root . ") : ";
            print $this->db_name;
            print '</td>';
            print '<td><img src="Resources/img/ok.png" alt="Ok"></td></tr>';

            $db->select_db($this->db_name);
            $check1 = $db->getDefaultCharacterSetDatabase();
            $check2 = $db->getDefaultCollationDatabase();
            $this->dolibarr_install_syslog('step1: new database is using charset=' . $check1 . ' collation=' . $check2);

            // If values differs, we save conf file again
            //if ($check1 != $dolibarr_main_db_character_set) $this->dolibarr_install_syslog('step1: value for character_set is not the one asked for database creation', LOG_WARNING);
            //if ($check2 != $dolibarr_main_db_collation)     $this->dolibarr_install_syslog('step1: value for collation is not the one asked for database creation', LOG_WARNING);
            return true;
        }

        // warning message
        print '<tr><td colspan="2"><br>';
        print Lang::_("ErrorFailedToCreateDatabase", $this->db_name) . '<br>';
        print $db->lasterror() . '<br>';
        print Lang::_("IfDatabaseExistsGoBackAndCheckCreate");
        print '<br>';
        print '</td></tr>';

        $this->dolibarr_install_syslog('step1: failed to create database ' . $this->db_name . ' ' . $db->lasterrno() . ' ' . $db->lasterror(), LOG_ERR);

        return false;
    }

    private function actionConfig()
    {
        $this->template = 'update/config';
        $this->buttons = [
            [
                'value' => 'populate',
                'text' => Lang::_('NextStep') . ' ->',
                'js' => 'return jscheckparam();',
            ],
        ];

        $this->getStartPostData();

        $db_root = false;
        if ($this->db_create_database || $this->db_create_user) {
            $db_root = DB::checkConnection(
                $this->db_type,
                $this->db_host,
                $this->db_user_root,
                $this->db_pass_root,
                '',
                (int) $this->db_port
            );

            // If not connection, re-start
            if ($db_root === false || $db_root->error === 'Failed to connect') {
                dump('db connection error in createDatabase');
                print Lang::_("BecauseConnectionFailedParametersMayBeWrong") . '<br><br>';
                print Lang::_("ErrorGoBackAndCorrectParameters");
                return $this->actionStart();
            }

            if ($this->db_create_user && !$this->createDatabaseUser($db_root)) {
                dump('Usuario no creado');
                // Si el usuario no se ha podido crear... Retornamos
                return $this->actionStart();
            }

            if ($this->db_create_database && !$this->createDatabase($db_root)) {
                dump('Base de datos no creada');
                // Si la base de datos no se ha podido crear... Retornamos
                return $this->actionStart();
            }

            DB::disconnect();
        }

        $db = DB::checkConnection(
            $this->db_type,
            $this->db_host,
            $this->db_user,
            $this->db_pass,
            $this->db_name,
            (int) $this->db_port,
            $this->db_prefix,
            true
        );

        // If not connection, re-start
        if ($db === false || !empty($db->error)) {
            dump(['db' => $db]);
            print '<br>' . Lang::_("BecauseConnectionFailedParametersMayBeWrong") . '<br><br>';
            print Lang::_("ErrorGoBackAndCorrectParameters");
            return $this->actionStart();
        }

        $this->db_exists = $db->select_db($this->db_name);
        if ($this->db_exists && $this->db_create) {
            print '<div class="error">' . Lang::_("ErrorDatabaseAlreadyExists", $this->db_name) . '</div>';
            print Lang::_("IfDatabaseExistsGoBackAndCheckCreate") . '<br><br>';
            print Lang::_("ErrorGoBackAndCorrectParameters");
            return $this->actionStart();
        }

        if (!$this->db_exists && !$this->db_create) {
            print '<div class="error">' . Lang::_("ErrorConnectedButDatabaseNotFound", $this->db_name) . '</div>';
            print Lang::_("IfDatabaseNotExistsGoBackAndUncheckCreate") . '<br><br>';
            print Lang::_("ErrorGoBackAndCorrectParameters");
            return $this->actionStart();
        }

        $this->dolibarr_install_syslog("--- step1: entering step1.php page");

        $errors = [];

        $this->main_dir = Functions::dol_sanitizePathName($this->main_dir);
        $this->main_data_dir = Functions::dol_sanitizePathName($this->main_data_dir);
        $this->main_url = Functions::dol_sanitizePathName($this->main_url);

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

        if (!is_dir($this->main_dir)) {
            $this->dolibarr_install_syslog("step1: directory '" . $this->main_dir . "' is unavailable or can't be accessed");

            print "<tr><td>";
            print Lang::_("ErrorDirDoesNotExists", $this->main_dir) . '<br>';
            print Lang::_("ErrorWrongValueForParameter", $this->lang->transnoentitiesnoconv("WebPagesDirectory")) . '<br>';
            print Lang::_("ErrorGoBackAndCorrectParameters") . '<br><br>';
            print '</td><td>';
            print Lang::_("Error");
            print "</td></tr>";
            $error++;
        }

        // Create subdirectory main_data_dir
        if (!$error) {
            // Create directory for documents
            if (!is_dir($this->main_data_dir)) {
                Functions::dol_mkdir($this->main_data_dir);
            }

            if (!is_dir($this->main_data_dir)) {
                print "<tr><td>" . Lang::_("ErrorDirDoesNotExists", $this->main_data_dir);
                print ' ' . Lang::_("YouMustCreateItAndAllowServerToWrite");
                print '</td><td>';
                print '<span class="error">' . Lang::_("Error") . '</span>';
                print "</td></tr>";
                print '<tr><td colspan="2"><br>' . Lang::_("CorrectProblemAndReloadPage", $_SERVER['PHP_SELF'] . '?testget=ok') . '</td></tr>';
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
                            $this->errors[] = Lang::_('ErrorFailToCreateDir', $dir[$i]);
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
                    print "<tr><td>" . Lang::_("ErrorDirDoesNotExists", $this->main_data_dir);
                    print ' ' . Lang::_("YouMustCreateItAndAllowServerToWrite");
                    print '</td><td>';
                    print '<span class="error">' . Lang::_("Error") . '</span>';
                    print "</td></tr>";
                    print '<tr><td colspan="2"><br>' . Lang::_("CorrectProblemAndReloadPage", $_SERVER['PHP_SELF'] . '?testget=ok') . '</td></tr>';
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
                            $this->errors[] = Lang::_('ErrorFailToCopyFile', $src, $dest);
                        }
                    }
                }
            }
        }

        $this->dolibarr_install_syslog("Exit " . $error);

        $this->dolibarr_install_syslog("--- step1: end");

        return true;
    }

    public function actionPopulate()
    {
        $this->template = 'update/populate';
        $this->subtitle = Lang::_("CreateDatabaseObjects");
        $this->buttons = [
            [
                'value' => 'admin_user',
                'text' => Lang::_('NextStep') . ' ->',
                'js' => 'return jscheckparam();',
            ],
        ];

        $this->fatalError = '';
        $this->connectionMessage = '';
        $this->connectionResult = '';
        $this->errors = [];

        // This page can be long. We increase the time allowed. / Cette page peut etre longue. On augmente le delai autorise.
        // Only works if you are not in safe_mode. / Ne fonctionne que si on est pas en safe_mode.

        $err = error_reporting();
        error_reporting(0);      // Disable all errors
        //error_reporting(E_ALL);
        @set_time_limit(1800);   // Need 1800 on some very slow OS like Windows 7/64
        error_reporting($err);

        // Now we load forced values from install.forced.php file.

        $useforcedwizard = false;

        $this->dolibarr_install_syslog("--- step2: entering step2.php page");

        // Test if we can run a first install process
        $this->fatalError = false;

        $error = 0;

        $db = Globals::getDb();
        if (DB::$connected) {
            $this->connectionMessage = Lang::_("ServerConnection") . ": " . $this->config->DB->DB_HOST;
            $this->connectionResult = '<img src="Resources/img/ok.png" alt="Ok">';
            $ok = 1;
        } else {
            $this->connectionMessage = 'Failed to connect to server: ' . $this->config->DB->DB_HOST;
            $this->connectionResult = '<img src="Resources/img/error.png" alt="Error">';
        }

        if ($ok) {
            if ($db->database_selected) {
                $this->dolibarr_install_syslog("step2: successful connection to database: " . $this->config->DB->DB_DATABASE);
            } else {
                $this->dolibarr_install_syslog("step2: failed connection to database :" . $this->config->DB->DB_DATABASE, LOG_ERR);
                $this->errors[] = [
                    'text' => 'Failed to select database ' . $this->config->DB->DB_DATABASE,
                    'icon' => '<img src="Resources/img/error.png" alt="Error">',
                ];
            }
        }

        // Display version / Affiche version
        if ($ok) {
            $version = $db->getVersion();
            $versionarray = $db->getVersionArray();

            $this->databaseVersionMessage = Lang::_("DatabaseVersion");
            $this->databaseVersionVersion = $version;

            $this->databaseNameMessage = Lang::_("DatabaseName");
            $this->databaseNameName = $db->database_name;
        }

        $requestnb = 0;

        // To disable some code, so you can call pupulate with url like
        // http://localhost/dolibarrnew/install/pupulate.php?action=set&token='.newToken().'&createtables=0&createkeys=0&createfunctions=0&createdata=llx_20_c_departements
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
            $dir = CheckPrerequisites::getDataDir('mysql/tables');

            $ok = 0;
            $handle = opendir($dir);
            $this->dolibarr_install_syslog("pupulate: open tables directory " . $dir . " handle=" . $handle);
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
                    if ($this->config->DB->DB_CONNECTION == 'mysql' || $this->config->DB->DB_CONNECTION == 'mysqli') {    // For Mysql 5.5+, we must replace type=innodb with ENGINE=innodb
                        $buffer = preg_replace('/type=innodb/i', 'ENGINE=innodb', $buffer);
                    } else {
                        // Keyword ENGINE is MySQL-specific, so scrub it for
                        // other database types (mssql, pgsql)
                        $buffer = preg_replace('/type=innodb/i', '', $buffer);
                        $buffer = preg_replace('/ENGINE=innodb/i', '', $buffer);
                    }

                    // Replace the prefix tables
                    if ($this->config->DB->DB_PREFIX != 'llx_') {
                        $buffer = preg_replace('/llx_/i', $this->config->DB->DB_PREFIX, $buffer);
                    }

                    //print "<tr><td>Creation of table $name/td>";
                    $requestnb++;

                    $this->dolibarr_install_syslog("pupulate: request: " . $buffer);
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
                                'text' => Lang::_("CreateTableAndPrimaryKey", $name) . '<br>' . Lang::_("Request") . ' ' . $requestnb . ': ' . $buffer . ' <br>Executed query : ' . $db->lastquery,
                                'icon' => '<span class="error">' . Lang::_("ErrorSQL") . " " . $db->errno() . " " . $db->error() . '</span>',
                            ];
                            $error++;
                        }
                    }
                } else {
                    $this->errors[] = [
                        'text' => Lang::_("CreateTableAndPrimaryKey", $name),
                        'icon' => '<span class="error">' . Lang::_("Error") . ' Failed to open file ' . $dir . $file . '</span>',
                    ];
                    $error++;
                    $this->dolibarr_install_syslog("pupulate: failed to open file " . $dir . $file, LOG_ERR);
                }
            }

            if ($tablefound) {
                if ($error == 0) {
                    $this->errors[] = [
                        'text' => Lang::_("TablesAndPrimaryKeysCreation"),
                        'icon' => '<img src="Resources/img/ok.png" alt="Ok">',
                    ];
                    $ok = 1;
                }
            } else {
                $this->errors[] = [
                    'text' => Lang::_("ErrorFailedToFindSomeFiles", $dir),
                    'icon' => '<img src="Resources/img/error.png" alt="Error">',
                ];
                $this->dolibarr_install_syslog("pupulate: failed to find files to create database in directory " . $dir, LOG_ERR);
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
            $dir = CheckPrerequisites::getDataDir('mysql/tables');

            $okkeys = 0;
            $handle = opendir($dir);
            $this->dolibarr_install_syslog("pupulate: open keys directory " . $dir . " handle=" . $handle);
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
                        if ($this->config->DB->DB_CONNECTION === 'mysqli' && preg_match('/^--\sV([0-9\.]+)/i', $buf, $reg)) {
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
                        if ($this->config->DB->DB_CONNECTION === 'pgsql' && preg_match('/^--\sPOSTGRESQL\sV([0-9\.]+)/i', $buf, $reg)) {
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
                            if ($this->config->DB->DB_PREFIX != 'llx_') {
                                $buffer = preg_replace('/llx_/i', $this->config->DB->DB_PREFIX, $buffer);
                            }

                            //print "<tr><td>Creation of keys and table index $name: '$buffer'</td>";
                            $requestnb++;

                            $this->dolibarr_install_syslog("pupulate: request: " . $buffer);
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
                                    print "<tr><td>" . Lang::_("CreateOtherKeysForTable", $name);
                                    print "<br>\n" . Lang::_("Request") . ' ' . $requestnb . ' : ' . $db->lastqueryerror();
                                    print "\n</td>";
                                    print '<td><span class="error">' . Lang::_("ErrorSQL") . " " . $db->errno() . " " . $db->error() . '</span></td></tr>';
                                    $error++;
                                }
                            }
                        }
                    }
                } else {
                    print "<tr><td>" . Lang::_("CreateOtherKeysForTable", $name);
                    print "</td>";
                    print '<td><span class="error">' . Lang::_("Error") . " Failed to open file " . $dir . $file . "</span></td></tr>";
                    $error++;
                    $this->dolibarr_install_syslog("pupulate: failed to open file " . $dir . $file, LOG_ERR);
                }
            }

            if ($tablefound && $error == 0) {
                print '<tr><td>';
                print Lang::_("OtherKeysCreation") . '</td><td><img src="../theme/eldy/img/tick.png" alt="Ok"></td></tr>';
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
            $folder = $this->config->DB->DB_CONNECTION;
            if ($folder === 'mysqli') {
                $folder = 'mysql';
            }

            $dir = CheckPrerequisites::getDataDir($folder . '/functions/');

            // Creation of data
            $file = "functions.sql";
            if (file_exists($dir . $file)) {
                $fp = fopen($dir . $file, "r");
                $this->dolibarr_install_syslog("pupulate: open function file " . $dir . $file . " handle=" . $fp);
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
                        if ($this->config->DB->DB_PREFIX != 'llx_') {
                            $buffer = preg_replace('/llx_/i', $this->config->DB->DB_PREFIX, $buffer);
                        }
                        $this->dolibarr_install_syslog("pupulate: request: " . $buffer);
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

                                print "<tr><td>" . Lang::_("FunctionsCreation");
                                print "<br>\n" . Lang::_("Request") . ' ' . $requestnb . ' : ' . $buffer;
                                print "\n</td>";
                                print '<td><span class="error">' . Lang::_("ErrorSQL") . " " . $db->errno() . " " . $db->error() . '</span></td></tr>';
                                $error++;
                            }
                        }
                    }
                }

                print "<tr><td>" . Lang::_("FunctionsCreation") . "</td>";
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
            $dir = CheckPrerequisites::getDataDir("mysql/data/");

            // Insert data
            $handle = opendir($dir);
            $this->dolibarr_install_syslog("pupulate: open directory data " . $dir . " handle=" . $handle);
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
                $this->dolibarr_install_syslog("pupulate: open data file " . $dir . $file . " handle=" . $fp);
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

                    $this->dolibarr_install_syslog("pupulate: found " . $linefound . " records, defined " . count($arrayofrequests) . " group(s).");

                    $okallfile = 1;
                    $db->begin();

                    // We loop on each requests of file
                    foreach ($arrayofrequests as $buffer) {
                        // Replace the tables prefixes
                        if ($this->config->DB->DB_PREFIX != 'llx_') {
                            $buffer = preg_replace('/llx_/i', $this->config->DB->DB_PREFIX, $buffer);
                        }

                        //dolibarr_install_syslog("pupulate: request: " . $buffer);
                        $resql = $db->query($buffer, 1);
                        if ($resql) {
                            //$db->free($resql);     // Not required as request we launch here does not return memory needs.
                        } else {
                            if ($db->lasterrno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
                                //print "<tr><td>Insertion ligne : $buffer</td><td>";
                            } else {
                                $ok = 0;
                                $okallfile = 0;
                                print '<span class="error">' . Lang::_("ErrorSQL") . " : " . $db->lasterrno() . " - " . $db->lastqueryerror() . " - " . $db->lasterror() . "</span><br>";
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

            print "<tr><td>" . Lang::_("ReferenceDataLoading") . "</td>";
            if ($ok) {
                print '<td><img src="../theme/eldy/img/tick.png" alt="Ok"></td></tr>';
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
        $this->dolibarr_install_syslog("Exit " . $ret);

        $this->dolibarr_install_syslog("- pupulate: end");

// Force here a value we need after because master.inc.php is not loaded into pupulate.
// This code must be similar with the one into main.inc.php

        $this->conf->file->instance_unique_id = (empty($this->instance_unique_id) ? (empty($dolibarr_main_cookie_cryptkey) ? '' : $dolibarr_main_cookie_cryptkey) : $this->instance_unique_id); // Unique id of instance

        $hash_unique_id = Security::dol_hash('dolibarr' . $this->conf->file->instance_unique_id, 'sha256');   // Note: if the global salt changes, this hash changes too so ping may be counted twice. We don't mind. It is for statistics purpose only.

        $out = '<input type="checkbox" name="dolibarrpingno" id="dolibarrpingno"' . ((Functions::getDolGlobalString('MAIN_FIRST_PING_OK_ID') == 'disabled') ? '' : ' value="checked"') . '> ';
        $out .= '<label for="dolibarrpingno">' . Lang::_("MakeAnonymousPing") . '</label>';

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

    public function actionAdminUser()
    {
        $this->template = 'update/admin_user';
        $this->subtitle = Lang::_("AdminAccountCreation");
        $this->buttons = [
            [
                'value' => 'setup_end',
                'text' => Lang::_('NextStep') . ' ->',
                'js' => 'return jscheckparam();',
            ],
        ];

        $this->lang->loadLangs(["admin", "install"]);

        $this->dolibarr_install_syslog("--- step4: entering step4.php page");

        $error = 0;
        $ok = 0;

        // Test if we can run a first install process
        $this->login_value = (Functions::GETPOSTISSET(" login")
                ? Functions::GETPOST("login", 'alpha')
                : (isset($force_install_dolibarrlogin) ?
                    $force_install_dolibarrlogin : '')) . '"' . (@$force_install_noedit == 2 &&
            $force_install_dolibarrlogin !== null ? ' disabled' : '');

        if (isset($_GET["error"]) && $_GET["error"] == 1) {
            print '<br>';
            print '<div class="error">' . Lang::_("PasswordsMismatch") . '</div>';
            $error = 0; // We show button
        }

        if (isset($_GET["error"]) && $_GET["error"] == 2) {
            print '<br>';
            print '<div class="error">';
            print Lang::_("PleaseTypePassword");
            print '</div>';
            $error = 0; // We show button
        }

        if (isset($_GET["error"]) && $_GET["error"] == 3) {
            print '<br>';
            print '<div class="error">' . Lang::_("PleaseTypeALogin") . '</div>';
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

    public function actionSetupEnd()
    {
        die('step5');
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

        $this->template = 'update/step5';
        $this->subtitle = Lang::_("SetupEnd");
        $this->nextButton = true;

        // Test if we can run a first install process
        if (empty($versionfrom) && empty($versionto) && !is_writable($conffile)) {
            print Lang::_("ConfFileIsNotWritable", $conffiletoshow);
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
                        print Lang::_("AdminLoginCreatedSuccessfuly", $login) . "<br>";
                        $success = 1;
                    } else {
                        if ($result == -6) {    //login or email already exists
                            $this->dolibarr_install_syslog('step5: AdminLoginAlreadyExists', LOG_WARNING);
                            print '<br><div class="warning">' . $newuser->error . "</div><br>";
                            $success = 1;
                        } else {
                            $this->dolibarr_install_syslog('step5: FailedToCreateAdminLogin ' . $newuser->error, LOG_ERR);
                            Functions::setEventMessages(Lang::_("FailedToCreateAdminLogin") . ' ' . $newuser->error, null, 'errors');
                            //header("Location: step4.php?error=3&selectlang=$setuplang".(isset($login) ? '&login='.$login : ''));
                            print '<br><div class="error">' . Lang::_("FailedToCreateAdminLogin") . ': ' . $newuser->error . '</div><br><br>';
                            print Lang::_("ErrorGoBackAndCorrectParameters") . '<br><br>';
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
                                //print Lang::_("ActivateModule", $modtoactivatenew).'<br>';

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
                    print Lang::_("ErrorFailedToConnect") . "<br>";
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
                    print Lang::_("ErrorFailedToConnect") . "<br>";
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
                    print '<br>' . Lang::_("SystemIsInstalled") . "<br>";

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
                        print '<div class="warning">' . Lang::_("WarningRemoveInstallDir") . "</div>";
                    }

                    print "<br>";

                    print Lang::_("YouNeedToPersonalizeSetup") . "<br><br><br>";

                    print '<div class="center">&gt; <a href="../admin/index.php?mainmenu=home&leftmenu=setup' . (isset($login) ? '&username=' . urlencode($login) : '') . '">';
                    print '<span class="fas fa-external-link-alt"></span> ' . Lang::_("GoToSetupArea");
                    print '</a></div><br>';
                } else {
                    // If here MAIN_VERSION_LAST_UPGRADE is not empty
                    print Lang::_("VersionLastUpgrade") . ': <b><span class="ok">' . Functions::getDolGlobalString('MAIN_VERSION_LAST_UPGRADE') . '</span></b><br>';
                    print Lang::_("VersionProgram") . ': <b><span class="ok">' . DOL_VERSION . '</span></b><br>';
                    print Lang::_("MigrationNotFinished") . '<br>';
                    print "<br>";

                    print '<div class="center"><a href="' . $dolibarr_main_url_root . '/install/index.php">';
                    print '<span class="fas fa-link-alt"></span> ' . Lang::_("GoToUpgradePage");
                    print '</a></div>';
                }
            }
        } elseif (empty($action) || preg_match('/upgrade/i', $action)) {
            // If upgrade
            if (!Functions::getDolGlobalString('MAIN_VERSION_LAST_UPGRADE') || ($conf->global->MAIN_VERSION_LAST_UPGRADE == DOL_VERSION)) {
                // Upgrade is finished (database is on the same version than files)
                print '<img class="valignmiddle inline-block paddingright" src="../theme/common/octicons/build/svg/checklist.svg" width="20" alt="Configuration">';
                print ' <span class="valignmiddle">' . Lang::_("SystemIsUpgraded") . "</span><br>";

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
                    print '<br><div class="warning">' . Lang::_("WarningRemoveInstallDir") . "</div>";
                }

                // Delete the upgrade.unlock file it it exists
                $unlockupgradefile = DOL_DATA_ROOT . '/upgrade.unlock';
                dol_delete_file($unlockupgradefile, 0, 0, 0, null, false, 0);

                print "<br>";

                $morehtml = '<br><div class="center"><a href="../index.php?mainmenu=home' . (isset($login) ? '&username=' . urlencode($login) : '') . '">';
                $morehtml .= '<span class="fas fa-link-alt"></span> ' . Lang::_("GoToDolibarr") . '...';
                $morehtml .= '</a></div><br>';
            } else {
                // If here MAIN_VERSION_LAST_UPGRADE is not empty
                print Lang::_("VersionLastUpgrade") . ': <b><span class="ok">' . Functions::getDolGlobalString('MAIN_VERSION_LAST_UPGRADE') . '</span></b><br>';
                print Lang::_("VersionProgram") . ': <b><span class="ok">' . DOL_VERSION . '</span></b>';

                print "<br>";

                $morehtml = '<br><div class="center"><a href="../install/index.php">';
                $morehtml .= '<span class="fas fa-link-alt"></span> ' . Lang::_("GoToUpgradePage");
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
            case 'start':
                return $this->actionStart();
            case 'config':
                return $this->actionConfig();
            case 'populate':
                return $this->actionPopulate();
            case 'admin_user':
                return $this->actionAdminUser();
            case 'setup_end':
                return $this->actionSetupEnd();
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