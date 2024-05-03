<?php


use DoliCore\Form\Form;
use DoliModules\BlockedLog\Model\BlockedLog;

$form = new Form($db);
$block_static = new BlockedLog($db);
$block_static->loadTrackedEvents();

$title = $langs->trans("BlockedLogSetup");
$help_url = "EN:Module_Unalterable_Archives_-_Logs|FR:Module_Archives_-_Logs_Inaltérable";

llxHeader('', $title, $help_url);

$linkback = '';
if ($withtab) {
    $linkback = '<a href="' . ($backtopage ? $backtopage : DOL_URL_ROOT . '/admin/modules.php') . '">' . $langs->trans("BackToModuleList") . '</a>';
}

print load_fiche_titre($langs->trans("ModuleSetup") . ' ' . $langs->trans('BlockedLog'), $linkback);

if ($withtab) {
    $head = blockedlogadmin_prepare_head();
    print dol_get_fiche_head($head, 'blockedlog', '', -1);
}


print '<span class="opacitymedium">' . $langs->trans("BlockedLogDesc") . "</span><br>\n";

print '<br>';

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Key") . '</td>';
print '<td>' . $langs->trans("Value") . '</td>';
print "</tr>\n";

print '<tr class="oddeven">';
print '<td class="titlefield">';
print $langs->trans("CompanyInitialKey") . '</td><td>';
print $block_static->getSignature();
print '</td></tr>';

if (getDolGlobalString('BLOCKEDLOG_USE_REMOTE_AUTHORITY')) {
    // Example with a yes / no select
    print '<tr class="oddeven">';
    print '<td>' . $langs->trans("BlockedLogAuthorityUrl") . img_info($langs->trans('BlockedLogAuthorityNeededToStoreYouFingerprintsInNonAlterableRemote')) . '</td>';
    print '<td class="right" width="300">';

    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="set_BLOCKEDLOG_AUTHORITY_URL">';
    print '<input type="hidden" name="withtab" value="' . $withtab . '">';
    print '<input type="text" name="BLOCKEDLOG_AUTHORITY_URL" value="' . getDolGlobalString('BLOCKEDLOG_AUTHORITY_URL') . '" size="40" />';
    print '<input type="submit" class="button button-edit" value="' . $langs->trans("Modify") . '">';
    print '</form>';

    print '</td></tr>';
}

print '<tr class="oddeven">';
print '<td>' . $langs->trans("BlockedLogDisableNotAllowedForCountry") . '</td>';
print '<td>';

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="set_BLOCKEDLOG_DISABLE_NOT_ALLOWED_FOR_COUNTRY">';
print '<input type="hidden" name="withtab" value="' . $withtab . '">';

$sql = "SELECT rowid, code as code_iso, code_iso as code_iso3, label, favorite";
$sql .= " FROM " . MAIN_DB_PREFIX . "c_country";
$sql .= " WHERE active > 0";

$countryArray = [];
$resql = $db->query($sql);
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $countryArray[$obj->code_iso] = ($obj->code_iso && $langs->transnoentitiesnoconv("Country" . $obj->code_iso) != "Country" . $obj->code_iso ? $langs->transnoentitiesnoconv("Country" . $obj->code_iso) : ($obj->label != '-' ? $obj->label : ''));
    }
}

$selected = !getDolGlobalString('BLOCKEDLOG_DISABLE_NOT_ALLOWED_FOR_COUNTRY') ? [] : explode(',', getDolGlobalString('BLOCKEDLOG_DISABLE_NOT_ALLOWED_FOR_COUNTRY'));

print $form->multiselectarray('BLOCKEDLOG_DISABLE_NOT_ALLOWED_FOR_COUNTRY', $countryArray, $selected);
print '<input type="submit" class="button button-edit" value="' . $langs->trans("Modify") . '">';
print '</form>';

print '</td>';


print '<tr class="oddeven">';
print '<td class="titlefield">';
print $langs->trans("ListOfTrackedEvents") . '</td><td>';
$arrayoftrackedevents = $block_static->trackedevents;
foreach ($arrayoftrackedevents as $key => $val) {
    print $key . ' - ' . $langs->trans($val) . '<br>';
}

print '</td></tr>';

print '</tr>';

print '</table>';
print '</div>';

if ($withtab) {
    print dol_get_fiche_end();
}

print '<br><br>';

// End of page
llxFooter();
