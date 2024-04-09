<?php

$form = new Form($db);

$help_url = 'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros|DE:Modul_Mitglieder';

llxHeader('', $langs->trans("MembersSetup"), $help_url);


$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans("MembersSetup"), $linkback, 'title_setup');


$head = member_admin_prepare_head();

print dol_get_fiche_head($head, 'general', $langs->trans("Members"), -1, 'user');

$dirModMember = array_merge(['/core/modules/member/'], $conf->modules_parts['member']);
foreach ($conf->modules_parts['models'] as $mo) {
    //Add more models
    $dirModMember[] = $mo . 'core/modules/member/';
}

// Module to manage customer/supplier code

print load_fiche_titre($langs->trans("MemberCodeChecker"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">' . "\n";
print '<tr class="liste_titre">' . "\n";
print '  <td>' . $langs->trans("Name") . '</td>';
print '  <td>' . $langs->trans("Description") . '</td>';
print '  <td>' . $langs->trans("Example") . '</td>';
print '  <td class="center" width="80">' . $langs->trans("Status") . '</td>';
print '  <td class="center" width="60">' . $langs->trans("ShortInfo") . '</td>';
print "</tr>\n";

$arrayofmodules = [];

foreach ($dirModMember as $dirroot) {
    $dir = dol_buildpath($dirroot, 0);

    $handle = @opendir($dir);
    if (is_resource($handle)) {
        // Loop on each module find in opened directory
        while (($file = readdir($handle)) !== false) {
            // module filename has to start with mod_member_
            if (substr($file, 0, 11) == 'mod_member_' && substr($file, -3) == 'php') {
                $file = substr($file, 0, dol_strlen($file) - 4);
                try {
                    dol_include_once($dirroot . $file . '.php');
                } catch (Exception $e) {
                    dol_syslog($e->getMessage(), LOG_ERR);
                    continue;
                }
                $modCodeMember = new $file();
                // Show modules according to features level
                if ($modCodeMember->version == 'development' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
                    continue;
                }
                if ($modCodeMember->version == 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1) {
                    continue;
                }

                $arrayofmodules[$file] = $modCodeMember;
            }
        }
        closedir($handle);
    }
}

$arrayofmodules = dol_sort_array($arrayofmodules, 'position');

foreach ($arrayofmodules as $file => $modCodeMember) {
    print '<tr class="oddeven">' . "\n";
    print '<td width="140">' . $modCodeMember->name . '</td>' . "\n";
    print '<td>' . $modCodeMember->info($langs) . '</td>' . "\n";
    print '<td class="nowrap">' . $modCodeMember->getExample($langs) . '</td>' . "\n";

    if (getDolGlobalString('MEMBER_CODEMEMBER_ADDON') == "$file") {
        print '<td class="center">' . "\n";
        print img_picto($langs->trans("Activated"), 'switch_on');
        print "</td>\n";
    } else {
        $disabled = (isModEnabled('multicompany') && (is_object($mc) && !empty($mc->sharings['referent']) && $mc->sharings['referent'] != $conf->entity) ? true : false);
        print '<td class="center">';
        if (!$disabled) {
            print '<a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?action=setcodemember&token=' . newToken() . '&value=' . urlencode($file) . '">';
        }
        print img_picto($langs->trans("Disabled"), 'switch_off');
        if (!$disabled) {
            print '</a>';
        }
        print '</td>';
    }

    print '<td class="center">';
    $s = $modCodeMember->getToolTip($langs, null, -1);
    print $form->textwithpicto('', $s, 1);
    print '</td>';

    print '</tr>';
}
print '</table>';
print '</div>';

print "<br>";

print '<form action="' . $_SERVER['PHP_SELF'] . '" method="POST">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="updatemainoptions">';


// Main options

print load_fiche_titre($langs->trans("MemberMainOptions"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td>' . $langs->trans("Value") . '</td>';
print "</tr>\n";

// Start date of new membership
$startpoint = [];
$startpoint[0] = $langs->trans("SubscriptionPayment");
$startpoint["m"] = $langs->trans("Month");
$startpoint["Y"] = $langs->trans("Year");
print '<tr class="oddeven drag" id="startfirstdayof"><td>';
print $langs->trans("MemberSubscriptionStartFirstDayOf");
print '</td><td>';
$startfirstdayof = !getDolGlobalString('MEMBER_SUBSCRIPTION_START_FIRST_DAY_OF') ? 0 : getDolGlobalString('MEMBER_SUBSCRIPTION_START_FIRST_DAY_OF');
print $form->selectarray("MEMBER_SUBSCRIPTION_START_FIRST_DAY_OF", $startpoint, $startfirstdayof, 0);
print "</td></tr>\n";

// Delay to start the new membership ([+/-][0-99][Y/m/d], for instance, with "+4m", the subscription will start in 4 month.)
print '<tr class="oddeven drag" id="startfirstdayof"><td>';
print $langs->trans("MemberSubscriptionStartAfter");
print '</td><td>';
print '<input type="text" class="right width50" id="MEMBER_SUBSCRIPTION_START_AFTER" name="MEMBER_SUBSCRIPTION_START_AFTER" value="' . getDolGlobalString('MEMBER_SUBSCRIPTION_START_AFTER') . '">';
print "</td></tr>\n";

// Mail required for members
print '<tr class="oddeven"><td>' . $langs->trans("AdherentMailRequired") . '</td><td>';
print $form->selectyesno('ADHERENT_MAIL_REQUIRED', getDolGlobalInt('ADHERENT_MAIL_REQUIRED'), 1);
print "</td></tr>\n";

// Login/Pass required for members
print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("AdherentLoginRequired"), $langs->trans("AdherentLoginRequiredDesc"));
print '</td><td>';
print $form->selectyesno('ADHERENT_LOGIN_NOT_REQUIRED', (getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED') ? 0 : 1), 1);
print "</td></tr>\n";

// Send mail information is on by default
print '<tr class="oddeven"><td>' . $langs->trans("MemberSendInformationByMailByDefault") . '</td><td>';
print $form->selectyesno('ADHERENT_DEFAULT_SENDINFOBYMAIL', getDolGlobalInt('ADHERENT_DEFAULT_SENDINFOBYMAIL', 0), 1);
print "</td></tr>\n";

// Create an external user login for each new member subscription validated
print '<tr class="oddeven"><td>' . $langs->trans("MemberCreateAnExternalUserForSubscriptionValidated") . '</td><td>';
print $form->selectyesno('ADHERENT_CREATE_EXTERNAL_USER_LOGIN', getDolGlobalInt('ADHERENT_CREATE_EXTERNAL_USER_LOGIN', 0), 1);
print "</td></tr>\n";

// Create an external user login for each new member subscription validated
$linkofpubliclist = DOL_MAIN_URL_ROOT . '/public/members/public_list.php' . ((isModEnabled('multicompany')) ? '?entity=' . ((int) $conf->entity) : '');
print '<tr class="oddeven"><td>' . $langs->trans("Public", getDolGlobalString('MAIN_INFO_SOCIETE_NOM'), $linkofpubliclist) . '</td><td>';
print $form->selectyesno('MEMBER_PUBLIC_ENABLED', getDolGlobalInt('MEMBER_PUBLIC_ENABLED', 0), 1);
print "</td></tr>\n";

// Allow members to change type on renewal forms
/* To test during next beta
print '<tr class="oddeven"><td>'.$langs->trans("MemberAllowchangeOfType").'</td><td>';
print $form->selectyesno('MEMBER_ALLOW_CHANGE_OF_TYPE', (getDolGlobalInt('MEMBER_ALLOW_CHANGE_OF_TYPE') ? 0 : 1), 1);
print "</td></tr>\n";
*/

// Insert subscription into bank account
print '<tr class="oddeven"><td>' . $langs->trans("MoreActionsOnSubscription") . '</td>';
$arraychoices = ['0' => $langs->trans("None")];
if (isModEnabled("bank")) {
    $arraychoices['bankdirect'] = $langs->trans("MoreActionBankDirect");
}
if (isModEnabled("bank") && isModEnabled("societe") && isModEnabled('invoice')) {
    $arraychoices['invoiceonly'] = $langs->trans("MoreActionInvoiceOnly");
}
if (isModEnabled("bank") && isModEnabled("societe") && isModEnabled('invoice')) {
    $arraychoices['bankviainvoice'] = $langs->trans("MoreActionBankViaInvoice");
}
print '<td>';
print $form->selectarray('ADHERENT_BANK_USE', $arraychoices, getDolGlobalString('ADHERENT_BANK_USE'), 0);
if (getDolGlobalString('ADHERENT_BANK_USE') == 'bankdirect' || getDolGlobalString('ADHERENT_BANK_USE') == 'bankviainvoice') {
    print '<br><div style="padding-top: 5px;"><span class="opacitymedium">' . $langs->trans("ABankAccountMustBeDefinedOnPaymentModeSetup") . '</span></div>';
}
print '</td>';
print "</tr>\n";

// Use vat for invoice creation
if (isModEnabled('invoice')) {
    print '<tr class="oddeven"><td>' . $langs->trans("VATToUseForSubscriptions") . '</td>';
    if (isModEnabled("bank")) {
        print '<td>';
        print $form->selectarray('ADHERENT_VAT_FOR_SUBSCRIPTIONS', ['0' => $langs->trans("NoVatOnSubscription"), 'defaultforfoundationcountry' => $langs->trans("Default")], getDolGlobalString('ADHERENT_VAT_FOR_SUBSCRIPTIONS', '0'), 0);
        print '</td>';
    } else {
        print '<td class="right">';
        print $langs->trans("WarningModuleNotActive", $langs->transnoentities("Module85Name"));
        print '</td>';
    }
    print "</tr>\n";

    if (isModEnabled("product") || isModEnabled("service")) {
        print '<tr class="oddeven"><td>' . $langs->trans("ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS") . '</td>';
        print '<td>';
        $selected = getDolGlobalString('ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS');
        print img_picto('', 'product', 'class="pictofixedwidth"');
        $form->select_produits($selected, 'ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS', '', 0);
        print '</td>';
    }
    print "</tr>\n";
}

print '</table>';
print '</div>';

print '<div class="center">';
print '<input type="submit" class="button" value="' . $langs->trans("Update") . '" name="Button">';
print '</div>';

print '</form>';


print '<br>';


// Document templates for documents generated from member record

$dirmodels = array_merge(['/'], (array) $conf->modules_parts['models']);

// Defined model definition table
$def = [];
$sql = "SELECT nom as name";
$sql .= " FROM " . MAIN_DB_PREFIX . "document_model";
$sql .= " WHERE type = '" . $db->escape($type) . "'";
$sql .= " AND entity = " . $conf->entity;
$resql = $db->query($sql);
if ($resql) {
    $i = 0;
    $num_rows = $db->num_rows($resql);
    while ($i < $num_rows) {
        $obj = $db->fetch_object($resql);
        array_push($def, $obj->name);
        $i++;
    }
} else {
    dol_print_error($db);
}


print load_fiche_titre($langs->trans("MembersDocModules"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td align="center" width="60">' . $langs->trans("Status") . "</td>\n";
print '<td align="center" width="60">' . $langs->trans("Default") . "</td>\n";
print '<td align="center" width="80">' . $langs->trans("ShortInfo") . '</td>';
print '<td align="center" width="80">' . $langs->trans("Preview") . '</td>';
print "</tr>\n";

clearstatcache();

foreach ($dirmodels as $reldir) {
    foreach (['', '/doc'] as $valdir) {
        $dir = dol_buildpath($reldir . "core/modules/member" . $valdir);
        if (is_dir($dir)) {
            $handle = opendir($dir);
            if (is_resource($handle)) {
                $filelist = [];
                while (($file = readdir($handle)) !== false) {
                    $filelist[] = $file;
                }
                closedir($handle);
                arsort($filelist);
                foreach ($filelist as $file) {
                    if (preg_match('/\.class\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file)) {
                        if (file_exists($dir . '/' . $file)) {
                            $name = substr($file, 4, dol_strlen($file) - 14);
                            $classname = substr($file, 0, dol_strlen($file) - 10);

                            require_once $dir . '/' . $file;
                            $module = new $classname($db);

                            $modulequalified = 1;
                            if ($module->version == 'development' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
                                $modulequalified = 0;
                            }
                            if ($module->version == 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1) {
                                $modulequalified = 0;
                            }

                            if ($modulequalified) {
                                print '<tr class="oddeven"><td width="100">';
                                print(empty($module->name) ? $name : $module->name);
                                print "</td><td>\n";
                                if (method_exists($module, 'info')) {
                                    print $module->info($langs);
                                } else {
                                    print $module->description;
                                }
                                print '</td>';

                                // Active
                                if (in_array($name, $def)) {
                                    print '<td class="center">' . "\n";
                                    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_default&token=' . newToken() . '&value=' . $name . '">';
                                    print img_picto($langs->trans("Enabled"), 'switch_on');
                                    print '</a>';
                                    print '</td>';
                                } else {
                                    print '<td class="center">' . "\n";
                                    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_default&token=' . newToken() . '&value=' . $name . '&scandir=' . (!empty($module->scandir) ? $module->scandir : '') . '&label=' . urlencode($module->name) . '">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
                                    print "</td>";
                                }

                                // Default
                                print '<td class="center">';
                                if (getDolGlobalString('MEMBER_ADDON_PDF_ODT') == $name) {
                                    print img_picto($langs->trans("Default"), 'on');
                                } else {
                                    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=setdoc&token=' . newToken() . '&value=' . $name . '&scandir=' . (!empty($module->scandir) ? $module->scandir : '') . '&label=' . urlencode($module->name) . '" alt="' . $langs->trans("Default") . '">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
                                }
                                print '</td>';

                                // Info
                                $htmltooltip = '' . $langs->trans("Name") . ': ' . $module->name;
                                $htmltooltip .= '<br>' . $langs->trans("Type") . ': ' . ($module->type ? $module->type : $langs->trans("Unknown"));
                                if ($module->type == 'pdf') {
                                    $htmltooltip .= '<br>' . $langs->trans("Width") . '/' . $langs->trans("Height") . ': ' . $module->page_largeur . '/' . $module->page_hauteur;
                                }
                                $htmltooltip .= '<br><br><u>' . $langs->trans("FeaturesSupported") . ':</u>';
                                $htmltooltip .= '<br>' . $langs->trans("Logo") . ': ' . yn(!empty($module->option_logo) ? $module->option_logo : 0, 1, 1);
                                $htmltooltip .= '<br>' . $langs->trans("MultiLanguage") . ': ' . yn(!empty($module->option_multilang) ? $module->option_multilang : 0, 1, 1);


                                print '<td class="center">';
                                print $form->textwithpicto('', $htmltooltip, 1, 0);
                                print '</td>';

                                // Preview
                                print '<td class="center">';
                                if ($module->type == 'pdf') {
                                    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=specimen&module=' . $name . '">' . img_object($langs->trans("Preview"), 'contract') . '</a>';
                                } else {
                                    print img_object($langs->trans("PreviewNotAvailable"), 'generic');
                                }
                                print '</td>';

                                print "</tr>\n";
                            }
                        }
                    }
                }
            }
        }
    }
}

print '</table>';
print '</div>';


// Generation of cards for members

print '<form action="' . $_SERVER['PHP_SELF'] . '" method="POST">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="updatememberscards">';

print load_fiche_titre($langs->trans("MembersCards"), '', '');

$helptext = '*' . $langs->trans("FollowingConstantsWillBeSubstituted") . '<br>';
$helptext .= '__DOL_MAIN_URL_ROOT__, __ID__, __FIRSTNAME__, __LASTNAME__, __FULLNAME__, __LOGIN__, __PASSWORD__, ';
$helptext .= '__COMPANY__, __ADDRESS__, __ZIP__, __TOWN__, __COUNTRY__, __EMAIL__, __BIRTH__, __PHOTO__, __TYPE__, ';
$helptext .= '__YEAR__, __MONTH__, __DAY__';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td>' . $form->textwithpicto($langs->trans("Value"), $helptext, 1, 'help', '', 0, 2, 'idhelptext') . '</td>';
print "</tr>\n";

// Format of cards page
print '<tr class="oddeven"><td>' . $langs->trans("DescADHERENT_CARD_TYPE") . '</td><td>';

require_once DOL_DOCUMENT_ROOT . '/core/lib/format_cards.lib.php'; // List of possible labels (defined into $_Avery_Labels variable set into format_cards.lib.php)
$arrayoflabels = [];
foreach (array_keys($_Avery_Labels) as $codecards) {
    $arrayoflabels[$codecards] = $_Avery_Labels[$codecards]['name'];
}
print $form->selectarray('ADHERENT_CARD_TYPE', $arrayoflabels, getDolGlobalString('ADHERENT_CARD_TYPE') ? getDolGlobalString('ADHERENT_CARD_TYPE') : 'CARD', 1, 0, 0);

print "</td></tr>\n";

// Text printed on top of member cards
print '<tr class="oddeven"><td>' . $langs->trans("DescADHERENT_CARD_HEADER_TEXT") . '</td><td>';
print '<input type="text" class="flat minwidth300" name="ADHERENT_CARD_HEADER_TEXT" value="' . dol_escape_htmltag(getDolGlobalString('ADHERENT_CARD_HEADER_TEXT')) . '">';
print "</td></tr>\n";

// Text printed on member cards (align on left)
print '<tr class="oddeven"><td>' . $langs->trans("DescADHERENT_CARD_TEXT") . '</td><td>';
print '<textarea class="flat" name="ADHERENT_CARD_TEXT" cols="50" rows="5" wrap="soft">' . "\n";
print getDolGlobalString('ADHERENT_CARD_TEXT');
print '</textarea>';
print "</td></tr>\n";

// Text printed on member cards (align on right)
print '<tr class="oddeven"><td>' . $langs->trans("DescADHERENT_CARD_TEXT_RIGHT") . '</td><td>';
print '<textarea class="flat" name="ADHERENT_CARD_TEXT_RIGHT" cols="50" rows="5" wrap="soft">' . "\n";
print getDolGlobalString('ADHERENT_CARD_TEXT_RIGHT');
print '</textarea>';
print "</td></tr>\n";

// Text printed on bottom of member cards
print '<tr class="oddeven"><td>' . $langs->trans("DescADHERENT_CARD_FOOTER_TEXT") . '</td><td>';
print '<input type="text" class="flat minwidth300" name="ADHERENT_CARD_FOOTER_TEXT" value="' . dol_escape_htmltag(getDolGlobalString('ADHERENT_CARD_FOOTER_TEXT')) . '">';
print "</td></tr>\n";

print '</table>';
print '</div>';

print '<div class="center">';
print '<input type="submit" class="button" value="' . $langs->trans("Update") . '" name="Button">';
print '</div>';

print '</form>';

print '<br>';

// Membership address sheet

print '<form action="' . $_SERVER['PHP_SELF'] . '" method="POST">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="updatememberstickets">';

print load_fiche_titre($langs->trans("MembersTickets"), '', '');

$helptext = '*' . $langs->trans("FollowingConstantsWillBeSubstituted") . '<br>';
$helptext .= '__DOL_MAIN_URL_ROOT__, __ID__, __FIRSTNAME__, __LASTNAME__, __FULLNAME__, __LOGIN__, __PASSWORD__, ';
$helptext .= '__COMPANY__, __ADDRESS__, __ZIP__, __TOWN__, __COUNTRY__, __EMAIL__, __BIRTH__, __PHOTO__, __TYPE__, ';
$helptext .= '__YEAR__, __MONTH__, __DAY__';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td>' . $form->textwithpicto($langs->trans("Value"), $helptext, 1, 'help', '', 0, 2, 'idhelptext') . '</td>';
print "</tr>\n";

// Format of labels page
print '<tr class="oddeven"><td>' . $langs->trans("DescADHERENT_ETIQUETTE_TYPE") . '</td><td>';

require_once DOL_DOCUMENT_ROOT . '/core/lib/format_cards.lib.php'; // List of possible labels (defined into $_Avery_Labels variable set into format_cards.lib.php)
$arrayoflabels = [];
foreach (array_keys($_Avery_Labels) as $codecards) {
    $arrayoflabels[$codecards] = $_Avery_Labels[$codecards]['name'];
}
print $form->selectarray('ADHERENT_ETIQUETTE_TYPE', $arrayoflabels, getDolGlobalString('ADHERENT_ETIQUETTE_TYPE') ? getDolGlobalString('ADHERENT_ETIQUETTE_TYPE') : 'CARD', 1, 0, 0);

print "</td></tr>\n";

// Text printed on member address sheets
print '<tr class="oddeven"><td>' . $langs->trans("DescADHERENT_ETIQUETTE_TEXT") . '</td><td>';
print '<textarea class="flat" name="ADHERENT_ETIQUETTE_TEXT" cols="50" rows="5" wrap="soft">' . "\n";
print getDolGlobalString('ADHERENT_ETIQUETTE_TEXT');
print '</textarea>';
print "</td></tr>\n";

print '</table>';
print '</div>';

print '<div class="center">';
print '<input type="submit" class="button" value="' . $langs->trans("Update") . '" name="Button">';
print '</div>';

print '</form>';

print '<br>';

print "<br>";

print dol_get_fiche_end();

// End of page
llxFooter();
