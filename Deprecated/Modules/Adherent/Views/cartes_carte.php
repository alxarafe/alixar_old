<?php

use DoliCore\Lib\AveryLabels;

$form = new Form($db);

llxHeader('', $langs->trans("MembersCards"));

print load_fiche_titre($langs->trans("LinkToGeneratedPages"), '', $adherentstatic->picto);

print '<span class="opacitymedium">' . $langs->trans("LinkToGeneratedPagesDesc") . '</span><br>';
print '<br>';

dol_htmloutput_errors($mesg);

print '<br>';

print img_picto('', 'card') . ' ' . $langs->trans("DocForAllMembersCards", getDolGlobalString('ADHERENT_CARD_TYPE', $langs->transnoentitiesnoconv("None"))) . ' ';
print '<form action="' . $_SERVER['PHP_SELF'] . '" method="POST">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="foruserid" value="all">';
print '<input type="hidden" name="mode" value="card">';
print '<input type="hidden" name="action" value="builddoc">';
print $langs->trans("DescADHERENT_CARD_TYPE") . ' ';
// List of possible labels (defined into $_Avery_Labels variable set into format_cards.lib.php)
$arrayoflabels = [];
$_Avery_Labels = AveryLabels::getAveryLables();
foreach (array_keys($_Avery_Labels) as $codecards) {
    $arrayoflabels[$codecards] = $_Avery_Labels[$codecards]['name'];
}
asort($arrayoflabels);
print $form->selectarray('modelcard', $arrayoflabels, (GETPOST('modelcard') ? GETPOST('modelcard') : getDolGlobalString('ADHERENT_CARD_TYPE')), 1, 0, 0, '', 0, 0, 0, '', '', 1);
print '<br><input type="submit" class="button small" value="' . $langs->trans("BuildDoc") . '">';
print '</form>';

print '<br><br>';

print img_picto('', 'card') . ' ' . $langs->trans("DocForOneMemberCards", getDolGlobalString('ADHERENT_CARD_TYPE', $langs->transnoentitiesnoconv("None"))) . ' ';
print '<form action="' . $_SERVER['PHP_SELF'] . '" method="POST">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="mode" value="cardlogin">';
print '<input type="hidden" name="action" value="builddoc">';
print $langs->trans("DescADHERENT_CARD_TYPE") . ' ';
// List of possible labels (defined into $_Avery_Labels variable set into format_cards.lib.php)
$arrayoflabels = [];
foreach (array_keys($_Avery_Labels) as $codecards) {
    $arrayoflabels[$codecards] = $_Avery_Labels[$codecards]['name'];
}
asort($arrayoflabels);
print $form->selectarray('model', $arrayoflabels, (GETPOST('model') ? GETPOST('model') : getDolGlobalString('ADHERENT_CARD_TYPE')), 1, 0, 0, '', 0, 0, 0, '', '', 1);
print '<br>' . $langs->trans("Login") . ': <input class="with100" type="text" name="foruserlogin" value="' . GETPOST('foruserlogin') . '">';
print '<br><input type="submit" class="button small" value="' . $langs->trans("BuildDoc") . '">';
print '</form>';

print '<br><br>';

print img_picto('', 'card') . ' ' . $langs->trans("DocForLabels", getDolGlobalString('ADHERENT_ETIQUETTE_TYPE')) . ' ';
print '<form action="' . $_SERVER['PHP_SELF'] . '" method="POST">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="mode" value="label">';
print '<input type="hidden" name="action" value="builddoc">';
print $langs->trans("DescADHERENT_ETIQUETTE_TYPE") . ' ';
// List of possible labels (defined into $_Avery_Labels variable set into format_cards.lib.php)
$arrayoflabels = [];
foreach (array_keys($_Avery_Labels) as $codecards) {
    $arrayoflabels[$codecards] = $_Avery_Labels[$codecards]['name'];
}
asort($arrayoflabels);
print $form->selectarray('modellabel', $arrayoflabels, (GETPOST('modellabel') ? GETPOST('modellabel') : getDolGlobalString('ADHERENT_ETIQUETTE_TYPE')), 1, 0, 0, '', 0, 0, 0, '', '', 1);
print '<br><input type="submit" class="button small" value="' . $langs->trans("BuildDoc") . '">';
print '</form>';

// End of page
llxFooter();
