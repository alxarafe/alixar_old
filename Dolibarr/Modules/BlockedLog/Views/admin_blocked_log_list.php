<?php

use DoliCore\Form\Form;

$form = new Form($db);

if (GETPOST('withtab', 'alpha')) {
    $title = $langs->trans("ModuleSetup") . ' ' . $langs->trans('BlockedLog');
} else {
    $title = $langs->trans("BrowseBlockedLog");
}
$help_url = "EN:Module_Unalterable_Archives_-_Logs|FR:Module_Archives_-_Logs_Inaltérable";

llxHeader('', $title, $help_url);

$MAXLINES = 10000;

$blocks = $block_static->getLog('all', $search_id, $MAXLINES, $sortfield, $sortorder, $search_fk_user, $search_start, $search_end, $search_ref, $search_amount, $search_code);
if (!is_array($blocks)) {
    if ($blocks == -2) {
        setEventMessages($langs->trans("TooManyRecordToScanRestrictFilters", $MAXLINES), null, 'errors');
    } else {
        dol_print_error($block_static->db, $block_static->error, $block_static->errors);
        exit;
    }
}

$linkback = '';
if (GETPOST('withtab', 'alpha')) {
    $linkback = '<a href="' . ($backtopage ? $backtopage : DOL_URL_ROOT . '/admin/modules.php') . '">' . $langs->trans("BackToModuleList") . '</a>';
}

print load_fiche_titre($title, $linkback);

if (GETPOST('withtab', 'alpha')) {
    $head = blockedlogadmin_prepare_head();
    print dol_get_fiche_head($head, 'fingerprints', '', -1);
}

print '<span class="opacitymedium hideonsmartphone">' . $langs->trans("FingerprintsDesc") . "<br></span>\n";

print '<br>';

$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER['PHP_SELF']) {
    $param .= '&contextpage=' . urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
    $param .= '&limit=' . ((int) $limit);
}
if ($search_id != '') {
    $param .= '&search_id=' . urlencode($search_id);
}
if ($search_fk_user > 0) {
    $param .= '&search_fk_user=' . urlencode($search_fk_user);
}
if ($search_startyear > 0) {
    $param .= '&search_startyear=' . urlencode($search_startyear);
}
if ($search_startmonth > 0) {
    $param .= '&search_startmonth=' . urlencode($search_startmonth);
}
if ($search_startday > 0) {
    $param .= '&search_startday=' . urlencode($search_startday);
}
if ($search_endyear > 0) {
    $param .= '&search_endyear=' . urlencode((string) ($search_endyear));
}
if ($search_endmonth > 0) {
    $param .= '&search_endmonth=' . urlencode((string) ($search_endmonth));
}
if ($search_endday > 0) {
    $param .= '&search_endday=' . urlencode((string) ($search_endday));
}
if ($search_showonlyerrors > 0) {
    $param .= '&search_showonlyerrors=' . urlencode((string) ($search_showonlyerrors));
}
if ($optioncss != '') {
    $param .= '&optioncss=' . urlencode($optioncss);
}
if (GETPOST('withtab', 'alpha')) {
    $param .= '&withtab=' . urlencode(GETPOST('withtab', 'alpha'));
}

// Add $param from extra fields
//include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

print '<form method="POST" id="searchFormList" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';

print '<div class="right">';
print $langs->trans("RestrictYearToExport") . ': ';
$smonth = GETPOSTINT('monthtoexport');
// Month
$retstring = '';
$retstring .= '<select class="flat valignmiddle maxwidth75imp marginrightonly" id="monthtoexport" name="monthtoexport">';
$retstring .= '<option value="0" selected>&nbsp;</option>';
for ($month = 1; $month <= 12; $month++) {
    $retstring .= '<option value="' . $month . '"' . ($month == $smonth ? ' selected' : '') . '>';
    $retstring .= dol_print_date(mktime(12, 0, 0, $month, 1, 2000), "%b");
    $retstring .= "</option>";
}
$retstring .= "</select>";
print $retstring;
print '<input type="text" name="yeartoexport" class="valignmiddle maxwidth50imp" value="' . GETPOSTINT('yeartoexport') . '">';
print '<input type="hidden" name="withtab" value="' . GETPOST('withtab', 'alpha') . '">';
print '<input type="submit" name="downloadcsv" class="button" value="' . $langs->trans('DownloadLogCSV') . '">';
if (getDolGlobalString('BLOCKEDLOG_USE_REMOTE_AUTHORITY')) {
    print ' | <a href="?action=downloadblockchain' . (GETPOST('withtab', 'alpha') ? '&withtab=' . GETPOST('withtab', 'alpha') : '') . '">' . $langs->trans('DownloadBlockChain') . '</a>';
}
print ' </div><br>';

print '</form>';

print '<form method="POST" id="searchFormList" action="' . $_SERVER['PHP_SELF'] . '">';

if ($optioncss != '') {
    print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
}
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
print '<input type="hidden" name="page" value="' . $page . '">';
print '<input type="hidden" name="contextpage" value="' . $contextpage . '">';
print '<input type="hidden" name="withtab" value="' . GETPOST('withtab', 'alpha') . '">';

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
print '<table class="noborder centpercent">';

// Line of filters
print '<tr class="liste_titre_filter">';

// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
    print '<td class="liste_titre center">';
    $searchpicto = $form->showFilterButtons();
    print $searchpicto;
    print '</td>';
}

print '<td class="liste_titre"><input type="text" class="maxwidth50" name="search_id" value="' . dol_escape_htmltag($search_id) . '"></td>';

print '<td class="liste_titre">';
//print $langs->trans("from").': ';
print $form->selectDate($search_start, 'search_start');
//print '<br>';
//print $langs->trans("to").': ';
print $form->selectDate($search_end, 'search_end');
print '</td>';

// User
print '<td class="liste_titre">';
print $form->select_dolusers($search_fk_user, 'search_fk_user', 1, null, 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth200');
print '</td>';

// Actions code
print '<td class="liste_titre">';
print $form->selectarray('search_code', $block_static->trackedevents, $search_code, 1, 0, 0, '', 1, 0, 0, 'ASC', 'maxwidth200', 1);
print '</td>';

// Ref
print '<td class="liste_titre"><input type="text" class="maxwidth50" name="search_ref" value="' . dol_escape_htmltag($search_ref) . '"></td>';

// Link to ref
print '<td class="liste_titre"></td>';

// Amount
print '<td class="liste_titre right"><input type="text" class="maxwidth50" name="search_amount" value="' . dol_escape_htmltag($search_amount) . '"></td>';

// Full data
print '<td class="liste_titre"></td>';

// Fingerprint
print '<td class="liste_titre"></td>';

// Status
print '<td class="liste_titre">';
$array = ["1" => "OnlyNonValid"];
print $form->selectarray('search_showonlyerrors', $array, $search_showonlyerrors, 1, 0, 0, '', 1, 0, 0, 'ASC', 'search_status maxwidth200 onrightofpage', 1);
print '</td>';

// Status note
print '<td class="liste_titre"></td>';

// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
    print '<td class="liste_titre center">';
    $searchpicto = $form->showFilterButtons();
    print $searchpicto;
    print '</td>';
}

print '</tr>';

print '<tr class="liste_titre">';
// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
    print getTitleFieldOfList('<span id="blockchainstatus"></span>', 0, $_SERVER['PHP_SELF'], '', '', $param, 'class="center"', $sortfield, $sortorder, '') . "\n";
}
print getTitleFieldOfList($langs->trans('#'), 0, $_SERVER['PHP_SELF'], 'rowid', '', $param, '', $sortfield, $sortorder, 'minwidth50 ') . "\n";
print getTitleFieldOfList($langs->trans('Date'), 0, $_SERVER['PHP_SELF'], 'date_creation', '', $param, '', $sortfield, $sortorder, '') . "\n";
print getTitleFieldOfList($langs->trans('Author'), 0, $_SERVER['PHP_SELF'], 'user_fullname', '', $param, '', $sortfield, $sortorder, '') . "\n";
print getTitleFieldOfList($langs->trans('Action'), 0, $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, '') . "\n";
print getTitleFieldOfList($langs->trans('Ref'), 0, $_SERVER['PHP_SELF'], 'ref_object', '', $param, '', $sortfield, $sortorder, '') . "\n";
print getTitleFieldOfList('', 0, $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, '') . "\n";
print getTitleFieldOfList($langs->trans('Amount'), 0, $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, 'right ') . "\n";
print getTitleFieldOfList($langs->trans('DataOfArchivedEvent'), 0, $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, 'center ') . "\n";
print getTitleFieldOfList($langs->trans('Fingerprint'), 0, $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, '') . "\n";
print getTitleFieldOfList($langs->trans('Status'), 0, $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, 'center ') . "\n";
print getTitleFieldOfList('', 0, $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, 'center ') . "\n";
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
    print getTitleFieldOfList('<span id="blockchainstatus"></span>', 0, $_SERVER['PHP_SELF'], '', '', $param, 'class="center"', $sortfield, $sortorder, '') . "\n";
}
print '</tr>';

if (getDolGlobalString('BLOCKEDLOG_SCAN_ALL_FOR_LOWERIDINERROR')) {
    // This is version that is faster but require more memory and report errors that are outside the filter range

    // TODO Make a full scan of table in reverse order of id of $block, so we can use the parameter $previoushash into checkSignature to save requests
    // to find the $loweridinerror.
} else {
    // This is version that optimize the memory (but will not report errors that are outside the filter range)
    $loweridinerror = 0;
    $checkresult = [];
    $checkdetail = [];
    if (is_array($blocks)) {
        foreach ($blocks as &$block) {
            $tmpcheckresult = $block->checkSignature('', 1); // Note: this make a sql request at each call, we can't avoid this as the sorting order is various

            $checksignature = $tmpcheckresult['checkresult'];

            $checkresult[$block->id] = $checksignature; // false if error
            $checkdetail[$block->id] = $tmpcheckresult;

            if (!$checksignature) {
                if (empty($loweridinerror)) {
                    $loweridinerror = $block->id;
                } else {
                    $loweridinerror = min($loweridinerror, $block->id);
                }
            }
        }
    }
}

if (is_array($blocks)) {
    $nbshown = 0;
    $MAXFORSHOWLINK = 100;
    $object_link = '';
    $object_link_title = '';

    foreach ($blocks as &$block) {
        //if (empty($search_showonlyerrors) || ! $checkresult[$block->id] || ($loweridinerror && $block->id >= $loweridinerror))
        if (empty($search_showonlyerrors) || !$checkresult[$block->id]) {
            $nbshown++;

            if ($nbshown < $MAXFORSHOWLINK) {   // For performance and memory purpose, we get/show the link of objects only for the 100 first output
                $object_link = $block->getObjectLink();
                $object_link_title = '';
            } else {
                $object_link = $block->element . '/' . $block->fk_object;
                $object_link_title = $langs->trans('LinkHasBeenDisabledForPerformancePurpose');
            }

            print '<tr class="oddeven">';

            // Action column
            if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                print '<td class="liste_titre">';
                print '</td>';
            }

            // ID
            print '<td>' . dol_escape_htmltag($block->id) . '</td>';

            // Date
            print '<td class="nowraponall">' . dol_print_date($block->date_creation, 'dayhour') . '</td>';

            // User
            print '<td class="tdoverflowmax200" title="' . dol_escape_htmltag($block->user_fullname) . '">';
            //print $block->getUser()
            print dol_escape_htmltag($block->user_fullname);
            print '</td>';

            // Action
            print '<td class="tdoverflowmax250" title="' . dol_escape_htmltag($langs->trans('log' . $block->action)) . '">' . $langs->trans('log' . $block->action) . '</td>';

            // Ref
            print '<td class="nowraponall">';
            print dol_escape_htmltag($block->ref_object);
            print '</td>';

            // Link to source object
            print '<td class="tdoverflowmax150"' . (preg_match('/<a/', $object_link) ? '' : 'title="' . dol_escape_htmltag(dol_string_nohtmltag($object_link . ' - ' . $object_link_title)) . '"') . '>';
            print '<!-- object_link -->';   // $object_link can be a '<a href' link or a text
            print $object_link;
            print '</td>';

            // Amount
            print '<td class="right nowraponall">' . price($block->amounts) . '</td>';

            // Details link
            print '<td class="center"><a href="#" data-blockid="' . $block->id . '" rel="show-info">' . img_info($langs->trans('ShowDetails')) . '</a></td>';

            // Fingerprint
            print '<td class="nowraponall">';
            $texttoshow = $langs->trans("Fingerprint") . ' - ' . $langs->trans("Saved") . ':<br>' . $block->signature;
            $texttoshow .= '<br><br>' . $langs->trans("Fingerprint") . ' - Recalculated sha256(previoushash * data):<br>' . $checkdetail[$block->id]['calculatedsignature'];
            $texttoshow .= '<br><span class="opacitymedium">' . $langs->trans("PreviousHash") . '=' . $checkdetail[$block->id]['previoushash'] . '</span>';
            //$texttoshow .= '<br>keyforsignature='.$checkdetail[$block->id]['keyforsignature'];
            print $form->textwithpicto(dol_trunc($block->signature, '8'), $texttoshow, 1, 'help', '', 0, 2, 'fingerprint' . $block->id);
            print '</td>';

            // Status
            print '<td class="center">';
            if (!$checkresult[$block->id] || ($loweridinerror && $block->id >= $loweridinerror)) {  // If error
                if ($checkresult[$block->id]) {
                    print '<span class="badge badge-status4 badge-status" title="' . $langs->trans('OkCheckFingerprintValidityButChainIsKo') . '">OK</span>';
                } else {
                    print '<span class="badge badge-status8 badge-status" title="' . $langs->trans('KoCheckFingerprintValidity') . '">KO</span>';
                }
            } else {
                print '<span class="badge badge-status4 badge-status" title="' . $langs->trans('OkCheckFingerprintValidity') . '">OK</span>';
            }
            print '</td>';

            // Note
            print '<td class="center">';
            if (!$checkresult[$block->id] || ($loweridinerror && $block->id >= $loweridinerror)) {  // If error
                if ($checkresult[$block->id]) {
                    print $form->textwithpicto('', $langs->trans('OkCheckFingerprintValidityButChainIsKo'));
                }
            }

            if (getDolGlobalString('BLOCKEDLOG_USE_REMOTE_AUTHORITY') && getDolGlobalString('BLOCKEDLOG_AUTHORITY_URL')) {
                print ' ' . ($block->certified ? img_picto($langs->trans('AddedByAuthority'), 'info') : img_picto($langs->trans('NotAddedByAuthorityYet'), 'info_black'));
            }
            print '</td>';

            // Action column
            if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                print '<td class="liste_titre">';
                print '</td>';
            }

            print '</tr>';
        }
    }

    if ($nbshown == 0) {
        print '<tr><td colspan="12"><span class="opacitymedium">' . $langs->trans("NoRecordFound") . '</span></td></tr>';
    }
}

print '</table>';

print '</div>';

print '</form>';

// Javascript to manage the showinfo popup
print '<script type="text/javascript">

jQuery(document).ready(function () {
	jQuery("#dialogforpopup").dialog(
	{ closeOnEscape: true, classes: { "ui-dialog": "highlight" },
	maxHeight: window.innerHeight-60, height: window.innerHeight-60, width: ' . ($conf->browser->layout == 'phone' ? 400 : 700) . ',
	modal: true,
	autoOpen: false }).css("z-index: 5000");

	$("a[rel=show-info]").click(function() {

	    console.log("We click on tooltip, we open popup and get content using an ajax call");

		var fk_block = $(this).attr("data-blockid");

		$.ajax({
			method: "GET",
			data: { token: \'' . currentToken() . '\' },
			url: "' . DOL_URL_ROOT . '/blockedlog/ajax/block-info.php?id="+fk_block,
			dataType: "html"
		}).done(function(data) {
			jQuery("#dialogforpopup").html(data);
		});

		jQuery("#dialogforpopup").dialog("open");
	});
})
</script>' . "\n";


if (getDolGlobalString('BLOCKEDLOG_USE_REMOTE_AUTHORITY') && getDolGlobalString('BLOCKEDLOG_AUTHORITY_URL')) {
    ?>
    <script type="text/javascript">

        $.ajax({
            method: "GET",
            data: {token: '<?php echo currentToken() ?>'},
            url: '<?php echo DOL_URL_ROOT . '/blockedlog/ajax/check_signature.php' ?>',
            dataType: 'html'
        }).done(function (data) {
            if (data == 'hashisok') {
                $('#blockchainstatus').html('<?php echo $langs->trans('AuthorityReconizeFingerprintConformity') . ' ' . img_picto($langs->trans('SignatureOK'), 'on') ?>');
            } else {
                $('#blockchainstatus').html('<?php echo $langs->trans('AuthorityDidntReconizeFingerprintConformity') . ' ' . img_picto($langs->trans('SignatureKO'), 'off') ?>');
            }

        });

    </script>
    <?php
}

if (GETPOST('withtab', 'alpha')) {
    print dol_get_fiche_end();
}

print '<br><br>';

// End of page
llxFooter();
