<?php

$formaccounting = new FormAccounting($db);
$formfile = new FormFile($db);
$formother = new FormOther($db);
$form = new Form($db);

$title_page = $langs->trans("Operations") . ' - ' . $langs->trans("VueByAccountAccounting") . ' (';
if ($type == 'sub') {
    $title_page .= $langs->trans("BookkeepingSubAccount");
} else {
    $title_page .= $langs->trans("Bookkeeping");
}
$title_page .= ')';
$help_url = 'EN:Module_Double_Entry_Accounting|FR:Module_Comptabilit&eacute;_en_Partie_Double';
llxHeader('', $title_page, $help_url);

// List
$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
    // TODO Perf Replace this by a count
    if ($type == 'sub') {
        $nbtotalofrecords = $object->fetchAllByAccount($sortorder, $sortfield, 0, 0, $filter, 'AND', 1, 1);
    } else {
        $nbtotalofrecords = $object->fetchAllByAccount($sortorder, $sortfield, 0, 0, $filter, 'AND', 0, 1);
    }

    if ($nbtotalofrecords < 0) {
        setEventMessages($object->error, $object->errors, 'errors');
        $error++;
    }
}

if (!$error) {
    if ($type == 'sub') {
        $result = $object->fetchAllByAccount($sortorder, $sortfield, $limit, $offset, $filter, 'AND', 1);
    } else {
        $result = $object->fetchAllByAccount($sortorder, $sortfield, $limit, $offset, $filter, 'AND', 0);
    }

    if ($result < 0) {
        setEventMessages($object->error, $object->errors, 'errors');
    }
}

$arrayofselected = is_array($toselect) ? $toselect : [];

$num = count($object->lines);


///if ($action == 'delbookkeepingyear') {
//  $form_question = array();
//  $delyear = GETPOST('delyear', 'int');
//  $deljournal = GETPOST('deljournal', 'alpha');
//
//  if (empty($delyear)) {
//      $delyear = dol_print_date(dol_now(), '%Y');
//  }
//  $month_array = array();
//  for ($i = 1; $i <= 12; $i++) {
//      $month_array[$i] = $langs->trans("Month".sprintf("%02d", $i));
//  }
//  $year_array = $formaccounting->selectyear_accountancy_bookkepping($delyear, 'delyear', 0, 'array');
//  $journal_array = $formaccounting->select_journal($deljournal, 'deljournal', '', 1, 1, 1, '', 0, 1);
//
//  $form_question['delmonth'] = array(
//      'name' => 'delmonth',
//      'type' => 'select',
//      'label' => $langs->trans('DelMonth'),
//      'values' => $month_array,
//      'default' => ''
//  );
//  $form_question['delyear'] = array(
//      'name' => 'delyear',
//      'type' => 'select',
//      'label' => $langs->trans('DelYear'),
//      'values' => $year_array,
//      'default' => $delyear
//  );
//  $form_question['deljournal'] = array(
//      'name' => 'deljournal',
//      'type' => 'other', // We don't use select here, the journal_array is already a select html component
//      'label' => $langs->trans('DelJournal'),
//      'value' => $journal_array,
//      'default' => $deljournal
//  );
//
//  $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'].'?'.$param, $langs->trans('DeleteMvt'), $langs->trans('ConfirmDeleteMvt', $langs->transnoentitiesnoconv("RegistrationInAccounting")), 'delbookkeepingyearconfirm', $form_question, '', 1, 300);
//}

// Print form confirm
$formconfirm = '';
print $formconfirm;

// List of mass actions available
$arrayofmassactions = [];
if (getDolGlobalInt('ACCOUNTING_ENABLE_LETTERING') && $user->hasRight('accounting', 'mouvements', 'creer')) {
    $arrayofmassactions['letteringauto'] = img_picto('', 'check', 'class="pictofixedwidth"') . $langs->trans('LetteringAuto');
    $arrayofmassactions['preunletteringauto'] = img_picto('', 'uncheck', 'class="pictofixedwidth"') . $langs->trans('UnletteringAuto');
    $arrayofmassactions['letteringmanual'] = img_picto('', 'check', 'class="pictofixedwidth"') . $langs->trans('LetteringManual');
    $arrayofmassactions['preunletteringmanual'] = img_picto('', 'uncheck', 'class="pictofixedwidth"') . $langs->trans('UnletteringManual');
}
if ($user->hasRight('accounting', 'mouvements', 'supprimer')) {
    $arrayofmassactions['predeletebookkeepingwriting'] = img_picto('', 'delete', 'class="pictofixedwidth"') . $langs->trans("Delete");
}
if (GETPOSTINT('nomassaction') || in_array($massaction, ['preunletteringauto', 'preunletteringmanual', 'predeletebookkeepingwriting'])) {
    $arrayofmassactions = [];
}
$massactionbutton = $form->selectMassAction($massaction, $arrayofmassactions);

print '<form method="POST" id="searchFormList" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="list">';
if ($optioncss != '') {
    print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
}
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="type" value="' . $type . '">';
print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
print '<input type="hidden" name="contextpage" value="' . $contextpage . '">';

$parameters = ['param' => $param];
$reshook = $hookmanager->executeHooks('addMoreActionsButtonsList', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if ($reshook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

$newcardbutton = empty($hookmanager->resPrint) ? '' : $hookmanager->resPrint;

if (empty($reshook)) {
    $newcardbutton = dolGetButtonTitle($langs->trans('ViewFlatList'), '', 'fa fa-list paddingleft imgforviewmode', DOL_URL_ROOT . '/accountancy/bookkeeping/list.php?' . $param);
    if ($type == 'sub') {
        $newcardbutton .= dolGetButtonTitle($langs->trans('GroupByAccountAccounting'), '', 'fa fa-stream paddingleft imgforviewmode', DOL_URL_ROOT . '/accountancy/bookkeeping/listbyaccount.php?' . $url_param, '', 1, ['morecss' => 'marginleftonly']);
        $newcardbutton .= dolGetButtonTitle($langs->trans('GroupBySubAccountAccounting'), '', 'fa fa-align-left vmirror paddingleft imgforviewmode', DOL_URL_ROOT . '/accountancy/bookkeeping/listbyaccount.php?type=sub&' . $url_param, '', 1, ['morecss' => 'marginleftonly btnTitleSelected']);
    } else {
        $newcardbutton .= dolGetButtonTitle($langs->trans('GroupByAccountAccounting'), '', 'fa fa-stream paddingleft imgforviewmode', DOL_URL_ROOT . '/accountancy/bookkeeping/listbyaccount.php?' . $url_param, '', 1, ['morecss' => 'marginleftonly btnTitleSelected']);
        $newcardbutton .= dolGetButtonTitle($langs->trans('GroupBySubAccountAccounting'), '', 'fa fa-align-left vmirror paddingleft imgforviewmode', DOL_URL_ROOT . '/accountancy/bookkeeping/listbyaccount.php?type=sub&' . $url_param, '', 1, ['morecss' => 'marginleftonly']);
    }
    $newcardbutton .= dolGetButtonTitleSeparator();
    $newcardbutton .= dolGetButtonTitle($langs->trans('NewAccountingMvt'), '', 'fa fa-plus-circle paddingleft', DOL_URL_ROOT . '/accountancy/bookkeeping/card.php?action=create');
}

if (!empty($contextpage) && $contextpage != $_SERVER['PHP_SELF']) {
    $param .= '&contextpage=' . urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
    $param .= '&limit=' . ((int) $limit);
}

print_barre_liste($title_page, $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, $massactionbutton, $result, $nbtotalofrecords, 'title_accountancy', 0, $newcardbutton, '', $limit, 0, 0, 1);

if ($massaction == 'preunletteringauto') {
    print $form->formconfirm($_SERVER['PHP_SELF'], $langs->trans("ConfirmMassUnletteringAuto"), $langs->trans("ConfirmMassUnletteringQuestion", count($toselect)), "unletteringauto", null, '', 0, 200, 500, 1);
} elseif ($massaction == 'preunletteringmanual') {
    print $form->formconfirm($_SERVER['PHP_SELF'], $langs->trans("ConfirmMassUnletteringManual"), $langs->trans("ConfirmMassUnletteringQuestion", count($toselect)), "unletteringmanual", null, '', 0, 200, 500, 1);
} elseif ($massaction == 'predeletebookkeepingwriting') {
    print $form->formconfirm($_SERVER['PHP_SELF'], $langs->trans("ConfirmMassDeleteBookkeepingWriting"), $langs->trans("ConfirmMassDeleteBookkeepingWritingQuestion", count($toselect)), "deletebookkeepingwriting", null, '', 0, 200, 500, 1);
}
//DeleteMvt=Supprimer des lignes d'opérations de la comptabilité
//DelMonth=Mois à effacer
//DelYear=Année à supprimer
//DelJournal=Journal à supprimer
//ConfirmDeleteMvt=Cette action supprime les lignes des opérations pour l'année/mois et/ou pour le journal sélectionné (au moins un critère est requis). Vous devrez utiliser de nouveau la fonctionnalité '%s' pour retrouver vos écritures dans la comptabilité.
//ConfirmDeleteMvtPartial=Cette action supprime l'écriture de la comptabilité (toutes les lignes opérations liées à une même écriture seront effacées).

//$topicmail = "Information";
//$modelmail = "accountingbookkeeping";
//$objecttmp = new BookKeeping($db);
//$trackid = 'bk'.$object->id;
include DOL_DOCUMENT_ROOT . '/core/tpl/massactions_pre.tpl.php';

$varpage = empty($contextpage) ? $_SERVER['PHP_SELF'] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')); // This also change content of $arrayfields
if ($massactionbutton && $contextpage != 'poslist') {
    $selectedfields .= $form->showCheckAddButtons('checkforselect', 1);
}

// Reverse sort order
if (preg_match('/^asc/i', $sortorder)) {
    $sortorder = "asc";
} else {
    $sortorder = "desc";
}

// Warning to explain why list of record is not consistent with the other list view (missing a lot of lines)
if ($type == 'sub') {
    print info_admin($langs->trans("WarningRecordWithoutSubledgerAreExcluded"));
}

$moreforfilter = '';

// Search on accountancy custom groups or account
$moreforfilter .= '<div class="divsearchfield">';
$moreforfilter .= $langs->trans('AccountAccounting') . ': ';
$moreforfilter .= '<div class="nowrap inline-block">';
if ($type == 'sub') {
    $moreforfilter .= $formaccounting->select_auxaccount($search_accountancy_code_start, 'search_accountancy_code_start', $langs->trans('From'), 'maxwidth200');
} else {
    $moreforfilter .= $formaccounting->select_account($search_accountancy_code_start, 'search_accountancy_code_start', $langs->trans('From'), [], 1, 1, 'maxwidth200');
}
$moreforfilter .= ' ';
if ($type == 'sub') {
    $moreforfilter .= $formaccounting->select_auxaccount($search_accountancy_code_end, 'search_accountancy_code_end', $langs->trans('to'), 'maxwidth200');
} else {
    $moreforfilter .= $formaccounting->select_account($search_accountancy_code_end, 'search_accountancy_code_end', $langs->trans('to'), [], 1, 1, 'maxwidth200');
}
$stringforfirstkey = $langs->trans("KeyboardShortcut");
if ($conf->browser->name == 'chrome') {
    $stringforfirstkey .= ' ALT +';
} elseif ($conf->browser->name == 'firefox') {
    $stringforfirstkey .= ' ALT + SHIFT +';
} else {
    $stringforfirstkey .= ' CTL +';
}
$moreforfilter .= '&nbsp;&nbsp;&nbsp;<a id="previous_account" accesskey="p" title="' . $stringforfirstkey . ' p" class="classfortooltip" href="#"><i class="fa fa-chevron-left"></i></a>';
$moreforfilter .= '&nbsp;&nbsp;&nbsp;<a id="next_account" accesskey="n" title="' . $stringforfirstkey . ' n" class="classfortooltip" href="#"><i class="fa fa-chevron-right"></i></a>';
$moreforfilter .= <<<SCRIPT
<script type="text/javascript">
	jQuery(document).ready(function() {
		var searchFormList = $('#searchFormList');
		var searchAccountancyCodeStart = $('#search_accountancy_code_start');
		var searchAccountancyCodeEnd = $('#search_accountancy_code_end');
		jQuery('#previous_account').on('click', function() {
			var previousOption = searchAccountancyCodeStart.find('option:selected').prev('option');
			if (previousOption.length == 1) searchAccountancyCodeStart.val(previousOption.attr('value'));
			searchAccountancyCodeEnd.val(searchAccountancyCodeStart.val());
			searchFormList.submit();
		});
		jQuery('#next_account').on('click', function() {
			var nextOption = searchAccountancyCodeStart.find('option:selected').next('option');
			if (nextOption.length == 1) searchAccountancyCodeStart.val(nextOption.attr('value'));
			searchAccountancyCodeEnd.val(searchAccountancyCodeStart.val());
			searchFormList.submit();
		});
		jQuery('input[name="search_mvt_num"]').on("keypress", function(event) {
			console.log(event);
		});
	});
</script>
SCRIPT;
$moreforfilter .= '</div>';
$moreforfilter .= '</div>';

$moreforfilter .= '<div class="divsearchfield">';
$moreforfilter .= $langs->trans('AccountingCategory') . ': ';
$moreforfilter .= '<div class="nowrap inline-block">';
$moreforfilter .= $formaccounting->select_accounting_category($search_account_category, 'search_account_category', 1, 0, 0, 0);
$moreforfilter .= '</div>';
$moreforfilter .= '</div>';

$parameters = [];
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
if (empty($reshook)) {
    $moreforfilter .= $hookmanager->resPrint;
} else {
    $moreforfilter = $hookmanager->resPrint;
}

print '<div class="liste_titre liste_titre_bydiv centpercent">';
print $moreforfilter;
print '</div>';

print '<div class="div-table-responsive">';
print '<table class="tagtable liste centpercent">';

// Filters lines
print '<tr class="liste_titre_filter">';
// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
    print '<td class="liste_titre center">';
    $searchpicto = $form->showFilterButtons('left');
    print $searchpicto;
    print '</td>';
}
// Movement number
if (!empty($arrayfields['t.piece_num']['checked'])) {
    print '<td class="liste_titre"><input type="text" name="search_mvt_num" class="width50" value="' . dol_escape_htmltag($search_mvt_num) . '"></td>';
}
// Code journal
if (!empty($arrayfields['t.code_journal']['checked'])) {
    print '<td class="liste_titre center">';
    print $formaccounting->multi_select_journal($search_ledger_code, 'search_ledger_code', 0, 1, 1, 1, 'maxwidth75');
    print '</td>';
}
// Date document
if (!empty($arrayfields['t.doc_date']['checked'])) {
    print '<td class="liste_titre center">';
    print '<div class="nowrapfordate">';
    print $form->selectDate($search_date_start, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
    print '</div>';
    print '<div class="nowrapfordate">';
    print $form->selectDate($search_date_end, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
    print '</div>';
    print '</td>';
}
// Ref document
if (!empty($arrayfields['t.doc_ref']['checked'])) {
    print '<td class="liste_titre"><input type="text" size="7" class="flat" name="search_doc_ref" value="' . dol_escape_htmltag($search_doc_ref) . '"/></td>';
}
// Label operation
if (!empty($arrayfields['t.label_operation']['checked'])) {
    print '<td class="liste_titre"><input type="text" size="7" class="flat" name="search_label_operation" value="' . dol_escape_htmltag($search_label_operation) . '"/></td>';
}
// Lettering code
if (!empty($arrayfields['t.lettering_code']['checked'])) {
    print '<td class="liste_titre center">';
    print '<input type="text" size="3" class="flat" name="search_lettering_code" value="' . $search_lettering_code . '"/>';
    print '<br><span class="nowrap"><input type="checkbox" name="search_not_reconciled" value="notreconciled"' . ($search_not_reconciled == 'notreconciled' ? ' checked' : '') . '>' . $langs->trans("NotReconciled") . '</span>';
    print '</td>';
}
// Debit
if (!empty($arrayfields['t.debit']['checked'])) {
    print '<td class="liste_titre right"><input type="text" class="flat" name="search_debit" size="4" value="' . dol_escape_htmltag($search_debit) . '"></td>';
}
// Credit
if (!empty($arrayfields['t.credit']['checked'])) {
    print '<td class="liste_titre right"><input type="text" class="flat" name="search_credit" size="4" value="' . dol_escape_htmltag($search_credit) . '"></td>';
}
// Balance
if (!empty($arrayfields['t.balance']['checked'])) {
    print '<td></td>';
}
// Date export
if (!empty($arrayfields['t.date_export']['checked'])) {
    print '<td class="liste_titre center">';
    print '<div class="nowrapfordate">';
    print $form->selectDate($search_date_export_start, 'search_date_export_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
    print '</div>';
    print '<div class="nowrapfordate">';
    print $form->selectDate($search_date_export_end, 'search_date_export_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
    print '</div>';
    print '</td>';
}
// Date validation
if (!empty($arrayfields['t.date_validated']['checked'])) {
    print '<td class="liste_titre center">';
    print '<div class="nowrapfordate">';
    print $form->selectDate($search_date_validation_start, 'search_date_validation_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
    print '</div>';
    print '<div class="nowrapfordate">';
    print $form->selectDate($search_date_validation_end, 'search_date_validation_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
    print '</div>';
    print '</td>';
}
if (!empty($arrayfields['t.import_key']['checked'])) {
    print '<td class="liste_titre center">';
    print '<input class="flat searchstring maxwidth50" type="text" name="search_import_key" value="' . dol_escape_htmltag($search_import_key) . '">';
    print '</td>';
}

// Fields from hook
$parameters = ['arrayfields' => $arrayfields];
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
    print '<td class="liste_titre center">';
    $searchpicto = $form->showFilterButtons();
    print $searchpicto;
    print '</td>';
}
print "</tr>\n";

print '<tr class="liste_titre">';
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
    print_liste_field_titre($selectedfields, $_SERVER['PHP_SELF'], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
}
if (!empty($arrayfields['t.piece_num']['checked'])) {
    print_liste_field_titre($arrayfields['t.piece_num']['label'], $_SERVER['PHP_SELF'], "t.piece_num", "", $param, '', $sortfield, $sortorder, 'tdoverflowmax80imp ');
}
if (!empty($arrayfields['t.code_journal']['checked'])) {
    print_liste_field_titre($arrayfields['t.code_journal']['label'], $_SERVER['PHP_SELF'], "t.code_journal", "", $param, '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['t.doc_date']['checked'])) {
    print_liste_field_titre($arrayfields['t.doc_date']['label'], $_SERVER['PHP_SELF'], "t.doc_date", "", $param, '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['t.doc_ref']['checked'])) {
    print_liste_field_titre($arrayfields['t.doc_ref']['label'], $_SERVER['PHP_SELF'], "t.doc_ref", "", $param, "", $sortfield, $sortorder);
}
if (!empty($arrayfields['t.label_operation']['checked'])) {
    print_liste_field_titre($arrayfields['t.label_operation']['label'], $_SERVER['PHP_SELF'], "t.label_operation", "", $param, "", $sortfield, $sortorder);
}
if (!empty($arrayfields['t.lettering_code']['checked'])) {
    print_liste_field_titre($arrayfields['t.lettering_code']['label'], $_SERVER['PHP_SELF'], "t.lettering_code", "", $param, '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['t.debit']['checked'])) {
    print_liste_field_titre($arrayfields['t.debit']['label'], $_SERVER['PHP_SELF'], "t.debit", "", $param, '', $sortfield, $sortorder, 'right ');
}
if (!empty($arrayfields['t.credit']['checked'])) {
    print_liste_field_titre($arrayfields['t.credit']['label'], $_SERVER['PHP_SELF'], "t.credit", "", $param, '', $sortfield, $sortorder, 'right ');
}
if (!empty($arrayfields['t.balance']['checked'])) {
    print_liste_field_titre($arrayfields['t.balance']['label'], "", "", "", $param, '', $sortfield, $sortorder, 'right ');
}
if (!empty($arrayfields['t.date_export']['checked'])) {
    print_liste_field_titre($arrayfields['t.date_export']['label'], $_SERVER['PHP_SELF'], "t.date_export", "", $param, '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['t.date_validated']['checked'])) {
    print_liste_field_titre($arrayfields['t.date_validated']['label'], $_SERVER['PHP_SELF'], "t.date_validated", "", $param, '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['t.import_key']['checked'])) {
    print_liste_field_titre($arrayfields['t.import_key']['label'], $_SERVER['PHP_SELF'], "t.import_key", "", $param, '', $sortfield, $sortorder, 'center ');
}
// Hook fields
$parameters = ['arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder];
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
    print_liste_field_titre($selectedfields, $_SERVER['PHP_SELF'], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
}
print "</tr>\n";

$displayed_account_number = null; // Start with undefined to be able to distinguish with empty

// Loop on record
// --------------------------------------------------------------------
$i = 0;

$totalarray = [];
$totalarray['val'] = [];
$totalarray['nbfield'] = 0;
$total_debit = 0;
$total_credit = 0;
$sous_total_debit = 0;
$sous_total_credit = 0;
$totalarray['val']['totaldebit'] = 0;
$totalarray['val']['totalcredit'] = 0;

while ($i < min($num, $limit)) {
    $line = $object->lines[$i];

    $total_debit += $line->debit;
    $total_credit += $line->credit;

    if ($type == 'sub') {
        $accountg = length_accounta($line->subledger_account);
    } else {
        $accountg = length_accountg($line->numero_compte);
    }
    //if (empty($accountg)) $accountg = '-';

    $colspan = 0;           // colspan before field 'label of operation'
    $colspanend = 0;        // colspan after debit/credit
    if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
        $colspan++;
    }
    if (!empty($arrayfields['t.piece_num']['checked'])) {
        $colspan++;
    }
    if (!empty($arrayfields['t.code_journal']['checked'])) {
        $colspan++;
    }
    if (!empty($arrayfields['t.doc_date']['checked'])) {
        $colspan++;
    }
    if (!empty($arrayfields['t.doc_ref']['checked'])) {
        $colspan++;
    }
    if (!empty($arrayfields['t.label_operation']['checked'])) {
        $colspan++;
    }
    if (!empty($arrayfields['t.lettering_code']['checked'])) {
        $colspan++;
    }

    if (!empty($arrayfields['t.balance']['checked'])) {
        $colspanend++;
    }
    if (!empty($arrayfields['t.date_export']['checked'])) {
        $colspanend++;
    }
    if (!empty($arrayfields['t.date_validated']['checked'])) {
        $colspanend++;
    }
    if (!empty($arrayfields['t.lettering_code']['checked'])) {
        $colspanend++;
    }
    if (!empty($arrayfields['t.import_key']['checked'])) {
        $colspanend++;
    }
    if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
        $colspan++;
        $colspanend--;
    }

    // Is it a break ?
    if ($accountg != $displayed_account_number || !isset($displayed_account_number)) {
        // Show a subtotal by accounting account
        if (isset($displayed_account_number)) {
            print '<tr class="liste_total">';
            if ($type == 'sub') {
                print '<td class="right" colspan="' . $colspan . '">' . $langs->trans("TotalForAccount") . ' ' . length_accounta($displayed_account_number) . ':</td>';
            } else {
                print '<td class="right" colspan="' . $colspan . '">' . $langs->trans("TotalForAccount") . ' ' . length_accountg($displayed_account_number) . ':</td>';
            }
            print '<td class="nowrap right">' . price(price2num($sous_total_debit, 'MT')) . '</td>';
            print '<td class="nowrap right">' . price(price2num($sous_total_credit, 'MT')) . '</td>';
            if ($colspanend > 0) {
                print '<td colspan="' . $colspanend . '"></td>';
            }
            print '</tr>';
            // Show balance of last shown account
            $balance = $sous_total_debit - $sous_total_credit;
            print '<tr class="liste_total">';
            print '<td class="right" colspan="' . $colspan . '">' . $langs->trans("Balance") . ':</td>';
            if ($balance > 0) {
                print '<td class="nowraponall right">';
                print price(price2num($sous_total_debit - $sous_total_credit, 'MT'));
                print '</td>';
                print '<td></td>';
            } else {
                print '<td></td>';
                print '<td class="nowraponall right">';
                print price(price2num($sous_total_credit - $sous_total_debit, 'MT'));
                print '</td>';
            }
            if ($colspanend > 0) {
                print '<td colspan="' . $colspanend . '"></td>';
            }
            print '</tr>';
        }

        // Show the break account
        print '<tr class="trforbreak">';
        print '<td colspan="' . ($totalarray['nbfield'] ? $totalarray['nbfield'] : count($arrayfields) + 1) . '" class="tdforbreak">';
        if ($type == 'sub') {
            if ($line->subledger_account != "" && $line->subledger_account != '-1') {
                print empty($line->subledger_label) ? '<span class="error">' . $langs->trans("Unknown") . '</span>' : $line->subledger_label;
                print ' : ';
                print length_accounta($line->subledger_account);
            } else {
                // Should not happen: subledger account must be null or a non empty value
                print '<span class="error">' . $langs->trans("Unknown");
                if ($line->subledger_label) {
                    print ' (' . $line->subledger_label . ')';
                    $htmltext = 'EmptyStringForSubledgerAccountButSubledgerLabelDefined';
                } else {
                    $htmltext = 'EmptyStringForSubledgerAccountAndSubledgerLabel';
                }
                print $form->textwithpicto('', $htmltext);
                print '</span>';
            }
        } else {
            if ($line->numero_compte != "" && $line->numero_compte != '-1') {
                print length_accountg($line->numero_compte) . ' : ' . $object->get_compte_desc($line->numero_compte);
            } else {
                print '<span class="error">' . $langs->trans("Unknown") . '</span>';
            }
        }
        print '</td>';
        print '</tr>';

        $displayed_account_number = $accountg;
        //if (empty($displayed_account_number)) $displayed_account_number='-';
        $sous_total_debit = 0;
        $sous_total_credit = 0;

        $colspan = 0;
    }

    print '<tr class="oddeven">';
    // Action column
    if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
        print '<td class="nowraponall center">';
        if (($massactionbutton || $massaction) && $contextpage != 'poslist') {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
            $selected = 0;
            if (in_array($line->id, $arrayofselected)) {
                $selected = 1;
            }
            print '<input id="cb' . $line->id . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $line->id . '"' . ($selected ? ' checked="checked"' : '') . ' />';
        }
        print '</td>';
        if (!$i) {
            $totalarray['nbfield']++;
        }
    }
    // Piece number
    if (!empty($arrayfields['t.piece_num']['checked'])) {
        print '<td>';
        $object->id = $line->id;
        $object->piece_num = $line->piece_num;
        print $object->getNomUrl(1, '', 0, '', 1);
        print '</td>';
        if (!$i) {
            $totalarray['nbfield']++;
        }
    }

    // Journal code
    if (!empty($arrayfields['t.code_journal']['checked'])) {
        $accountingjournal = new AccountingJournal($db);
        $result = $accountingjournal->fetch('', $line->code_journal);
        $journaltoshow = (($result > 0) ? $accountingjournal->getNomUrl(0, 0, 0, '', 0) : $line->code_journal);
        print '<td class="center tdoverflowmax80">' . $journaltoshow . '</td>';
        if (!$i) {
            $totalarray['nbfield']++;
        }
    }

    // Document date
    if (!empty($arrayfields['t.doc_date']['checked'])) {
        print '<td class="center">' . dol_print_date($line->doc_date, 'day') . '</td>';
        if (!$i) {
            $totalarray['nbfield']++;
        }
    }

    // Document ref
    if (!empty($arrayfields['t.doc_ref']['checked'])) {
        if ($line->doc_type == 'customer_invoice') {
            $langs->loadLangs(['bills']);

            $objectstatic = new Facture($db);
            $objectstatic->fetch($line->fk_doc);
            //$modulepart = 'facture';

            $filename = dol_sanitizeFileName($line->doc_ref);
            $filedir = $conf->facture->dir_output . '/' . dol_sanitizeFileName($line->doc_ref);
            $urlsource = $_SERVER['PHP_SELF'] . '?id=' . $objectstatic->id;
            $documentlink = $formfile->getDocumentsLink($objectstatic->element, $filename, $filedir);
        } elseif ($line->doc_type == 'supplier_invoice') {
            $langs->loadLangs(['bills']);

            $objectstatic = new FactureFournisseur($db);
            $objectstatic->fetch($line->fk_doc);
            //$modulepart = 'invoice_supplier';

            $filename = dol_sanitizeFileName($line->doc_ref);
            $filedir = $conf->fournisseur->facture->dir_output . '/' . get_exdir($line->fk_doc, 2, 0, 0, $objectstatic, $modulepart) . dol_sanitizeFileName($line->doc_ref);
            $subdir = get_exdir($objectstatic->id, 2, 0, 0, $objectstatic, $modulepart) . dol_sanitizeFileName($line->doc_ref);
            $documentlink = $formfile->getDocumentsLink($objectstatic->element, $subdir, $filedir);
        } elseif ($line->doc_type == 'expense_report') {
            $langs->loadLangs(['trips']);

            $objectstatic = new ExpenseReport($db);
            $objectstatic->fetch($line->fk_doc);
            //$modulepart = 'expensereport';

            $filename = dol_sanitizeFileName($line->doc_ref);
            $filedir = $conf->expensereport->dir_output . '/' . dol_sanitizeFileName($line->doc_ref);
            $urlsource = $_SERVER['PHP_SELF'] . '?id=' . $objectstatic->id;
            $documentlink = $formfile->getDocumentsLink($objectstatic->element, $filename, $filedir);
        } elseif ($line->doc_type == 'bank') {
            require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
            $objectstatic = new AccountLine($db);
            $objectstatic->fetch($line->fk_doc);
        } else {
            // Other type
        }

        print '<td class="tdoverflowmax250">';

        // Picto + Ref
        if ($line->doc_type == 'customer_invoice' || $line->doc_type == 'supplier_invoice' || $line->doc_type == 'expense_report') {
            print $objectstatic->getNomUrl(1, '', 0, 0, '', 0, -1, 1);
            print $documentlink;
        } elseif ($line->doc_type == 'bank') {
            print $objectstatic->getNomUrl(1);
            $bank_ref = strstr($line->doc_ref, '-');
            print " " . $bank_ref;
        } else {
            print $line->doc_ref;
        }

        print "</td>\n";
        if (!$i) {
            $totalarray['nbfield']++;
        }
    }

    // Label operation
    if (!empty($arrayfields['t.label_operation']['checked'])) {
        // Affiche un lien vers la facture client/fournisseur
        $doc_ref = preg_replace('/\(.*\)/', '', $line->doc_ref);
        if (strlen(length_accounta($line->subledger_account)) == 0) {
            print '<td class="small tdoverflowmax350 classfortooltip" title="' . dol_escape_htmltag($line->label_operation) . '">' . dol_escape_htmltag($line->label_operation) . '</td>';
        } else {
            print '<td class="small tdoverflowmax350 classfortooltip" title="' . dol_escape_htmltag($line->label_operation . ($line->label_operation ? '<br>' : '') . '<span style="font-size:0.8em">(' . length_accounta($line->subledger_account) . ')') . '">' . dol_escape_htmltag($line->label_operation) . ($line->label_operation ? '<br>' : '') . '<span style="font-size:0.8em">(' . dol_escape_htmltag(length_accounta($line->subledger_account)) . ')</span></td>';
        }
        if (!$i) {
            $totalarray['nbfield']++;
        }
    }

    // Lettering code
    if (!empty($arrayfields['t.lettering_code']['checked'])) {
        print '<td class="center">' . dol_escape_htmltag($line->lettering_code) . '</td>';
        if (!$i) {
            $totalarray['nbfield']++;
        }
    }

    // Amount debit
    if (!empty($arrayfields['t.debit']['checked'])) {
        print '<td class="right nowraponall amount">' . ($line->debit != 0 ? price($line->debit) : '') . '</td>';
        if (!$i) {
            $totalarray['nbfield']++;
        }
        if (!$i) {
            $totalarray['pos'][$totalarray['nbfield']] = 'totaldebit';
        }
        $totalarray['val']['totaldebit'] += $line->debit;
    }

    // Amount credit
    if (!empty($arrayfields['t.credit']['checked'])) {
        print '<td class="right nowraponall amount">' . ($line->credit != 0 ? price($line->credit) : '') . '</td>';
        if (!$i) {
            $totalarray['nbfield']++;
        }
        if (!$i) {
            $totalarray['pos'][$totalarray['nbfield']] = 'totalcredit';
        }
        $totalarray['val']['totalcredit'] += $line->credit;
    }

    // Amount balance
    if (!empty($arrayfields['t.balance']['checked'])) {
        print '<td class="right nowraponall amount">' . price(price2num($sous_total_debit + $line->debit - $sous_total_credit - $line->credit, 'MT')) . '</td>';
        if (!$i) {
            $totalarray['nbfield']++;
        }
        if (!$i) {
            $totalarray['pos'][$totalarray['nbfield']] = 'totalbalance';
        };
        $totalarray['val']['totalbalance'] += $line->debit - $line->credit;
    }

    // Exported operation date
    if (!empty($arrayfields['t.date_export']['checked'])) {
        print '<td class="center">' . dol_print_date($line->date_export, 'dayhour') . '</td>';
        if (!$i) {
            $totalarray['nbfield']++;
        }
    }

    // Validated operation date
    if (!empty($arrayfields['t.date_validated']['checked'])) {
        print '<td class="center">' . dol_print_date($line->date_validation, 'dayhour') . '</td>';
        if (!$i) {
            $totalarray['nbfield']++;
        }
    }

    if (!empty($arrayfields['t.import_key']['checked'])) {
        print '<td class="tdoverflowmax100">' . dol_escape_htmltag($line->import_key) . "</td>\n";
        if (!$i) {
            $totalarray['nbfield']++;
        }
    }

    // Fields from hook
    $parameters = ['arrayfields' => $arrayfields, 'obj' => $line];
    $reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;

    // Action column
    if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
        print '<td class="nowraponall center">';
        if (($massactionbutton || $massaction) && $contextpage != 'poslist') {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
            $selected = 0;
            if (in_array($line->id, $arrayofselected)) {
                $selected = 1;
            }
            print '<input id="cb' . $line->id . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $line->id . '"' . ($selected ? ' checked="checked"' : '') . ' />';
        }
        print '</td>';
        if (!$i) {
            $totalarray['nbfield']++;
        }
    }

    // Comptabilise le sous-total
    $sous_total_debit += $line->debit;
    $sous_total_credit += $line->credit;

    print "</tr>\n";

    $i++;
}

if ($num > 0 && $colspan > 0) {
    print '<tr class="liste_total">';
    print '<td class="right" colspan="' . $colspan . '">' . $langs->trans("TotalForAccount") . ' ' . $accountg . ':</td>';
    print '<td class="nowrap right">' . price(price2num($sous_total_debit, 'MT')) . '</td>';
    print '<td class="nowrap right">' . price(price2num($sous_total_credit, 'MT')) . '</td>';
    if ($colspanend > 0) {
        print '<td colspan="' . $colspanend . '"></td>';
    }
    print '</tr>';
    // Show balance of last shown account
    $balance = $sous_total_debit - $sous_total_credit;
    print '<tr class="liste_total">';
    print '<td class="right" colspan="' . $colspan . '">' . $langs->trans("Balance") . ':</td>';
    if ($balance > 0) {
        print '<td class="nowraponall right">';
        print price(price2num($sous_total_debit - $sous_total_credit, 'MT'));
        print '</td>';
        print '<td></td>';
    } else {
        print '<td></td>';
        print '<td class="nowraponall right">';
        print price(price2num($sous_total_credit - $sous_total_debit, 'MT'));
        print '</td>';
    }
    if ($colspanend > 0) {
        print '<td colspan="' . $colspanend . '"></td>';
    }
    print '</tr>';
}


// Clean total values to round them
if (!empty($totalarray['val']['totaldebit'])) {
    $totalarray['val']['totaldebit'] = price2num($totalarray['val']['totaldebit'], 'MT');
}
if (!empty($totalarray['val']['totalcredit'])) {
    $totalarray['val']['totalcredit'] = price2num($totalarray['val']['totalcredit'], 'MT');
}
if (!empty($totalarray['val']['totalbalance'])) {
    $totalarray['val']['totalbalance'] = price2num($totalarray['val']['totaldebit'] - $totalarray['val']['totalcredit'], 'MT');
}

// Show total line
include DOL_DOCUMENT_ROOT . '/core/tpl/list_print_total.tpl.php';

// If no record found
if ($num == 0) {
    $colspan = 1;
    foreach ($arrayfields as $key => $val) {
        if (!empty($val['checked'])) {
            $colspan++;
        }
    }
    print '<tr><td colspan="' . $colspan . '"><span class="opacitymedium">' . $langs->trans("NoRecordFound") . '</span></td></tr>';
}

$parameters = ['arrayfields' => $arrayfields];
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print "</table>";
print '</div>';

// TODO Replace this with mass delete action
//if ($user->hasRight('accounting', 'mouvements, 'supprimer_tous')) {
//  print '<div class="tabsAction tabsActionNoBottom">'."\n";
//  print '<a class="butActionDelete" name="button_delmvt" href="'.$_SERVER['PHP_SELF'].'?action=delbookkeepingyear&token='.newToken().($param ? '&'.$param : '').'">'.$langs->trans("DeleteMvt").'</a>';
//  print '</div>';
//}

print '</form>';

// End of page
llxFooter();
