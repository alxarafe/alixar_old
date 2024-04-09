<?php

$form = new Form($db);
$formaccounting = new FormAccounting($db);

$help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';

llxHeader('', $langs->trans('MenuDefaultAccounts'), $help_url);

$linkback = '';
print load_fiche_titre($langs->trans('MenuDefaultAccounts'), $linkback, 'title_accountancy');

print '<span class="opacitymedium">' . $langs->trans("DefaultBindingDesc") . '</span><br>';
print '<br>';

print '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update">';


// Define main accounts for thirdparty

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td>' . $langs->trans("ThirdParties") . ' | ' . $langs->trans("Users") . '</td><td></td></tr>';

foreach ($list_account_main as $key) {
    print '<tr class="oddeven value">';
    // Param
    $label = $langs->trans($key);
    $keydesc = $key . '_Desc';

    $htmltext = $langs->trans($keydesc);
    print '<td class="fieldrequired">';
    if ($key == 'ACCOUNTING_ACCOUNT_CUSTOMER') {
        print img_picto('', 'company', 'class="pictofixedwidth"');
    } elseif ($key == 'ACCOUNTING_ACCOUNT_SUPPLIER') {
        print img_picto('', 'company', 'class="pictofixedwidth"');
    } else {
        print img_picto('', 'user', 'class="pictofixedwidth"');
    }
    print $form->textwithpicto($label, $htmltext);
    print '</td>';
    // Value
    print '<td class="right">'; // Do not force class=right, or it align also the content of the select box
    $key_value = getDolGlobalString($key);
    print $formaccounting->select_account($key_value, $key, 1, '', 1, 1, 'minwidth100 maxwidth300 maxwidthonsmartphone', 'accountsmain');
    print '</td>';
    print '</tr>';
}
print "</table>\n";
print "</div>\n";


print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

foreach ($list_account as $key) {
    $reg = [];
    if (preg_match('/---(.*)---/', $key, $reg)) {
        print '<tr class="liste_titre"><td>' . $langs->trans($reg[1]) . '</td><td></td></tr>';
    } else {
        print '<tr class="oddeven value">';
        // Param
        $label = $langs->trans($key);
        print '<td>';
        if (preg_match('/^ACCOUNTING_PRODUCT/', $key)) {
            print img_picto('', 'product', 'class="pictofixedwidth"');
        } elseif (preg_match('/^ACCOUNTING_SERVICE/', $key)) {
            print img_picto('', 'service', 'class="pictofixedwidth"');
        } elseif (preg_match('/^ACCOUNTING_VAT_PAY_ACCOUNT/', $key)) {
            print img_picto('', 'payment_vat', 'class="pictofixedwidth"');
        } elseif (preg_match('/^ACCOUNTING_VAT/', $key)) {
            print img_picto('', 'vat', 'class="pictofixedwidth"');
        } elseif (preg_match('/^ACCOUNTING_ACCOUNT_CUSTOMER/', $key)) {
            print img_picto('', 'bill', 'class="pictofixedwidth"');
        } elseif (preg_match('/^LOAN_ACCOUNTING_ACCOUNT/', $key)) {
            print img_picto('', 'loan', 'class="pictofixedwidth"');
        } elseif (preg_match('/^DONATION_ACCOUNTING/', $key)) {
            print img_picto('', 'donation', 'class="pictofixedwidth"');
        } elseif (preg_match('/^ADHERENT_SUBSCRIPTION/', $key)) {
            print img_picto('', 'member', 'class="pictofixedwidth"');
        } elseif (preg_match('/^ACCOUNTING_ACCOUNT_TRANSFER/', $key)) {
            print img_picto('', 'bank_account', 'class="pictofixedwidth"');
        } elseif (preg_match('/^ACCOUNTING_ACCOUNT_SUSPENSE/', $key)) {
            print img_picto('', 'question', 'class="pictofixedwidth"');
        }
        // Note: account for revenue stamp are store into dictionary of revenue stamp. There is no default value.
        print $label;
        print '</td>';
        // Value
        print '<td class="right">'; // Do not force class=right, or it align also the content of the select box
        print $formaccounting->select_account(getDolGlobalString($key), $key, 1, '', 1, 1, 'minwidth100 maxwidth300 maxwidthonsmartphone', 'accounts');
        print '</td>';
        print '</tr>';
    }
}


// Customer deposit account
print '<tr class="oddeven value">';
// Param
print '<td>';
print img_picto('', 'bill', 'class="pictofixedwidth"') . $langs->trans('ACCOUNTING_ACCOUNT_CUSTOMER_DEPOSIT');
print '</td>';
// Value
print '<td class="right">'; // Do not force class=right, or it align also the content of the select box
print $formaccounting->select_account(getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER_DEPOSIT'), 'ACCOUNTING_ACCOUNT_CUSTOMER_DEPOSIT', 1, '', 1, 1, 'minwidth100 maxwidth300 maxwidthonsmartphone', 'accounts');
print '</td>';
print '</tr>';

if (isModEnabled('societe') && getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER_DEPOSIT') && getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER_DEPOSIT') != '-1') {
    print '<tr class="oddeven">';
    print '<td>' . img_picto('', 'bill', 'class="pictofixedwidth"') . $langs->trans("UseAuxiliaryAccountOnCustomerDeposit") . '</td>';
    if (getDolGlobalInt('ACCOUNTING_ACCOUNT_CUSTOMER_USE_AUXILIARY_ON_DEPOSIT')) {
        print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setACCOUNTING_ACCOUNT_CUSTOMER_USE_AUXILIARY_ON_DEPOSIT&value=0">';
        print img_picto($langs->trans("Activated"), 'switch_on', '', false, 0, 0, '', 'warning');
        print '</a></td>';
    } else {
        print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setACCOUNTING_ACCOUNT_CUSTOMER_USE_AUXILIARY_ON_DEPOSIT&value=1">';
        print img_picto($langs->trans("Disabled"), 'switch_off');
        print '</a></td>';
    }
    print '</tr>';
}

// Supplier deposit account
print '<tr class="oddeven value">';
// Param
print '<td>';
print img_picto('', 'supplier_invoice', 'class="pictofixedwidth"') . $langs->trans('ACCOUNTING_ACCOUNT_SUPPLIER_DEPOSIT');
print '</td>';
// Value
print '<td class="right">'; // Do not force class=right, or it align also the content of the select box
print $formaccounting->select_account(getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER_DEPOSIT'), 'ACCOUNTING_ACCOUNT_SUPPLIER_DEPOSIT', 1, '', 1, 1, 'minwidth100 maxwidth300 maxwidthonsmartphone', 'accounts');
print '</td>';
print '</tr>';

if (isModEnabled('societe') && getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER_DEPOSIT') && getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER_DEPOSIT') != '-1') {
    print '<tr class="oddeven">';
    print '<td>' . img_picto('', 'supplier_invoice', 'class="pictofixedwidth"') . $langs->trans("UseAuxiliaryAccountOnSupplierDeposit") . '</td>';
    if (getDolGlobalInt('ACCOUNTING_ACCOUNT_SUPPLIER_USE_AUXILIARY_ON_DEPOSIT')) {
        print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setACCOUNTING_ACCOUNT_SUPPLIER_USE_AUXILIARY_ON_DEPOSIT&value=0">';
        print img_picto($langs->trans("Activated"), 'switch_on', '', false, 0, 0, '', 'warning');
        print '</a></td>';
    } else {
        print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setACCOUNTING_ACCOUNT_SUPPLIER_USE_AUXILIARY_ON_DEPOSIT&value=1">';
        print img_picto($langs->trans("Disabled"), 'switch_off');
        print '</a></td>';
    }
    print '</tr>';
}

print "</table>\n";
print "</div>\n";

print '<div class="center"><input type="submit" class="button button-edit" name="button" value="' . $langs->trans('Save') . '"></div>';

print '</form>';

// End of page
llxFooter();
