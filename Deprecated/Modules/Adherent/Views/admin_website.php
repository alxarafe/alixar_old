<?php

$form = new Form($db);

$title = $langs->trans("MembersSetup");
$help_url = 'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros|DE:Modul_Mitglieder';
llxHeader('', $title, $help_url);


$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($title, $linkback, 'title_setup');

$head = member_admin_prepare_head();


print '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="token" value="' . newToken() . '">';

print dol_get_fiche_head($head, 'website', $langs->trans("Members"), -1, 'user');

if ($conf->use_javascript_ajax) {
    print "\n" . '<script type="text/javascript">';
    print 'jQuery(document).ready(function () {
                function initemail()
                {
                    if (jQuery("#MEMBER_NEWFORM_PAYONLINE").val()==\'-1\')
                    {
                        jQuery("#tremail").hide();
					}
					else
					{
                        jQuery("#tremail").show();
					}
				}
                function initfields()
                {
					if (jQuery("#MEMBER_ENABLE_PUBLIC").val()==\'0\')
                    {
                        jQuery("#trforcetype, #tramount, #tredit, #trpayment, #tremail").hide();
                    }
                    if (jQuery("#MEMBER_ENABLE_PUBLIC").val()==\'1\')
                    {
                        jQuery("#trforcetype, #tramount, #tredit, #trpayment").show();
                        if (jQuery("#MEMBER_NEWFORM_PAYONLINE").val()==\'-1\') jQuery("#tremail").hide();
                        else jQuery("#tremail").show();
					}
				}
				initfields();
                jQuery("#MEMBER_ENABLE_PUBLIC").change(function() { initfields(); });
                jQuery("#MEMBER_NEWFORM_PAYONLINE").change(function() { initemail(); });
			})';
    print '</script>' . "\n";
}


print '<span class="opacitymedium">' . $langs->trans("BlankSubscriptionFormDesc") . '</span><br><br>';

$param = '';

$enabledisablehtml = $langs->trans("EnablePublicSubscriptionForm") . ' ';
if (!getDolGlobalString('MEMBER_ENABLE_PUBLIC')) {
    // Button off, click to enable
    $enabledisablehtml .= '<a class="reposition valignmiddle" href="' . $_SERVER['PHP_SELF'] . '?action=setMEMBER_ENABLE_PUBLIC&token=' . newToken() . '&value=1' . $param . '">';
    $enabledisablehtml .= img_picto($langs->trans("Disabled"), 'switch_off');
    $enabledisablehtml .= '</a>';
} else {
    // Button on, click to disable
    $enabledisablehtml .= '<a class="reposition valignmiddle" href="' . $_SERVER['PHP_SELF'] . '?action=setMEMBER_ENABLE_PUBLIC&token=' . newToken() . '&value=0' . $param . '">';
    $enabledisablehtml .= img_picto($langs->trans("Activated"), 'switch_on');
    $enabledisablehtml .= '</a>';
}
print $enabledisablehtml;
print '<input type="hidden" id="MEMBER_ENABLE_PUBLIC" name="MEMBER_ENABLE_PUBLIC" value="' . (!getDolGlobalString('MEMBER_ENABLE_PUBLIC') ? 0 : 1) . '">';

print '<br><br>';


if (getDolGlobalString('MEMBER_ENABLE_PUBLIC')) {
    print '<br>';
    //print $langs->trans('FollowingLinksArePublic').'<br>';
    print img_picto('', 'globe') . ' <span class="opacitymedium">' . $langs->trans('BlankSubscriptionForm') . '</span><br>';
    if (isModEnabled('multicompany')) {
        $entity_qr = '?entity=' . ((int) $conf->entity);
    } else {
        $entity_qr = '';
    }

    // Define $urlwithroot
    $urlwithouturlroot = preg_replace('/' . preg_quote(DOL_URL_ROOT, '/') . '$/i', '', trim($dolibarr_main_url_root));
    $urlwithroot = $urlwithouturlroot . DOL_URL_ROOT; // This is to use external domain name found into config file
    //$urlwithroot=DOL_MAIN_URL_ROOT;                   // This is to use same domain name than current

    print '<div class="urllink">';
    print '<input type="text" id="publicurlmember" class="quatrevingtpercentminusx" value="' . $urlwithroot . '/public/members/new.php' . $entity_qr . '">';
    print '<a target="_blank" rel="noopener noreferrer" href="' . $urlwithroot . '/public/members/new.php' . $entity_qr . '">' . img_picto('', 'globe', 'class="paddingleft"') . '</a>';
    print '</div>';
    print ajax_autoselect('publicurlmember');

    print '<br>';

    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';

    print '<tr class="liste_titre">';
    print '<td>' . $langs->trans("Parameter") . '</td>';
    print '<td>' . $langs->trans("Value") . '</td>';
    print "</tr>\n";

    // Force Type
    $adht = new AdherentType($db);
    print '<tr class="oddeven drag" id="trforcetype"><td>';
    print $langs->trans("ForceMemberType");
    print '</td><td>';
    $listofval = [];
    $listofval += $adht->liste_array(1);
    $forcetype = getDolGlobalInt('MEMBER_NEWFORM_FORCETYPE', -1);
    print $form->selectarray("MEMBER_NEWFORM_FORCETYPE", $listofval, $forcetype, count($listofval) > 1 ? 1 : 0);
    print "</td></tr>\n";

    // Force nature of member (mor/phy)
    $morphys = [];
    $morphys["phy"] = $langs->trans("Physical");
    $morphys["mor"] = $langs->trans("Moral");
    print '<tr class="oddeven drag" id="trforcenature"><td>';
    print $langs->trans("ForceMemberNature");
    print '</td><td>';
    $forcenature = getDolGlobalInt('MEMBER_NEWFORM_FORCEMORPHY', 0);
    print $form->selectarray("MEMBER_NEWFORM_FORCEMORPHY", $morphys, $forcenature, 1);
    print "</td></tr>\n";

    // Amount
    print '<tr class="oddeven" id="tramount"><td>';
    print $langs->trans("DefaultAmount");
    print '</td><td>';
    print '<input type="text" class="right width50" id="MEMBER_NEWFORM_AMOUNT" name="MEMBER_NEWFORM_AMOUNT" value="' . getDolGlobalString('MEMBER_NEWFORM_AMOUNT') . '">';
    print "</td></tr>\n";

    // Min amount
    print '<tr class="oddeven" id="tredit"><td>';
    print $langs->trans("MinimumAmount");
    print '</td><td>';
    print '<input type="text" class="right width50" id="MEMBER_MIN_AMOUNT" name="MEMBER_MIN_AMOUNT" value="' . getDolGlobalString('MEMBER_MIN_AMOUNT') . '">';
    print "</td></tr>\n";

    // SHow counter of validated members publicly
    print '<tr class="oddeven" id="tredit"><td>';
    print $langs->trans("MemberCountersArePublic");
    print '</td><td>';
    print $form->selectyesno("MEMBER_COUNTERS_ARE_PUBLIC", getDolGlobalInt('MEMBER_COUNTERS_ARE_PUBLIC'), 1);
    print "</td></tr>\n";

    // Show the table of all available membership types. If not, show a form (as the default was for Dolibarr <=16.0)
    $skiptable = getDolGlobalInt('MEMBER_SKIP_TABLE');
    print '<tr class="oddeven" id="tredit"><td>';
    print $langs->trans("MembersShowMembershipTypesTable");
    print '</td><td>';
    print $form->selectyesno("MEMBER_SHOW_TABLE", !$skiptable, 1); // Reverse the logic "hide -> show" for retrocompatibility
    print "</td></tr>\n";

    // Show "vote allowed" setting for membership types
    $hidevoteallowed = getDolGlobalInt('MEMBER_HIDE_VOTE_ALLOWED');
    print '<tr class="oddeven" id="tredit"><td>';
    print $langs->trans("MembersShowVotesAllowed");
    print '</td><td>';
    print $form->selectyesno("MEMBER_SHOW_VOTE_ALLOWED", !$hidevoteallowed, 1); // Reverse the logic "hide -> show" for retrocompatibility
    print "</td></tr>\n";

    // Jump to an online payment page
    print '<tr class="oddeven" id="trpayment"><td>';
    print $langs->trans("MEMBER_NEWFORM_PAYONLINE");
    print '</td><td>';
    $listofval = [];
    $listofval['-1'] = $langs->trans('No');
    $listofval['all'] = $langs->trans('Yes') . ' (' . $langs->trans("VisitorCanChooseItsPaymentMode") . ')';
    if (isModEnabled('paybox')) {
        $listofval['paybox'] = 'Paybox';
    }
    if (isModEnabled('paypal')) {
        $listofval['paypal'] = 'PayPal';
    }
    if (isModEnabled('stripe')) {
        $listofval['stripe'] = 'Stripe';
    }
    print $form->selectarray("MEMBER_NEWFORM_PAYONLINE", $listofval, getDolGlobalString('MEMBER_NEWFORM_PAYONLINE'), 0);
    print "</td></tr>\n";

    print '</table>';
    print '</div>';

    print '<div class="center">';
    print '<input type="submit" class="button button-edit" value="' . $langs->trans("Modify") . '">';
    print '</div>';
}


print dol_get_fiche_end();

print '</form>';

// End of page
llxFooter();
