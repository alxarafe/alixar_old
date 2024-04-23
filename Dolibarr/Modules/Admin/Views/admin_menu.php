<?php

global $db;

use DoliCore\Form\Form;
use DoliCore\Form\FormAdmin;

$form = new Form($db);
$formadmin = new FormAdmin($db);

$wikihelp = 'EN:First_setup|FR:Premiers_paramétrages|ES:Primeras_configuraciones';
llxHeader('', $langs->trans("Setup"), $wikihelp);

print load_fiche_titre($langs->trans("Menus"), '', 'title_setup');


$h = 0;

$head = [];
$head[$h][0] = DOL_URL_ROOT . "/admin/menus.php";
$head[$h][1] = $langs->trans("MenuHandlers");
$head[$h][2] = 'handler';
$h++;

$head[$h][0] = DOL_URL_ROOT . "/admin/menus/index.php";
$head[$h][1] = $langs->trans("MenuAdmin");
$head[$h][2] = 'editor';
$h++;

print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update">';

print dol_get_fiche_head($head, 'handler', '', -1);

print '<span class="opacitymedium">' . $langs->trans("MenusDesc") . "</span><br>\n";
print "<br>\n";


clearstatcache();

// Gestionnaires de menu
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td width="35%">' . $langs->trans("Menu") . '</td>';
print '<td>';
print $form->textwithpicto($langs->trans("InternalUsers"), $langs->trans("InternalExternalDesc"));
print '</td>';
print '<td>';
print $form->textwithpicto($langs->trans("ExternalUsers"), $langs->trans("InternalExternalDesc"));
print '</td>';
print '</tr>';

// Menu top
print '<tr class="oddeven"><td>' . $langs->trans("DefaultMenuManager") . '</td>';
print '<td>';
$formadmin->select_menu(!getDolGlobalString('MAIN_MENU_STANDARD_FORCED') ? $conf->global->MAIN_MENU_STANDARD : $conf->global->MAIN_MENU_STANDARD_FORCED, 'MAIN_MENU_STANDARD', $dirstandard, !getDolGlobalString('MAIN_MENU_STANDARD_FORCED') ? '' : ' disabled');
print '</td>';
print '<td>';
$formadmin->select_menu(!getDolGlobalString('MAIN_MENUFRONT_STANDARD_FORCED') ? $conf->global->MAIN_MENUFRONT_STANDARD : $conf->global->MAIN_MENUFRONT_STANDARD_FORCED, 'MAIN_MENUFRONT_STANDARD', $dirstandard, !getDolGlobalString('MAIN_MENUFRONT_STANDARD_FORCED') ? '' : ' disabled');
print '</td>';
print '</tr>';

// Menu smartphone
print '<tr class="oddeven"><td>' . $langs->trans("DefaultMenuSmartphoneManager") . '</td>';
print '<td>';
$formadmin->select_menu(!getDolGlobalString('MAIN_MENU_SMARTPHONE_FORCED') ? $conf->global->MAIN_MENU_SMARTPHONE : $conf->global->MAIN_MENU_SMARTPHONE_FORCED, 'MAIN_MENU_SMARTPHONE', array_merge($dirstandard, $dirsmartphone), !getDolGlobalString('MAIN_MENU_SMARTPHONE_FORCED') ? '' : ' disabled');

if (
    getDolGlobalString('MAIN_MENU_SMARTPHONE_FORCED') && preg_match('/smartphone/', $conf->global->MAIN_MENU_SMARTPHONE_FORCED)
    || (!getDolGlobalString('MAIN_MENU_SMARTPHONE_FORCED') && getDolGlobalString('MAIN_MENU_SMARTPHONE') && preg_match('/smartphone/', $conf->global->MAIN_MENU_SMARTPHONE))
) {
    print ' ' . img_warning($langs->transnoentitiesnoconv("ThisForceAlsoTheme"));
}

print '</td>';
print '<td>';
$formadmin->select_menu(!getDolGlobalString('MAIN_MENUFRONT_SMARTPHONE_FORCED') ? $conf->global->MAIN_MENUFRONT_SMARTPHONE : $conf->global->MAIN_MENUFRONT_SMARTPHONE_FORCED, 'MAIN_MENUFRONT_SMARTPHONE', array_merge($dirstandard, $dirsmartphone), !getDolGlobalString('MAIN_MENUFRONT_SMARTPHONE_FORCED') ? '' : ' disabled');

if (
    getDolGlobalString('MAIN_MENU_SMARTPHONE_FORCED') && preg_match('/smartphone/', $conf->global->MAIN_MENUFRONT_SMARTPHONE_FORCED)
    || (!getDolGlobalString('MAIN_MENUFRONT_SMARTPHONE_FORCED') && getDolGlobalString('MAIN_MENU_SMARTPHONE') && preg_match('/smartphone/', $conf->global->MAIN_MENUFRONT_SMARTPHONE))
) {
    print ' ' . img_warning($langs->transnoentitiesnoconv("ThisForceAlsoTheme"));
}

print '</td>';
print '</tr>';

print '</table>';

print dol_get_fiche_end();

print '<div class="center">';
print '<input class="button button-save" type="submit" name="save" value="' . $langs->trans("Save") . '">';
print '</div>';

print '</form>';

// End of page
llxFooter();
