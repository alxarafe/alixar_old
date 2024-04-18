<?php

namespace Alxarafe\Lib;

class Auth
{
    protected $auth;

    public function __construct(PHPAuth $auth)
    {
        $this->auth = $auth;
    }

    public function login($email, $password)
    {
        return $this->auth->login($email, $password);
    }

    public function isLogged()
    {
        return $this->auth->isLogged();
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
