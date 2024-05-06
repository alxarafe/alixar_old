<?php

llxHeader();

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans("BookmarkSetup"), $linkback, 'title_setup');

print $langs->trans("BookmarkDesc") . "<br>\n";

print '<br>';
print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="setvalue">';

print '<table summary="bookmarklist" class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Value") . '</td>';
print "</tr>\n";

print '<tr class="oddeven"><td>';
print $langs->trans("NbOfBoomarkToShow") . '</td><td>';
print '<input size="3" type="text" name="BOOKMARKS_SHOW_IN_MENU" value="' . getDolGlobalString('BOOKMARKS_SHOW_IN_MENU') . '">';
print '</td></tr>';
print '</table><br><div class="center"><input type="submit" class="button button-edit" value="' . $langs->trans("Modify") . '"></div></form>';

// End of page
llxFooter();
