<?php

use DoliCore\Form\Form;

$form = new Form($db);

$title = $langs->trans("BillOfMaterials") . ' - ' . $langs->trans("Files");

$help_url = 'EN:Module_BOM';
$morehtmlref = "";

llxHeader('', $title, $help_url);

if ($object->id) {
    /*
     * Show tabs
     */
    $head = bomPrepareHead($object);

    print dol_get_fiche_head($head, 'document', $langs->trans("BillOfMaterials"), -1, 'bom');


    // Build file list
    $filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
    $totalsize = 0;
    foreach ($filearray as $key => $file) {
        $totalsize += $file['size'];
    }

    // Object card
    // ------------------------------------------------------------
    $linkback = '<a href="' . DOL_URL_ROOT . '/bom/bom_list.php?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

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

    $modulepart = 'bom';
    $permissiontoadd = $user->hasRight('bom', 'write');
    $permtoedit = $user->hasRight('bom', 'write');
    $param = '&id=' . $object->id;

    //$relativepathwithnofile='bom/' . dol_sanitizeFileName($object->id).'/';
    $relativepathwithnofile = dol_sanitizeFileName($object->ref) . '/';

    include DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';
} else {
    accessforbidden('', 0, 1);
}

// End of page
llxFooter();
