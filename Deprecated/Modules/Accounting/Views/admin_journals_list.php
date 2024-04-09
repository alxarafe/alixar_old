<?php

$form = new Form($db);
$formadmin = new FormAdmin($db);

$title = $langs->trans('AccountingJournals');
$help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';
llxHeader('', $title, $help_url);

$titre = $langs->trans("DictionarySetup");
$linkback = '';
if ($id) {
    $titre .= ' - ' . $langs->trans($tablib[$id]);
    $titlepicto = 'title_accountancy';
}

print load_fiche_titre($titre, $linkback, $titlepicto);


// Confirmation de la suppression de la ligne
if ($action == 'delete') {
    print $form->formconfirm($_SERVER['PHP_SELF'] . '?' . ($page ? 'page=' . $page . '&' : '') . 'sortfield=' . $sortfield . '&sortorder=' . $sortorder . '&rowid=' . $rowid . '&code=' . $code . '&id=' . $id, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_delete', '', 0, 1);
}

/*
 * Show a dictionary
 */
if ($id) {
    // Complete requete recherche valeurs avec critere de tri
    $sql = $tabsql[$id];
    $sql .= " WHERE a.entity = " . ((int) $conf->entity);

    // If sort order is "country", we use country_code instead
    if ($sortfield == 'country') {
        $sortfield = 'country_code';
    }
    $sql .= $db->order($sortfield, $sortorder);
    $sql .= $db->plimit($listlimit + 1, $offset);

    $fieldlist = explode(',', $tabfield[$id]);

    print '<form action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '" method="POST">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="from" value="' . dol_escape_htmltag(GETPOST('from', 'alpha')) . '">';

    print '<div class="div-table-responsive">';
    print '<table class="noborder centpercent">';

    // Form to add a new line
    if ($tabname[$id]) {
        $fieldlist = explode(',', $tabfield[$id]);

        // Line for title
        print '<tr class="liste_titre">';
        foreach ($fieldlist as $field => $value) {
            // Determine le nom du champ par rapport aux noms possibles
            // dans les dictionnaires de donnees
            $valuetoshow = ucfirst($fieldlist[$field]); // By default
            $valuetoshow = $langs->trans($valuetoshow); // try to translate
            $class = "left";
            if ($fieldlist[$field] == 'code') {
                $valuetoshow = $langs->trans("Code");
            }
            if ($fieldlist[$field] == 'libelle' || $fieldlist[$field] == 'label') {
                $valuetoshow = $langs->trans("Label");
            }
            if ($fieldlist[$field] == 'nature') {
                $valuetoshow = $langs->trans("NatureOfJournal");
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
        print '<td></td>';
        print '<td></td>';
        print '</tr>';

        // Line to enter new values
        print '<tr class="oddeven nodrag nodrap nohover">';

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
            $this->fieldListJournal($fieldlist, $obj, $tabname[$id], 'add');
        }

        print '<td colspan="4" class="right">';
        print '<input type="submit" class="button button-add" name="actionadd" value="' . $langs->trans("Add") . '">';
        print '</td>';
        print "</tr>";

        print '<tr><td colspan="7">&nbsp;</td></tr>'; // Keep &nbsp; to have a line with enough height
    }


    // List of available record in database
    dol_syslog("htdocs/admin/dict", LOG_DEBUG);
    $resql = $db->query($sql);
    if ($resql) {
        $num = $db->num_rows($resql);
        $i = 0;

        $param = '&id=' . ((int) $id);
        if ($search_country_id > 0) {
            $param .= '&search_country_id=' . urlencode((string) ($search_country_id));
        }
        $paramwithsearch = $param;
        if ($sortorder) {
            $paramwithsearch .= '&sortorder=' . $sortorder;
        }
        if ($sortfield) {
            $paramwithsearch .= '&sortfield=' . $sortfield;
        }
        if (GETPOST('from', 'alpha')) {
            $paramwithsearch .= '&from=' . GETPOST('from', 'alpha');
        }

        // There is several pages
        if ($num > $listlimit) {
            print '<tr class="none"><td class="right" colspan="' . (3 + count($fieldlist)) . '">';
            print_fleche_navigation($page, $_SERVER['PHP_SELF'], $paramwithsearch, ($num > $listlimit), '<li class="pagination"><span>' . $langs->trans("Page") . ' ' . ($page + 1) . '</span></li>');
            print '</td></tr>';
        }

        // Title line with search boxes
        /*print '<tr class="liste_titre_filter liste_titre_add">';
        print '<td class="liste_titre"></td>';
        print '<td class="liste_titre"></td>';
        print '<td class="liste_titre"></td>';
        print '<td class="liste_titre"></td>';
        print '<td class="liste_titre"></td>';
        print '<td class="liste_titre"></td>';
        print '<td class="liste_titre center">';
        $searchpicto=$form->showFilterButtons();
        print $searchpicto;
        print '</td>';
        print '</tr>';
        */

        // Title of lines
        print '<tr class="liste_titre liste_titre_add">';
        foreach ($fieldlist as $field => $value) {
            // Determine le nom du champ par rapport aux noms possibles
            // dans les dictionnaires de donnees
            $showfield = 1; // By default
            $class = "left";
            $sortable = 1;
            $valuetoshow = '';
            /*
            $tmparray=getLabelOfField($fieldlist[$field]);
            $showfield=$tmp['showfield'];
            $valuetoshow=$tmp['valuetoshow'];
            $align=$tmp['align'];
            $sortable=$tmp['sortable'];
            */
            $valuetoshow = ucfirst($fieldlist[$field]); // By default
            $valuetoshow = $langs->trans($valuetoshow); // try to translate
            if ($fieldlist[$field] == 'code') {
                $valuetoshow = $langs->trans("Code");
            }
            if ($fieldlist[$field] == 'libelle' || $fieldlist[$field] == 'label') {
                $valuetoshow = $langs->trans("Label");
            }
            if ($fieldlist[$field] == 'nature') {
                $valuetoshow = $langs->trans("NatureOfJournal");
            }

            // Affiche nom du champ
            if ($showfield) {
                print getTitleFieldOfList($valuetoshow, 0, $_SERVER['PHP_SELF'], ($sortable ? $fieldlist[$field] : ''), ($page ? 'page=' . $page . '&' : ''), $param, "", $sortfield, $sortorder, $class . ' ');
            }
        }
        print getTitleFieldOfList($langs->trans("Status"), 0, $_SERVER['PHP_SELF'], "active", ($page ? 'page=' . $page . '&' : ''), $param, '', $sortfield, $sortorder, 'center ');
        print getTitleFieldOfList('');
        print getTitleFieldOfList('');
        print getTitleFieldOfList('');
        print '</tr>';

        if ($num) {
            // Lines with values
            while ($i < $num) {
                $obj = $db->fetch_object($resql);
                //print_r($obj);
                print '<tr class="oddeven" id="rowid-' . $obj->rowid . '">';
                if ($action == 'edit' && ($rowid == (!empty($obj->rowid) ? $obj->rowid : $obj->code))) {
                    $tmpaction = 'edit';
                    $parameters = ['fieldlist' => $fieldlist, 'tabname' => $tabname[$id]];
                    $reshook = $hookmanager->executeHooks('editDictionaryFieldlist', $parameters, $obj, $tmpaction); // Note that $action and $object may have been modified by some hooks
                    $error = $hookmanager->error;
                    $errors = $hookmanager->errors;

                    // Show fields
                    if (empty($reshook)) {
                        $this->fieldListJournal($fieldlist, $obj, $tabname[$id], 'edit');
                    }

                    print '<td class="center" colspan="4">';
                    print '<input type="hidden" name="page" value="' . $page . '">';
                    print '<input type="hidden" name="rowid" value="' . $rowid . '">';
                    print '<input type="submit" class="button button-edit" name="actionmodify" value="' . $langs->trans("Modify") . '">';
                    print '<input type="submit" class="button button-cancel" name="actioncancel" value="' . $langs->trans("Cancel") . '">';
                    print '<div name="' . (!empty($obj->rowid) ? $obj->rowid : $obj->code) . '"></div>';
                    print '</td>';
                } else {
                    $tmpaction = 'view';
                    $parameters = ['fieldlist' => $fieldlist, 'tabname' => $tabname[$id]];
                    $reshook = $hookmanager->executeHooks('viewDictionaryFieldlist', $parameters, $obj, $tmpaction); // Note that $action and $object may have been modified by some hooks

                    $error = $hookmanager->error;
                    $errors = $hookmanager->errors;

                    if (empty($reshook)) {
                        $langs->load("accountancy");
                        foreach ($fieldlist as $field => $value) {
                            $showfield = 1;
                            $class = "left";
                            $tmpvar = $fieldlist[$field];
                            $valuetoshow = $obj->$tmpvar;
                            if ($valuetoshow == 'all') {
                                $valuetoshow = $langs->trans('All');
                            } elseif ($fieldlist[$field] == 'nature' && $tabname[$id] == MAIN_DB_PREFIX . 'accounting_journal') {
                                $key = $langs->trans("AccountingJournalType" . strtoupper($obj->nature));
                                $valuetoshow = ($obj->nature && $key != "AccountingJournalType" . strtoupper($langs->trans($obj->nature)) ? $key : $obj->{$fieldlist[$field]});
                            } elseif ($fieldlist[$field] == 'label' && $tabname[$id] == MAIN_DB_PREFIX . 'accounting_journal') {
                                $valuetoshow = $langs->trans($obj->label);
                            }

                            $class = 'tddict';
                            // Show value for field
                            if ($showfield) {
                                print '<!-- ' . $fieldlist[$field] . ' --><td class="' . $class . '">' . dol_escape_htmltag($valuetoshow) . '</td>';
                            }
                        }
                    }

                    // Can an entry be erased or disabled ?
                    $iserasable = 1;
                    $canbedisabled = 1;
                    $canbemodified = 1; // true by default
                    if (isset($obj->code) && $id != 10) {
                        if (($obj->code == '0' || $obj->code == '' || preg_match('/unknown/i', $obj->code))) {
                            $iserasable = 0;
                            $canbedisabled = 0;
                        }
                    }

                    $canbemodified = $iserasable;

                    $url = $_SERVER['PHP_SELF'] . '?' . ($page ? 'page=' . $page . '&' : '') . 'sortfield=' . $sortfield . '&sortorder=' . $sortorder . '&rowid=' . (!empty($obj->rowid) ? $obj->rowid : (!empty($obj->code) ? $obj->code : '')) . '&code=' . (!empty($obj->code) ? urlencode($obj->code) : '');
                    if ($param) {
                        $url .= '&' . $param;
                    }
                    $url .= '&';

                    // Active
                    print '<td class="nowrap center">';
                    if ($canbedisabled) {
                        print '<a href="' . $url . 'action=' . $acts[$obj->active] . '&token=' . newToken() . '">' . $actl[$obj->active] . '</a>';
                    } else {
                        print $langs->trans("AlwaysActive");
                    }
                    print "</td>";

                    // Modify link
                    if ($canbemodified) {
                        print '<td class="center"><a class="reposition editfielda" href="' . $url . 'action=edit&token=' . newToken() . '">' . img_edit() . '</a></td>';
                    } else {
                        print '<td>&nbsp;</td>';
                    }

                    // Delete link
                    if ($iserasable) {
                        print '<td class="center">';
                        if ($user->admin) {
                            print '<a href="' . $url . 'action=delete&token=' . newToken() . '">' . img_delete() . '</a>';
                        }
                        //else print '<a href="#">'.img_delete().'</a>';    // Some dictionary can be edited by other profile than admin
                        print '</td>';
                    } else {
                        print '<td>&nbsp;</td>';
                    }

                    print '<td></td>';

                    print '</td>';
                }

                print "</tr>\n";
                $i++;
            }
        }
    } else {
        dol_print_error($db);
    }

    print '</table>';
    print '</div>';

    print '</form>';
}

print '<br>';

// End of page
llxFooter();
