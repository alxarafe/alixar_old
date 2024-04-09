<?php

$form = new Form($db);

$help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';
$title = $langs->trans('ExportOptions');
llxHeader('', $title, $help_url);

$linkback = '';
// $linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans('ExportOptions'), $linkback, 'accountancy');


print "\n" . '<script type="text/javascript">' . "\n";
print 'jQuery(document).ready(function () {' . "\n";
print '    function initfields()' . "\n";
print '    {' . "\n";
foreach ($listparam as $key => $param) {
    print '        if (jQuery("#ACCOUNTING_EXPORT_MODELCSV").val()=="' . $key . '")' . "\n";
    print '        {' . "\n";
    print '            //console.log("' . $param['label'] . '");' . "\n";
    if (empty($param['ACCOUNTING_EXPORT_FORMAT'])) {
        print '            jQuery("#ACCOUNTING_EXPORT_FORMAT").val("' . getDolGlobalString('ACCOUNTING_EXPORT_FORMAT') . '");' . "\n";
        print '            jQuery("#ACCOUNTING_EXPORT_FORMAT").prop("disabled", true);' . "\n";
    } else {
        print '            jQuery("#ACCOUNTING_EXPORT_FORMAT").val("' . $param['ACCOUNTING_EXPORT_FORMAT'] . '");' . "\n";
        print '            jQuery("#ACCOUNTING_EXPORT_FORMAT").removeAttr("disabled");' . "\n";
    }
    if (empty($param['ACCOUNTING_EXPORT_SEPARATORCSV'])) {
        print '            jQuery("#ACCOUNTING_EXPORT_SEPARATORCSV").val("");' . "\n";
        print '            jQuery("#ACCOUNTING_EXPORT_SEPARATORCSV").prop("disabled", true);' . "\n";
    } else {
        print '            jQuery("#ACCOUNTING_EXPORT_SEPARATORCSV").val("' . getDolGlobalString('ACCOUNTING_EXPORT_SEPARATORCSV') . '");' . "\n";
        print '            jQuery("#ACCOUNTING_EXPORT_SEPARATORCSV").removeAttr("disabled");' . "\n";
    }
    if (empty($param['ACCOUNTING_EXPORT_ENDLINE'])) {
        print '            jQuery("#ACCOUNTING_EXPORT_ENDLINE").prop("disabled", true);' . "\n";
    } else {
        print '            jQuery("#ACCOUNTING_EXPORT_ENDLINE").removeAttr("disabled");' . "\n";
    }
    if (empty($param['ACCOUNTING_EXPORT_DATE'])) {
        print '            jQuery("#ACCOUNTING_EXPORT_DATE").val("");' . "\n";
        print '            jQuery("#ACCOUNTING_EXPORT_DATE").prop("disabled", true);' . "\n";
    } else {
        print '            jQuery("#ACCOUNTING_EXPORT_DATE").val("' . getDolGlobalString('ACCOUNTING_EXPORT_DATE') . '");' . "\n";
        print '            jQuery("#ACCOUNTING_EXPORT_DATE").removeAttr("disabled");' . "\n";
    }
    print '        }' . "\n";
}
print '    }' . "\n";
print '    initfields();' . "\n";
print '    jQuery("#ACCOUNTING_EXPORT_MODELCSV").change(function() {' . "\n";
print '        initfields();' . "\n";
print '    });' . "\n";
print '})' . "\n";
print '</script>' . "\n";

print '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="update">';

/*
 * Main Options
 */

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="3">' . $langs->trans('Options') . '</td>';
print "</tr>\n";

$num = count($main_option);
if ($num) {
    foreach ($main_option as $key) {
        print '<tr class="oddeven value">';

        // Param
        $label = $langs->trans($key);
        print '<td width="50%">' . $label . '</td>';

        // Value
        print '<td>';
        print '<input type="text" size="20" id="' . $key . '" name="' . $key . '" value="' . getDolGlobalString($key) . '">';
        print '</td></tr>';
    }
}

print "</table>\n";

print "<br>\n";

/*
 * Export model
 */
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td colspan="2">' . $langs->trans("Modelcsv") . '</td>';
print '</tr>';


print '<tr class="oddeven">';
print '<td width="50%">' . $langs->trans("Selectmodelcsv") . '</td>';
if (!$conf->use_javascript_ajax) {
    print '<td class="nowrap">';
    print $langs->trans("NotAvailableWhenAjaxDisabled");
    print "</td>";
} else {
    print '<td>';
    $listmodelcsv = $accountancyexport->getType();
    print $form->selectarray("ACCOUNTING_EXPORT_MODELCSV", $listmodelcsv, getDolGlobalString('ACCOUNTING_EXPORT_MODELCSV'), 0, 0, 0, '', 0, 0, 0, '', '', 1);

    print '</td>';
}
print "</td></tr>";
print "</table>";

print "<br>\n";

/*
 *  Parameters
 */

$num2 = count($model_option);
if ($num2) {
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td colspan="3">' . $langs->trans('OtherOptions') . '</td>';
    print "</tr>\n";

    foreach ($model_option as $key) {
        print '<tr class="oddeven value">';

        // Param
        $label = $key['label'];
        print '<td width="50%">' . $langs->trans($label) . '</td>';

        // Value
        print '<td>';
        if (is_array($key['param'])) {
            print $form->selectarray($label, $key['param'], getDolGlobalString($label), 0);
        } else {
            print '<input type="text" size="20" id="' . $label . '" name="' . $key['label'] . '" value="' . getDolGlobalString($label) . '">';
        }

        print '</td></tr>';
    }

    print "</table>\n";
}

print '<div class="center"><input type="submit" class="button" value="' . dol_escape_htmltag($langs->trans('Modify')) . '" name="button"></div>';

print '</form>';

// End of page
llxFooter();
