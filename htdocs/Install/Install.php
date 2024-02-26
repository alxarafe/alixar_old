<?php

namespace Alixar\Install;

use Alxarafe\Base\BasicController;
use Alxarafe\LibClass\FormAdmin;

class Install extends BasicController
{
    public function __construct()
    {
        parent::__construct();

        $this->template = 'install/install';
        $this->lang->loadLangs(['main', 'admin', 'install']);
    }

    public function noAction(): bool
    {
        $form = new FormAdmin(null);
        $this->htmlComboLanguages = $form->select_language('auto', 'selectlang', 1, 0, 0, 1);

        return true;
    }

    public function checkAction(): bool
    {
        if (parent::checkAction()) {
            return true;
        }

        switch ($this->action) {
            case 'checked':
                dd($_POST);
                return true;
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