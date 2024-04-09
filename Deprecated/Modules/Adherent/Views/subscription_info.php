<?php

$form = new Form($db);

llxHeader();

$object = new Subscription($db);
$result = $object->fetch($rowid);

$head = subscription_prepare_head($object);

print dol_get_fiche_head($head, 'info', $langs->trans("Subscription"), -1, 'payment');

$linkback = '<a href="' . DOL_URL_ROOT . '/adherents/subscription/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

dol_banner_tab($object, 'rowid', $linkback, 1);

print '<div class="fichecenter">';

print '<div class="underbanner clearboth"></div>';

print '<br>';

$object->info($rowid);

print '<table width="100%"><tr><td>';
dol_print_object_info($object);
print '</td></tr></table>';

print '</div>';


print dol_get_fiche_end();

// End of page
llxFooter();
