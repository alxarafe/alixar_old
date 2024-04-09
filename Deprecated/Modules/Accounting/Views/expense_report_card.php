<?php

$help_url = 'EN:Module_Double_Entry_Accounting|FR:Module_Comptabilit&eacute;_en_Partie_Double#Liaisons_comptables';

llxHeader("", $langs->trans('FicheVentilation'), $help_url);

if ($cancel == $langs->trans("Cancel")) {
    $action = '';
}

// Create
$form = new Form($db);
$expensereport_static = new ExpenseReport($db);
$formaccounting = new FormAccounting($db);

if (!empty($id)) {
    $sql = "SELECT er.ref, er.rowid as facid, erd.fk_c_type_fees, erd.comments, erd.rowid, erd.fk_code_ventilation,";
    $sql .= " f.id as type_fees_id, f.code as type_fees_code, f.label as type_fees_label,";
    $sql .= " aa.account_number, aa.label";
    $sql .= " FROM " . MAIN_DB_PREFIX . "expensereport_det as erd";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_type_fees as f ON f.id = erd.fk_c_type_fees";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_account as aa ON erd.fk_code_ventilation = aa.rowid";
    $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "expensereport as er ON er.rowid = erd.fk_expensereport";
    $sql .= " WHERE er.fk_statut > 0 AND erd.rowid = " . ((int) $id);
    $sql .= " AND er.entity IN (" . getEntity('expensereport', 0) . ")"; // We don't share object for accountancy

    dol_syslog("/accounting/expensereport/card.php", LOG_DEBUG);
    $result = $db->query($sql);

    if ($result) {
        $num_lines = $db->num_rows($result);
        $i = 0;

        if ($num_lines) {
            $objp = $db->fetch_object($result);

            print '<form action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '" method="post">' . "\n";
            print '<input type="hidden" name="token" value="' . newToken() . '">';
            print '<input type="hidden" name="action" value="ventil">';
            print '<input type="hidden" name="backtopage" value="' . dol_escape_htmltag($backtopage) . '">';

            print load_fiche_titre($langs->trans('ExpenseReportsVentilation'), '', 'title_accountancy');

            print dol_get_fiche_head();

            print '<table class="border centpercent">';

            // Ref
            print '<tr><td class="titlefield">' . $langs->trans("ExpenseReport") . '</td>';
            $expensereport_static->ref = $objp->ref;
            $expensereport_static->id = $objp->erid;
            print '<td>' . $expensereport_static->getNomUrl(1) . '</td>';
            print '</tr>';

            print '<tr><td>' . $langs->trans("Line") . '</td>';
            print '<td>' . stripslashes(nl2br($objp->rowid)) . '</td></tr>';

            print '<tr><td>' . $langs->trans("Description") . '</td>';
            print '<td>' . stripslashes(nl2br($objp->comments)) . '</td></tr>';

            print '<tr><td>' . $langs->trans("TypeFees") . '</td>';
            print '<td>' . ($langs->trans($objp->type_fees_code) == $objp->type_fees_code ? $objp->type_fees_label : $langs->trans(($objp->type_fees_code))) . '</td>';

            print '<tr><td>' . $langs->trans("Account") . '</td><td>';
            print $formaccounting->select_account($objp->fk_code_ventilation, 'codeventil', 1);
            print '</td></tr>';
            print '</table>';

            print dol_get_fiche_end();

            print '<div class="center">';
            print '<input class="button button-save" type="submit" value="' . $langs->trans("Save") . '">';
            print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            print '<input class="button button-cancel" type="submit" name="cancel" value="' . $langs->trans("Cancel") . '">';
            print '</div>';

            print '</form>';
        } else {
            print "Error";
        }
    } else {
        print "Error";
    }
} else {
    print "Error ID incorrect";
}

// End of page
llxFooter();
