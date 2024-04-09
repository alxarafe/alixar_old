<?php

$form = new Form($db);

$title = $langs->trans("Asset") . ' - ' . $langs->trans("Files");
$help_url = '';
llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-asset page-card_documents');

if ($object->id) {
    /*
     * Show tabs
     */
    $head = assetPrepareHead($object);

    print dol_get_fiche_head($head, 'document', $langs->trans("Asset"), -1, $object->picto);


    // Build file list
    $filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
    $totalsize = 0;
    foreach ($filearray as $key => $file) {
        $totalsize += $file['size'];
    }

    // Object card
    // ------------------------------------------------------------
    $linkback = '<a href="' . dol_buildpath('/asset/asset_list.php', 1) . '?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

    $morehtmlref = '<div class="refidno">';
    $morehtmlref .= '</div>';

    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

    print '<div class="fichecenter">';

    print '<div class="underbanner clearboth"></div>';
    print '<table class="border centpercent tableforfield">';

    // Number of files
    print '<tr><td class="titlefield">' . $langs->trans("NbOfAttachedFiles") . '</td><td colspan="3">' . count($filearray) . '</td></tr>';

    // Total size
    print '<tr><td>' . $langs->trans("TotalSizeOfAttachedFiles") . '</td><td colspan="3">' . $totalsize . ' ' . $langs->trans("bytes") . '</td></tr>';

    print '</table>';

    print '</div>';

    print dol_get_fiche_end();

    $modulepart = 'asset';
    $permissiontoadd = $user->hasRight('asset', 'write');
    //  $permissiontoadd = 1;
    $permtoedit = $user->hasRight('asset', 'write');
    //  $permtoedit = 1;
    $param = '&id=' . $object->id;

    //$relativepathwithnofile='asset/' . dol_sanitizeFileName($object->id).'/';
    $relativepathwithnofile = dol_sanitizeFileName($object->ref) . '/';

    include DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';
} else {
    accessforbidden('', 0, 1);
}

// End of page
llxFooter();
