<?php

$form = new Form($db);
$formaccounting = new FormAccounting($db);

$title = $langs->trans('AccountingCategory');
$help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';

llxHeader('', $title, $help_url);

$linkback = '<a href="' . DOL_URL_ROOT . '/accountancy/admin/categories_list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';
$titlepicto = 'setup';

print load_fiche_titre($langs->trans('AccountingCategory'), $linkback, $titlepicto);

print '<form name="add" action="' . $_SERVER['PHP_SELF'] . '" method="POST">' . "\n";
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="display">';

print dol_get_fiche_head();

print '<table class="border centpercent">';

// Select the category
print '<tr><td class="titlefield">' . $langs->trans("AccountingCategory") . '</td>';
print '<td>';
$s = $formaccounting->select_accounting_category($cat_id, 'account_category', 1, 0, 0, 0);
if ($formaccounting->nbaccounts_category <= 0) {
    print '<span class="opacitymedium">' . $s . '</span>';
} else {
    print $s;
    print '<input type="submit" class="button small" value="' . $langs->trans("Select") . '">';
}
print '</td></tr>';

print '</table>';

print dol_get_fiche_end();


// Select the accounts
if (!empty($cat_id)) {
    $return = $accountingcategory->getAccountsWithNoCategory($cat_id);
    if ($return < 0) {
        setEventMessages(null, $accountingcategory->errors, 'errors');
    }
    print '<br>';

    $arraykeyvalue = [];
    foreach ($accountingcategory->lines_cptbk as $key => $val) {
        $doc_ref = !empty($val->doc_ref) ? $val->doc_ref : '';
        $arraykeyvalue[length_accountg($val->numero_compte)] = length_accountg($val->numero_compte) . ' - ' . $val->label_compte . ($doc_ref ? ' ' . $doc_ref : '');
    }

    if (is_array($accountingcategory->lines_cptbk) && count($accountingcategory->lines_cptbk) > 0) {
        print img_picto($langs->trans("AccountingAccount"), 'accounting_account', 'class="pictofixedwith"');
        print $form->multiselectarray('cpt_bk', $arraykeyvalue, GETPOST('cpt_bk', 'array'), 0, 0, '', 0, "80%", '', '', $langs->transnoentitiesnoconv("AddAccountFromBookKeepingWithNoCategories"));
        print '<input type="submit" class="button button-add small" id="" class="action-delete" value="' . $langs->trans("Add") . '"> ';
    }
}

print '</form>';


if ((empty($action) || $action == 'display' || $action == 'delete') && $cat_id > 0) {
    $param = 'account_category=' . ((int) $cat_id);

    print '<br>';
    print '<table class="noborder centpercent">' . "\n";
    print '<tr class="liste_titre">';
    print getTitleFieldOfList('AccountAccounting', 0, $_SERVER['PHP_SELF'], 'account_number', '', $param, '', $sortfield, $sortorder, '') . "\n";
    print getTitleFieldOfList('Label', 0, $_SERVER['PHP_SELF'], 'label', '', $param, '', $sortfield, $sortorder, '') . "\n";
    print getTitleFieldOfList('', 0, $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, '') . "\n";
    print '</tr>' . "\n";

    if (!empty($cat_id)) {
        $return = $accountingcategory->display($cat_id); // This load ->lines_display
        if ($return < 0) {
            setEventMessages(null, $accountingcategory->errors, 'errors');
        }

        if (is_array($accountingcategory->lines_display) && count($accountingcategory->lines_display) > 0) {
            $accountingcategory->lines_display = dol_sort_array($accountingcategory->lines_display, $sortfield, $sortorder, -1, 0, 1);

            foreach ($accountingcategory->lines_display as $cpt) {
                print '<tr class="oddeven">';
                print '<td>' . length_accountg($cpt->account_number) . '</td>';
                print '<td>' . $cpt->label . '</td>';
                print '<td class="right">';
                print '<a href="' . $_SERVER['PHP_SELF'] . '?action=delete&token=' . newToken() . '&account_category=' . $cat_id . '&cptid=' . $cpt->rowid . '">';
                print $langs->trans("DeleteFromCat");
                print img_picto($langs->trans("DeleteFromCat"), 'unlink', 'class="paddingleft"');
                print "</a>";
                print "</td>";
                print "</tr>\n";
            }
        } else {
            print '<tr><td colspan="3"><span class="opacitymedium">' . $langs->trans("NoRecordFound") . '</span></td></tr>';
        }
    }

    print "</table>";
}

// End of page
llxFooter();
