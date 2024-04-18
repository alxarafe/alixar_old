<?php

use DoliCore\Form\Form;
use DoliModules\Adherent\Model\Adherent;
use DoliModules\Adherent\Model\AdherentType;
use DoliModules\Adherent\Model\Subscription;
use DoliModules\Bank\Model\Account;
use DoliModules\Category\Model\Categorie;

$form = new Form($db);

$now = dol_now();

$title = $langs->trans("Member") . " - " . $langs->trans("Subscriptions");

$help_url = "EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros|DE:Modul_Mitglieder";

llxHeader("", $title, $help_url);


$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER['PHP_SELF']) {
    $param .= '&contextpage=' . urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
    $param .= '&limit=' . ((int) $limit);
}
$param .= '&id=' . $rowid;
if ($optioncss != '') {
    $param .= '&optioncss=' . urlencode($optioncss);
}
// Add $param from extra fields
//include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';


if (!($object->id > 0)) {
    $langs->load("errors");
    print $langs->trans("ErrorRecordNotFound");
}

/*$res = $object->fetch($rowid);
    if ($res < 0) {
        dol_print_error($db, $object->error);
        exit;
    }
*/

$adht->fetch($object->typeid);

$defaultdelay = !empty($adht->duration_value) ? $adht->duration_value : 1;
$defaultdelayunit = !empty($adht->duration_unit) ? $adht->duration_unit : 'y';

$head = member_prepare_head($object);

$rowspan = 10;
if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
    $rowspan++;
}
if (isModEnabled('societe')) {
    $rowspan++;
}

print '<form action="' . $_SERVER['PHP_SELF'] . '" method="POST">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="rowid" value="' . $object->id . '">';

print dol_get_fiche_head($head, 'subscription', $langs->trans("Member"), -1, 'user');

$linkback = '<a href="' . DOL_URL_ROOT . '/adherents/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

$morehtmlref = '<a href="' . DOL_URL_ROOT . '/adherents/vcard.php?id=' . $object->id . '" class="refid">';
$morehtmlref .= img_picto($langs->trans("Download") . ' ' . $langs->trans("VCard"), 'vcard.png', 'class="valignmiddle marginleftonly paddingrightonly"');
$morehtmlref .= '</a>';

dol_banner_tab($object, 'rowid', $linkback, 1, 'rowid', 'ref', $morehtmlref);

print '<div class="fichecenter">';
print '<div class="fichehalfleft">';

print '<div class="underbanner clearboth"></div>';
print '<table class="border centpercent tableforfield">';

// Login
if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
    print '<tr><td class="titlefield">' . $langs->trans("Login") . ' / ' . $langs->trans("Id") . '</td><td class="valeur">' . dol_escape_htmltag($object->login) . '</td></tr>';
}

// Type
print '<tr><td class="titlefield">' . $langs->trans("Type") . '</td>';
print '<td class="valeur">' . $adht->getNomUrl(1) . "</td></tr>\n";

// Morphy
print '<tr><td>' . $langs->trans("MemberNature") . '</td>';
print '<td class="valeur" >' . $object->getmorphylib('', 1) . '</td>';
print '</tr>';

// Company
print '<tr><td>' . $langs->trans("Company") . '</td><td class="valeur">' . dol_escape_htmltag($object->company) . '</td></tr>';

// Civility
print '<tr><td>' . $langs->trans("UserTitle") . '</td><td class="valeur">' . $object->getCivilityLabel() . '</td>';
print '</tr>';

// Password
if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
    print '<tr><td>' . $langs->trans("Password") . '</td><td>';
    if ($object->pass) {
        print preg_replace('/./i', '*', $object->pass);
    } else {
        if ($user->admin) {
            print '<!-- ' . $langs->trans("Crypted") . ': ' . $object->pass_indatabase_crypted . ' -->';
        }
        print '<span class="opacitymedium">' . $langs->trans("Hidden") . '</span>';
    }
    if (!empty($object->pass_indatabase) && empty($object->user_id)) {  // Show warning only for old password still in clear (does not happen anymore)
        $langs->load("errors");
        $htmltext = $langs->trans("WarningPasswordSetWithNoAccount");
        print ' ' . $form->textwithpicto('', $htmltext, 1, 'warning');
    }
    print '</td></tr>';
}

// Date end subscription
print '<tr><td>' . $langs->trans("SubscriptionEndDate") . '</td><td class="valeur">';
if ($object->datefin) {
    print dol_print_date($object->datefin, 'day');
    if ($object->hasDelay()) {
        print " " . img_warning($langs->trans("Late"));
    }
} else {
    if ($object->need_subscription == 0) {
        print $langs->trans("SubscriptionNotNeeded");
    } elseif (!$adht->subscription) {
        print $langs->trans("SubscriptionNotRecorded");
        if (Adherent::STATUS_VALIDATED == $object->statut) {
            print " " . img_warning($langs->trans("Late")); // displays delay Pictogram only if not a draft, not excluded and not resiliated
        }
    } else {
        print $langs->trans("SubscriptionNotReceived");
        if (Adherent::STATUS_VALIDATED == $object->statut) {
            print " " . img_warning($langs->trans("Late")); // displays delay Pictogram only if not a draft, not excluded and not resiliated
        }
    }
}
print '</td></tr>';

print '</table>';

print '</div>';

print '<div class="fichehalfright">';
print '<div class="underbanner clearboth"></div>';

print '<table class="border tableforfield centpercent">';

// Tags / Categories
if (isModEnabled('category') && $user->hasRight('categorie', 'lire')) {
    print '<tr><td>' . $langs->trans("Categories") . '</td>';
    print '<td colspan="2">';
    print $form->showCategories($object->id, Categorie::TYPE_MEMBER, 1);
    print '</td></tr>';
}

// Birth Date
print '<tr><td class="titlefield">' . $langs->trans("DateOfBirth") . '</td><td class="valeur">' . dol_print_date($object->birth, 'day') . '</td></tr>';

// Default language
if (getDolGlobalInt('MAIN_MULTILANGS')) {
    require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
    print '<tr><td>' . $langs->trans("DefaultLang") . '</td><td>';
    //$s=picto_from_langcode($object->default_lang);
    //print ($s?$s.' ':'');
    $langs->load("languages");
    $labellang = ($object->default_lang ? $langs->trans('Language_' . $object->default_lang) : '');
    print picto_from_langcode($object->default_lang, 'class="paddingrightonly saturatemedium opacitylow"');
    print $labellang;
    print '</td></tr>';
}

// Public
$linkofpubliclist = DOL_MAIN_URL_ROOT . '/public/members/public_list.php' . ((isModEnabled('multicompany')) ? '?entity=' . $conf->entity : '');
print '<tr><td>' . $form->textwithpicto($langs->trans("PublicFile"), $langs->trans("Public", getDolGlobalString('MAIN_INFO_SOCIETE_NOM'), $linkofpubliclist), 1, 'help', '', 0, 3, 'publicfile') . '</td><td class="valeur">' . yn($object->public) . '</td></tr>';

// Other attributes
$cols = 2;
include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

// Third party Dolibarr
if (isModEnabled('societe')) {
    print '<tr><td>';
    print '<table class="nobordernopadding" width="100%"><tr><td>';
    print $langs->trans("LinkedToDolibarrThirdParty");
    print '</td>';
    if ($action != 'editthirdparty' && $user->hasRight('adherent', 'creer')) {
        print '<td class="right"><a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=editthirdparty&token=' . newToken() . '&rowid=' . $object->id . '">' . img_edit($langs->trans('SetLinkToThirdParty'), 1) . '</a></td>';
    }
    print '</tr></table>';
    print '</td><td colspan="2" class="valeur">';
    if ($action == 'editthirdparty') {
        $htmlname = 'socid';
        print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '" name="form' . $htmlname . '">';
        print '<input type="hidden" name="rowid" value="' . $object->id . '">';
        print '<input type="hidden" name="action" value="set' . $htmlname . '">';
        print '<input type="hidden" name="token" value="' . newToken() . '">';
        print '<table class="nobordernopadding">';
        print '<tr><td>';
        print $form->select_company($object->fk_soc, 'socid', '', 1);
        print '</td>';
        print '<td class="left"><input type="submit" class="button button-edit" value="' . $langs->trans("Modify") . '"></td>';
        print '</tr></table></form>';
    } else {
        if ($object->fk_soc) {
            $company = new Societe($db);
            $result = $company->fetch($object->fk_soc);
            print $company->getNomUrl(1);

            // Show link to invoices
            $tmparray = $company->getOutstandingBills('customer');
            if (!empty($tmparray['refs'])) {
                print ' - ' . img_picto($langs->trans("Invoices"), 'bill', 'class="paddingright"') . '<a href="' . DOL_URL_ROOT . '/compta/facture/list.php?socid=' . $object->socid . '">' . $langs->trans("Invoices") . ' (' . count($tmparray['refs']) . ')';
                // TODO Add alert if warning on at least one invoice late
                print '</a>';
            }
        } else {
            print '<span class="opacitymedium">' . $langs->trans("NoThirdPartyAssociatedToMember") . '</span>';
        }
    }
    print '</td></tr>';
}

// Login Dolibarr - Link to user
print '<tr><td>';
print '<table class="nobordernopadding" width="100%"><tr><td>';
print $langs->trans("LinkedToDolibarrUser");
print '</td>';
if ($action != 'editlogin' && $user->hasRight('adherent', 'creer')) {
    print '<td class="right">';
    if ($user->hasRight("user", "user", "creer")) {
        print '<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=editlogin&token=' . newToken() . '&rowid=' . $object->id . '">' . img_edit($langs->trans('SetLinkToUser'), 1) . '</a>';
    }
    print '</td>';
}
print '</tr></table>';
print '</td><td colspan="2" class="valeur">';
if ($action == 'editlogin') {
    $form->form_users($_SERVER['PHP_SELF'] . '?rowid=' . $object->id, $object->user_id, 'userid', '');
} else {
    if ($object->user_id) {
        $linkeduser = new User($db);
        $linkeduser->fetch($object->user_id);
        print $linkeduser->getNomUrl(-1);
    } else {
        print '<span class="opacitymedium">' . $langs->trans("NoDolibarrAccess") . '</span>';
    }
}
print '</td></tr>';

print "</table>\n";

print "</div></div>\n";
print '<div class="clearboth"></div>';

print dol_get_fiche_end();


/*
 * Action bar
 */

// Button to create a new subscription if member no draft (-1) neither resiliated (0) neither excluded (-2)
if ($user->hasRight('adherent', 'cotisation', 'creer')) {
    if ($action != 'addsubscription' && $action != 'create_thirdparty') {
        print '<div class="tabsAction">';

        if ($object->statut > 0) {
            print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?rowid=' . $rowid . '&action=addsubscription&token=' . newToken() . '">' . $langs->trans("AddSubscription") . "</a></div>";
        } else {
            print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("ValidateBefore")) . '">' . $langs->trans("AddSubscription") . '</a></div>';
        }

        print '</div>';
    }
}

/*
 * List of subscriptions
 */
if ($action != 'addsubscription' && $action != 'create_thirdparty') {
    $sql = "SELECT d.rowid, d.firstname, d.lastname, d.societe, d.fk_adherent_type as type,";
    $sql .= " c.rowid as crowid, c.subscription,";
    $sql .= " c.datec, c.fk_type as cfk_type,";
    $sql .= " c.dateadh as dateh,";
    $sql .= " c.datef,";
    $sql .= " c.fk_bank,";
    $sql .= " b.rowid as bid,";
    $sql .= " ba.rowid as baid, ba.label, ba.bank, ba.ref, ba.account_number, ba.fk_accountancy_journal, ba.number, ba.currency_code";
    $sql .= " FROM " . MAIN_DB_PREFIX . "adherent as d, " . MAIN_DB_PREFIX . "subscription as c";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "bank as b ON c.fk_bank = b.rowid";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "bank_account as ba ON b.fk_account = ba.rowid";
    $sql .= " WHERE d.rowid = c.fk_adherent AND d.rowid=" . ((int) $rowid);
    $sql .= $db->order($sortfield, $sortorder);

    $result = $db->query($sql);
    if ($result) {
        $subscriptionstatic = new Subscription($db);

        $num = $db->num_rows($result);

        print '<table class="noborder centpercent">' . "\n";

        print '<tr class="liste_titre">';
        print_liste_field_titre('Ref', $_SERVER['PHP_SELF'], 'c.rowid', '', $param, '', $sortfield, $sortorder);
        print_liste_field_titre('DateCreation', $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, 'center ');
        print_liste_field_titre('Type', $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, 'center ');
        print_liste_field_titre('DateStart', $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, 'center ');
        print_liste_field_titre('DateEnd', $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, 'center ');
        print_liste_field_titre('Amount', $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, 'right ');
        if (isModEnabled('bank')) {
            print_liste_field_titre('Account', $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, 'right ');
        }
        print "</tr>\n";

        $accountstatic = new Account($db);
        $adh = new Adherent($db);
        $adht = new AdherentType($db);

        $i = 0;
        while ($i < $num) {
            $objp = $db->fetch_object($result);

            $adh->id = $objp->rowid;
            $adh->typeid = $objp->type;

            $subscriptionstatic->ref = $objp->crowid;
            $subscriptionstatic->id = $objp->crowid;

            $typeid = $objp->cfk_type;
            if ($typeid > 0) {
                $adht->fetch($typeid);
            }

            print '<tr class="oddeven">';
            print '<td>' . $subscriptionstatic->getNomUrl(1) . '</td>';
            print '<td class="center">' . dol_print_date($db->jdate($objp->datec), 'dayhour') . "</td>\n";
            print '<td class="center">';
            if ($typeid > 0) {
                print $adht->getNomUrl(1);
            }
            print '</td>';
            print '<td class="center">' . dol_print_date($db->jdate($objp->dateh), 'day') . "</td>\n";
            print '<td class="center">' . dol_print_date($db->jdate($objp->datef), 'day') . "</td>\n";
            print '<td class="right amount">' . price($objp->subscription) . '</td>';
            if (isModEnabled('bank')) {
                print '<td class="right">';
                if ($objp->bid) {
                    $accountstatic->label = $objp->label;
                    $accountstatic->id = $objp->baid;
                    $accountstatic->number = $objp->number;
                    $accountstatic->account_number = $objp->account_number;
                    $accountstatic->currency_code = $objp->currency_code;

                    if (isModEnabled('accounting') && $objp->fk_accountancy_journal > 0) {
                        $accountingjournal = new AccountingJournal($db);
                        $accountingjournal->fetch($objp->fk_accountancy_journal);

                        $accountstatic->accountancy_journal = $accountingjournal->getNomUrl(0, 1, 1, '', 1);
                    }

                    $accountstatic->ref = $objp->ref;
                    print $accountstatic->getNomUrl(1);
                } else {
                    print '&nbsp;';
                }
                print '</td>';
            }
            print "</tr>";
            $i++;
        }

        if (empty($num)) {
            $colspan = 6;
            if (isModEnabled('bank')) {
                $colspan++;
            }
            print '<tr><td colspan="' . $colspan . '"><span class="opacitymedium">' . $langs->trans("None") . '</span></td></tr>';
        }

        print "</table>";
    } else {
        dol_print_error($db);
    }
}


if (($action != 'addsubscription' && $action != 'create_thirdparty')) {
    // Shon online payment link
    $useonlinepayment = (isModEnabled('paypal') || isModEnabled('stripe') || isModEnabled('paybox'));

    $parameters = [];
    $reshook = $hookmanager->executeHooks('doShowOnlinePaymentUrl', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
    if ($reshook > 0) {
        if (isset($hookmanager->resArray['showonlinepaymenturl'])) {
            $useonlinepayment = $hookmanager->resArray['showonlinepaymenturl'];
        }
    }

    if ($useonlinepayment) {
        print '<br>';

        require_once DOL_DOCUMENT_ROOT . '/core/lib/payments.lib.php';
        print showOnlinePaymentUrl('membersubscription', $object->ref);
        print '<br>';
    }
}

/*
 * Add new subscription form
 */
if (($action == 'addsubscription' || $action == 'create_thirdparty') && $user->hasRight('adherent', 'cotisation', 'creer')) {
    print '<br>';

    print load_fiche_titre($langs->trans("NewCotisation"));

    // Define default choice for complementary actions
    $bankdirect = 0; // 1 means option by default is write to bank direct with no invoice
    $invoiceonly = 0; // 1 means option by default is invoice only
    $bankviainvoice = 0; // 1 means option by default is write to bank via invoice
    if (GETPOST('paymentsave')) {
        if (GETPOST('paymentsave') == 'bankdirect') {
            $bankdirect = 1;
        }
        if (GETPOST('paymentsave') == 'invoiceonly') {
            $invoiceonly = 1;
        }
        if (GETPOST('paymentsave') == 'bankviainvoice') {
            $bankviainvoice = 1;
        }
    } else {
        if (getDolGlobalString('ADHERENT_BANK_USE') == 'bankviainvoice' && isModEnabled('bank') && isModEnabled('societe') && isModEnabled('invoice')) {
            $bankviainvoice = 1;
        } elseif (getDolGlobalString('ADHERENT_BANK_USE') == 'bankdirect' && isModEnabled('bank')) {
            $bankdirect = 1;
        } elseif (getDolGlobalString('ADHERENT_BANK_USE') == 'invoiceonly' && isModEnabled('bank') && isModEnabled('societe') && isModEnabled('invoice')) {
            $invoiceonly = 1;
        }
    }

    print "\n\n<!-- Form add subscription -->\n";

    if ($conf->use_javascript_ajax) {
        //var_dump($bankdirect.'-'.$bankviainvoice.'-'.$invoiceonly);
        print "\n" . '<script type="text/javascript">';
        print '$(document).ready(function () {
					$(".bankswitchclass, .bankswitchclass2").' . (($bankdirect || $bankviainvoice) ? 'show()' : 'hide()') . ';
					$("#none, #invoiceonly").click(function() {
						$(".bankswitchclass").hide();
						$(".bankswitchclass2").hide();
					});
					$("#bankdirect, #bankviainvoice").click(function() {
						$(".bankswitchclass").show();
						$(".bankswitchclass2").show();
					});
					$("#selectoperation").change(function() {
						var code = $(this).val();
						if (code == "CHQ")
						{
							$(".fieldrequireddyn").addClass("fieldrequired");
							if ($("#fieldchqemetteur").val() == "")
							{
								$("#fieldchqemetteur").val($("#memberlabel").val());
							}
						}
						else
						{
							$(".fieldrequireddyn").removeClass("fieldrequired");
						}
					});
					';
        if (GETPOST('paymentsave')) {
            print '$("#' . GETPOST('paymentsave', 'aZ09') . '").prop("checked", true);';
        }
        print '});';
        print '</script>' . "\n";
    }


    // Confirm create third party
    if ($action == 'create_thirdparty') {
        $companyalias = '';
        $fullname = $object->getFullName($langs);

        if ($object->morphy == 'mor') {
            $companyname = $object->company;
            if (!empty($fullname)) {
                $companyalias = $fullname;
            }
        } else {
            $companyname = $fullname;
            if (!empty($object->company)) {
                $companyalias = $object->company;
            }
        }

        // Create a form array
        $formquestion = [
            ['label' => $langs->trans("NameToCreate"), 'type' => 'text', 'name' => 'companyname', 'value' => $companyname, 'morecss' => 'minwidth300', 'moreattr' => 'maxlength="128"'],
            ['label' => $langs->trans("AliasNames"), 'type' => 'text', 'name' => 'companyalias', 'value' => $companyalias, 'morecss' => 'minwidth300', 'moreattr' => 'maxlength="128"'],
        ];
        // If customer code was forced to "required", we ask it at creation to avoid error later
        if (getDolGlobalString('MAIN_COMPANY_CODE_ALWAYS_REQUIRED')) {
            $tmpcompany = new Societe($db);
            $tmpcompany->name = $companyname;
            $tmpcompany->get_codeclient($tmpcompany, 0);
            $customercode = $tmpcompany->code_client;
            $formquestion[] = [
                'label' => $langs->trans("CustomerCode"),
                'type' => 'text',
                'name' => 'customercode',
                'value' => $customercode,
                'morecss' => 'minwidth300',
                'moreattr' => 'maxlength="128"',
            ];
        }
        // @todo Add other extrafields mandatory for thirdparty creation

        print $form->formconfirm($_SERVER['PHP_SELF'] . "?rowid=" . $object->id, $langs->trans("CreateDolibarrThirdParty"), $langs->trans("ConfirmCreateThirdParty"), "confirm_create_thirdparty", $formquestion, 1);
    }


    print '<form name="subscription" method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="subscription">';
    print '<input type="hidden" name="rowid" value="' . $rowid . '">';
    print '<input type="hidden" name="memberlabel" id="memberlabel" value="' . dol_escape_htmltag($object->getFullName($langs)) . '">';
    print '<input type="hidden" name="thirdpartylabel" id="thirdpartylabel" value="' . dol_escape_htmltag($object->company) . '">';

    print dol_get_fiche_head('');

    print '<div class="div-table-responsive">';
    print '<table class="border centpercent">' . "\n";
    print '<tbody>';

    // Date payment
    if (GETPOST('paymentyear') && GETPOST('paymentmonth') && GETPOST('paymentday')) {
        $paymentdate = dol_mktime(0, 0, 0, GETPOST('paymentmonth'), GETPOST('paymentday'), GETPOST('paymentyear'));
    }

    print '<tr>';
    // Date start subscription
    $currentyear = dol_print_date($now, "%Y");
    $currentmonth = dol_print_date($now, "%m");
    print '<td class="fieldrequired">' . $langs->trans("DateSubscription") . '</td><td>';
    if (GETPOST('reday')) {
        $datefrom = dol_mktime(0, 0, 0, GETPOSTINT('remonth'), GETPOSTINT('reday'), GETPOSTINT('reyear'));
    }
    if (!$datefrom) {
        $datefrom = $object->datevalid;
        if (getDolGlobalString('MEMBER_SUBSCRIPTION_START_AFTER')) {
            $datefrom = dol_time_plus_duree($now, (int) substr(getDolGlobalString('MEMBER_SUBSCRIPTION_START_AFTER'), 0, -1), substr(getDolGlobalString('MEMBER_SUBSCRIPTION_START_AFTER'), -1));
        } elseif ($object->datefin > 0 && dol_time_plus_duree($object->datefin, $defaultdelay, $defaultdelayunit) > $now) {
            $datefrom = dol_time_plus_duree($object->datefin, 1, 'd');
        }

        if (getDolGlobalString('MEMBER_SUBSCRIPTION_START_FIRST_DAY_OF') === "m") {
            $datefrom = dol_get_first_day(dol_print_date($datefrom, "%Y"), dol_print_date($datefrom, "%m"));
        } elseif (getDolGlobalString('MEMBER_SUBSCRIPTION_START_FIRST_DAY_OF') === "Y") {
            $datefrom = dol_get_first_day(dol_print_date($datefrom, "%Y"));
        }
    }
    print $form->selectDate($datefrom, '', 0, 0, 0, "subscription", 1, 1);
    print "</td></tr>";

    // Date end subscription
    if (GETPOST('endday')) {
        $dateto = dol_mktime(0, 0, 0, GETPOSTINT('endmonth'), GETPOSTINT('endday'), GETPOSTINT('endyear'));
    }
    if (!$dateto) {
        if (getDolGlobalInt('MEMBER_SUBSCRIPTION_SUGGEST_END_OF_MONTH')) {
            $dateto = dol_get_last_day(dol_print_date($datefrom, "%Y"), dol_print_date($datefrom, "%m"));
        } elseif (getDolGlobalInt('MEMBER_SUBSCRIPTION_SUGGEST_END_OF_YEAR')) {
            $dateto = dol_get_last_day(dol_print_date($datefrom, "%Y"));
        } else {
            $dateto = -1; // By default, no date is suggested
        }
    }
    print '<tr><td>' . $langs->trans("DateEndSubscription") . '</td><td>';
    print $form->selectDate($dateto, 'end', 0, 0, 0, "subscription", 1, 0);
    print "</td></tr>";

    if ($adht->subscription) {
        // Amount
        print '<tr><td class="fieldrequired">' . $langs->trans("Amount") . '</td><td><input type="text" name="subscription" size="6" value="' . (GETPOSTISSET('subscription') ? GETPOST('subscription') : price($adht->amount, 0, '', 0)) . '"> ' . $langs->trans("Currency" . $conf->currency) . '</td></tr>';

        // Label
        print '<tr><td>' . $langs->trans("Label") . '</td>';
        print '<td><input name="label" type="text" size="32" value="';
        if (!getDolGlobalString('MEMBER_NO_DEFAULT_LABEL')) {
            print $langs->trans("Subscription") . ' ' . dol_print_date(($datefrom ? $datefrom : time()), "%Y");
        }
        print '"></td></tr>';

        // Complementary action
        if ((isModEnabled('bank') || isModEnabled('invoice')) && !getDolGlobalString('ADHERENT_SUBSCRIPTION_HIDECOMPLEMENTARYACTIONS')) {
            $company = new Societe($db);
            if ($object->socid) {
                $result = $company->fetch($object->socid);
            }

            // No more action
            print '<tr><td class="tdtop fieldrequired">' . $langs->trans('MoreActions');
            print '</td>';
            print '<td class="line-height-large">';

            print '<input type="radio" class="moreaction" id="none" name="paymentsave" value="none"' . (empty($bankdirect) && empty($invoiceonly) && empty($bankviainvoice) ? ' checked' : '') . '>';
            print '<label for="none"> ' . $langs->trans("None") . '</label><br>';
            // Add entry into bank account
            if (isModEnabled('bank')) {
                print '<input type="radio" class="moreaction" id="bankdirect" name="paymentsave" value="bankdirect"' . (!empty($bankdirect) ? ' checked' : '');
                print '><label for="bankdirect">  ' . $langs->trans("MoreActionBankDirect") . '</label><br>';
            }
            // Add invoice with no payments
            if (isModEnabled('societe') && isModEnabled('invoice')) {
                print '<input type="radio" class="moreaction" id="invoiceonly" name="paymentsave" value="invoiceonly"' . (!empty($invoiceonly) ? ' checked' : '');
                //if (empty($object->fk_soc)) print ' disabled';
                print '><label for="invoiceonly"> ' . $langs->trans("MoreActionInvoiceOnly");
                if ($object->fk_soc) {
                    print ' (' . $langs->trans("ThirdParty") . ': ' . $company->getNomUrl(1) . ')';
                } else {
                    print ' (';
                    if (empty($object->fk_soc)) {
                        print img_warning($langs->trans("NoThirdPartyAssociatedToMember"));
                    }
                    print $langs->trans("NoThirdPartyAssociatedToMember");
                    print ' - <a href="' . $_SERVER['PHP_SELF'] . '?rowid=' . $object->id . '&amp;action=create_thirdparty">';
                    print $langs->trans("CreateDolibarrThirdParty");
                    print '</a>)';
                }
                if (!getDolGlobalString('ADHERENT_VAT_FOR_SUBSCRIPTIONS') || getDolGlobalString('ADHERENT_VAT_FOR_SUBSCRIPTIONS') != 'defaultforfoundationcountry') {
                    print '. <span class="opacitymedium">' . $langs->trans("NoVatOnSubscription", 0) . '</span>';
                }
                if (getDolGlobalString('ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS') && (isModEnabled('product') || isModEnabled('service'))) {
                    $prodtmp = new Product($db);
                    $result = $prodtmp->fetch(getDolGlobalString('ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS'));
                    if ($result < 0) {
                        setEventMessage($prodtmp->error, 'errors');
                    }
                    print '. ' . $langs->transnoentitiesnoconv("ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS", $prodtmp->getNomUrl(1)); // must use noentitiesnoconv to avoid to encode html into getNomUrl of product
                }
                print '</label><br>';
            }
            // Add invoice with payments
            if (isModEnabled('bank') && isModEnabled('societe') && isModEnabled('invoice')) {
                print '<input type="radio" class="moreaction" id="bankviainvoice" name="paymentsave" value="bankviainvoice"' . (!empty($bankviainvoice) ? ' checked' : '');
                //if (empty($object->fk_soc)) print ' disabled';
                print '><label for="bankviainvoice">  ' . $langs->trans("MoreActionBankViaInvoice");
                if ($object->socid) {
                    print ' (' . $langs->trans("ThirdParty") . ': ' . $company->getNomUrl(1) . ')';
                } else {
                    print ' (';
                    if (empty($object->socid)) {
                        print img_warning($langs->trans("NoThirdPartyAssociatedToMember"));
                    }
                    print $langs->trans("NoThirdPartyAssociatedToMember");
                    print ' - <a href="' . $_SERVER['PHP_SELF'] . '?rowid=' . $object->id . '&amp;action=create_thirdparty">';
                    print $langs->trans("CreateDolibarrThirdParty");
                    print '</a>)';
                }
                if (!getDolGlobalString('ADHERENT_VAT_FOR_SUBSCRIPTIONS') || getDolGlobalString('ADHERENT_VAT_FOR_SUBSCRIPTIONS') != 'defaultforfoundationcountry') {
                    print '. <span class="opacitymedium">' . $langs->trans("NoVatOnSubscription", 0) . '</span>';
                }
                if (getDolGlobalString('ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS') && (isModEnabled('product') || isModEnabled('service'))) {
                    $prodtmp = new Product($db);
                    $result = $prodtmp->fetch(getDolGlobalString('ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS'));
                    if ($result < 0) {
                        setEventMessage($prodtmp->error, 'errors');
                    }
                    print '. ' . $langs->transnoentitiesnoconv("ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS", $prodtmp->getNomUrl(1)); // must use noentitiesnoconv to avoid to encode html into getNomUrl of product
                }
                print '</label><br>';
            }
            print '</td></tr>';

            // Bank account
            print '<tr class="bankswitchclass"><td class="fieldrequired">' . $langs->trans("FinancialAccount") . '</td><td>';
            print img_picto('', 'bank_account');
            $form->select_comptes(GETPOST('accountid'), 'accountid', 0, '', 2, '', 0, 'minwidth200');
            print "</td></tr>\n";

            // Payment mode
            print '<tr class="bankswitchclass"><td class="fieldrequired">' . $langs->trans("PaymentMode") . '</td><td>';
            print $form->select_types_paiements(GETPOST('operation'), 'operation', '', 2, 1, 0, 0, 1, 'minwidth200', 1);
            print "</td></tr>\n";

            // Date of payment
            print '<tr class="bankswitchclass"><td class="fieldrequired">' . $langs->trans("DatePayment") . '</td><td>';
            print $form->selectDate(isset($paymentdate) ? $paymentdate : -1, 'payment', 0, 0, 1, 'subscription', 1, 1);
            print "</td></tr>\n";

            print '<tr class="bankswitchclass2"><td>' . $langs->trans('Numero');
            print ' <em>(' . $langs->trans("ChequeOrTransferNumber") . ')</em>';
            print '</td>';
            print '<td><input id="fieldnum_chq" name="num_chq" type="text" size="8" value="' . (!GETPOST('num_chq') ? '' : GETPOST('num_chq')) . '"></td></tr>';

            print '<tr class="bankswitchclass2 fieldrequireddyn"><td>' . $langs->trans('CheckTransmitter');
            print ' <em>(' . $langs->trans("ChequeMaker") . ')</em>';
            print '</td>';
            print '<td><input id="fieldchqemetteur" name="chqemetteur" size="32" type="text" value="' . (!GETPOST('chqemetteur') ? '' : GETPOST('chqemetteur')) . '"></td></tr>';

            print '<tr class="bankswitchclass2"><td>' . $langs->trans('Bank');
            print ' <em>(' . $langs->trans("ChequeBank") . ')</em>';
            print '</td>';
            print '<td><input id="chqbank" name="chqbank" size="32" type="text" value="' . (!GETPOST('chqbank') ? '' : GETPOST('chqbank')) . '"></td></tr>';
        }
    }

    print '<tr><td></td><td></td></tr>';

    print '<tr><td>' . $langs->trans("SendAcknowledgementByMail") . '</td>';
    print '<td>';
    if (!$object->email) {
        print $langs->trans("NoEMail");
    } else {
        $adht = new AdherentType($db);
        $adht->fetch($object->typeid);

        // Send subscription email
        $subject = '';
        $msg = '';

        // Send subscription email
        include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
        $formmail = new FormMail($db);
        // Set output language
        $outputlangs = new Translate('', $conf);
        $outputlangs->setDefaultLang(empty($object->thirdparty->default_lang) ? $mysoc->default_lang : $object->thirdparty->default_lang);
        // Load traductions files required by page
        $outputlangs->loadLangs(["main", "members"]);
        // Get email content from template
        $arraydefaultmessage = null;
        $labeltouse = getDolGlobalString('ADHERENT_EMAIL_TEMPLATE_SUBSCRIPTION');

        if (!empty($labeltouse)) {
            $arraydefaultmessage = $formmail->getEMailTemplate($db, 'member', $user, $outputlangs, 0, 1, $labeltouse);
        }

        if (!empty($labeltouse) && is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0) {
            $subject = $arraydefaultmessage->topic;
            $msg = $arraydefaultmessage->content;
        }

        $substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
        complete_substitutions_array($substitutionarray, $outputlangs, $object);
        $subjecttosend = make_substitutions($subject, $substitutionarray, $outputlangs);
        $texttosend = make_substitutions(dol_concatdesc($msg, $adht->getMailOnSubscription()), $substitutionarray, $outputlangs);

        $tmp = '<input name="sendmail" type="checkbox"' . (GETPOST('sendmail', 'alpha') ? ' checked' : (getDolGlobalString('ADHERENT_DEFAULT_SENDINFOBYMAIL') ? ' checked' : '')) . '>';
        $helpcontent = '';
        $helpcontent .= '<b>' . $langs->trans("MailFrom") . '</b>: ' . getDolGlobalString('ADHERENT_MAIL_FROM') . '<br>' . "\n";
        $helpcontent .= '<b>' . $langs->trans("MailRecipient") . '</b>: ' . $object->email . '<br>' . "\n";
        $helpcontent .= '<b>' . $langs->trans("MailTopic") . '</b>:<br>' . "\n";
        if ($subjecttosend) {
            $helpcontent .= $subjecttosend . "\n";
        } else {
            $langs->load("errors");
            $helpcontent .= '<span class="error">' . $langs->trans("ErrorModuleSetupNotComplete", $langs->transnoentitiesnoconv("Module310Name")) . '</span>' . "\n";
        }
        $helpcontent .= "<br>";
        $helpcontent .= '<b>' . $langs->trans("MailText") . '</b>:<br>';
        if ($texttosend) {
            $helpcontent .= dol_htmlentitiesbr($texttosend) . "\n";
        } else {
            $langs->load("errors");
            $helpcontent .= '<span class="error">' . $langs->trans("ErrorModuleSetupNotComplete", $langs->transnoentitiesnoconv("Module310Name")) . '</span>' . "\n";
        }
        // @phan-suppress-next-line PhanPluginSuspiciousParamOrder
        print $form->textwithpicto($tmp, $helpcontent, 1, 'help', '', 0, 2, 'helpemailtosend');
    }
    print '</td></tr>';
    print '</tbody>';
    print '</table>';
    print '</div>';

    print dol_get_fiche_end();

    print '<div class="center">';
    $parameters = [];
    $reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action);
    if (empty($reshook)) {
        print '<input type="submit" class="button" name="add" value="' . $langs->trans("AddSubscription") . '">';
        print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        print '<input type="submit" class="button button-cancel" name="cancel" value="' . $langs->trans("Cancel") . '">';
    }
    print '</div>';

    print '</form>';

    print "\n<!-- End form subscription -->\n\n";
}


// End of page
llxFooter();
