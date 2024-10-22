<?php

$form = new Form($db);

$now = dol_now();

$help_url = '';
$title = $langs->trans('ListOf', $langs->transnoentitiesnoconv("Assets"));
$morejs = [];
$morecss = [];


// Build and execute select
// --------------------------------------------------------------------
$sql = 'SELECT ';
$sql .= $object->getFieldList('t');
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
    foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
        $sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef." . $key . " as options_" . $key : '');
    }
}
// Add fields from hooks
$parameters = [];
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql = preg_replace('/,\s*$/', '', $sql);

$sqlfields = $sql; // $sql fields to remove for count total

$sql .= " FROM " . MAIN_DB_PREFIX . $object->table_element . " as t";
if (isset($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . $object->table_element . "_extrafields as ef on (t.rowid = ef.fk_object)";
}
// Add table from hooks
$parameters = [];
$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
if ($object->ismultientitymanaged == 1) {
    $sql .= " WHERE t.entity IN (" . getEntity($object->element) . ")";
} else {
    $sql .= " WHERE 1 = 1";
}
foreach ($search as $key => $val) {
    if (array_key_exists($key, $object->fields)) {
        if ($key == 'status' && $search[$key] == -1) {
            continue;
        }
        $mode_search = (($object->isInt($object->fields[$key]) || $object->isFloat($object->fields[$key])) ? 1 : 0);
        if ((strpos($object->fields[$key]['type'], 'integer:') === 0) || (strpos($object->fields[$key]['type'], 'sellist:') === 0) || !empty($object->fields[$key]['arrayofkeyval'])) {
            if ($search[$key] == '-1' || ($search[$key] === '0' && (empty($object->fields[$key]['arrayofkeyval']) || !array_key_exists('0', $object->fields[$key]['arrayofkeyval'])))) {
                $search[$key] = '';
            }
            $mode_search = 2;
        }
        if ($search[$key] != '') {
            $sql .= natural_search($key, $search[$key], (($key == 'status') ? 2 : $mode_search));
        }
    } else {
        if (preg_match('/(_dtstart|_dtend)$/', $key) && $search[$key] != '') {
            $columnName = preg_replace('/(_dtstart|_dtend)$/', '', $key);
            if (preg_match('/^(date|timestamp|datetime)/', $object->fields[$columnName]['type'])) {
                if (preg_match('/_dtstart$/', $key)) {
                    $sql .= " AND t." . $columnName . " >= '" . $db->idate($search[$key]) . "'";
                }
                if (preg_match('/_dtend$/', $key)) {
                    $sql .= " AND t." . $columnName . " <= '" . $db->idate($search[$key]) . "'";
                }
            }
        }
    }
}
if ($search_all) {
    $sql .= natural_search(array_keys($fieldstosearchall), $search_all);
}
//$sql.= dolSqlDateFilter("t.field", $search_xxxday, $search_xxxmonth, $search_xxxyear);
// Add where from extra fields
include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters = [];
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

/* If a group by is required
$sql .= " GROUP BY ";
foreach($object->fields as $key => $val) {
    $sql .= "t.".$key.", ";
}
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
    foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
        $sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? "ef.".$key.', ' : '');
    }
}
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListGroupBy', $parameters, $object);    // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql = preg_replace('/,\s*$/', '', $sql);
*/

// Add HAVING from hooks
/*
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListHaving', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= !empty($hookmanager->resPrint) ? (" HAVING 1=1 " . $hookmanager->resPrint) : "";
*/

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


// Direct jump if only one record found
if ($num == 1 && getDolGlobalString('MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE') && $search_all && !$page) {
    $obj = $db->fetch_object($resql);
    $id = $obj->rowid;
    header("Location: " . DOL_URL_ROOT . '/asset/card.php?id=' . $id);
    exit;
}


// Output page
// --------------------------------------------------------------------

llxHeader('', $title, $help_url, '', 0, 0, $morejs, $morecss, '', 'mod-asset page-list');

$arrayofselected = is_array($toselect) ? $toselect : [];

$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER['PHP_SELF']) {
    $param .= '&contextpage=' . urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
    $param .= '&limit=' . ((int) $limit);
}
foreach ($search as $key => $val) {
    if (is_array($search[$key]) && count($search[$key])) {
        foreach ($search[$key] as $skey) {
            if ($skey != '') {
                $param .= '&search_' . $key . '[]=' . urlencode($skey);
            }
        }
    } elseif ($search[$key] != '') {
        $param .= '&search_' . $key . '=' . urlencode($search[$key]);
    }
}
if ($optioncss != '') {
    $param .= '&optioncss=' . urlencode($optioncss);
}
// Add $param from extra fields
include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_param.tpl.php';
// Add $param from hooks
$parameters = [];
$reshook = $hookmanager->executeHooks('printFieldListSearchParam', $parameters, $object); // Note that $action and $object may have been modified by hook
$param .= $hookmanager->resPrint;

// List of mass actions available
$arrayofmassactions = [
    //'validate'=>img_picto('', 'check', 'class="pictofixedwidth"').$langs->trans("Validate"),
    //'generate_doc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("ReGeneratePDF"),
    //'builddoc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
    //'presend'=>img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
];
if ($permissiontodelete) {
    $arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"') . $langs->trans("Delete");
}
if (GETPOSTINT('nomassaction') || in_array($massaction, ['presend', 'predelete'])) {
    $arrayofmassactions = [];
}
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

print '<form method="POST" id="searchFormList" action="' . $_SERVER['PHP_SELF'] . '">' . "\n";
if ($optioncss != '') {
    print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
}
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
print '<input type="hidden" name="page" value="' . $page . '">';
print '<input type="hidden" name="contextpage" value="' . $contextpage . '">';

$newcardbutton = '';
$newcardbutton .= dolGetButtonTitle($langs->trans('New'), '', 'fa fa-plus-circle', DOL_URL_ROOT . '/asset/card.php?action=create&backtopage=' . urlencode($_SERVER['PHP_SELF']), '', $permissiontoadd);

print_barre_liste($title, $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'object_' . $object->picto, 0, $newcardbutton, '', $limit, 0, 0, 1);

// Add code for pre mass action (confirmation or email presend form)
$topicmail = "SendAssetRef";
$modelmail = "asset";
$objecttmp = new Asset($db);
$trackid = 'asset' . $object->id;
include DOL_DOCUMENT_ROOT . '/core/tpl/massactions_pre.tpl.php';

if ($search_all) {
    foreach ($fieldstosearchall as $key => $val) {
        $fieldstosearchall[$key] = $langs->trans($val);
    }
    print '<div class="divsearchfieldfilter">' . $langs->trans("FilterOnInto", $search_all) . implode(', ', $fieldstosearchall) . '</div>';
}

$moreforfilter = '';
/*$moreforfilter.='<div class="divsearchfield">';
$moreforfilter.= $langs->trans('MyFilter') . ': <input type="text" name="search_myfield" value="'.dol_escape_htmltag($search_myfield).'">';
$moreforfilter.= '</div>';*/

$parameters = [];
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object); // Note that $action and $object may have been modified by hook
if (empty($reshook)) {
    $moreforfilter .= $hookmanager->resPrint;
} else {
    $moreforfilter = $hookmanager->resPrint;
}

if (!empty($moreforfilter)) {
    print '<div class="liste_titre liste_titre_bydiv centpercent">';
    print $moreforfilter;
    print '</div>';
}

$varpage = empty($contextpage) ? $_SERVER['PHP_SELF'] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
print '<table class="tagtable nobottomiftotal liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">' . "\n";


// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
foreach ($object->fields as $key => $val) {
    $cssforfield = (empty($val['csslist']) ? (empty($val['css']) ? '' : $val['css']) : $val['csslist']);
    if ($key == 'status') {
        $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
    } elseif (in_array($val['type'], ['date', 'datetime', 'timestamp'])) {
        $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
    } elseif (in_array($val['type'], ['timestamp'])) {
        $cssforfield .= ($cssforfield ? ' ' : '') . 'nowrap';
    } elseif (in_array($val['type'], ['double(24,8)', 'double(6,3)', 'integer', 'real', 'price']) && $val['label'] != 'TechnicalID' && empty($val['arrayofkeyval'])) {
        $cssforfield .= ($cssforfield ? ' ' : '') . 'right';
    }
    if (!empty($arrayfields['t.' . $key]['checked'])) {
        print '<td class="liste_titre' . ($cssforfield ? ' ' . $cssforfield : '') . '">';
        if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
            print $form->selectarray('search_' . $key, $val['arrayofkeyval'], (isset($search[$key]) ? $search[$key] : ''), $val['notnull'], 0, 0, '', 1, 0, 0, '', 'maxwidth100', 1);
        } elseif ((strpos($val['type'], 'integer:') === 0) || (strpos($val['type'], 'sellist:') === 0)) {
            print $object->showInputField($val, $key, (isset($search[$key]) ? $search[$key] : ''), '', '', 'search_', 'maxwidth125', 1);
        } elseif (preg_match('/^(date|timestamp|datetime)/', $val['type'])) {
            print '<div class="nowrap">';
            print $form->selectDate($search[$key . '_dtstart'] ? $search[$key . '_dtstart'] : '', "search_" . $key . "_dtstart", 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
            print '</div>';
            print '<div class="nowrap">';
            print $form->selectDate($search[$key . '_dtend'] ? $search[$key . '_dtend'] : '', "search_" . $key . "_dtend", 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
            print '</div>';
        } elseif ($key == 'lang') {
            $formadmin = new FormAdmin($db);
            print $formadmin->select_language($search[$key], 'search_lang', 0, null, 1, 0, 0, 'minwidth150 maxwidth200', 2);
        } else {
            print '<input type="text" class="flat maxwidth75" name="search_' . $key . '" value="' . dol_escape_htmltag(isset($search[$key]) ? $search[$key] : '') . '">';
        }
        print '</td>';
    }
}
// Extra fields
include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters = ['arrayfields' => $arrayfields];
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Action column
print '<td class="liste_titre maxwidthsearch">';
$searchpicto = $form->showFilterButtons();
print $searchpicto;
print '</td>';
print '</tr>' . "\n";


// Fields title label
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
foreach ($object->fields as $key => $val) {
    $cssforfield = (empty($val['csslist']) ? (empty($val['css']) ? '' : $val['css']) : $val['csslist']);
    if ($key == 'status') {
        $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
    } elseif (in_array($val['type'], ['date', 'datetime', 'timestamp'])) {
        $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
    } elseif (in_array($val['type'], ['timestamp'])) {
        $cssforfield .= ($cssforfield ? ' ' : '') . 'nowrap';
    } elseif (in_array($val['type'], ['double(24,8)', 'double(6,3)', 'integer', 'real', 'price']) && $val['label'] != 'TechnicalID' && empty($val['arrayofkeyval'])) {
        $cssforfield .= ($cssforfield ? ' ' : '') . 'right';
    }
    if (!empty($arrayfields['t.' . $key]['checked'])) {
        print getTitleFieldOfList($arrayfields['t.' . $key]['label'], 0, $_SERVER['PHP_SELF'], 't.' . $key, '', $param, ($cssforfield ? 'class="' . $cssforfield . '"' : ''), $sortfield, $sortorder, ($cssforfield ? $cssforfield . ' ' : '')) . "\n";
    }
}
// Extra fields
include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_title.tpl.php';
// Hook fields
$parameters = ['arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder];
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Action column
print getTitleFieldOfList($selectedfields, 0, $_SERVER['PHP_SELF'], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ') . "\n";
print '</tr>' . "\n";


// Detect if we need a fetch on each output line
$needToFetchEachLine = 0;
if (isset($extrafields->attributes[$object->table_element]['computed']) && is_array($extrafields->attributes[$object->table_element]['computed']) && count($extrafields->attributes[$object->table_element]['computed']) > 0) {
    foreach ($extrafields->attributes[$object->table_element]['computed'] as $key => $val) {
        if (!is_null($val) && preg_match('/\$object/', $val)) {
            $needToFetchEachLine++; // There is at least one compute field that use $object
        }
    }
}


// Loop on record
// --------------------------------------------------------------------
$i = 0;
$totalarray = [];
$totalarray['nbfield'] = 0;
while ($i < ($limit ? min($num, $limit) : $num)) {
    $obj = $db->fetch_object($resql);
    if (empty($obj)) {
        break; // Should not happen
    }

    // Store properties in $object
    $object->setVarsFromFetchObj($obj);

    // Show here line of result
    print '<tr class="oddeven">';
    foreach ($object->fields as $key => $val) {
        $cssforfield = (empty($val['csslist']) ? (empty($val['css']) ? '' : $val['css']) : $val['csslist']);
        if (in_array($val['type'], ['date', 'datetime', 'timestamp'])) {
            $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
        } elseif ($key == 'status') {
            $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
        }

        if (in_array($val['type'], ['timestamp'])) {
            $cssforfield .= ($cssforfield ? ' ' : '') . 'nowrap';
        } elseif ($key == 'ref') {
            $cssforfield .= ($cssforfield ? ' ' : '') . 'nowrap';
        }

        if (in_array($val['type'], ['double(24,8)', 'double(6,3)', 'integer', 'real', 'price']) && !in_array($key, ['rowid', 'status']) && empty($val['arrayofkeyval'])) {
            $cssforfield .= ($cssforfield ? ' ' : '') . 'right';
        }
        //if (in_array($key, array('fk_soc', 'fk_user', 'fk_warehouse'))) $cssforfield = 'tdoverflowmax100';

        if (!empty($arrayfields['t.' . $key]['checked'])) {
            print '<td' . ($cssforfield ? ' class="' . $cssforfield . '"' : '') . '>';
            if ($key == 'status') {
                print $object->getLibStatut(5);
            } elseif ($key == 'rowid') {
                print $object->showOutputField($val, $key, $object->id, '');
            } else {
                print $object->showOutputField($val, $key, $object->$key, '');
            }
            print '</td>';
            if (!$i) {
                $totalarray['nbfield']++;
            }
            if (!empty($val['isameasure']) && $val['isameasure'] == 1) {
                if (!$i) {
                    $totalarray['pos'][$totalarray['nbfield']] = 't.' . $key;
                }
                if (!isset($totalarray['val'])) {
                    $totalarray['val'] = [];
                }
                if (!isset($totalarray['val']['t.' . $key])) {
                    $totalarray['val']['t.' . $key] = 0;
                }
                $totalarray['val']['t.' . $key] += $object->$key;
            }
        }
    }
    // Extra fields
    include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_print_fields.tpl.php';
    // Fields from hook
    $parameters = ['arrayfields' => $arrayfields, 'object' => $object, 'obj' => $obj, 'i' => $i, 'totalarray' => &$totalarray];
    $reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object); // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
    // Action column
    print '<td class="nowrap center">';
    if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
        $selected = 0;
        if (in_array($object->id, $arrayofselected)) {
            $selected = 1;
        }
        print '<input id="cb' . $object->id . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $object->id . '"' . ($selected ? ' checked="checked"' : '') . '>';
    }
    print '</td>';
    if (!$i) {
        $totalarray['nbfield']++;
    }

    print '</tr>' . "\n";

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


$db->free($resql);

$parameters = ['arrayfields' => $arrayfields, 'sql' => $sql];
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</table>' . "\n";
print '</div>' . "\n";

print '</form>' . "\n";

if (in_array('builddoc', array_keys($arrayofmassactions)) && ($nbtotalofrecords === '' || $nbtotalofrecords)) {
    $hidegeneratedfilelistifempty = 1;
    if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) {
        $hidegeneratedfilelistifempty = 0;
    }

    $formfile = new FormFile($db);

    // Show list of available documents
    $urlsource = $_SERVER['PHP_SELF'] . '?sortfield=' . $sortfield . '&sortorder=' . $sortorder;
    $urlsource .= str_replace('&amp;', '&', $param);

    $filedir = $diroutputmassaction;
    $genallowed = $permissiontoread;
    $delallowed = $permissiontoadd;

    print $formfile->showdocuments('massfilesarea_asset', '', $filedir, $urlsource, 0, $delallowed, '', 1, 1, 0, 48, 1, $param, $title, '', '', '', null, $hidegeneratedfilelistifempty);
}

// End of page
llxFooter();
