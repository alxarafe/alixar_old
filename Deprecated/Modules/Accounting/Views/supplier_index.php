<?php

use DoliModules\Supplier\Model\FactureFournisseur;

$help_url = 'EN:Module_Double_Entry_Accounting|FR:Module_Comptabilit&eacute;_en_Partie_Double#Liaisons_comptables';

llxHeader('', $langs->trans("SuppliersVentilation"), $help_url);

$textprevyear = '<a href="' . $_SERVER['PHP_SELF'] . '?year=' . ($year_current - 1) . '">' . img_previous() . '</a>';
$textnextyear = '&nbsp;<a href="' . $_SERVER['PHP_SELF'] . '?year=' . ($year_current + 1) . '">' . img_next() . '</a>';

print load_fiche_titre($langs->trans("SuppliersVentilation") . " " . $textprevyear . "&nbsp;" . $langs->trans("Year") . "&nbsp;" . $year_start . "&nbsp;" . $textnextyear, '', 'title_accountancy');

print '<span class="opacitymedium">' . $langs->trans("DescVentilSupplier") . '</span><br>';
print '<span class="opacitymedium hideonsmartphone">' . $langs->trans("DescVentilMore", $langs->transnoentitiesnoconv("ValidateHistory"), $langs->transnoentitiesnoconv("ToBind")) . '<br>';
print '</span><br>';

$y = $year_current;

$buttonbind = '<a class="button small" href="' . $_SERVER['PHP_SELF'] . '?action=validatehistory&token=' . newToken() . '">' . img_picto('', 'link', 'class="paddingright fa-color-unset smallpaddingimp"') . $langs->trans("ValidateHistory") . '</a>';


print_barre_liste(img_picto('', 'unlink', 'class="paddingright fa-color-unset"') . $langs->trans("OverviewOfAmountOfLinesNotBound"), '', '', '', '', '', '', -1, '', '', 0, '', '', 0, 1, 1, 0, $buttonbind);
//print load_fiche_titre($langs->trans("OverviewOfAmountOfLinesNotBound"), $buttonbind, '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td class="minwidth100">' . $langs->trans("Account") . '</td>';
print '<td>' . $langs->trans("Label") . '</td>';
for ($i = 1; $i <= 12; $i++) {
    $j = $i + getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) - 1;
    if ($j > 12) {
        $j -= 12;
    }
    $cursormonth = $j;
    if ($cursormonth > 12) {
        $cursormonth -= 12;
    }
    $cursoryear = ($cursormonth < getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1)) ? $y + 1 : $y;
    $tmp = dol_getdate(dol_get_last_day($cursoryear, $cursormonth, 'gmt'), false, 'gmt');

    print '<td width="60" class="right">';
    if (!empty($tmp['mday'])) {
        $param = 'search_date_startday=1&search_date_startmonth=' . $cursormonth . '&search_date_startyear=' . $cursoryear;
        $param .= '&search_date_endday=' . $tmp['mday'] . '&search_date_endmonth=' . $tmp['mon'] . '&search_date_endyear=' . $tmp['year'];
        print '<a href="' . DOL_URL_ROOT . '/accountancy/supplier/list.php?' . $param . '">';
    }
    print $langs->trans('MonthShort' . str_pad((int) $j, 2, '0', STR_PAD_LEFT));
    if (!empty($tmp['mday'])) {
        print '</a>';
    }
    print '</td>';
}
print '<td width="60" class="right"><b>' . $langs->trans("Total") . '</b></td></tr>';

$sql = "SELECT " . $db->ifsql('aa.account_number IS NULL', "'tobind'", 'aa.account_number') . " AS codecomptable,";
$sql .= "  " . $db->ifsql('aa.label IS NULL', "'tobind'", 'aa.label') . " AS intitule,";
for ($i = 1; $i <= 12; $i++) {
    $j = $i + getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) - 1;
    if ($j > 12) {
        $j -= 12;
    }
    $sql .= "  SUM(" . $db->ifsql("MONTH(ff.datef) = " . ((int) $j), "ffd.total_ht", "0") . ") AS month" . str_pad((int) $j, 2, "0", STR_PAD_LEFT) . ",";
}
$sql .= "  SUM(ffd.total_ht) as total";
$sql .= " FROM " . $db->prefix() . "facture_fourn_det as ffd";
$sql .= "  LEFT JOIN " . $db->prefix() . "facture_fourn as ff ON ff.rowid = ffd.fk_facture_fourn";
$sql .= "  LEFT JOIN " . $db->prefix() . "accounting_account as aa ON aa.rowid = ffd.fk_code_ventilation";
$sql .= " WHERE ff.datef >= '" . $db->idate($search_date_start) . "'";
$sql .= "  AND ff.datef <= '" . $db->idate($search_date_end) . "'";
// Define begin binding date
if (getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) {
    $sql .= " AND ff.datef >= '" . $db->idate(getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) . "'";
}
$sql .= "  AND ff.fk_statut > 0";
$sql .= "  AND ffd.product_type <= 2";
$sql .= " AND ff.entity IN (" . getEntity('facture_fourn', 0) . ")"; // We don't share object for accountancy
$sql .= " AND aa.account_number IS NULL";
if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {
    $sql .= " AND ff.type IN (" . FactureFournisseur::TYPE_STANDARD . "," . FactureFournisseur::TYPE_REPLACEMENT . "," . FactureFournisseur::TYPE_CREDIT_NOTE . ")";
} else {
    $sql .= " AND ff.type IN (" . FactureFournisseur::TYPE_STANDARD . "," . FactureFournisseur::TYPE_REPLACEMENT . "," . FactureFournisseur::TYPE_CREDIT_NOTE . "," . FactureFournisseur::TYPE_DEPOSIT . ")";
}
$sql .= " GROUP BY ffd.fk_code_ventilation,aa.account_number,aa.label";

dol_syslog('htdocs/accountancy/supplier/index.php', LOG_DEBUG);
$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);

    while ($row = $db->fetch_row($resql)) {
        print '<tr class="oddeven">';
        print '<td>';
        if ($row[0] == 'tobind') {
            print '<span class="opacitymedium">' . $langs->trans("Unknown") . '</span>';
        } else {
            print length_accountg($row[0]);
        }
        print '</td>';
        print '<td>';
        if ($row[0] == 'tobind') {
            $startmonth = getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1);
            if ($startmonth > 12) {
                $startmonth -= 12;
            }
            $startyear = ($startmonth < getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1)) ? $y + 1 : $y;
            $endmonth = getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) + 11;
            if ($endmonth > 12) {
                $endmonth -= 12;
            }
            $endyear = ($endmonth < getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1)) ? $y + 1 : $y;
            print $langs->trans("UseMenuToSetBindindManualy", DOL_URL_ROOT . '/accountancy/supplier/list.php?search_date_startday=1&search_date_startmonth=' . ((int) $startmonth) . '&search_date_startyear=' . ((int) $startyear) . '&search_date_endday=&search_date_endmonth=' . ((int) $endmonth) . '&search_date_endyear=' . ((int) $endyear), $langs->transnoentitiesnoconv("ToBind"));
        } else {
            print $row[1];
        }
        print '</td>';
        for ($i = 2; $i <= 13; $i++) {
            $cursormonth = (getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) + $i - 2);
            if ($cursormonth > 12) {
                $cursormonth -= 12;
            }
            $cursoryear = ($cursormonth < getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1)) ? $y + 1 : $y;
            $tmp = dol_getdate(dol_get_last_day($cursoryear, $cursormonth, 'gmt'), false, 'gmt');

            print '<td class="right nowraponall amount">';
            print price($row[$i]);
            // Add link to make binding
            if (!empty(price2num($row[$i]))) {
                print '<a href="' . $_SERVER['PHP_SELF'] . '?action=validatehistory&year=' . $y . '&validatemonth=' . ((int) $cursormonth) . '&validateyear=' . ((int) $cursoryear) . '&token=' . newToken() . '">';
                print img_picto($langs->trans("ValidateHistory") . ' (' . $langs->trans('Month' . str_pad($cursormonth, 2, '0', STR_PAD_LEFT)) . ' ' . $cursoryear . ')', 'link', 'class="marginleft2"');
                print '</a>';
            }
            print '</td>';
        }
        print '<td class="right nowraponall amount"><b>' . price($row[14]) . '</b></td>';
        print '</tr>';
    }
    $db->free($resql);

    if ($num == 0) {
        print '<tr class="oddeven"><td colspan="16">';
        print '<span class="opacitymedium">' . $langs->trans("NoRecordFound") . '</span>';
        print '</td></tr>';
    }
} else {
    print $db->lasterror(); // Show last sql error
}
print "</table>\n";
print '</div>';


print '<br>';


print_barre_liste(img_picto('', 'link', 'class="paddingright fa-color-unset"') . $langs->trans("OverviewOfAmountOfLinesBound"), '', '', '', '', '', '', -1, '', '', 0, '', '', 0, 1, 1);
//print load_fiche_titre($langs->trans("OverviewOfAmountOfLinesBound"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td class="minwidth100">' . $langs->trans("Account") . '</td>';
print '<td>' . $langs->trans("Label") . '</td>';
for ($i = 1; $i <= 12; $i++) {
    $j = $i + getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) - 1;
    if ($j > 12) {
        $j -= 12;
    }
    $cursormonth = $j;
    if ($cursormonth > 12) {
        $cursormonth -= 12;
    }
    $cursoryear = ($cursormonth < getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1)) ? $y + 1 : $y;
    $tmp = dol_getdate(dol_get_last_day($cursoryear, $cursormonth, 'gmt'), false, 'gmt');

    print '<td width="60" class="right">';
    if (!empty($tmp['mday'])) {
        $param = 'search_date_startday=1&search_date_startmonth=' . $cursormonth . '&search_date_startyear=' . $cursoryear;
        $param .= '&search_date_endday=' . $tmp['mday'] . '&search_date_endmonth=' . $tmp['mon'] . '&search_date_endyear=' . $tmp['year'];
        print '<a href="' . DOL_URL_ROOT . '/accountancy/supplier/lines.php?' . $param . '">';
    }
    print $langs->trans('MonthShort' . str_pad($j, 2, '0', STR_PAD_LEFT));
    if (!empty($tmp['mday'])) {
        print '</a>';
    }
    print '</td>';
}
print '<td width="60" class="right"><b>' . $langs->trans("Total") . '</b></td></tr>';

$sql = "SELECT " . $db->ifsql('aa.account_number IS NULL', "'tobind'", 'aa.account_number') . " AS codecomptable,";
$sql .= "  " . $db->ifsql('aa.label IS NULL', "'tobind'", 'aa.label') . " AS intitule,";
for ($i = 1; $i <= 12; $i++) {
    $j = $i + getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) - 1;
    if ($j > 12) {
        $j -= 12;
    }
    $sql .= "  SUM(" . $db->ifsql("MONTH(ff.datef) = " . ((int) $j), "ffd.total_ht", "0") . ") AS month" . str_pad((int) $j, 2, "0", STR_PAD_LEFT) . ",";
}
$sql .= "  SUM(ffd.total_ht) as total";
$sql .= " FROM " . $db->prefix() . "facture_fourn_det as ffd";
$sql .= "  LEFT JOIN " . $db->prefix() . "facture_fourn as ff ON ff.rowid = ffd.fk_facture_fourn";
$sql .= "  LEFT JOIN " . $db->prefix() . "accounting_account as aa ON aa.rowid = ffd.fk_code_ventilation";
$sql .= " WHERE ff.datef >= '" . $db->idate($search_date_start) . "'";
$sql .= "  AND ff.datef <= '" . $db->idate($search_date_end) . "'";
// Define begin binding date
if (getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) {
    $sql .= " AND ff.datef >= '" . $db->idate(getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) . "'";
}
$sql .= " AND ff.entity IN (" . getEntity('facture_fourn', 0) . ")"; // We don't share object for accountancy
$sql .= "  AND ff.fk_statut > 0";
$sql .= "  AND ffd.product_type <= 2";
if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {
    $sql .= " AND ff.type IN (" . FactureFournisseur::TYPE_STANDARD . ", " . FactureFournisseur::TYPE_REPLACEMENT . ", " . FactureFournisseur::TYPE_CREDIT_NOTE . ")";
} else {
    $sql .= " AND ff.type IN (" . FactureFournisseur::TYPE_STANDARD . ", " . FactureFournisseur::TYPE_REPLACEMENT . ", " . FactureFournisseur::TYPE_CREDIT_NOTE . ", " . FactureFournisseur::TYPE_DEPOSIT . ")";
}
$sql .= " AND aa.account_number IS NOT NULL";
$sql .= " GROUP BY ffd.fk_code_ventilation,aa.account_number,aa.label";
$sql .= ' ORDER BY aa.account_number';

dol_syslog('htdocs/accountancy/supplier/index.php');
$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);

    while ($row = $db->fetch_row($resql)) {
        print '<tr class="oddeven">';
        print '<td>';
        if ($row[0] == 'tobind') {
            print $langs->trans("Unknown");
        } else {
            print length_accountg($row[0]);
        }
        print '</td>';

        print '<td class="tdoverflowmax300"' . (empty($row[1]) ? '' : ' title="' . dol_escape_htmltag($row[1]) . '"') . '>';
        if ($row[0] == 'tobind') {
            print $langs->trans("UseMenuToSetBindindManualy", DOL_URL_ROOT . '/accountancy/supplier/list.php?search_year=' . ((int) $y), $langs->transnoentitiesnoconv("ToBind"));
        } else {
            print dol_escape_htmltag($row[1]);
        }
        print '</td>';

        for ($i = 2; $i <= 13; $i++) {
            print '<td class="right nowraponall amount">';
            print price($row[$i]);
            print '</td>';
        }
        print '<td class="right nowraponall amount"><b>' . price($row[14]) . '</b></td>';
        print '</tr>';
    }
    $db->free($resql);

    if ($num == 0) {
        print '<tr class="oddeven"><td colspan="16">';
        print '<span class="opacitymedium">' . $langs->trans("NoRecordFound") . '</span>';
        print '</td></tr>';
    }
} else {
    print $db->lasterror(); // Show last sql error
}
print "</table>\n";
print '</div>';


if (getDolGlobalString('SHOW_TOTAL_OF_PREVIOUS_LISTS_IN_LIN_PAGE')) { // This part of code looks strange. Why showing a report that should rely on result of this step ?
    print '<br>';
    print '<br>';

    print_barre_liste($langs->trans("OtherInfo"), '', '', '', '', '', '', -1, '', '', 0, '', '', 0, 1, 1);
    //print load_fiche_titre($langs->trans("OtherInfo"), '', '');

    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><td>' . $langs->trans("Total") . '</td>';
    for ($i = 1; $i <= 12; $i++) {
        $j = $i + getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) - 1;
        if ($j > 12) {
            $j -= 12;
        }
        print '<td width="60" class="right">' . $langs->trans('MonthShort' . str_pad((int) $j, 2, '0', STR_PAD_LEFT)) . '</td>';
    }
    print '<td width="60" class="right"><b>' . $langs->trans("Total") . '</b></td></tr>';

    $sql = "SELECT '" . $db->escape($langs->trans("CAHTF")) . "' AS label,";
    for ($i = 1; $i <= 12; $i++) {
        $j = $i + getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) - 1;
        if ($j > 12) {
            $j -= 12;
        }
        $sql .= "  SUM(" . $db->ifsql("MONTH(ff.datef) = " . ((int) $j), "ffd.total_ht", "0") . ") AS month" . str_pad((int) $j, 2, "0", STR_PAD_LEFT) . ",";
    }
    $sql .= "  SUM(ffd.total_ht) as total";
    $sql .= " FROM " . $db->prefix() . "facture_fourn_det as ffd";
    $sql .= "  LEFT JOIN " . $db->prefix() . "facture_fourn as ff ON ff.rowid = ffd.fk_facture_fourn";
    $sql .= " WHERE ff.datef >= '" . $db->idate($search_date_start) . "'";
    $sql .= "  AND ff.datef <= '" . $db->idate($search_date_end) . "'";
    // Define begin binding date
    if (getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) {
        $sql .= " AND ff.datef >= '" . $db->idate(getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) . "'";
    }
    $sql .= " AND ff.entity IN (" . getEntity('facture_fourn', 0) . ")"; // We don't share object for accountancy
    $sql .= "  AND ff.fk_statut > 0";
    $sql .= "  AND ffd.product_type <= 2";
    if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {
        $sql .= " AND ff.type IN (" . FactureFournisseur::TYPE_STANDARD . ", " . FactureFournisseur::TYPE_REPLACEMENT . ", " . FactureFournisseur::TYPE_CREDIT_NOTE . ")";
    } else {
        $sql .= " AND ff.type IN (" . FactureFournisseur::TYPE_STANDARD . ", " . FactureFournisseur::TYPE_REPLACEMENT . ", " . FactureFournisseur::TYPE_CREDIT_NOTE . ", " . FactureFournisseur::TYPE_DEPOSIT . ")";
    }

    dol_syslog('htdocs/accountancy/supplier/index.php');
    $resql = $db->query($sql);
    if ($resql) {
        $num = $db->num_rows($resql);

        while ($row = $db->fetch_row($resql)) {
            print '<tr><td>' . $row[0] . '</td>';
            for ($i = 1; $i <= 12; $i++) {
                print '<td class="right nowraponall amount">' . price($row[$i]) . '</td>';
            }
            print '<td class="right nowraponall amount"><b>' . price($row[13]) . '</b></td>';
            print '</tr>';
        }
        $db->free($resql);
    } else {
        print $db->lasterror(); // Show last sql error
    }
    print "</table>\n";
    print '</div>';
}

// End of page
llxFooter();
