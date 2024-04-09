<?php

$form = new Form($db);

if ($object->nature == 2) {
    $some_mandatory_steps_of_setup_were_not_done = getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER') == "" || getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER') == '-1';
    $account_accounting_not_defined = getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER') == "" || getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER') == '-1';
} elseif ($object->nature == 3) {
    $some_mandatory_steps_of_setup_were_not_done = getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER') == "" || getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER') == '-1';
    $account_accounting_not_defined = getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER') == "" || getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER') == '-1';
} elseif ($object->nature == 4) {
    $some_mandatory_steps_of_setup_were_not_done = getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER') == "" || getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER') == '-1'
        || getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER') == "" || getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER') == '-1'
        || !getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT') || getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT') == '-1';
    $account_accounting_not_defined = getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER') == "" || getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER') == '-1'
        || getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER') == "" || getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER') == '-1';
} elseif ($object->nature == 5) {
    $some_mandatory_steps_of_setup_were_not_done = !getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT') || getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT') == '-1';
    $account_accounting_not_defined = !getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT') || getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT') == '-1';
} else {
    $title = $object->getLibType();
    $some_mandatory_steps_of_setup_were_not_done = false;
    $account_accounting_not_defined = false;
}

$title = $langs->trans("GenerationOfAccountingEntries") . ' - ' . $object->getNomUrl(0, 2, 1, '', 1);

llxHeader('', dol_string_nohtmltag($title));

$nom = $title;
$nomlink = '';
$periodlink = '';
$exportlink = '';
$builddate = dol_now();
$description = $langs->trans("DescJournalOnlyBindedVisible") . '<br>';
if ($object->nature == 2 || $object->nature == 3) {
    if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
        $description .= $langs->trans("DepositsAreNotIncluded");
    } else {
        $description .= $langs->trans("DepositsAreIncluded");
    }
    if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {
        $description .= $langs->trans("SupplierDepositsAreNotIncluded");
    }
}

$listofchoices = ['notyet' => $langs->trans("NotYetInGeneralLedger"), 'already' => $langs->trans("AlreadyInGeneralLedger")];
$period = $form->selectDate($date_start ? $date_start : -1, 'date_start', 0, 0, 0, '', 1, 0) . ' - ' . $form->selectDate($date_end ? $date_end : -1, 'date_end', 0, 0, 0, '', 1, 0);
$period .= ' -  ' . $langs->trans("JournalizationInLedgerStatus") . ' ' . $form->selectarray('in_bookkeeping', $listofchoices, $in_bookkeeping, 1);

$varlink = 'id_journal=' . $id_journal;

journalHead($nom, $nomlink, $period, $periodlink, $description, $builddate, $exportlink, ['action' => ''], '', $varlink);

if (getDolGlobalString('ACCOUNTANCY_FISCAL_PERIOD_MODE') != 'blockedonclosed') {
    // Test that setup is complete (we are in accounting, so test on entity is always on $conf->entity only, no sharing allowed)
    // Fiscal period test
    $sql = "SELECT COUNT(rowid) as nb FROM " . MAIN_DB_PREFIX . "accounting_fiscalyear WHERE entity = " . ((int) $conf->entity);
    $resql = $db->query($sql);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        if ($obj->nb == 0) {
            print '<br><div class="warning">' . img_warning() . ' ' . $langs->trans("TheFiscalPeriodIsNotDefined");
            $desc = ' : ' . $langs->trans("AccountancyAreaDescFiscalPeriod", 4, '{link}');
            $desc = str_replace('{link}', '<strong>' . $langs->transnoentitiesnoconv("MenuAccountancy") . '-' . $langs->transnoentitiesnoconv("Setup") . "-" . $langs->transnoentitiesnoconv("FiscalPeriod") . '</strong>', $desc);
            print $desc;
            print '</div>';
        }
    } else {
        dol_print_error($db);
    }
}

if ($object->nature == 4) { // Bank journal
    // Test that setup is complete (we are in accounting, so test on entity is always on $conf->entity only, no sharing allowed)
    $sql = "SELECT COUNT(rowid) as nb";
    $sql .= " FROM " . MAIN_DB_PREFIX . "bank_account";
    $sql .= " WHERE entity = " . (int) $conf->entity;
    $sql .= " AND fk_accountancy_journal IS NULL";
    $sql .= " AND clos=0";
    $resql = $db->query($sql);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        if ($obj->nb > 0) {
            print '<br>' . img_warning() . ' ' . $langs->trans("TheJournalCodeIsNotDefinedOnSomeBankAccount");
            print ' : ' . $langs->trans("AccountancyAreaDescBank", 9, '<strong>' . $langs->transnoentitiesnoconv("MenuAccountancy") . '-' . $langs->transnoentitiesnoconv("Setup") . "-" . $langs->transnoentitiesnoconv("BankAccounts") . '</strong>');
        }
    } else {
        dol_print_error($db);
    }
}

// Button to write into Ledger
if ($some_mandatory_steps_of_setup_were_not_done) {
    print '<br><div class="warning">' . img_warning() . ' ' . $langs->trans("SomeMandatoryStepsOfSetupWereNotDone");
    print ' : ' . $langs->trans("AccountancyAreaDescMisc", 4, '<strong>' . $langs->transnoentitiesnoconv("MenuAccountancy") . '-' . $langs->transnoentitiesnoconv("Setup") . "-" . $langs->transnoentitiesnoconv("MenuDefaultAccounts") . '</strong>');
    print '</div>';
}
print '<br><div class="tabsAction tabsActionNoBottom centerimp">';
if (getDolGlobalString('ACCOUNTING_ENABLE_EXPORT_DRAFT_JOURNAL') && $in_bookkeeping == 'notyet') {
    print '<input type="button" class="butAction" name="exportcsv" value="' . $langs->trans("ExportDraftJournal") . '" onclick="launch_export();" />';
}
if ($account_accounting_not_defined) {
    print '<input type="button" class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans("SomeMandatoryStepsOfSetupWereNotDone")) . '" value="' . $langs->trans("WriteBookKeeping") . '" />';
} else {
    if ($in_bookkeeping == 'notyet') {
        print '<input type="button" class="butAction" name="writebookkeeping" value="' . $langs->trans("WriteBookKeeping") . '" onclick="writebookkeeping();" />';
    } else {
        print '<a href="#" class="butActionRefused classfortooltip" name="writebookkeeping">' . $langs->trans("WriteBookKeeping") . '</a>';
    }
}
print '</div>';

// TODO Avoid using js. We can use a direct link with $param
print '
	<script type="text/javascript">
		function launch_export() {
			$("div.fiche form input[name=\"action\"]").val("exportcsv");
			$("div.fiche form input[type=\"submit\"]").click();
			$("div.fiche form input[name=\"action\"]").val("");
		}
		function writebookkeeping() {
			console.log("click on writebookkeeping");
			$("div.fiche form input[name=\"action\"]").val("writebookkeeping");
			$("div.fiche form input[type=\"submit\"]").click();
			$("div.fiche form input[name=\"action\"]").val("");
		}
	</script>';

$object_label = $langs->trans("ObjectsRef");
if ($object->nature == 2 || $object->nature == 3) {
    $object_label = $langs->trans("InvoiceRef");
}
if ($object->nature == 5) {
    $object_label = $langs->trans("ExpenseReportRef");
}


// Show result array
$i = 0;

print '<br>';

print '<div class="div-table-responsive">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Date") . '</td>';
print '<td>' . $langs->trans("Piece") . ' (' . $object_label . ')</td>';
print '<td>' . $langs->trans("AccountAccounting") . '</td>';
print '<td>' . $langs->trans("SubledgerAccount") . '</td>';
print '<td>' . $langs->trans("LabelOperation") . '</td>';
if ($object->nature == 4) {
    print '<td class="center">' . $langs->trans("PaymentMode") . '</td>';
} // bank
print '<td class="right">' . $langs->trans("AccountingDebit") . '</td>';
print '<td class="right">' . $langs->trans("AccountingCredit") . '</td>';
print "</tr>\n";

if (is_array($journal_data) && !empty($journal_data)) {
    foreach ($journal_data as $element_id => $element) {
        foreach ($element['blocks'] as $lines) {
            foreach ($lines as $line) {
                print '<tr class="oddeven">';
                print '<td>' . $line['date'] . '</td>';
                print '<td>' . $line['piece'] . '</td>';
                print '<td>' . $line['account_accounting'] . '</td>';
                print '<td>' . $line['subledger_account'] . '</td>';
                print '<td>' . $line['label_operation'] . '</td>';
                if ($object->nature == 4) {
                    print '<td class="center">' . $line['payment_mode'] . '</td>';
                }
                print '<td class="right nowraponall">' . $line['debit'] . '</td>';
                print '<td class="right nowraponall">' . $line['credit'] . '</td>';
                print '</tr>';

                $i++;
            }
        }
    }
}

if (!$i) {
    $colspan = 7;
    if ($object->nature == 4) {
        $colspan++;
    }
    print '<tr class="oddeven"><td colspan="' . $colspan . '"><span class="opacitymedium">' . $langs->trans("NoRecordFound") . '</span></td></tr>';
}

print '</table>';
print '</div>';

llxFooter();
