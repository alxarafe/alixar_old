<?php

$form = new Form($db);
$formadmin = new FormAdmin($db);

$help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';

llxHeader('', $langs->trans('DictionaryAccountancyCategory'), $help_url);

$titre = $langs->trans($tablib[$id]);
$linkback = '';
$titlepicto = 'setup';

print load_fiche_titre($titre, $linkback, $titlepicto);

print '<span class="opacitymedium">' . $langs->trans("AccountingAccountGroupsDesc", $langs->transnoentitiesnoconv("ByPersonalizedAccountGroups")) . '</span><br><br>';

// Confirmation of the deletion of the line
if ($action == 'delete') {
    print $form->formconfirm($_SERVER['PHP_SELF'] . '?' . ($page ? 'page=' . $page . '&' : '') . 'sortfield=' . $sortfield . '&sortorder=' . $sortorder . '&rowid=' . $rowid . '&code=' . $code . '&id=' . $id . ($search_country_id > 0 ? '&search_country_id=' . $search_country_id : ''), $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_delete', '', 0, 1);
}

// Complete search query with sorting criteria
$sql = $tabsql[$id];

if ($search_country_id > 0) {
    if (preg_match('/ WHERE /', $sql)) {
        $sql .= " AND ";
    } else {
        $sql .= " WHERE ";
    }
    $sql .= " (a.fk_country = " . ((int) $search_country_id) . " OR a.fk_country = 0)";
}

// If sort order is "country", we use country_code instead
if ($sortfield == 'country') {
    $sortfield = 'country_code';
}
if (empty($sortfield)) {
    $sortfield = 'position';
}

$sql .= $db->order($sortfield, $sortorder);
$sql .= $db->plimit($listlimit + 1, $offset);


$fieldlist = explode(',', $tabfield[$id]);

$param = '&id=' . $id;
if ($search_country_id > 0) {
    $param .= '&search_country_id=' . urlencode((string) ($search_country_id));
}
$paramwithsearch = $param;
if ($sortorder) {
    $paramwithsearch .= '&sortorder=' . urlencode($sortorder);
}
if ($sortfield) {
    $paramwithsearch .= '&sortfield=' . urlencode($sortfield);
}
if (GETPOST('from', 'alpha')) {
    $paramwithsearch .= '&from=' . urlencode(GETPOST('from', 'alpha'));
}
if ($listlimit) {
    $paramwithsearch .= '&listlimit=' . urlencode((string) (GETPOSTINT('listlimit')));
}
print '<form action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '" method="POST">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="from" value="' . dol_escape_htmltag(GETPOST('from', 'alpha')) . '">';
print '<input type="hidden" name="sortfield" value="' . dol_escape_htmltag($sortfield) . '">';
print '<input type="hidden" name="sortorder" value="' . dol_escape_htmltag($sortorder) . '">';


print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

// Form to add a new line
if ($tabname[$id]) {
    $fieldlist = explode(',', $tabfield[$id]);

    // Line for title
    print '<tr class="liste_titre">';
    // Action column
    if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
        print '<td></td>';
    }
    foreach ($fieldlist as $field => $value) {
        // Determine le nom du champ par rapport aux noms possibles
        // dans les dictionnaires de donnees
        $valuetoshow = ucfirst($fieldlist[$field]); // By default
        $valuetoshow = $langs->trans($valuetoshow); // try to translate
        $class = "left";
        if ($fieldlist[$field] == 'type') {
            if ($tabname[$id] == MAIN_DB_PREFIX . "c_paiement") {
                $valuetoshow = $form->textwithtooltip($langs->trans("Type"), $langs->trans("TypePaymentDesc"), 2, 1, img_help(1, ''));
            } else {
                $valuetoshow = $langs->trans("Type");
            }
        }
        if ($fieldlist[$field] == 'code') {
            $valuetoshow = $langs->trans("Code");
            $class = 'width75';
        }
        if ($fieldlist[$field] == 'libelle' || $fieldlist[$field] == 'label') {
            $valuetoshow = $langs->trans("Label");
        }
        if ($fieldlist[$field] == 'libelle_facture') {
            $valuetoshow = $langs->trans("LabelOnDocuments");
        }
        if ($fieldlist[$field] == 'country') {
            $valuetoshow = $langs->trans("Country");
        }
        if ($fieldlist[$field] == 'accountancy_code') {
            $valuetoshow = $langs->trans("AccountancyCode");
        }
        if ($fieldlist[$field] == 'accountancy_code_sell') {
            $valuetoshow = $langs->trans("AccountancyCodeSell");
        }
        if ($fieldlist[$field] == 'accountancy_code_buy') {
            $valuetoshow = $langs->trans("AccountancyCodeBuy");
        }
        if ($fieldlist[$field] == 'pcg_version' || $fieldlist[$field] == 'fk_pcg_version') {
            $valuetoshow = $langs->trans("Pcg_version");
        }
        if ($fieldlist[$field] == 'range_account') {
            $valuetoshow = $langs->trans("Comment");
            $class = 'width75';
        }
        if ($fieldlist[$field] == 'category_type') {
            $valuetoshow = $langs->trans("Calculated");
        }

        if ($valuetoshow != '') {
            print '<td class="' . $class . '">';
            if (!empty($tabhelp[$id][$value]) && preg_match('/^http(s*):/i', $tabhelp[$id][$value])) {
                print '<a href="' . $tabhelp[$id][$value] . '">' . $valuetoshow . ' ' . img_help(1, $valuetoshow) . '</a>';
            } elseif (!empty($tabhelp[$id][$value])) {
                print $form->textwithpicto($valuetoshow, $tabhelp[$id][$value]);
            } else {
                print $valuetoshow;
            }
            print '</td>';
        }
    }

    print '<td>';
    print '<input type="hidden" name="id" value="' . $id . '">';
    print '</td>';
    print '<td></td>';
    // Action column
    if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
        print '<td></td>';
    }
    print '</tr>';

    // Line to enter new values
    print '<tr class="oddeven nodrag nodrop nohover">';

    // Action column
    if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
        print '<td></td>';
    }

    $obj = new stdClass();
    // If data was already input, we define them in obj to populate input fields.
    if (GETPOST('actionadd', 'alpha')) {
        foreach ($fieldlist as $key => $val) {
            if (GETPOST($val) != '') {
                $obj->$val = GETPOST($val);
            }
        }
    }

    $tmpaction = 'create';
    $parameters = ['fieldlist' => $fieldlist, 'tabname' => $tabname[$id]];
    $reshook = $hookmanager->executeHooks('createDictionaryFieldlist', $parameters, $obj, $tmpaction); // Note that $action and $object may have been modified by some hooks
    $error = $hookmanager->error;
    $errors = $hookmanager->errors;

    if (empty($reshook)) {
        fieldListAccountingCategories($fieldlist, $obj, $tabname[$id], 'add');
    }

    print '<td colspan="2" class="right">';
    print '<input type="submit" class="button button-add" name="actionadd" value="' . $langs->trans("Add") . '">';
    print '</td>';

    // Action column
    if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
        print '<td></td>';
    }

    print "</tr>";

    $colspan = count($fieldlist) + 3;
    if ($id == 32) {
        $colspan++;
    }
}

print '</table>';
print '</div>';

print '<div class="div-table-responsive">';
print '<table class="noborder centpercent">';

// List of available record in database
dol_syslog("htdocs/accountancy/admin/categories_list.php", LOG_DEBUG);

$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);
    $i = 0;

    // There is several pages
    if ($num > $listlimit) {
        print '<tr class="none"><td class="right" colspan="' . (2 + count($fieldlist)) . '">';
        print_fleche_navigation($page, $_SERVER['PHP_SELF'], $paramwithsearch, ($num > $listlimit), '<li class="pagination"><span>' . $langs->trans("Page") . ' ' . ($page + 1) . '</span></li>');
        print '</td></tr>';
    }

    $filterfound = 0;
    foreach ($fieldlist as $field => $value) {
        $showfield = 1; // By default
        if ($fieldlist[$field] == 'region_id' || $fieldlist[$field] == 'country_id') {
            $showfield = 0;
        }
        if ($showfield) {
            if ($value == 'country') {
                $filterfound++;
            }
        }
    }

    // Title line with search boxes
    print '<tr class="liste_titre liste_titre_add liste_titre_filter">';

    // Action column
    if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
        print '<td class="liste_titre center">';
        if ($filterfound) {
            $searchpicto = $form->showFilterAndCheckAddButtons(0);
            print $searchpicto;
        }
        print '</td>';
    }

    $filterfound = 0;
    foreach ($fieldlist as $field => $value) {
        $showfield = 1; // By default

        if ($fieldlist[$field] == 'region_id' || $fieldlist[$field] == 'country_id') {
            $showfield = 0;
        }

        if ($showfield) {
            if ($value == 'country') {
                print '<td class="liste_titre">';
                print $form->select_country($search_country_id, 'search_country_id', '', 28, 'maxwidth150 maxwidthonsmartphone');
                print '</td>';
                $filterfound++;
            } else {
                print '<td class="liste_titre"></td>';
            }
        }
    }
    print '<td class="liste_titre"></td>';
    print '<td class="liste_titre"></td>';
    // Action column
    if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
        print '<td class="liste_titre center">';
        if ($filterfound) {
            $searchpicto = $form->showFilterAndCheckAddButtons(0);
            print $searchpicto;
        }
        print '</td>';
    }
    print '</tr>';

    // Title of lines
    print '<tr class="liste_titre">';
    // Action column
    if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
        print getTitleFieldOfList('');
    }
    foreach ($fieldlist as $field => $value) {
        // Determines the name of the field in relation to the possible names
        // in data dictionaries
        $showfield = 1; // By default
        $class = "left";
        $sortable = 1;
        $valuetoshow = '';

        $valuetoshow = ucfirst($fieldlist[$field]); // By default
        $valuetoshow = $langs->trans($valuetoshow); // try to translate
        if ($fieldlist[$field] == 'source') {
            $valuetoshow = $langs->trans("Contact");
        }
        if ($fieldlist[$field] == 'price') {
            $valuetoshow = $langs->trans("PriceUHT");
        }
        if ($fieldlist[$field] == 'taux') {
            if ($tabname[$id] != MAIN_DB_PREFIX . "c_revenuestamp") {
                $valuetoshow = $langs->trans("Rate");
            } else {
                $valuetoshow = $langs->trans("Amount");
            }
            $class = 'center';
        }
        if ($fieldlist[$field] == 'type') {
            $valuetoshow = $langs->trans("Type");
        }
        if ($fieldlist[$field] == 'code') {
            $valuetoshow = $langs->trans("Code");
        }
        if ($fieldlist[$field] == 'libelle' || $fieldlist[$field] == 'label') {
            $valuetoshow = $langs->trans("Label");
        }
        if ($fieldlist[$field] == 'country') {
            $valuetoshow = $langs->trans("Country");
        }
        if ($fieldlist[$field] == 'region_id' || $fieldlist[$field] == 'country_id') {
            $showfield = 0;
        }
        if ($fieldlist[$field] == 'accountancy_code') {
            $valuetoshow = $langs->trans("AccountancyCode");
        }
        if ($fieldlist[$field] == 'accountancy_code_sell') {
            $valuetoshow = $langs->trans("AccountancyCodeSell");
            $sortable = 0;
        }
        if ($fieldlist[$field] == 'accountancy_code_buy') {
            $valuetoshow = $langs->trans("AccountancyCodeBuy");
            $sortable = 0;
        }
        if ($fieldlist[$field] == 'fk_pcg_version') {
            $valuetoshow = $langs->trans("Pcg_version");
        }
        if ($fieldlist[$field] == 'account_parent') {
            $valuetoshow = $langs->trans("Accountsparent");
        }
        if ($fieldlist[$field] == 'pcg_type') {
            $valuetoshow = $langs->trans("Pcg_type");
        }
        if ($fieldlist[$field] == 'type_template') {
            $valuetoshow = $langs->trans("TypeOfTemplate");
        }
        if ($fieldlist[$field] == 'range_account') {
            $valuetoshow = $langs->trans("Comment");
        }
        if ($fieldlist[$field] == 'category_type') {
            $valuetoshow = $langs->trans("Calculated");
        }
        // Affiche nom du champ
        if ($showfield) {
            print getTitleFieldOfList($valuetoshow, 0, $_SERVER['PHP_SELF'], ($sortable ? $fieldlist[$field] : ''), ($page ? 'page=' . $page . '&' : ''), $param, "", $sortfield, $sortorder, $class . ' ');
        }
    }
    print getTitleFieldOfList($langs->trans("ListOfAccounts"), 0, $_SERVER['PHP_SELF'], "", ($page ? 'page=' . $page . '&' : ''), $param, '', $sortfield, $sortorder, '');
    print getTitleFieldOfList($langs->trans("Status"), 0, $_SERVER['PHP_SELF'], "active", ($page ? 'page=' . $page . '&' : ''), $param, '', $sortfield, $sortorder, 'center ');
    // Action column
    if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
        print getTitleFieldOfList('');
    }
    print '</tr>';


    if ($num) {
        $imaxinloop = ($listlimit ? min($num, $listlimit) : $num);

        // Lines with values
        while ($i < $imaxinloop) {
            $obj = $db->fetch_object($resql);

            //print_r($obj);
            print '<tr class="oddeven" id="rowid-' . $obj->rowid . '">';
            if ($action == 'edit' && ($rowid == (!empty($obj->rowid) ? $obj->rowid : $obj->code))) {
                $tmpaction = 'edit';
                $parameters = ['fieldlist' => $fieldlist, 'tabname' => $tabname[$id]];
                $reshook = $hookmanager->executeHooks('editDictionaryFieldlist', $parameters, $obj, $tmpaction); // Note that $action and $object may have been modified by some hooks
                $error = $hookmanager->error;
                $errors = $hookmanager->errors;

                // Actions
                if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                    print '<td></td>';
                }

                // Show fields
                if (empty($reshook)) {
                    fieldListAccountingCategories($fieldlist, $obj, $tabname[$id], 'edit');
                }

                print '<td></td>';
                print '<td class="center">';
                print '<div name="' . (!empty($obj->rowid) ? $obj->rowid : $obj->code) . '"></div>';
                print '<input type="hidden" name="page" value="' . $page . '">';
                print '<input type="hidden" name="rowid" value="' . $rowid . '">';
                print '<input type="submit" class="button button-edit smallpaddingimp" name="actionmodify" value="' . $langs->trans("Modify") . '">';
                print '<input type="submit" class="button button-cancel smallpaddingimp" name="actioncancel" value="' . $langs->trans("Cancel") . '">';
                print '</td>';
                // Actions
                if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                    print '<td></td>';
                }
            } else {
                // Can an entry be erased or disabled ?
                $iserasable = 1;
                $canbedisabled = 1;
                $canbemodified = 1; // true by default
                if (isset($obj->code)) {
                    if (($obj->code == '0' || $obj->code == '' || preg_match('/unknown/i', $obj->code))) {
                        $iserasable = 0;
                        $canbedisabled = 0;
                    }
                }
                $url = $_SERVER['PHP_SELF'] . '?' . ($page ? 'page=' . $page . '&' : '') . 'sortfield=' . $sortfield . '&sortorder=' . $sortorder . '&rowid=' . (!empty($obj->rowid) ? $obj->rowid : (!empty($obj->code) ? $obj->code : '')) . '&code=' . (!empty($obj->code) ? urlencode($obj->code) : '');
                if ($param) {
                    $url .= '&' . $param;
                }
                $url .= '&';

                $canbemodified = $iserasable;

                $tmpaction = 'view';
                $parameters = ['fieldlist' => $fieldlist, 'tabname' => $tabname[$id]];
                $reshook = $hookmanager->executeHooks('viewDictionaryFieldlist', $parameters, $obj, $tmpaction); // Note that $action and $object may have been modified by some hooks

                $error = $hookmanager->error;
                $errors = $hookmanager->errors;

                // Actions
                if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                    print '<td class="center">';
                    if ($canbemodified) {
                        print '<a class="reposition editfielda marginleftonly marginrightonly" href="' . $url . 'action=edit&token=' . newToken() . '">' . img_edit() . '</a>';
                    }
                    if ($iserasable) {
                        if ($user->admin) {
                            print '<a class="marginleftonly marginrightonly" href="' . $url . 'action=delete&token=' . newToken() . '">' . img_delete() . '</a>';
                        }
                    }
                    print '</td>';
                }

                if (empty($reshook)) {
                    foreach ($fieldlist as $field => $value) {
                        $showfield = 1;
                        $title = '';
                        $class = 'tddict';

                        $tmpvar = $fieldlist[$field];
                        $valuetoshow = $obj->$tmpvar;
                        if ($value == 'category_type') {
                            $valuetoshow = yn($valuetoshow);
                        } elseif ($valuetoshow == 'all') {
                            $valuetoshow = $langs->trans('All');
                        } elseif ($fieldlist[$field] == 'country') {
                            if (empty($obj->country_code)) {
                                $valuetoshow = '-';
                            } else {
                                $key = $langs->trans("Country" . strtoupper($obj->country_code));
                                $valuetoshow = ($key != "Country" . strtoupper($obj->country_code) ? $obj->country_code . " - " . $key : $obj->country);
                            }
                        } elseif (in_array($fieldlist[$field], ['label', 'formula'])) {
                            $class = "tdoverflowmax250";
                            $title = $valuetoshow;
                        } elseif (in_array($fieldlist[$field], ['range_account'])) {
                            $class = "tdoverflowmax250 small";
                            $title = $valuetoshow;
                        } elseif ($fieldlist[$field] == 'region_id' || $fieldlist[$field] == 'country_id') {
                            $showfield = 0;
                        }

                        // Show value for field
                        if ($showfield) {
                            print '<!-- ' . $fieldlist[$field] . ' --><td class="' . $class . '"' . ($title ? ' title="' . dol_escape_htmltag($title) . '"' : '') . '>' . dol_escape_htmltag($valuetoshow) . '</td>';
                        }
                    }
                }

                // Link to setup the group
                print '<td>';
                if (empty($obj->formula)) {
                    // Count number of accounts into group
                    $nbofaccountintogroup = 0;
                    $listofaccountintogroup = $accountingcategory->getCptsCat($obj->rowid);
                    $nbofaccountintogroup = count($listofaccountintogroup);

                    print '<a href="' . DOL_URL_ROOT . '/accountancy/admin/categories.php?action=display&save_lastsearch_values=1&account_category=' . $obj->rowid . '">';
                    print $langs->trans("NAccounts", $nbofaccountintogroup);
                    print '</a>';
                } else {
                    print '<span class="opacitymedium">' . $langs->trans("Formula") . '</span>';
                }
                print '</td>';

                // Active
                print '<td class="center" class="nowrap">';
                if ($canbedisabled) {
                    print '<a href="' . $url . 'action=' . $acts[$obj->active] . '">' . $actl[$obj->active] . '</a>';
                } else {
                    print $langs->trans("AlwaysActive");
                }
                print "</td>";

                // Actions
                if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                    print '<td class="center">';
                    if ($canbemodified) {
                        print '<a class="reposition editfielda paddingleft marginleftonly marginrightonly paddingright" href="' . $url . 'action=edit&token=' . newToken() . '">' . img_edit() . '</a>';
                    }
                    if ($iserasable) {
                        if ($user->admin) {
                            print '<a class="paddingleft marginleftonly marginrightonly paddingright" href="' . $url . 'action=delete&token=' . newToken() . '">' . img_delete() . '</a>';
                        }
                    }
                    print '</td>';
                }
            }
            print "</tr>\n";
            $i++;
        }
    } else {
        $colspan = 10;
        print '<tr><td colspan="' . $colspan . '"><span class="opacitymedium">' . $langs->trans("None") . '</td></tr>';
    }
} else {
    dol_print_error($db);
}

print '</table>';
print '</div>';

print '</form>';

print '<br>';

// End of page
llxFooter();