<?php

use DoliCore\Form\Form;
use DoliCore\Form\FormAccounting;

$form = new Form($db);
$formaccounting = new FormAccounting($db);

$title = $langs->trans('Closure');

$help_url = 'EN:Module_Double_Entry_Accounting|FR:Module_Comptabilit&eacute;_en_Partie_Double#Cl.C3.B4ture_annuelle';

llxHeader('', $title, $help_url);

$formconfirm = '';

if (isset($current_fiscal_period)) {
    if ($action == 'step_1') {
        $form_question = [];

        $form_question['date_start'] = [
            'name' => 'date_start',
            'type' => 'date',
            'label' => $langs->trans('DateStart'),
            'value' => $current_fiscal_period['date_start'],
        ];
        $form_question['date_end'] = [
            'name' => 'date_end',
            'type' => 'date',
            'label' => $langs->trans('DateEnd'),
            'value' => $current_fiscal_period['date_end'],
        ];

        $formconfirm = $form->formconfirm(
            $_SERVER['PHP_SELF'] . '?fiscal_period_id=' . $current_fiscal_period['id'],
            $langs->trans('ValidateMovements'),
            $langs->trans('DescValidateMovements', $langs->transnoentitiesnoconv("RegistrationInAccounting")),
            'confirm_step_1',
            $form_question,
            '',
            1,
            300
        );
    } elseif ($action == 'step_2') {
        $form_question = [];

        $fiscal_period_arr = [];
        foreach ($active_fiscal_periods as $info) {
            $fiscal_period_arr[$info['id']] = $info['label'];
        }
        $form_question['new_fiscal_period_id'] = [
            'name' => 'new_fiscal_period_id',
            'type' => 'select',
            'label' => $langs->trans('AccountancyClosureStep3NewFiscalPeriod'),
            'values' => $fiscal_period_arr,
            'default' => isset($next_active_fiscal_period) ? $next_active_fiscal_period['id'] : '',
        ];
        $form_question['generate_bookkeeping_records'] = [
            'name' => 'generate_bookkeeping_records',
            'type' => 'checkbox',
            'label' => $langs->trans('AccountancyClosureGenerateClosureBookkeepingRecords'),
            'value' => 1,
        ];
        $form_question['separate_auxiliary_account'] = [
            'name' => 'separate_auxiliary_account',
            'type' => 'checkbox',
            'label' => $langs->trans('AccountancyClosureSeparateAuxiliaryAccounts'),
            'value' => 0,
        ];

        $formconfirm = $form->formconfirm(
            $_SERVER['PHP_SELF'] . '?fiscal_period_id=' . $current_fiscal_period['id'],
            $langs->trans('AccountancyClosureClose'),
            $langs->trans('AccountancyClosureConfirmClose'),
            'confirm_step_2',
            $form_question,
            '',
            1,
            300
        );
    } elseif ($action == 'step_3') {
        $form_question = [];

        $form_question['inventory_journal_id'] = [
            'name' => 'inventory_journal_id',
            'type' => 'other',
            'label' => $langs->trans('InventoryJournal'),
            'value' => $formaccounting->select_journal(0, "inventory_journal_id", 8, 1, 0, 0),
        ];
        $fiscal_period_arr = [];
        foreach ($active_fiscal_periods as $info) {
            $fiscal_period_arr[$info['id']] = $info['label'];
        }
        $form_question['new_fiscal_period_id'] = [
            'name' => 'new_fiscal_period_id',
            'type' => 'select',
            'label' => $langs->trans('AccountancyClosureStep3NewFiscalPeriod'),
            'values' => $fiscal_period_arr,
            'default' => isset($next_active_fiscal_period) ? $next_active_fiscal_period['id'] : '',
        ];
        $form_question['date_start'] = [
            'name' => 'date_start',
            'type' => 'date',
            'label' => $langs->trans('DateStart'),
            'value' => dol_time_plus_duree($current_fiscal_period['date_end'], -1, 'm'),
        ];
        $form_question['date_end'] = [
            'name' => 'date_end',
            'type' => 'date',
            'label' => $langs->trans('DateEnd'),
            'value' => $current_fiscal_period['date_end'],
        ];

        $formconfirm = $form->formconfirm(
            $_SERVER['PHP_SELF'] . '?fiscal_period_id=' . $current_fiscal_period['id'],
            $langs->trans('AccountancyClosureAccountingReversal'),
            $langs->trans('AccountancyClosureConfirmAccountingReversal'),
            'confirm_step_3',
            $form_question,
            '',
            1,
            300
        );
    }
}

// Call Hook formConfirm
$parameters = ['formConfirm' => $formconfirm, 'fiscal_periods' => $fiscal_periods, 'last_fiscal_period' => $last_fiscal_period, 'current_fiscal_period' => $current_fiscal_period, 'next_fiscal_period' => $next_fiscal_period];
$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if (empty($reshook)) {
    $formconfirm .= $hookmanager->resPrint;
} elseif ($reshook > 0) {
    $formconfirm = $hookmanager->resPrint;
}

// Print form confirm
print $formconfirm;

$fiscal_period_nav_text = $langs->trans("FiscalPeriod");

$fiscal_period_nav_text .= '&nbsp;<a href="' . (isset($last_fiscal_period) ? $_SERVER['PHP_SELF'] . '?fiscal_period_id=' . $last_fiscal_period['id'] : '#" class="disabled') . '">' . img_previous() . '</a>';
$fiscal_period_nav_text .= '&nbsp;<a href="' . (isset($next_fiscal_period) ? $_SERVER['PHP_SELF'] . '?fiscal_period_id=' . $next_fiscal_period['id'] : '#" class="disabled') . '">' . img_next() . '</a>';
if (!empty($current_fiscal_period)) {
    $fiscal_period_nav_text .= $current_fiscal_period['label'] . ' &nbsp;(' . (isset($current_fiscal_period) ? dol_print_date($current_fiscal_period['date_start'], 'day') . '&nbsp;-&nbsp;' . dol_print_date($current_fiscal_period['date_end'], 'day') . ')' : '');
}

print load_fiche_titre($langs->trans("Closure") . " - " . $fiscal_period_nav_text, '', 'title_accountancy');

if (empty($current_fiscal_period)) {
    print $langs->trans('ErrorNoFiscalPeriodActiveFound');
}

if (isset($current_fiscal_period)) {
    // Step 1
    $head = [];
    $head[0][0] = DOL_URL_ROOT . '/accountancy/closure/index.php?fiscal_period_id=' . $current_fiscal_period['id'];
    $head[0][1] = $langs->trans("AccountancyClosureStep1");
    $head[0][2] = 'step1';
    print dol_get_fiche_head($head, 'step1', '', -1, 'title_accountancy');

    print '<span class="opacitymedium">' . $langs->trans("AccountancyClosureStep1Desc") . '</span><br>';

    $count_by_month = $object->getCountByMonthForFiscalPeriod($current_fiscal_period['date_start'], $current_fiscal_period['date_end']);
    if (!is_array($count_by_month)) {
        setEventMessages($object->error, $object->errors, 'errors');
    }

    if (empty($count_by_month['total'])) {
        $buttonvalidate = '<a class="butActionRefused classfortooltip" href="#">' . $langs->trans("ValidateMovements") . '</a>';
    } else {
        $buttonvalidate = '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=step_1&fiscal_period_id=' . $current_fiscal_period['id'] . '">' . $langs->trans("ValidateMovements") . '</a>';
    }
    print_barre_liste($langs->trans("OverviewOfMovementsNotValidated"), '', '', '', '', '', '', -1, '', '', 0, $buttonvalidate, '', 0, 1, 0);

    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';

    print '<tr class="liste_titre">';
    $nb_years = is_array($count_by_month['list']) ? count($count_by_month['list']) : 0;
    if ($nb_years > 1) {
        print '<td class="right">' . $langs->trans("Year") . '</td>';
    }
    for ($i = 1; $i <= 12; $i++) {
        print '<td class="right">' . $langs->trans('MonthShort' . str_pad($i, 2, '0', STR_PAD_LEFT)) . '</td>';
    }
    print '<td class="right"><b>' . $langs->trans("Total") . '</b></td>';
    print '</tr>';

    if (is_array($count_by_month['list'])) {
        foreach ($count_by_month['list'] as $info) {
            print '<tr class="oddeven">';
            if ($nb_years > 1) {
                print '<td class="right">' . $info['year'] . '</td>';
            }
            for ($i = 1; $i <= 12; $i++) {
                print '<td class="right">' . ((int) $info['count'][$i]) . '</td>';
            }
            print '<td class="right"><b>' . $info['total'] . '</b></td></tr>';
        }
    }

    print "</table>\n";
    print '</div>';

    // Step 2
    $head = [];
    $head[0][0] = DOL_URL_ROOT . '/accountancy/closure/index.php?fiscal_period_id=' . $current_fiscal_period['id'];
    $head[0][1] = $langs->trans("AccountancyClosureStep2");
    $head[0][2] = 'step2';
    print dol_get_fiche_head($head, 'step2', '', -1, 'title_accountancy');

    // print '<span class="opacitymedium">' . $langs->trans("AccountancyClosureStep2Desc") . '</span><br>';

    if (empty($count_by_month['total']) && empty($current_fiscal_period['status'])) {
        $button = '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=step_2&fiscal_period_id=' . $current_fiscal_period['id'] . '">' . $langs->trans("AccountancyClosureClose") . '</a>';
    } else {
        $button = '<a class="butActionRefused classfortooltip" href="#">' . $langs->trans("AccountancyClosureClose") . '</a>';
    }
    print_barre_liste('', '', '', '', '', '', '', -1, '', '', 0, $button, '', 0, 1, 0);

    // Step 3
    $head = [];
    $head[0][0] = DOL_URL_ROOT . '/accountancy/closure/index.php?fiscal_period_id=' . $current_fiscal_period['id'];
    $head[0][1] = $langs->trans("AccountancyClosureStep3");
    $head[0][2] = 'step3';
    print dol_get_fiche_head($head, 'step3', '', -1, 'title_accountancy');

    // print '<span class="opacitymedium">' . $langs->trans("AccountancyClosureStep3Desc") . '</span><br>';

    if (empty($current_fiscal_period['status'])) {
        $button = '<a class="butActionRefused classfortooltip" href="#">' . $langs->trans("AccountancyClosureAccountingReversal") . '</a>';
    } else {
        $button = '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=step_3&fiscal_period_id=' . $current_fiscal_period['id'] . '">' . $langs->trans("AccountancyClosureAccountingReversal") . '</a>';
    }
    print_barre_liste('', '', '', '', '', '', '', -1, '', '', 0, $button, '', 0, 1, 0);
}

// End of page
llxFooter();
