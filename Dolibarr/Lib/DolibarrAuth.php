<?php

namespace DoliLib;

use DoliCore\Base\Config;
use DoliCore\Tools\Load;

class DolibarrAuth
{
    private const COOKIE_NAME = 'dol_login';
    private const COOKIE_USER = self::COOKIE_NAME . '_user';
    private const COOKIE_EXPIRE_TIME = 30 * 86400; // 30 days
    private const COOKIE_SAMESITE = 'Strict';

    public static $user = null;

    public static function isLogged()
    {
        $userId = FILTER_INPUT(INPUT_COOKIE, self::COOKIE_USER);
        $token = FILTER_INPUT(INPUT_COOKIE, self::COOKIE_NAME);
        if (empty($token)) {
            return false;
        }

        self::$user = Load::getUser();
        $result = self::$user->fetch($userId, '', '', 1, 1);
        if ($result <= 0) {
            return false;
        }

        return self::checkToken(self::$user->login, $token);
    }

    private static function checkToken($username, $token)
    {
        $token_file = self::getTokenFilename($username);
        if (!file_exists($token_file)) {
            return false;
        }
        $stored_token = file_get_contents($token_file);
        return $token === $stored_token;
    }

    private static function getTokenFilename($username)
    {
        $tokens_path = realpath(BASE_PATH . '/..') . '/tmp/tokens/';
        if (!is_dir($tokens_path) && !mkdir($tokens_path, 0777, true) && !is_dir($tokens_path)) {
            die('Could not create tokens directory:' . $tokens_path);
        }

        return $tokens_path . md5($username) . '.token';
    }

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
        $result = $user->fetch('', $username, '', 1, ($entitytotest > 0 ? $entitytotest : -1));
        if ($result <= 0) {
            return false;
        }

        $cookie_options = [
            'expires' => time() + self::COOKIE_EXPIRE_TIME,
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'],
            'secure' => true, // Ensure the cookie is sent over HTTPS only
            'httponly' => true, // Prevent JavaScript from accessing the cookie
            'samesite' => self::COOKIE_SAMESITE, // Mitigate CSRF attacks
        ];

        $token = self::generateToken();

        // Set the user ID cookie securely
        setcookie(self::COOKIE_USER, $user->id, $cookie_options);

        // Set the authentication token cookie securely
        setcookie(self::COOKIE_NAME, $token, $cookie_options);

        if (!self::setToken($username, $token)) {
            die('Can`t write to token for ' . $username);
            return false;
        }

        return true;
    }

    private static function generateToken($length = 32)
    {
        return bin2hex(random_bytes($length));
    }

    private static function setToken($username, $token)
    {
        $token_file = self::getTokenFilename($username);
        return file_put_contents($token_file, $token) > 0;
    }

}
