<?php

use DoliCore\Form\Form;
use DoliCore\Form\FormAccounting;
use DoliModules\Accounting\Model\BookKeeping;

$form = new Form($db);
$formaccounting = new FormAccounting($db);

$title = $langs->trans("CreateMvts");
$help_url = 'EN:Module_Double_Entry_Accounting|FR:Module_Comptabilit&eacute;_en_Partie_Double';
llxHeader('', $title, $help_url);

// Confirmation to delete the command
if ($action == 'delete') {
    $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $id . '&mode=' . $mode, $langs->trans('DeleteMvt'), $langs->trans('ConfirmDeleteMvt', $langs->transnoentitiesnoconv("RegistrationInAccounting")), 'confirm_delete', '', 0, 1);
    print $formconfirm;
}

if ($action == 'create') {
    print load_fiche_titre($title);

    $object = new BookKeeping($db);
    $next_num_mvt = $object->getNextNumMvt('_tmp');

    if (empty($next_num_mvt)) {
        dol_print_error(null, 'Failed to get next piece number');
    }

    print '<form action="' . $_SERVER['PHP_SELF'] . '" name="create_mvt" method="POST">';
    if ($optioncss != '') {
        print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
    }
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="confirm_create">' . "\n";
    print '<input type="hidden" name="next_num_mvt" value="' . $next_num_mvt . '">' . "\n";
    print '<input type="hidden" name="mode" value="_tmp">' . "\n";

    print dol_get_fiche_head();

    print '<table class="border centpercent">';

    /*print '<tr>';
    print '<td class="titlefieldcreate fieldrequired">' . $langs->trans("NumPiece") . '</td>';
    print '<td>' . $next_num_mvt . '</td>';
    print '</tr>';*/

    print '<tr>';
    print '<td class="titlefieldcreate fieldrequired">' . $langs->trans("Docdate") . '</td>';
    print '<td>';
    print $form->selectDate('', 'doc_date', 0, 0, 0, "create_mvt", 1, 1);
    print '</td>';
    print '</tr>';

    print '<tr>';
    print '<td class="fieldrequired">' . $langs->trans("Codejournal") . '</td>';
    print '<td>' . $formaccounting->select_journal($journal_code, 'code_journal', 0, 0, 1, 1) . '</td>';
    print '</tr>';

    print '<tr>';
    print '<td class="fieldrequired">' . $langs->trans("Piece") . '</td>';
    print '<td><input type="text" class="minwidth200" name="doc_ref" value="' . GETPOST('doc_ref', 'alpha') . '"></td>';
    print '</tr>';

    /*
    print '<tr>';
    print '<td>' . $langs->trans("Doctype") . '</td>';
    print '<td><input type="text" class="minwidth200 name="doc_type" value=""/></td>';
    print '</tr>';
    */

    print '</table>';

    print dol_get_fiche_end();

    print $form->buttonsSaveCancel("Create");

    print '</form>';
} else {
    $object = new BookKeeping($db);
    $result = $object->fetchPerMvt($piece_num, $mode);
    if ($result < 0) {
        setEventMessages($object->error, $object->errors, 'errors');
    }

    if (!empty($object->piece_num)) {
        $backlink = '<a href="' . DOL_URL_ROOT . '/accountancy/bookkeeping/list.php?restore_lastsearch_values=1">' . $langs->trans('BackToList') . '</a>';

        if ($mode == '_tmp') {
            print load_fiche_titre($langs->trans("CreateMvts"), $backlink);
        } else {
            print load_fiche_titre($langs->trans("UpdateMvts"), $backlink);
        }

        $head = [];
        $h = 0;
        $head[$h][0] = $_SERVER['PHP_SELF'] . '?piece_num=' . $object->piece_num . ($mode ? '&mode=' . $mode : '');
        $head[$h][1] = $langs->trans("Transaction");
        $head[$h][2] = 'transaction';
        $h++;

        print dol_get_fiche_head($head, 'transaction', '', -1);

        //dol_banner_tab($object, '', $backlink);

        print '<div class="fichecenter">';
        print '<div class="fichehalfleft">';

        print '<div class="underbanner clearboth"></div>';
        print '<table class="border tableforfield" width="100%">';

        // Account movement
        print '<tr>';
        print '<td class="titlefield">' . $langs->trans("NumMvts") . '</td>';
        print '<td>' . ($mode == '_tmp' ? '<span class="opacitymedium" title="Id tmp ' . $object->piece_num . '">' . $langs->trans("Draft") . '</span>' : $object->piece_num) . '</td>';
        print '</tr>';

        // Date
        print '<tr><td>';
        print '<table class="nobordernopadding centpercent"><tr><td>';
        print $langs->trans('Docdate');
        print '</td>';
        if ($action != 'editdate') {
            print '<td class="right"><a class="editfielda reposition" href="' . $_SERVER['PHP_SELF'] . '?action=editdate&token=' . newToken() . '&piece_num=' . urlencode((string) ($object->piece_num)) . '&mode=' . urlencode((string) ($mode)) . '">' . img_edit($langs->transnoentitiesnoconv('SetDate'), 1) . '</a></td>';
        }
        print '</tr></table>';
        print '</td><td colspan="3">';
        if ($action == 'editdate') {
            print '<form name="setdate" action="' . $_SERVER['PHP_SELF'] . '?piece_num=' . $object->piece_num . '" method="post">';
            if ($optioncss != '') {
                print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
            }
            print '<input type="hidden" name="token" value="' . newToken() . '">';
            print '<input type="hidden" name="action" value="setdate">';
            print '<input type="hidden" name="mode" value="' . $mode . '">';
            print $form->selectDate($object->doc_date ? $object->doc_date : -1, 'doc_date', 0, 0, 0, "setdate");
            print '<input type="submit" class="button button-edit" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            print $object->doc_date ? dol_print_date($object->doc_date, 'day') : '&nbsp;';
        }
        print '</td>';
        print '</tr>';

        // Journal
        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('Codejournal');
        print '</td>';
        if ($action != 'editjournal') {
            print '<td class="right"><a class="editfielda reposition" href="' . $_SERVER['PHP_SELF'] . '?action=editjournal&token=' . newToken() . '&piece_num=' . urlencode((string) ($object->piece_num)) . '&mode=' . urlencode((string) ($mode)) . '">' . img_edit($langs->transnoentitiesnoconv('Edit'), 1) . '</a></td>';
        }
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'editjournal') {
            print '<form name="setjournal" action="' . $_SERVER['PHP_SELF'] . '?piece_num=' . $object->piece_num . '" method="post">';
            if ($optioncss != '') {
                print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
            }
            print '<input type="hidden" name="token" value="' . newToken() . '">';
            print '<input type="hidden" name="action" value="setjournal">';
            print '<input type="hidden" name="mode" value="' . $mode . '">';
            print $formaccounting->select_journal($object->code_journal, 'code_journal', 0, 0, [], 1, 1);
            print '<input type="submit" class="button button-edit" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            print $object->code_journal;
        }
        print '</td>';
        print '</tr>';

        // Ref document
        print '<tr><td>';
        print '<table class="nobordernopadding centpercent"><tr><td>';
        print $langs->trans('Piece');
        print '</td>';
        if ($action != 'editdocref') {
            print '<td class="right"><a class="editfielda reposition" href="' . $_SERVER['PHP_SELF'] . '?action=editdocref&token=' . newToken() . '&piece_num=' . urlencode((string) ($object->piece_num)) . '&mode=' . urlencode((string) ($mode)) . '">' . img_edit($langs->transnoentitiesnoconv('Edit'), 1) . '</a></td>';
        }
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'editdocref') {
            print '<form name="setdocref" action="' . $_SERVER['PHP_SELF'] . '?piece_num=' . $object->piece_num . '" method="post">';
            if ($optioncss != '') {
                print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
            }
            print '<input type="hidden" name="token" value="' . newToken() . '">';
            print '<input type="hidden" name="action" value="setdocref">';
            print '<input type="hidden" name="mode" value="' . $mode . '">';
            print '<input type="text" size="20" name="doc_ref" value="' . dol_escape_htmltag($object->doc_ref) . '">';
            print '<input type="submit" class="button button-edit" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            print $object->doc_ref;
        }
        print '</td>';
        print '</tr>';

        print '</table>';

        print '</div>';

        print '<div class="fichehalfright">';

        print '<div class="underbanner clearboth"></div>';
        print '<table class="border tableforfield centpercent">';

        // Doc type
        if (!empty($object->doc_type)) {
            print '<tr>';
            print '<td class="titlefield">' . $langs->trans("Doctype") . '</td>';
            print '<td>' . $object->doc_type . '</td>';
            print '</tr>';
        }

        // Date document creation
        print '<tr>';
        print '<td class="titlefield">' . $langs->trans("DateCreation") . '</td>';
        print '<td>';
        print $object->date_creation ? dol_print_date($object->date_creation, 'day') : '&nbsp;';
        print '</td>';
        print '</tr>';

        // Don't show in tmp mode, inevitably empty
        if ($mode != "_tmp") {
            // Date document export
            print '<tr>';
            print '<td class="titlefield">' . $langs->trans("DateExport") . '</td>';
            print '<td>';
            print $object->date_export ? dol_print_date($object->date_export, 'dayhour') : '&nbsp;';
            print '</td>';
            print '</tr>';

            // Date document validation
            print '<tr>';
            print '<td class="titlefield">' . $langs->trans("DateValidation") . '</td>';
            print '<td>';
            print $object->date_validation ? dol_print_date($object->date_validation, 'dayhour') : '&nbsp;';
            print '</td>';
            print '</tr>';
        }

        // Validate
        /*
        print '<tr>';
        print '<td class="titlefield">' . $langs->trans("Status") . '</td>';
        print '<td>';
            if (empty($object->validated)) {
                print '<a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?piece_num=' . $line->id . '&action=enable&token='.newToken().'">';
                print img_picto($langs->trans("Disabled"), 'switch_off');
                print '</a>';
            } else {
                print '<a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?piece_num=' . $line->id . '&action=disable&token='.newToken().'">';
                print img_picto($langs->trans("Activated"), 'switch_on');
                print '</a>';
            }
            print '</td>';
        print '</tr>';
        */

        // check data
        /*
        print '<tr>';
        print '<td class="titlefield">' . $langs->trans("Control") . '</td>';
        if ($object->doc_type == 'customer_invoice')
        {
         $sqlmid = 'SELECT rowid as ref';
            $sqlmid .= " FROM ".MAIN_DB_PREFIX."facture as fac";
            $sqlmid .= " WHERE fac.rowid=" . ((int) $object->fk_doc);
            dol_syslog("accountancy/bookkeeping/card.php::sqlmid=" . $sqlmid, LOG_DEBUG);
            $resultmid = $db->query($sqlmid);
            if ($resultmid) {
                $objmid = $db->fetch_object($resultmid);
                $invoicestatic = new Facture($db);
                $invoicestatic->fetch($objmid->ref);
                $ref=$langs->trans("Invoice").' '.$invoicestatic->getNomUrl(1);
            }
            else dol_print_error($db);
        }
        print '<td>' . $ref .'</td>';
        print '</tr>';
        */
        print "</table>\n";

        print '</div>';

        print dol_get_fiche_end();

        print '<div class="clearboth"></div>';

        print '<br>';

        $result = $object->fetchAllPerMvt($piece_num, $mode);   // This load $object->linesmvt

        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
        } else {
            // List of movements
            print load_fiche_titre($langs->trans("ListeMvts"), '', '');

            print '<form action="' . $_SERVER['PHP_SELF'] . '?piece_num=' . $object->piece_num . '" method="post">';
            if ($optioncss != '') {
                print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
            }
            print '<input type="hidden" name="token" value="' . newToken() . '">';
            print '<input type="hidden" name="doc_date" value="' . $object->doc_date . '">' . "\n";
            print '<input type="hidden" name="doc_type" value="' . $object->doc_type . '">' . "\n";
            print '<input type="hidden" name="doc_ref" value="' . $object->doc_ref . '">' . "\n";
            print '<input type="hidden" name="code_journal" value="' . $object->code_journal . '">' . "\n";
            print '<input type="hidden" name="fk_doc" value="' . $object->fk_doc . '">' . "\n";
            print '<input type="hidden" name="fk_docdet" value="' . $object->fk_docdet . '">' . "\n";
            print '<input type="hidden" name="mode" value="' . $mode . '">' . "\n";

            if (count($object->linesmvt) > 0) {
                print '<div class="div-table-responsive-no-min">';
                print '<table class="noborder centpercent">';

                $total_debit = 0;
                $total_credit = 0;

                print '<tr class="liste_titre">';

                print_liste_field_titre("AccountAccountingShort");
                print_liste_field_titre("SubledgerAccount");
                print_liste_field_titre("LabelOperation");
                print_liste_field_titre("AccountingDebit", "", "", "", "", 'class="right"');
                print_liste_field_titre("AccountingCredit", "", "", "", "", 'class="right"');
                if (empty($object->date_validation)) {
                    print_liste_field_titre("Action", "", "", "", "", 'width="60"', "", "", 'center ');
                } else {
                    print_liste_field_titre("");
                }

                print "</tr>\n";

                // Add an empty line if there is not yet
                if (!empty($object->linesmvt[0])) {
                    $tmpline = $object->linesmvt[0];
                    if (!empty($tmpline->numero_compte)) {
                        $line = new BookKeepingLine($db);
                        $object->linesmvt[] = $line;
                    }
                }

                foreach ($object->linesmvt as $line) {
                    print '<tr class="oddeven" data-lineid="' . ((int) $line->id) . '">';
                    $total_debit += $line->debit;
                    $total_credit += $line->credit;

                    if ($action == 'update' && $line->id == $id) {
                        print '<!-- td columns in edit mode -->';
                        print '<td>';
                        print $formaccounting->select_account((GETPOSTISSET("accountingaccount_number") ? GETPOST("accountingaccount_number", "alpha") : $line->numero_compte), 'accountingaccount_number', 1, [], 1, 1, 'minwidth200 maxwidth500');
                        print '</td>';
                        print '<td>';
                        // TODO For the moment we keep a free input text instead of a combo. The select_auxaccount has problem because:
                        // It does not use the setup of "key pressed" to select a thirdparty and this hang browser on large databases.
                        // Also, it is not possible to use a value that is not in the list.
                        // Also, the label is not automatically filled when a value is selected.
                        if (getDolGlobalString('ACCOUNTANCY_COMBO_FOR_AUX')) {
                            print $formaccounting->select_auxaccount((GETPOSTISSET("subledger_account") ? GETPOST("subledger_account", "alpha") : $line->subledger_account), 'subledger_account', 1, 'maxwidth250', '', 'subledger_label');
                        } else {
                            print '<input type="text" class="maxwidth150" name="subledger_account" value="' . (GETPOSTISSET("subledger_account") ? GETPOST("subledger_account", "alpha") : $line->subledger_account) . '" placeholder="' . dol_escape_htmltag($langs->trans("SubledgerAccount")) . '">';
                        }
                        // Add also input for subledger label
                        print '<br><input type="text" class="maxwidth150" name="subledger_label" value="' . (GETPOSTISSET("subledger_label") ? GETPOST("subledger_label", "alpha") : $line->subledger_label) . '" placeholder="' . dol_escape_htmltag($langs->trans("SubledgerAccountLabel")) . '">';
                        print '</td>';
                        print '<td><input type="text" class="minwidth200" name="label_operation" value="' . (GETPOSTISSET("label_operation") ? GETPOST("label_operation", "alpha") : $line->label_operation) . '"></td>';
                        print '<td class="right"><input type="text" size="6" class="right" name="debit" value="' . (GETPOSTISSET("debit") ? GETPOST("debit", "alpha") : price($line->debit)) . '"></td>';
                        print '<td class="right"><input type="text" size="6" class="right" name="credit" value="' . (GETPOSTISSET("credit") ? GETPOST("credit", "alpha") : price($line->credit)) . '"></td>';
                        print '<td>';
                        print '<input type="hidden" name="id" value="' . $line->id . '">' . "\n";
                        print '<input type="submit" class="button" name="update" value="' . $langs->trans("Update") . '">';
                        print '</td>';
                    } elseif (empty($line->numero_compte) || (empty($line->debit) && empty($line->credit))) {
                        if ($action == "" || $action == 'add') {
                            print '<!-- td columns in add mode -->';
                            print '<td>';
                            print $formaccounting->select_account('', 'accountingaccount_number', 1, [], 1, 1, 'minwidth200 maxwidth500');
                            print '</td>';
                            print '<td>';
                            // TODO For the moment we keep a free input text instead of a combo. The select_auxaccount has problem because:
                            // It does not use the setup of "key pressed" to select a thirdparty and this hang browser on large databases.
                            // Also, it is not possible to use a value that is not in the list.
                            // Also, the label is not automatically filled when a value is selected.
                            if (getDolGlobalString('ACCOUNTANCY_COMBO_FOR_AUX')) {
                                print $formaccounting->select_auxaccount('', 'subledger_account', 1, 'maxwidth250', '', 'subledger_label');
                            } else {
                                print '<input type="text" class="maxwidth150" name="subledger_account" value="" placeholder="' . dol_escape_htmltag($langs->trans("SubledgerAccount")) . '">';
                            }
                            print '<br><input type="text" class="maxwidth150" name="subledger_label" value="" placeholder="' . dol_escape_htmltag($langs->trans("SubledgerAccountLabel")) . '">';
                            print '</td>';
                            print '<td><input type="text" class="minwidth200" name="label_operation" value="' . $label_operation . '"/></td>';
                            print '<td class="right"><input type="text" size="6" class="right" name="debit" value=""/></td>';
                            print '<td class="right"><input type="text" size="6" class="right" name="credit" value=""/></td>';
                            print '<td class="center"><input type="submit" class="button small" name="save" value="' . $langs->trans("Add") . '"></td>';
                        }
                    } else {
                        print '<!-- td columns in display mode -->';
                        $resultfetch = $accountingaccount->fetch(null, $line->numero_compte, true);
                        print '<td>';
                        if ($resultfetch > 0) {
                            print $accountingaccount->getNomUrl(0, 1, 1, '', 0);
                        } else {
                            print $line->numero_compte . ' <span class="warning">(' . $langs->trans("AccountRemovedFromCurrentChartOfAccount") . ')</span>';
                        }
                        print '</td>';
                        print '<td>' . length_accounta($line->subledger_account);
                        if ($line->subledger_label) {
                            print ' - <span class="opacitymedium">' . $line->subledger_label . '</span>';
                        }
                        print '</td>';
                        print '<td>' . $line->label_operation . '</td>';
                        print '<td class="right nowraponall amount">' . ($line->debit != 0 ? price($line->debit) : '') . '</td>';
                        print '<td class="right nowraponall amount">' . ($line->credit != 0 ? price($line->credit) : '') . '</td>';

                        print '<td class="center nowraponall">';
                        if (empty($line->date_export) && empty($line->date_validation)) {
                            print '<a class="editfielda reposition" href="' . $_SERVER['PHP_SELF'] . '?action=update&id=' . $line->id . '&piece_num=' . urlencode($line->piece_num) . '&mode=' . urlencode($mode) . '&token=' . urlencode(newToken()) . '">';
                            print img_edit('', 0, 'class="marginrightonly"');
                            print '</a> &nbsp;';
                        } else {
                            print '<a class="editfielda nohover cursornotallowed reposition disabled" href="#" title="' . dol_escape_htmltag($langs->trans("ForbiddenTransactionAlreadyExported")) . '">';
                            print img_edit($langs->trans("ForbiddenTransactionAlreadyExported"), 0, 'class="marginrightonly"');
                            print '</a> &nbsp;';
                        }

                        if (empty($line->date_validation)) {
                            $actiontodelete = 'delete';
                            if ($mode == '_tmp' || $action != 'delmouv') {
                                $actiontodelete = 'confirm_delete';
                            }

                            print '<a href="' . $_SERVER['PHP_SELF'] . '?action=' . $actiontodelete . '&id=' . $line->id . '&piece_num=' . urlencode($line->piece_num) . '&mode=' . urlencode($mode) . '&token=' . urlencode(newToken()) . '">';
                            print img_delete();
                            print '</a>';
                        } else {
                            print '<a class="editfielda nohover cursornotallowed disabled" href="#" title="' . dol_escape_htmltag($langs->trans("ForbiddenTransactionAlreadyExported")) . '">';
                            print img_delete($langs->trans("ForbiddenTransactionAlreadyValidated"));
                            print '</a>';
                        }

                        print '</td>';
                    }
                    print "</tr>\n";
                }

                $total_debit = price2num($total_debit, 'MT');
                $total_credit = price2num($total_credit, 'MT');

                if ($total_debit != $total_credit) {
                    setEventMessages(null, [$langs->trans('MvtNotCorrectlyBalanced', $total_debit, $total_credit)], 'warnings');
                }

                print '</table>';
                print '</div>';

                if ($mode == '_tmp' && $action == '') {
                    print '<br>';
                    print '<div class="center">';
                    if ($total_debit == $total_credit) {
                        print '<a class="button" href="' . $_SERVER['PHP_SELF'] . '?piece_num=' . $object->piece_num . '&action=valid">' . $langs->trans("ValidTransaction") . '</a>';
                    } else {
                        print '<input type="submit" class="button" disabled="disabled" href="#" title="' . dol_escape_htmltag($langs->trans("MvtNotCorrectlyBalanced", $debit, $credit)) . '" value="' . dol_escape_htmltag($langs->trans("ValidTransaction")) . '">';
                    }

                    print ' &nbsp; ';
                    print '<a class="button button-cancel" href="' . DOL_URL_ROOT . '/accountancy/bookkeeping/list.php">' . $langs->trans("Cancel") . '</a>';

                    print "</div>";
                }
            }

            print '</form>';
        }
    } else {
        print load_fiche_titre($langs->trans("NoRecords"));
    }
}

print dol_get_fiche_end();

// End of page
llxFooter();
