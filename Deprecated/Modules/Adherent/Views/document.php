<?php

$form = new Form($db);

$title = $langs->trans("Member") . " - " . $langs->trans("Documents");

$help_url = "EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros|DE:Modul_Mitglieder";

llxHeader("", $title, $help_url);

if ($id > 0) {
    $result = $membert->fetch($object->typeid);
    if ($result > 0) {
        // Build file list
        $filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
        $totalsize = 0;
        foreach ($filearray as $key => $file) {
            $totalsize += $file['size'];
        }

        if (isModEnabled('notification')) {
            $langs->load("mails");
        }

        $head = member_prepare_head($object);

        print dol_get_fiche_head($head, 'document', $langs->trans("Member"), -1, 'user');

        $linkback = '<a href="' . DOL_URL_ROOT . '/adherents/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

        $morehtmlref = '<a href="' . DOL_URL_ROOT . '/adherents/vcard.php?id=' . $object->id . '" class="refid">';
        $morehtmlref .= img_picto($langs->trans("Download") . ' ' . $langs->trans("VCard"), 'vcard.png', 'class="valignmiddle marginleftonly paddingrightonly"');
        $morehtmlref .= '</a>';

        dol_banner_tab($object, 'rowid', $linkback, 1, 'rowid', 'ref', $morehtmlref);

        print '<div class="fichecenter">';

        print '<div class="underbanner clearboth"></div>';
        print '<table class="border tableforfield centpercent">';

        $linkback = '<a href="' . DOL_URL_ROOT . '/adherents/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

        // Login
        if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
            print '<tr><td class="titlefield">' . $langs->trans("Login") . ' / ' . $langs->trans("Id") . '</td><td class="valeur">' . dol_escape_htmltag($object->login) . '</td></tr>';
        }

        // Type
        print '<tr><td>' . $langs->trans("Type") . '</td>';
        print '<td class="valeur">' . $membert->getNomUrl(1) . "</td></tr>\n";

        // Morphy
        print '<tr><td class="titlefield">' . $langs->trans("MemberNature") . '</td>';
        print '<td class="valeur" >' . $object->getmorphylib('', 1) . '</td>';
        print '</tr>';

        // Company
        print '<tr><td>' . $langs->trans("Company") . '</td><td class="valeur">' . dol_escape_htmltag($object->company) . '</td></tr>';

        // Civility
        print '<tr><td>' . $langs->trans("UserTitle") . '</td><td class="valeur">' . $object->getCivilityLabel() . '&nbsp;</td>';
        print '</tr>';

        // Number of Attached Files
        print '<tr><td>' . $langs->trans("NbOfAttachedFiles") . '</td><td colspan="3">' . count($filearray) . '</td></tr>';

        //Total Size Of Attached Files
        print '<tr><td>' . $langs->trans("TotalSizeOfAttachedFiles") . '</td><td colspan="3">' . dol_print_size($totalsize, 1, 1) . '</td></tr>';

        print '</table>';

        print '</div>';

        print dol_get_fiche_end();

        $modulepart = 'member';
        $permissiontoadd = $user->hasRight('adherent', 'creer');
        $permtoedit = $user->hasRight('adherent', 'creer');
        $param = '&id=' . $object->id;
        include DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';
        print "<br><br>";
    } else {
        dol_print_error($db);
    }
} else {
    $langs->load("errors");
    print $langs->trans("ErrorRecordNotFound");
}

// End of page
llxFooter();
