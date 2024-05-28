<?php

/* Copyright (C) 2002-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2003       Xavier Dutoit           <doli@sydesy.com>
 * Copyright (C) 2004-2021  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004       Sebastien Di Cintio     <sdicintio@ressource-toi.org>
 * Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2021  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2014  Philippe Grand          <philippe.grand@atoo-net.com>
 * Copyright (C) 2008       Matteli
 * Copyright (C) 2011-2016  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2012       Christophe Battarel     <christophe.battarel@altairis.fr>
 * Copyright (C) 2014-2015  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2020       Demarest Maxime         <maxime@indelog.fr>
 * Copyright (C) 2020       Charlene Benke          <charlie@patas-monkey.com>
 * Copyright (C) 2021       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2021       Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2023       Joachim Küter      		<git-jk@bloxera.com>
 * Copyright (C) 2023       Eric Seigne      		<eric.seigne@cap-rel.fr>
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

/**
 *  \file       htdocs/main.inc.php
 *  \ingroup    core
 *  \brief      File that defines environment for Dolibarr GUI pages only (file not required by scripts)
 */

global $db;

/**
 * @deprecated Use $config instead
 */

use DoliCore\Tools\Debug;
use DoliCore\Base\Config;
use DoliCore\Base\Constants;

defineIfNotDefined('DOL_APPLICATION_TITLE', 'Alixar');
defineIfNotDefined('DOL_VERSION', '20.0.0-alpha');
defineIfNotDefined('APPLICATION_VERSION', '0.0');

require_once BASE_PATH . '/../Dolibarr/Lib/MainFunctions.php';

$conf = Config::getConf();

// If $conf is not empty, we load the "superglobal" variables.
if ($conf !== null && isset($conf->db->name) && !empty($conf->db->name)) {
    if (!isset($db)) {
        $db = Config::getDb($conf);
    }

    $config = Config::getConfig($conf);
    Constants::define($config);

    $hookmanager = Config::getHookManager();
    $langs = Config::getLangs($conf);
    $user = Config::getUser();
    $menumanager = Config::getMenuManager($conf);

    Config::setConfigValues($conf, $db);
}

//@ini_set('memory_limit', '128M'); // This may be useless if memory is hard limited by your PHP

// For optional tuning. Enabled if environment variable MAIN_SHOW_TUNING_INFO is defined.
$micro_start_time = 0;
if (isset($config) && $config->server->detailed_info) {
    [$usec, $sec] = explode(" ", microtime());
    $micro_start_time = ((float) $usec + (float) $sec);
    // Add Xdebug code coverage
    //define('XDEBUGCOVERAGE',1);
    if (defined('XDEBUGCOVERAGE')) {
        xdebug_start_code_coverage();
    }
}

/**
 * Return the real char for a numeric entities.
 * WARNING: This function is required by testSqlAndScriptInject() and the GETPOST 'restricthtml'. Regex calling must be
 * similar.
 *
 * @param string $matches String of numeric entity
 *
 * @return  string                          New value
 */
if (!function_exists('realCharForNumericEntities')) {
    function realCharForNumericEntities($matches)
    {
        $newstringnumentity = preg_replace('/;$/', '', $matches[1]);
        //print  ' $newstringnumentity='.$newstringnumentity;

        if (preg_match('/^x/i', $newstringnumentity)) {
            $newstringnumentity = hexdec(preg_replace('/^x/i', '', $newstringnumentity));
        }

        // The numeric value we don't want as entities because they encode ascii char, and why using html entities on ascii except for haking ?
        if (($newstringnumentity >= 65 && $newstringnumentity <= 90) || ($newstringnumentity >= 97 && $newstringnumentity <= 122)) {
            return chr((int) $newstringnumentity);
        }

        return '&#' . $matches[1]; // Value will be unchanged because regex was /&#(  )/
    }
}

/**
 * Security: WAF layer for SQL Injection and XSS Injection (scripts) protection (Filters on GET, POST, PHP_SELF).
 * Warning: Such a protection can't be enough. It is not reliable as it will always be possible to bypass this. Good
 * protection can only be guaranteed by escaping data during output.
 *
 * @param string $val  Brute value found into $_GET, $_POST or PHP_SELF
 * @param string $type 0=POST, 1=GET, 2=PHP_SELF, 3=GET without sql reserved keywords (the less tolerant test)
 *
 * @return      int                     >0 if there is an injection, 0 if none
 */
if (!function_exists('testSqlAndScriptInject')) {
    function testSqlAndScriptInject($val, $type)
    {
        // Decode string first because a lot of things are obfuscated by encoding or multiple encoding.
        // So <svg o&#110;load='console.log(&quot;123&quot;)' become <svg onload='console.log(&quot;123&quot;)'
        // So "&colon;&apos;" become ":'" (due to ENT_HTML5)
        // So "&Tab;&NewLine;" become ""
        // So "&lpar;&rpar;" become "()"

        // Loop to decode until no more things to decode.
        //print "before decoding $val\n";
        do {
            $oldval = $val;
            $val = html_entity_decode($val, ENT_QUOTES | ENT_HTML5);    // Decode '&colon;', '&apos;', '&Tab;', '&NewLine', ...
            // Sometimes we have entities without the ; at end so html_entity_decode does not work but entities is still interpreted by browser.
            $val = preg_replace_callback(
                '/&#(x?[0-9][0-9a-f]+;?)/i',
                /**
                 * @param string $m
                 *
                 * @return string
                 */
                static function ($m) {
                    // Decode '&#110;', ...
                    return realCharForNumericEntities($m);
                },
                $val
            );

            // We clean html comments because some hacks try to obfuscate evil strings by inserting HTML comments. Example: on<!-- -->error=alert(1)
            $val = preg_replace('/<!--[^>]*-->/', '', $val);
            $val = preg_replace('/[\r\n\t]/', '', $val);
        } while ($oldval != $val);
        //print "type = ".$type." after decoding: ".$val."\n";

        $inj = 0;

        // We check string because some hacks try to obfuscate evil strings by inserting non printable chars. Example: 'java(ascci09)scr(ascii00)ipt' is processed like 'javascript' (whatever is place of evil ascii char)
        // We should use dol_string_nounprintableascii but function is not yet loaded/available
        // Example of valid UTF8 chars:
        // utf8=utf8mb3:    '\x09', '\x0A', '\x0D', '\x7E'
        // utf8=utf8mb3:    '\xE0\xA0\x80'
        // utf8mb4:         '\xF0\x9D\x84\x9E'   (but this may be refused by the database insert if pagecode is utf8=utf8mb3)
        $newval = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/u', '', $val); // /u operator makes UTF8 valid characters being ignored so are not included into the replace

        // Note that $newval may also be completely empty '' when non valid UTF8 are found.
        if ($newval != $val) {
            // If $val has changed after removing non valid UTF8 chars, it means we have an evil string.
            $inj += 1;
        }
        //print 'inj='.$inj.'-type='.$type.'-val='.$val.'-newval='.$newval."\n";

        // For SQL Injection (only GET are used to scan for such injection strings)
        if ($type == 1 || $type == 3) {
            // Note the \s+ is replaced into \s* because some spaces may have been modified in previous loop
            $inj += preg_match('/delete\s*from/i', $val);
            $inj += preg_match('/create\s*table/i', $val);
            $inj += preg_match('/insert\s*into/i', $val);
            $inj += preg_match('/select\s*from/i', $val);
            $inj += preg_match('/into\s*(outfile|dumpfile)/i', $val);
            $inj += preg_match('/user\s*\(/i', $val); // avoid to use function user() or mysql_user() that return current database login
            $inj += preg_match('/information_schema/i', $val); // avoid to use request that read information_schema database
            $inj += preg_match('/<svg/i', $val); // <svg can be allowed in POST
            $inj += preg_match('/update[^&=\w].*set.+=/i', $val);   // the [^&=\w] test is to avoid error when request is like action=update&...set... or &updatemodule=...set...
            $inj += preg_match('/union.+select/i', $val);
        }
        if ($type == 3) {
            // Note the \s+ is replaced into \s* because some spaces may have been modified in previous loop
            $inj += preg_match('/select|update|delete|truncate|replace|group\s*by|concat|count|from|union/i', $val);
        }
        if ($type != 2) {   // Not common key strings, so we can check them both on GET and POST
            $inj += preg_match('/updatexml\(/i', $val);
            $inj += preg_match('/(\.\.%2f)+/i', $val);
            $inj += preg_match('/\s@@/', $val);
        }
        // For XSS Injection done by closing textarea to execute content into a textarea field
        $inj += preg_match('/<\/textarea/i', $val);
        // For XSS Injection done by adding javascript with script
        // This is all cases a browser consider text is javascript:
        // When it found '<script', 'javascript:', '<style', 'onload\s=' on body tag, '="&' on a tag size with old browsers
        // All examples on page: http://ha.ckers.org/xss.html#XSScalc
        // More on https://www.owasp.org/index.php/XSS_Filter_Evasion_Cheat_Sheet
        $inj += preg_match('/<audio/i', $val);
        $inj += preg_match('/<embed/i', $val);
        $inj += preg_match('/<iframe/i', $val);
        $inj += preg_match('/<object/i', $val);
        $inj += preg_match('/<script/i', $val);
        $inj += preg_match('/Set\.constructor/i', $val); // ECMA script 6
        if (!defined('NOSTYLECHECK')) {
            $inj += preg_match('/<style/i', $val);
        }
        $inj += preg_match('/base\s+href/si', $val);
        $inj += preg_match('/=data:/si', $val);
        // List of dom events is on https://www.w3schools.com/jsref/dom_obj_event.asp and https://developer.mozilla.org/en-US/docs/Web/Events
        $inj += preg_match('/on(mouse|drag|key|load|touch|pointer|select|transition)[a-z]*\s*=/i', $val); // onmousexxx can be set on img or any html tag like <img title='...' onmouseover=alert(1)>
        $inj += preg_match('/on(abort|after|animation|auxclick|before|blur|cancel|canplay|canplaythrough|change|click|close|contextmenu|cuechange|copy|cut)[a-z]*\s*=/i', $val);
        $inj += preg_match('/on(dblclick|drop|durationchange|emptied|end|ended|error|focus|focusin|focusout|formdata|gotpointercapture|hashchange|input|invalid)[a-z]*\s*=/i', $val);
        $inj += preg_match('/on(lostpointercapture|offline|online|pagehide|pageshow)[a-z]*\s*=/i', $val);
        $inj += preg_match('/on(paste|pause|play|playing|progress|ratechange|reset|resize|scroll|search|seeked|seeking|show|stalled|start|submit|suspend)[a-z]*\s*=/i', $val);
        $inj += preg_match('/on(timeupdate|toggle|unload|volumechange|waiting|wheel)[a-z]*\s*=/i', $val);
        // More not into the previous list

        $inj += preg_match('/on(repeat|begin|finish|beforeinput)[a-z]*\s*=/i', $val);

        // We refuse html into html because some hacks try to obfuscate evil strings by inserting HTML into HTML. Example: <img on<a>error=alert(1) to bypass test on onerror
        $tmpval = preg_replace('/<[^<]+>/', '', $val);
        // List of dom events is on https://www.w3schools.com/jsref/dom_obj_event.asp and https://developer.mozilla.org/en-US/docs/Web/Events
        $inj += preg_match('/on(mouse|drag|key|load|touch|pointer|select|transition)[a-z]*\s*=/i', $tmpval); // onmousexxx can be set on img or any html tag like <img title='...' onmouseover=alert(1)>
        $inj += preg_match('/on(abort|after|animation|auxclick|before|blur|cancel|canplay|canplaythrough|change|click|close|contextmenu|cuechange|copy|cut)[a-z]*\s*=/i', $tmpval);
        $inj += preg_match('/on(dblclick|drop|durationchange|emptied|end|ended|error|focus|focusin|focusout|formdata|gotpointercapture|hashchange|input|invalid)[a-z]*\s*=/i', $tmpval);
        $inj += preg_match('/on(lostpointercapture|offline|online|pagehide|pageshow)[a-z]*\s*=/i', $tmpval);
        $inj += preg_match('/on(paste|pause|play|playing|progress|ratechange|reset|resize|scroll|search|seeked|seeking|show|stalled|start|submit|suspend)[a-z]*\s*=/i', $tmpval);
        $inj += preg_match('/on(timeupdate|toggle|unload|volumechange|waiting|wheel)[a-z]*\s*=/i', $tmpval);
        // More not into the previous list
        $inj += preg_match('/on(repeat|begin|finish|beforeinput)[a-z]*\s*=/i', $tmpval);

        //$inj += preg_match('/on[A-Z][a-z]+\*=/', $val);   // To lock event handlers onAbort(), ...
        $inj += preg_match('/&#58;|&#0000058|&#x3A/i', $val); // refused string ':' encoded (no reason to have it encoded) to lock 'javascript:...'
        $inj += preg_match('/j\s*a\s*v\s*a\s*s\s*c\s*r\s*i\s*p\s*t\s*:/i', $val);
        $inj += preg_match('/vbscript\s*:/i', $val);
        // For XSS Injection done by adding javascript closing html tags like with onmousemove, etc... (closing a src or href tag with not cleaned param)
        if ($type == 1 || $type == 3) {
            $val = str_replace('enclosure="', 'enclosure=X', $val); // We accept enclosure=" for the export/import module
            $inj += preg_match('/"/i', $val); // We refused " in GET parameters value.
        }
        if ($type == 2) {
            $inj += preg_match('/[:;"\'<>\?\(\){}\$%]/', $val); // PHP_SELF is a file system (or url path without parameters). It can contains spaces.
        }

        return $inj;
    }
}

/**
 * Return true if security check on parameters are OK, false otherwise.
 *
 * @param string|array $var      Variable name
 * @param int          $type     1=GET, 0=POST, 2=PHP_SELF
 * @param int          $stopcode 0=No stop code, 1=Stop code (default) if injection found
 *
 * @return      boolean|null                True if there is no injection.
 */
if (!function_exists('analyseVarsForSqlAndScriptsInjection')) {
    function analyseVarsForSqlAndScriptsInjection(&$var, $type, $stopcode = 1)
    {
        if (is_array($var)) {
            foreach ($var as $key => $value) {  // Warning, $key may also be used for attacks
                // Exclude check for some variable keys
                if ($type === 0 && defined('NOSCANPOSTFORINJECTION') && is_array(constant('NOSCANPOSTFORINJECTION')) && in_array($key, constant('NOSCANPOSTFORINJECTION'))) {
                    continue;
                }

                if (analyseVarsForSqlAndScriptsInjection($key, $type, $stopcode) && analyseVarsForSqlAndScriptsInjection($value, $type, $stopcode)) {
                    //$var[$key] = $value;  // This is useless
                } else {
                    http_response_code(403);

                    // Get remote IP: PS: We do not use getRemoteIP(), function is not yet loaded and we need a value that can't be spoofed
                    $ip = (empty($_SERVER['REMOTE_ADDR']) ? 'unknown' : $_SERVER['REMOTE_ADDR']);

                    if ($stopcode) {
                        $errormessage = 'Access refused to ' . htmlentities($ip, ENT_COMPAT, 'UTF-8') . ' by SQL or Script injection protection in main.inc.php:analyseVarsForSqlAndScriptsInjection type=' . htmlentities($type, ENT_COMPAT, 'UTF-8');
                        //$errormessage .= ' paramkey='.htmlentities($key, ENT_COMPAT, 'UTF-8');    // Disabled to avoid text injection

                        $errormessage2 = 'page=' . htmlentities((empty($_SERVER["REQUEST_URI"]) ? '' : $_SERVER["REQUEST_URI"]), ENT_COMPAT, 'UTF-8');
                        $errormessage2 .= ' paramkey=' . htmlentities($key, ENT_COMPAT, 'UTF-8');
                        $errormessage2 .= ' paramvalue=' . htmlentities($value, ENT_COMPAT, 'UTF-8');

                        print $errormessage;
                        print "<br>\n";
                        print 'Try to go back, fix data of your form and resubmit it. You can contact also your technical support.';

                        print "\n" . '<!--' . "\n";
                        print $errormessage2;
                        print "\n" . '-->';

                        // Add entry into the PHP server error log
                        if (function_exists('error_log')) {
                            error_log($errormessage . ' ' . substr($errormessage2, 2000));
                        }

                        // Note: No addition into security audit table is done because we don't want to execute code in such a case.
                        // Detection of too many such requests can be done with a fail2ban rule on 403 error code or into the PHP server error log.


                        if (class_exists('PHPUnit\Framework\TestSuite')) {
                            $message = $errormessage . ' ' . substr($errormessage2, 2000);
                            throw new Exception("Security injection exception: $message");
                        }
                        exit;
                    } else {
                        return false;
                    }
                }
            }
            return true;
        } else {
            return (testSqlAndScriptInject($var, $type) <= 0);
        }
    }
}

// To disable the WAF for GET and POST and PHP_SELF, uncomment this
//define('NOSCANPHPSELFFORINJECTION', 1);
//define('NOSCANGETFORINJECTION', 1);
//define('NOSCANPOSTFORINJECTION', 1 or 2);

// Check consistency of NOREQUIREXXX DEFINES
if ((defined('NOREQUIREDB') || defined('NOREQUIRETRAN')) && !defined('NOREQUIREMENU')) {
    print 'If define NOREQUIREDB or NOREQUIRETRAN are set, you must also set NOREQUIREMENU or not set them.';
    exit;
}
if (defined('NOREQUIREUSER') && !defined('NOREQUIREMENU')) {
    print 'If define NOREQUIREUSER is set, you must also set NOREQUIREMENU or not set it.';
    exit;
}

// Sanity check on URL
if (!defined('NOSCANPHPSELFFORINJECTION') && !empty($_SERVER['PHP_SELF'])) {
    $morevaltochecklikepost = [$_SERVER['PHP_SELF']];
    analyseVarsForSqlAndScriptsInjection($morevaltochecklikepost, 2);
}
// Sanity check on GET parameters
if (!defined('NOSCANGETFORINJECTION') && !empty($_SERVER["QUERY_STRING"])) {
    // Note: QUERY_STRING is url encoded, but $_GET and $_POST are already decoded
    // Because the analyseVarsForSqlAndScriptsInjection is designed for already url decoded value, we must decode QUERY_STRING
    // Another solution is to provide $_GET as parameter with analyseVarsForSqlAndScriptsInjection($_GET, 1);
    $morevaltochecklikeget = [urldecode($_SERVER["QUERY_STRING"])];
    analyseVarsForSqlAndScriptsInjection($morevaltochecklikeget, 1);
}
// Sanity check on POST
if (!defined('NOSCANPOSTFORINJECTION') || is_array(constant('NOSCANPOSTFORINJECTION'))) {
    analyseVarsForSqlAndScriptsInjection($_POST, 0);
}

// This is to make Dolibarr working with Plesk
if (!empty($_SERVER['DOCUMENT_ROOT']) && substr($_SERVER['DOCUMENT_ROOT'], -6) !== 'htdocs') {
    set_include_path($_SERVER['DOCUMENT_ROOT'] . '/htdocs');
}

// Include the conf.php and functions.lib.php and security.lib.php. This defined the constants like DOL_DOCUMENT_ROOT, DOL_DATA_ROOT, DOL_URL_ROOT...
require_once BASE_PATH . '/filefunc.inc.php';

// If there is a POST parameter to tell to save automatically some POST parameters into cookies, we do it.
// This is used for example by form of boxes to save personalization of some options.
// DOL_AUTOSET_COOKIE=cookiename:val1,val2 and  cookiename_val1=aaa cookiename_val2=bbb will set cookie_name with value json_encode(array('val1'=> , ))
if (!empty($_POST["DOL_AUTOSET_COOKIE"])) {
    $tmpautoset = explode(':', $_POST["DOL_AUTOSET_COOKIE"], 2);
    $tmplist = explode(',', $tmpautoset[1]);
    $cookiearrayvalue = [];
    foreach ($tmplist as $tmpkey) {
        $postkey = $tmpautoset[0] . '_' . $tmpkey;
        //var_dump('tmpkey='.$tmpkey.' postkey='.$postkey.' value='.$_POST[$postkey]);
        if (!empty($_POST[$postkey])) {
            $cookiearrayvalue[$tmpkey] = $_POST[$postkey];
        }
    }
    $cookiename = $tmpautoset[0];
    $cookievalue = json_encode($cookiearrayvalue);
    //var_dump('setcookie cookiename='.$cookiename.' cookievalue='.$cookievalue);
    if (PHP_VERSION_ID < 70300) {
        setcookie($cookiename, empty($cookievalue) ? '' : $cookievalue, empty($cookievalue) ? 0 : (time() + (86400 * 354)), '/', '', ((empty($dolibarr_main_force_https) && isHTTPS() === false) ? false : true), true); // keep cookie 1 year and add tag httponly
    } else {
        // Only available for php >= 7.3
        $cookieparams = [
            'expires' => empty($cookievalue) ? 0 : (time() + (86400 * 354)),
            'path' => '/',
            //'domain' => '.mywebsite.com', // the dot at the beginning allows compatibility with subdomains
            'secure' => ((empty($dolibarr_main_force_https) && isHTTPS() === false) ? false : true),
            'httponly' => true,
            'samesite' => 'Lax', // None || Lax  || Strict
        ];
        setcookie($cookiename, empty($cookievalue) ? '' : $cookievalue, $cookieparams);
    }
    if (empty($cookievalue)) {
        unset($_COOKIE[$cookiename]);
    }
}

// Set the handler of session
// if (ini_get('session.save_handler') == 'user')
if (!empty($php_session_save_handler) && $php_session_save_handler == 'db') {
    require_once 'core/lib/phpsessionin' . $php_session_save_handler . '.lib.php';
}

// Init session. Name of session is specific to Dolibarr instance.
// Must be done after the include of filefunc.inc.php so global variables of conf file are defined (like $dolibarr_main_instance_unique_id or $dolibarr_main_force_https).
// Note: the function dol_getprefix() is defined into functions.lib.php but may have been defined to return a different key to manage another area to protect.
$prefix = dol_getprefix('');
$sessionname = 'DOLSESSID_' . $prefix;
$sessiontimeout = 'DOLSESSTIMEOUT_' . $prefix;
if (!empty($_COOKIE[$sessiontimeout])) {
    ini_set('session.gc_maxlifetime', $_COOKIE[$sessiontimeout]);
}

// This create lock, released by session_write_close() or end of page.
// We need this lock as long as we read/write $_SESSION ['vars']. We can remove lock when finished.
if (!defined('NOSESSION')) {
    if (PHP_VERSION_ID < 70300) {
        session_set_cookie_params(0, '/', null, ((empty($dolibarr_main_force_https) && isHTTPS() === false) ? false : true), true); // Add tag secure and httponly on session cookie (same as setting session.cookie_httponly into php.ini). Must be called before the session_start.
    } else {
        // Only available for php >= 7.3
        $sessioncookieparams = [
            'lifetime' => 0,
            'path' => '/',
            //'domain' => '.mywebsite.com', // the dot at the beginning allows compatibility with subdomains
            'secure' => ((empty($dolibarr_main_force_https) && isHTTPS() === false) ? false : true),
            'httponly' => true,
            'samesite' => 'Lax', // None || Lax  || Strict
        ];
        session_set_cookie_params($sessioncookieparams);
    }
    session_name($sessionname);
    session_start();    // This call the open and read of session handler
    //exit; // this exist generates a call to write and close
}

// Init the 6 global objects, this include will make the 'new Xxx()' and set properties for: $conf, $db, $langs, $user, $mysoc, $hookmanager
require_once 'master.inc.php';

// Uncomment this and set session.save_handler = user to use local session storing
// include DOL_DOCUMENT_ROOT.'/core/lib/phpsessionindb.inc.php

// If software has been locked. Only login $conf->global->MAIN_ONLY_LOGIN_ALLOWED is allowed.
if (getDolGlobalString('MAIN_ONLY_LOGIN_ALLOWED')) {
    $ok = 0;
    if ((!session_id() || !isset($_SESSION["dol_login"])) && !isset($_POST["username"]) && !empty($_SERVER["GATEWAY_INTERFACE"])) {
        $ok = 1; // We let working pages if not logged and inside a web browser (login form, to allow login by admin)
    } elseif (isset($_POST["username"]) && $_POST["username"] == $conf->global->MAIN_ONLY_LOGIN_ALLOWED) {
        $ok = 1; // We let working pages that is a login submission (login submit, to allow login by admin)
    } elseif (defined('NOREQUIREDB')) {
        $ok = 1; // We let working pages that don't need database access (xxx.css.php)
    } elseif (defined('EVEN_IF_ONLY_LOGIN_ALLOWED')) {
        $ok = 1; // We let working pages that ask to work even if only login enabled (logout.php)
    } elseif (session_id() && isset($_SESSION["dol_login"]) && $_SESSION["dol_login"] == $conf->global->MAIN_ONLY_LOGIN_ALLOWED) {
        $ok = 1; // We let working if user is allowed admin
    }
    if (!$ok) {
        if (session_id() && isset($_SESSION["dol_login"]) && $_SESSION["dol_login"] != $conf->global->MAIN_ONLY_LOGIN_ALLOWED) {
            print 'Sorry, your application is offline.' . "\n";
            print 'You are logged with user "' . $_SESSION["dol_login"] . '" and only administrator user "' . getDolGlobalString('MAIN_ONLY_LOGIN_ALLOWED') . '" is allowed to connect for the moment.' . "\n";
            $nexturl = DOL_URL_ROOT . '/user/logout.php?token=' . newToken();
            print 'Please try later or <a href="' . $nexturl . '">click here to disconnect and change login user</a>...' . "\n";
        } else {
            print 'Sorry, your application is offline. Only administrator user "' . getDolGlobalString('MAIN_ONLY_LOGIN_ALLOWED') . '" is allowed to connect for the moment.' . "\n";
            $nexturl = DOL_URL_ROOT . '/';
            print 'Please try later or <a href="' . $nexturl . '">click here to change login user</a>...' . "\n";
        }
        exit;
    }
}

// Activate end of page function
register_shutdown_function('dol_shutdown');

// Load debugbar
if (isModEnabled('debugbar') && !GETPOST('dol_use_jmobile') && empty($_SESSION['dol_use_jmobile'])) {
    global $debugbar;
    /*
    include_once DOL_DOCUMENT_ROOT . '/debugbar/class/DebugBar.php';
    $debugbar = new DolibarrDebugBar();
    $renderer = $debugbar->getRenderer();
    if (!getDolGlobalString('MAIN_HTML_HEADER')) {
        $conf->global->MAIN_HTML_HEADER = '';
    }
    $conf->global->MAIN_HTML_HEADER .= $renderer->renderHead();

    $debugbar['time']->startMeasure('pageaftermaster', 'Page generation (after environment init)');
    */

    $debugbar = Debug::getDebugBar();

    if (!getDolGlobalString('MAIN_HTML_HEADER')) {
        $conf->global->MAIN_HTML_HEADER = '';
    }
    $conf->global->MAIN_HTML_HEADER .= Debug::getRenderHeader();

    $debugbar['time']->startMeasure('pageaftermaster', 'Page generation (after environment init)');
}

// Detection browser
if (isset($_SERVER["HTTP_USER_AGENT"])) {
    $tmp = getBrowserInfo($_SERVER["HTTP_USER_AGENT"]);
    $conf->browser->name = $tmp['browsername'];
    $conf->browser->os = $tmp['browseros'];
    $conf->browser->version = $tmp['browserversion'];
    $conf->browser->ua = $tmp['browserua'];
    $conf->browser->layout = $tmp['layout']; // 'classic', 'phone', 'tablet'
    //var_dump($conf->browser);

    if ($conf->browser->layout == 'phone') {
        $conf->dol_no_mouse_hover = 1;
    }
}

// If theme is forced
if (GETPOST('theme', 'aZ09')) {
    $conf->theme = GETPOST('theme', 'aZ09');
    $conf->css = "/theme/" . $conf->theme . "/style.css.php";
}

// Set global MAIN_OPTIMIZEFORTEXTBROWSER (must be before login part)
if (GETPOSTINT('textbrowser') || (!empty($conf->browser->name) && $conf->browser->name == 'lynxlinks')) {   // If we must enable text browser
    $conf->global->MAIN_OPTIMIZEFORTEXTBROWSER = 2;
}

// Force HTTPS if required ($conf->file->main_force_https is 0/1 or 'https dolibarr root url')
// $_SERVER["HTTPS"] is 'on' when link is https, otherwise $_SERVER["HTTPS"] is empty or 'off'
if (!empty($conf->file->main_force_https) && !isHTTPS() && !defined('NOHTTPSREDIRECT')) {
    $newurl = '';
    if (is_numeric($conf->file->main_force_https)) {
        if ($conf->file->main_force_https == '1' && !empty($_SERVER["SCRIPT_URI"])) {   // If SCRIPT_URI supported by server
            if (preg_match('/^http:/i', $_SERVER["SCRIPT_URI"]) && !preg_match('/^https:/i', $_SERVER["SCRIPT_URI"])) { // If link is http
                $newurl = preg_replace('/^http:/i', 'https:', $_SERVER["SCRIPT_URI"]);
            }
        } else {
            // Check HTTPS environment variable (Apache/mod_ssl only)
            $newurl = preg_replace('/^http:/i', 'https:', DOL_MAIN_URL_ROOT) . $_SERVER["REQUEST_URI"];
        }
    } else {
        // Check HTTPS environment variable (Apache/mod_ssl only)
        $newurl = $conf->file->main_force_https . $_SERVER["REQUEST_URI"];
    }
    // Start redirect
    if ($newurl) {
        header_remove(); // Clean header already set to be sure to remove any header like "Set-Cookie: DOLSESSID_..." from non HTTPS answers
        dol_syslog("main.inc: dolibarr_main_force_https is on, we make a redirect to " . $newurl);
        header("Location: " . $newurl);
        exit;
    } else {
        dol_syslog("main.inc: dolibarr_main_force_https is on but we failed to forge new https url so no redirect is done", LOG_WARNING);
    }
}

if (!defined('NOLOGIN') && !defined('NOIPCHECK') && !empty($dolibarr_main_restrict_ip)) {
    $listofip = explode(',', $dolibarr_main_restrict_ip);
    $found = false;
    foreach ($listofip as $ip) {
        $ip = trim($ip);
        if ($ip == $_SERVER['REMOTE_ADDR']) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        print 'Access refused by IP protection. Your detected IP is ' . $_SERVER['REMOTE_ADDR'];
        exit;
    }
}

if (!defined('NOREQUIREAJAX')) {
    require_once BASE_PATH . '/../Dolibarr/Lib/Ajax.php'; // Need 22ko memory
}

// If install or upgrade process not done or not completely finished, we call the install page.
if (getDolGlobalString('MAIN_NOT_INSTALLED') || getDolGlobalString('MAIN_NOT_UPGRADED')) {
    dol_syslog("main.inc: A previous install or upgrade was not complete. Redirect to install page.", LOG_WARNING);
    header("Location: " . DOL_URL_ROOT . "/install/index.php");
    exit;
}

// If an upgrade process is required, we call the install page.
$checkifupgraderequired = false;
if (getDolGlobalString('MAIN_VERSION_LAST_UPGRADE') && getDolGlobalString('MAIN_VERSION_LAST_UPGRADE') != DOL_VERSION) {
    $checkifupgraderequired = true;
}

if (!getDolGlobalString('MAIN_VERSION_LAST_UPGRADE') && getDolGlobalString('MAIN_VERSION_LAST_INSTALL') && getDolGlobalString('MAIN_VERSION_LAST_INSTALL') != DOL_VERSION) {
    $checkifupgraderequired = true;
}

if ($checkifupgraderequired) {
    $versiontocompare = getDolGlobalString('MAIN_VERSION_LAST_UPGRADE', getDolGlobalString('MAIN_VERSION_LAST_INSTALL'));
    require_once BASE_PATH . '/../Dolibarr/Lib/Admin.php';
    $dolibarrversionlastupgrade = preg_split('/[.-]/', $versiontocompare);
    $dolibarrversionprogram = preg_split('/[.-]/', DOL_VERSION);
    $rescomp = versioncompare($dolibarrversionprogram, $dolibarrversionlastupgrade);
    if ($rescomp > 0) {   // Programs have a version higher than database.
        if (!getDolGlobalString('MAIN_NO_UPGRADE_REDIRECT_ON_LEVEL_3_CHANGE') || $rescomp < 3) {
            // We did not add "&& $rescomp < 3" because we want upgrade process for build upgrades
            dol_syslog("main.inc: database version " . $versiontocompare . " is lower than programs version " . DOL_VERSION . ". Redirect to install/upgrade page.", LOG_WARNING);
            header("Location: " . DOL_URL_ROOT . "/install/index.php");
            exit;
        }
    }
}

// Creation of a token against CSRF vulnerabilities
if (!defined('NOTOKENRENEWAL') && !defined('NOSESSION')) {
    // No token renewal on .css.php, .js.php and .json.php (even if the NOTOKENRENEWAL was not provided)
    if (!preg_match('/\.(css|js|json)\.php$/', $_SERVER['PHP_SELF'])) {
        // Rolling token at each call ($_SESSION['token'] contains token of previous page)
        if (isset($_SESSION['newtoken'])) {
            $_SESSION['token'] = $_SESSION['newtoken'];
        }

        if (!isset($_SESSION['newtoken']) || getDolGlobalInt('MAIN_SECURITY_CSRF_TOKEN_RENEWAL_ON_EACH_CALL')) {
            // Note: Using MAIN_SECURITY_CSRF_TOKEN_RENEWAL_ON_EACH_CALL is not recommended: if a user succeed in entering a data from
            // a public page with a link that make a token regeneration, it can make use of the backoffice no more possible !
            // Save in $_SESSION['newtoken'] what will be next token. Into forms, we will add param token = $_SESSION['newtoken']
            $token = dol_hash(uniqid(mt_rand(), false), 'md5'); // Generates a hash of a random number. We don't need a secured hash, just a changing random value.
            $_SESSION['newtoken'] = $token;
            dol_syslog("NEW TOKEN generated by : " . $_SERVER['PHP_SELF'], LOG_DEBUG);
        }
    }
}

//dol_syslog("CSRF info: ".defined('NOCSRFCHECK')." - ".$dolibarr_nocsrfcheck." - ".$conf->global->MAIN_SECURITY_CSRF_WITH_TOKEN." - ".$_SERVER['REQUEST_METHOD']." - ".GETPOST('token', 'alpha'));

// Check validity of token, only if option MAIN_SECURITY_CSRF_WITH_TOKEN enabled or if constant CSRFCHECK_WITH_TOKEN is set into page
if ((!defined('NOCSRFCHECK') && empty($dolibarr_nocsrfcheck) && getDolGlobalInt('MAIN_SECURITY_CSRF_WITH_TOKEN')) || defined('CSRFCHECK_WITH_TOKEN')) {
    // Array of action code where CSRFCHECK with token will be forced (so token must be provided on url request)
    $sensitiveget = false;
    if ((GETPOSTISSET('massaction') || GETPOST('action', 'aZ09')) && getDolGlobalInt('MAIN_SECURITY_CSRF_WITH_TOKEN') >= 3) {
        // All GET actions (except the listed exception that are post actions) and mass actions are processed as sensitive.
        if (GETPOSTISSET('massaction') || !in_array(GETPOST('action', 'aZ09'), ['create', 'createsite', 'createcard', 'edit', 'editvalidator', 'file_manager', 'presend', 'presend_addmessage', 'preview', 'specimen'])) { // We exclude some action that are not sensitive so legitimate
            $sensitiveget = true;
        }
    } elseif (getDolGlobalInt('MAIN_SECURITY_CSRF_WITH_TOKEN') >= 2) {
        // Few GET actions coded with a &token into url are also processed as sensitive.
        $arrayofactiontoforcetokencheck = [
            'activate',
            'doprev', 'donext', 'dvprev', 'dvnext',
            'freezone', 'install',
            'reopen',
        ];
        if (in_array(GETPOST('action', 'aZ09'), $arrayofactiontoforcetokencheck)) {
            $sensitiveget = true;
        }
        // We also need a valid token for actions matching one of these values
        if (preg_match('/^(confirm_)?(add|classify|close|confirm|copy|del|disable|enable|remove|set|unset|update|save)/', GETPOST('action', 'aZ09'))) {
            $sensitiveget = true;
        }
    }

    // Check a token is provided for all cases that need a mandatory token
    // (all POST actions + all sensitive GET actions + all mass actions + all login/actions/logout on pages with CSRFCHECK_WITH_TOKEN set)
    if (
        (!empty($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') ||
        $sensitiveget ||
        GETPOSTISSET('massaction') ||
        ((GETPOSTISSET('actionlogin') || GETPOSTISSET('action')) && defined('CSRFCHECK_WITH_TOKEN'))
    ) {
        // If token is not provided or empty, error (we are in case it is mandatory)
        if (!GETPOST('token', 'alpha') || GETPOST('token', 'alpha') == 'notrequired') {
            // dump(debug_backtrace());
            top_httphead();
            if (GETPOSTINT('uploadform')) {
                dol_syslog("--- Access to " . (empty($_SERVER["REQUEST_METHOD"]) ? '' : $_SERVER["REQUEST_METHOD"] . ' ') . $_SERVER['PHP_SELF'] . " refused. File size too large or not provided.");
                $langs->loadLangs(["errors", "install"]);
                print $langs->trans("ErrorFileSizeTooLarge") . ' ';
                print $langs->trans("ErrorGoBackAndCorrectParameters");
            } else {
                http_response_code(403);
                if (defined('CSRFCHECK_WITH_TOKEN')) {
                    dol_syslog("--- Access to " . (empty($_SERVER["REQUEST_METHOD"]) ? '' : $_SERVER["REQUEST_METHOD"] . ' ') . $_SERVER['PHP_SELF'] . " refused by CSRF protection (CSRFCHECK_WITH_TOKEN protection) in main.inc.php. Token not provided.", LOG_WARNING);
                    print "Access to a page that needs a token (constant CSRFCHECK_WITH_TOKEN is defined) is refused by CSRF protection in main.inc.php. Token not provided.\n";
                } else {
                    dol_syslog("--- Access to " . (empty($_SERVER["REQUEST_METHOD"]) ? '' : $_SERVER["REQUEST_METHOD"] . ' ') . $_SERVER['PHP_SELF'] . " refused by CSRF protection (POST method or GET with a sensible value for 'action' parameter) in main.inc.php. Token not provided.", LOG_WARNING);
                    print "Access to this page this way (POST method or GET with a sensible value for 'action' parameter) is refused by CSRF protection in main.inc.php. Token not provided.\n";
                    print "If you access your server behind a proxy using url rewriting and the parameter is provided by caller, you might check that all HTTP header are propagated (or add the line \$dolibarr_nocsrfcheck=1 into your conf.php file or MAIN_SECURITY_CSRF_WITH_TOKEN to 0";
                    if (getDolGlobalString('MAIN_SECURITY_CSRF_WITH_TOKEN')) {
                        print " instead of " . getDolGlobalString('MAIN_SECURITY_CSRF_WITH_TOKEN');
                    }
                    print " into setup).\n";
                }
            }
            die;
        }
    }

    $sessiontokenforthisurl = (empty($_SESSION['token']) ? '' : $_SESSION['token']);
    // TODO Get the sessiontokenforthisurl into an array of session token (one array per base URL so we can use the CSRF per page and we keep ability for several tabs per url in a browser)
    if (GETPOSTISSET('token') && GETPOST('token') != 'notrequired' && GETPOST('token', 'alpha') != $sessiontokenforthisurl) {
        dol_syslog("--- Access to " . (empty($_SERVER["REQUEST_METHOD"]) ? '' : $_SERVER["REQUEST_METHOD"] . ' ') . $_SERVER['PHP_SELF'] . " refused by CSRF protection (invalid token), so we disable POST and some GET parameters - referrer=" . (empty($_SERVER['HTTP_REFERER']) ? '' : $_SERVER['HTTP_REFERER']) . ", action=" . GETPOST('action', 'aZ09') . ", _GET|POST['token']=" . GETPOST('token', 'alpha'), LOG_WARNING);
        //dol_syslog("_SESSION['token']=".$sessiontokenforthisurl, LOG_DEBUG);
        // Do not output anything on standard output because this create problems when using the BACK button on browsers. So we just set a message into session.
        if (!defined('NOTOKENRENEWAL')) {
            // If the page is not a page that disable the token renewal, we report a warning message to explain token has epired.
            setEventMessages('SecurityTokenHasExpiredSoActionHasBeenCanceledPleaseRetry', null, 'warnings', '', 1);
        }
        $savid = null;
        if (isset($_POST['id'])) {
            $savid = ((int) $_POST['id']);
        }
        unset($_POST);
        unset($_GET['confirm']);
        unset($_GET['action']);
        unset($_GET['confirmmassaction']);
        unset($_GET['massaction']);
        unset($_GET['token']);          // TODO Make a redirect if we have a token in url to remove it ?
        if (isset($savid)) {
            $_POST['id'] = ((int) $savid);
        }
        // So rest of code can know something was wrong here
        $_GET['errorcode'] = 'InvalidToken';
    }

    // Note: There is another CSRF protection into the filefunc.inc.php
}

// Disable modules (this must be after session_start and after conf has been loaded)
if (GETPOSTISSET('disablemodules')) {
    $_SESSION["disablemodules"] = GETPOST('disablemodules', 'alpha');
}

if (!empty($_SESSION["disablemodules"])) {
    $modulepartkeys = ['css', 'js', 'tabs', 'triggers', 'login', 'substitutions', 'menus', 'theme', 'sms', 'tpl', 'barcode', 'models', 'societe', 'hooks', 'dir', 'syslog', 'tpllinkable', 'contactelement', 'moduleforexternal', 'websitetemplates'];

    $disabled_modules = explode(',', $_SESSION["disablemodules"]);
    foreach ($disabled_modules as $module) {
        if ($module) {
            if (empty($conf->$module)) {
                $conf->$module = new stdClass(); // To avoid warnings
            }
            $conf->$module->enabled = false;
            foreach ($modulepartkeys as $modulepartkey) {
                unset($conf->modules_parts[$modulepartkey][$module]);
            }
            if ($module == 'fournisseur') {     // Special case
                $conf->supplier_order->enabled = 0;
                $conf->supplier_invoice->enabled = 0;
            }
        }
    }
}

// Set current modulepart
$modulepart = explode("/", $_SERVER['PHP_SELF']);
if (is_array($modulepart) && count($modulepart) > 0) {
    foreach ($conf->modules as $module) {
        if (in_array($module, $modulepart)) {
            $modulepart = $module;
            break;
        }
    }
}
if (is_array($modulepart)) {
    $modulepart = '';
}

require_once BASE_PATH . '/login.inc.php';

// Case forcing style from url
if (GETPOST('theme', 'aZ09')) {
    $conf->theme = GETPOST('theme', 'aZ09', 1);
    $conf->css = "/theme/" . $conf->theme . "/style.css.php";
}

// Set javascript option
if (GETPOSTINT('nojs')) {  // If javascript was not disabled on URL
    $conf->use_javascript_ajax = 0;
} else {
    if (!empty($user->conf->MAIN_DISABLE_JAVASCRIPT)) {
        $conf->use_javascript_ajax = !$user->conf->MAIN_DISABLE_JAVASCRIPT;
    }
}

// Set MAIN_OPTIMIZEFORTEXTBROWSER for user (must be after login part)
if (!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') && !empty($user->conf->MAIN_OPTIMIZEFORTEXTBROWSER)) {
    $conf->global->MAIN_OPTIMIZEFORTEXTBROWSER = $user->conf->MAIN_OPTIMIZEFORTEXTBROWSER;
    if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') == 1) {
        $conf->global->THEME_TOPMENU_DISABLE_IMAGE = 1;
    }
}
//var_dump($conf->global->THEME_TOPMENU_DISABLE_IMAGE);
//var_dump($user->conf->THEME_TOPMENU_DISABLE_IMAGE);

// set MAIN_OPTIMIZEFORCOLORBLIND for user
$conf->global->MAIN_OPTIMIZEFORCOLORBLIND = empty($user->conf->MAIN_OPTIMIZEFORCOLORBLIND) ? '' : $user->conf->MAIN_OPTIMIZEFORCOLORBLIND;

// Set terminal output option according to conf->browser.
if (GETPOSTINT('dol_hide_leftmenu') || !empty($_SESSION['dol_hide_leftmenu'])) {
    $conf->dol_hide_leftmenu = 1;
}
if (GETPOSTINT('dol_hide_topmenu') || !empty($_SESSION['dol_hide_topmenu'])) {
    $conf->dol_hide_topmenu = 1;
}
if (GETPOSTINT('dol_optimize_smallscreen') || !empty($_SESSION['dol_optimize_smallscreen'])) {
    $conf->dol_optimize_smallscreen = 1;
}
if (GETPOSTINT('dol_no_mouse_hover') || !empty($_SESSION['dol_no_mouse_hover'])) {
    $conf->dol_no_mouse_hover = 1;
}
if (GETPOSTINT('dol_use_jmobile') || !empty($_SESSION['dol_use_jmobile'])) {
    $conf->dol_use_jmobile = 1;
}
// If not on Desktop
if (!empty($conf->browser->layout) && $conf->browser->layout != 'classic') {
    $conf->dol_no_mouse_hover = 1;
}

// If on smartphone or optimized for small screen
if (
    (!empty($conf->browser->layout) && $conf->browser->layout == 'phone')
    || (!empty($_SESSION['dol_screenwidth']) && $_SESSION['dol_screenwidth'] < 400)
    || (!empty($_SESSION['dol_screenheight']) && $_SESSION['dol_screenheight'] < 400
        || getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER'))
) {
    $conf->dol_optimize_smallscreen = 1;

    if (getDolGlobalInt('PRODUIT_DESC_IN_FORM') == 1) {
        $conf->global->PRODUIT_DESC_IN_FORM_ACCORDING_TO_DEVICE = 0;
    }
}
// Replace themes bugged with jmobile with eldy
if (!empty($conf->dol_use_jmobile) && in_array($conf->theme, ['bureau2crea', 'cameleo', 'amarok'])) {
    $conf->theme = 'eldy';
    $conf->css = "/theme/" . $conf->theme . "/style.css.php";
}

if (!defined('NOREQUIRETRAN')) {
    if (!GETPOST('lang', 'aZ09')) { // If language was not forced on URL
        // If user has chosen its own language
        if (!empty($user->conf->MAIN_LANG_DEFAULT)) {
            // If different than current language
            //print ">>>".$langs->getDefaultLang()."-".$user->conf->MAIN_LANG_DEFAULT;
            if ($langs->getDefaultLang() != $user->conf->MAIN_LANG_DEFAULT) {
                $langs->setDefaultLang($user->conf->MAIN_LANG_DEFAULT);
            }
        }
    }
}

if (!defined('NOLOGIN')) {
    // If the login is not recovered, it is identified with an account that does not exist.
    // Hacking attempt?
    if (!$user->login) {
        accessforbidden();
    }

    // Check if user is active
    if ($user->statut < 1) {
        // If not active, we refuse the user
        $langs->loadLangs(["errors", "other"]);
        dol_syslog("Authentication KO as login is disabled", LOG_NOTICE);
        accessforbidden("ErrorLoginDisabled");
    }

    // Load permissions
    $user->getrights();
}

dol_syslog("--- Access to " . (empty($_SERVER["REQUEST_METHOD"]) ? '' : $_SERVER["REQUEST_METHOD"] . ' ') . $_SERVER['PHP_SELF'] . ' - action=' . GETPOST('action', 'aZ09') . ', massaction=' . GETPOST('massaction', 'aZ09') . (defined('NOTOKENRENEWAL') ? ' NOTOKENRENEWAL=' . constant('NOTOKENRENEWAL') : ''), LOG_NOTICE);
//Another call for easy debug
//dol_syslog("Access to ".$_SERVER['PHP_SELF'].' '.$_SERVER["HTTP_REFERER"].' GET='.join(',',array_keys($_GET)).'->'.join(',',$_GET).' POST:'.join(',',array_keys($_POST)).'->'.join(',',$_POST));

// Load main languages files
if (!defined('NOREQUIRETRAN')) {
    // Load translation files required by page
    $langs->loadLangs(['main', 'dict']);
}

// Define some constants used for style of arrays
$bc = [0 => 'class="impair"', 1 => 'class="pair"'];
$bcdd = [0 => 'class="drag drop oddeven"', 1 => 'class="drag drop oddeven"'];
$bcnd = [0 => 'class="nodrag nodrop nohover"', 1 => 'class="nodrag nodrop nohoverpair"']; // Used for tr to add new lines
$bctag = [0 => 'class="impair tagtr"', 1 => 'class="pair tagtr"'];

// Define messages variables
$mesg = '';
$warning = '';
$error = 0;
// deprecated, see setEventMessages() and dol_htmloutput_events()
$mesgs = [];
$warnings = [];
$errors = [];

// Constants used to defined number of lines in textarea
if (empty($conf->browser->firefox)) {
    define('ROWS_1', 1);
    define('ROWS_2', 2);
    define('ROWS_3', 3);
    define('ROWS_4', 4);
    define('ROWS_5', 5);
    define('ROWS_6', 6);
    define('ROWS_7', 7);
    define('ROWS_8', 8);
    define('ROWS_9', 9);
} else {
    define('ROWS_1', 0);
    define('ROWS_2', 1);
    define('ROWS_3', 2);
    define('ROWS_4', 3);
    define('ROWS_5', 4);
    define('ROWS_6', 5);
    define('ROWS_7', 6);
    define('ROWS_8', 7);
    define('ROWS_9', 8);
}

$heightforframes = 50;

// Init menu manager
if (!defined('NOREQUIREMENU')) {
    if (empty($user->socid)) {    // If internal user or not defined
        $conf->standard_menu = (!getDolGlobalString('MAIN_MENU_STANDARD_FORCED') ? (!getDolGlobalString('MAIN_MENU_STANDARD') ? 'eldy_menu.php' : $conf->global->MAIN_MENU_STANDARD) : $conf->global->MAIN_MENU_STANDARD_FORCED);
    } else {
        // If external user
        $conf->standard_menu = (!getDolGlobalString('MAIN_MENUFRONT_STANDARD_FORCED') ? (!getDolGlobalString('MAIN_MENUFRONT_STANDARD') ? 'eldy_menu.php' : $conf->global->MAIN_MENUFRONT_STANDARD) : $conf->global->MAIN_MENUFRONT_STANDARD_FORCED);
    }

    // Load the menu manager (only if not already done)
    $file_menu = $conf->standard_menu;
    if (GETPOST('menu', 'alpha')) {
        $file_menu = GETPOST('menu', 'alpha'); // example: menu=eldy_menu.php
    }
    if (!class_exists('MenuManager')) {
        $menufound = 0;
        $dirmenus = array_merge(["/core/menus/"], (array) $conf->modules_parts['menus']);
        foreach ($dirmenus as $dirmenu) {
            $menufound = dol_include_once($dirmenu . "standard/" . $file_menu);
            if (class_exists('MenuManager')) {
                break;
            }
        }
        if (!class_exists('MenuManager')) { // If failed to include, we try with standard eldy_menu.php
            dol_syslog("You define a menu manager '" . $file_menu . "' that can not be loaded.", LOG_WARNING);
            $file_menu = 'eldy_menu.php';
            include_once DOL_DOCUMENT_ROOT . "/../Dolibarr/Core/Menu/standard/" . $file_menu;
        }
    }
    $menumanager = new MenuManager($db, empty($user->socid) ? 0 : 1);
    $menumanager->loadMenu();
}

if (!empty(GETPOST('seteventmessages', 'alpha'))) {
    $message = GETPOST('seteventmessages', 'alpha');
    $messages = explode(',', $message);
    foreach ($messages as $key => $msg) {
        $tmp = explode(':', $msg);
        setEventMessages($tmp[0], null, !empty($tmp[1]) ? $tmp[1] : 'mesgs');
    }
}
