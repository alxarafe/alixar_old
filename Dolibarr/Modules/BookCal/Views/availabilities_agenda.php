<?php

$form = new Form($db);

if ($object->id > 0) {
    $title = $langs->trans("Agenda");
    //if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->name." - ".$title;
    $help_url = 'EN:Module_Agenda_En|DE:Modul_Terminplanung';
    llxHeader('', $title, $help_url);

    if (!empty($conf->notification->enabled)) {
        $langs->load("mails");
    }
    $head = availabilitiesPrepareHead($object);


    print dol_get_fiche_head($head, 'agenda', $langs->trans("Availabilities"), -1, $object->picto);

    // Object card
    // ------------------------------------------------------------
    $linkback = '<a href="' . dol_buildpath('/bookcal/availabilities_list.php', 1) . '?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

    $morehtmlref = '<div class="refidno">';
    /*
    // Ref customer
    $morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
    $morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
    // Thirdparty
    $morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . (is_object($object->thirdparty) ? $object->thirdparty->getNomUrl(1) : '');
    // Project
    if (isModEnabled('project')) {
        $langs->load("projects");
        $morehtmlref.='<br>'.$langs->trans('Project') . ' ';
        if ($permissiontoadd) {
            if ($action != 'classify') {
                //$morehtmlref.='<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=classify&token='.newToken().'&id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
            }
            $morehtmlref.=' : ';
            if ($action == 'classify') {
                //$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
                $morehtmlref.='<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
                $morehtmlref.='<input type="hidden" name="action" value="classin">';
                $morehtmlref.='<input type="hidden" name="token" value="'.newToken().'">';
                $morehtmlref.=$formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
                $morehtmlref.='<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
                $morehtmlref.='</form>';
            } else {
                $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
            }
        } else {
            if (! empty($object->fk_project)) {
                $proj = new Project($db);
                $proj->fetch($object->fk_project);
                $morehtmlref .= ': '.$proj->getNomUrl();
            } else {
                $morehtmlref .= '';
            }
        }
    }*/
    $morehtmlref .= '</div>';


    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

    print '<div class="fichecenter">';
    print '<div class="underbanner clearboth"></div>';

    $object->info($object->id);
    dol_print_object_info($object, 1);

    print '</div>';

    print dol_get_fiche_end();


    // Actions buttons

    $objthirdparty = $object;
    $objcon = new stdClass();

    $out = '&origin=' . urlencode((string) ($object->element . '@' . $object->module)) . '&originid=' . urlencode((string) ($object->id));
    $urlbacktopage = $_SERVER['PHP_SELF'] . '?id=' . $object->id;
    $out .= '&backtopage=' . urlencode($urlbacktopage);
    $permok = $user->hasRight('agenda', 'myactions', 'create');
    if ((!empty($objthirdparty->id) || !empty($objcon->id)) && $permok) {
        //$out.='<a href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create';
        if (get_class($objthirdparty) == 'Societe') {
            $out .= '&socid=' . urlencode((string) ($objthirdparty->id));
        }
        $out .= (!empty($objcon->id) ? '&contactid=' . urlencode($objcon->id) : '');
        //$out.=$langs->trans("AddAnAction").' ';
        //$out.=img_picto($langs->trans("AddAnAction"),'filenew');
        //$out.="</a>";
    }


    print '<div class="tabsAction">';

    if (isModEnabled('agenda')) {
        if ($user->hasRight('agenda', 'myactions', 'create') || $user->hasRight('agenda', 'allactions', 'create')) {
            print '<a class="butAction" href="' . DOL_URL_ROOT . '/comm/action/card.php?action=create' . $out . '">' . $langs->trans("AddAction") . '</a>';
        } else {
            print '<a class="butActionRefused classfortooltip" href="#">' . $langs->trans("AddAction") . '</a>';
        }
    }

    print '</div>';

    /*
    if (isModEnabled('agenda') && ($user->hasRight('agenda', 'myactions', 'read') || $user->hasRight('agenda', 'allactions', 'read'))) {
        $param = '&id='.$object->id.(!empty($socid) ? '&socid='.$socid : '');
        if (!empty($contextpage) && $contextpage != $_SERVER['PHP_SELF']) {
            $param .= '&contextpage='.urlencode($contextpage);
        }
        if ($limit > 0 && $limit != $conf->liste_limit) {
            $param .= '&limit='.((int) $limit);
        }

        // Try to know count of actioncomm from cache
        $nbEvent = 0;
        //require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';
        //$cachekey = 'count_events_myobject_'.$object->id;
        //$nbEvent = dol_getcache($cachekey);
        $titlelist = $langs->trans("Actions").(is_numeric($nbEvent) ? '<span class="opacitymedium colorblack paddingleft">('.$nbEvent.')</span>': '');

        print_barre_liste($titlelist, 0, $_SERVER['PHP_SELF'], '', $sortfield, $sortorder, '', 0, -1, '', 0, $morehtmlright, '', 0, 1, 0);

        // List of all actions
        $filters = array();
        $filters['search_agenda_label'] = $search_agenda_label;
        $filters['search_rowid'] = $search_rowid;

        // TODO Replace this with same code than into list.php
        show_actions_done($conf, $langs, $db, $object, null, 0, $actioncode, '', $filters, $sortfield, $sortorder, $object->module);
    }
    */
}

// End of page
llxFooter();
