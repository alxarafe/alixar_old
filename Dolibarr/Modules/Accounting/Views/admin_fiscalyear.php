<?php

use DoliCore\Form\Form;

$max = 100;

$form = new Form($db);
$fiscalyearstatic = new Fiscalyear($db);

$title = $langs->trans('AccountingPeriods');

$help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';

llxHeader('', $title, $help_url);

$sql = "SELECT f.rowid, f.label, f.date_start, f.date_end, f.statut as status, f.entity";
$sql .= " FROM " . MAIN_DB_PREFIX . "accounting_fiscalyear as f";
$sql .= " WHERE f.entity = " . $conf->entity;
$sql .= $db->order($sortfield, $sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
    if (($page * $limit) > $nbtotalofrecords) { // if total resultset is smaller then paging size (filtering), goto and load page 0
        $page = 0;
        $offset = 0;
    }
}

$sql .= $db->plimit($limit + 1, $offset);

$result = $db->query($sql);
if ($result) {
    $num = $db->num_rows($result);
    $param = '';

    $parameters = ['param' => $param];
    $reshook = $hookmanager->executeHooks('addMoreActionsButtonsList', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
    if ($reshook < 0) {
        setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
    }

    $newcardbutton = empty($hookmanager->resPrint) ? '' : $hookmanager->resPrint;

    if (empty($reshook)) {
        $newcardbutton .= dolGetButtonTitle($langs->trans('NewFiscalYear'), '', 'fa fa-plus-circle', 'fiscalyear_card.php?action=create', '', $user->hasRight('accounting', 'fiscalyear', 'write'));
    }

    $title = $langs->trans('AccountingPeriods');
    print_barre_liste($title, $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'calendar', 0, $newcardbutton, '', $limit, 1);

    print '<div class="div-table-responsive">';
    print '<table class="tagtable liste centpercent">';
    print '<tr class="liste_titre">';
    print '<td>' . $langs->trans("Ref") . '</td>';
    print '<td>' . $langs->trans("Label") . '</td>';
    print '<td>' . $langs->trans("DateStart") . '</td>';
    print '<td>' . $langs->trans("DateEnd") . '</td>';
    print '<td class="center">' . $langs->trans("NumberOfAccountancyEntries") . '</td>';
    print '<td class="center">' . $langs->trans("NumberOfAccountancyMovements") . '</td>';
    print '<td class="right">' . $langs->trans("Status") . '</td>';
    print '</tr>';

    // Loop on record
    // --------------------------------------------------------------------
    $i = 0;
    if ($num) {
        while ($i < $num && $i < $max) {
            $obj = $db->fetch_object($result);

            $fiscalyearstatic->ref = $obj->rowid;
            $fiscalyearstatic->id = $obj->rowid;
            $fiscalyearstatic->date_start = $obj->date_start;
            $fiscalyearstatic->date_end = $obj->date_end;
            $fiscalyearstatic->statut = $obj->status;
            $fiscalyearstatic->status = $obj->status;

            print '<tr class="oddeven">';
            print '<td>';
            print $fiscalyearstatic->getNomUrl(1);
            print '</td>';
            print '<td class="left">' . $obj->label . '</td>';
            print '<td class="left">' . dol_print_date($db->jdate($obj->date_start), 'day') . '</td>';
            print '<td class="left">' . dol_print_date($db->jdate($obj->date_end), 'day') . '</td>';
            print '<td class="center">' . $object->getAccountancyEntriesByFiscalYear($obj->date_start, $obj->date_end) . '</td>';
            print '<td class="center">' . $object->getAccountancyMovementsByFiscalYear($obj->date_start, $obj->date_end) . '</td>';
            print '<td class="right">' . $fiscalyearstatic->LibStatut($obj->status, 5) . '</td>';
            print '</tr>';
            $i++;
        }
    } else {
        print '<tr class="oddeven"><td colspan="7"><span class="opacitymedium">' . $langs->trans("NoRecordFound") . '</span></td></tr>';
    }
    print '</table>';
    print '</div>';
} else {
    dol_print_error($db);
}

// End of page
llxFooter();
