<?php

$form = new Form($db);

//$help_url='EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes';
$help_url = '';
$title = $langs->trans('Availabilities') . ' - ' . $langs->trans("Notes");
llxHeader('', $title, $help_url);

if ($id > 0 || !empty($ref)) {
    $object->fetch_thirdparty();

    $head = availabilitiesPrepareHead($object);

    print dol_get_fiche_head($head, 'note', $langs->trans("Availabilities"), -1, $object->picto);

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
     if (isModEnabled('project'))
     {
     $langs->load("projects");
     $morehtmlref.='<br>'.$langs->trans('Project') . ' ';
     if ($permissiontoadd)
     {
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


    $cssclass = "titlefield";
    include DOL_DOCUMENT_ROOT . '/core/tpl/notes.tpl.php';

    print '</div>';

    print dol_get_fiche_end();
}

// End of page
llxFooter();
