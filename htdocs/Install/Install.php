<?php

namespace Alixar\Install;

use Alxarafe\Base\BasicController;
use Alxarafe\LibClass\FormAdmin;

class Install extends BasicController
{
    public function __construct()
    {
        $this->template = 'install/install';
    }

    public function body()
    {
        $form = new FormAdmin(null);
        $this->htmlComboLanguages = $form->select_language('auto', 'selectlang', 1, 0, 0, 1);
    }

}