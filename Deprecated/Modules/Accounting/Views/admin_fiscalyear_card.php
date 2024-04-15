<?php

use DoliCore\Form\Form;

$form = new Form($db);

$title = $langs->trans("Fiscalyear") . " - " . $langs->trans("Card");
if ($action == 'create') {
    $title = $langs->trans("NewFiscalYear");
}

$help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';

llxHeader('', $title, $help_url);

if ($action == 'create') {
    print load_fiche_titre($title, '', 'object_' . $object->picto);

    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="add">';

    print dol_get_fiche_head();

    print '<table class="border centpercent">';

    // Label
    print '<tr><td class="titlefieldcreate fieldrequired">' . $langs->trans("Label") . '</td><td><input name="label" size="32" value="' . GETPOST('label', 'alpha') . '"></td></tr>';

    // Date start
    print '<tr><td class="fieldrequired">' . $langs->trans("DateStart") . '</td><td>';
    print $form->selectDate(($date_start ? $date_start : ''), 'fiscalyear');
    print '</td></tr>';

    // Date end
    print '<tr><td class="fieldrequired">' . $langs->trans("DateEnd") . '</td><td>';
    print $form->selectDate(($date_end ? $date_end : -1), 'fiscalyearend');
    print '</td></tr>';

    /*
    // Status
    print '<tr>';
    print '<td class="fieldrequired">' . $langs->trans("Status") . '</td>';
    print '<td class="valeur">';
    print $form->selectarray('status', $status2label, GETPOST('status', 'int'));
    print '</td></tr>';
    */

    print '</table>';

    print dol_get_fiche_end();

    print $form->buttonsSaveCancel("Create");

    print '</form>';
}


// Part to edit record
if (($id || $ref) && $action == 'edit') {
    print load_fiche_titre($langs->trans("Fiscalyear"), '', 'object_' . $object->picto);

    print '<form method="POST" name="update" action="' . $_SERVER['PHP_SELF'] . '">' . "\n";
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

    // Ref
    print "<tr>";
    print '<td class="titlefieldcreate titlefield">' . $langs->trans("Ref") . '</td><td>';
    print $object->ref;
    print '</td></tr>';

    // Label
    print '<tr><td class="fieldrequired">' . $langs->trans("Label") . '</td><td>';
    print '<input name="label" class="flat" size="32" value="' . $object->label . '">';
    print '</td></tr>';

    // Date start
    print '<tr><td class="fieldrequired">' . $langs->trans("DateStart") . '</td><td>';
    print $form->selectDate($object->date_start ? $object->date_start : -1, 'fiscalyear');
    print '</td></tr>';

    // Date end
    print '<tr><td class="fieldrequired">' . $langs->trans("DateEnd") . '</td><td>';
    print $form->selectDate($object->date_end ? $object->date_end : -1, 'fiscalyearend');
    print '</td></tr>';

    // Status
    print '<tr><td>' . $langs->trans("Status") . '</td><td>';
    print $object->getLibStatut(4);
    print '</td></tr>';

    print '</table>';

    print dol_get_fiche_end();

    print $form->buttonsSaveCancel();

    print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
    $head = fiscalyear_prepare_head($object);

    print dol_get_fiche_head($head, 'card', $langs->trans("Fiscalyear"), 0, 'calendar');

    $formconfirm = '';

    // Confirmation to delete
    if ($action == 'delete') {
        $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . "?id=" . $object->id, $langs->trans("DeleteFiscalYear"), $langs->trans("ConfirmDeleteFiscalYear"), "confirm_delete", '', 0, 1);
    }

    // Print form confirm
    print $formconfirm;

    // Object card
    // ------------------------------------------------------------
    $linkback = '<a href="' . DOL_URL_ROOT . '/accountancy/admin/fiscalyear.php">' . $langs->trans("BackToList") . '</a>';

    print '<table class="border centpercent">';

    // Ref
    print '<tr><td class="titlefield">' . $langs->trans("Ref") . '</td><td width="50%">';
    print $object->ref;
    print '</td><td>';
    print $linkback;
    print '</td></tr>';

    // Label
    print '<tr><td class="tdtop">';
    print $form->editfieldkey("Label", 'label', $object->label, $object, 1, 'alpha:32');
    print '</td><td colspan="2">';
    print $form->editfieldval("Label", 'label', $object->label, $object, 1, 'alpha:32');
    print "</td></tr>";

    // Date start
    print '<tr><td>';
    print $form->editfieldkey("DateStart", 'date_start', $object->date_start, $object, 1, 'datepicker');
    print '</td><td colspan="2">';
    print $form->editfieldval("DateStart", 'date_start', $object->date_start, $object, 1, 'datepicker');
    print '</td></tr>';

    // Date end
    print '<tr><td>';
    print $form->editfieldkey("DateEnd", 'date_end', $object->date_end, $object, 1, 'datepicker');
    print '</td><td colspan="2">';
    print $form->editfieldval("DateEnd", 'date_end', $object->date_end, $object, 1, 'datepicker');
    print '</td></tr>';

    // Status
    print '<tr><td>' . $langs->trans("Status") . '</td><td colspan="2">' . $object->getLibStatut(4) . '</td></tr>';

    print "</table>";

    print dol_get_fiche_end();

    /*
     * Action bar
     */
    if ($user->hasRight('accounting', 'fiscalyear', 'write')) {
        print '<div class="tabsAction">';

        print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=edit&token=' . newToken() . '&id=' . $id . '">' . $langs->trans('Modify') . '</a>';

        //print dolGetButtonAction($langs->trans("Delete"), '', 'delete', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delete&token='.newToken(), 'delete', $permissiontodelete);

        print '</div>';
    }
}

// End of page
llxFooter();
