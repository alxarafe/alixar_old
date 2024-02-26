<?php

namespace Alixar\Install;

use Alxarafe\Base\BasicController;
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
            $result[text] = $this->lang->trans("ErrorPHPVersionTooHigh", Admin::versiontostring($arrayphpmaxversionwarning));
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
            $result['text'] = $this->lang->trans("PHPSupportPOSTGETKo") . ' (<a href="' . dol_escape_htmltag($_SERVER["PHP_SELF"]) . '?testget=ok">' . $langs->trans("Recheck") . '</a>)';
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
    }

    private function checkConfFile()
    {
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
            if (copy($conffile . ".example", $conffile)) {
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
    }

    public function checkConfFileWritable()
    {

    }

    public function noAction(): bool
    {
        $this->template = 'install/install';

        $form = new FormAdmin(null);
        $this->htmlComboLanguages = $form->select_language('auto', 'selectlang', 1, 0, 0, 1);

        return true;
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
        /*
        $value = $this->checkMemory();
        if ($value) {
            $this->checks[] = $value;
            $ok = $ok && $value['ok'];
        }
        $value = $this->checkConfFile();
        if ($value) {
            $this->checks[] = $value;
            $ok = $ok && $value['ok'];
        }
        $value = $this->checkConfFileWritable();
        if ($value) {
            $this->checks[] = $value;
            $ok = $ok && $value['ok'];
        }
        */

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
                die("The action $this->action is not defined!");
        }

        return false;
    }

    public function body()
    {
        parent::body();
    }

}