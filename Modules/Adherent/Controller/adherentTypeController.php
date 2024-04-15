<?php

namespace Modules\Adherent\Controller;

use Alxarafe\Base\Controller;

class adherentTypeController extends Controller
{

    public function index()
    {
        $this->template = 'index';

        parent::index();
    }
}