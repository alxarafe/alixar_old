<?php

use DoliCore\Form\Form;

$form = new Form($db);

$title = $langs->trans('ConfigAccountingExpert');
$help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';
llxHeader('', $title, $help_url);


$linkback = '';
//$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($title, $linkback, 'accountancy');

print '<br>';

// Show message if accountancy hidden options are activated to help to resolve some problems
if (!$user->admin) {
    if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
        print '<div class="info">' . $langs->trans("ConstantIsOn", "FACTURE_DEPOSITS_ARE_JUST_PAYMENTS") . '</div>';
    }
    if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {
        print '<div class="info">' . $langs->trans("ConstantIsOn", "FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS") . '</div>';
    }
    if (getDolGlobalString('ACCOUNTANCY_USE_PRODUCT_ACCOUNT_ON_THIRDPARTY')) {
        print '<div class="info">' . $langs->trans("ConstantIsOn", "ACCOUNTANCY_USE_PRODUCT_ACCOUNT_ON_THIRDPARTY") . '</div>';
    }
    if (getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
        print '<div class="info">' . $langs->trans("ConstantIsOn", "MAIN_COMPANY_PERENTITY_SHARED") . '</div>';
    }
    if (getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED')) {
        print '<div class="info">' . $langs->trans("ConstantIsOn", "MAIN_PRODUCT_PERENTITY_SHARED") . '</div>';
    }
}

print '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update">';

// Params
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="2">' . $langs->trans('Options') . '</td>';
print "</tr>\n";

// TO DO Mutualize code for yes/no constants

/* Set this option as a hidden option but keep it for some needs.
print '<tr>';
print '<td>'.$langs->trans("ACCOUNTING_ENABLE_EXPORT_DRAFT_JOURNAL").'</td>';
if (getDolGlobalString('ACCOUNTING_ENABLE_EXPORT_DRAFT_JOURNAL')) {
    print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&enabledraftexport&value=0">';
    print img_picto($langs->trans("Activated"), 'switch_on');
    print '</a></td>';
} else {
    print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&enabledraftexport&value=1">';
    print img_picto($langs->trans("Disabled"), 'switch_off');
    print '</a></td>';
}
print '</tr>';
*/

print '<tr class="oddeven">';
print '<td>' . $langs->trans("BANK_DISABLE_DIRECT_INPUT") . '</td>';
if (getDolGlobalString('BANK_DISABLE_DIRECT_INPUT')) {
    print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setBANK_DISABLE_DIRECT_INPUT&value=0">';
    print img_picto($langs->trans("Activated"), 'switch_on');
    print '</a></td>';
} else {
    print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setBANK_DISABLE_DIRECT_INPUT&value=1">';
    print img_picto($langs->trans("Disabled"), 'switch_off');
    print '</a></td>';
}
print '</tr>';

print '<tr class="oddeven">';
print '<td>' . $langs->trans("ACCOUNTANCY_COMBO_FOR_AUX");
print ' - <span class="opacitymedium">' . $langs->trans("NotRecommended") . '</span>';
print '</td>';

if (getDolGlobalString('ACCOUNTANCY_COMBO_FOR_AUX')) {
    print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setACCOUNTANCY_COMBO_FOR_AUX&value=0">';
    print img_picto($langs->trans("Activated"), 'switch_on');
    print '</a></td>';
} else {
    print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setACCOUNTANCY_COMBO_FOR_AUX&value=1">';
    print img_picto($langs->trans("Disabled"), 'switch_off');
    print '</a></td>';
}
print '</tr>';

print '<tr class="oddeven">';
print '<td>' . $langs->trans("ACCOUNTING_MANAGE_ZERO") . '</td>';
if (getDolGlobalInt('ACCOUNTING_MANAGE_ZERO')) {
    print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setACCOUNTING_MANAGE_ZERO&value=0">';
    print img_picto($langs->trans("Activated"), 'switch_on');
    print '</a></td>';
} else {
    print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setACCOUNTING_MANAGE_ZERO&value=1">';
    print img_picto($langs->trans("Disabled"), 'switch_off');
    print '</a></td>';
}
print '</tr>';

// Param a user $user->hasRights('accounting', 'chartofaccount') can access
foreach ($list as $key) {
    print '<tr class="oddeven value">';

    if (getDolGlobalInt('ACCOUNTING_MANAGE_ZERO') && ($key == 'ACCOUNTING_LENGTH_GACCOUNT' || $key == 'ACCOUNTING_LENGTH_AACCOUNT')) {
        continue;
    }

    // Param
    $label = $langs->trans($key);
    print '<td>' . $label . '</td>';
    // Value
    print '<td class="right">';
    print '<input type="text" class="maxwidth50 right" id="' . $key . '" name="' . $key . '" value="' . getDolGlobalString($key) . '">';

    print '</td>';
    print '</tr>';
}
print '</table>';
print '</div>';

print '<br>';

// Binding params
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="2">' . $langs->trans('BindingOptions') . '</td>';
print "</tr>\n";

// Param a user $user->hasRights('accounting', 'chartofaccount') can access
foreach ($list_binding as $key) {
    print '<tr class="oddeven value">';

    // Param
    $label = $langs->trans($key);
    print '<td>' . $label . '</td>';
    // Value
    print '<td class="right minwidth75imp parentonrightofpage">';
    if ($key == 'ACCOUNTING_DATE_START_BINDING') {
        print $form->selectDate((getDolGlobalInt($key) ? (int) getDolGlobalInt($key) : -1), $key, 0, 0, 1);
    } elseif ($key == 'ACCOUNTING_DEFAULT_PERIOD_ON_TRANSFER') {
        $array = [0 => $langs->trans("PreviousMonth"), 1 => $langs->trans("CurrentMonth"), 2 => $langs->trans("Fiscalyear")];
        print $form->selectarray($key, $array, getDolGlobalInt('ACCOUNTING_DEFAULT_PERIOD_ON_TRANSFER', 0), 0, 0, 0, '', 0, 0, 0, '', 'onrightofpage width200');
    } else {
        print '<input type="text" class="maxwidth100" id="' . $key . '" name="' . $key . '" value="' . getDolGlobalString($key) . '">';
    }

    print '</td>';
    print '</tr>';
}

print '<tr class="oddeven">';
print '<td>' . $langs->trans("ACCOUNTING_DISABLE_BINDING_ON_SALES") . '</td>';
if (getDolGlobalString('ACCOUNTING_DISABLE_BINDING_ON_SALES')) {
    print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setdisablebindingonsales&value=0">';
    print img_picto($langs->trans("Activated"), 'switch_on', '', false, 0, 0, '', 'warning');
    print '</a></td>';
} else {
    print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setdisablebindingonsales&value=1">';
    print img_picto($langs->trans("Disabled"), 'switch_off');
    print '</a></td>';
}
print '</tr>';

print '<tr class="oddeven">';
print '<td>' . $langs->trans("ACCOUNTING_DISABLE_BINDING_ON_PURCHASES") . '</td>';
if (getDolGlobalString('ACCOUNTING_DISABLE_BINDING_ON_PURCHASES')) {
    print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setdisablebindingonpurchases&value=0">';
    print img_picto($langs->trans("Activated"), 'switch_on', '', false, 0, 0, '', 'warning');
    print '</a></td>';
} else {
    print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setdisablebindingonpurchases&value=1">';
    print img_picto($langs->trans("Disabled"), 'switch_off');
    print '</a></td>';
}
print '</tr>';

print '<tr class="oddeven">';
print '<td>' . $langs->trans("ACCOUNTING_DISABLE_BINDING_ON_EXPENSEREPORTS") . '</td>';
if (getDolGlobalString('ACCOUNTING_DISABLE_BINDING_ON_EXPENSEREPORTS')) {
    print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setdisablebindingonexpensereports&value=0">';
    print img_picto($langs->trans("Activated"), 'switch_on', '', false, 0, 0, '', 'warning');
    print '</a></td>';
} else {
    print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setdisablebindingonexpensereports&value=1">';
    print img_picto($langs->trans("Disabled"), 'switch_off');
    print '</a></td>';
}
print '</tr>';

print '</table>';
print '</div>';


// Show advanced options
print '<br>';


// Advanced params
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="2">' . $langs->trans('OptionsAdvanced') . '</td>';
print "</tr>\n";

print '<tr class="oddeven">';
print '<td>';
print $form->textwithpicto($langs->trans("ACCOUNTING_ENABLE_LETTERING"), $langs->trans("ACCOUNTING_ENABLE_LETTERING_DESC", $langs->transnoentitiesnoconv("NumMvts")) . '<br>' . $langs->trans("EnablingThisFeatureIsNotNecessary")) . '</td>';
if (getDolGlobalInt('ACCOUNTING_ENABLE_LETTERING')) {
    print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setenablelettering&value=0">';
    print img_picto($langs->trans("Activated"), 'switch_on');
    print '</a></td>';
} else {
    print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setenablelettering&value=1">';
    print img_picto($langs->trans("Disabled"), 'switch_off');
    print '</a></td>';
}
print '</tr>';

if (getDolGlobalInt('ACCOUNTING_ENABLE_LETTERING')) {
    // Number of letters for lettering (3 by default (AAA), min 2 (AA))
    print '<tr class="oddeven">';
    print '<td>';
    print $form->textwithpicto($langs->trans("ACCOUNTING_LETTERING_NBLETTERS"), $langs->trans("ACCOUNTING_LETTERING_NBLETTERS_DESC")) . '</td>';
    print '<td class="right">';

    if (empty($letter)) {
        if (getDolGlobalInt('ACCOUNTING_LETTERING_NBLETTERS')) {
            $nbletter = getDolGlobalInt('ACCOUNTING_LETTERING_NBLETTERS');
        } else {
            $nbletter = 3;
        }
    }

    print '<input class="flat right" name="ACCOUNTING_LETTERING_NBLETTERS" id="ACCOUNTING_LETTERING_NBLETTERS" value="' . $nbletter . '" type="number" step="1" min="2" max="3" >' . "\n";
    print '</tr>';

    // Auto Lettering when transfer in accountancy is realized
    print '<tr class="oddeven">';
    print '<td>';
    print $form->textwithpicto($langs->trans("ACCOUNTING_ENABLE_AUTOLETTERING"), $langs->trans("ACCOUNTING_ENABLE_AUTOLETTERING_DESC")) . '</td>';
    if (getDolGlobalInt('ACCOUNTING_ENABLE_AUTOLETTERING')) {
        print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setenableautolettering&value=0">';
        print img_picto($langs->trans("Activated"), 'switch_on');
        print '</a></td>';
    } else {
        print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setenableautolettering&value=1">';
        print img_picto($langs->trans("Disabled"), 'switch_off');
        print '</a></td>';
    }
    print '</tr>';
}

print '<tr class="oddeven">';
print '<td>';
print $form->textwithpicto($langs->trans("ACCOUNTING_FORCE_ENABLE_VAT_REVERSE_CHARGE"), $langs->trans("ACCOUNTING_FORCE_ENABLE_VAT_REVERSE_CHARGE_DESC", $langs->transnoentities("MenuDefaultAccounts"))) . '</td>';
if (getDolGlobalString('ACCOUNTING_FORCE_ENABLE_VAT_REVERSE_CHARGE')) {
    print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setenablevatreversecharge&value=0">';
    print img_picto($langs->trans("Activated"), 'switch_on');
    print '</a></td>';
} else {
    print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setenablevatreversecharge&value=1">';
    print img_picto($langs->trans("Disabled"), 'switch_off');
    print '</a></td>';
}
print '</tr>';

print '</table>';
print '</div>';


print '<div class="center"><input type="submit" class="button button-edit" name="button" value="' . $langs->trans('Modify') . '"></div>';

print '</form>';

// End of page
llxFooter();
