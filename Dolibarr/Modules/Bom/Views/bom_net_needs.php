<?php

use DoliCore\Form\Form;
use DoliCore\Form\FormFile;

$form = new Form($db);
$formfile = new FormFile($db);

$title = $langs->trans('BOM');
$help_url = 'EN:Module_BOM';
llxHeader('', $title, $help_url);


// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
    $head = bomPrepareHead($object);
    print dol_get_fiche_head($head, 'net_needs', $langs->trans("BillOfMaterials"), -1, 'bom');

    $formconfirm = '';

    // Call Hook formConfirm
    $parameters = ['formConfirm' => $formconfirm, 'lineid' => $lineid];
    $reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
    if (empty($reshook)) {
        $formconfirm .= $hookmanager->resPrint;
    } elseif ($reshook > 0) {
        $formconfirm = $hookmanager->resPrint;
    }

    // Print form confirm
    print $formconfirm;


    // Object card
    // ------------------------------------------------------------
    $linkback = '<a href="' . DOL_URL_ROOT . '/bom/bom_list.php?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

    $morehtmlref = '<div class="refidno">';

    $morehtmlref .= '</div>';


    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


    print '<div class="fichecenter">';
    print '<div class="fichehalfleft">';
    print '<div class="underbanner clearboth"></div>';
    print '<table class="border centpercent tableforfield">' . "\n";

    // Common attributes
    $keyforbreak = 'duration';
    include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_view.tpl.php';

    print '<tr><td>' . $form->textwithpicto($langs->trans("TotalCost"), $langs->trans("BOMTotalCost")) . '</td><td><span class="amount">' . price($object->total_cost) . '</span></td></tr>';
    print '<tr><td>' . $langs->trans("UnitCost") . '</td><td>' . price($object->unit_cost) . '</td></tr>';

    // Other attributes
    include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

    print '</table>';
    print '</div>';
    print '</div>';

    print '<div class="clearboth"></div>';

    print dol_get_fiche_end();

    $viewlink = dolGetButtonTitle($langs->trans('GroupByX', $langs->transnoentitiesnoconv("Products")), '', 'fa fa-bars imgforviewmode', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&token=' . newToken(), '', 1, ['morecss' => 'reposition ' . ($action !== 'treeview' ? 'btnTitleSelected' : '')]);
    $viewlink .= dolGetButtonTitle($langs->trans('TreeView'), '', 'fa fa-stream imgforviewmode', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=treeview&token=' . newToken(), '', 1, ['morecss' => 'reposition marginleftonly ' . ($action == 'treeview' ? 'btnTitleSelected' : '')]);

    print load_fiche_titre($langs->trans("BOMNetNeeds"), $viewlink, 'product');

    /*
     * Lines
     */
    $text_stock_options = $langs->trans("RealStockDesc") . '<br>';
    $text_stock_options .= $langs->trans("RealStockWillAutomaticallyWhen") . '<br>';
    $text_stock_options .= (getDolGlobalString('STOCK_CALCULATE_ON_SHIPMENT') || getDolGlobalString('STOCK_CALCULATE_ON_SHIPMENT_CLOSE') ? '- ' . $langs->trans("DeStockOnShipment") . '<br>' : '');
    $text_stock_options .= (getDolGlobalString('STOCK_CALCULATE_ON_VALIDATE_ORDER') ? '- ' . $langs->trans("DeStockOnValidateOrder") . '<br>' : '');
    $text_stock_options .= (getDolGlobalString('STOCK_CALCULATE_ON_BILL') ? '- ' . $langs->trans("DeStockOnBill") . '<br>' : '');
    $text_stock_options .= (getDolGlobalString('STOCK_CALCULATE_ON_SUPPLIER_BILL') ? '- ' . $langs->trans("ReStockOnBill") . '<br>' : '');
    $text_stock_options .= (getDolGlobalString('STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER') ? '- ' . $langs->trans("ReStockOnValidateOrder") . '<br>' : '');
    $text_stock_options .= (getDolGlobalString('STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER') ? '- ' . $langs->trans("ReStockOnDispatchOrder") . '<br>' : '');
    $text_stock_options .= (getDolGlobalString('STOCK_CALCULATE_ON_RECEPTION') || getDolGlobalString('STOCK_CALCULATE_ON_RECEPTION_CLOSE') ? '- ' . $langs->trans("StockOnReception") . '<br>' : '');

    print '<table id="tablelines" class="noborder noshadow" width="100%">';
    print "<thead>\n";
    print '<tr class="liste_titre nodrag nodrop">';
    print '<td class="linecoldescription">' . $langs->trans('Product');
    if (getDolGlobalString('BOM_SUB_BOM') && $action == 'treeview') {
        print ' &nbsp; <a id="show_all" href="#">' . img_picto('', 'folder-open', 'class="paddingright"') . $langs->trans("ExpandAll") . '</a>&nbsp;&nbsp;';
        print '<a id="hide_all" href="#">' . img_picto('', 'folder', 'class="paddingright"') . $langs->trans("UndoExpandAll") . '</a>&nbsp;';
    }
    print '</td>';
    if ($action == 'treeview') {
        print '<td class="left">' . $langs->trans('ProducedBy') . '</td>';
    }
    print '<td class="linecolqty right">' . $langs->trans('Quantity') . '</td>';
    print '<td class="linecolstock right">' . $form->textwithpicto($langs->trans("PhysicalStock"), $text_stock_options, 1) . '</td>';
    print '<td class="linecoltheoricalstock right">' . $form->textwithpicto($langs->trans("VirtualStock"), $langs->trans("VirtualStockDesc")) . '</td>';
    print  '</tr>';

    print '</thead>';
    print '<tbody>';
    if (!empty($TChildBom)) {
        if ($action == 'treeview') {
            foreach ($TChildBom as $fk_bom => $TProduct) {
                $repeatChar = '&emsp;';
                if (!empty($TProduct['bom'])) {
                    $prod = new Product($db);
                    $prod->fetch($TProduct['bom']->fk_product);
                    if ($TProduct['parentid'] != $object->id) {
                        print '<tr class="sub_bom_lines oddeven" parentid="' . $TProduct['parentid'] . '">';
                    } else {
                        print '<tr class="oddeven">';
                    }
                    if ($action == 'treeview') {
                        print '<td class="linecoldescription">' . str_repeat($repeatChar, $TProduct['level']) . $prod->getNomUrl(1);
                    } else {
                        print '<td class="linecoldescription">' . str_repeat($repeatChar, $TProduct['level']) . $TProduct['bom']->getNomUrl(1);
                    }
                    print ' <a class="collapse_bom" id="collapse-' . $fk_bom . '" href="#">';
                    print img_picto('', 'folder-open');
                    print '</a>';
                    print  '</td>';
                    if ($action == 'treeview') {
                        print '<td class="left">' . $TProduct['bom']->getNomUrl(1) . '</td>';
                    }
                    print '<td class="linecolqty right">' . $TProduct['qty'] . '</td>';
                    print '<td class="linecolstock right"></td>';
                    print '<td class="linecoltheoricalstock right"></td>';
                    print '</tr>';
                }
                if (!empty($TProduct['product'])) {
                    foreach ($TProduct['product'] as $fk_product => $TInfos) {
                        $prod = new Product($db);
                        $prod->fetch($fk_product);
                        $prod->load_virtual_stock();
                        if (empty($prod->stock_reel)) {
                            $prod->stock_reel = 0;
                        }
                        if ($fk_bom != $object->id) {
                            print '<tr class="sub_bom_lines oddeven" parentid="' . $fk_bom . '">';
                        } else {
                            print '<tr class="oddeven">';
                        }
                        print '<td class="linecoldescription">' . str_repeat($repeatChar, $TInfos['level']) . $prod->getNomUrl(1) . '</td>';
                        if ($action == 'treeview') {
                            print '<td></td>';
                        }
                        print '<td class="linecolqty right">' . $TInfos['qty'] . '</td>';
                        print '<td class="linecolstock right">' . price2num($prod->stock_reel, 'MS') . '</td>';
                        print '<td class="linecoltheoricalstock right">' . $prod->stock_theorique . '</td>';
                        print '</tr>';
                    }
                }
            }
        } else {
            foreach ($TChildBom as $fk_product => $qty) {
                $prod = new Product($db);
                $prod->fetch($fk_product);
                $prod->load_virtual_stock();
                if (empty($prod->stock_reel)) {
                    $prod->stock_reel = 0;
                }
                print '<tr class="oddeven">';
                print '<td class="linecoldescription">' . $prod->getNomUrl(1) . '</td>';
                print '<td class="linecolqty right">' . $qty . '</td>';
                print '<td class="linecolstock right">' . price2num($prod->stock_reel, 'MS') . '</td>';
                print '<td class="linecoltheoricalstock right">' . $prod->stock_theorique . '</td>';
                print '</tr>';
            }
        }
    }
    print '</tbody>';
    print '</table>';


    /*
     * ButAction
     */
    print '<div class="tabsAction">' . "\n";
    $parameters = [];
    $reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
    if ($reshook < 0) {
        setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
    }
    print '</div>'; ?>

    <script type="text/javascript" language="javascript">
        $(document).ready(function () {

            function folderManage(element) {
                var id_bom_line = element.attr('id').replace('collapse-', '');
                let TSubLines = $('[parentid="' + id_bom_line + '"]');

                if (element.html().indexOf('folder-open') <= 0) {
                    $('[parentid="' + id_bom_line + '"]').show();
                    element.html('<?php echo dol_escape_js(img_picto('', 'folder-open')); ?>');
                } else {
                    for (let i = 0; i < TSubLines.length; i++) {
                        let subBomFolder = $(TSubLines[i]).children('.linecoldescription').children('.collapse_bom');
                        if (subBomFolder.length > 0) {
                            folderManage(subBomFolder);
                        }
                    }
                    TSubLines.hide();
                    element.html('<?php echo dol_escape_js(img_picto('', 'folder')); ?>');
                }
            }

            // When clicking on collapse
            $(".collapse_bom").click(function () {
                folderManage($(this));
                return false;
            });

            // To Show all the sub bom lines
            $("#show_all").click(function () {
                console.log("We click on show all");
                $("[class^=sub_bom_lines]").show();
                $("[class^=collapse_bom]").html('<?php echo dol_escape_js(img_picto('', 'folder-open')); ?>');
                return false;
            });

            // To Hide all the sub bom lines
            $("#hide_all").click(function () {
                console.log("We click on hide all");
                $("[class^=sub_bom_lines]").hide();
                $("[class^=collapse_bom]").html('<?php echo dol_escape_js(img_picto('', 'folder')); ?>');
                return false;
            });

        });
    </script>

    <?php
}

// End of page
llxFooter();
