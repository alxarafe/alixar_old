<?php


$form = new Form($db);

$help_url = 'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros|DE:Modul_Mitglieder';

llxHeader('', $langs->trans("MembersSetup"), $help_url);


$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans("MembersSetup"), $linkback, 'title_setup');


$head = member_admin_prepare_head();

print dol_get_fiche_head($head, 'emails', $langs->trans("Members"), -1, 'user');

// TODO Use global form
print '<form action="' . $_SERVER['PHP_SELF'] . '" method="POST">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="updateall">';

form_constantes($constantes, 3, '');

print '<div class="center"><input type="submit" class="button" value="' . $langs->trans("Update") . '" name="update"></div>';
print '</form>';

print dol_get_fiche_end();

// End of page
llxFooter();
