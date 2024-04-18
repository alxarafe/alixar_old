<?php

use DoliCore\Form\Form;

$title = $langs->trans("Member") . " - " . $langs->trans("Note");

$help_url = "EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros|DE:Modul_Mitglieder";

llxHeader("", $title, $help_url);

$form = new Form($db);

if ($id) {
    $head = member_prepare_head($object);

    print dol_get_fiche_head($head, 'note', $langs->trans("Member"), -1, 'user');

    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';

    $linkback = '<a href="' . DOL_URL_ROOT . '/adherents/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

    $morehtmlref = '<a href="' . DOL_URL_ROOT . '/adherents/vcard.php?id=' . $object->id . '" class="refid">';
    $morehtmlref .= img_picto($langs->trans("Download") . ' ' . $langs->trans("VCard"), 'vcard.png', 'class="valignmiddle marginleftonly paddingrightonly"');
    $morehtmlref .= '</a>';

    dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref);

    print '<div class="fichecenter">';

    print '<div class="underbanner clearboth"></div>';
    print '<table class="border centpercent tableforfield">';

    // Login
    if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
        print '<tr><td class="titlefield">' . $langs->trans("Login") . ' / ' . $langs->trans("Id") . '</td><td class="valeur">' . dol_escape_htmltag($object->login) . '</td></tr>';
    }

    // Type
    print '<tr><td>' . $langs->trans("Type") . '</td>';
    print '<td class="valeur">' . $adht->getNomUrl(1) . "</td></tr>\n";

    // Morphy
    print '<tr><td class="titlefield">' . $langs->trans("MemberNature") . '</td>';
    print '<td class="valeur" >' . $object->getmorphylib('', 1) . '</td>';
    print '</tr>';

    // Company
    print '<tr><td>' . $langs->trans("Company") . '</td><td class="valeur">' . dol_escape_htmltag($object->company) . '</td></tr>';

    // Civility
    print '<tr><td>' . $langs->trans("UserTitle") . '</td><td class="valeur">' . $object->getCivilityLabel() . '</td>';
    print '</tr>';

    print "</table>";

    print '</div>';


    $cssclass = 'titlefield';
    $permission = $user->hasRight('adherent', 'creer'); // Used by the include of notes.tpl.php
    include DOL_DOCUMENT_ROOT . '/core/tpl/notes.tpl.php';


    print dol_get_fiche_end();
}

// End of page
llxFooter();
