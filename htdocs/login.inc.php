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

/*
 * Phase authentication / login
 */

$login = '';
$error = 0;
if (!defined('NOLOGIN')) {
    // $authmode lists the different method of identification to be tested in order of preference.
    // Example: 'http', 'dolibarr', 'ldap', 'http,forceuser', '...'

    if (defined('MAIN_AUTHENTICATION_MODE')) {
        $dolibarr_main_authentication = constant('MAIN_AUTHENTICATION_MODE');
    } else {
        // Authentication mode
        if (empty($dolibarr_main_authentication)) {
            $dolibarr_main_authentication = 'dolibarr';
        }
        // Authentication mode: forceuser
        if ($dolibarr_main_authentication == 'forceuser' && empty($dolibarr_auto_user)) {
            $dolibarr_auto_user = 'auto';
        }
    }
    // Set authmode
    $authmode = explode(',', $dolibarr_main_authentication);

    // No authentication mode
    if (!count($authmode)) {
        $langs->load('main');
        dol_print_error(null, $langs->trans("ErrorConfigParameterNotDefined", 'dolibarr_main_authentication'));
        exit;
    }

    // If login request was already post, we retrieve login from the session
    // Call module if not realized that his request.
    // At the end of this phase, the variable $login is defined.
    $resultFetchUser = '';
    $test = true;
    if (!isset($_SESSION["dol_login"])) {
        // It is not already authenticated and it requests the login / password
        include_once DOL_DOCUMENT_ROOT . '/core/lib/security2.lib.php';

        $dol_dst_observed = GETPOSTINT("dst_observed", 3);
        $dol_dst_first = GETPOSTINT("dst_first", 3);
        $dol_dst_second = GETPOSTINT("dst_second", 3);
        $dol_screenwidth = GETPOSTINT("screenwidth", 3);
        $dol_screenheight = GETPOSTINT("screenheight", 3);
        $dol_hide_topmenu = GETPOSTINT('dol_hide_topmenu', 3);
        $dol_hide_leftmenu = GETPOSTINT('dol_hide_leftmenu', 3);
        $dol_optimize_smallscreen = GETPOSTINT('dol_optimize_smallscreen', 3);
        $dol_no_mouse_hover = GETPOSTINT('dol_no_mouse_hover', 3);
        $dol_use_jmobile = GETPOSTINT('dol_use_jmobile', 3); // 0=default, 1=to say we use app from a webview app, 2=to say we use app from a webview app and keep ajax
        //dol_syslog("POST key=".join(array_keys($_POST),',').' value='.join($_POST,','));

        // If in demo mode, we check we go to home page through the public/demo/index.php page
        if (!empty($dolibarr_main_demo) && $_SERVER['PHP_SELF'] == DOL_URL_ROOT . '/index.php') {  // We ask index page
            if (empty($_SERVER['HTTP_REFERER']) || !preg_match('/public/', $_SERVER['HTTP_REFERER'])) {
                dol_syslog("Call index page from another url than demo page (call is done from page " . (empty($_SERVER['HTTP_REFERER']) ? '' : $_SERVER['HTTP_REFER']) . ")");
                $url = '';
                $url .= ($url ? '&' : '') . ($dol_hide_topmenu ? 'dol_hide_topmenu=' . $dol_hide_topmenu : '');
                $url .= ($url ? '&' : '') . ($dol_hide_leftmenu ? 'dol_hide_leftmenu=' . $dol_hide_leftmenu : '');
                $url .= ($url ? '&' : '') . ($dol_optimize_smallscreen ? 'dol_optimize_smallscreen=' . $dol_optimize_smallscreen : '');
                $url .= ($url ? '&' : '') . ($dol_no_mouse_hover ? 'dol_no_mouse_hover=' . $dol_no_mouse_hover : '');
                $url .= ($url ? '&' : '') . ($dol_use_jmobile ? 'dol_use_jmobile=' . $dol_use_jmobile : '');
                $url = DOL_URL_ROOT . '/public/demo/index.php' . ($url ? '?' . $url : '');
                header("Location: " . $url);
                exit;
            }
        }

        // Hooks for security access
        $action = '';
        $hookmanager->initHooks(['login']);
        $parameters = [];
        $reshook = $hookmanager->executeHooks('beforeLoginAuthentication', $parameters, $user, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook < 0) {
            $test = false;
            $error++;
        }

        // Verification security graphic code
        if ($test && GETPOST("username", "alpha", 2) && getDolGlobalString('MAIN_SECURITY_ENABLECAPTCHA') && !isset($_SESSION['dol_bypass_antispam'])) {
            $sessionkey = 'dol_antispam_value';
            $ok = (array_key_exists($sessionkey, $_SESSION) === true && (strtolower($_SESSION[$sessionkey]) === strtolower(GETPOST('code', 'restricthtml'))));

            // Check code
            if (!$ok) {
                dol_syslog('Bad value for code, connection refused', LOG_NOTICE);
                // Load translation files required by page
                $langs->loadLangs(['main', 'errors']);

                $_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("ErrorBadValueForCode");
                $test = false;

                // Call trigger for the "security events" log
                $user->context['audit'] = 'ErrorBadValueForCode - login=' . GETPOST("username", "alpha", 2);

                // Call trigger
                $result = $user->call_trigger('USER_LOGIN_FAILED', $user);
                if ($result < 0) {
                    $error++;
                }
                // End call triggers

                // Hooks on failed login
                $action = '';
                $hookmanager->initHooks(['login']);
                $parameters = ['dol_authmode' => $authmode, 'dol_loginmesg' => $_SESSION["dol_loginmesg"]];
                $reshook = $hookmanager->executeHooks('afterLoginFailed', $parameters, $user, $action); // Note that $action and $object may have been modified by some hooks
                if ($reshook < 0) {
                    $error++;
                }

                // Note: exit is done later
            }
        }

        $allowedmethodtopostusername = 3;
        if (defined('MAIN_AUTHENTICATION_POST_METHOD')) {
            $allowedmethodtopostusername = constant('MAIN_AUTHENTICATION_POST_METHOD'); // Note a value of 2 is not compatible with some authentication methods that put username as GET parameter
        }
        // TODO Remove use of $_COOKIE['login_dolibarr'] ? Replace $usertotest = with $usertotest = GETPOST("username", "alpha", $allowedmethodtopostusername);
        $usertotest = (!empty($_COOKIE['login_dolibarr']) ? preg_replace('/[^a-zA-Z0-9_@\-\.]/', '', $_COOKIE['login_dolibarr']) : GETPOST("username", "alpha", $allowedmethodtopostusername));
        $passwordtotest = GETPOST('password', 'none', $allowedmethodtopostusername);
        $entitytotest = (GETPOSTINT('entity') ? GETPOSTINT('entity') : (!empty($conf->entity) ? $conf->entity : 1));

        // Define if we received the correct data to go into the test of the login with the checkLoginPassEntity().
        $goontestloop = false;
        if (isset($_SERVER["REMOTE_USER"]) && in_array('http', $authmode)) {    // For http basic login test
            $goontestloop = true;
        }
        if ($dolibarr_main_authentication == 'forceuser' && !empty($dolibarr_auto_user)) {  // For automatic login with a forced user
            $goontestloop = true;
        }
        if (GETPOST("username", "alpha", $allowedmethodtopostusername)) {   // For posting the login form
            $goontestloop = true;
        }
        if (GETPOST('openid_mode', 'alpha', 1)) {   // For openid_connect ?
            $goontestloop = true;
        }
        if (GETPOST('beforeoauthloginredirect') || GETPOST('afteroauthloginreturn')) {  // For oauth login
            $goontestloop = true;
        }
        if (!empty($_COOKIE['login_dolibarr'])) {   // TODO For ? Remove this ?
            $goontestloop = true;
        }

        if (!is_object($langs)) { // This can occurs when calling page with NOREQUIRETRAN defined, however we need langs for error messages.
            include_once DOL_DOCUMENT_ROOT . '/core/class/translate.class.php';
            $langs = new Translate("", $conf);
            $langcode = (GETPOST('lang', 'aZ09', 1) ? GETPOST('lang', 'aZ09', 1) : getDolGlobalString('MAIN_LANG_DEFAULT', 'auto'));
            if (defined('MAIN_LANG_DEFAULT')) {
                $langcode = constant('MAIN_LANG_DEFAULT');
            }
            $langs->setDefaultLang($langcode);
        }

        // Validation of login/pass/entity
        // If ok, the variable login will be returned
        // If error, we will put error message in session under the name dol_loginmesg
        if ($test && $goontestloop && (GETPOST('actionlogin', 'aZ09') == 'login' || $dolibarr_main_authentication != 'dolibarr')) {
            // Loop on each test mode defined into $authmode
            // $authmode is an array for example: array('0'=>'dolibarr', '1'=>'googleoauth');
            $oauthmodetotestarray = ['google'];
            foreach ($oauthmodetotestarray as $oauthmodetotest) {
                if (in_array($oauthmodetotest . 'oauth', $authmode) && GETPOST('beforeoauthloginredirect') != $oauthmodetotest) {
                    // If we did not click on the link to use OAuth authentication, we do not try it.
                    dol_syslog("User did not click on link for OAuth so we disable check using googleoauth");
                    foreach ($authmode as $tmpkey => $tmpval) {
                        if ($tmpval == $oauthmodetotest . 'oauth') {
                            unset($authmode[$tmpkey]);
                            break;
                        }
                    }
                }
            }

            $login = checkLoginPassEntity($usertotest, $passwordtotest, $entitytotest, $authmode);
            if ($login === '--bad-login-validity--') {
                $login = '';
            }

            $dol_authmode = '';

            if ($login) {
                $dol_authmode = $conf->authmode; // This properties is defined only when logged, to say what mode was successfully used
                $dol_tz = empty($_POST["tz"]) ? (empty($_SESSION["tz"]) ? '' : $_SESSION["tz"]) : $_POST["tz"];
                $dol_tz_string = empty($_POST["tz_string"]) ? (empty($_SESSION["tz_string"]) ? '' : $_SESSION["tz_string"]) : $_POST["tz_string"];
                $dol_tz_string = preg_replace('/\s*\(.+\)$/', '', $dol_tz_string);
                $dol_tz_string = preg_replace('/,/', '/', $dol_tz_string);
                $dol_tz_string = preg_replace('/\s/', '_', $dol_tz_string);
                $dol_dst = 0;
                // Keep $_POST here. Do not use GETPOSTISSET
                $dol_dst_first = empty($_POST["dst_first"]) ? (empty($_SESSION["dst_first"]) ? '' : $_SESSION["dst_first"]) : $_POST["dst_first"];
                $dol_dst_second = empty($_POST["dst_second"]) ? (empty($_SESSION["dst_second"]) ? '' : $_SESSION["dst_second"]) : $_POST["dst_second"];
                if ($dol_dst_first && $dol_dst_second) {
                    include_once BASE_PATH . '/../Dolibarr/Lib/Date.php';
                    $datenow = dol_now();
                    $datefirst = dol_stringtotime($dol_dst_first);
                    $datesecond = dol_stringtotime($dol_dst_second);
                    if ($datenow >= $datefirst && $datenow < $datesecond) {
                        $dol_dst = 1;
                    }
                }
                $dol_screenheight = empty($_POST["screenheight"]) ? (empty($_SESSION["dol_screenheight"]) ? '' : $_SESSION["dol_screenheight"]) : $_POST["screenheight"];
                $dol_screenwidth = empty($_POST["screenwidth"]) ? (empty($_SESSION["dol_screenwidth"]) ? '' : $_SESSION["dol_screenwidth"]) : $_POST["screenwidth"];
                //print $datefirst.'-'.$datesecond.'-'.$datenow.'-'.$dol_tz.'-'.$dol_tzstring.'-'.$dol_dst.'-'.sdol_screenheight.'-'.sdol_screenwidth; exit;
            }

            if (!$login) {
                dol_syslog('Bad password, connection refused (see a previous notice message for more info)', LOG_NOTICE);
                // Load translation files required by page
                $langs->loadLangs(['main', 'errors']);

                // Bad password. No authmode has found a good password.
                // We set a generic message if not defined inside function checkLoginPassEntity or subfunctions
                if (empty($_SESSION["dol_loginmesg"])) {
                    $_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("ErrorBadLoginPassword");
                }

                // Call trigger for the "security events" log
                $user->context['audit'] = $langs->trans("ErrorBadLoginPassword") . ' - login=' . GETPOST("username", "alpha", 2);

                // Call trigger
                $result = $user->call_trigger('USER_LOGIN_FAILED', $user);
                if ($result < 0) {
                    $error++;
                }
                // End call triggers

                // Hooks on failed login
                $action = '';
                $hookmanager->initHooks(['login']);
                $parameters = ['dol_authmode' => $dol_authmode, 'dol_loginmesg' => $_SESSION["dol_loginmesg"]];
                $reshook = $hookmanager->executeHooks('afterLoginFailed', $parameters, $user, $action); // Note that $action and $object may have been modified by some hooks
                if ($reshook < 0) {
                    $error++;
                }

                // Note: exit is done in next chapter
            }
        }

        // End test login / passwords
        if (!$login || (in_array('ldap', $authmode) && empty($passwordtotest))) {   // With LDAP we refused empty password because some LDAP are "opened" for anonymous access so connection is a success.
            // No data to test login, so we show the login page.
            dol_syslog("--- Access to " . (empty($_SERVER["REQUEST_METHOD"]) ? '' : $_SERVER["REQUEST_METHOD"] . ' ') . $_SERVER['PHP_SELF'] . " - action=" . GETPOST('action', 'aZ09') . " - actionlogin=" . GETPOST('actionlogin', 'aZ09') . " - showing the login form and exit", LOG_NOTICE);
            if (defined('NOREDIRECTBYMAINTOLOGIN')) {
                // When used with NOREDIRECTBYMAINTOLOGIN set, the http header must already be set when including the main.
                // See example with selectsearchbox.php. This case is reserved for the selectesearchbox.php so we can
                // report a message to ask to login when search ajax component is used after a timeout.
                //top_httphead();
                return 'ERROR_NOT_LOGGED';
            } else {
                if (!empty($_SERVER["HTTP_USER_AGENT"]) && $_SERVER["HTTP_USER_AGENT"] == 'securitytest') {
                    http_response_code(401); // It makes easier to understand if session was broken during security tests
                }
                dol_loginfunction($langs, $conf, (!empty($mysoc) ? $mysoc : ''));   // This include http headers
            }
            exit;
        }

        $resultFetchUser = $user->fetch('', $login, '', 1, ($entitytotest > 0 ? $entitytotest : -1)); // value for $login was retrieved previously when checking password.
        if ($resultFetchUser <= 0 || $user->isNotIntoValidityDateRange()) {
            dol_syslog('User not found or not valid, connection refused');
            session_destroy();
            session_set_cookie_params(0, '/', null, (empty($dolibarr_main_force_https) ? false : true), true); // Add tag secure and httponly on session cookie
            session_name($sessionname);
            session_start();

            if ($resultFetchUser == 0) {
                // Load translation files required by page
                $langs->loadLangs(['main', 'errors']);

                $_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("ErrorCantLoadUserFromDolibarrDatabase", $login);

                $user->context['audit'] = 'ErrorCantLoadUserFromDolibarrDatabase - login=' . $login;
            } elseif ($resultFetchUser < 0) {
                $_SESSION["dol_loginmesg"] = $user->error;

                $user->context['audit'] = $user->error;
            } else {
                // Load translation files required by the page
                $langs->loadLangs(['main', 'errors']);

                $_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("ErrorLoginDateValidity");

                $user->context['audit'] = $langs->trans("ErrorLoginDateValidity") . ' - login=' . $login;
            }

            // Call trigger
            $result = $user->call_trigger('USER_LOGIN_FAILED', $user);
            if ($result < 0) {
                $error++;
            }
            // End call triggers


            // Hooks on failed login
            $action = '';
            $hookmanager->initHooks(['login']);
            $parameters = ['dol_authmode' => $dol_authmode, 'dol_loginmesg' => $_SESSION["dol_loginmesg"]];
            $reshook = $hookmanager->executeHooks('afterLoginFailed', $parameters, $user, $action); // Note that $action and $object may have been modified by some hooks
            if ($reshook < 0) {
                $error++;
            }

            $paramsurl = [];
            if (GETPOSTINT('textbrowser')) {
                $paramsurl[] = 'textbrowser=' . GETPOSTINT('textbrowser');
            }
            if (GETPOSTINT('nojs')) {
                $paramsurl[] = 'nojs=' . GETPOSTINT('nojs');
            }
            if (GETPOST('lang', 'aZ09')) {
                $paramsurl[] = 'lang=' . GETPOST('lang', 'aZ09');
            }
            header('Location: ' . DOL_URL_ROOT . '/index.php' . (count($paramsurl) ? '?' . implode('&', $paramsurl) : ''));
            exit;
        } else {
            // User is loaded, we may need to change language for him according to its choice
            if (!empty($user->conf->MAIN_LANG_DEFAULT)) {
                $langs->setDefaultLang($user->conf->MAIN_LANG_DEFAULT);
            }
        }
    } else {
        // We are already into an authenticated session
        $login = $_SESSION["dol_login"];
        $entity = isset($_SESSION["dol_entity"]) ? $_SESSION["dol_entity"] : 0;
        dol_syslog("- This is an already logged session. _SESSION['dol_login']=" . $login . " _SESSION['dol_entity']=" . $entity, LOG_DEBUG);

        $resultFetchUser = $user->fetch('', $login, '', 1, ($entity > 0 ? $entity : -1));

        //var_dump(dol_print_date($user->flagdelsessionsbefore, 'dayhour', 'gmt')." ".dol_print_date($_SESSION["dol_logindate"], 'dayhour', 'gmt'));

        if (
            $resultFetchUser <= 0
            || ($user->flagdelsessionsbefore && !empty($_SESSION["dol_logindate"]) && $user->flagdelsessionsbefore > $_SESSION["dol_logindate"])
            || ($user->status != $user::STATUS_ENABLED)
            || ($user->isNotIntoValidityDateRange())
        ) {
            if ($resultFetchUser <= 0) {
                // Account has been removed after login
                dol_syslog("Can't load user even if session logged. _SESSION['dol_login']=" . $login, LOG_WARNING);
            } elseif ($user->flagdelsessionsbefore && !empty($_SESSION["dol_logindate"]) && $user->flagdelsessionsbefore > $_SESSION["dol_logindate"]) {
                // Session is no more valid
                dol_syslog("The user has a date for session invalidation = " . $user->flagdelsessionsbefore . " and a session date = " . $_SESSION["dol_logindate"] . ". We must invalidate its sessions.");
            } elseif ($user->status != $user::STATUS_ENABLED) {
                // User is not enabled
                dol_syslog("The user login is disabled");
            } else {
                // User validity dates are no more valid
                dol_syslog("The user login has a validity between [" . $user->datestartvalidity . " and " . $user->dateendvalidity . "], current date is " . dol_now());
            }
            session_destroy();
            session_set_cookie_params(0, '/', null, (empty($dolibarr_main_force_https) ? false : true), true); // Add tag secure and httponly on session cookie
            session_name($sessionname);
            session_start();

            if ($resultFetchUser == 0) {
                $langs->loadLangs(['main', 'errors']);

                $_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("ErrorCantLoadUserFromDolibarrDatabase", $login);

                $user->context['audit'] = 'ErrorCantLoadUserFromDolibarrDatabase - login=' . $login;
            } elseif ($resultFetchUser < 0) {
                $_SESSION["dol_loginmesg"] = $user->error;

                $user->context['audit'] = $user->error;
            } else {
                $langs->loadLangs(['main', 'errors']);

                $_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("ErrorSessionInvalidatedAfterPasswordChange");

                $user->context['audit'] = 'ErrorUserSessionWasInvalidated - login=' . $login;
            }

            // Call trigger
            $result = $user->call_trigger('USER_LOGIN_FAILED', $user);
            if ($result < 0) {
                $error++;
            }
            // End call triggers

            // Hooks on failed login
            $action = '';
            $hookmanager->initHooks(['login']);
            $parameters = ['dol_authmode' => (isset($dol_authmode) ? $dol_authmode : ''), 'dol_loginmesg' => $_SESSION["dol_loginmesg"]];
            $reshook = $hookmanager->executeHooks('afterLoginFailed', $parameters, $user, $action); // Note that $action and $object may have been modified by some hooks
            if ($reshook < 0) {
                $error++;
            }

            $paramsurl = [];
            if (GETPOSTINT('textbrowser')) {
                $paramsurl[] = 'textbrowser=' . GETPOSTINT('textbrowser');
            }
            if (GETPOSTINT('nojs')) {
                $paramsurl[] = 'nojs=' . GETPOSTINT('nojs');
            }
            if (GETPOST('lang', 'aZ09')) {
                $paramsurl[] = 'lang=' . GETPOST('lang', 'aZ09');
            }

            header('Location: ' . DOL_URL_ROOT . '/index.php' . (count($paramsurl) ? '?' . implode('&', $paramsurl) : ''));
            exit;
        } else {
            // Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
            $hookmanager->initHooks(['main']);

            // Code for search criteria persistence.
            if (!empty($_GET['save_lastsearch_values']) && !empty($_SERVER["HTTP_REFERER"])) {    // We must use $_GET here
                $relativepathstring = preg_replace('/\?.*$/', '', $_SERVER["HTTP_REFERER"]);
                $relativepathstring = preg_replace('/^https?:\/\/[^\/]*/', '', $relativepathstring); // Get full path except host server
                // Clean $relativepathstring
                if (constant('DOL_URL_ROOT')) {
                    $relativepathstring = preg_replace('/^' . preg_quote(constant('DOL_URL_ROOT'), '/') . '/', '', $relativepathstring);
                }
                $relativepathstring = preg_replace('/^\//', '', $relativepathstring);
                $relativepathstring = preg_replace('/^custom\//', '', $relativepathstring);
                //var_dump($relativepathstring);

                // We click on a link that leave a page we have to save search criteria, contextpage, limit and page and mode. We save them from tmp to no tmp
                if (!empty($_SESSION['lastsearch_values_tmp_' . $relativepathstring])) {
                    $_SESSION['lastsearch_values_' . $relativepathstring] = $_SESSION['lastsearch_values_tmp_' . $relativepathstring];
                    unset($_SESSION['lastsearch_values_tmp_' . $relativepathstring]);
                }
                if (!empty($_SESSION['lastsearch_contextpage_tmp_' . $relativepathstring])) {
                    $_SESSION['lastsearch_contextpage_' . $relativepathstring] = $_SESSION['lastsearch_contextpage_tmp_' . $relativepathstring];
                    unset($_SESSION['lastsearch_contextpage_tmp_' . $relativepathstring]);
                }
                if (!empty($_SESSION['lastsearch_limit_tmp_' . $relativepathstring]) && $_SESSION['lastsearch_limit_tmp_' . $relativepathstring] != $conf->liste_limit) {
                    $_SESSION['lastsearch_limit_' . $relativepathstring] = $_SESSION['lastsearch_limit_tmp_' . $relativepathstring];
                    unset($_SESSION['lastsearch_limit_tmp_' . $relativepathstring]);
                }
                if (!empty($_SESSION['lastsearch_page_tmp_' . $relativepathstring]) && $_SESSION['lastsearch_page_tmp_' . $relativepathstring] > 0) {
                    $_SESSION['lastsearch_page_' . $relativepathstring] = $_SESSION['lastsearch_page_tmp_' . $relativepathstring];
                    unset($_SESSION['lastsearch_page_tmp_' . $relativepathstring]);
                }
                if (!empty($_SESSION['lastsearch_mode_tmp_' . $relativepathstring])) {
                    $_SESSION['lastsearch_mode_' . $relativepathstring] = $_SESSION['lastsearch_mode_tmp_' . $relativepathstring];
                    unset($_SESSION['lastsearch_mode_tmp_' . $relativepathstring]);
                }
            }

            if (!empty($_GET['save_pageforbacktolist']) && !empty($_SERVER["HTTP_REFERER"])) {    // We must use $_GET here
                if (empty($_SESSION['pageforbacktolist'])) {
                    $pageforbacktolistarray = [];
                } else {
                    $pageforbacktolistarray = $_SESSION['pageforbacktolist'];
                }
                $tmparray = explode(':', $_GET['save_pageforbacktolist'], 2);
                if (!empty($tmparray[0]) && !empty($tmparray[1])) {
                    $pageforbacktolistarray[$tmparray[0]] = $tmparray[1];
                    $_SESSION['pageforbacktolist'] = $pageforbacktolistarray;
                }
            }

            $action = '';
            $parameters = [];
            $reshook = $hookmanager->executeHooks('updateSession', $parameters, $user, $action);
            if ($reshook < 0) {
                setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
            }
        }
    }

    // Is it a new session that has started ?
    // If we are here, this means authentication was successful.
    if (!isset($_SESSION["dol_login"])) {
        // New session for this login has started.
        $error = 0;

        // Store value into session (values always stored)
        $_SESSION["dol_login"] = $user->login;
        $_SESSION["dol_logindate"] = dol_now('gmt');
        $_SESSION["dol_authmode"] = isset($dol_authmode) ? $dol_authmode : '';
        $_SESSION["dol_tz"] = isset($dol_tz) ? $dol_tz : '';
        $_SESSION["dol_tz_string"] = isset($dol_tz_string) ? $dol_tz_string : '';
        $_SESSION["dol_dst"] = isset($dol_dst) ? $dol_dst : '';
        $_SESSION["dol_dst_observed"] = isset($dol_dst_observed) ? $dol_dst_observed : '';
        $_SESSION["dol_dst_first"] = isset($dol_dst_first) ? $dol_dst_first : '';
        $_SESSION["dol_dst_second"] = isset($dol_dst_second) ? $dol_dst_second : '';
        $_SESSION["dol_screenwidth"] = isset($dol_screenwidth) ? $dol_screenwidth : '';
        $_SESSION["dol_screenheight"] = isset($dol_screenheight) ? $dol_screenheight : '';
        $_SESSION["dol_company"] = getDolGlobalString("MAIN_INFO_SOCIETE_NOM");
        $_SESSION["dol_entity"] = $conf->entity;
        // Store value into session (values stored only if defined)
        if (!empty($dol_hide_topmenu)) {
            $_SESSION['dol_hide_topmenu'] = $dol_hide_topmenu;
        }
        if (!empty($dol_hide_leftmenu)) {
            $_SESSION['dol_hide_leftmenu'] = $dol_hide_leftmenu;
        }
        if (!empty($dol_optimize_smallscreen)) {
            $_SESSION['dol_optimize_smallscreen'] = $dol_optimize_smallscreen;
        }
        if (!empty($dol_no_mouse_hover)) {
            $_SESSION['dol_no_mouse_hover'] = $dol_no_mouse_hover;
        }
        if (!empty($dol_use_jmobile)) {
            $_SESSION['dol_use_jmobile'] = $dol_use_jmobile;
        }

        dol_syslog("This is a new started user session. _SESSION['dol_login']=" . $_SESSION["dol_login"] . " Session id=" . session_id());

        $db->begin();

        $user->update_last_login_date();

        $loginfo = 'TZ=' . $_SESSION["dol_tz"] . ';TZString=' . $_SESSION["dol_tz_string"] . ';Screen=' . $_SESSION["dol_screenwidth"] . 'x' . $_SESSION["dol_screenheight"];
        $loginfo .= ' - authmode=' . $dol_authmode . ' - entity=' . $conf->entity;

        // Call triggers for the "security events" log
        $user->context['audit'] = $loginfo;
        $user->context['authentication_method'] = $dol_authmode;

        // Call trigger
        $result = $user->call_trigger('USER_LOGIN', $user);
        if ($result < 0) {
            $error++;
        }
        // End call triggers

        // Hooks on successful login
        $action = '';
        $hookmanager->initHooks(['login']);
        $parameters = ['dol_authmode' => $dol_authmode, 'dol_loginfo' => $loginfo];
        $reshook = $hookmanager->executeHooks('afterLogin', $parameters, $user, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook < 0) {
            $error++;
        }

        if ($error) {
            $db->rollback();
            session_destroy();
            dol_print_error($db, 'Error in some triggers USER_LOGIN or in some hooks afterLogin');
            exit;
        } else {
            $db->commit();
        }

        // Change landing page if defined.
        $landingpage = (empty($user->conf->MAIN_LANDING_PAGE) ? (!getDolGlobalString('MAIN_LANDING_PAGE') ? '' : $conf->global->MAIN_LANDING_PAGE) : $user->conf->MAIN_LANDING_PAGE);
        if (!empty($landingpage)) {    // Example: /index.php
            $newpath = dol_buildpath($landingpage, 1);
            if ($_SERVER['PHP_SELF'] != $newpath) {   // not already on landing page (avoid infinite loop)
                header('Location: ' . $newpath);
                exit;
            }
        }
    }

    // If user admin, we force the rights-based modules
    if ($user->admin) {
        $user->rights->user->user->lire = 1;
        $user->rights->user->user->creer = 1;
        $user->rights->user->user->password = 1;
        $user->rights->user->user->supprimer = 1;
        $user->rights->user->self->creer = 1;
        $user->rights->user->self->password = 1;

        //Required if advanced permissions are used with MAIN_USE_ADVANCED_PERMS
        if (getDolGlobalString('MAIN_USE_ADVANCED_PERMS')) {
            if (!$user->hasRight('user', 'user_advance')) {
                $user->rights->user->user_advance = new stdClass(); // To avoid warnings
            }
            if (!$user->hasRight('user', 'self_advance')) {
                $user->rights->user->self_advance = new stdClass(); // To avoid warnings
            }
            if (!$user->hasRight('user', 'group_advance')) {
                $user->rights->user->group_advance = new stdClass(); // To avoid warnings
            }

            $user->rights->user->user_advance->readperms = 1;
            $user->rights->user->user_advance->write = 1;
            $user->rights->user->self_advance->readperms = 1;
            $user->rights->user->self_advance->writeperms = 1;
            $user->rights->user->group_advance->read = 1;
            $user->rights->user->group_advance->readperms = 1;
            $user->rights->user->group_advance->write = 1;
            $user->rights->user->group_advance->delete = 1;
        }
    }

    /*
     * Overwrite some configs globals (try to avoid this and have code to use instead $user->conf->xxx)
     */

    // Set liste_limit
    if (isset($user->conf->MAIN_SIZE_LISTE_LIMIT)) {
        $conf->liste_limit = $user->conf->MAIN_SIZE_LISTE_LIMIT; // Can be 0
    }
    if (isset($user->conf->PRODUIT_LIMIT_SIZE)) {
        $conf->product->limit_size = $user->conf->PRODUIT_LIMIT_SIZE; // Can be 0
    }

    // Replace conf->css by personalized value if theme not forced
    if (!getDolGlobalString('MAIN_FORCETHEME') && !empty($user->conf->MAIN_THEME)) {
        $conf->theme = $user->conf->MAIN_THEME;
        $conf->css = "/theme/" . $conf->theme . "/style.css.php";
    }
} else {
    // We may have NOLOGIN set, but NOREQUIREUSER not
    if (!empty($user) && method_exists($user, 'loadDefaultValues') && !defined('NODEFAULTVALUES')) {
        $user->loadDefaultValues();     // Load default values for everybody (works even if $user->id = 0
    }
}
