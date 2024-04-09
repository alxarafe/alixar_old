<?php

$form = new Form($db);
$formadmin = new FormAdmin($db);

$help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';

llxHeader('', $langs->trans("Pcg_version"), $help_url);

$titre = $langs->trans($tablib[$id]);
$linkback = '';

print load_fiche_titre($titre, $linkback, 'title_accountancy');


// Confirmation de la suppression de la ligne
if ($action == 'delete') {
    print $form->formconfirm($_SERVER['PHP_SELF'] . '?' . ($page ? 'page=' . urlencode((string) ($page)) . '&' : '') . 'sortfield=' . urlencode((string) ($sortfield)) . '&sortorder=' . urlencode((string) ($sortorder)) . '&rowid=' . urlencode((string) ($rowid)) . '&code=' . urlencode((string) ($code)) . '&id=' . urlencode((string) ($id)), $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_delete', '', 0, 1);
}
//var_dump($elementList);

/*
 * Show a dictionary
 */
if ($id) {
    // Complete requete recherche valeurs avec critere de tri
    $sql = $tabsql[$id];

    if ($search_country_id > 0) {
        if (preg_match('/ WHERE /', $sql)) {
            $sql .= " AND ";
        } else {
            $sql .= " WHERE ";
        }
        $sql .= " c.rowid = " . ((int) $search_country_id);
    }

    // If sort order is "country", we use country_code instead
    if ($sortfield == 'country') {
        $sortfield = 'country_code';
    }
    $sql .= $db->order($sortfield, $sortorder);
    $sql .= $db->plimit($listlimit + 1, $offset);
    //print $sql;

    $fieldlist = explode(',', $tabfield[$id]);

    print '<form action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '" method="POST">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';

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
            if ($fieldlist[$field] == 'label') {
                $valuetoshow = $langs->trans("Label");
                $class = 'minwidth300';
            }
            if ($fieldlist[$field] == 'country') {
                if (in_array('region_id', $fieldlist)) {
                    print '<td>&nbsp;</td>';
                    continue;
                }       // For region page, we do not show the country input
                $valuetoshow = $langs->trans("Country");
            }
            if ($fieldlist[$field] == 'country_id') {
                $valuetoshow = '';
            }
            if ($fieldlist[$field] == 'pcg_version' || $fieldlist[$field] == 'fk_pcg_version') {
                $valuetoshow = $langs->trans("Pcg_version");
            }
            //var_dump($value);

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
        print '</tr>';

        // Line to enter new values
        print '<tr class="oddeven">';

        $obj = new stdClass();
        // If data was already input, we define them in obj to populate input fields.
        if (GETPOST('actionadd', 'alpha')) {
            foreach ($fieldlist as $key => $val) {
                if (GETPOST($val)) {
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
            $this->fieldListAccountModel($fieldlist, $obj, $tabname[$id], 'add');
        }

        print '<td colspan="3" class="right">';
        print '<input type="submit" class="button button-add" name="actionadd" value="' . $langs->trans("Add") . '">';
        print '</td>';
        print "</tr>";

        $colspan = count($fieldlist) + 3;

        print '<tr><td colspan="' . $colspan . '">&nbsp;</td></tr>'; // Keep &nbsp; to have a line with enough height
    }


    // List of available values in database
    dol_syslog("htdocs/admin/dict", LOG_DEBUG);
    $resql = $db->query($sql);
    if ($resql) {
        $num = $db->num_rows($resql);
        $i = 0;

        $param = '&id=' . urlencode((string) ($id));
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

        // There is several pages
        if ($num > $listlimit) {
            print '<tr class="none"><td class="right" colspan="' . (3 + count($fieldlist)) . '">';
            print_fleche_navigation($page, $_SERVER['PHP_SELF'], $paramwithsearch, ($num > $listlimit), '<li class="pagination"><span>' . $langs->trans("Page") . ' ' . ($page + 1) . '</span></li>');
            print '</td></tr>';
        }

        // Title line with search boxes
        print '<tr class="liste_titre liste_titre_add">';
        foreach ($fieldlist as $field => $value) {
            $showfield = 1; // By default

            if ($fieldlist[$field] == 'region_id' || $fieldlist[$field] == 'country_id') {
                $showfield = 0;
            }

            if ($showfield) {
                if ($value == 'country') {
                    print '<td class="liste_titre">';
                    print $form->select_country($search_country_id, 'search_country_id', '', 28, 'maxwidth200 maxwidthonsmartphone');
                    print '</td>';
                } else {
                    print '<td class="liste_titre"></td>';
                }
            }
        }
        print '<td class="liste_titre"></td>';
        print '<td class="liste_titre right" colspan="2">';
        $searchpicto = $form->showFilterAndCheckAddButtons(0);
        print $searchpicto;
        print '</td>';
        print '</tr>';

        // Title of lines
        print '<tr class="liste_titre">';
        print getTitleFieldOfList($langs->trans("Pcg_version"), 0, $_SERVER['PHP_SELF'], "pcg_version", ($page ? 'page=' . $page . '&' : ''), $param, '', $sortfield, $sortorder, '');
        print getTitleFieldOfList($langs->trans("Label"), 0, $_SERVER['PHP_SELF'], "label", ($page ? 'page=' . $page . '&' : ''), $param, '', $sortfield, $sortorder, '');
        print getTitleFieldOfList($langs->trans("Country"), 0, $_SERVER['PHP_SELF'], "country_code", ($page ? 'page=' . $page . '&' : ''), $param, '', $sortfield, $sortorder, '');
        print getTitleFieldOfList($langs->trans("Status"), 0, $_SERVER['PHP_SELF'], "active", ($page ? 'page=' . $page . '&' : ''), $param, '', $sortfield, $sortorder, 'center ');
        print getTitleFieldOfList('');
        print getTitleFieldOfList('');
        print '</tr>';

        if ($num) {
            $i = 0;
            // Lines with values
            while ($i < $num) {
                $obj = $db->fetch_object($resql);
                //print_r($obj);

                print '<tr class="oddeven" id="rowid-' . $obj->rowid . '">';
                if ($action == 'edit' && ($rowid == (!empty($obj->rowid) ? $obj->rowid : $obj->code))) {
                    print '<form action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '" method="POST">';
                    print '<input type="hidden" name="token" value="' . newToken() . '">';
                    print '<input type="hidden" name="page" value="' . $page . '">';
                    print '<input type="hidden" name="rowid" value="' . $rowid . '">';

                    $tmpaction = 'edit';
                    $parameters = ['fieldlist' => $fieldlist, 'tabname' => $tabname[$id]];
                    $reshook = $hookmanager->executeHooks('editDictionaryFieldlist', $parameters, $obj, $tmpaction); // Note that $action and $object may have been modified by some hooks
                    $error = $hookmanager->error;
                    $errors = $hookmanager->errors;

                    if (empty($reshook)) {
                        $this->fieldListAccountModel($fieldlist, $obj, $tabname[$id], 'edit');
                    }

                    print '<td colspan="3" class="right">';
                    print '<a name="' . (!empty($obj->rowid) ? $obj->rowid : $obj->code) . '">&nbsp;</a><input type="submit" class="button button-edit" name="actionmodify" value="' . $langs->trans("Modify") . '">';
                    print '&nbsp;<input type="submit" class="button button-cancel" name="actioncancel" value="' . $langs->trans("Cancel") . '">';
                    print '</td>';
                } else {
                    $tmpaction = 'view';
                    $parameters = ['fieldlist' => $fieldlist, 'tabname' => $tabname[$id]];
                    $reshook = $hookmanager->executeHooks('viewDictionaryFieldlist', $parameters, $obj, $tmpaction); // Note that $action and $object may have been modified by some hooks

                    $error = $hookmanager->error;
                    $errors = $hookmanager->errors;

                    if (empty($reshook)) {
                        foreach ($fieldlist as $field => $value) {
                            $showfield = 1;
                            $class = "left";
                            $tmpvar = $fieldlist[$field];
                            $valuetoshow = $obj->$tmpvar;
                            if ($value == 'type_template') {
                                $valuetoshow = isset($elementList[$valuetoshow]) ? $elementList[$valuetoshow] : $valuetoshow;
                            }
                            if ($value == 'element') {
                                $valuetoshow = isset($elementList[$valuetoshow]) ? $elementList[$valuetoshow] : $valuetoshow;
                            } elseif ($value == 'source') {
                                $valuetoshow = isset($sourceList[$valuetoshow]) ? $sourceList[$valuetoshow] : $valuetoshow;
                            } elseif ($valuetoshow == 'all') {
                                $valuetoshow = $langs->trans('All');
                            } elseif ($fieldlist[$field] == 'country') {
                                if (empty($obj->country_code)) {
                                    $valuetoshow = '-';
                                } else {
                                    $key = $langs->trans("Country" . strtoupper($obj->country_code));
                                    $valuetoshow = ($key != "Country" . strtoupper($obj->country_code) ? $obj->country_code . " - " . $key : $obj->country);
                                }
                            } elseif ($fieldlist[$field] == 'country_id') {
                                $showfield = 0;
                            }

                            $class = 'tddict';
                            if ($fieldlist[$field] == 'tracking') {
                                $class .= ' tdoverflowauto';
                            }
                            // Show value for field
                            if ($showfield) {
                                print '<!-- ' . $fieldlist[$field] . ' --><td class="' . $class . '">' . $valuetoshow . '</td>';
                            }
                        }
                    }

                    // Can an entry be erased or disabled ?
                    $iserasable = 1;
                    $canbedisabled = 1;
                    $canbemodified = 1; // true by default

                    $url = $_SERVER['PHP_SELF'] . '?token=' . newToken() . ($page ? '&page=' . $page : '') . '&sortfield=' . $sortfield . '&sortorder=' . $sortorder . '&rowid=' . (!empty($obj->rowid) ? $obj->rowid : (!empty($obj->code) ? $obj->code : '')) . '&code=' . (!empty($obj->code) ? urlencode($obj->code) : '');
                    if ($param) {
                        $url .= '&' . $param;
                    }
                    $url .= '&';

                    // Active
                    print '<td class="center nowrap">';
                    if ($canbedisabled) {
                        print '<a href="' . $url . 'action=' . $acts[$obj->active] . '">' . $actl[$obj->active] . '</a>';
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
                        print '<td class="center"><a href="' . $url . 'action=delete&token=' . newToken() . '">' . img_delete() . '</a></td>';
                    } else {
                        print '<td>&nbsp;</td>';
                    }

                    print "</tr>\n";
                }

                $i++;
            }
        } else {
            print '<tr><td colspan="6"><span class="opacitymedium">' . $langs->trans("NoRecordFound") . '</span></td></tr>';
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
