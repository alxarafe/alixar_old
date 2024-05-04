<?php

use DoliCore\Form\Form;
use DoliModules\Contact\Model\Contact;

$contactstatic = new Contact($db);

$form = new Form($db);

if ($object->id > 0) {
    $title = $langs->trans("Agenda");
    //if (getDolGlobalString('MAIN_HTML_TITLE') && preg_match('/thirdpartynameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->name." - ".$title;
    $help_url = 'EN:Module_Agenda_En|FR:Module_Agenda|ES:Módulo_Agenda|DE:Modul_Agenda';
    llxHeader('', $title, $help_url);

    if (isModEnabled('notification')) {
        $langs->load("mails");
    }
    $head = bomPrepareHead($object);


    print dol_get_fiche_head($head, 'agenda', $langs->trans("BillOfMaterials"), -1, 'bom');

    // Object card
    // ------------------------------------------------------------
    $linkback = '<a href="' . DOL_URL_ROOT . '/bom/bom_list.php?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

    $morehtmlref = '<div class="refidno">';
    /*
     // Ref customer
     $morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
     $morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
     // Thirdparty
     $morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1);
     // Project
     if (isModEnabled('project'))
     {
     $langs->load("projects");
     $morehtmlref.='<br>'.$langs->trans('Project') . ' ';
     if ($user->hasRight('bom', 'creer')) {
     if ($action != 'classify')
         //$morehtmlref.='<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=classify&token='.newToken().'&id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
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
         if (!empty($object->fk_project)) {
         $proj = new Project($db);
         $proj->fetch($object->fk_project);
         $morehtmlref.='<a href="'.DOL_URL_ROOT.'/projet/card.php?id=' . $object->fk_project . '" title="' . $langs->trans('ShowProject') . '">';
         $morehtmlref.=$proj->ref;
         $morehtmlref.='</a>';
         } else {
         $morehtmlref.='';
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

    $out = '&origin=' . $object->element . '&originid=' . $object->id;
    $permok = $user->hasRight('agenda', 'myactions', 'create');
    if ((!empty($objthirdparty->id) || !empty($objcon->id)) && $permok) {
        //$out.='<a href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create';
        if (get_class($objthirdparty) == 'Societe') {
            $out .= '&amp;socid=' . $objthirdparty->id;
        }
        $out .= (!empty($objcon->id) ? '&amp;contactid=' . $objcon->id : '') . '&amp;backtopage=1';
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


        //print load_fiche_titre($langs->trans("ActionsOnBom"), '', '');

        // List of all actions
        $filters = [];
        $filters['search_agenda_label'] = $search_agenda_label;
        $filters['search_rowid'] = $search_rowid;

        // TODO Replace this with same code than into list.php
        show_actions_done($conf, $langs, $db, $object, null, 0, $actioncode, '', $filters, $sortfield, $sortorder);
    }
}

// End of page
llxFooter();
