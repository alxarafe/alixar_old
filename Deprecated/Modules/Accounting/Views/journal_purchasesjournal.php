<?php

$form = new Form($db);

// Export
if ($action == 'exportcsv' && !$error) {        // ISO and not UTF8 !
    $sep = getDolGlobalString('ACCOUNTING_EXPORT_SEPARATORCSV');

    $filename = 'journal';
    $type_export = 'journal';
    include DOL_DOCUMENT_ROOT . '/accountancy/tpl/export_journal.tpl.php';

    $companystatic = new Fournisseur($db);
    $invoicestatic = new FactureFournisseur($db);

    foreach ($tabfac as $key => $val) {
        $companystatic->id = $tabcompany[$key]['id'];
        $companystatic->name = $tabcompany[$key]['name'];
        $companystatic->code_compta_fournisseur = $tabcompany[$key]['code_compta_fournisseur'];
        $companystatic->code_fournisseur = $tabcompany[$key]['code_fournisseur'];
        $companystatic->fournisseur = 1;

        $invoicestatic->id = $key;
        $invoicestatic->ref = $val["refsologest"];
        $invoicestatic->ref_supplier = $val["refsuppliersologest"];
        $invoicestatic->type = $val["type"];
        $invoicestatic->description = dol_trunc(html_entity_decode($val["description"]), 32);
        $invoicestatic->close_code = $val["close_code"];

        $date = dol_print_date($val["date"], 'day');

        // Is it a replaced invoice ? 0=not a replaced invoice, 1=replaced invoice not yet dispatched, 2=replaced invoice dispatched
        $replacedinvoice = 0;
        if ($invoicestatic->close_code == FactureFournisseur::CLOSECODE_REPLACED) {
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

        // Third party
        foreach ($tabttc[$key] as $k => $mt) {
            //if ($mt) {
            print '"' . $key . '"' . $sep;
            print '"' . $date . '"' . $sep;
            print '"' . $val["refsologest"] . '"' . $sep;
            print '"' . mb_convert_encoding(dol_trunc($companystatic->name, 32), 'ISO-8859-1') . '"' . $sep;
            print '"' . length_accounta(html_entity_decode($k)) . '"' . $sep;
            print '"' . length_accountg(getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER')) . '"' . $sep;
            print '"' . length_accounta(html_entity_decode($k)) . '"' . $sep;
            print '"' . $langs->trans("Thirdparty") . '"' . $sep;
            print '"' . mb_convert_encoding(dol_trunc($companystatic->name, 16), 'ISO-8859-1') . ' - ' . $val["refsuppliersologest"] . ' - ' . $langs->trans("Thirdparty") . '"' . $sep;
            print '"' . ($mt < 0 ? price(-$mt) : '') . '"' . $sep;
            print '"' . ($mt >= 0 ? price($mt) : '') . '"' . $sep;
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
            print '"' . $val["refsologest"] . '"' . $sep;
            print '"' . mb_convert_encoding(dol_trunc($companystatic->name, 32), 'ISO-8859-1') . '"' . $sep;
            print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
            print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
            print '""' . $sep;
            print '"' . mb_convert_encoding(dol_trunc($accountingaccount->label, 32), 'ISO-8859-1') . '"' . $sep;
            print '"' . mb_convert_encoding(dol_trunc($companystatic->name, 16), 'ISO-8859-1') . ' - ' . $val["refsuppliersologest"] . ' - ' . dol_trunc($accountingaccount->label, 32) . '"' . $sep;
            print '"' . ($mt >= 0 ? price($mt) : '') . '"' . $sep;
            print '"' . ($mt < 0 ? price(-$mt) : '') . '"' . $sep;
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

            // VAT Reverse charge
            if ($mysoc->country_code == 'FR' || getDolGlobalString('ACCOUNTING_FORCE_ENABLE_VAT_REVERSE_CHARGE')) {
                $has_vat = false;
                foreach ($arrayofvat[$key] as $k => $mt) {
                    if ($mt) {
                        $has_vat = true;
                    }
                }

                if (!$has_vat) {
                    $arrayofvat = $tabrctva;
                    if ($numtax == 1) {
                        $arrayofvat = $tabrclocaltax1;
                    }
                    if ($numtax == 2) {
                        $arrayofvat = $tabrclocaltax2;
                    }
                    if (!is_array($arrayofvat[$key])) {
                        $arrayofvat[$key] = [];
                    }
                }
            }

            foreach ($arrayofvat[$key] as $k => $mt) {
                if ($mt) {
                    print '"' . $key . '"' . $sep;
                    print '"' . $date . '"' . $sep;
                    print '"' . $val["refsologest"] . '"' . $sep;
                    print '"' . mb_convert_encoding(dol_trunc($companystatic->name, 32), 'ISO-8859-1') . '"' . $sep;
                    print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
                    print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
                    print '""' . $sep;
                    print '"' . $langs->trans("VAT") . ' - ' . implode(', ', $def_tva[$key][$k]) . ' %"' . $sep;
                    print '"' . mb_convert_encoding(dol_trunc($companystatic->name, 16), 'ISO-8859-1') . ' - ' . $val["refsuppliersologest"] . ' - ' . $langs->trans("VAT") . implode(', ', $def_tva[$key][$k]) . ' %' . ($numtax ? ' - Localtax ' . $numtax : '') . '"' . $sep;
                    print '"' . ($mt >= 0 ? price($mt) : '') . '"' . $sep;
                    print '"' . ($mt < 0 ? price(-$mt) : '') . '"' . $sep;
                    print '"' . $journal . '"';
                    print "\n";
                }
            }

            // VAT counterpart for NPR
            if (is_array($tabother[$key])) {
                foreach ($tabother[$key] as $k => $mt) {
                    if ($mt) {
                        print '"' . $key . '"' . $sep;
                        print '"' . $date . '"' . $sep;
                        print '"' . $val["refsologest"] . '"' . $sep;
                        print '"' . mb_convert_encoding(dol_trunc($companystatic->name, 32), 'ISO-8859-1') . '"' . $sep;
                        print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
                        print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
                        print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
                        print '"' . $langs->trans("Thirdparty") . '"' . $sep;
                        print '"' . mb_convert_encoding(dol_trunc($companystatic->name, 16), 'ISO-8859-1') . ' - ' . $val["refsuppliersologest"] . ' - ' . $langs->trans("VAT") . ' NPR"' . $sep;
                        print '"' . ($mt < 0 ? price(-$mt) : '') . '"' . $sep;
                        print '"' . ($mt >= 0 ? price($mt) : '') . '"' . $sep;
                        print '"' . $journal . '"';
                        print "\n";
                    }
                }
            }
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
    if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {
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
    $acctSupplierNotConfigured = in_array(getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER'), ['', '-1']);
    if ($acctSupplierNotConfigured) {
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
    if ($acctSupplierNotConfigured) {
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

    $invoicestatic = new FactureFournisseur($db);
    $companystatic = new Fournisseur($db);

    foreach ($tabfac as $key => $val) {
        $companystatic->id = $tabcompany[$key]['id'];
        $companystatic->name = $tabcompany[$key]['name'];
        $companystatic->code_compta_fournisseur = $tabcompany[$key]['code_compta_fournisseur'];
        $companystatic->code_fournisseur = $tabcompany[$key]['code_fournisseur'];
        $companystatic->fournisseur = 1;

        $invoicestatic->id = $key;
        $invoicestatic->ref = $val["refsologest"];
        $invoicestatic->ref_supplier = $val["refsuppliersologest"];
        $invoicestatic->type = $val["type"];
        $invoicestatic->description = dol_trunc(html_entity_decode($val["description"]), 32);
        $invoicestatic->close_code = $val["close_code"];

        $date = dol_print_date($val["date"], 'day');

        // Is it a replaced invoice ? 0=not a replaced invoice, 1=replaced invoice not yet dispatched, 2=replaced invoice dispatched
        $replacedinvoice = 0;
        if ($invoicestatic->close_code == FactureFournisseur::CLOSECODE_REPLACED) {
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

        // Third party
        foreach ($tabttc[$key] as $k => $mt) {
            print '<tr class="oddeven">';
            print "<!-- Thirdparty -->";
            print "<td>" . $date . "</td>";
            print "<td>" . $invoicestatic->getNomUrl(1) . "</td>";
            // Account
            print "<td>";
            $accountoshow = length_accountg(getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER'));
            if (($accountoshow == "") || $accountoshow == 'NotDefined') {
                print '<span class="error">' . $langs->trans("MainAccountForSuppliersNotDefined") . '</span>';
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
            print "<td>" . $companystatic->getNomUrl(0, 'supplier', 16) . ' - ' . $invoicestatic->ref_supplier . ' - ' . $langs->trans("SubledgerAccount") . "</td>";
            print '<td class="right nowraponall amount">' . ($mt < 0 ? price(-$mt) : '') . "</td>";
            print '<td class="right nowraponall amount">' . ($mt >= 0 ? price($mt) : '') . "</td>";
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
                print '<span class="error">' . $langs->trans("ProductAccountNotDefined") . '</span>';
            } else {
                print $accountoshow;
            }
            print "</td>";
            // Subledger account
            print "<td>";
            if (getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER_USE_AUXILIARY_ON_DEPOSIT')) {
                if ($k == getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER_DEPOSIT')) {
                    print length_accounta($tabcompany[$key]['code_compta']);
                }
            } elseif (($accountoshow == "") || $accountoshow == 'NotDefined') {
                print '<span class="error">' . $langs->trans("ThirdpartyAccountNotDefined") . '</span>';
            }
            print '</td>';
            $companystatic->id = $tabcompany[$key]['id'];
            $companystatic->name = $tabcompany[$key]['name'];
            print "<td>" . $companystatic->getNomUrl(0, 'supplier', 16) . ' - ' . $invoicestatic->ref_supplier . ' - ' . $accountingaccount->label . "</td>";
            print '<td class="right nowraponall amount">' . ($mt >= 0 ? price($mt) : '') . "</td>";
            print '<td class="right nowraponall amount">' . ($mt < 0 ? price(-$mt) : '') . "</td>";
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

            // VAT Reverse charge
            if ($mysoc->country_code == 'FR' || getDolGlobalString('ACCOUNTING_FORCE_ENABLE_VAT_REVERSE_CHARGE')) {
                $has_vat = false;
                foreach ($arrayofvat[$key] as $k => $mt) {
                    if ($mt) {
                        $has_vat = true;
                    }
                }

                if (!$has_vat) {
                    $arrayofvat = $tabrctva;
                    if ($numtax == 1) {
                        $arrayofvat = $tabrclocaltax1;
                    }
                    if ($numtax == 2) {
                        $arrayofvat = $tabrclocaltax2;
                    }
                    if (!is_array($arrayofvat[$key])) {
                        $arrayofvat[$key] = [];
                    }
                }
            }

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
                        print '<span class="error">' . $langs->trans("VATAccountNotDefined") . ' (' . $langs->trans("AccountingJournalType3") . ')</span>';
                    } else {
                        print $accountoshow;
                    }
                    print "</td>";
                    // Subledger account
                    print "<td>";
                    print '</td>';
                    print "<td>";
                    print $companystatic->getNomUrl(0, 'supplier', 16) . ' - ' . $invoicestatic->ref_supplier . ' - ' . $langs->trans("VAT") . ' ' . implode(', ', $def_tva[$key][$k]) . ' %' . ($numtax ? ' - Localtax ' . $numtax : '');
                    print "</td>";
                    print '<td class="right nowraponall amount">' . ($mt >= 0 ? price($mt) : '') . "</td>";
                    print '<td class="right nowraponall amount">' . ($mt < 0 ? price(-$mt) : '') . "</td>";
                    print "</tr>";

                    $i++;
                }
            }
        }

        // VAT counterpart for NPR
        if (is_array($tabother[$key])) {
            foreach ($tabother[$key] as $k => $mt) {
                if ($mt) {
                    print '<tr class="oddeven">';
                    print '<!-- VAT counterpart NPR -->';
                    print "<td>" . $date . "</td>";
                    print "<td>" . $invoicestatic->getNomUrl(1) . "</td>";
                    // Account
                    print '<td>';
                    $accountoshow = length_accountg($k);
                    if ($accountoshow == '' || $accountoshow == 'NotDefined') {
                        print '<span class="error">' . $langs->trans("VATAccountNotDefined") . ' (' . $langs->trans("NPR counterpart") . '). Set ACCOUNTING_COUNTERPART_VAT_NPR to the subvention account</span>';
                    } else {
                        print $accountoshow;
                    }
                    print '</td>';
                    // Subledger account
                    print "<td>";
                    print '</td>';
                    print "<td>" . $companystatic->getNomUrl(0, 'supplier', 16) . ' - ' . $invoicestatic->ref_supplier . ' - ' . $langs->trans("VAT") . " NPR (counterpart)</td>";
                    print '<td class="right nowraponall amount">' . ($mt < 0 ? price(-$mt) : '') . "</td>";
                    print '<td class="right nowraponall amount">' . ($mt >= 0 ? price($mt) : '') . "</td>";
                    print "</tr>";

                    $i++;
                }
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
