<?php

use DoliCore\Form\FormFile;
use DoliCore\Form\FormOther;
use DoliModules\Accounting\Model\BookKeepingLine;

$formother = new FormOther($db);
$formfile = new FormFile($db);

$title_page = $langs->trans("Operations") . ' - ' . $langs->trans("Journals");

// Count total nb of records
$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
    /* The fast and low memory method to get and count full list converts the sql into a sql count */
    $sqlforcount = preg_replace('/^' . preg_quote($sqlfields, '/') . '/', 'SELECT COUNT(*) as nbtotalofrecords', $sql);
    $sqlforcount = preg_replace('/GROUP BY .*$/', '', $sqlforcount);
    $resql = $db->query($sqlforcount);
    if ($resql) {
        $objforcount = $db->fetch_object($resql);
        $nbtotalofrecords = $objforcount->nbtotalofrecords;
    } else {
        dol_print_error($db);
    }

    if (($page * $limit) > $nbtotalofrecords) { // if total resultset is smaller then paging size (filtering), goto and load page 0
        $page = 0;
        $offset = 0;
    }
    $db->free($resql);
}

// Complete request and execute it with limit
$sql .= $db->order($sortfield, $sortorder);
if ($limit) {
    $sql .= $db->plimit($limit + 1, $offset);
}

$resql = $db->query($sql);
if (!$resql) {
    dol_print_error($db);
    exit;
}

$num = $db->num_rows($resql);

$arrayofselected = is_array($toselect) ? $toselect : [];

// Output page
// --------------------------------------------------------------------
$help_url = 'EN:Module_Double_Entry_Accounting|FR:Module_Comptabilit&eacute;_en_Partie_Double';
llxHeader('', $title_page, $help_url);

$formconfirm = '';

// Print form confirm
print $formconfirm;

//$param='';    param started before
if (!empty($contextpage) && $contextpage != $_SERVER['PHP_SELF']) {
    $param .= '&contextpage=' . urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
    $param .= '&limit=' . ((int) $limit);
}

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
    print '<input type="hidden" name="optioncss" value="' . urlencode($optioncss) . '">';
}
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
print '<input type="hidden" name="contextpage" value="' . $contextpage . '">';

if (count($filter)) {
    $buttonLabel = $langs->trans("ExportFilteredList");
} else {
    $buttonLabel = $langs->trans("ExportList");
}

$parameters = ['param' => $param];
$reshook = $hookmanager->executeHooks('addMoreActionsButtonsList', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if ($reshook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

$newcardbutton = empty($hookmanager->resPrint) ? '' : $hookmanager->resPrint;

if (empty($reshook)) {
    $newcardbutton .= dolGetButtonTitle($langs->trans('ViewFlatList'), '', 'fa fa-list paddingleft imgforviewmode', DOL_URL_ROOT . '/accountancy/bookkeeping/list.php?' . $param, '', 1, ['morecss' => 'marginleftonly btnTitleSelected']);
    $newcardbutton .= dolGetButtonTitle($langs->trans('GroupByAccountAccounting'), '', 'fa fa-stream paddingleft imgforviewmode', DOL_URL_ROOT . '/accountancy/bookkeeping/listbyaccount.php?' . $param, '', 1, ['morecss' => 'marginleftonly']);
    $newcardbutton .= dolGetButtonTitle($langs->trans('GroupBySubAccountAccounting'), '', 'fa fa-align-left vmirror paddingleft imgforviewmode', DOL_URL_ROOT . '/accountancy/bookkeeping/listbyaccount.php?type=sub' . $param, '', 1, ['morecss' => 'marginleftonly']);

    $url = './card.php?action=create';
    if (!empty($socid)) {
        $url .= '&socid=' . $socid;
    }
    $newcardbutton .= dolGetButtonTitleSeparator();
    $newcardbutton .= dolGetButtonTitle($langs->trans('NewAccountingMvt'), '', 'fa fa-plus-circle paddingleft', $url, '', $user->hasRight('accounting', 'mouvements', 'creer'));
}

print_barre_liste($title_page, $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'title_accountancy', 0, $newcardbutton, '', $limit, 0, 0, 1);

if ($massaction == 'preunletteringauto') {
    print $form->formconfirm($_SERVER['PHP_SELF'], $langs->trans("ConfirmMassUnletteringAuto"), $langs->trans("ConfirmMassUnletteringQuestion", count($toselect)), "unletteringauto", null, '', 0, 200, 500, 1);
} elseif ($massaction == 'preunletteringmanual') {
    print $form->formconfirm($_SERVER['PHP_SELF'], $langs->trans("ConfirmMassUnletteringManual"), $langs->trans("ConfirmMassUnletteringQuestion", count($toselect)), "unletteringmanual", null, '', 0, 200, 500, 1);
} elseif ($massaction == 'predeletebookkeepingwriting') {
    print $form->formconfirm($_SERVER['PHP_SELF'], $langs->trans("ConfirmMassDeleteBookkeepingWriting"), $langs->trans("ConfirmMassDeleteBookkeepingWritingQuestion", count($toselect)), "deletebookkeepingwriting", null, '', 0, 200, 500, 1);
}

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

$moreforfilter = '';
$moreforfilter .= '<div class="divsearchfield">';
$moreforfilter .= $langs->trans('AccountingCategory') . ': ';
$moreforfilter .= '<div class="nowrap inline-block">';
$moreforfilter .= $formaccounting->select_accounting_category($search_account_category, 'search_account_category', 1, 0, 0, 0);
$moreforfilter .= '</div>';
$moreforfilter .= '</div>';

$parameters = [];
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if (empty($reshook)) {
    $moreforfilter .= $hookmanager->resPrint;
} else {
    $moreforfilter = $hookmanager->resPrint;
}

print '<div class="liste_titre liste_titre_bydiv centpercent">';
print $moreforfilter;
print '</div>';

print '<div class="div-table-responsive">';
print '<table class="tagtable liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">';

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
    print '<td class="liste_titre"><input type="text" name="search_mvt_num" size="6" value="' . dol_escape_htmltag($search_mvt_num) . '"></td>';
}
// Code journal
if (!empty($arrayfields['t.code_journal']['checked'])) {
    print '<td class="liste_titre center">';
    print $formaccounting->multi_select_journal($search_ledger_code, 'search_ledger_code', 0, 1, 1, 1, 'small maxwidth75');
    print '</td>';
}
// Date document
if (!empty($arrayfields['t.doc_date']['checked'])) {
    print '<td class="liste_titre center">';
    print '<div class="nowrapfordate">';
    print $form->selectDate($search_date_start ? $search_date_start : -1, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
    print '</div>';
    print '<div class="nowrapfordate">';
    print $form->selectDate($search_date_end ? $search_date_end : -1, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
    print '</div>';
    print '</td>';
}
// Ref document
if (!empty($arrayfields['t.doc_ref']['checked'])) {
    print '<td class="liste_titre"><input type="text" name="search_doc_ref" size="8" value="' . dol_escape_htmltag($search_doc_ref) . '"></td>';
}
// Accountancy account
if (!empty($arrayfields['t.numero_compte']['checked'])) {
    print '<td class="liste_titre">';
    print '<div class="nowrap">';
    print $formaccounting->select_account($search_accountancy_code_start, 'search_accountancy_code_start', $langs->trans('From'), [], 1, 1, 'maxwidth150', 'account');
    print '</div>';
    print '<div class="nowrap">';
    print $formaccounting->select_account($search_accountancy_code_end, 'search_accountancy_code_end', $langs->trans('to'), [], 1, 1, 'maxwidth150', 'account');
    print '</div>';
    print '</td>';
}
// Subledger account
if (!empty($arrayfields['t.subledger_account']['checked'])) {
    print '<td class="liste_titre">';
    // TODO For the moment we keep a free input text instead of a combo. The select_auxaccount has problem because it does not
    // use setup of keypress to select thirdparty and this hang browser on large database.
    if (getDolGlobalString('ACCOUNTANCY_COMBO_FOR_AUX')) {
        print '<div class="nowrap">';
        //print $langs->trans('From').' ';
        print $formaccounting->select_auxaccount($search_accountancy_aux_code_start, 'search_accountancy_aux_code_start', $langs->trans('From'), 'maxwidth250', 'subledgeraccount');
        print '</div>';
        print '<div class="nowrap">';
        print $formaccounting->select_auxaccount($search_accountancy_aux_code_end, 'search_accountancy_aux_code_end', $langs->trans('to'), 'maxwidth250', 'subledgeraccount');
        print '</div>';
    } else {
        print '<input type="text" class="maxwidth75" name="search_accountancy_aux_code" value="' . dol_escape_htmltag($search_accountancy_aux_code) . '">';
    }
    print '</td>';
}
// Label operation
if (!empty($arrayfields['t.label_operation']['checked'])) {
    print '<td class="liste_titre">';
    print '<input type="text" size="7" class="flat" name="search_mvt_label" value="' . dol_escape_htmltag($search_mvt_label) . '"/>';
    print '</td>';
}
// Debit
if (!empty($arrayfields['t.debit']['checked'])) {
    print '<td class="liste_titre right">';
    print '<input type="text" class="flat" name="search_debit" size="4" value="' . dol_escape_htmltag($search_debit) . '">';
    print '</td>';
}
// Credit
if (!empty($arrayfields['t.credit']['checked'])) {
    print '<td class="liste_titre right">';
    print '<input type="text" class="flat" name="search_credit" size="4" value="' . dol_escape_htmltag($search_credit) . '">';
    print '</td>';
}
// Lettering code
if (!empty($arrayfields['t.lettering_code']['checked'])) {
    print '<td class="liste_titre center">';
    print '<input type="text" size="3" class="flat" name="search_lettering_code" value="' . dol_escape_htmltag($search_lettering_code) . '"/>';
    print '<br><span class="nowrap"><input type="checkbox" name="search_not_reconciled" value="notreconciled"' . ($search_not_reconciled == 'notreconciled' ? ' checked' : '') . '>' . $langs->trans("NotReconciled") . '</span>';
    print '</td>';
}

// Fields from hook
$parameters = ['arrayfields' => $arrayfields];
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

// Date creation
if (!empty($arrayfields['t.date_creation']['checked'])) {
    print '<td class="liste_titre center">';
    print '<div class="nowrapfordate">';
    print $form->selectDate($search_date_creation_start, 'search_date_creation_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
    print '</div>';
    print '<div class="nowrapfordate">';
    print $form->selectDate($search_date_creation_end, 'search_date_creation_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
    print '</div>';
    print '</td>';
}
// Date modification
if (!empty($arrayfields['t.tms']['checked'])) {
    print '<td class="liste_titre center">';
    print '<div class="nowrapfordate">';
    print $form->selectDate($search_date_modification_start, 'search_date_modification_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
    print '</div>';
    print '<div class="nowrapfordate">';
    print $form->selectDate($search_date_modification_end, 'search_date_modification_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
    print '</div>';
    print '</td>';
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
    print_liste_field_titre($selectedfields, $_SERVER['PHP_SELF'], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch actioncolumn ');
}
if (!empty($arrayfields['t.piece_num']['checked'])) {
    print_liste_field_titre($arrayfields['t.piece_num']['label'], $_SERVER['PHP_SELF'], "t.piece_num", "", $param, "", $sortfield, $sortorder);
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
if (!empty($arrayfields['t.numero_compte']['checked'])) {
    print_liste_field_titre($arrayfields['t.numero_compte']['label'], $_SERVER['PHP_SELF'], "t.numero_compte", "", $param, "", $sortfield, $sortorder);
}
if (!empty($arrayfields['t.subledger_account']['checked'])) {
    print_liste_field_titre($arrayfields['t.subledger_account']['label'], $_SERVER['PHP_SELF'], "t.subledger_account", "", $param, "", $sortfield, $sortorder);
}
if (!empty($arrayfields['t.label_operation']['checked'])) {
    print_liste_field_titre($arrayfields['t.label_operation']['label'], $_SERVER['PHP_SELF'], "t.label_operation", "", $param, "", $sortfield, $sortorder);
}
if (!empty($arrayfields['t.debit']['checked'])) {
    print_liste_field_titre($arrayfields['t.debit']['label'], $_SERVER['PHP_SELF'], "t.debit", "", $param, '', $sortfield, $sortorder, 'right ');
}
if (!empty($arrayfields['t.credit']['checked'])) {
    print_liste_field_titre($arrayfields['t.credit']['label'], $_SERVER['PHP_SELF'], "t.credit", "", $param, '', $sortfield, $sortorder, 'right ');
}
if (!empty($arrayfields['t.lettering_code']['checked'])) {
    print_liste_field_titre($arrayfields['t.lettering_code']['label'], $_SERVER['PHP_SELF'], "t.lettering_code", "", $param, '', $sortfield, $sortorder, 'center ');
}
// Hook fields
$parameters = ['arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder];
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (!empty($arrayfields['t.date_creation']['checked'])) {
    print_liste_field_titre($arrayfields['t.date_creation']['label'], $_SERVER['PHP_SELF'], "t.date_creation", "", $param, '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['t.tms']['checked'])) {
    print_liste_field_titre($arrayfields['t.tms']['label'], $_SERVER['PHP_SELF'], "t.tms", "", $param, '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['t.date_export']['checked'])) {
    print_liste_field_titre($arrayfields['t.date_export']['label'], $_SERVER['PHP_SELF'], "t.date_export,t.doc_date", "", $param, '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['t.date_validated']['checked'])) {
    print_liste_field_titre($arrayfields['t.date_validated']['label'], $_SERVER['PHP_SELF'], "t.date_validated,t.doc_date", "", $param, '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['t.import_key']['checked'])) {
    print_liste_field_titre($arrayfields['t.import_key']['label'], $_SERVER['PHP_SELF'], "t.import_key", "", $param, '', $sortfield, $sortorder, 'center ');
}
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
    print_liste_field_titre($selectedfields, $_SERVER['PHP_SELF'], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
}
print "</tr>\n";

$line = new BookKeepingLine($db);

// Loop on record
// --------------------------------------------------------------------
$i = 0;
$totalarray = [];
$totalarray['nbfield'] = 0;
$total_debit = 0;
$total_credit = 0;
$totalarray['val'] = [];
$totalarray['val']['totaldebit'] = 0;
$totalarray['val']['totalcredit'] = 0;

while ($i < min($num, $limit)) {
    $obj = $db->fetch_object($resql);
    if (empty($obj)) {
        break; // Should not happen
    }

    $line->id = $obj->rowid;
    $line->doc_date = $db->jdate($obj->doc_date);
    $line->doc_type = $obj->doc_type;
    $line->doc_ref = $obj->doc_ref;
    $line->fk_doc = $obj->fk_doc;
    $line->fk_docdet = $obj->fk_docdet;
    $line->thirdparty_code = $obj->thirdparty_code;
    $line->subledger_account = $obj->subledger_account;
    $line->subledger_label = $obj->subledger_label;
    $line->numero_compte = $obj->numero_compte;
    $line->label_compte = $obj->label_compte;
    $line->label_operation = $obj->label_operation;
    $line->debit = $obj->debit;
    $line->credit = $obj->credit;
    $line->montant = $obj->amount; // deprecated
    $line->amount = $obj->amount;
    $line->sens = $obj->sens;
    $line->lettering_code = $obj->lettering_code;
    $line->fk_user_author = $obj->fk_user_author;
    $line->import_key = $obj->import_key;
    $line->code_journal = $obj->code_journal;
    $line->journal_label = $obj->journal_label;
    $line->piece_num = $obj->piece_num;
    $line->date_creation = $db->jdate($obj->date_creation);
    $line->date_modification = $db->jdate($obj->date_modification);
    $line->date_export = $db->jdate($obj->date_export);
    $line->date_validation = $db->jdate($obj->date_validation);

    $total_debit += $line->debit;
    $total_credit += $line->credit;

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
        print '<td class="center tdoverflowmax150">' . $journaltoshow . '</td>';
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
            $objectstatic = new AccountLine($db);
            $objectstatic->fetch($line->fk_doc);
        } else {
            // Other type
        }

        $labeltoshow = '';
        $labeltoshowalt = '';
        if ($line->doc_type == 'customer_invoice' || $line->doc_type == 'supplier_invoice' || $line->doc_type == 'expense_report') {
            $labeltoshow .= $objectstatic->getNomUrl(1, '', 0, 0, '', 0, -1, 1);
            $labeltoshow .= $documentlink;
            $labeltoshowalt .= $objectstatic->ref;
        } elseif ($line->doc_type == 'bank') {
            $labeltoshow .= $objectstatic->getNomUrl(1);
            $labeltoshowalt .= $objectstatic->ref;
            $bank_ref = strstr($line->doc_ref, '-');
            $labeltoshow .= " " . $bank_ref;
            $labeltoshowalt .= " " . $bank_ref;
        } else {
            $labeltoshow .= $line->doc_ref;
            $labeltoshowalt .= $line->doc_ref;
        }

        print '<td class="nowraponall tdoverflowmax250" title="' . dol_escape_htmltag($labeltoshowalt) . '">';
        print $labeltoshow;
        print "</td>\n";
        if (!$i) {
            $totalarray['nbfield']++;
        }
    }

    // Account number
    if (!empty($arrayfields['t.numero_compte']['checked'])) {
        print '<td>' . length_accountg($line->numero_compte) . '</td>';
        if (!$i) {
            $totalarray['nbfield']++;
        }
    }

    // Subledger account
    if (!empty($arrayfields['t.subledger_account']['checked'])) {
        print '<td>' . length_accounta($line->subledger_account) . '</td>';
        if (!$i) {
            $totalarray['nbfield']++;
        }
    }

    // Label operation
    if (!empty($arrayfields['t.label_operation']['checked'])) {
        print '<td class="small tdoverflowmax200" title="' . dol_escape_htmltag($line->label_operation) . '">' . dol_escape_htmltag($line->label_operation) . '</td>';
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

    // Lettering code
    if (!empty($arrayfields['t.lettering_code']['checked'])) {
        print '<td class="center">' . $line->lettering_code . '</td>';
        if (!$i) {
            $totalarray['nbfield']++;
        }
    }

    // Fields from hook
    $parameters = ['arrayfields' => $arrayfields, 'obj' => $obj, 'i' => $i, 'totalarray' => &$totalarray];
    $reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;

    // Creation operation date
    if (!empty($arrayfields['t.date_creation']['checked'])) {
        print '<td class="center">' . dol_print_date($line->date_creation, 'dayhour', 'tzuserrel') . '</td>';
        if (!$i) {
            $totalarray['nbfield']++;
        }
    }

    // Modification operation date
    if (!empty($arrayfields['t.tms']['checked'])) {
        print '<td class="center">' . dol_print_date($line->date_modification, 'dayhour', 'tzuserrel') . '</td>';
        if (!$i) {
            $totalarray['nbfield']++;
        }
    }

    // Exported operation date
    if (!empty($arrayfields['t.date_export']['checked'])) {
        print '<td class="center nowraponall">' . dol_print_date($line->date_export, 'dayhour', 'tzuserrel') . '</td>';
        if (!$i) {
            $totalarray['nbfield']++;
        }
    }

    // Validated operation date
    if (!empty($arrayfields['t.date_validated']['checked'])) {
        print '<td class="center nowraponall">' . dol_print_date($line->date_validation, 'dayhour', 'tzuserrel') . '</td>';
        if (!$i) {
            $totalarray['nbfield']++;
        }
    }

    if (!empty($arrayfields['t.import_key']['checked'])) {
        print '<td class="tdoverflowmax100">' . $obj->import_key . "</td>\n";
        if (!$i) {
            $totalarray['nbfield']++;
        }
    }

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


    print "</tr>\n";

    $i++;
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

$parameters = ['arrayfields' => $arrayfields, 'sql' => $sql];
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print "</table>";
print '</div>';

print '</form>';

// End of page
llxFooter();
