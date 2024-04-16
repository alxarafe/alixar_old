<?php

namespace Modules\Adherent\Controller;

use Alxarafe\Base\Controller;

class AdherentTypeController extends Controller
{
    public function index(bool $executeActions = true): bool
    {
        switch ($this->action) {
            case 'logina':
                $this->template = 'theme/adminlte/auth/login';
                break;
            case 'loginm':
                $this->template = 'theme/md/auth/login';
                break;
            case 'logine':
                $this->template = 'theme/eldy/auth/login';
                break;
            case 'lista':
                $this->template = 'theme/adminlte/page/adherent/type_list';
                break;
            case 'listm':
                $this->template = 'theme/md/page/adherent/type_list';
                break;
            case 'liste':
                $this->template = 'theme/eldy/page/adherent/type_list';
                break;
            case 'edita':
                $this->template = 'theme/adminlte/page/adherent/type_edit';
                break;
            case 'editm':
                $this->template = 'theme/md/page/adherent/type_edit';
                break;
            case 'edite':
                $this->template = 'theme/md/page/adherent/type_edit';
                break;
//            default:
//                $this->template = 'page/adherent/type_edit';
        }
        return parent::index();
    }
}
