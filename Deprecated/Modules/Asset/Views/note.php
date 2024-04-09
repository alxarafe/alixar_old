<?php

$form = new Form($db);

$help_url = '';
llxHeader('', $langs->trans('Asset'), $help_url, '', 0, 0, '', '', '', 'mod-asset page-card_notes');

if ($id > 0 || !empty($ref)) {
    $object->fetch_thirdparty();

    $head = assetPrepareHead($object);

    print dol_get_fiche_head($head, 'note', $langs->trans("Asset"), -1, $object->picto);

    // Object card
    // ------------------------------------------------------------
    $linkback = '<a href="' . DOL_URL_ROOT . '/asset/list.php?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

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
