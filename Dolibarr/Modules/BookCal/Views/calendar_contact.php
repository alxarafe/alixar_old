<?php

$title = $langs->trans('Calendar') . " - " . $langs->trans('ContactsAddresses');
$help_url = '';
//$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $title, $help_url);

$form = new Form($db);
$formcompany = new FormCompany($db);
$contactstatic = new Contact($db);
$userstatic = new User($db);


/* *************************************************************************** */
/*                                                                             */
/* View and edit mode                                                         */
/*                                                                             */
/* *************************************************************************** */

if ($object->id) {
    /*
     * Show tabs
     */
    $head = calendarPrepareHead($object);

    print dol_get_fiche_head($head, 'contact', $langs->trans("Calendar"), -1, $object->picto);

    $linkback = '<a href="' . dol_buildpath('/bookcal/calendar_list.php', 1) . '?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

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
     if (!empty($object->fk_project)) {
     $proj = new Project($db);
     $proj->fetch($object->fk_project);
     $morehtmlref .= ': '.$proj->getNomUrl();
     } else {
     $morehtmlref .= '';
     }
     }
     }*/
    $morehtmlref .= '</div>';

    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '', 0, '', '', 1);

    print dol_get_fiche_end();

    print '<br>';

    // Contacts lines (modules that overwrite templates must declare this into descriptor)
    $dirtpls = array_merge($conf->modules_parts['tpl'], ['/core/tpl']);
    foreach ($dirtpls as $reldir) {
        $res = @include dol_buildpath($reldir . '/contacts.tpl.php');
        if ($res) {
            break;
        }
    }
}

// End of page
llxFooter();
