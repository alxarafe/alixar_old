<?php

use function DoliModules\Accounting\Controller\getSourceDocRef;

$form = new Form($db);

if (empty($action) || $action == 'view') {
    $invoicestatic = new Facture($db);
    $invoicesupplierstatic = new FactureFournisseur($db);
    $expensereportstatic = new ExpenseReport($db);
    $vatstatic = new Tva($db);
    $donationstatic = new Don($db);
    $loanstatic = new Loan($db);
    $salarystatic = new Salary($db);
    $variousstatic = new PaymentVarious($db);

    $title = $langs->trans("GenerationOfAccountingEntries") . ' - ' . $accountingjournalstatic->getNomUrl(0, 2, 1, '', 1);

    llxHeader('', dol_string_nohtmltag($title));

    $nom = $title;
    $builddate = dol_now();
    //$description = $langs->trans("DescFinanceJournal") . '<br>';
    $description = $langs->trans("DescJournalOnlyBindedVisible") . '<br>';

    $listofchoices = [
        'notyet' => $langs->trans("NotYetInGeneralLedger"),
        'already' => $langs->trans("AlreadyInGeneralLedger"),
    ];
    $period = $form->selectDate($date_start ? $date_start : -1, 'date_start', 0, 0, 0, '', 1, 0) . ' - ' . $form->selectDate($date_end ? $date_end : -1, 'date_end', 0, 0, 0, '', 1, 0);
    $period .= ' -  ' . $langs->trans("JournalizationInLedgerStatus") . ' ' . $form->selectarray('in_bookkeeping', $listofchoices, $in_bookkeeping, 1);

    $varlink = 'id_journal=' . $id_journal;
    $periodlink = '';
    $exportlink = '';

    journalHead($nom, '', $period, $periodlink, $description, $builddate, $exportlink, ['action' => ''], '', $varlink);

    $desc = '';

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

    // Bank test
    $sql = "SELECT COUNT(rowid) as nb FROM " . MAIN_DB_PREFIX . "bank_account WHERE entity = " . ((int) $conf->entity) . " AND fk_accountancy_journal IS NULL AND clos=0";
    $resql = $db->query($sql);
    if ($resql) {
        $obj = $db->fetch_object($resql);
        if ($obj->nb > 0) {
            print '<br><div class="warning">' . img_warning() . ' ' . $langs->trans("TheJournalCodeIsNotDefinedOnSomeBankAccount");
            $desc = ' : ' . $langs->trans("AccountancyAreaDescBank", 6, '{link}');
            $desc = str_replace('{link}', '<strong>' . $langs->transnoentitiesnoconv("MenuAccountancy") . '-' . $langs->transnoentitiesnoconv("Setup") . "-" . $langs->transnoentitiesnoconv("BankAccounts") . '</strong>', $desc);
            print $desc;
            print '</div>';
        }
    } else {
        dol_print_error($db);
    }


    // Button to write into Ledger
    if (getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER') == "" || getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER') == '-1'
        || getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER') == "" || getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER') == '-1'
        || getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT') == "" || getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT') == '-1') {
        print($desc ? '' : '<br>') . '<div class="warning">' . img_warning() . ' ' . $langs->trans("SomeMandatoryStepsOfSetupWereNotDone");
        $desc = ' : ' . $langs->trans("AccountancyAreaDescMisc", 4, '{link}');
        $desc = str_replace('{link}', '<strong>' . $langs->transnoentitiesnoconv("MenuAccountancy") . '-' . $langs->transnoentitiesnoconv("Setup") . "-" . $langs->transnoentitiesnoconv("MenuDefaultAccounts") . '</strong>', $desc);
        print $desc;
        print '</div>';
    }


    print '<br><div class="tabsAction tabsActionNoBottom centerimp">';

    if (getDolGlobalString('ACCOUNTING_ENABLE_EXPORT_DRAFT_JOURNAL') && $in_bookkeeping == 'notyet') {
        print '<input type="button" class="butAction" name="exportcsv" value="' . $langs->trans("ExportDraftJournal") . '" onclick="launch_export();" />';
    }

    if (getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER') == "" || getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER') == '-1'
        || getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER') == "" || getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER') == '-1') {
        print '<input type="button" class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans("SomeMandatoryStepsOfSetupWereNotDone")) . '" value="' . $langs->trans("WriteBookKeeping") . '" />';
    } else {
        if ($in_bookkeeping == 'notyet') {
            print '<input type="button" class="butAction" name="writebookkeeping" value="' . $langs->trans("WriteBookKeeping") . '" onclick="writebookkeeping();" />';
        } else {
            print '<a class="butActionRefused classfortooltip" name="writebookkeeping">' . $langs->trans("WriteBookKeeping") . '</a>';
        }
    }

    print '</div>';

    // TODO Avoid using js. We can use a direct link with $param
    print '
	<script type="text/javascript">
		function launch_export() {
			console.log("Set value into form and submit");
			$("div.fiche form input[name=\"action\"]").val("exportcsv");
			$("div.fiche form input[type=\"submit\"]").click();
			$("div.fiche form input[name=\"action\"]").val("");
		}
		function writebookkeeping() {
			console.log("Set value into form and submit");
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
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print "<td>" . $langs->trans("Date") . "</td>";
    print "<td>" . $langs->trans("Piece") . ' (' . $langs->trans("ObjectsRef") . ")</td>";
    print "<td>" . $langs->trans("AccountAccounting") . "</td>";
    print "<td>" . $langs->trans("SubledgerAccount") . "</td>";
    print "<td>" . $langs->trans("LabelOperation") . "</td>";
    print '<td class="center">' . $langs->trans("PaymentMode") . "</td>";
    print '<td class="right">' . $langs->trans("AccountingDebit") . "</td>";
    print '<td class="right">' . $langs->trans("AccountingCredit") . "</td>";
    print "</tr>\n";

    $r = '';

    foreach ($tabpay as $key => $val) {              // $key is rowid in llx_bank
        $date = dol_print_date($val["date"], 'day');

        $ref = getSourceDocRef($val, $tabtype[$key]);

        // Bank
        foreach ($tabbq[$key] as $k => $mt) {
            if ($mt) {
                $reflabel = '';
                if (!empty($val['lib'])) {
                    $reflabel .= $val['lib'] . " - ";
                }
                $reflabel .= $langs->trans("Bank") . ' ' . $val['bank_account_ref'];
                if (!empty($val['soclib'])) {
                    $reflabel .= " - " . $val['soclib'];
                }

                //var_dump($tabpay[$key]);
                print '<!-- Bank bank.rowid=' . $key . ' type=' . $tabpay[$key]['type'] . ' ref=' . $tabpay[$key]['ref'] . '-->';
                print '<tr class="oddeven">';

                // Date
                print "<td>" . $date . "</td>";

                // Ref
                print "<td>" . dol_escape_htmltag($ref) . "</td>";

                // Ledger account
                $accounttoshow = length_accountg($k);
                if (empty($accounttoshow) || $accounttoshow == 'NotDefined') {
                    $accounttoshow = '<span class="error">' . $langs->trans("BankAccountNotDefined") . '</span>';
                }
                print '<td class="maxwidth300" title="' . dol_escape_htmltag(dol_string_nohtmltag($accounttoshow)) . '">';
                print $accounttoshow;
                print "</td>";

                // Subledger account
                print '<td class="maxwidth300">';
                /*$accounttoshow = length_accountg($k);
                if (empty($accounttoshow) || $accounttoshow == 'NotDefined')
                {
                    print '<span class="error">'.$langs->trans("BankAccountNotDefined").'</span>';
                }
                else print $accounttoshow;*/
                print "</td>";

                // Label operation
                print '<td>';
                print $reflabel;    // This is already html escaped content
                print "</td>";

                print '<td class="center">' . $val["type_payment"] . "</td>";
                print '<td class="right nowraponall amount">' . ($mt >= 0 ? price($mt) : '') . "</td>";
                print '<td class="right nowraponall amount">' . ($mt < 0 ? price(-$mt) : '') . "</td>";
                print "</tr>";

                $i++;
            }
        }

        // Third party
        if (is_array($tabtp[$key])) {
            foreach ($tabtp[$key] as $k => $mt) {
                if ($mt) {
                    $reflabel = '';
                    if (!empty($val['lib'])) {
                        $reflabel .= $val['lib'] . ($val['soclib'] ? " - " : "");
                    }
                    if ($tabtype[$key] == 'banktransfert') {
                        $reflabel .= $langs->trans('TransitionalAccount') . ' ' . $account_transfer;
                    } else {
                        $reflabel .= $val['soclib'];
                    }

                    print '<!-- Thirdparty bank.rowid=' . $key . ' -->';
                    print '<tr class="oddeven">';

                    // Date
                    print "<td>" . $date . "</td>";

                    // Ref
                    print "<td>" . dol_escape_htmltag($ref) . "</td>";

                    // Ledger account
                    $account_ledger = $k;
                    // Try to force general ledger account depending on type
                    if ($tabtype[$key] == 'payment') {
                        $account_ledger = getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER');
                    }
                    if ($tabtype[$key] == 'payment_supplier') {
                        $account_ledger = getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER');
                    }
                    if ($tabtype[$key] == 'payment_expensereport') {
                        $account_ledger = getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT');
                    }
                    if ($tabtype[$key] == 'payment_salary') {
                        $account_ledger = getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT');
                    }
                    if ($tabtype[$key] == 'payment_vat') {
                        $account_ledger = getDolGlobalString('ACCOUNTING_VAT_PAY_ACCOUNT');
                    }
                    if ($tabtype[$key] == 'member') {
                        $account_ledger = getDolGlobalString('ADHERENT_SUBSCRIPTION_ACCOUNTINGACCOUNT');
                    }
                    if ($tabtype[$key] == 'payment_various') {
                        $account_ledger = $tabpay[$key]["account_various"];
                    }
                    $accounttoshow = length_accountg($account_ledger);
                    if (empty($accounttoshow) || $accounttoshow == 'NotDefined') {
                        if ($tabtype[$key] == 'unknown') {
                            // We will accept writing, but into a waiting account
                            if (!getDolGlobalString('ACCOUNTING_ACCOUNT_SUSPENSE') || getDolGlobalString('ACCOUNTING_ACCOUNT_SUSPENSE') == '-1') {
                                $accounttoshow = '<span class="error small">' . $langs->trans('UnknownAccountForThirdpartyAndWaitingAccountNotDefinedBlocking') . '</span>';
                            } else {
                                $accounttoshow = '<span class="warning small">' . $langs->trans('UnknownAccountForThirdparty', length_accountg(getDolGlobalString('ACCOUNTING_ACCOUNT_SUSPENSE'))) . '</span>'; // We will use a waiting account
                            }
                        } else {
                            // We will refuse writing
                            $errorstring = 'UnknownAccountForThirdpartyBlocking';
                            if ($tabtype[$key] == 'payment') {
                                $errorstring = 'MainAccountForCustomersNotDefined';
                            }
                            if ($tabtype[$key] == 'payment_supplier') {
                                $errorstring = 'MainAccountForSuppliersNotDefined';
                            }
                            if ($tabtype[$key] == 'payment_expensereport') {
                                $errorstring = 'MainAccountForUsersNotDefined';
                            }
                            if ($tabtype[$key] == 'payment_salary') {
                                $errorstring = 'MainAccountForUsersNotDefined';
                            }
                            if ($tabtype[$key] == 'payment_vat') {
                                $errorstring = 'MainAccountForVatPaymentNotDefined';
                            }
                            if ($tabtype[$key] == 'member') {
                                $errorstring = 'MainAccountForSubscriptionPaymentNotDefined';
                            }
                            $accounttoshow = '<span class="error small">' . $langs->trans($errorstring) . '</span>';
                        }
                    }
                    print '<td class="maxwidth300" title="' . dol_escape_htmltag(dol_string_nohtmltag($accounttoshow)) . '">';
                    print $accounttoshow;    // This is a HTML string
                    print "</td>";

                    // Subledger account
                    $accounttoshowsubledger = '';
                    if (in_array($tabtype[$key], ['payment', 'payment_supplier', 'payment_expensereport', 'payment_salary', 'payment_various'])) {    // Type of payments that uses a subledger
                        $accounttoshowsubledger = length_accounta($k);
                        if ($accounttoshow != $accounttoshowsubledger) {
                            if (empty($accounttoshowsubledger) || $accounttoshowsubledger == 'NotDefined') {
                                //var_dump($tabpay[$key]);
                                //var_dump($tabtype[$key]);
                                //var_dump($tabbq[$key]);
                                //print '<span class="error">'.$langs->trans("ThirdpartyAccountNotDefined").'</span>';
                                if (!empty($tabcompany[$key]['code_compta'])) {
                                    if (in_array($tabtype[$key], ['payment_various', 'payment_salary'])) {
                                        // For such case, if subledger is not defined, we won't use subledger accounts.
                                        $accounttoshowsubledger = '<span class="warning small">' . $langs->trans("ThirdpartyAccountNotDefinedOrThirdPartyUnknownSubledgerIgnored") . '</span>';
                                    } else {
                                        $accounttoshowsubledger = '<span class="warning small">' . $langs->trans("ThirdpartyAccountNotDefinedOrThirdPartyUnknown", $tabcompany[$key]['code_compta']) . '</span>';
                                    }
                                } else {
                                    $accounttoshowsubledger = '<span class="error small">' . $langs->trans("ThirdpartyAccountNotDefinedOrThirdPartyUnknownBlocking") . '</span>';
                                }
                            }
                        } else {
                            $accounttoshowsubledger = '';
                        }
                    }
                    print '<td class="maxwidth300">';
                    print $accounttoshowsubledger;    // This is a html string
                    print "</td>";

                    print "<td>" . $reflabel . "</td>";

                    print '<td class="center">' . $val["type_payment"] . "</td>";

                    print '<td class="right nowraponall amount">' . ($mt < 0 ? price(-$mt) : '') . "</td>";

                    print '<td class="right nowraponall amount">' . ($mt >= 0 ? price($mt) : '') . "</td>";

                    print "</tr>";

                    $i++;
                }
            }
        } else {    // Waiting account
            foreach ($tabbq[$key] as $k => $mt) {
                if ($mt) {
                    $reflabel = '';
                    if (!empty($val['lib'])) {
                        $reflabel .= $val['lib'] . " - ";
                    }
                    $reflabel .= 'WaitingAccount';

                    print '<!-- Wait bank.rowid=' . $key . ' -->';
                    print '<tr class="oddeven">';
                    print "<td>" . $date . "</td>";
                    print "<td>" . $ref . "</td>";
                    // Ledger account
                    print "<td>";
                    /*if (empty($accounttoshow) || $accounttoshow == 'NotDefined')
                    {
                        print '<span class="error">'.$langs->trans("WaitAccountNotDefined").'</span>';
                    }
                    else */
                    print length_accountg(getDolGlobalString('ACCOUNTING_ACCOUNT_SUSPENSE'));
                    print "</td>";
                    // Subledger account
                    print "<td>";
                    print "</td>";
                    print "<td>" . dol_escape_htmltag($reflabel) . "</td>";
                    print '<td class="center">' . $val["type_payment"] . "</td>";
                    print '<td class="right nowraponall amount">' . ($mt < 0 ? price(-$mt) : '') . "</td>";
                    print '<td class="right nowraponall amount">' . ($mt >= 0 ? price($mt) : '') . "</td>";
                    print "</tr>";

                    $i++;
                }
            }
        }
    }

    if (!$i) {
        $colspan = 8;
        print '<tr class="oddeven"><td colspan="' . $colspan . '"><span class="opacitymedium">' . $langs->trans("NoRecordFound") . '</span></td></tr>';
    }

    print "</table>";
    print '</div>';

    llxFooter();
}
