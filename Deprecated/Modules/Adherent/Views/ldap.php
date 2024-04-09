<?php

use DoliModules\Adherent\Model\AdherentType;

$form = new Form($db);

llxHeader('', $langs->trans("Member"), 'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros|DE:Modul_Mitglieder');

$head = member_prepare_head($object);

print dol_get_fiche_head($head, 'ldap', $langs->trans("Member"), 0, 'user');

$linkback = '<a href="' . DOL_URL_ROOT . '/adherents/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

dol_banner_tab($object, 'rowid', $linkback);

print '<div class="fichecenter">';

print '<div class="underbanner clearboth"></div>';
print '<table class="border centpercent tableforfield">';

// Login
print '<tr><td class="titlefield">' . $langs->trans("Login") . ' / ' . $langs->trans("Id") . '</td><td class="valeur">' . dol_escape_htmltag($object->login) . '&nbsp;</td></tr>';

// If there is a link to the unencrypted password, we show the value in database here so we can compare because it is shown nowhere else
// This is for very old situation. Password are now encrypted and $object->pass is empty.
if (getDolGlobalString('LDAP_MEMBER_FIELD_PASSWORD')) {
    print '<tr><td>' . $langs->trans("LDAPFieldPasswordNotCrypted") . '</td>';
    print '<td class="valeur">' . dol_escape_htmltag($object->pass) . '</td>';
    print "</tr>\n";
}

$adht = new AdherentType($db);
$adht->fetch($object->typeid);

// Type
print '<tr><td>' . $langs->trans("Type") . '</td><td class="valeur">' . $adht->getNomUrl(1) . "</td></tr>\n";

// LDAP DN
print '<tr><td>LDAP ' . $langs->trans("LDAPMemberDn") . '</td><td class="valeur">' . getDolGlobalString('LDAP_MEMBER_DN') . "</td></tr>\n";

// LDAP Cle
print '<tr><td>LDAP ' . $langs->trans("LDAPNamingAttribute") . '</td><td class="valeur">' . getDolGlobalString('LDAP_KEY_MEMBERS') . "</td></tr>\n";

// LDAP Server
print '<tr><td>LDAP ' . $langs->trans("Type") . '</td><td class="valeur">' . getDolGlobalString('LDAP_SERVER_TYPE') . "</td></tr>\n";
print '<tr><td>LDAP ' . $langs->trans("Version") . '</td><td class="valeur">' . getDolGlobalString('LDAP_SERVER_PROTOCOLVERSION') . "</td></tr>\n";
print '<tr><td>LDAP ' . $langs->trans("LDAPPrimaryServer") . '</td><td class="valeur">' . getDolGlobalString('LDAP_SERVER_HOST') . "</td></tr>\n";
print '<tr><td>LDAP ' . $langs->trans("LDAPSecondaryServer") . '</td><td class="valeur">' . getDolGlobalString('LDAP_SERVER_HOST_SLAVE') . "</td></tr>\n";
print '<tr><td>LDAP ' . $langs->trans("LDAPServerPort") . '</td><td class="valeur">' . getDolGlobalString('LDAP_SERVER_PORT') . "</td></tr>\n";

print '</table>';

print '</div>';

print dol_get_fiche_end();

/*
 * Action bar
 */
print '<div class="tabsAction">';

if (getDolGlobalString('LDAP_MEMBER_ACTIVE') && getDolGlobalString('LDAP_MEMBER_ACTIVE') != Ldap::SYNCHRO_LDAP_TO_DOLIBARR) {
    print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&amp;action=dolibarr2ldap">' . $langs->trans("ForceSynchronize") . '</a></div>';
}

print "</div>\n";

if (getDolGlobalString('LDAP_MEMBER_ACTIVE') && getDolGlobalString('LDAP_MEMBER_ACTIVE') != Ldap::SYNCHRO_LDAP_TO_DOLIBARR) {
    print "<br>\n";
}


// Affichage attributes LDAP
print load_fiche_titre($langs->trans("LDAPInformationsForThisMember"));

print '<table width="100%" class="noborder">';

print '<tr class="liste_titre">';
print '<td>' . $langs->trans("LDAPAttributes") . '</td>';
print '<td>' . $langs->trans("Value") . '</td>';
print '</tr>';

// Lecture LDAP
$ldap = new Ldap();
$result = $ldap->connectBind();
if ($result > 0) {
    $info = $object->_load_ldap_info();
    $dn = $object->_load_ldap_dn($info, 1);
    $search = "(" . $object->_load_ldap_dn($info, 2) . ")";

    if (empty($dn)) {
        $langs->load("errors");
        print '<tr class="oddeven"><td colspan="2"><span class="error">' . $langs->trans("ErrorModuleSetupNotComplete", $langs->transnoentitiesnoconv("Member")) . '</span></td></tr>';
    } else {
        $records = $ldap->getAttribute($dn, $search);

        //print_r($records);

        // Show tree
        if (((!is_numeric($records)) || $records != 0) && (!isset($records['count']) || $records['count'] > 0)) {
            if (!is_array($records)) {
                print '<tr class="oddeven"><td colspan="2"><span class="error">' . $langs->trans("ErrorFailedToReadLDAP") . '</span></td></tr>';
            } else {
                $result = show_ldap_content($records, 0, $records['count'], true);
            }
        } else {
            print '<tr class="oddeven"><td colspan="2">' . $langs->trans("LDAPRecordNotFound") . ' (dn=' . dol_escape_htmltag($dn) . ' - search=' . dol_escape_htmltag($search) . ')</td></tr>';
        }
    }

    $ldap->unbind();
} else {
    setEventMessages($ldap->error, $ldap->errors, 'errors');
}


print '</table>';

// End of page
llxFooter();
