<?php

use DoliCore\Form\Form;

$form = new Form($db);

// Page Header
$help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';
$title = $langs->trans('ChartOfIndividualAccountsOfSubsidiaryLedger');
llxHeader('', $title, $help_url);


// Customer
$sql = "SELECT sa.rowid, sa.nom as label, sa.code_compta as subaccount, '1' as type, sa.entity, sa.client as nature";
$sql .= " FROM " . MAIN_DB_PREFIX . "societe sa";
$sql .= " WHERE sa.entity IN (" . getEntity('societe') . ")";
$sql .= " AND sa.code_compta <> ''";
//print $sql;
if (strlen(trim($search_subaccount))) {
    $lengthpaddingaccount = 0;
    if (getDolGlobalInt('ACCOUNTING_LENGTH_AACCOUNT')) {
        $lengthpaddingaccount = getDolGlobalInt('ACCOUNTING_LENGTH_AACCOUNT');
    }
    $search_subaccount_tmp = $search_subaccount;
    $weremovedsomezero = 0;
    if (strlen($search_subaccount_tmp) <= $lengthpaddingaccount) {
        for ($i = 0; $i < $lengthpaddingaccount; $i++) {
            if (preg_match('/0$/', $search_subaccount_tmp)) {
                $weremovedsomezero++;
                $search_subaccount_tmp = preg_replace('/0$/', '', $search_subaccount_tmp);
            }
        }
    }

    //var_dump($search_subaccount); exit;
    if ($search_subaccount_tmp) {
        if ($weremovedsomezero) {
            $search_subaccount_tmp_clean = $search_subaccount_tmp;
            $search_subaccount_clean = $search_subaccount;
            $startchar = '%';
            if (strpos($search_subaccount_tmp, '^') === 0) {
                $startchar = '';
                $search_subaccount_tmp_clean = preg_replace('/^\^/', '', $search_subaccount_tmp);
                $search_subaccount_clean = preg_replace('/^\^/', '', $search_subaccount);
            }
            $sql .= " AND (sa.code_compta LIKE '" . $db->escape($startchar . $search_subaccount_tmp_clean) . "'";
            $sql .= " OR sa.code_compta LIKE '" . $db->escape($startchar . $search_subaccount_clean) . "%')";
        } else {
            $sql .= natural_search("sa.code_compta", $search_subaccount_tmp);
        }
    }
}
if (strlen(trim($search_label))) {
    $sql .= natural_search("sa.nom", $search_label);
}
if (!empty($search_type) && $search_type >= 0) {
    $sql .= " HAVING type LIKE '" . $db->escape($search_type) . "'";
}

// Supplier
$sql .= " UNION ";
$sql .= " SELECT sa.rowid, sa.nom as label, sa.code_compta_fournisseur as subaccount, '2' as type, sa.entity, '0' as nature FROM " . MAIN_DB_PREFIX . "societe sa";
$sql .= " WHERE sa.entity IN (" . getEntity('societe') . ")";
$sql .= " AND sa.code_compta_fournisseur <> ''";
//print $sql;
if (strlen(trim($search_subaccount))) {
    $lengthpaddingaccount = 0;
    if (getDolGlobalInt('ACCOUNTING_LENGTH_AACCOUNT')) {
        $lengthpaddingaccount = getDolGlobalInt('ACCOUNTING_LENGTH_AACCOUNT');
    }
    $search_subaccount_tmp = $search_subaccount;
    $weremovedsomezero = 0;
    if (strlen($search_subaccount_tmp) <= $lengthpaddingaccount) {
        for ($i = 0; $i < $lengthpaddingaccount; $i++) {
            if (preg_match('/0$/', $search_subaccount_tmp)) {
                $weremovedsomezero++;
                $search_subaccount_tmp = preg_replace('/0$/', '', $search_subaccount_tmp);
            }
        }
    }

    //var_dump($search_subaccount); exit;
    if ($search_subaccount_tmp) {
        if ($weremovedsomezero) {
            $search_subaccount_tmp_clean = $search_subaccount_tmp;
            $search_subaccount_clean = $search_subaccount;
            $startchar = '%';
            if (strpos($search_subaccount_tmp, '^') === 0) {
                $startchar = '';
                $search_subaccount_tmp_clean = preg_replace('/^\^/', '', $search_subaccount_tmp);
                $search_subaccount_clean = preg_replace('/^\^/', '', $search_subaccount);
            }
            $sql .= " AND (sa.code_compta_fournisseur LIKE '" . $db->escape($startchar . $search_subaccount_tmp_clean) . "'";
            $sql .= " OR sa.code_compta_fournisseur LIKE '" . $db->escape($startchar . $search_subaccount_clean) . "%')";
        } else {
            $sql .= natural_search("sa.code_compta_fournisseur", $search_subaccount_tmp);
        }
    }
}
if (strlen(trim($search_label))) {
    $sql .= natural_search("sa.nom", $search_label);
}
if (!empty($search_type) && $search_type >= 0) {
    $sql .= " HAVING type LIKE '" . $db->escape($search_type) . "'";
}

// User - Employee
$sql .= " UNION ";
$sql .= " SELECT u.rowid, u.lastname as label, u.accountancy_code as subaccount, '3' as type, u.entity, '0' as nature FROM " . MAIN_DB_PREFIX . "user u";
$sql .= " WHERE u.entity IN (" . getEntity('user') . ")";
$sql .= " AND u.accountancy_code <> ''";
//print $sql;
if (strlen(trim($search_subaccount))) {
    $lengthpaddingaccount = 0;
    if (getDolGlobalInt('ACCOUNTING_LENGTH_AACCOUNT')) {
        $lengthpaddingaccount = getDolGlobalInt('ACCOUNTING_LENGTH_AACCOUNT');
    }
    $search_subaccount_tmp = $search_subaccount;
    $weremovedsomezero = 0;
    if (strlen($search_subaccount_tmp) <= $lengthpaddingaccount) {
        for ($i = 0; $i < $lengthpaddingaccount; $i++) {
            if (preg_match('/0$/', $search_subaccount_tmp)) {
                $weremovedsomezero++;
                $search_subaccount_tmp = preg_replace('/0$/', '', $search_subaccount_tmp);
            }
        }
    }

    //var_dump($search_subaccount); exit;
    if ($search_subaccount_tmp) {
        if ($weremovedsomezero) {
            $search_subaccount_tmp_clean = $search_subaccount_tmp;
            $search_subaccount_clean = $search_subaccount;
            $startchar = '%';
            if (strpos($search_subaccount_tmp, '^') === 0) {
                $startchar = '';
                $search_subaccount_tmp_clean = preg_replace('/^\^/', '', $search_subaccount_tmp);
                $search_subaccount_clean = preg_replace('/^\^/', '', $search_subaccount);
            }
            $sql .= " AND (u.accountancy_code LIKE '" . $db->escape($startchar . $search_subaccount_tmp_clean) . "'";
            $sql .= " OR u.accountancy_code LIKE '" . $db->escape($startchar . $search_subaccount_clean) . "%')";
        } else {
            $sql .= natural_search("u.accountancy_code", $search_subaccount_tmp);
        }
    }
}
if (strlen(trim($search_label))) {
    $sql .= natural_search("u.lastname", $search_label);
}
if (!empty($search_type) && $search_type >= 0) {
    $sql .= " HAVING type LIKE '" . $db->escape($search_type) . "'";
}

$sql .= $db->order($sortfield, $sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
    $resql = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($resql);
    if (($page * $limit) > $nbtotalofrecords) { // if total resultset is smaller then paging size (filtering), goto and load page 0
        $page = 0;
        $offset = 0;
    }
}

$sql .= $db->plimit($limit + 1, $offset);

dol_syslog('accountancy/admin/subaccount.php:: $sql=' . $sql);
$resql = $db->query($sql);

if ($resql) {
    $num = $db->num_rows($resql);

    $param = '';
    if (!empty($contextpage) && $contextpage != $_SERVER['PHP_SELF']) {
        $param .= '&contextpage=' . urlencode($contextpage);
    }
    if ($limit > 0 && $limit != $conf->liste_limit) {
        $param .= '&limit=' . ((int) $limit);
    }
    if ($search_subaccount) {
        $param .= '&search_subaccount=' . urlencode($search_subaccount);
    }
    if ($search_label) {
        $param .= '&search_label=' . urlencode($search_label);
    }
    if ($optioncss != '') {
        $param .= '&optioncss=' . urlencode($optioncss);
    }

    // List of mass actions available
    $arrayofmassactions = [];

    print '<form method="POST" id="searchFormList" action="' . $_SERVER['PHP_SELF'] . '">';
    if ($optioncss != '') {
        print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
    }
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
    print '<input type="hidden" name="action" value="list">';
    print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
    print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
    print '<input type="hidden" name="contextpage" value="' . $contextpage . '">';

    print_barre_liste($title, $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_accountancy', 0, '', '', $limit, 0, 0, 1);

    print '<div class="info">' . $langs->trans("WarningCreateSubAccounts") . '</div>';

    $varpage = empty($contextpage) ? $_SERVER['PHP_SELF'] : $contextpage;
    $selectedfields = ($mode != 'kanban' ? $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) : ''); // This also change content of $arrayfields
    $selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

    $moreforfilter = '';
    $massactionbutton = '';

    print '<div class="div-table-responsive">';
    print '<table class="tagtable liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">' . "\n";

    // Line for search fields
    print '<tr class="liste_titre_filter">';
    // Action column
    if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
        print '<td class="liste_titre center maxwidthsearch">';
        $searchpicto = $form->showFilterAndCheckAddButtons($massactionbutton ? 1 : 0, 'checkforselect', 1);
        print $searchpicto;
        print '</td>';
    }
    if (!empty($arrayfields['subaccount']['checked'])) {
        print '<td class="liste_titre"><input type="text" class="flat" size="10" name="search_subaccount" value="' . $search_subaccount . '"></td>';
    }
    if (!empty($arrayfields['label']['checked'])) {
        print '<td class="liste_titre"><input type="text" class="flat" size="20" name="search_label" value="' . $search_label . '"></td>';
    }
    if (!empty($arrayfields['type']['checked'])) {
        print '<td class="liste_titre center">' . $form->selectarray('search_type', ['1' => $langs->trans('Customer'), '2' => $langs->trans('Supplier'), '3' => $langs->trans('Employee')], $search_type, 1) . '</td>';
    }
    if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
        if (!empty($arrayfields['reconcilable']['checked'])) {
            print '<td class="liste_titre">&nbsp;</td>';
        }
    }
    // Action column
    if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
        print '<td class="liste_titre maxwidthsearch">';
        $searchpicto = $form->showFilterAndCheckAddButtons($massactionbutton ? 1 : 0, 'checkforselect', 1);
        print $searchpicto;
        print '</td>';
    }
    print '</tr>';

    print '<tr class="liste_titre">';
    // Action column
    if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
        print_liste_field_titre($selectedfields, $_SERVER['PHP_SELF'], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
    }
    if (!empty($arrayfields['subaccount']['checked'])) {
        print_liste_field_titre($arrayfields['subaccount']['label'], $_SERVER['PHP_SELF'], "subaccount", "", $param, '', $sortfield, $sortorder);
    }
    if (!empty($arrayfields['label']['checked'])) {
        print_liste_field_titre($arrayfields['label']['label'], $_SERVER['PHP_SELF'], "label", "", $param, '', $sortfield, $sortorder);
    }
    if (!empty($arrayfields['type']['checked'])) {
        print_liste_field_titre($arrayfields['type']['label'], $_SERVER['PHP_SELF'], "type", "", $param, '', $sortfield, $sortorder, 'center ');
    }
    if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
        if (!empty($arrayfields['reconcilable']['checked'])) {
            print_liste_field_titre($arrayfields['reconcilable']['label'], $_SERVER['PHP_SELF'], 'reconcilable', '', $param, '', $sortfield, $sortorder, 'center ');
        }
    }
    // Action column
    if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
        print_liste_field_titre($selectedfields, $_SERVER['PHP_SELF'], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
    }
    print "</tr>\n";

    $totalarray = [];
    $totalarray['nbfield'] = 0;
    $i = 0;
    while ($i < min($num, $limit)) {
        $obj = $db->fetch_object($resql);

        print '<tr class="oddeven">';

        // Action column
        if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
            print '<td class="center">';
            $e = '';

            // Customer
            if ($obj->type == 1) {
                $e .= '<a class="editfielda" title="' . $langs->trans("Customer") . '" href="' . DOL_URL_ROOT . '/societe/card.php?action=edit&token=' . newToken() . '&socid=' . $obj->rowid . '&backtopage=' . urlencode($_SERVER['PHP_SELF']) . '">' . img_edit() . '</a>';
            } elseif ($obj->type == 2) {
                // Supplier
                $e .= '<a class="editfielda" title="' . $langs->trans("Supplier") . '" href="' . DOL_URL_ROOT . '/societe/card.php?action=edit&token=' . newToken() . '&socid=' . $obj->rowid . '&backtopage=' . urlencode($_SERVER['PHP_SELF']) . '">' . img_edit() . '</a>';
            } elseif ($obj->type == 3) {
                // User - Employee
                $e .= '<a class="editfielda" title="' . $langs->trans("Employee") . '" href="' . DOL_URL_ROOT . '/user/card.php?action=edit&token=' . newToken() . '&id=' . $obj->rowid . '&backtopage=' . urlencode($_SERVER['PHP_SELF']) . '">' . img_edit() . '</a>';
            }
            print $e;
            print '</td>' . "\n";
            if (!$i) {
                $totalarray['nbfield']++;
            }
        }

        // Account number
        if (!empty($arrayfields['subaccount']['checked'])) {
            print "<td>";
            print length_accounta($obj->subaccount);
            print "</td>\n";
            if (!$i) {
                $totalarray['nbfield']++;
            }
        }

        // Subaccount label
        if (!empty($arrayfields['label']['checked'])) {
            print "<td>";
            print dol_escape_htmltag($obj->label);
            print "</td>\n";
            if (!$i) {
                $totalarray['nbfield']++;
            }
        }

        // Type
        if (!empty($arrayfields['type']['checked'])) {
            print '<td class="center">';
            $s = '';

            // Customer
            if ($obj->type == 1) {
                $s .= '<a class="customer-back" style="padding-left: 6px; padding-right: 6px" title="' . $langs->trans("Customer") . '" href="' . DOL_URL_ROOT . '/comm/card.php?socid=' . $obj->rowid . '">' . $langs->trans("Customer") . '</a>';
            } elseif ($obj->type == 2) {
                // Supplier
                $s .= '<a class="vendor-back" style="padding-left: 6px; padding-right: 6px" title="' . $langs->trans("Supplier") . '" href="' . DOL_URL_ROOT . '/fourn/card.php?socid=' . $obj->rowid . '">' . $langs->trans("Supplier") . '</a>';
            } elseif ($obj->type == 3) {
                // User - Employee
                $s .= '<a class="user-back" style="padding-left: 6px; padding-right: 6px" title="' . $langs->trans("Employee") . '" href="' . DOL_URL_ROOT . '/user/card.php?id=' . $obj->rowid . '">' . $langs->trans("Employee") . '</a>';
            }
            print $s;
            if ($obj->nature == 2) {
                print ' <span class="warning bold">(' . $langs->trans("Prospect") . ')</span>';
            }
            print '</td>';
            if (!$i) {
                $totalarray['nbfield']++;
            }
        }

        if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
            // Activated or not reconciliation on accounting account
            if (!empty($arrayfields['reconcilable']['checked'])) {
                print '<td class="center">';
                if (empty($obj->reconcilable)) {
                    print '<a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?id=' . $obj->rowid . '&action=enable&mode=1&token=' . newToken() . '">';
                    print img_picto($langs->trans("Disabled"), 'switch_off');
                    print '</a>';
                } else {
                    print '<a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?id=' . $obj->rowid . '&action=disable&mode=1&token=' . newToken() . '">';
                    print img_picto($langs->trans("Activated"), 'switch_on');
                    print '</a>';
                }
                print '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }
        }

        // Action column
        if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
            print '<td class="center">';
            $e = '';

            // Customer
            if ($obj->type == 1) {
                $e .= '<a class="editfielda" title="' . $langs->trans("Customer") . '" href="' . DOL_URL_ROOT . '/societe/card.php?action=edit&token=' . newToken() . '&socid=' . $obj->rowid . '&backtopage=' . urlencode($_SERVER['PHP_SELF']) . '">' . img_edit() . '</a>';
            } elseif ($obj->type == 2) {
                // Supplier
                $e .= '<a class="editfielda" title="' . $langs->trans("Supplier") . '" href="' . DOL_URL_ROOT . '/societe/card.php?action=edit&token=' . newToken() . '&socid=' . $obj->rowid . '&backtopage=' . urlencode($_SERVER['PHP_SELF']) . '">' . img_edit() . '</a>';
            } elseif ($obj->type == 3) {
                // User - Employee
                $e .= '<a class="editfielda" title="' . $langs->trans("Employee") . '" href="' . DOL_URL_ROOT . '/user/card.php?action=edit&token=' . newToken() . '&id=' . $obj->rowid . '&backtopage=' . urlencode($_SERVER['PHP_SELF']) . '">' . img_edit() . '</a>';
            }
            print $e;
            print '</td>' . "\n";
            if (!$i) {
                $totalarray['nbfield']++;
            }
        }

        print '</tr>' . "\n";
        $i++;
    }

    // If no record found
    if ($num == 0) {
        $colspan = 1;
        foreach ($arrayfields as $key => $val) {
            if (!empty($val['checked'])) {
                $colspan++;
            }
        }
        print '<tr><td colspan="' . $colspan . '"><span class="opacitymedium">' . $langs->trans("NoRecordFound") . '</span></td></tr>';
    }

    $db->free($resql);

    $parameters = ['arrayfields' => $arrayfields, 'sql' => $sql];
    $reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters); // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;

    print "</table>";
    print "</div>";

    print '</form>';
} else {
    dol_print_error($db);
}

// End of page
llxFooter();
