<?php

$form = new Form($db);
$formother = new FormOther($db);

$help_url = 'EN:Module_Double_Entry_Accounting|FR:Module_Comptabilit&eacute;_en_Partie_Double#Liaisons_comptables';

llxHeader('', $langs->trans("ExpenseReportsVentilation"), $help_url);

if (empty($chartaccountcode)) {
    print $langs->trans("ErrorChartOfAccountSystemNotSelected");
    // End of page
    llxFooter();
    $db->close();
    exit;
}

// Expense report lines
$sql = "SELECT er.ref, er.rowid as erid, er.date_debut, er.date_valid,";
$sql .= " erd.rowid, erd.fk_c_type_fees, erd.comments, erd.total_ht as price, erd.fk_code_ventilation, erd.tva_tx as tva_tx_line, erd.vat_src_code, erd.date,";
$sql .= " f.id as type_fees_id, f.code as type_fees_code, f.label as type_fees_label, f.accountancy_code as code_buy,";
$sql .= " u.rowid as userid, u.login, u.lastname, u.firstname, u.email, u.gender, u.employee, u.photo, u.statut,";
$sql .= " aa.rowid as aarowid";
$parameters = [];
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= " FROM " . MAIN_DB_PREFIX . "expensereport as er";
$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "expensereport_det as erd ON er.rowid = erd.fk_expensereport";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_type_fees as f ON f.id = erd.fk_c_type_fees";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as u ON u.rowid = er.fk_user_author";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_account as aa ON f.accountancy_code = aa.account_number AND aa.fk_pcg_version = '" . $db->escape($chartaccountcode) . "' AND aa.entity = " . $conf->entity;
$sql .= " WHERE er.fk_statut IN (" . ExpenseReport::STATUS_APPROVED . ", " . ExpenseReport::STATUS_CLOSED . ") AND erd.fk_code_ventilation <= 0";
// Add search filter like
if (strlen(trim($search_login))) {
    $sql .= natural_search("u.login", $search_login);
}
if (strlen(trim($search_expensereport))) {
    $sql .= natural_search("er.ref", $search_expensereport);
}
if (strlen(trim($search_label))) {
    $sql .= natural_search("f.label", $search_label);
}
if (strlen(trim($search_desc))) {
    $sql .= natural_search("erd.comments", $search_desc);
}
if (strlen(trim($search_amount))) {
    $sql .= natural_search("erd.total_ht", $search_amount, 1);
}
if (strlen(trim($search_account))) {
    $sql .= natural_search("aa.account_number", $search_account);
}
if (strlen(trim($search_vat))) {
    $sql .= natural_search("erd.tva_tx", $search_vat, 1);
}
if ($search_date_start) {
    $sql .= " AND erd.date >= '" . $db->idate($search_date_start) . "'";
}
if ($search_date_end) {
    $sql .= " AND erd.date <= '" . $db->idate($search_date_end) . "'";
}
$sql .= " AND er.entity IN (" . getEntity('expensereport', 0) . ")"; // We don't share object for accountancy

// Add where from hooks
$parameters = [];
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= $db->order($sortfield, $sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
    if (($page * $limit) > $nbtotalofrecords) { // if total resultset is smaller then paging size (filtering), goto and load page 0
        $page = 0;
        $offset = 0;
    }
}
//print $sql;

$sql .= $db->plimit($limit + 1, $offset);

dol_syslog("accountancy/expensereport/list.php", LOG_DEBUG);
// MAX_JOIN_SIZE can be very low (ex: 300000) on some limited configurations (ex: https://www.online.net/fr/hosting/online-perso)
// This big SELECT command may exceed the MAX_JOIN_SIZE limit => Therefore we use SQL_BIG_SELECTS=1 to disable the MAX_JOIN_SIZE security
if ($db->type == 'mysqli') {
    $db->query("SET SQL_BIG_SELECTS=1");
}

$result = $db->query($sql);
if ($result) {
    $num_lines = $db->num_rows($result);
    $i = 0;

    $arrayofselected = is_array($toselect) ? $toselect : [];

    $param = '';
    if (!empty($contextpage) && $contextpage != $_SERVER['PHP_SELF']) {
        $param .= '&contextpage=' . urlencode($contextpage);
    }
    if ($limit > 0 && $limit != $conf->liste_limit) {
        $param .= '&limit=' . ((int) $limit);
    }
    if ($search_login) {
        $param .= '&search_login=' . urlencode($search_login);
    }
    if ($search_lineid) {
        $param .= '&search_lineid=' . urlencode($search_lineid);
    }
    if ($search_date_startday) {
        $param .= '&search_date_startday=' . urlencode((string) ($search_date_startday));
    }
    if ($search_date_startmonth) {
        $param .= '&search_date_startmonth=' . urlencode((string) ($search_date_startmonth));
    }
    if ($search_date_startyear) {
        $param .= '&search_date_startyear=' . urlencode((string) ($search_date_startyear));
    }
    if ($search_date_endday) {
        $param .= '&search_date_endday=' . urlencode((string) ($search_date_endday));
    }
    if ($search_date_endmonth) {
        $param .= '&search_date_endmonth=' . urlencode((string) ($search_date_endmonth));
    }
    if ($search_date_endyear) {
        $param .= '&search_date_endyear=' . urlencode((string) ($search_date_endyear));
    }
    if ($search_expensereport) {
        $param .= '&search_expensereport=' . urlencode($search_expensereport);
    }
    if ($search_label) {
        $param .= '&search_label=' . urlencode($search_label);
    }
    if ($search_desc) {
        $param .= '&search_desc=' . urlencode($search_desc);
    }
    if ($search_amount) {
        $param .= '&search_amount=' . urlencode($search_amount);
    }
    if ($search_vat) {
        $param .= '&search_vat=' . urlencode($search_vat);
    }

    $arrayofmassactions = [
        'ventil' => img_picto('', 'check', 'class="pictofixedwidth"') . $langs->trans("Ventilate"),
    ];
    $massactionbutton = $form->selectMassAction('ventil', $arrayofmassactions, 1);

    print '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">' . "\n";
    print '<input type="hidden" name="action" value="ventil">';
    if ($optioncss != '') {
        print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
    }
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
    print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
    print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
    print '<input type="hidden" name="page" value="' . $page . '">';

    // @phan-suppress-next-line PhanPluginSuspiciousParamOrder
    print_barre_liste($langs->trans("ExpenseReportLines"), $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, $massactionbutton, $num_lines, $nbtotalofrecords, 'title_accountancy', 0, '', '', $limit);

    print '<span class="opacitymedium">' . $langs->trans("DescVentilTodoExpenseReport") . '</span></br><br>';

    if (!empty($msg)) {
        print $msg . '<br>';
    }

    $moreforfilter = '';

    print '<div class="div-table-responsive">';
    print '<table class="tagtable liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">' . "\n";

    // We add search filter
    print '<tr class="liste_titre_filter">';
    print '<td class="liste_titre"><input type="text" name="search_login" class="maxwidth50" value="' . $search_login . '"></td>';
    print '<td class="liste_titre"></td>';
    print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_expensereport" value="' . dol_escape_htmltag($search_expensereport) . '"></td>';
    if (getDolGlobalString('ACCOUNTANCY_USE_EXPENSE_REPORT_VALIDATION_DATE')) {
        print '<td class="liste_titre"></td>';
    }
    print '<td class="liste_titre center">';
    print '<div class="nowrapfordate">';
    print $form->selectDate($search_date_start ? $search_date_start : -1, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
    print '</div>';
    print '<div class="nowrapfordate">';
    print $form->selectDate($search_date_end ? $search_date_end : -1, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
    print '</div>';
    print '</td>';
    print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_label" value="' . dol_escape_htmltag($search_label) . '"></td>';
    print '<td class="liste_titre"><input type="text" class="flat maxwidthonsmartphone" name="search_desc" value="' . dol_escape_htmltag($search_desc) . '"></td>';
    print '<td class="liste_titre right"><input type="text" class="flat maxwidth50 right" name="search_amount" value="' . dol_escape_htmltag($search_amount) . '"></td>';
    print '<td class="liste_titre right"><input type="text" class="flat maxwidth50 right" name="search_vat" placeholder="%" size="1" value="' . dol_escape_htmltag($search_vat) . '"></td>';
    print '<td class="liste_titre"></td>';
    print '<td class="liste_titre"></td>';
    print '<td class="center liste_titre">';
    $searchpicto = $form->showFilterButtons();
    print $searchpicto;
    print '</td>';
    print '</tr>';

    print '<tr class="liste_titre">';
    print_liste_field_titre("Employee", $_SERVER['PHP_SELF'], "u.login", $param, "", "", $sortfield, $sortorder);
    print_liste_field_titre("LineId", $_SERVER['PHP_SELF'], "erd.rowid", "", $param, '', $sortfield, $sortorder);
    print_liste_field_titre("ExpenseReport", $_SERVER['PHP_SELF'], "er.ref", "", $param, '', $sortfield, $sortorder);
    if (getDolGlobalString('ACCOUNTANCY_USE_EXPENSE_REPORT_VALIDATION_DATE')) {
        print_liste_field_titre("DateValidation", $_SERVER['PHP_SELF'], "er.date_valid", "", $param, '', $sortfield, $sortorder, 'center ');
    }
    print_liste_field_titre("DateOfLine", $_SERVER['PHP_SELF'], "erd.date, erd.rowid", "", $param, '', $sortfield, $sortorder, 'center ');
    print_liste_field_titre("TypeFees", $_SERVER['PHP_SELF'], "f.label", "", $param, '', $sortfield, $sortorder);
    print_liste_field_titre("Description", $_SERVER['PHP_SELF'], "erd.comments", "", $param, '', $sortfield, $sortorder);
    print_liste_field_titre("Amount", $_SERVER['PHP_SELF'], "erd.total_ht", "", $param, '', $sortfield, $sortorder, 'right maxwidth50 ');
    print_liste_field_titre("VATRate", $_SERVER['PHP_SELF'], "erd.tva_tx", "", $param, '', $sortfield, $sortorder, 'right ');
    print_liste_field_titre("DataUsedToSuggestAccount", '', '', '', '', '', '', '', 'nowraponall ');
    print_liste_field_titre("AccountAccountingSuggest", '', '', '', '', '', '', '', '');
    $checkpicto = '';
    if ($massactionbutton) {
        $checkpicto = $form->showCheckAddButtons('checkforselect', 1);
    }
    print_liste_field_titre($checkpicto, '', '', '', '', '', '', '', 'center ');
    print "</tr>\n";


    $expensereport_static = new ExpenseReport($db);
    $userstatic = new User($db);
    $form = new Form($db);

    while ($i < min($num_lines, $limit)) {
        $objp = $db->fetch_object($result);

        $objp->aarowid_suggest = '';
        $objp->aarowid_suggest = $objp->aarowid;

        $expensereport_static->ref = $objp->ref;
        $expensereport_static->id = $objp->erid;

        $userstatic->id = $objp->userid;
        $userstatic->login = $objp->login;
        $userstatic->statut = $objp->statut;
        $userstatic->email = $objp->email;
        $userstatic->gender = $objp->gender;
        $userstatic->firstname = $objp->firstname;
        $userstatic->lastname = $objp->lastname;
        $userstatic->employee = $objp->employee;
        $userstatic->photo = $objp->photo;

        print '<tr class="oddeven">';

        // Login
        print '<td class="nowraponall">';
        print $userstatic->getNomUrl(-1, '', 0, 0, 24, 1, 'login', '', 1);
        print '</td>';

        // Line id
        print '<td>' . $objp->rowid . '</td>';

        // Ref Expense report
        print '<td>' . $expensereport_static->getNomUrl(1) . '</td>';

        // Date validation
        if (getDolGlobalString('ACCOUNTANCY_USE_EXPENSE_REPORT_VALIDATION_DATE')) {
            print '<td class="center">' . dol_print_date($db->jdate($objp->date_valid), 'day') . '</td>';
        }

        // Date
        print '<td class="center">' . dol_print_date($db->jdate($objp->date), 'day') . '</td>';

        // Fees label
        print '<td>';
        print($langs->trans($objp->type_fees_code) == $objp->type_fees_code ? $objp->type_fees_label : $langs->trans(($objp->type_fees_code)));
        print '</td>';

        // Fees description -- Can be null
        print '<td>';
        $text = dolGetFirstLineOfText(dol_string_nohtmltag($objp->comments, 1));
        $trunclength = getDolGlobalInt('ACCOUNTING_LENGTH_DESCRIPTION', 32);
        print $form->textwithtooltip(dol_trunc($text, $trunclength), $objp->comments);
        print '</td>';

        // Amount without taxes
        print '<td class="right nowraponall amount">';
        print price($objp->price);
        print '</td>';

        // Vat rate
        print '<td class="right">';
        print vatrate($objp->tva_tx_line . ($objp->vat_src_code ? ' (' . $objp->vat_src_code . ')' : ''));
        print '</td>';

        // Current account
        print '<td>';
        print length_accountg(html_entity_decode($objp->code_buy));
        print '</td>';

        // Suggested accounting account
        print '<td>';
        print $formaccounting->select_account($objp->aarowid_suggest, 'codeventil' . $objp->rowid, 1, [], 0, 0, 'codeventil maxwidth200 maxwidthonsmartphone', 'cachewithshowemptyone');
        print '</td>';

        print '<td class="center">';
        print '<input type="checkbox" class="flat checkforselect checkforselect' . $objp->rowid . '" name="toselect[]" value="' . $objp->rowid . "_" . $i . '"' . ($objp->aarowid ? "checked" : "") . '/>';
        print '</td>';

        print "</tr>";
        $i++;
    }
    if ($num_lines == 0) {
        print '<tr><td colspan="13"><span class="opacitymedium">' . $langs->trans("NoRecordFound") . '</span></td></tr>';
    }

    print '</table>';
    print "</div>";

    print '</form>';
} else {
    print $db->error();
}
if ($db->type == 'mysqli') {
    $db->query("SET SQL_BIG_SELECTS=0"); // Enable MAX_JOIN_SIZE limitation
}

// Add code to auto check the box when we select an account
print '<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery(".codeventil").change(function() {
		var s=$(this).attr("id").replace("codeventil", "")
		console.log(s+" "+$(this).val());
		if ($(this).val() == -1) jQuery(".checkforselect"+s).prop("checked", false);
		else jQuery(".checkforselect"+s).prop("checked", true);
	});
});
</script>';

// End of page
llxFooter();
