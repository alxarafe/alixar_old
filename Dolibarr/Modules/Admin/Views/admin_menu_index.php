<?php

require_once BASE_PATH . '/core/lib/treeview.lib.php';

use DoliCore\Form\Form;
use DoliCore\Form\FormAdmin;

$form = new Form($db);
$formadmin = new FormAdmin($db);

$arrayofjs = ['/Templates/Lib/jquery/plugins/jquerytreeview/jquery.treeview.js', '/Templates/Lib/jquery/plugins/jquerytreeview/lib/jquery.cookie.js'];
$arrayofcss = ['/Templates/Lib/jquery/plugins/jquerytreeview/jquery.treeview.css'];

llxHeader('', $langs->trans("Menus"), '', '', 0, 0, $arrayofjs, $arrayofcss);


print load_fiche_titre($langs->trans("Menus"), '', 'title_setup');


$h = 0;
$head = [];

$head[$h][0] = DOL_URL_ROOT . "/admin/menus.php";
$head[$h][1] = $langs->trans("MenuHandlers");
$head[$h][2] = 'handler';
$h++;

$head[$h][0] = DOL_URL_ROOT . "/admin/menus/index.php";
$head[$h][1] = $langs->trans("MenuAdmin");
$head[$h][2] = 'editor';
$h++;

print dol_get_fiche_head($head, 'editor', '', -1);

print '<span class="opacitymedium hideonsmartphone">' . $langs->trans("MenusEditorDesc") . "</span><br>\n";
print "<br>\n";


// Confirmation for remove menu entry
if ($action == 'delete') {
    $sql = "SELECT m.titre as title";
    $sql .= " FROM " . MAIN_DB_PREFIX . "menu as m";
    $sql .= " WHERE m.rowid = " . GETPOSTINT('menuId');
    $result = $db->query($sql);
    $obj = $db->fetch_object($result);

    print $form->formconfirm("index.php?menu_handler=" . $menu_handler . "&menuId=" . GETPOSTINT('menuId'), $langs->trans("DeleteMenu"), $langs->trans("ConfirmDeleteMenu", $obj->title), "confirm_delete");
}

$newcardbutton = '';
if ($user->admin) {
    $newcardbutton .= dolGetButtonTitle($langs->trans('New'), '', 'fa fa-plus-circle', DOL_URL_ROOT . '/admin/menus/edit.php?menuId=0&action=create&menu_handler=' . urlencode($menu_handler) . '&backtopage=' . urlencode($_SERVER['PHP_SELF']));
}

print '<form name="newmenu" class="nocellnopadd" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" action="change_menu_handler">';
print $langs->trans("MenuHandler") . ': ';
$formadmin->select_menu_families($menu_handler . (preg_match('/_menu/', $menu_handler) ? '' : '_menu'), 'menu_handler', array_merge($dirstandard, $dirsmartphone));
print ' &nbsp; <input type="submit" class="button small" value="' . $langs->trans("Refresh") . '">';

print '<div class="floatright">';
print $newcardbutton;
print '</div>';

print '</form>';

print '<br>';


// MENU TREE


/*-------------------- MAIN -----------------------
Array of the menu tree:
- Is an array in with 2 dimensions.
- A single line represents an item : data[$x]
- Each line has 3 data items:
- The index of the item;
- The index of the item's parent;
- The string to show
i.e.: data[]= array (index, parent index, string )
*/

// First the root item of the tree must be declared:

$data = [];
$data[] = ['rowid' => 0, 'fk_menu' => -1, 'title' => "racine", 'mainmenu' => '', 'leftmenu' => '', 'fk_mainmenu' => '', 'fk_leftmenu' => ''];

// Then all child items must be declared

$sql = "SELECT m.rowid, m.titre, m.langs, m.mainmenu, m.leftmenu, m.fk_menu, m.fk_mainmenu, m.fk_leftmenu, m.position, m.module";
$sql .= " FROM " . MAIN_DB_PREFIX . "menu as m";
$sql .= " WHERE menu_handler = '" . $db->escape($menu_handler_to_search) . "'";
$sql .= " AND entity = " . $conf->entity;
//$sql.= " AND fk_menu >= 0";
$sql .= " ORDER BY m.position, m.rowid"; // Order is position then rowid (because we need a sort criteria when position is same)

$res = $db->query($sql);
if ($res) {
    $num = $db->num_rows($res);

    $i = 1;
    while ($menu = $db->fetch_array($res)) {
        if (!empty($menu['langs'])) {
            $langs->load($menu['langs']);
        }
        $titre = $langs->trans($menu['titre']);

        $entry = '<table class="nobordernopadding centpercent"><tr><td class="tdoverflowmax200">';
        $entry .= '<strong class="paddingleft"><a href="edit.php?menu_handler=' . $menu_handler_to_search . '&action=edit&token=' . newToken() . '&menuId=' . $menu['rowid'] . '">' . $titre . '</a></strong>';
        $entry .= '</td>';
        $entry .= '<td class="right nowraponall">';
        $entry .= '<a class="editfielda marginleftonly marginrightonly" href="edit.php?menu_handler=' . $menu_handler_to_search . '&action=edit&token=' . newToken() . '&menuId=' . $menu['rowid'] . '">' . img_edit('default', 0, 'class="menuEdit" id="edit' . $menu['rowid'] . '"') . '</a> ';
        $entry .= '<a class="marginleftonly marginrightonly" href="edit.php?menu_handler=' . $menu_handler_to_search . '&action=create&token=' . newToken() . '&menuId=' . $menu['rowid'] . '">' . img_edit_add('default') . '</a> ';
        $entry .= '<a class="marginleftonly marginrightonly" href="index.php?menu_handler=' . $menu_handler_to_search . '&action=delete&token=' . newToken() . '&menuId=' . $menu['rowid'] . '">' . img_delete('default') . '</a> ';
        $entry .= '&nbsp; ';
        $entry .= '<a class="marginleftonly marginrightonly" href="index.php?menu_handler=' . $menu_handler_to_search . '&action=up&token=' . newToken() . '&menuId=' . $menu['rowid'] . '">' . img_picto("Up", "1uparrow") . '</a><a href="index.php?menu_handler=' . $menu_handler_to_search . '&action=down&menuId=' . $menu['rowid'] . '">' . img_picto("Down", "1downarrow") . '</a>';
        $entry .= '</td></tr></table>';

        $buttons = '<a class="editfielda marginleftonly marginrightonly" href="edit.php?menu_handler=' . $menu_handler_to_search . '&action=edit&token=' . newToken() . '&menuId=' . $menu['rowid'] . '">' . img_edit('default', 0, 'class="menuEdit" id="edit' . $menu['rowid'] . '"') . '</a> ';
        $buttons .= '<a class="marginleftonly marginrightonly" href="edit.php?menu_handler=' . $menu_handler_to_search . '&action=create&token=' . newToken() . '&menuId=' . $menu['rowid'] . '">' . img_edit_add('default') . '</a> ';
        $buttons .= '<a class="marginleftonly marginrightonly" href="index.php?menu_handler=' . $menu_handler_to_search . '&action=delete&token=' . newToken() . '&menuId=' . $menu['rowid'] . '">' . img_delete('default') . '</a> ';
        $buttons .= '&nbsp; ';
        $buttons .= '<a class="marginleftonly marginrightonly" href="index.php?menu_handler=' . $menu_handler_to_search . '&action=up&token=' . newToken() . '&menuId=' . $menu['rowid'] . '">' . img_picto("Up", "1uparrow") . '</a><a href="index.php?menu_handler=' . $menu_handler_to_search . '&action=down&menuId=' . $menu['rowid'] . '">' . img_picto("Down", "1downarrow") . '</a>';

        $data[] = [
            'rowid' => $menu['rowid'],
            'module' => $menu['module'],
            'fk_menu' => $menu['fk_menu'],
            'title' => $titre,
            'mainmenu' => $menu['mainmenu'],
            'leftmenu' => $menu['leftmenu'],
            'fk_mainmenu' => $menu['fk_mainmenu'],
            'fk_leftmenu' => $menu['fk_leftmenu'],
            'position' => $menu['position'],
            'entry' => $entry,
            'buttons' => $buttons,
        ];
        $i++;
    }
}

global $tree_recur_alreadyadded; // This var was def into tree_recur

//var_dump($data);

print '<div class="div-table-responsive">';
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td>' . $langs->trans("TreeMenuPersonalized") . '</td>';
print '<td class="right"><div id="iddivjstreecontrol"><a href="#">' . img_picto('', 'folder', 'class="paddingright"') . $langs->trans("UndoExpandAll") . '</a>';
print ' | <a href="#">' . img_picto('', 'folder-open', 'class="paddingright"') . $langs->trans("ExpandAll") . '</a></div></td>';
print '</tr>';

print '<tr>';
print '<td colspan="2">';


//tree_recur($data, $data[0], 0, 'iddivjstree', 0, 1);  // use this to get info on name and foreign keys of menu entry
tree_recur($data, $data[0], 0, 'iddivjstree', 0, 0); // $data[0] is virtual record 'racine'


print '</td>';
print '</tr>';

print '</table>';
print '</div>';

// Process remaining records (records that are not linked to root by any path)
$remainingdata = [];
foreach ($data as $datar) {
    if (empty($datar['rowid']) || !empty($tree_recur_alreadyadded[$datar['rowid']])) {
        continue;
    }
    $remainingdata[] = $datar;
}

if (count($remainingdata)) {
    print '<div class="div-table-responsive">';
    print '<table class="noborder centpercent">';

    print '<tr class="liste_titre">';
    print '<td>' . $langs->trans("NotTopTreeMenuPersonalized") . '</td>';
    print '<td class="right"></td>';
    print '</tr>';

    print '<tr>';
    print '<td colspan="2">';
    foreach ($remainingdata as $datar) {
        $father = ['rowid' => $datar['rowid'], 'title' => "???", 'mainmenu' => $datar['fk_mainmenu'], 'leftmenu' => $datar['fk_leftmenu'], 'fk_mainmenu' => '', 'fk_leftmenu' => ''];
        //print 'Start with rowid='.$datar['rowid'].' mainmenu='.$father ['mainmenu'].' leftmenu='.$father ['leftmenu'].'<br>'."\n";
        tree_recur($data, $father, 0, 'iddivjstree' . $datar['rowid'], 1, 1);
    }

    print '</td>';

    print '</tr>';

    print '</table>';
    print '</div>';
}

print '<br>';

// End of page
llxFooter();
