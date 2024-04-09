<?php


$title = $langs->trans('MemberTypeCard');

$help_url = '';

$shortlabel = dol_trunc($object->label, 16);

$title = $langs->trans('MemberType') . " " . $shortlabel . " - " . $langs->trans('Translation');

$help_url = 'EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios|DE:Modul_Mitglieder';

llxHeader('', $title, $help_url);

$form = new Form($db);
$formadmin = new FormAdmin($db);

$head = member_type_prepare_head($object);
$titre = $langs->trans("MemberType" . $object->id);

// Calculate $cnt_trans
$cnt_trans = 0;
if (!empty($object->multilangs)) {
    foreach ($object->multilangs as $key => $value) {
        $cnt_trans++;
    }
}


print dol_get_fiche_head($head, 'translation', $titre, 0, 'group');

$linkback = '<a href="' . dol_buildpath('/adherents/type.php', 1) . '">' . $langs->trans("BackToList") . '</a>';

dol_banner_tab($object, 'rowid', $linkback);

print dol_get_fiche_end();


/*
 * Action bar
 */
print "\n<div class=\"tabsAction\">\n";

if ($action == '') {
    if ($user->hasRight('produit', 'creer') || $user->hasRight('service', 'creer')) {
        print '<a class="butAction" href="' . DOL_URL_ROOT . '/adherents/type_translation.php?action=create&token=' . newToken() . '&rowid=' . $object->id . '">' . $langs->trans("Add") . '</a>';
        if ($cnt_trans > 0) {
            print '<a class="butAction" href="' . DOL_URL_ROOT . '/adherents/type_translation.php?action=edit&token=' . newToken() . '&rowid=' . $object->id . '">' . $langs->trans("Update") . '</a>';
        }
    }
}

print "\n</div>\n";


if ($action == 'edit') {
    //WYSIWYG Editor
    require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';

    print '<form action="' . $_SERVER['PHP_SELF'] . '" method="POST">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="vedit">';
    print '<input type="hidden" name="rowid" value="' . $object->id . '">';

    if (!empty($object->multilangs)) {
        foreach ($object->multilangs as $key => $value) {
            $s = picto_from_langcode($key);
            print '<br>';
            print '<div class="inline-block marginbottomonly">';
            print($s ? $s . ' ' : '') . '<b>' . $langs->trans('Language_' . $key) . ':</b>';
            print '</div>';
            print '<div class="inline-block marginbottomonly floatright">';
            print '<a href="' . $_SERVER['PHP_SELF'] . '?rowid=' . $object->id . '&action=delete&token=' . newToken() . '&langtodelete=' . $key . '">' . img_delete('', 'class="valigntextbottom"') . "</a><br>";
            print '</div>';

            print '<div class="underbanner clearboth"></div>';
            print '<table class="border centpercent">';
            print '<tr><td class="tdtop titlefieldcreate fieldrequired">' . $langs->trans('Label') . '</td><td><input name="libelle-' . $key . '" class="minwidth300" value="' . dol_escape_htmltag($object->multilangs[$key]["label"]) . '"></td></tr>';
            print '<tr><td class="tdtop">' . $langs->trans('Description') . '</td><td>';
            $doleditor = new DolEditor("desc-$key", $object->multilangs[$key]["description"], '', 160, 'dolibarr_notes', '', false, true, getDolGlobalInt('FCKEDITOR_ENABLE_SOCIETE'), ROWS_3, '90%');
            $doleditor->Create();
            print '</td></tr>';
            print '</td></tr>';
            print '</table>';
        }
    }

    print $form->buttonsSaveCancel();

    print '</form>';
} elseif ($action != 'create') {
    if (!empty($object->multilangs)) {
        foreach ($object->multilangs as $key => $value) {
            $s = picto_from_langcode($key);
            print '<div class="inline-block marginbottomonly">';
            print($s ? $s . ' ' : '') . '<b>' . $langs->trans('Language_' . $key) . ':</b>';
            print '</div>';
            print '<div class="inline-block marginbottomonly floatright">';
            print '<a href="' . $_SERVER['PHP_SELF'] . '?rowid=' . $object->id . '&action=delete&token=' . newToken() . '&langtodelete=' . $key . '">' . img_delete('', 'class="valigntextbottom"') . '</a>';
            print '</div>';


            print '<div class="fichecenter">';
            print '<div class="underbanner clearboth"></div>';
            print '<table class="border centpercent">';
            print '<tr><td class="titlefieldcreate">' . $langs->trans('Label') . '</td><td>' . $object->multilangs[$key]["label"] . '</td></tr>';
            print '<tr><td class="tdtop">' . $langs->trans('Description') . '</td><td>' . $object->multilangs[$key]["description"] . '</td></tr>';
            print '</table>';
            print '</div>';

            print '<br>';
        }
    }
    if (!$cnt_trans && $action != 'create') {
        print '<div class="opacitymedium">' . $langs->trans('NoTranslation') . '</div>';
    }
}


/*
 * Form to add a new translation
 */

if ($action == 'create' && $user->hasRight('adherent', 'configurer')) {
    //WYSIWYG Editor
    require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';

    print '<br>';
    print '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="vadd">';
    print '<input type="hidden" name="rowid" value="' . GETPOSTINT("rowid") . '">';

    print dol_get_fiche_head();

    print '<table class="border centpercent">';
    print '<tr><td class="tdtop titlefieldcreate fieldrequired">' . $langs->trans('Language') . '</td><td>';
    print $formadmin->select_language('', 'forcelangprod', 0, $object->multilangs, 1);
    print '</td></tr>';
    print '<tr><td class="tdtop fieldrequired">' . $langs->trans('Label') . '</td><td><input name="libelle" class="minwidth300" value="' . dol_escape_htmltag(GETPOST("libelle", 'alphanohtml')) . '"></td></tr>';
    print '<tr><td class="tdtop">' . $langs->trans('Description') . '</td><td>';
    $doleditor = new DolEditor('desc', '', '', 160, 'dolibarr_notes', '', false, true, isModEnabled('fckeditor'), ROWS_3, '90%');
    $doleditor->Create();
    print '</td></tr>';

    print '</table>';

    print dol_get_fiche_end();

    print $form->buttonsSaveCancel();

    print '</form>';

    print '<br>';
}

// End of page
llxFooter();
