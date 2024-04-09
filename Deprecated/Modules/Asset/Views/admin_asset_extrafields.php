<?php

$help_url = '';
$page_name = "AssetSetup";
$textobject = $langs->transnoentitiesnoconv("Assets");

llxHeader('', $langs->trans("AssetSetup"), $help_url);


$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');


$head = assetAdminPrepareHead();

print dol_get_fiche_head($head, 'asset_extrafields', $langs->trans($page_name), -1, 'asset');

require DOL_DOCUMENT_ROOT . '/core/tpl/admin_extrafields_view.tpl.php';

print dol_get_fiche_end();


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
