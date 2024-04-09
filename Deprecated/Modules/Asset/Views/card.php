<?php

$form = new Form($db);
$formfile = new FormFile($db);

$title = $langs->trans("Asset") . ' - ' . $langs->trans("Card");
$help_url = '';
llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-asset page-card');

// Part to create
if ($action == 'create') {
    print load_fiche_titre($langs->trans("NewObject", $langs->transnoentitiesnoconv("Asset")), '', 'object_' . $object->picto);

    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="add">';
    if ($backtopage) {
        print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
    }
    if ($backtopageforcancel) {
        print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';
    }
    if (GETPOSTISSET('supplier_invoice_id')) {
        $object->fields['supplier_invoice_id'] = ['type' => 'integer:FactureFournisseur:fourn/class/fournisseur.facture.class.php:1:entity IN (__SHARED_ENTITIES__)', 'label' => 'SupplierInvoice', 'enabled' => '1', 'noteditable' => '1', 'position' => 280, 'notnull' => 0, 'visible' => 1, 'index' => 1, 'validate' => '1',];
        print '<input type="hidden" name="supplier_invoice_id" value="' . GETPOSTINT('supplier_invoice_id') . '">';
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
    print load_fiche_titre($langs->trans("Asset"), '', 'object_' . $object->picto);

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
    $res = $object->fetch_optionals();

    $head = assetPrepareHead($object);
    print dol_get_fiche_head($head, 'card', $langs->trans("Asset"), -1, $object->picto);

    $formconfirm = '';

    // Confirmation to delete
    if ($action == 'delete') {
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('DeleteAsset'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
    } elseif ($action == 'disposal') {
        // Disposal
        $langs->load('bills');

        $disposal_date = dol_mktime(12, 0, 0, GETPOSTINT('disposal_datemonth'), GETPOSTINT('disposal_dateday'), GETPOSTINT('disposal_dateyear')); // for date without hour, we use gmt
        $disposal_amount = GETPOSTINT('disposal_amount');
        $fk_disposal_type = GETPOSTINT('fk_disposal_type');
        $disposal_invoice_id = GETPOSTINT('disposal_invoice_id');
        $disposal_depreciated = GETPOSTISSET('disposal_depreciated') ? GETPOST('disposal_depreciated') : 1;
        $disposal_depreciated = !empty($disposal_depreciated) ? 1 : 0;
        $disposal_subject_to_vat = GETPOSTISSET('disposal_subject_to_vat') ? GETPOST('disposal_subject_to_vat') : 1;
        $disposal_subject_to_vat = !empty($disposal_subject_to_vat) ? 1 : 0;

        $object->fields['fk_disposal_type']['visible'] = 1;
        $disposal_type_form = $object->showInputField(null, 'fk_disposal_type', $fk_disposal_type, '', '', '', 0);
        $object->fields['fk_disposal_type']['visible'] = -2;

        $object->fields['disposal_invoice_id'] = ['type' => 'integer:Facture:compta/facture/class/facture.class.php::entity IN (__SHARED_ENTITIES__)', 'enabled' => '1', 'notnull' => 1, 'visible' => 1, 'index' => 1, 'validate' => '1',];
        $disposal_invoice_form = $object->showInputField(null, 'disposal_invoice_id', $disposal_invoice_id, '', '', '', 0);
        unset($object->fields['disposal_invoice_id']);

        // Create an array for form
        $formquestion = [
            ['type' => 'date', 'name' => 'disposal_date', 'tdclass' => 'fieldrequired', 'label' => $langs->trans("AssetDisposalDate"), 'value' => $disposal_date],
            ['type' => 'text', 'name' => 'disposal_amount', 'tdclass' => 'fieldrequired', 'label' => $langs->trans("AssetDisposalAmount"), 'value' => $disposal_amount],
            ['type' => 'other', 'name' => 'fk_disposal_type', 'tdclass' => 'fieldrequired', 'label' => $langs->trans("AssetDisposalType"), 'value' => $disposal_type_form],
            ['type' => 'other', 'name' => 'disposal_invoice_id', 'label' => $langs->trans("InvoiceCustomer"), 'value' => $disposal_invoice_form],
            ['type' => 'checkbox', 'name' => 'disposal_depreciated', 'label' => $langs->trans("AssetDisposalDepreciated"), 'value' => $disposal_depreciated],
            ['type' => 'checkbox', 'name' => 'disposal_subject_to_vat', 'label' => $langs->trans("AssetDisposalSubjectToVat"), 'value' => $disposal_subject_to_vat],
        ];
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('AssetDisposal'), $langs->trans('AssetConfirmDisposalAsk', $object->ref . ' - ' . $object->label), 'confirm_disposal', $formquestion, 'yes', 1);
    } elseif ($action == 'reopen') {
        // Re-open
        // Create an array for form
        $formquestion = [];
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ReOpen'), $langs->trans('AssetConfirmReOpenAsk', $object->ref), 'confirm_reopen', $formquestion, 'yes', 1);
    }
    // Clone confirmation
    /*  elseif ($action == 'clone') {
        // Create an array for form
        $formquestion = array();
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneAsk', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
    }*/

    // Call Hook formConfirm
    $parameters = ['formConfirm' => $formconfirm];
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
    $linkback = '<a href="' . DOL_URL_ROOT . '/asset/list.php?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

    $morehtmlref = '<div class="refidno">';
    $morehtmlref .= '</div>';


    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


    print '<div class="fichecenter">';
    print '<div class="fichehalfleft">';
    print '<div class="underbanner clearboth"></div>';
    print '<table class="border centpercent tableforfield">' . "\n";

    // Common attributes
    $keyforbreak = 'date_acquisition';    // We change column just before this field
    //unset($object->fields['fk_project']);             // Hide field already shown in banner
    //unset($object->fields['fk_soc']);                 // Hide field already shown in banner
    include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_view.tpl.php';

    // Other attributes. Fields from hook formObjectOptions and Extrafields.
    include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

    print '</table>';
    print '</div>';
    print '</div>';

    print '<div class="clearboth"></div>';

    print dol_get_fiche_end();

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
            if (empty($user->socid)) {
                print dolGetButtonAction('', $langs->trans('SendMail'), 'default', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=presend&mode=init&token=' . newToken() . '#formmailbeforetitle');
            }

            if ($object->status == $object::STATUS_DRAFT) {
                print dolGetButtonAction($langs->trans('Modify'), '', 'default', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=edit&token=' . newToken(), '', $permissiontoadd);
            }

            // Clone
            //print dolGetButtonAction($langs->trans('ToClone'), '', 'default', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=clone&token=' . newToken(), '', false && $permissiontoadd);

            if ($object->status == $object::STATUS_DRAFT) {
                print dolGetButtonAction($langs->trans('AssetDisposal'), '', 'default', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=disposal&token=' . newToken(), '', $permissiontoadd);
            } else {
                print dolGetButtonAction($langs->trans('ReOpen'), '', 'default', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=reopen&token=' . newToken(), '', $permissiontoadd);
            }

            // Delete (need delete permission, or if draft, just need create/modify permission)
            print dolGetButtonAction($langs->trans('Delete'), '', 'delete', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=delete&token=' . newToken(), '', $permissiontodelete || ($object->status == $object::STATUS_DRAFT && $permissiontoadd));
        }
        print '</div>' . "\n";
    }

    // Select mail models is same action as presend
    if (GETPOST('modelselected')) {
        $action = 'presend';
    }

    if ($action != 'presend') {
        print '<div class="fichecenter"><div class="fichehalfleft">';
        print '<a name="builddoc"></a>'; // ancre

        $includedocgeneration = 0;

        // Documents
        if ($includedocgeneration) {
            $objref = dol_sanitizeFileName($object->ref);
            $relativepath = $objref . '/' . $objref . '.pdf';
            $filedir = $conf->asset->dir_output . '/' . $objref;
            $urlsource = $_SERVER['PHP_SELF'] . "?id=" . $object->id;
            $genallowed = $user->hasRight('asset', 'read'); // If you can read, you can build the PDF to read content
            $delallowed = $user->hasRight('asset', 'write'); // If you can create/edit, you can remove a file on card
            print $formfile->showdocuments('asset:Asset', $objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang);
        }

        // Show links to link elements
        $linktoelem = $form->showLinkToObjectBlock($object, null, ['asset']);
        $somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);


        print '</div><div class="fichehalfright">';

        $morehtmlcenter = '';
        $MAXEVENT = 10;

        $morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', DOL_URL_ROOT . '/asset/agenda.php?id=' . $object->id);

        // List of actions on element
        include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
        $formactions = new FormActions($db);
        $somethingshown = $formactions->showactions($object, $object->element, 0, 1, '', $MAXEVENT, '', $morehtmlcenter);

        print '</div></div>';
    }

    //Select mail models is same action as presend
    if (GETPOST('modelselected')) {
        $action = 'presend';
    }

    // Presend form
    $modelmail = 'asset';
    $defaulttopic = 'InformationMessage';
    $diroutput = $conf->asset->dir_output;
    $trackid = 'asset' . $object->id;

    include DOL_DOCUMENT_ROOT . '/core/tpl/card_presend.tpl.php';
}

// End of page
llxFooter();
