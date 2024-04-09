<?php

$form = new Form($db);

if ($object->id > 0) {
    $title = $langs->trans("Agenda");
    //if (getDolGlobalString('MAIN_HTML_TITLE') && preg_match('/thirdpartynameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->name." - ".$title;
    $help_url = 'EN:Module_Agenda_En|DE:Modul_Terminplanung';
    llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-asset page-card_agenda');

    if (isModEnabled('notification')) {
        $langs->load("mails");
    }
    $head = assetPrepareHead($object);


    print dol_get_fiche_head($head, 'agenda', $langs->trans("Asset"), -1, $object->picto);

    // Object card
    // ------------------------------------------------------------
    $linkback = '<a href="' . DOL_URL_ROOT . '/asset/list.php?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

    $morehtmlref = '<div class="refidno">';
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

    if (isModEnabled('agenda') && ($user->hasRight('agenda', 'myactions', 'read') || $user->hasRight('agenda', 'allactions', 'read'))) {
        $param = '&id=' . $object->id . '&socid=' . $socid;
        if (!empty($contextpage) && $contextpage != $_SERVER['PHP_SELF']) {
            $param .= '&contextpage=' . urlencode($contextpage);
        }
        if ($limit > 0 && $limit != $conf->liste_limit) {
            $param .= '&limit=' . ((int) $limit);
        }


        print load_fiche_titre($langs->trans("ActionsOnAsset"), '', '');

        // List of all actions
        $filters = [];
        $filters['search_agenda_label'] = $search_agenda_label;
        $filters['search_rowid'] = $search_rowid;

        // TODO Replace this with same code than into list.php
        show_actions_done($conf, $langs, $db, $object, null, 0, $actioncode, '', $filters, $sortfield, $sortorder, $object->module);
    }
}

// End of page
llxFooter();
