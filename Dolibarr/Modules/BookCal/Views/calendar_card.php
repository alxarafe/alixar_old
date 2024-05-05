<?php

use DoliCore\Form\Form;
use DoliCore\Form\FormFile;
use DoliCore\Form\FormProjets;

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title = $langs->trans("Calendar");
$help_url = '';
llxHeader('', $title, $help_url);

// Example : Adding jquery code
// print '<script type="text/javascript">
// jQuery(document).ready(function() {
//  function init_myfunc()
//  {
//      jQuery("#myid").removeAttr(\'disabled\');
//      jQuery("#myid").attr(\'disabled\',\'disabled\');
//  }
//  init_myfunc();
//  jQuery("#mybutton").click(function() {
//      init_myfunc();
//  });
// });
// </script>';


// Part to create
if ($action == 'create') {
    if (empty($permissiontoadd)) {
        accessforbidden('NotEnoughPermissions', 0, 1);
    }

    print load_fiche_titre($langs->trans("NewObject", $langs->transnoentitiesnoconv("Calendar")), '', 'object_' . $object->picto);

    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="add">';
    if ($backtopage) {
        print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
    }
    if ($backtopageforcancel) {
        print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';
    }
    if ($backtopagejsfields) {
        print '<input type="hidden" name="backtopagejsfields" value="' . $backtopagejsfields . '">';
    }
    if ($dol_openinpopup) {
        print '<input type="hidden" name="dol_openinpopup" value="' . $dol_openinpopup . '">';
    }

    print dol_get_fiche_head([], '');

    // Set some default values
    //if (! GETPOSTISSET('fieldname')) $_POST['fieldname'] = 'myvalue';

    print '<table class="border centpercent tableforfieldcreate">' . "\n";

    // Common attributes
    include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_add.tpl.php';

    // Other attributes
    include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';

    print '</table>' . "\n";

    print dol_get_fiche_end();

    print $form->buttonsSaveCancel("Create");

    print '</form>';

    //dol_set_focus('input[name="ref"]');
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
    print load_fiche_titre($langs->trans("Calendar"), '', 'object_' . $object->picto);

    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="update">';
    print '<input type="hidden" name="id" value="' . $object->id . '">';
    if ($backtopage) {
        print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
    }
    if ($backtopageforcancel) {
        print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';
    }

    print dol_get_fiche_head();

    print '<table class="border centpercent tableforfieldedit">' . "\n";

    // Common attributes
    include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_edit.tpl.php';

    // Other attributes
    include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_edit.tpl.php';

    print '</table>';

    print dol_get_fiche_end();

    print $form->buttonsSaveCancel();

    print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
    $head = calendarPrepareHead($object);

    print dol_get_fiche_head($head, 'card', $langs->trans("Calendar"), -1, $object->picto, 0, '', '', 0, '', 1);

    $formconfirm = '';

    // Confirmation to delete
    if ($action == 'delete') {
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('DeleteCalendar'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
    }
    // Confirmation to delete line
    if ($action == 'deleteline') {
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id . '&lineid=' . $lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
    }

    // Clone confirmation
    if ($action == 'clone') {
        // Create an array for form
        $formquestion = [];
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneAsk', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
    }

    // Call Hook formConfirm
    $parameters = ['formConfirm' => $formconfirm, 'lineid' => $lineid];
    $reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
    if (empty($reshook)) {
        $formconfirm .= $hookmanager->resPrint;
    } elseif ($reshook > 0) {
        $formconfirm = $hookmanager->resPrint;
    }

    // Print form confirm
    print $formconfirm;


    // Object card
    // ------------------------------------------------------------
    $linkback = '<a href="' . dol_buildpath('/bookcal/calendar_list.php', 1) . '?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

    $morehtmlref = '<div class="refidno">';
    /*
        // Ref customer
        $morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, $usercancreate, 'string', '', 0, 1);
        $morehtmlref .= $form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, $usercancreate, 'string'.(isset($conf->global->THIRDPARTY_REF_INPUT_SIZE) ? ':'.$conf->global->THIRDPARTY_REF_INPUT_SIZE : ''), '', null, null, '', 1);
        // Thirdparty
        $morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1, 'customer');
        if (empty($conf->global->MAIN_DISABLE_OTHER_LINK) && $object->thirdparty->id > 0) {
            $morehtmlref .= ' (<a href="'.DOL_URL_ROOT.'/commande/list.php?socid='.$object->thirdparty->id.'&search_societe='.urlencode($object->thirdparty->name).'">'.$langs->trans("OtherOrders").'</a>)';
        }
        // Project
        if (isModEnabled('project')) {
            $langs->load("projects");
            $morehtmlref .= '<br>';
            if ($permissiontoadd) {
                $morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
                if ($action != 'classify') {
                    $morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
                }
                $morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
            } else {
                if (!empty($object->fk_project)) {
                    $proj = new Project($db);
                    $proj->fetch($object->fk_project);
                    $morehtmlref .= $proj->getNomUrl(1);
                    if ($proj->title) {
                        $morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
                    }
                }
            }
        }
    */
    $morehtmlref .= '</div>';


    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


    print '<div class="fichecenter">';
    print '<div class="fichehalfleft">';
    print '<div class="underbanner clearboth"></div>';
    print '<table class="border centpercent tableforfield">' . "\n";

    // Common attributes
    //$keyforbreak='fieldkeytoswitchonsecondcolumn';    // We change column just before this field
    //unset($object->fields['fk_project']);             // Hide field already shown in banner
    //unset($object->fields['fk_soc']);                 // Hide field already shown in banner
    include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_view.tpl.php';

    // Other attributes. Fields from hook formObjectOptions and Extrafields.
    include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

    // Link to public page
    print '<tr><td>Link</td>';
    print '<td><a href="' . DOL_URL_ROOT . '/public/bookcal/index.php?id=' . $object->id . '" target="_blank">Public page</a>';
    print '</td></tr>';

    print '</table>';
    print '</div>';
    print '</div>';

    print '<div class="clearboth"></div>';

    print dol_get_fiche_end();


    /*
     * Lines
     */

    if (!empty($object->table_element_line)) {
        // Show object lines
        $result = $object->getLinesArray();

        print '	<form name="addproduct" id="addproduct" action="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . (($action != 'editline') ? '' : '#line_' . GETPOSTINT('lineid')) . '" method="POST">
		<input type="hidden" name="token" value="' . newToken() . '">
		<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline') . '">
		<input type="hidden" name="mode" value="">
		<input type="hidden" name="page_y" value="">
		<input type="hidden" name="id" value="' . $object->id . '">
		';

        if (!empty($conf->use_javascript_ajax) && $object->status == 0) {
            include DOL_DOCUMENT_ROOT . '/core/tpl/ajaxrow.tpl.php';
        }

        print '<div class="div-table-responsive-no-min">';
        if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
            print '<table id="tablelines" class="noborder noshadow" width="100%">';
        }

        if (!empty($object->lines)) {
            $object->printObjectLines($action, $mysoc, null, GETPOSTINT('lineid'), 1);
        }

        // Form to add new line
        if ($object->status == 0 && $permissiontoadd && $action != 'selectlines') {
            if ($action != 'editline') {
                // Add products/services form

                $parameters = [];
                $reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
                if ($reshook < 0) {
                    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
                }
                if (empty($reshook)) {
                    $object->formAddObjectLine(1, $mysoc, $soc);
                }
            }
        }

        if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
            print '</table>';
        }
        print '</div>';

        print "</form>\n";
    }


    // Buttons for actions

    if ($action != 'presend' && $action != 'editline') {
        print '<div class="tabsAction">' . "\n";
        $parameters = [];
        $reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

        if (empty($reshook)) {
            // Send
            /*if (empty($user->socid)) {
                print dolGetButtonAction('', $langs->trans('SendMail'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=presend&token='.newToken().'&mode=init#formmailbeforetitle');
            }*/

            // Back to draft
            if ($object->status == $object::STATUS_VALIDATED) {
                print dolGetButtonAction('', $langs->trans('SetToDraft'), 'default', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=confirm_setdraft&confirm=yes&token=' . newToken(), '', $permissiontoadd);
            }

            print dolGetButtonAction('', $langs->trans('Modify'), 'default', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=edit&token=' . newToken(), '', $permissiontoadd);

            // Validate
            if ($object->status == $object::STATUS_DRAFT) {
                if (empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0)) {
                    print dolGetButtonAction('', $langs->trans('Validate'), 'default', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=confirm_validate&confirm=yes&token=' . newToken(), '', $permissiontoadd);
                } else {
                    $langs->load("errors");
                    print dolGetButtonAction($langs->trans("ErrorAddAtLeastOneLineFirst"), $langs->trans("Validate"), 'default', '#', '', 0);
                }
            }

            // Clone
            if ($permissiontoadd) {
                print dolGetButtonAction('', $langs->trans('ToClone'), 'default', $_SERVER['PHP_SELF'] . '?id=' . $object->id . (!empty($object->socid) ? '&socid=' . $object->socid : '') . '&action=clone&token=' . newToken(), '', $permissiontoadd);
            }

            /*
            if ($permissiontoadd) {
                if ($object->status == $object::STATUS_ENABLED) {
                    print dolGetButtonAction('', $langs->trans('Disable'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=disable&token='.newToken(), '', $permissiontoadd);
                } else {
                    print dolGetButtonAction('', $langs->trans('Enable'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=enable&token='.newToken(), '', $permissiontoadd);
                }
            }
            if ($permissiontoadd) {
                if ($object->status == $object::STATUS_VALIDATED) {
                    print dolGetButtonAction('', $langs->trans('Cancel'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=close&token='.newToken(), '', $permissiontoadd);
                } else {
                    print dolGetButtonAction('', $langs->trans('Re-Open'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=reopen&token='.newToken(), '', $permissiontoadd);
                }
            }
            */

            // Delete
            $params = [];
            print dolGetButtonAction('', $langs->trans("Delete"), 'delete', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=delete&token=' . newToken(), 'delete', $permissiontodelete, $params);
        }
        print '</div>' . "\n";
    }


    // Select mail models is same action as presend
    if (GETPOST('modelselected')) {
        $action = 'presend';
    }

    /*
    if ($action != 'presend') {
        print '<div class="fichecenter"><div class="fichehalfleft">';
        print '<a name="builddoc"></a>'; // ancre

        $includedocgeneration = 0;

        // Documents
        if ($includedocgeneration) {
            $objref = dol_sanitizeFileName($object->ref);
            $relativepath = $objref.'/'.$objref.'.pdf';
            $filedir = $conf->bookcal->dir_output.'/'.$object->element.'/'.$objref;
            $urlsource = $_SERVER['PHP_SELF']."?id=".$object->id;
            $genallowed = $permissiontoread; // If you can read, you can build the PDF to read content
            $delallowed = $permissiontoadd; // If you can create/edit, you can remove a file on card
            print $formfile->showdocuments('bookcal:Calendar', $object->element.'/'.$objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang);
        }

        // Show links to link elements
        $linktoelem = $form->showLinkToObjectBlock($object, null, array('calendar'));
        $somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);


        print '</div><div class="fichehalfright">';

        $MAXEVENT = 10;

        $morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', dol_buildpath('/bookcal/calendar_agenda.php', 1).'?id='.$object->id);

        // List of actions on element
        include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
        $formactions = new FormActions($db);
        $somethingshown = $formactions->showactions($object, $object->element.'@'.$object->module, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlcenter);

        print '</div></div>';
    }

    //Select mail models is same action as presend
    if (GETPOST('modelselected')) {
        $action = 'presend';
    }

    // Presend form
    $modelmail = 'calendar';
    $defaulttopic = 'InformationMessage';
    $diroutput = $conf->bookcal->dir_output;
    $trackid = 'calendar'.$object->id;

    include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
    */
}

// End of page
llxFooter();
