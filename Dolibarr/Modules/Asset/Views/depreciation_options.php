<?php

$form = new Form($db);

$help_url = '';
llxHeader('', $langs->trans('Asset'), $help_url, '', 0, 0, '', '', '', 'mod-asset page-card_depreciation_options');

if ($id > 0 || !empty($ref)) {
    $head = assetPrepareHead($object);
    print dol_get_fiche_head($head, 'depreciation_options', $langs->trans("Asset"), -1, $object->picto);

    // Object card
    // ------------------------------------------------------------
    $linkback = '<a href="' . DOL_URL_ROOT . '/asset/list.php?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

    $morehtmlref = '<div class="refidno">';
    $morehtmlref .= '</div>';

    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

    print '<div class="fichecenter">';
    print '<div class="underbanner clearboth"></div>';
    print '</div>';

    if ($action == 'edit') {
        print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '">';
        print '<input type="hidden" name="token" value="' . newToken() . '">';
        print '<input type="hidden" name="action" value="update">';
        if ($backtopage) {
            print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
        }
        if ($backtopageforcancel) {
            print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';
        }

        print dol_get_fiche_head([], '');

        include DOL_DOCUMENT_ROOT . '/asset/tpl/depreciation_options_edit.tpl.php';

        print dol_get_fiche_end();

        print $form->buttonsSaveCancel();

        print '</form>';
    } else {
        include DOL_DOCUMENT_ROOT . '/asset/tpl/depreciation_options_view.tpl.php';
    }

    print dol_get_fiche_end();

    if ($action != 'edit') {
        print '<div class="tabsAction">' . "\n";
        $parameters = [];
        $reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

        if (empty($reshook)) {
            if ($object->status == $object::STATUS_DRAFT/* && !empty($object->enabled_modes)*/) {
                print dolGetButtonAction($langs->trans('Modify'), '', 'default', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=edit&token=' . newToken(), '', $permissiontoadd);
            }
        }
        print '</div>' . "\n";
    }
}

// End of page
llxFooter();
