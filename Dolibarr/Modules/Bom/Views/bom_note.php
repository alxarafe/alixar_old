<?php

use DoliCore\Form\Form;

$form = new Form($db);

$title = $langs->trans('BillOfMaterials');

$help_url = 'EN:Module_BOM';

llxHeader('', $title, $help_url);

if ($id > 0 || !empty($ref)) {
    $object->fetch_thirdparty();

    $head = bomPrepareHead($object);

    print dol_get_fiche_head($head, 'note', $langs->trans("BillOfMaterials"), -1, 'bom');

    // Object card
    // ------------------------------------------------------------
    $linkback = '<a href="' . DOL_URL_ROOT . '/bom/bom_list.php?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

    $morehtmlref = '<div class="refidno">';

    $morehtmlref .= '</div>';


    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


    print '<div class="fichecenter">';
    print '<div class="underbanner clearboth"></div>';


    $cssclass = "titlefield";
    include DOL_DOCUMENT_ROOT . '/core/tpl/notes.tpl.php';

    print '</div>';

    print dol_get_fiche_end();
}

// End of page
llxFooter();
