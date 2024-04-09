<?php


llxHeader();

$form = new Form($db);

$head = member_type_prepare_head($object);

print dol_get_fiche_head($head, 'ldap', $langs->trans("MemberType"), -1, 'group');

$linkback = '<a href="' . DOL_URL_ROOT . '/adherents/type.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

dol_banner_tab($object, 'rowid', $linkback);

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<table class="border centpercent">';

// LDAP DN
print '<tr><td>LDAP ' . $langs->trans("LDAPMemberTypeDn") . '</td><td class="valeur">' . getDolGlobalString('LDAP_MEMBER_TYPE_DN') . "</td></tr>\n";

// LDAP Cle
print '<tr><td>LDAP ' . $langs->trans("LDAPNamingAttribute") . '</td><td class="valeur">' . getDolGlobalString('LDAP_KEY_MEMBERS_TYPES') . "</td></tr>\n";

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

if (getDolGlobalInt('LDAP_MEMBER_TYPE_ACTIVE') === Ldap::SYNCHRO_DOLIBARR_TO_LDAP) {
    print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?rowid=' . $object->id . '&action=dolibarr2ldap">' . $langs->trans("ForceSynchronize") . '</a>';
}

print "</div>\n";

if (getDolGlobalInt('LDAP_MEMBER_TYPE_ACTIVE') === Ldap::SYNCHRO_DOLIBARR_TO_LDAP) {
    print "<br>\n";
}


// Display LDAP attributes
print load_fiche_titre($langs->trans("LDAPInformationsForThisMemberType"));

print '<table width="100%" class="noborder">';

print '<tr class="liste_titre">';
print '<td>' . $langs->trans("LDAPAttributes") . '</td>';
print '<td>' . $langs->trans("Value") . '</td>';
print '</tr>';

// LDAP reading
$ldap = new Ldap();
$result = $ldap->connectBind();
if ($result > 0) {
    $info = $object->_load_ldap_info();
    $dn = $object->_load_ldap_dn($info, 1);
    $search = "(" . $object->_load_ldap_dn($info, 2) . ")";

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

    $ldap->unbind();
} else {
    setEventMessages($ldap->error, $ldap->errors, 'errors');
}

print '</table>';

// End of page
llxFooter();
