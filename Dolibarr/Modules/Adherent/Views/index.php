<?php

use DoliCore\Form\Form;
use DoliCore\Form\FormOther;
use DoliModules\Adherent\Model\Adherent;
use DoliModules\Adherent\Model\AdherentType;
use DoliModules\Adherent\Model\Subscription;
use DoliModules\Adherent\Statistics\AdherentStats;

$form = new Form($db);

// Load $resultboxes (selectboxlist + boxactivated + boxlista + boxlistb)
$resultboxes = FormOther::getBoxesArea($user, "2");

llxHeader('', $langs->trans("Members"), 'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros|DE:Modul_Mitglieder');

$staticmember = new Adherent($db);
$statictype = new AdherentType($db);
$subscriptionstatic = new Subscription($db);

print load_fiche_titre($langs->trans("MembersArea"), $resultboxes['selectboxlist'], 'members');

/*
 * Statistics
 */

$boxgraph = '';
if ($conf->use_javascript_ajax) {
    $year = date('Y');
    $numberyears = getDolGlobalInt("MAIN_NB_OF_YEAR_IN_MEMBERSHIP_WIDGET_GRAPH");

    $boxgraph .= '<div class="div-table-responsive-no-min">';
    $boxgraph .= '<table class="noborder nohover centpercent">';
    $boxgraph .= '<tr class="liste_titre"><th colspan="2">' . $langs->trans("Statistics") . ($numberyears ? ' (' . ($year - $numberyears) . ' - ' . $year . ')' : '') . '</th></tr>';
    $boxgraph .= '<tr><td class="center" colspan="2">';

    $stats = new AdherentStats($db, 0, $userid);

    // Show array
    $sumMembers = $stats->countMembersByTypeAndStatus($numberyears);
    if (is_array($sumMembers) && !empty($sumMembers)) {
        $total = $sumMembers['total']['members_draft'] + $sumMembers['total']['members_pending'] + $sumMembers['total']['members_uptodate'] + $sumMembers['total']['members_expired'] + $sumMembers['total']['members_excluded'] + $sumMembers['total']['members_resiliated'];
    } else {
        $total = 0;
    }
    foreach (['members_draft', 'members_pending', 'members_uptodate', 'members_expired', 'members_excluded', 'members_resiliated'] as $val) {
        if (empty($sumMembers['total'][$val])) {
            $sumMembers['total'][$val] = 0;
        }
    }

    $dataseries = [];
    $dataseries[] = [$langs->transnoentitiesnoconv("MembersStatusToValid"), $sumMembers['total']['members_draft']];            // Draft, not yet validated
    $dataseries[] = [$langs->transnoentitiesnoconv("WaitingSubscription"), $sumMembers['total']['members_pending']];
    $dataseries[] = [$langs->transnoentitiesnoconv("UpToDate"), $sumMembers['total']['members_uptodate']];
    $dataseries[] = [$langs->transnoentitiesnoconv("OutOfDate"), $sumMembers['total']['members_expired']];
    $dataseries[] = [$langs->transnoentitiesnoconv("MembersStatusExcluded"), $sumMembers['total']['members_excluded']];
    $dataseries[] = [$langs->transnoentitiesnoconv("MembersStatusResiliated"), $sumMembers['total']['members_resiliated']];

    include DOL_DOCUMENT_ROOT . '/theme/' . $conf->theme . '/theme_vars.inc.php';

    include_once DOL_DOCUMENT_ROOT . '/core/class/dolgraph.class.php';
    $dolgraph = new DolGraph();
    $dolgraph->SetData($dataseries);
    $dolgraph->SetDataColor(['-' . $badgeStatus0, $badgeStatus1, $badgeStatus4, $badgeStatus8, '-' . $badgeStatus8, $badgeStatus6]);
    $dolgraph->setShowLegend(2);
    $dolgraph->setShowPercent(1);
    $dolgraph->SetType(['pie']);
    $dolgraph->setHeight('200');
    $dolgraph->draw('idgraphstatus');
    $boxgraph .= $dolgraph->show($total ? 0 : 1);

    $boxgraph .= '</td></tr>';
    $boxgraph .= '<tr class="liste_total"><td>' . $langs->trans("Total") . '</td><td class="right">';
    $boxgraph .= $total;
    $boxgraph .= '</td></tr>';
    $boxgraph .= '</table>';
    $boxgraph .= '</div>';
    $boxgraph .= '<br>';
}

// boxes
print '<div class="clearboth"></div>';
print '<div class="fichecenter fichecenterbis">';

print '<div class="twocolumns">';

print '<div class="firstcolumn fichehalfleft boxhalfleft" id="boxhalfleft">';

print $boxgraph;

print $resultboxes['boxlista'];

print '</div>' . "\n";

print '<div class="secondcolumn fichehalfright boxhalfright" id="boxhalfright">';

print $resultboxes['boxlistb'];

print '</div>' . "\n";

print '</div>';
print '</div>';

$parameters = ['user' => $user];
$reshook = $hookmanager->executeHooks('dashboardMembers', $parameters, $object); // Note that $action and $object may have been modified by hook

// End of page
llxFooter();
