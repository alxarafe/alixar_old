<?php

$form = new Form($db);

$help_url = '';
$title = "AiSetup";

llxHeader('', $langs->trans($title), $help_url);

// Subheader
$linkback = '<a href="' . ($backtopage ? $backtopage : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($title), $linkback, 'title_setup');

// Configuration header
$head = aiAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $langs->trans($title), -1, "fa-microchip");


if ($action == 'edit') {
    print $formSetup->generateOutput(true);
    print '<br>';
} elseif (!empty($formSetup->items)) {
    print $formSetup->generateOutput();
    print '<div class="tabsAction">';
    print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=edit&token=' . newToken() . '">' . $langs->trans("Modify") . '</a>';
    print '</div>';
} else {
    print '<br>' . $langs->trans("NothingToSetup");
}


if (empty($setupnotempty)) {
    print '<br>' . $langs->trans("NothingToSetup");
}

// Page end
print dol_get_fiche_end();

llxFooter();
