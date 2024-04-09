<?php

$title = $langs->trans("Fiscalyear") . " - " . $langs->trans("Info");

$help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';

llxHeader('', $title, $help_url);

if ($id) {
    $object = new Fiscalyear($db);
    $object->fetch($id);
    $object->info($id);

    $head = fiscalyear_prepare_head($object);

    print dol_get_fiche_head($head, 'info', $langs->trans("Fiscalyear"), 0, 'calendar');

    print '<table width="100%"><tr><td>';
    dol_print_object_info($object);
    print '</td></tr></table>';

    print '</div>';
}

// End of page
llxFooter();
