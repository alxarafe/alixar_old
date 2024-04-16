<?php

namespace Modules\Adherent\Controller;

use Alxarafe\Base\Controller;

class AdherentTypeController extends Controller
{

    public function index()
    {
        switch ($this->action) {
            case 'login':
                $this->template = 'login';
                break;
            default:
                $this->template = 'index';
        }

        parent::index();
    }
}