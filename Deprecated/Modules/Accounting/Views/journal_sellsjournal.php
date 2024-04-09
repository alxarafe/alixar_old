<?php

$form = new Form($db);

// Export
if ($action == 'exportcsv' && !$error) {        // ISO and not UTF8 !
    // Note that to have the button to get this feature enabled, you must enable ACCOUNTING_ENABLE_EXPORT_DRAFT_JOURNAL
    $sep = getDolGlobalString('ACCOUNTING_EXPORT_SEPARATORCSV');

    $filename = 'journal';
    $type_export = 'journal';
    include DOL_DOCUMENT_ROOT . '/accountancy/tpl/export_journal.tpl.php';

    $companystatic = new Client($db);
    $invoicestatic = new Facture($db);

    foreach ($tabfac as $key => $val) {
        $companystatic->id = $tabcompany[$key]['id'];
        $companystatic->name = $tabcompany[$key]['name'];
        $companystatic->code_compta = $tabcompany[$key]['code_compta'];             // deprecated
        $companystatic->code_compta_client = $tabcompany[$key]['code_compta'];
        $companystatic->code_client = $tabcompany[$key]['code_client'];
        $companystatic->client = 3;

        $invoicestatic->id = $key;
        $invoicestatic->ref = (string) $val["ref"];
        $invoicestatic->type = $val["type"];
        $invoicestatic->close_code = $val["close_code"];

        $date = dol_print_date($val["date"], 'day');

        // Is it a replaced invoice ? 0=not a replaced invoice, 1=replaced invoice not yet dispatched, 2=replaced invoice dispatched
        $replacedinvoice = 0;
        if ($invoicestatic->close_code == Facture::CLOSECODE_REPLACED) {
            $replacedinvoice = 1;
            $alreadydispatched = $invoicestatic->getVentilExportCompta(); // Test if replaced invoice already into bookkeeping.
            if ($alreadydispatched) {
                $replacedinvoice = 2;
            }
        }

        // If not already into bookkeeping, we won't add it. If yes, do nothing (should not happen because creating replacement not possible if invoice is accounted)
        if ($replacedinvoice == 1) {
            continue;
        }

        // Warranty
        foreach ($tabwarranty[$key] as $k => $mt) {
            //if ($mt) {
            print '"' . $key . '"' . $sep;
            print '"' . $date . '"' . $sep;
            print '"' . $val["ref"] . '"' . $sep;
            print '"' . mb_convert_encoding(dol_trunc($companystatic->name, 32), 'ISO-8859-1') . '"' . $sep;
            print '"' . length_accounta(html_entity_decode($k)) . '"' . $sep;
            print '"' . length_accountg(getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER_RETAINED_WARRANTY')) . '"' . $sep;
            print '"' . length_accounta(html_entity_decode($k)) . '"' . $sep;
            print '"' . $langs->trans("Thirdparty") . '"' . $sep;
            print '"' . mb_convert_encoding(dol_trunc($companystatic->name, 16), 'ISO-8859-1') . ' - ' . $invoicestatic->ref . ' - ' . $langs->trans("Retainedwarranty") . '"' . $sep;
            print '"' . ($mt >= 0 ? price($mt) : '') . '"' . $sep;
            print '"' . ($mt < 0 ? price(-$mt) : '') . '"' . $sep;
            print '"' . $journal . '"';
            print "\n";
            //}
        }

        // Third party
        foreach ($tabttc[$key] as $k => $mt) {
            //if ($mt) {
            print '"' . $key . '"' . $sep;
            print '"' . $date . '"' . $sep;
            print '"' . $val["ref"] . '"' . $sep;
            print '"' . mb_convert_encoding(dol_trunc($companystatic->name, 32), 'ISO-8859-1') . '"' . $sep;
            print '"' . length_accounta(html_entity_decode($k)) . '"' . $sep;
            print '"' . length_accountg(getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER')) . '"' . $sep;
            print '"' . length_accounta(html_entity_decode($k)) . '"' . $sep;
            print '"' . $langs->trans("Thirdparty") . '"' . $sep;
            print '"' . mb_convert_encoding(dol_trunc($companystatic->name, 16), 'ISO-8859-1') . ' - ' . $invoicestatic->ref . ' - ' . $langs->trans("Thirdparty") . '"' . $sep;
            print '"' . ($mt >= 0 ? price($mt) : '') . '"' . $sep;
            print '"' . ($mt < 0 ? price(-$mt) : '') . '"' . $sep;
            print '"' . $journal . '"';
            print "\n";
            //}
        }

        // Product / Service
        foreach ($tabht[$key] as $k => $mt) {
            $accountingaccount = new AccountingAccount($db);
            $accountingaccount->fetch(null, $k, true);
            //if ($mt) {
            print '"' . $key . '"' . $sep;
            print '"' . $date . '"' . $sep;
            print '"' . $val["ref"] . '"' . $sep;
            print '"' . mb_convert_encoding(dol_trunc($companystatic->name, 32), 'ISO-8859-1') . '"' . $sep;
            print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
            print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
            print '""' . $sep;
            print '"' . mb_convert_encoding(dol_trunc($accountingaccount->label, 32), 'ISO-8859-1') . '"' . $sep;
            print '"' . mb_convert_encoding(dol_trunc($companystatic->name, 16), 'ISO-8859-1') . ' - ' . dol_trunc($accountingaccount->label, 32) . '"' . $sep;
            print '"' . ($mt < 0 ? price(-$mt) : '') . '"' . $sep;
            print '"' . ($mt >= 0 ? price($mt) : '') . '"' . $sep;
            print '"' . $journal . '"';
            print "\n";
            //}
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
                    print '"' . $key . '"' . $sep;
                    print '"' . $date . '"' . $sep;
                    print '"' . $val["ref"] . '"' . $sep;
                    print '"' . mb_convert_encoding(dol_trunc($companystatic->name, 32), 'ISO-8859-1') . '"' . $sep;
                    print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
                    print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
                    print '""' . $sep;
                    print '"' . $langs->trans("VAT") . ' - ' . implode(', ', $def_tva[$key][$k]) . ' %"' . $sep;
                    print '"' . mb_convert_encoding(dol_trunc($companystatic->name, 16), 'ISO-8859-1') . ' - ' . $invoicestatic->ref . ' - ' . $langs->trans("VAT") . implode(', ', $def_tva[$key][$k]) . ' %' . ($numtax ? ' - Localtax ' . $numtax : '') . '"' . $sep;
                    print '"' . ($mt < 0 ? price(-$mt) : '') . '"' . $sep;
                    print '"' . ($mt >= 0 ? price($mt) : '') . '"' . $sep;
                    print '"' . $journal . '"';
                    print "\n";
                }
            }
        }

        // Revenue stamp
        foreach ($tabrevenuestamp[$key] as $k => $mt) {
            //if ($mt) {
            print '"' . $key . '"' . $sep;
            print '"' . $date . '"' . $sep;
            print '"' . $val["ref"] . '"' . $sep;
            print '"' . mb_convert_encoding(dol_trunc($companystatic->name, 32), 'ISO-8859-1') . '"' . $sep;
            print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
            print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
            print '""' . $sep;
            print '"' . $langs->trans("RevenueStamp") . '"' . $sep;
            print '"' . mb_convert_encoding(dol_trunc($companystatic->name, 16), 'ISO-8859-1') . ' - ' . $invoicestatic->ref . ' - ' . $langs->trans("RevenueStamp") . '"' . $sep;
            print '"' . ($mt < 0 ? price(-$mt) : '') . '"' . $sep;
            print '"' . ($mt >= 0 ? price($mt) : '') . '"' . $sep;
            print '"' . $journal . '"';
            print "\n";
            //}
        }
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
    if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
        $description .= $langs->trans("DepositsAreNotIncluded");
    } else {
        $description .= $langs->trans("DepositsAreIncluded");
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

    // Button to write into Ledger
    $acctCustomerNotConfigured = in_array(getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER'), ['', '-1']);
    if ($acctCustomerNotConfigured) {
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
    if ($acctCustomerNotConfigured) {
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

    print '<div class="div-table-responsive">';
    print "<table class=\"noborder\" width=\"100%\">";
    print "<tr class=\"liste_titre\">";
    print "<td>" . $langs->trans("Date") . "</td>";
    print "<td>" . $langs->trans("Piece") . ' (' . $langs->trans("InvoiceRef") . ")</td>";
    print "<td>" . $langs->trans("AccountAccounting") . "</td>";
    print "<td>" . $langs->trans("SubledgerAccount") . "</td>";
    print "<td>" . $langs->trans("LabelOperation") . "</td>";
    print '<td class="center">' . $langs->trans("AccountingDebit") . "</td>";
    print '<td class="center">' . $langs->trans("AccountingCredit") . "</td>";
    print "</tr>\n";

    $i = 0;

    $companystatic = new Client($db);
    $invoicestatic = new Facture($db);

    foreach ($tabfac as $key => $val) {
        $companystatic->id = $tabcompany[$key]['id'];
        $companystatic->name = $tabcompany[$key]['name'];
        $companystatic->code_compta = $tabcompany[$key]['code_compta'];
        $companystatic->code_compta_client = $tabcompany[$key]['code_compta'];
        $companystatic->code_client = $tabcompany[$key]['code_client'];
        $companystatic->client = 3;

        $invoicestatic->id = $key;
        $invoicestatic->ref = (string) $val["ref"];
        $invoicestatic->type = $val["type"];
        $invoicestatic->close_code = $val["close_code"];

        $date = dol_print_date($val["date"], 'day');

        // Is it a replaced invoice ? 0=not a replaced invoice, 1=replaced invoice not yet dispatched, 2=replaced invoice dispatched
        $replacedinvoice = 0;
        if ($invoicestatic->close_code == Facture::CLOSECODE_REPLACED) {
            $replacedinvoice = 1;
            $alreadydispatched = $invoicestatic->getVentilExportCompta(); // Test if replaced invoice already into bookkeeping.
            if ($alreadydispatched) {
                $replacedinvoice = 2;
            }
        }

        // If not already into bookkeeping, we won't add it, if yes, add the counterpart ???.
        if ($replacedinvoice == 1) {
            print '<tr class="oddeven">';
            print "<!-- Replaced invoice -->";
            print "<td>" . $date . "</td>";
            print "<td><strike>" . $invoicestatic->getNomUrl(1) . "</strike></td>";
            // Account
            print "<td>";
            print $langs->trans("Replaced");
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
            continue;
        }
        if ($errorforinvoice[$key] == 'somelinesarenotbound') {
            print '<tr class="oddeven">';
            print "<!-- Some lines are not bound -->";
            print "<td>" . $date . "</td>";
            print "<td>" . $invoicestatic->getNomUrl(1) . "</td>";
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

        // Warranty
        if (is_array($tabwarranty[$key])) {
            foreach ($tabwarranty[$key] as $k => $mt) {
                print '<tr class="oddeven">';
                print "<!-- Thirdparty warranty -->";
                print "<td>" . $date . "</td>";
                print "<td>" . $invoicestatic->getNomUrl(1) . "</td>";
                // Account
                print "<td>";
                $accountoshow = length_accountg(getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER_RETAINED_WARRANTY'));
                if (($accountoshow == "") || $accountoshow == 'NotDefined') {
                    print '<span class="error">' . $langs->trans("MainAccountForRetainedWarrantyNotDefined") . '</span>';
                } else {
                    print $accountoshow;
                }
                print '</td>';
                // Subledger account
                print "<td>";
                $accountoshow = length_accounta($k);
                if (($accountoshow == "") || $accountoshow == 'NotDefined') {
                    print '<span class="error">' . $langs->trans("ThirdpartyAccountNotDefined") . '</span>';
                } else {
                    print $accountoshow;
                }
                print '</td>';
                print "<td>" . $companystatic->getNomUrl(0, 'customer', 16) . ' - ' . $invoicestatic->ref . ' - ' . $langs->trans("Retainedwarranty") . "</td>";
                print '<td class="right nowraponall amount">' . ($mt >= 0 ? price($mt) : '') . "</td>";
                print '<td class="right nowraponall amount">' . ($mt < 0 ? price(-$mt) : '') . "</td>";
                print "</tr>";
            }
        }

        // Third party
        foreach ($tabttc[$key] as $k => $mt) {
            print '<tr class="oddeven">';
            print "<!-- Thirdparty -->";
            print "<td>" . $date . "</td>";
            print "<td>" . $invoicestatic->getNomUrl(1) . "</td>";
            // Account
            print "<td>";
            $accountoshow = length_accountg(getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER'));
            if (($accountoshow == "") || $accountoshow == 'NotDefined') {
                print '<span class="error">' . $langs->trans("MainAccountForCustomersNotDefined") . '</span>';
            } else {
                print $accountoshow;
            }
            print '</td>';
            // Subledger account
            print "<td>";
            $accountoshow = length_accounta($k);
            if (($accountoshow == "") || $accountoshow == 'NotDefined') {
                print '<span class="error">' . $langs->trans("ThirdpartyAccountNotDefined") . '</span>';
            } else {
                print $accountoshow;
            }
            print '</td>';
            print "<td>" . $companystatic->getNomUrl(0, 'customer', 16) . ' - ' . $invoicestatic->ref . ' - ' . $langs->trans("SubledgerAccount") . "</td>";
            print '<td class="right nowraponall amount">' . ($mt >= 0 ? price($mt) : '') . "</td>";
            print '<td class="right nowraponall amount">' . ($mt < 0 ? price(-$mt) : '') . "</td>";
            print "</tr>";

            $i++;
        }

        // Product / Service
        foreach ($tabht[$key] as $k => $mt) {
            $accountingaccount = new AccountingAccount($db);
            $accountingaccount->fetch(null, $k, true);

            print '<tr class="oddeven">';
            print "<!-- Product -->";
            print "<td>" . $date . "</td>";
            print "<td>" . $invoicestatic->getNomUrl(1) . "</td>";
            // Account
            print "<td>";
            $accountoshow = length_accountg($k);
            if (($accountoshow == "") || $accountoshow == 'NotDefined') {
                print '<span class="error">' . $langs->trans("ProductNotDefined") . '</span>';
            } else {
                print $accountoshow;
            }
            print "</td>";
            // Subledger account
            print "<td>";
            if (getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER_USE_AUXILIARY_ON_DEPOSIT')) {
                if ($k == getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER_DEPOSIT')) {
                    print length_accounta($tabcompany[$key]['code_compta']);
                }
            } elseif (($accountoshow == "") || $accountoshow == 'NotDefined') {
                print '<span class="error">' . $langs->trans("ThirdpartyAccountNotDefined") . '</span>';
            }
            print '</td>';
            $companystatic->id = $tabcompany[$key]['id'];
            $companystatic->name = $tabcompany[$key]['name'];
            print "<td>" . $companystatic->getNomUrl(0, 'customer', 16) . ' - ' . $invoicestatic->ref . ' - ' . $accountingaccount->label . "</td>";
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

            // $key is id of invoice
            foreach ($arrayofvat[$key] as $k => $mt) {
                if ($mt) {
                    print '<tr class="oddeven">';
                    print "<!-- VAT -->";
                    print "<td>" . $date . "</td>";
                    print "<td>" . $invoicestatic->getNomUrl(1) . "</td>";
                    // Account
                    print "<td>";
                    $accountoshow = length_accountg($k);
                    if (($accountoshow == "") || $accountoshow == 'NotDefined') {
                        print '<span class="error">' . $langs->trans("VATAccountNotDefined") . ' (' . $langs->trans("AccountingJournalType2") . ')</span>';
                    } else {
                        print $accountoshow;
                    }
                    print "</td>";
                    // Subledger account
                    print "<td>";
                    print '</td>';
                    print "<td>" . $companystatic->getNomUrl(0, 'customer', 16) . ' - ' . $invoicestatic->ref;
                    // $def_tva is array[invoiceid][accountancy_code_sell_of_vat_rate_found][vatrate]=vatrate
                    //var_dump($arrayofvat[$key]); //var_dump($key); //var_dump($k);
                    $tmpvatrate = (empty($def_tva[$key][$k]) ? (empty($arrayofvat[$key][$k]) ? '' : $arrayofvat[$key][$k]) : implode(', ', $def_tva[$key][$k]));
                    print ' - ' . $langs->trans("Taxes") . ' ' . $tmpvatrate . ' %';
                    print($numtax ? ' - Localtax ' . $numtax : '');
                    print "</td>";
                    print '<td class="right nowraponall amount">' . ($mt < 0 ? price(-$mt) : '') . "</td>";
                    print '<td class="right nowraponall amount">' . ($mt >= 0 ? price($mt) : '') . "</td>";
                    print "</tr>";

                    $i++;
                }
            }
        }

        // Revenue stamp
        if (is_array($tabrevenuestamp[$key])) {
            foreach ($tabrevenuestamp[$key] as $k => $mt) {
                print '<tr class="oddeven">';
                print "<!-- Thirdparty revenuestamp -->";
                print "<td>" . $date . "</td>";
                print "<td>" . $invoicestatic->getNomUrl(1) . "</td>";
                // Account
                print "<td>";
                $accountoshow = length_accountg($k);
                if (($accountoshow == "") || $accountoshow == 'NotDefined') {
                    print '<span class="error">' . $langs->trans("MainAccountForRevenueStampSaleNotDefined") . '</span>';
                } else {
                    print $accountoshow;
                }
                print '</td>';
                // Subledger account
                print "<td>";
                print '</td>';
                print "<td>" . $companystatic->getNomUrl(0, 'customer', 16) . ' - ' . $invoicestatic->ref . ' - ' . $langs->trans("RevenueStamp") . "</td>";
                print '<td class="right nowraponall amount">' . ($mt < 0 ? price(-$mt) : '') . "</td>";
                print '<td class="right nowraponall amount">' . ($mt >= 0 ? price($mt) : '') . "</td>";
                print "</tr>";
            }
        }
    }

    if (!$i) {
        print '<tr class="oddeven"><td colspan="7"><span class="opacitymedium">' . $langs->trans("NoRecordFound") . '</span></td></tr>';
    }

    print "</table>";
    print '</div>';

    // End of page
    llxFooter();
}