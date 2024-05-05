<?php

$form = new Form($db);

$now = dol_now();
$title = $langs->trans('Calendar') . " - " . $langs->trans('Bookings');

llxHeader('', $title, $helpurl);


if ($object->id > 0) {
    $head = calendarPrepareHead($object);

    print dol_get_fiche_head($head, 'booking', $langs->trans("Calendar"), -1, $object->picto, 0, '', '', 0, '', 1);

    $formconfirm = '';

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
    $morehtmlref .= '</div>';


    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


    print '<div class="fichecenter">';
    print '<div class="fichehalfleft">';
    print '<div class="underbanner clearboth"></div>';
    print '<table class="border centpercent tableforfield">' . "\n";

    // Common attributes
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
     * Bookings
     */

    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';

    print '<tr class="liste_titre">';

    print '<td class="left">' . $langs->trans("Ref") . '</td>';
    print '<td>' . $langs->trans("Title") . '</td>';
    print '<td class="center">' . $langs->trans("DateStart") . '</td>';
    print '<td class="center">' . $langs->trans("DateEnd") . '</td>';
    print '<td class="left">' . $langs->trans("Contact") . '</td>';
    print '</tr>';


    $sql = "SELECT ac.id, ac.ref, ac.datep as date_start, ac.datep2 as date_end, ac.label, acr.fk_element";
    $sql .= " FROM " . MAIN_DB_PREFIX . "actioncomm as ac";
    $sql .= " JOIN " . MAIN_DB_PREFIX . "actioncomm_resources as acr on acr.fk_actioncomm = ac.id";
    $sql .= " WHERE ac.fk_bookcal_calendar = " . ((int) $object->id);
    $sql .= " AND ac.code = 'AC_RDV'";
    $sql .= " AND acr.element_type = 'socpeople'";
    $resql = $db->query($sql);

    $num = 0;
    if ($resql) {
        $i = 0;

        $tmpcontact = new Contact($db);
        $tmpactioncomm = new ActionComm($db);

        $num = $db->num_rows($result);
        while ($i < $num) {
            $obj = $db->fetch_object($resql);
            $tmpcontact->fetch($obj->fk_element);
            $tmpactioncomm->fetch($obj->id);

            print '<tr class="oddeven">';

            // Ref
            print '<td class="nowraponall">' . $tmpactioncomm->getNomUrl(1, -1) . "</td>\n";

            // Title
            print '<td class="tdoverflowmax125">';
            print $obj->label;
            print '</td>';

            // Amount
            print '<td class="center">' . dol_print_date($db->jdate($obj->date_start), "dayhour") . '</td>';

            // Date process
            print '<td class="center">' . dol_print_date($db->jdate($obj->date_end), "dayhour") . '</td>';

            // Link to make payment now
            print '<td class="minwidth75">';
            print $tmpcontact->getNomUrl(1, -1);
            print '</td>';


            print "</tr>\n";
            $i++;
        }

        $db->free($resql);
    } else {
        dol_print_error($db);
    }

    print "</table>";
    print '</div>';
}

// End of page
llxFooter();
