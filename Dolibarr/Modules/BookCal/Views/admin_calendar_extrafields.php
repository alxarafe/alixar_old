<?php

$textobject = $langs->transnoentitiesnoconv("Calendar");

$help_url = '';
$page_name = "BookCalSetup";

llxHeader('', $langs->trans("BookCalSetup"), $help_url);


$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');


$head = bookcalAdminPrepareHead();

print dol_get_fiche_head($head, 'calendar_extrafields', $langs->trans($page_name), -1, 'agenda');

require DOL_DOCUMENT_ROOT . '/core/tpl/admin_extrafields_view.tpl.php';

print dol_get_fiche_end();


// Buttons
if ((float) DOL_VERSION < 17) { // On v17+, the "New Attribute" button is included into tpl.
    if ($action != 'create' && $action != 'edit') {
        print '<div class="tabsAction">';
        print '<a class="butAction reposition" href="' . $_SERVER['PHP_SELF'] . '?action=create">' . $langs->trans("NewAttribute") . '</a>';
        print "</div>";
    }
}


/*
 * Creation of an optional field
 */
if ($action == 'create') {
    print '<br><div id="newattrib"></div>';
    print load_fiche_titre($langs->trans('NewAttribute'));

    require DOL_DOCUMENT_ROOT . '/core/tpl/admin_extrafields_add.tpl.php';
}

/*
 * Edition of an optional field
 */
if ($action == 'edit' && !empty($attrname)) {
    print "<br>";
    print load_fiche_titre($langs->trans("FieldEdition", $attrname));

    require DOL_DOCUMENT_ROOT . '/core/tpl/admin_extrafields_edit.tpl.php';
}

// End of page
llxFooter();
