<?php

$form = new Form($db);

$help_url = '';
llxHeader('', $langs->trans('Asset'), $help_url, '', 0, 0, '', '', '', 'mod-asset page-card_depreciation');

if ($id > 0 || !empty($ref)) {
    $head = assetPrepareHead($object);
    print dol_get_fiche_head($head, 'depreciation', $langs->trans("Asset"), -1, $object->picto);

    // Object card
    // ------------------------------------------------------------
    $linkback = '<a href="' . DOL_URL_ROOT . '/asset/list.php?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

    $morehtmlref = '<div class="refidno">';
    $morehtmlref .= '</div>';

    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

    print '<div class="fichecenter">';
    print '<div class="underbanner clearboth"></div>';
    print '</div>';

    print dol_get_fiche_end();

    $parameters = [];
    $reshook = $hookmanager->executeHooks('listAssetDeprecation', $parameters, $object, $action);
    print $hookmanager->resPrint;
    if ($reshook < 0) {
        setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
    } elseif (empty($reshook)) {
        $bookkeeping_icon = '<i class="fas fa-save" title="' . $langs->trans('AssetDispatchedInBookkeeping') . '"></i>';
        $future_icon = '<i class="fas fa-clock" title="' . $langs->trans('AssetFutureDepreciationLine') . '"></i>';
        $now = dol_now();

        foreach ($assetdepreciationoptions->deprecation_options_fields as $mode_key => $fields) {
            $lines = $object->depreciation_lines[$mode_key];
            if (!empty($lines)) {
                $mode_info = $assetdepreciationoptions->deprecation_options_fields[$mode_key];
                $depreciation_info = $assetdepreciationoptions->getGeneralDepreciationInfoForMode($mode_key);

                print load_fiche_titre($langs->trans($mode_info['label']), '', '');

                // Depreciation general info
                //---------------------------------
                print '<div class="fichecenter">';
                print '<div class="fichehalfleft">';
                print '<div class="underbanner clearboth"></div>';
                print '<table class="border centpercent tableforfield">' . "\n";
                print '<tr><td class="titlefield">' . $langs->trans('AssetBaseDepreciationHT') . '</td><td>' . price($depreciation_info['base_depreciation_ht']) . '</td></tr>';
                print '<tr><td class="titlefield">' . $langs->trans('AssetDepreciationBeginDate') . '</td><td>' . dol_print_date($object->date_start > $object->date_acquisition ? $object->date_start : $object->date_acquisition, 'day') . '</td></tr>';
                print '</table>';

                // We close div and reopen for second column
                print '</div>';
                print '<div class="fichehalfright">';

                print '<div class="underbanner clearboth"></div>';
                print '<table class="border centpercent tableforfield">';
                print '<tr><td class="titlefield">' . $langs->trans('AssetDepreciationDuration') . '</td><td>' . $depreciation_info['duration'] . ' ( ' . $depreciation_info['duration_type'] . ' )</td></tr>';
                print '<tr><td class="titlefield">' . $langs->trans('AssetDepreciationRate') . '</td><td>' . $depreciation_info['rate'] . '</td></tr>';
                print '</table>';
                print '</div>';
                print '</div>';
                print '<div class="clearboth"></div>';

                // Depreciation lines
                //---------------------------------
                print '<br>';
                print '<div class="div-table-responsive-no-min">';
                print '<table class="noborder allwidth">';

                print '<tr class="liste_titre">';
                print '<td class="width20"></td>';
                print '<td>' . $langs->trans("Ref") . '</td>';
                print '<td class="center">' . $langs->trans("AssetDepreciationDate") . '</td>';
                print '<td class="right">' . $langs->trans("AssetDepreciationHT") . '</td>';
                print '<td class="right">' . $langs->trans("AssetCumulativeDepreciationHT") . '</td>';
                print '<td class="right">' . $langs->trans("AssetResidualHT") . '</td>';
                print '</tr>';

                if (empty($lines)) {
                    print '<tr><td class="impair center" colspan="6"><span class="opacitymedium">' . $langs->trans("None") . '</span></td></tr>';
                } else {
                    foreach ($lines as $line) {
                        print '<tr class="oddeven">';
                        print '<td>' . ($line['bookkeeping'] ? $bookkeeping_icon : ($line['depreciation_date'] > $now ? $future_icon : '')) . '</td>';
                        print '<td >' . (empty($line['ref']) ? $langs->trans('AssetDepreciationReversal') : $line['ref']) . '</td>';
                        print '<td class="center">' . dol_print_date($line['depreciation_date'], 'day') . '</td>';
                        print '<td class="right">';
                        print price($line['depreciation_ht']);
                        print '</td>';
                        print '<td class="right">';
                        print price($line['cumulative_depreciation_ht']);
                        print '</td>';
                        print '<td class="right">';
                        print price(price2num($depreciation_info['base_depreciation_ht'] - $line['cumulative_depreciation_ht'], 'MT'));
                        print '</td>';
                        print "</tr>\n";
                    }
                }

                print '</table>';
                print '</div>';
            }
        }
    }
}

// End of page
llxFooter();
