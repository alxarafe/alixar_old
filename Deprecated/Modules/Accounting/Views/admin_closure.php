<?php

use DoliCore\Form\Form;
use DoliCore\Form\FormAccounting;

$form = new Form($db);
$formaccounting = new FormAccounting($db);

$title = $langs->trans('Closure');

$help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';

llxHeader('', $title, $help_url);

$linkback = '';
print load_fiche_titre($langs->trans('MenuClosureAccounts'), $linkback, 'title_accountancy');

print '<span class="opacitymedium">' . $langs->trans("DefaultClosureDesc") . '</span><br>';
print '<br>';

print '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update">';

// Define main accounts for closure
print '<table class="noborder centpercent">';

foreach ($list_account_main as $key) {
    print '<tr class="oddeven value">';
    // Param
    $label = $langs->trans($key);
    $keydesc = $key . '_Desc';

    $htmltext = $langs->trans($keydesc);
    print '<td class="fieldrequired" width="50%">';
    print $form->textwithpicto($label, $htmltext);
    print '</td>';
    // Value
    print '<td>'; // Do not force class=right, or it align also the content of the select box
    print $formaccounting->select_account(getDolGlobalString($key), $key, 1, '', 1, 1);
    print '</td>';
    print '</tr>';
}

// Journal
print '<tr class="oddeven">';
print '<td class="fieldrequired">' . $langs->trans("ACCOUNTING_CLOSURE_DEFAULT_JOURNAL") . '</td>';
print '<td>';
$defaultjournal = getDolGlobalString('ACCOUNTING_CLOSURE_DEFAULT_JOURNAL');
print $formaccounting->select_journal($defaultjournal, "ACCOUNTING_CLOSURE_DEFAULT_JOURNAL", 9, 1, 0, 0);
print '</td></tr>';

// Accounting groups used for the balance sheet account
print '<tr class="oddeven">';
print '<td class="fieldrequired">' . $langs->trans("ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_BALANCE_SHEET_ACCOUNT") . '</td>';
print '<td>';
print '<input type="text" size="100" id="ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_BALANCE_SHEET_ACCOUNT" name="ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_BALANCE_SHEET_ACCOUNT" value="' . dol_escape_htmltag(getDolGlobalString('ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_BALANCE_SHEET_ACCOUNT')) . '">';
print '</td></tr>';

// Accounting groups used for the income statement
print '<tr class="oddeven">';
print '<td class="fieldrequired">' . $langs->trans("ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_INCOME_STATEMENT") . '</td>';
print '<td>';
print '<input type="text" size="100" id="ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_INCOME_STATEMENT" name="ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_INCOME_STATEMENT" value="' . dol_escape_htmltag(getDolGlobalString('ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_INCOME_STATEMENT')) . '">';
print '</td></tr>';

print "</table>\n";

print '<div class="center"><input type="submit" class="button button-edit" name="button" value="' . $langs->trans('Modify') . '"></div>';

print '</form>';

// End of page
llxFooter();
