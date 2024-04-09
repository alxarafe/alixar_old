<?php

$contactstatic = new Contact($db);

$form = new Form($db);


if ($object->id > 0) {
    require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
    require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

    $langs->load("companies");

    $title = $langs->trans("Member") . " - " . $langs->trans("Agenda");

    $help_url = "EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros|DE:Modul_Mitglieder";

    llxHeader("", $title, $help_url);

    if (isModEnabled('notification')) {
        $langs->load("mails");
    }
    $head = member_prepare_head($object);

    print dol_get_fiche_head($head, 'agenda', $langs->trans("Member"), -1, 'user');

    $linkback = '<a href="' . DOL_URL_ROOT . '/adherents/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

    $morehtmlref = '<a href="' . DOL_URL_ROOT . '/adherents/vcard.php?id=' . $object->id . '" class="refid">';
    $morehtmlref .= img_picto($langs->trans("Download") . ' ' . $langs->trans("VCard"), 'vcard.png', 'class="valignmiddle marginleftonly paddingrightonly"');
    $morehtmlref .= '</a>';

    dol_banner_tab($object, 'rowid', $linkback, 1, 'rowid', 'ref', $morehtmlref);

    print '<div class="fichecenter">';

    print '<div class="underbanner clearboth"></div>';

    $object->info($id);
    dol_print_object_info($object, 1);

    print '</div>';

    print dol_get_fiche_end();


//print '<div class="tabsAction">';
    //print '</div>';


    $newcardbutton = '';
    if (isModEnabled('agenda')) {
        $newcardbutton .= dolGetButtonTitle($langs->trans('AddAction'), '', 'fa fa-plus-circle', DOL_URL_ROOT . '/comm/action/card.php?action=create&backtopage=' . urlencode($_SERVER['PHP_SELF']) . ($object->id > 0 ? '?id=' . $object->id : '') . '&origin=member&originid=' . $id);
    }

    if (isModEnabled('agenda') && ($user->hasRight('agenda', 'myactions', 'read') || $user->hasRight('agenda', 'allactions', 'read'))) {
        print '<br>';

        $param = '&id=' . $id;
        if (!empty($contextpage) && $contextpage != $_SERVER['PHP_SELF']) {
            $param .= '&contextpage=' . $contextpage;
        }
        if ($limit > 0 && $limit != $conf->liste_limit) {
            $param .= '&limit=' . $limit;
        }

        print_barre_liste($langs->trans("ActionsOnMember"), 0, $_SERVER['PHP_SELF'], '', $sortfield, $sortorder, '', 0, -1, '', '', $newcardbutton, '', 0, 1, 1);

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
