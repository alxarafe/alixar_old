<?php

$textobject = $langs->transnoentitiesnoconv("Members");

$help_url = 'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros|DE:Modul_Mitglieder';
llxHeader('', $langs->trans("MembersSetup"), $help_url);


$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans("MembersSetup"), $linkback, 'title_setup');


$head = member_admin_prepare_head();

print dol_get_fiche_head($head, 'attributes', $langs->trans("Members"), -1, 'user');

require DOL_DOCUMENT_ROOT . '/core/tpl/admin_extrafields_view.tpl.php';

print dol_get_fiche_end();


// Creation of an optional field
if ($action == 'create') {
    print '<div name="topofform"></div><br>';
    print load_fiche_titre($langs->trans('NewAttribute'));

    require DOL_DOCUMENT_ROOT . '/core/tpl/admin_extrafields_add.tpl.php';
}

// Edition of an optional field
if ($action == 'edit' && !empty($attrname)) {
    print '<div name="topofform"></div><br>';
    print load_fiche_titre($langs->trans("FieldEdition", $attrname));

    require DOL_DOCUMENT_ROOT . '/core/tpl/admin_extrafields_edit.tpl.php';
}

// End of page
llxFooter();