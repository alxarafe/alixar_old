<?php


$form = new Form($db);

$userstatic = new User($db);

// Export
if ($action == 'exportcsv' && !$error) {        // ISO and not UTF8 !
    $sep = getDolGlobalString('ACCOUNTING_EXPORT_SEPARATORCSV');

    $filename = 'journal';
    $type_export = 'journal';
    include DOL_DOCUMENT_ROOT . '/accountancy/tpl/export_journal.tpl.php';

    // CSV header line
    print '"' . $langs->transnoentitiesnoconv("Date") . '"' . $sep;
    print '"' . $langs->transnoentitiesnoconv("Piece") . '"' . $sep;
    print '"' . $langs->transnoentitiesnoconv("AccountAccounting") . '"' . $sep;
    print '"' . $langs->transnoentitiesnoconv("LabelOperation") . '"' . $sep;
    print '"' . $langs->transnoentitiesnoconv("AccountingDebit") . '"' . $sep;
    print '"' . $langs->transnoentitiesnoconv("AccountingCredit") . '"' . $sep;
    print "\n";

    foreach ($taber as $key => $val) {
        $date = dol_print_date($val["date"], 'day');

        $userstatic->id = $tabuser[$key]['id'];
        $userstatic->name = $tabuser[$key]['name'];

        // Fees
        foreach ($tabht[$key] as $k => $mt) {
            $accountingaccount = new AccountingAccount($db);
            $accountingaccount->fetch(null, $k, true);
            if ($mt) {
                print '"' . $date . '"' . $sep;
                print '"' . $val["ref"] . '"' . $sep;
                print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
                print '"' . dol_trunc($accountingaccount->label, 32) . '"' . $sep;
                print '"' . ($mt >= 0 ? price($mt) : '') . '"' . $sep;
                print '"' . ($mt < 0 ? price(-$mt) : '') . '"';
                print "\n";
            }
        }

        // VAT
        foreach ($tabtva[$key] as $k => $mt) {
            if ($mt) {
                print '"' . $date . '"' . $sep;
                print '"' . $val["ref"] . '"' . $sep;
                print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
                print '"' . dol_trunc($langs->trans("VAT")) . '"' . $sep;
                print '"' . ($mt >= 0 ? price($mt) : '') . '"' . $sep;
                print '"' . ($mt < 0 ? price(-$mt) : '') . '"';
                print "\n";
            }
        }

        // Third party
        foreach ($tabttc[$key] as $k => $mt) {
            print '"' . $date . '"' . $sep;
            print '"' . $val["ref"] . '"' . $sep;
            print '"' . length_accounta(html_entity_decode($k)) . '"' . $sep;
            print '"' . dol_trunc($userstatic->name) . '"' . $sep;
            print '"' . ($mt < 0 ? price(-$mt) : '') . '"' . $sep;
            print '"' . ($mt >= 0 ? price($mt) : '') . '"';
        }
        print "\n";
    }
}

if (empty($action) || $action == 'view') {
    $title = $langs->trans("GenerationOfAccountingEntries") . ' - ' . $accountingjournalstatic->getNomUrl(0, 2, 1, '', 1);

    llxHeader('', dol_string_nohtmltag($title));

    $nom = $title;
    $nomlink = '';
    $periodlink = '';
    $exportlink = '';
    $builddate = dol_now();
    $description = $langs->trans("DescJournalOnlyBindedVisible") . '<br>';

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

    // Button to write into Ledger
    if (!getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT') || getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT') == '-1') {
        print '<br><div class="warning">' . img_warning() . ' ' . $langs->trans("SomeMandatoryStepsOfSetupWereNotDone");
        $desc = ' : ' . $langs->trans("AccountancyAreaDescMisc", 4, '{link}');
        $desc = str_replace('{link}', '<strong>' . $langs->transnoentitiesnoconv("MenuAccountancy") . '-' . $langs->transnoentitiesnoconv("Setup") . "-" . $langs->transnoentitiesnoconv("MenuDefaultAccounts") . '</strong>', $desc);
        print $desc;
        print '</div>';
    }
    print '<br><div class="tabsAction tabsActionNoBottom centerimp">';

    if (getDolGlobalString('ACCOUNTING_ENABLE_EXPORT_DRAFT_JOURNAL') && $in_bookkeeping == 'notyet') {
        print '<input type="button" class="butAction" name="exportcsv" value="' . $langs->trans("ExportDraftJournal") . '" onclick="launch_export();" />';
    }
    if (!getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT') || getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT') == '-1') {
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

    /*
     * Show result array
     */
    print '<br>';

    $i = 0;
    print '<div class="div-table-responsive">';
    print "<table class=\"noborder\" width=\"100%\">";
    print "<tr class=\"liste_titre\">";
    print "<td>" . $langs->trans("Date") . "</td>";
    print "<td>" . $langs->trans("Piece") . ' (' . $langs->trans("ExpenseReportRef") . ")</td>";
    print "<td>" . $langs->trans("AccountAccounting") . "</td>";
    print "<td>" . $langs->trans("SubledgerAccount") . "</td>";
    print "<td>" . $langs->trans("LabelOperation") . "</td>";
    print '<td class="right">' . $langs->trans("AccountingDebit") . "</td>";
    print '<td class="right">' . $langs->trans("AccountingCredit") . "</td>";
    print "</tr>\n";

    $i = 0;

    $expensereportstatic = new ExpenseReport($db);
    $expensereportlinestatic = new ExpenseReportLine($db);

    foreach ($taber as $key => $val) {
        $expensereportstatic->id = $key;
        $expensereportstatic->ref = $val["ref"];
        $expensereportlinestatic->comments = html_entity_decode(dol_trunc($val["comments"], 32));

        $date = dol_print_date($val["date"], 'day');

        if ($errorforinvoice[$key] == 'somelinesarenotbound') {
            print '<tr class="oddeven">';
            print "<!-- Some lines are not bound -->";
            print "<td>" . $date . "</td>";
            print "<td>" . $expensereportstatic->getNomUrl(1) . "</td>";
            // Account
            print "<td>";
            print '<span class="error">' . $langs->trans('ErrorInvoiceContainsLinesNotYetBoundedShort', $val['ref']) . '</span>';
            print '</td>';
            // Subledger account
            print "<td>";
            print '</td>';
            print "<td>";
            print "</td>";
            print '<td class="right"></td>';
            print '<td class="right"></td>';
            print "</tr>";

            $i++;
        }

        // Fees
        foreach ($tabht[$key] as $k => $mt) {
            $accountingaccount = new AccountingAccount($db);
            $accountingaccount->fetch(null, $k, true);

            if ($mt) {
                print '<tr class="oddeven">';
                print "<!-- Fees -->";
                print "<td>" . $date . "</td>";
                print "<td>" . $expensereportstatic->getNomUrl(1) . "</td>";
                $userstatic->id = $tabuser[$key]['id'];
                $userstatic->name = $tabuser[$key]['name'];
                // Account
                print "<td>";
                $accountoshow = length_accountg($k);
                if (($accountoshow == "") || $accountoshow == 'NotDefined') {
                    print '<span class="error">' . $langs->trans("FeeAccountNotDefined") . '</span>';
                } else {
                    print $accountoshow;
                }
                print '</td>';
                // Subledger account
                print "<td>";
                print '</td>';
                $userstatic->id = $tabuser[$key]['id'];
                $userstatic->name = $tabuser[$key]['name'];
                print "<td>" . $userstatic->getNomUrl(0, 'user', 16) . ' - ' . $accountingaccount->label . "</td>";
                print '<td class="right nowraponall amount">' . ($mt >= 0 ? price($mt) : '') . "</td>";
                print '<td class="right nowraponall amount">' . ($mt < 0 ? price(-$mt) : '') . "</td>";
                print "</tr>";

                $i++;
            }
        }

        // Third party
        foreach ($tabttc[$key] as $k => $mt) {
            $userstatic->id = $tabuser[$key]['id'];
            $userstatic->name = $tabuser[$key]['name'];

            print '<tr class="oddeven">';
            print "<!-- Thirdparty -->";
            print "<td>" . $date . "</td>";
            print "<td>" . $expensereportstatic->getNomUrl(1) . "</td>";
            // Account
            print "<td>";
            $accountoshow = length_accountg(getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT'));
            if (($accountoshow == "") || $accountoshow == 'NotDefined') {
                print '<span class="error">' . $langs->trans("MainAccountForUsersNotDefined") . '</span>';
            } else {
                print $accountoshow;
            }
            print "</td>";
            // Subledger account
            print "<td>";
            $accountoshow = length_accounta($k);
            if (($accountoshow == "") || $accountoshow == 'NotDefined') {
                print '<span class="error">' . $langs->trans("UserAccountNotDefined") . '</span>';
            } else {
                print $accountoshow;
            }
            print '</td>';
            print "<td>" . $userstatic->getNomUrl(0, 'user', 16) . ' - ' . $langs->trans("SubledgerAccount") . "</td>";
            print '<td class="right nowraponall amount">' . ($mt < 0 ? price(-$mt) : '') . "</td>";
            print '<td class="right nowraponall amount">' . ($mt >= 0 ? price($mt) : '') . "</td>";
            print "</tr>";

            $i++;
        }

        // VAT
        $listoftax = [0, 1, 2];
        foreach ($listoftax as $numtax) {
            $arrayofvat = $tabtva;
            if ($numtax == 1) {
                $arrayofvat = $tablocaltax1;
            }
            if ($numtax == 2) {
                $arrayofvat = $tablocaltax2;
            }

            foreach ($arrayofvat[$key] as $k => $mt) {
                if ($mt) {
                    print '<tr class="oddeven">';
                    print "<!-- VAT -->";
                    print "<td>" . $date . "</td>";
                    print "<td>" . $expensereportstatic->getNomUrl(1) . "</td>";
                    // Account
                    print "<td>";
                    $accountoshow = length_accountg($k);
                    if (($accountoshow == "") || $accountoshow == 'NotDefined') {
                        print '<span class="error">' . $langs->trans("VATAccountNotDefined") . '</span>';
                    } else {
                        print $accountoshow;
                    }
                    print "</td>";
                    // Subledger account
                    print "<td>";
                    print '</td>';
                    print "<td>" . $userstatic->getNomUrl(0, 'user', 16) . ' - ' . $langs->trans("VAT") . ' ' . implode(', ', $def_tva[$key][$k]) . ' %' . ($numtax ? ' - Localtax ' . $numtax : '');
                    print "</td>";
                    print '<td class="right nowraponall amount">' . ($mt >= 0 ? price($mt) : '') . "</td>";
                    print '<td class="right nowraponall amount">' . ($mt < 0 ? price(-$mt) : '') . "</td>";
                    print "</tr>";

                    $i++;
                }
            }
        }
    }

    if (!$i) {
        $colspan = 7;
        print '<tr class="oddeven"><td colspan="' . $colspan . '"><span class="opacitymedium">' . $langs->trans("NoRecordFound") . '</span></td></tr>';
    }

    print "</table>";
    print '</div>';

    // End of page
    llxFooter();
}
