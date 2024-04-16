<?php

$help_url = 'EN:Module_Double_Entry_Accounting|FR:Module_Comptabilit&eacute;_en_Partie_Double#Liaisons_comptables';

llxHeader("", $langs->trans('FicheVentilation'), $help_url);

if ($cancel == $langs->trans("Cancel")) {
    $action = '';
}

// Create
$form = new Form($db);
$facturefournisseur_static = new FactureFournisseur($db);
$formaccounting = new FormAccounting($db);

if (!empty($id)) {
    $sql = "SELECT f.ref as ref, f.rowid as facid, l.fk_product, l.description, l.rowid, l.fk_code_ventilation, ";
    $sql .= " p.rowid as product_id, p.ref as product_ref, p.label as product_label,";
    if (getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED')) {
        $sql .= " ppe.accountancy_code_buy as code_buy,";
    } else {
        $sql .= " p.accountancy_code_buy as code_buy,";
    }
    $sql .= " aa.account_number, aa.label";
    $sql .= " FROM " . MAIN_DB_PREFIX . "facture_fourn_det as l";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product as p ON p.rowid = l.fk_product";
    if (getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED')) {
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product_perentity as ppe ON ppe.fk_product = p.rowid AND ppe.entity = " . ((int) $conf->entity);
    }
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_account as aa ON l.fk_code_ventilation = aa.rowid";
    $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "facture_fourn as f ON f.rowid = l.fk_facture_fourn ";
    $sql .= " WHERE f.fk_statut > 0 AND l.rowid = " . ((int) $id);
    $sql .= " AND f.entity IN (" . getEntity('facture_fourn', 0) . ")"; // We don't share object for accountancy

    dol_syslog("/accounting/supplier/card.php", LOG_DEBUG);
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

            print load_fiche_titre($langs->trans('SuppliersVentilation'), '', 'title_accountancy');

            print dol_get_fiche_head();

            print '<table class="border centpercent">';

            // ref invoice
            print '<tr><td>' . $langs->trans("BillsSuppliers") . '</td>';
            $facturefournisseur_static->ref = $objp->ref;
            $facturefournisseur_static->id = $objp->facid;
            print '<td>' . $facturefournisseur_static->getNomUrl(1) . '</td>';
            print '</tr>';

            print '<tr><td width="20%">' . $langs->trans("Line") . '</td>';
            print '<td>' . stripslashes(nl2br($objp->description)) . '</td></tr>';
            print '<tr><td width="20%">' . $langs->trans("ProductLabel") . '</td>';
            print '<td>' . dol_trunc($objp->product_label, 24) . '</td>';
            print '<tr><td width="20%">' . $langs->trans("Account") . '</td><td>';
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
