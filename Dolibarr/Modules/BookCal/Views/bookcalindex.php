<?php

use DoliCore\Form\Form;
use DoliCore\Form\FormFile;

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", $langs->trans("BookCalArea"));

print load_fiche_titre($langs->trans("BookCalArea"), '', 'fa-calendar-check');

print '<div class="fichecenter"><div class="fichethirdleft">';


// BEGIN MODULEBUILDER DRAFT MYOBJECT
// Draft MyObject
if ($user->hasRight('bookcal', 'availabilities', 'read') && isModEnabled('bookcal')) {
    $langs->load("orders");
    /*$myobjectstatic = new Booking($db);

    $sql = "SELECT rowid, `ref`, fk_soc, fk_project, description, note_public, note_private, date_creation, tms, fk_user_creat, fk_user_modif, last_main_doc, import_key, model_pdf, status, firstname, lastname, email, `start`, duration";
    $sql .= " FROM ". MAIN_DB_PREFIX . 'bookcal_booking';

    $resql = $db->query($sql);
    if ($resql) {
        $total = 0;
        $num = $db->num_rows($resql);

        print '<table class="noborder centpercent">';
        print '<tr class="liste_titre">';
        print '<th colspan="21">'.$langs->trans("Bookings").($num?'<span class="badge marginleftonlyshort">'.$num.'</span>':'').'</th></tr>';

        $var = true;
        print '
        <tr>
        <th colspan="3">id</th>
        <th colspan="3">ref</th>
        <th colspan="3">name</th>
        <th colspan="3">hour</th>
        <th colspan="3">duration</th>
        <th colspan="3">description</th>
        </tr>';
        if ($num > 0) {
            $i = 0;
            while ($i < $num) {
                $obj = $db->fetch_object($resql);
                print '<tr class="oddeven">';

                $myobjectstatic->id=$obj->rowid;
                $myobjectstatic->ref=$obj->ref;
                $myobjectstatic->firstname = $obj->firstname;
                $myobjectstatic->lastname = $obj->lastname;
                $myobjectstatic->start = $obj->start;
                $myobjectstatic->duration = $obj->duration;
                $myobjectstatic->description = $obj->description;


                print '<td colspan="3" class="nowrap">' . $myobjectstatic->id . "</td>";
                print '<td colspan="3" class="nowrap">' . $myobjectstatic->ref . "</td>";
                print '<td colspan="3" class="nowrap">' . $myobjectstatic->firstname . " " . $myobjectstatic->lastname . "</td>";
                print '<td colspan="3" class="nowrap">' . dol_print_date($myobjectstatic->start, 'dayhourtext') . "</td>";
                print '<td colspan="3" class="nowrap">' . $myobjectstatic->duration . "</td>";
                print '<td colspan="3" class="nowrap">' . $myobjectstatic->description . "</td>";
                $i++;
            }
        } else {
            print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("NoOrder").'</td></tr>';
        }
        print "</table><br>";

        $db->free($resql);
    } else {
        dol_print_error($db);
    }*/
}
//END MODULEBUILDER DRAFT MYOBJECT */


print '</div><div class="fichetwothirdright">';


$NBMAX = getDolGlobalString('MAIN_SIZE_SHORTLIST_LIMIT');
$max = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT');

/* BEGIN MODULEBUILDER LASTMODIFIED MYOBJECT
// Last modified myobject
if (isModEnabled('bookcal')) {
    $sql = "SELECT rowid, `ref`, fk_soc, fk_project, description, note_public, note_private, date_creation, tms, fk_user_creat, fk_user_modif, last_main_doc, import_key, model_pdf, status, firstname, lastname, email, `start`, duration";
    $sql .= " FROM ". MAIN_DB_PREFIX . 'bookcal_booking';
    print "here2";
    $resql = $db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;

        print '<table class="noborder centpercent">';
        print '<tr class="liste_titre">';
        print '<th colspan="2">';
        print $langs->trans("BoxTitleLatestModifiedMyObjects", $max);
        print '</th>';
        print '<th class="right">'.$langs->trans("DateModificationShort").'</th>';
        print '</tr>';
        print $num;
        if ($num)
        {
            while ($i < $num)
            {
                $objp = $db->fetch_object($resql);

                $myobjectstatic->id=$objp->rowid;
                $myobjectstatic->ref=$objp->ref;
                $myobjectstatic->label=$objp->label;
                $myobjectstatic->status = $objp->status;

                print '<tr class="oddeven">';
                print '<td class="nowrap">'.$myobjectstatic->getNomUrl(1).'</td>';
                print '<td class="right nowrap">';
                print "</td>";
                print '<td class="right nowrap">'.dol_print_date($db->jdate($objp->tms), 'day')."</td>";
                print '</tr>';
                $i++;
            }

            $db->free($resql);
        } else {
            print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
        }
        print "</table><br>";
    }
}

*/
print '</div></div>';

// End of page
llxFooter();