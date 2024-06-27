<?php

namespace DoliLib;

use DoliCore\Base\Config;
use DoliCore\Tools\Load;

class DolibarrAuth
{
    public static function login($username, $password, $entity = 1)
    {
        $conf = Config::getConfig();
        $mode = $conf->security->authentication_method ?? 'dolibarr';
        $authmode = explode(',', $mode);
        if (!self::checkLogin($authmode, $username, $password, $entity)) {
            return false;
        }

        return static::setSession($username);
    }

    private static function checkLogin($authmode, $user, $pass, $entity): bool
    {
        foreach ($authmode as $mode) {
            $method = realpath(BASE_PATH . '/../Dolibarr/Core/Login') . '/functions_' . $mode . '.php';
            if (!file_exists($method)) {
                continue;
            }
            $function = 'check_user_password_' . $mode;
            require_once $method;
            if ($function($user, $pass, $entity)) {
                return true;
            }
        }

        return false;
    }

    public static function setSession($username, $entitytotest = 1)
    {

        $user = Load::getUser();
        $result = $user->fetch('', $username, '', 1, ($entitytotest > 0 ? $entitytotest : -1)); // value for $login was retrieved previously when checking password.
        if ($result <= 0) {
            return false;
        }

        // Store value into session (values always stored)
        $_SESSION['dol_login'] = $user->login;
        /*
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
        */

        return true;
    }
}
