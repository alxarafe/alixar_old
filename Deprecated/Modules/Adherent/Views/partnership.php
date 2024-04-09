<?php

use DoliModules\Adherent\Model\Adherent;

$form = new Form($db);
$formfile = new FormFile($db);

$title = $langs->trans("Partnership");
llxHeader('', $title);

$form = new Form($db);

if ($id > 0) {
    $langs->load("members");

    $object = new Adherent($db);
    $result = $object->fetch($id);

    if (isModEnabled('notification')) {
        $langs->load("mails");
    }

    $adht->fetch($object->typeid);

    $head = member_prepare_head($object);

    print dol_get_fiche_head($head, 'partnership', $langs->trans("ThirdParty"), -1, 'user');

    $linkback = '<a href="' . DOL_URL_ROOT . '/adherents/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

    dol_banner_tab($object, 'rowid', $linkback);

    print '<div class="fichecenter">';

    print '<div class="underbanner clearboth"></div>';
    print '<table class="border centpercent tableforfield">';

    // Login
    if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
        print '<tr><td class="titlefield">' . $langs->trans("Login") . ' / ' . $langs->trans("Id") . '</td><td class="valeur">' . $object->login . '&nbsp;</td></tr>';
    }

    // Type
    print '<tr><td class="titlefield">' . $langs->trans("Type") . '</td><td class="valeur">' . $adht->getNomUrl(1) . "</td></tr>\n";

    // Morphy
    print '<tr><td>' . $langs->trans("MemberNature") . '</td><td class="valeur" >' . $object->getmorphylib() . '</td>';
    print '</tr>';

    // Company
    print '<tr><td>' . $langs->trans("Company") . '</td><td class="valeur">' . $object->company . '</td></tr>';

    // Civility
    print '<tr><td>' . $langs->trans("UserTitle") . '</td><td class="valeur">' . $object->getCivilityLabel() . '&nbsp;</td>';
    print '</tr>';

    print '</table>';

    print '</div>';

    print dol_get_fiche_end();
} else {
    dol_print_error(null, 'Parameter rowid not defined');
}


// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
    // Buttons for actions

    if ($action != 'presend') {
        print '<div class="tabsAction">' . "\n";
        $parameters = [];
        $reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

        if (empty($reshook)) {
            // Show
            if ($permissiontoadd) {
                print dolGetButtonAction($langs->trans('AddPartnership'), '', 'default', DOL_URL_ROOT . '/partnership/partnership_card.php?action=create&fk_member=' . $object->id . '&backtopage=' . urlencode(DOL_URL_ROOT . '/adherents/partnership.php?id=' . $object->id), '', $permissiontoadd);
            }
        }
        print '</div>' . "\n";
    }


    //$morehtmlright = 'partnership/partnership_card.php?action=create&backtopage=%2Fdolibarr%2Fhtdocs%2Fpartnership%2Fpartnership_list.php';
    $morehtmlright = '';

    print load_fiche_titre($langs->trans("PartnershipDedicatedToThisMember", $langs->transnoentitiesnoconv("Partnership")), $morehtmlright, '');

    $memberid = $object->id;


    // TODO Replace this card with the list of all partnerships.

    $object = new Partnership($db);
    $partnershipid = $object->fetch(0, "", $memberid);

    if ($partnershipid > 0) {
        print '<div class="fichecenter">';
        print '<div class="fichehalfleft">';
        print '<div class="underbanner clearboth"></div>';
        print '<table class="border centpercent tableforfield">' . "\n";

        // Common attributes
        //$keyforbreak='fieldkeytoswitchonsecondcolumn';    // We change column just before this field
        //unset($object->fields['fk_project']);             // Hide field already shown in banner
        //unset($object->fields['fk_member']);                  // Hide field already shown in banner
        include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_view.tpl.php';

        // End of subscription date
        $fadherent = new Adherent($db);
        $fadherent->fetch($object->fk_member);
        print '<tr><td>' . $langs->trans("SubscriptionEndDate") . '</td><td class="valeur">';
        if ($fadherent->datefin) {
            print dol_print_date($fadherent->datefin, 'day');
            if ($fadherent->hasDelay()) {
                print " " . img_warning($langs->trans("Late"));
            }
        } else {
            if (!$adht->subscription) {
                print $langs->trans("SubscriptionNotRecorded");
                if ($fadherent->statut > 0) {
                    print " " . img_warning($langs->trans("Late")); // Display a delay picto only if it is not a draft and is not canceled
                }
            } else {
                print $langs->trans("SubscriptionNotReceived");
                if ($fadherent->statut > 0) {
                    print " " . img_warning($langs->trans("Late")); // Display a delay picto only if it is not a draft and is not canceled
                }
            }
        }
        print '</td></tr>';

        print '</table>';
        print '</div>';
    }
}

// End of page
llxFooter();
