<?php

namespace Alxarafe\Lib;

class Auth
{
    protected $auth;

    public function __construct(PHPAuth $auth)
    {
        $this->auth = $auth;
    }

    public static function login($email, $password)
    {
        /**
         * TODO: This is a test. It will be checked against a user database.
         */
        setcookie('login_alixar', $email, time() + 3600);
        return ($email === 'user') && ($password === 'password');
    }

    public static function isLogged()
    {
        /**
         * TODO: This is a test.
         */
        return $_COOKIE['login_alixar'];
    }

    public function changePassword($currentPassword, $newPassword)
    {
        $uid = $this->auth->getCurrentUID();
        return $this->auth->changePassword($uid, $currentPassword, $newPassword);
    }

    public function sendEmail($email, $subject, $body)
    {
        // Implement email sending logic here, possibly using a separate email sending class or library
    }

    public function logout()
    {
        return $this->auth->logout();
    }
}
