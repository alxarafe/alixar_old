<?php

use DoliCore\Form\Form;
use DoliCore\Form\FormActions;
use DoliCore\Form\FormAdmin;
use DoliCore\Form\FormCompany;
use DoliCore\Form\FormFile;
use DoliModules\Adherent\Model\Adherent;
use DoliModules\Adherent\Model\AdherentType;
use DoliModules\Category\Model\Categorie;
use DoliModules\Company\Model\Company;

$form = new Form($db);
$formfile = new FormFile($db);
$formadmin = new FormAdmin($db);
$formcompany = new FormCompany($db);

$title = $langs->trans("Member") . " - " . $langs->trans("Card");
$help_url = 'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros|DE:Modul_Mitglieder';
llxHeader('', $title, $help_url);

$countrynotdefined = $langs->trans("ErrorSetACountryFirst") . ' (' . $langs->trans("SeeAbove") . ')';

if (is_object($objcanvas) && $objcanvas->displayCanvasExists($action)) {
    // -----------------------------------------
    // When used with CANVAS
    // -----------------------------------------
    if (empty($object->error) && $id) {
        $object = new Adherent($db);
        $result = $object->fetch($id);
        if ($result <= 0) {
            dol_print_error(null, $object->error);
        }
    }
    $objcanvas->assign_values($action, $object->id, $object->ref); // Set value for templates
    $objcanvas->display_canvas($action); // Show template
} else {
    // -----------------------------------------
    // When used in standard mode
    // -----------------------------------------

    // Create mode
    if ($action == 'create') {
        $object->canvas = $canvas;
        $object->state_id = GETPOSTINT('state_id');

        // We set country_id, country_code and country for the selected country
        $object->country_id = GETPOSTINT('country_id') ? GETPOSTINT('country_id') : $mysoc->country_id;
        if ($object->country_id) {
            $tmparray = getCountry($object->country_id, 'all');
            $object->country_code = $tmparray['code'];
            $object->country = $tmparray['label'];
        }

        $soc = new Company($db);
        if (!empty($socid)) {
            if ($socid > 0) {
                $soc->fetch($socid);
            }

            if (!($soc->id > 0)) {
                $langs->load("errors");
                print($langs->trans('ErrorRecordNotFound'));
                exit;
            }
        }

        $adht = new AdherentType($db);

        print load_fiche_titre($langs->trans("NewMember"), '', $object->picto);

        if ($conf->use_javascript_ajax) {
            print "\n" . '<script type="text/javascript">' . "\n";
            print 'jQuery(document).ready(function () {
						jQuery("#selectcountry_id").change(function() {
							document.formsoc.action.value="create";
							document.formsoc.submit();
						});
						function initfieldrequired() {
							jQuery("#tdcompany").removeClass("fieldrequired");
							jQuery("#tdlastname").removeClass("fieldrequired");
							jQuery("#tdfirstname").removeClass("fieldrequired");
							if (jQuery("#morphy").val() == \'mor\') {
								jQuery("#tdcompany").addClass("fieldrequired");
							}
							if (jQuery("#morphy").val() == \'phy\') {
								jQuery("#tdlastname").addClass("fieldrequired");
								jQuery("#tdfirstname").addClass("fieldrequired");
							}
						}
						jQuery("#morphy").change(function() {
							initfieldrequired();
						});
						initfieldrequired();
					})';
            print '</script>' . "\n";
        }

        print '<form name="formsoc" action="' . $_SERVER['PHP_SELF'] . '" method="post" enctype="multipart/form-data">';
        print '<input type="hidden" name="token" value="' . newToken() . '">';
        print '<input type="hidden" name="action" value="add">';
        print '<input type="hidden" name="socid" value="' . $socid . '">';
        if ($backtopage) {
            print '<input type="hidden" name="backtopage" value="' . ($backtopage != '1' ? $backtopage : $_SERVER["HTTP_REFERER"]) . '">';
        }

        print dol_get_fiche_head('');

        print '<table class="border centpercent">';
        print '<tbody>';

        // Login
        if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
            print '<tr><td><span class="fieldrequired">' . $langs->trans("Login") . ' / ' . $langs->trans("Id") . '</span></td><td><input type="text" name="member_login" class="minwidth300" maxlength="50" value="' . (GETPOSTISSET("member_login") ? GETPOST("member_login", 'alphanohtml', 2) : $object->login) . '" autofocus="autofocus"></td></tr>';
        }

        // Password
        if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
            require_once DOL_DOCUMENT_ROOT . '/core/lib/security2.lib.php';
            $generated_password = getRandomPassword(false);
            print '<tr><td><span class="fieldrequired">' . $langs->trans("Password") . '</span></td><td>';
            print '<input type="text" class="minwidth300" maxlength="50" name="password" value="' . dol_escape_htmltag($generated_password) . '">';
            print '</td></tr>';
        }

        // Type
        print '<tr><td class="fieldrequired">' . $langs->trans("MemberType") . '</td><td>';
        $listetype = $adht->liste_array(1);
        print img_picto('', $adht->picto, 'class="pictofixedwidth"');
        if (count($listetype)) {
            print $form->selectarray("typeid", $listetype, (GETPOSTINT('typeid') ? GETPOSTINT('typeid') : $typeid), (count($listetype) > 1 ? 1 : 0), 0, 0, '', 0, 0, 0, '', '', 1);
        } else {
            print '<span class="error">' . $langs->trans("NoTypeDefinedGoToSetup") . '</span>';
        }
        print "</td>\n";

        // Morphy
        $morphys = [];
        $morphys["phy"] = $langs->trans("Physical");
        $morphys["mor"] = $langs->trans("Moral");
        print '<tr><td class="fieldrequired">' . $langs->trans("MemberNature") . "</td><td>\n";
        print $form->selectarray("morphy", $morphys, (GETPOST('morphy', 'alpha') ? GETPOST('morphy', 'alpha') : $object->morphy), 1, 0, 0, '', 0, 0, 0, '', '', 1);
        print "</td>\n";

        // Company
        print '<tr><td id="tdcompany">' . $langs->trans("Company") . '</td><td><input type="text" name="societe" class="minwidth300" maxlength="128" value="' . (GETPOSTISSET('societe') ? GETPOST('societe', 'alphanohtml') : $soc->name) . '"></td></tr>';

        // Civility
        print '<tr><td>' . $langs->trans("UserTitle") . '</td><td>';
        print $formcompany->select_civility(GETPOSTINT('civility_id') ? GETPOSTINT('civility_id') : $object->civility_id, 'civility_id', 'maxwidth150', 1) . '</td>';
        print '</tr>';

        // Lastname
        print '<tr><td id="tdlastname">' . $langs->trans("Lastname") . '</td><td><input type="text" name="lastname" class="minwidth300" maxlength="50" value="' . (GETPOSTISSET('lastname') ? GETPOST('lastname', 'alphanohtml') : $object->lastname) . '"></td>';
        print '</tr>';

        // Firstname
        print '<tr><td id="tdfirstname">' . $langs->trans("Firstname") . '</td><td><input type="text" name="firstname" class="minwidth300" maxlength="50" value="' . (GETPOSTISSET('firstname') ? GETPOST('firstname', 'alphanohtml') : $object->firstname) . '"></td>';
        print '</tr>';

        // Gender
        print '<tr><td>' . $langs->trans("Gender") . '</td>';
        print '<td>';
        $arraygender = ['man' => $langs->trans("Genderman"), 'woman' => $langs->trans("Genderwoman"), 'other' => $langs->trans("Genderother")];
        print $form->selectarray('gender', $arraygender, GETPOST('gender', 'alphanohtml'), 1, 0, 0, '', 0, 0, 0, '', '', 1);
        print '</td></tr>';

        // EMail
        print '<tr><td>' . (getDolGlobalString('ADHERENT_MAIL_REQUIRED') ? '<span class="fieldrequired">' : '') . $langs->trans("EMail") . (getDolGlobalString('ADHERENT_MAIL_REQUIRED') ? '</span>' : '') . '</td>';
        print '<td>' . img_picto('', 'object_email') . ' <input type="text" name="member_email" class="minwidth300" maxlength="255" value="' . (GETPOSTISSET('member_email') ? GETPOST('member_email', 'alpha') : $soc->email) . '"></td></tr>';

        // Website
        print '<tr><td>' . $form->editfieldkey('Web', 'member_url', GETPOST('member_url', 'alpha'), $object, 0) . '</td>';
        print '<td>' . img_picto('', 'globe') . ' <input type="text" class="maxwidth500 widthcentpercentminusx" name="member_url" id="member_url" value="' . (GETPOSTISSET('member_url') ? GETPOST('member_url', 'alpha') : $object->url) . '"></td></tr>';

        // Address
        print '<tr><td class="tdtop">' . $langs->trans("Address") . '</td><td>';
        print '<textarea name="address" wrap="soft" class="quatrevingtpercent" rows="2">' . (GETPOSTISSET('address') ? GETPOST('address', 'alphanohtml') : $soc->address) . '</textarea>';
        print '</td></tr>';

        // Zip / Town
        print '<tr><td>' . $langs->trans("Zip") . ' / ' . $langs->trans("Town") . '</td><td>';
        print $formcompany->select_ziptown((GETPOSTISSET('zipcode') ? GETPOST('zipcode', 'alphanohtml') : $soc->zip), 'zipcode', ['town', 'selectcountry_id', 'state_id'], 6);
        print ' ';
        print $formcompany->select_ziptown((GETPOSTISSET('town') ? GETPOST('town', 'alphanohtml') : $soc->town), 'town', ['zipcode', 'selectcountry_id', 'state_id']);
        print '</td></tr>';

        // Country
        if (empty($soc->country_id)) {
            $soc->country_id = $mysoc->country_id;
            $soc->country_code = $mysoc->country_code;
            $soc->state_id = $mysoc->state_id;
        }
        print '<tr><td>' . $langs->trans('Country') . '</td><td>';
        print img_picto('', 'country', 'class="pictofixedwidth"');
        print $form->select_country(GETPOSTISSET('country_id') ? GETPOST('country_id', 'alpha') : $soc->country_id, 'country_id');
        if ($user->admin) {
            print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
        }
        print '</td></tr>';

        // State
        if (!getDolGlobalString('MEMBER_DISABLE_STATE')) {
            print '<tr><td>' . $langs->trans('State') . '</td><td>';
            if ($soc->country_id) {
                print img_picto('', 'state', 'class="pictofixedwidth"');
                print $formcompany->select_state(GETPOSTISSET('state_id') ? GETPOSTINT('state_id') : $soc->state_id, $soc->country_code);
            } else {
                print $countrynotdefined;
            }
            print '</td></tr>';
        }

        // Pro phone
        print '<tr><td>' . $langs->trans("PhonePro") . '</td>';
        print '<td>' . img_picto('', 'object_phoning', 'class="pictofixedwidth"') . '<input type="text" name="phone" size="20" value="' . (GETPOSTISSET('phone') ? GETPOST('phone', 'alpha') : $soc->phone) . '"></td></tr>';

        // Personal phone
        print '<tr><td>' . $langs->trans("PhonePerso") . '</td>';
        print '<td>' . img_picto('', 'object_phoning', 'class="pictofixedwidth"') . '<input type="text" name="phone_perso" size="20" value="' . (GETPOSTISSET('phone_perso') ? GETPOST('phone_perso', 'alpha') : $object->phone_perso) . '"></td></tr>';

        // Mobile phone
        print '<tr><td>' . $langs->trans("PhoneMobile") . '</td>';
        print '<td>' . img_picto('', 'object_phoning_mobile', 'class="pictofixedwidth"') . '<input type="text" name="phone_mobile" size="20" value="' . (GETPOSTISSET('phone_mobile') ? GETPOST('phone_mobile', 'alpha') : $object->phone_mobile) . '"></td></tr>';

        if (isModEnabled('socialnetworks')) {
            foreach ($socialnetworks as $key => $value) {
                if (!$value['active']) {
                    break;
                }
                $val = (GETPOSTISSET('member_' . $key) ? GETPOST('member_' . $key, 'alpha') : (empty($object->socialnetworks[$key]) ? '' : $object->socialnetworks[$key]));
                print '<tr><td>' . $langs->trans($value['label']) . '</td><td><input type="text" name="member_' . $key . '" size="40" value="' . $val . '"></td></tr>';
            }
        }

        // Birth Date
        print "<tr><td>" . $langs->trans("DateOfBirth") . "</td><td>\n";
        print img_picto('', 'object_calendar', 'class="pictofixedwidth"') . $form->selectDate(($object->birth ? $object->birth : -1), 'birth', 0, 0, 1, 'formsoc');
        print "</td></tr>\n";

        // Public profil
        print "<tr><td>";
        $htmltext = $langs->trans("Public", getDolGlobalString('MAIN_INFO_SOCIETE_NOM'), $linkofpubliclist);
        print $form->textwithpicto($langs->trans("MembershipPublic"), $htmltext, 1, 'help', '', 0, 3, 'membershippublic');
        print "</td><td>\n";
        print $form->selectyesno("public", $object->public, 1);
        print "</td></tr>\n";

        // Categories
        if (isModEnabled('category') && $user->hasRight('categorie', 'lire')) {
            print '<tr><td>' . $form->editfieldkey("Categories", 'memcats', '', $object, 0) . '</td><td>';
            $cate_arbo = $form->select_all_categories(Categorie::TYPE_MEMBER, null, 'parent', null, null, 1);
            print img_picto('', 'category') . $form->multiselectarray('memcats', $cate_arbo, GETPOST('memcats', 'array'), null, null, 'quatrevingtpercent widthcentpercentminusx', 0, 0);
            print "</td></tr>";
        }

        // Other attributes
        include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';

        print '<tbody>';
        print "</table>\n";

        print dol_get_fiche_end();

        print $form->buttonsSaveCancel("AddMember");

        print "</form>\n";
    }

    // Edit mode
    if ($action == 'edit') {
        $res = $object->fetch($id);
        if ($res < 0) {
            dol_print_error($db, $object->error);
            exit;
        }
        $res = $object->fetch_optionals();
        if ($res < 0) {
            dol_print_error($db);
            exit;
        }

        $adht = new AdherentType($db);
        $adht->fetch($object->typeid);

        // We set country_id, and country_code, country of the chosen country
        $country = GETPOSTINT('country');
        if (!empty($country) || $object->country_id) {
            $sql = "SELECT rowid, code, label from " . MAIN_DB_PREFIX . "c_country";
            $sql .= " WHERE rowid = " . (int) (!empty($country) ? $country : $object->country_id);
            $resql = $db->query($sql);
            if ($resql) {
                $obj = $db->fetch_object($resql);
            } else {
                dol_print_error($db);
            }
            $object->country_id = $obj->rowid;
            $object->country_code = $obj->code;
            $object->country = $langs->trans("Country" . $obj->code) ? $langs->trans("Country" . $obj->code) : $obj->label;
        }

        $head = member_prepare_head($object);


        if ($conf->use_javascript_ajax) {
            print "\n" . '<script type="text/javascript">';
            print 'jQuery(document).ready(function () {
				jQuery("#selectcountry_id").change(function() {
					document.formsoc.action.value="edit";
					document.formsoc.submit();
				});
				function initfieldrequired() {
					jQuery("#tdcompany").removeClass("fieldrequired");
					jQuery("#tdlastname").removeClass("fieldrequired");
					jQuery("#tdfirstname").removeClass("fieldrequired");
					if (jQuery("#morphy").val() == \'mor\') {
						jQuery("#tdcompany").addClass("fieldrequired");
					}
					if (jQuery("#morphy").val() == \'phy\') {
						jQuery("#tdlastname").addClass("fieldrequired");
						jQuery("#tdfirstname").addClass("fieldrequired");
					}
				}
				jQuery("#morphy").change(function() {
					initfieldrequired();
				});
				initfieldrequired();
			})';
            print '</script>' . "\n";
        }

        print '<form name="formsoc" action="' . $_SERVER['PHP_SELF'] . '" method="post" enctype="multipart/form-data">';
        print '<input type="hidden" name="token" value="' . newToken() . '" />';
        print '<input type="hidden" name="action" value="update" />';
        print '<input type="hidden" name="rowid" value="' . $id . '" />';
        print '<input type="hidden" name="statut" value="' . $object->statut . '" />';
        if ($backtopage) {
            print '<input type="hidden" name="backtopage" value="' . ($backtopage != '1' ? $backtopage : $_SERVER["HTTP_REFERER"]) . '">';
        }

        print dol_get_fiche_head($head, 'general', $langs->trans("Member"), 0, 'user');

        print '<table class="border centpercent">';

        // Ref
        print '<tr><td class="titlefieldcreate">' . $langs->trans("Ref") . '</td><td class="valeur">' . $object->ref . '</td></tr>';

        // Login
        if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
            print '<tr><td><span class="fieldrequired">' . $langs->trans("Login") . ' / ' . $langs->trans("Id") . '</span></td><td><input type="text" name="login" class="minwidth300" maxlength="50" value="' . (GETPOSTISSET("login") ? GETPOST("login", 'alphanohtml', 2) : $object->login) . '"></td></tr>';
        }

        // Password
        if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
            print '<tr><td class="fieldrequired">' . $langs->trans("Password") . '</td><td><input type="password" name="pass" class="minwidth300" maxlength="50" value="' . dol_escape_htmltag(GETPOSTISSET("pass") ? GETPOST("pass", 'none', 2) : '') . '"></td></tr>';
        }

        // Type
        print '<tr><td class="fieldrequired">' . $langs->trans("Type") . '</td><td>';
        if ($user->hasRight('adherent', 'creer')) {
            print $form->selectarray("typeid", $adht->liste_array(), (GETPOSTISSET("typeid") ? GETPOSTINT("typeid") : $object->typeid), 0, 0, 0, '', 0, 0, 0, '', '', 1);
        } else {
            print $adht->getNomUrl(1);
            print '<input type="hidden" name="typeid" value="' . $object->typeid . '">';
        }
        print "</td></tr>";

        // Morphy
        $morphys["phy"] = $langs->trans("Physical");
        $morphys["mor"] = $langs->trans("Moral");
        print '<tr><td><span class="fieldrequired">' . $langs->trans("MemberNature") . '</span></td><td>';
        print $form->selectarray("morphy", $morphys, (GETPOSTISSET("morphy") ? GETPOST("morphy", 'alpha') : $object->morphy), 0, 0, 0, '', 0, 0, 0, '', '', 1);
        print "</td></tr>";

        // Company
        print '<tr><td id="tdcompany">' . $langs->trans("Company") . '</td><td><input type="text" name="societe" class="minwidth300" maxlength="128" value="' . (GETPOSTISSET("societe") ? GETPOST("societe", 'alphanohtml', 2) : $object->company) . '"></td></tr>';

        // Civility
        print '<tr><td>' . $langs->trans("UserTitle") . '</td><td>';
        print $formcompany->select_civility(GETPOSTISSET("civility_id") ? GETPOST("civility_id", 'alpha') : $object->civility_id, 'civility_id', 'maxwidth150', 1);
        print '</td>';
        print '</tr>';

        // Lastname
        print '<tr><td id="tdlastname">' . $langs->trans("Lastname") . '</td><td><input type="text" name="lastname" class="minwidth300" maxlength="50" value="' . (GETPOSTISSET("lastname") ? GETPOST("lastname", 'alphanohtml', 2) : $object->lastname) . '"></td>';
        print '</tr>';

        // Firstname
        print '<tr><td id="tdfirstname">' . $langs->trans("Firstname") . '</td><td><input type="text" name="firstname" class="minwidth300" maxlength="50" value="' . (GETPOSTISSET("firstname") ? GETPOST("firstname", 'alphanohtml', 3) : $object->firstname) . '"></td>';
        print '</tr>';

        // Gender
        print '<tr><td>' . $langs->trans("Gender") . '</td>';
        print '<td>';
        $arraygender = ['man' => $langs->trans("Genderman"), 'woman' => $langs->trans("Genderwoman"), 'other' => $langs->trans("Genderother")];
        print $form->selectarray('gender', $arraygender, GETPOSTISSET('gender') ? GETPOST('gender', 'alphanohtml') : $object->gender, 1, 0, 0, '', 0, 0, 0, '', '', 1);
        print '</td></tr>';

        // Photo
        print '<tr><td>' . $langs->trans("Photo") . '</td>';
        print '<td class="hideonsmartphone" valign="middle">';
        print $form->showphoto('memberphoto', $object) . "\n";
        if ($caneditfieldmember) {
            if ($object->photo) {
                print "<br>\n";
            }
            print '<table class="nobordernopadding">';
            if ($object->photo) {
                print '<tr><td><input type="checkbox" class="flat photodelete" name="deletephoto" id="photodelete"> ' . $langs->trans("Delete") . '<br><br></td></tr>';
            }
            print '<tr><td>' . $langs->trans("PhotoFile") . '</td></tr>';
            print '<tr><td>';
            $maxfilesizearray = getMaxFileSizeArray();
            $maxmin = $maxfilesizearray['maxmin'];
            if ($maxmin > 0) {
                print '<input type="hidden" name="MAX_FILE_SIZE" value="' . ($maxmin * 1024) . '">';    // MAX_FILE_SIZE must precede the field type=file
            }
            print '<input type="file" class="flat" name="photo" id="photoinput">';
            print '</td></tr>';
            print '</table>';
        }
        print '</td></tr>';

        // EMail
        print '<tr><td>' . (getDolGlobalString("ADHERENT_MAIL_REQUIRED") ? '<span class="fieldrequired">' : '') . $langs->trans("EMail") . (getDolGlobalString("ADHERENT_MAIL_REQUIRED") ? '</span>' : '') . '</td>';
        print '<td>' . img_picto('', 'object_email', 'class="pictofixedwidth"') . '<input type="text" name="member_email" class="minwidth300" maxlength="255" value="' . (GETPOSTISSET("member_email") ? GETPOST("member_email", '', 2) : $object->email) . '"></td></tr>';

        // Website
        print '<tr><td>' . $form->editfieldkey('Web', 'member_url', GETPOST('member_url', 'alpha'), $object, 0) . '</td>';
        print '<td>' . img_picto('', 'globe', 'class="pictofixedwidth"') . '<input type="text" name="member_url" id="member_url" class="maxwidth200onsmartphone maxwidth500 widthcentpercentminusx " value="' . (GETPOSTISSET('member_url') ? GETPOST('member_url', 'alpha') : $object->url) . '"></td></tr>';

        // Address
        print '<tr><td>' . $langs->trans("Address") . '</td><td>';
        print '<textarea name="address" wrap="soft" class="quatrevingtpercent" rows="' . ROWS_2 . '">' . (GETPOSTISSET("address") ? GETPOST("address", 'alphanohtml', 2) : $object->address) . '</textarea>';
        print '</td></tr>';

        // Zip / Town
        print '<tr><td>' . $langs->trans("Zip") . ' / ' . $langs->trans("Town") . '</td><td>';
        print $formcompany->select_ziptown((GETPOSTISSET("zipcode") ? GETPOST("zipcode", 'alphanohtml', 2) : $object->zip), 'zipcode', ['town', 'selectcountry_id', 'state_id'], 6);
        print ' ';
        print $formcompany->select_ziptown((GETPOSTISSET("town") ? GETPOST("town", 'alphanohtml', 2) : $object->town), 'town', ['zipcode', 'selectcountry_id', 'state_id']);
        print '</td></tr>';

        // Country
        //$object->country_id=$object->country_id?$object->country_id:$mysoc->country_id;    // In edit mode we don't force to company country if not defined
        print '<tr><td>' . $langs->trans('Country') . '</td><td>';
        print img_picto('', 'country', 'class="pictofixedwidth"');
        print $form->select_country(GETPOSTISSET("country_id") ? GETPOST("country_id", "alpha") : $object->country_id, 'country_id');
        if ($user->admin) {
            print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
        }
        print '</td></tr>';

        // State
        if (!getDolGlobalString('MEMBER_DISABLE_STATE')) {
            print '<tr><td>' . $langs->trans('State') . '</td><td>';
            print img_picto('', 'state', 'class="pictofixedwidth"');
            print $formcompany->select_state($object->state_id, GETPOSTISSET("country_id") ? GETPOST("country_id", "alpha") : $object->country_id);
            print '</td></tr>';
        }

        // Pro phone
        print '<tr><td>' . $langs->trans("PhonePro") . '</td>';
        print '<td>' . img_picto('', 'object_phoning', 'class="pictofixedwidth"') . '<input type="text" name="phone" value="' . (GETPOSTISSET("phone") ? GETPOST("phone") : $object->phone) . '"></td></tr>';

        // Personal phone
        print '<tr><td>' . $langs->trans("PhonePerso") . '</td>';
        print '<td>' . img_picto('', 'object_phoning', 'class="pictofixedwidth"') . '<input type="text" name="phone_perso" value="' . (GETPOSTISSET("phone_perso") ? GETPOST("phone_perso") : $object->phone_perso) . '"></td></tr>';

        // Mobile phone
        print '<tr><td>' . $langs->trans("PhoneMobile") . '</td>';
        print '<td>' . img_picto('', 'object_phoning_mobile', 'class="pictofixedwidth"') . '<input type="text" name="phone_mobile" value="' . (GETPOSTISSET("phone_mobile") ? GETPOST("phone_mobile") : $object->phone_mobile) . '"></td></tr>';

        if (isModEnabled('socialnetworks')) {
            foreach ($socialnetworks as $key => $value) {
                if (!$value['active']) {
                    break;
                }
                print '<tr><td>' . $langs->trans($value['label']) . '</td><td><input type="text" name="' . $key . '" class="minwidth100" value="' . (GETPOSTISSET($key) ? GETPOST($key, 'alphanohtml') : (isset($object->socialnetworks[$key]) ? $object->socialnetworks[$key] : null)) . '"></td></tr>';
            }
        }

        // Birth Date
        print "<tr><td>" . $langs->trans("DateOfBirth") . "</td><td>\n";
        print img_picto('', 'object_calendar', 'class="pictofixedwidth"') . $form->selectDate(($object->birth ? $object->birth : -1), 'birth', 0, 0, 1, 'formsoc');
        print "</td></tr>\n";

        // Default language
        if (getDolGlobalInt('MAIN_MULTILANGS')) {
            print '<tr><td>' . $form->editfieldkey('DefaultLang', 'default_lang', '', $object, 0) . '</td><td colspan="3">' . "\n";
            print img_picto('', 'language', 'class="pictofixedwidth"') . $formadmin->select_language($object->default_lang, 'default_lang', 0, 0, 1);
            print '</td>';
            print '</tr>';
        }

        // Public profil
        print "<tr><td>";
        $htmltext = $langs->trans("Public", getDolGlobalString('MAIN_INFO_SOCIETE_NOM'), $linkofpubliclist);
        print $form->textwithpicto($langs->trans("MembershipPublic"), $htmltext, 1, 'help', '', 0, 3, 'membershippublic');
        print "</td><td>\n";
        print $form->selectyesno("public", (GETPOSTISSET("public") ? GETPOST("public", 'alphanohtml', 2) : $object->public), 1);
        print "</td></tr>\n";

        // Categories
        if (isModEnabled('category') && $user->hasRight('categorie', 'lire')) {
            print '<tr><td>' . $form->editfieldkey("Categories", 'memcats', '', $object, 0) . '</td>';
            print '<td>';
            $cate_arbo = $form->select_all_categories(Categorie::TYPE_MEMBER, null, null, null, null, 1);
            $c = new Categorie($db);
            $cats = $c->containing($object->id, Categorie::TYPE_MEMBER);
            $arrayselected = [];
            if (is_array($cats)) {
                foreach ($cats as $cat) {
                    $arrayselected[] = $cat->id;
                }
            }
            print $form->multiselectarray('memcats', $cate_arbo, $arrayselected, '', 0, '', 0, '100%');
            print "</td></tr>";
        }

        // Third party Dolibarr
        if (isModEnabled('societe')) {
            print '<tr><td>' . $langs->trans("LinkedToDolibarrThirdParty") . '</td><td colspan="2" class="valeur">';
            if ($object->socid) {
                $company = new Societe($db);
                $result = $company->fetch($object->socid);
                print $company->getNomUrl(1);
            } else {
                print $langs->trans("NoThirdPartyAssociatedToMember");
            }
            print '</td></tr>';
        }

        // Login Dolibarr
        print '<tr><td>' . $langs->trans("LinkedToDolibarrUser") . '</td><td colspan="2" class="valeur">';
        if ($object->user_id) {
            $form->form_users($_SERVER['PHP_SELF'] . '?rowid=' . $object->id, $object->user_id, 'none');
        } else {
            print $langs->trans("NoDolibarrAccess");
        }
        print '</td></tr>';

        // Other attributes. Fields from hook formObjectOptions and Extrafields.
        include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';

        print '</table>';
        print dol_get_fiche_end();

        print $form->buttonsSaveCancel("Save", 'Cancel');

        print '</form>';
    }

    // View
    if ($id > 0 && $action != 'edit') {
        $res = $object->fetch($id);
        if ($res < 0) {
            dol_print_error($db, $object->error);
            exit;
        }
        $res = $object->fetch_optionals();
        if ($res < 0) {
            dol_print_error($db);
            exit;
        }

        $adht = new AdherentType($db);
        $res = $adht->fetch($object->typeid);
        if ($res < 0) {
            dol_print_error($db);
            exit;
        }

        /*
         * Show tabs
         */
        $head = member_prepare_head($object);

        print dol_get_fiche_head($head, 'general', $langs->trans("Member"), -1, 'user');

        // Confirm create user
        if ($action == 'create_user') {
            $login = (GETPOSTISSET('login') ? GETPOST('login', 'alphanohtml') : $object->login);
            if (empty($login)) {
                // Full firstname and name separated with a dot : firstname.name
                include_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
                $login = dol_buildlogin($object->lastname, $object->firstname);
            }
            if (empty($login)) {
                $login = strtolower(substr($object->firstname, 0, 4)) . strtolower(substr($object->lastname, 0, 4));
            }

            // Create a form array
            $formquestion = [
                ['label' => $langs->trans("LoginToCreate"), 'type' => 'text', 'name' => 'login', 'value' => $login],
            ];
            if (isModEnabled('societe') && $object->socid > 0) {
                $object->fetch_thirdparty();
                $formquestion[] = ['label' => $langs->trans("UserWillBe"), 'type' => 'radio', 'name' => 'internalorexternal', 'default' => 'external', 'values' => ['external' => $langs->trans("External") . ' - ' . $langs->trans("LinkedToDolibarrThirdParty") . ' ' . $object->thirdparty->getNomUrl(1, '', 0, 1), 'internal' => $langs->trans("Internal")]];
            }
            $text = '';
            if (isModEnabled('societe') && $object->socid <= 0) {
                $text .= $langs->trans("UserWillBeInternalUser") . '<br>';
            }
            $text .= $langs->trans("ConfirmCreateLogin");
            print $form->formconfirm($_SERVER['PHP_SELF'] . "?rowid=" . $object->id, $langs->trans("CreateDolibarrLogin"), $text, "confirm_create_user", $formquestion, 'yes');
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

            print $form->formconfirm($_SERVER['PHP_SELF'] . "?rowid=" . $object->id, $langs->trans("CreateDolibarrThirdParty"), $langs->trans("ConfirmCreateThirdParty"), "confirm_create_thirdparty", $formquestion, 'yes');
        }

        // Confirm validate member
        if ($action == 'valid') {
            $langs->load("mails");

            $adht = new AdherentType($db);
            $adht->fetch($object->typeid);

            $subject = '';
            $msg = '';

            // Send subscription email
            include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
            $formmail = new FormMail($db);
            // Set output language
            $outputlangs = new Translate('', $conf);
            $outputlangs->setDefaultLang(empty($object->thirdparty->default_lang) ? $mysoc->default_lang : $object->thirdparty->default_lang);
            // Load traductions files required by page
            $outputlangs->loadLangs(["main", "members", "companies", "install", "other"]);
            // Get email content from template
            $arraydefaultmessage = null;
            $labeltouse = getDolGlobalString("ADHERENT_EMAIL_TEMPLATE_MEMBER_VALIDATION");

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
            $texttosend = make_substitutions(dol_concatdesc($msg, $adht->getMailOnValid()), $substitutionarray, $outputlangs);

            $tmp = $langs->trans("SendingAnEMailToMember");
            $tmp .= '<br>' . $langs->trans("MailFrom") . ': <b>' . getDolGlobalString('ADHERENT_MAIL_FROM') . '</b>, ';
            $tmp .= '<br>' . $langs->trans("MailRecipient") . ': <b>' . $object->email . '</b>';
            $helpcontent = '';
            $helpcontent .= '<b>' . $langs->trans("MailFrom") . '</b>: ' . getDolGlobalString('ADHERENT_MAIL_FROM') . '<br>' . "\n";
            $helpcontent .= '<b>' . $langs->trans("MailRecipient") . '</b>: ' . $object->email . '<br>' . "\n";
            $helpcontent .= '<b>' . $langs->trans("Subject") . '</b>:<br>' . "\n";
            $helpcontent .= $subjecttosend . "\n";
            $helpcontent .= "<br>";
            $helpcontent .= '<b>' . $langs->trans("Content") . '</b>:<br>';
            $helpcontent .= dol_htmlentitiesbr($texttosend) . "\n";
            // @phan-suppress-next-line PhanPluginSuspiciousParamOrder
            $label = $form->textwithpicto($tmp, $helpcontent, 1, 'help');

            // Create form popup
            $formquestion = [];
            if ($object->email) {
                $formquestion[] = ['type' => 'checkbox', 'name' => 'send_mail', 'label' => $label, 'value' => (getDolGlobalString('ADHERENT_DEFAULT_SENDINFOBYMAIL') ? true : false)];
            }
            if (isModEnabled('mailman') && getDolGlobalString('ADHERENT_USE_MAILMAN')) {
                $formquestion[] = ['type' => 'other', 'label' => $langs->transnoentitiesnoconv("SynchroMailManEnabled"), 'value' => ''];
            }
            if (isModEnabled('mailman') && getDolGlobalString('ADHERENT_USE_SPIP')) {
                $formquestion[] = ['type' => 'other', 'label' => $langs->transnoentitiesnoconv("SynchroSpipEnabled"), 'value' => ''];
            }
            print $form->formconfirm("card.php?rowid=" . $id, $langs->trans("ValidateMember"), $langs->trans("ConfirmValidateMember"), "confirm_valid", $formquestion, 'yes', 1, 220);
        }

        // Confirm resiliate
        if ($action == 'resiliate') {
            $langs->load("mails");

            $adht = new AdherentType($db);
            $adht->fetch($object->typeid);

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
            $labeltouse = getDolGlobalString('ADHERENT_EMAIL_TEMPLATE_CANCELATION');

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
            $texttosend = make_substitutions(dol_concatdesc($msg, $adht->getMailOnResiliate()), $substitutionarray, $outputlangs);

            $tmp = $langs->trans("SendingAnEMailToMember");
            $tmp .= '<br>(' . $langs->trans("MailFrom") . ': <b>' . getDolGlobalString('ADHERENT_MAIL_FROM') . '</b>, ';
            $tmp .= $langs->trans("MailRecipient") . ': <b>' . $object->email . '</b>)';
            $helpcontent = '';
            $helpcontent .= '<b>' . $langs->trans("MailFrom") . '</b>: ' . getDolGlobalString('ADHERENT_MAIL_FROM') . '<br>' . "\n";
            $helpcontent .= '<b>' . $langs->trans("MailRecipient") . '</b>: ' . $object->email . '<br>' . "\n";
            $helpcontent .= '<b>' . $langs->trans("Subject") . '</b>:<br>' . "\n";
            $helpcontent .= $subjecttosend . "\n";
            $helpcontent .= "<br>";
            $helpcontent .= '<b>' . $langs->trans("Content") . '</b>:<br>';
            $helpcontent .= dol_htmlentitiesbr($texttosend) . "\n";
            // @phan-suppress-next-line PhanPluginSuspiciousParamOrder
            $label = $form->textwithpicto($tmp, $helpcontent, 1, 'help');

            // Create an array
            $formquestion = [];
            if ($object->email) {
                $formquestion[] = ['type' => 'checkbox', 'name' => 'send_mail', 'label' => $label, 'value' => (getDolGlobalString('ADHERENT_DEFAULT_SENDINFOBYMAIL') ? 'true' : 'false')];
            }
            if ($backtopage) {
                $formquestion[] = ['type' => 'hidden', 'name' => 'backtopage', 'value' => ($backtopage != '1' ? $backtopage : $_SERVER["HTTP_REFERER"])];
            }
            print $form->formconfirm("card.php?rowid=" . $id, $langs->trans("ResiliateMember"), $langs->trans("ConfirmResiliateMember"), "confirm_resiliate", $formquestion, 'no', 1, 240);
        }

        // Confirm exclude
        if ($action == 'exclude') {
            $langs->load("mails");

            $adht = new AdherentType($db);
            $adht->fetch($object->typeid);

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
            $labeltouse = getDolGlobalString('ADHERENT_EMAIL_TEMPLATE_EXCLUSION');

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
            $texttosend = make_substitutions(dol_concatdesc($msg, $adht->getMailOnExclude()), $substitutionarray, $outputlangs);

            $tmp = $langs->trans("SendingAnEMailToMember");
            $tmp .= '<br>(' . $langs->trans("MailFrom") . ': <b>' . getDolGlobalString('ADHERENT_MAIL_FROM') . '</b>, ';
            $tmp .= $langs->trans("MailRecipient") . ': <b>' . $object->email . '</b>)';
            $helpcontent = '';
            $helpcontent .= '<b>' . $langs->trans("MailFrom") . '</b>: ' . getDolGlobalString('ADHERENT_MAIL_FROM') . '<br>' . "\n";
            $helpcontent .= '<b>' . $langs->trans("MailRecipient") . '</b>: ' . $object->email . '<br>' . "\n";
            $helpcontent .= '<b>' . $langs->trans("Subject") . '</b>:<br>' . "\n";
            $helpcontent .= $subjecttosend . "\n";
            $helpcontent .= "<br>";
            $helpcontent .= '<b>' . $langs->trans("Content") . '</b>:<br>';
            $helpcontent .= dol_htmlentitiesbr($texttosend) . "\n";
            // @phan-suppress-next-line PhanPluginSuspiciousParamOrder
            $label = $form->textwithpicto($tmp, $helpcontent, 1, 'help');

            // Create an array
            $formquestion = [];
            if ($object->email) {
                $formquestion[] = ['type' => 'checkbox', 'name' => 'send_mail', 'label' => $label, 'value' => (getDolGlobalString('ADHERENT_DEFAULT_SENDINFOBYMAIL') ? 'true' : 'false')];
            }
            if ($backtopage) {
                $formquestion[] = ['type' => 'hidden', 'name' => 'backtopage', 'value' => ($backtopage != '1' ? $backtopage : $_SERVER["HTTP_REFERER"])];
            }
            print $form->formconfirm("card.php?rowid=" . $id, $langs->trans("ExcludeMember"), $langs->trans("ConfirmExcludeMember"), "confirm_exclude", $formquestion, 'no', 1, 240);
        }

        // Confirm remove member
        if ($action == 'delete') {
            $formquestion = [];
            if ($backtopage) {
                $formquestion[] = ['type' => 'hidden', 'name' => 'backtopage', 'value' => ($backtopage != '1' ? $backtopage : $_SERVER["HTTP_REFERER"])];
            }
            print $form->formconfirm("card.php?rowid=" . $id, $langs->trans("DeleteMember"), $langs->trans("ConfirmDeleteMember"), "confirm_delete", $formquestion, 'no', 1);
        }

        // Confirm add in spip
        if ($action == 'add_spip') {
            print $form->formconfirm("card.php?rowid=" . $id, $langs->trans('AddIntoSpip'), $langs->trans('AddIntoSpipConfirmation'), 'confirm_add_spip');
        }
        // Confirm removed from spip
        if ($action == 'del_spip') {
            print $form->formconfirm("card.php?rowid=$id", $langs->trans('DeleteIntoSpip'), $langs->trans('DeleteIntoSpipConfirmation'), 'confirm_del_spip');
        }

        $rowspan = 17;
        if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
            $rowspan++;
        }
        if (isModEnabled('societe')) {
            $rowspan++;
        }

        $linkback = '<a href="' . DOL_URL_ROOT . '/adherents/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

        $morehtmlref = '<a href="' . DOL_URL_ROOT . '/adherents/vcard.php?id=' . $object->id . '" class="refid">';
        $morehtmlref .= img_picto($langs->trans("Download") . ' ' . $langs->trans("VCard"), 'vcard.png', 'class="valignmiddle marginleftonly paddingrightonly"');
        $morehtmlref .= '</a>';


        dol_banner_tab($object, 'rowid', $linkback, 1, 'rowid', 'ref', $morehtmlref);

        print '<div class="fichecenter">';
        print '<div class="fichehalfleft">';

        print '<div class="underbanner clearboth"></div>';
        print '<table class="border tableforfield centpercent">';

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
        print '<tr><td>';
        $htmltext = $langs->trans("Public", getDolGlobalString('MAIN_INFO_SOCIETE_NOM'), $linkofpubliclist);
        print $form->textwithpicto($langs->trans("MembershipPublic"), $htmltext, 1, 'help', '', 0, 3, 'membershippublic');
        print '</td><td class="valeur">' . yn($object->public) . '</td></tr>';

        // Other attributes
        include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

        // Third party Dolibarr
        if (isModEnabled('societe')) {
            print '<tr><td>';
            $editenable = $user->hasRight('adherent', 'creer');
            print $form->editfieldkey('LinkedToDolibarrThirdParty', 'thirdparty', '', $object, $editenable);
            print '</td><td colspan="2" class="valeur">';
            if ($action == 'editthirdparty') {
                $htmlname = 'socid';
                print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '" name="form' . $htmlname . '">';
                print '<input type="hidden" name="rowid" value="' . $object->id . '">';
                print '<input type="hidden" name="action" value="set' . $htmlname . '">';
                print '<input type="hidden" name="token" value="' . newToken() . '">';
                print '<table class="nobordernopadding">';
                print '<tr><td>';
                print $form->select_company($object->socid, 'socid', '', 1);
                print '</td>';
                print '<td class="left"><input type="submit" class="button button-edit" value="' . $langs->trans("Modify") . '"></td>';
                print '</tr></table></form>';
            } else {
                if ($object->socid) {
                    $company = new Societe($db);
                    $result = $company->fetch($object->socid);
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
        $editenable = $user->hasRight('adherent', 'creer') && $user->hasRight('user', 'user', 'creer');
        print $form->editfieldkey('LinkedToDolibarrUser', 'login', '', $object, $editenable);
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

        print '<div class="tabsAction">';
        $isinspip = 0;
        $parameters = [];
        $reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
        if (empty($reshook)) {
            if ($action != 'editlogin' && $action != 'editthirdparty') {
                // Send
                if (empty($user->socid)) {
                    if (Adherent::STATUS_VALIDATED == $object->statut) {
                        print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . ((int) $object->id) . '&action=presend&mode=init#formmailbeforetitle">' . $langs->trans('SendMail') . '</a>' . "\n";
                    }
                }

                // Send card by email
                // TODO Remove this to replace with a template
                /*
                if ($user->hasRight('adherent', 'creer')) {
                    if (Adherent::STATUS_VALIDATED == $object->statut) {
                        if ($object->email) print '<a class="butAction" href="card.php?rowid='.$object->id.'&action=sendinfo">'.$langs->trans("SendCardByMail")."</a>\n";
                        else print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NoEMail")).'">'.$langs->trans("SendCardByMail")."</a>\n";
                    } else {
                        print '<span class="butActionRefused classfortooltip" title="'.dol_escape_htmltag($langs->trans("ValidateBefore")).'">'.$langs->trans("SendCardByMail")."</span>";
                    }
                } else {
                    print '<span class="butActionRefused classfortooltip" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("SendCardByMail")."</span>";
                }*/

                // Modify
                if ($user->hasRight('adherent', 'creer')) {
                    print '<a class="butAction" href="card.php?rowid=' . ((int) $object->id) . '&action=edit&token=' . newToken() . '">' . $langs->trans("Modify") . '</a>' . "\n";
                } else {
                    print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans("Modify") . '</span>' . "\n";
                }

                // Validate
                if (Adherent::STATUS_DRAFT == $object->statut) {
                    if ($user->hasRight('adherent', 'creer')) {
                        print '<a class="butAction" href="card.php?rowid=' . ((int) $object->id) . '&action=valid&token=' . newToken() . '">' . $langs->trans("Validate") . '</a>' . "\n";
                    } else {
                        print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans("Validate") . '</span>' . "\n";
                    }
                }

                // Reactivate
                if (Adherent::STATUS_RESILIATED == $object->statut || Adherent::STATUS_EXCLUDED == $object->statut) {
                    if ($user->hasRight('adherent', 'creer')) {
                        print '<a class="butAction" href="card.php?rowid=' . ((int) $object->id) . '&action=valid">' . $langs->trans("Reenable") . "</a>\n";
                    } else {
                        print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans("Reenable") . '</span>' . "\n";
                    }
                }

                // Resiliate
                if (Adherent::STATUS_VALIDATED == $object->statut) {
                    if ($user->hasRight('adherent', 'supprimer')) {
                        print '<a class="butAction" href="card.php?rowid=' . ((int) $object->id) . '&action=resiliate">' . $langs->trans("Resiliate") . "</a></span>\n";
                    } else {
                        print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans("Resiliate") . '</span>' . "\n";
                    }
                }

                // Exclude
                if (Adherent::STATUS_VALIDATED == $object->statut) {
                    if ($user->hasRight('adherent', 'supprimer')) {
                        print '<a class="butAction" href="card.php?rowid=' . ((int) $object->id) . '&action=exclude">' . $langs->trans("Exclude") . "</a></span>\n";
                    } else {
                        print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans("Exclude") . '</span>' . "\n";
                    }
                }

                // Create third party
                if (isModEnabled('societe') && !$object->socid) {
                    if ($user->hasRight('societe', 'creer')) {
                        if (Adherent::STATUS_DRAFT != $object->statut) {
                            print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?rowid=' . ((int) $object->id) . '&amp;action=create_thirdparty" title="' . dol_escape_htmltag($langs->trans("CreateDolibarrThirdPartyDesc")) . '">' . $langs->trans("CreateDolibarrThirdParty") . '</a>' . "\n";
                        } else {
                            print '<a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("ValidateBefore")) . '">' . $langs->trans("CreateDolibarrThirdParty") . '</a>' . "\n";
                        }
                    } else {
                        print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans("CreateDolibarrThirdParty") . '</span>' . "\n";
                    }
                }

                // Create user
                if (!$user->socid && !$object->user_id) {
                    if ($user->hasRight('user', 'user', 'creer')) {
                        if (Adherent::STATUS_DRAFT != $object->statut) {
                            print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?rowid=' . ((int) $object->id) . '&amp;action=create_user" title="' . dol_escape_htmltag($langs->trans("CreateDolibarrLoginDesc")) . '">' . $langs->trans("CreateDolibarrLogin") . '</a>' . "\n";
                        } else {
                            print '<a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("ValidateBefore")) . '">' . $langs->trans("CreateDolibarrLogin") . '</a>' . "\n";
                        }
                    } else {
                        print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans("CreateDolibarrLogin") . '</span>' . "\n";
                    }
                }

                // Action SPIP
                if (isModEnabled('mailmanspip') && getDolGlobalString('ADHERENT_USE_SPIP')) {
                    $isinspip = $mailmanspip->is_in_spip($object);

                    if ($isinspip == 1) {
                        print '<a class="butAction" href="card.php?rowid=' . ((int) $object->id) . '&action=del_spip&token=' . newToken() . '">' . $langs->trans("DeleteIntoSpip") . '</a>' . "\n";
                    }
                    if ($isinspip == 0) {
                        print '<a class="butAction" href="card.php?rowid=' . ((int) $object->id) . '&action=add_spip&token=' . newToken() . '">' . $langs->trans("AddIntoSpip") . '</a>' . "\n";
                    }
                }

                // Delete
                if ($user->hasRight('adherent', 'supprimer')) {
                    print '<a class="butActionDelete" href="card.php?rowid=' . ((int) $object->id) . '&action=delete&token=' . newToken() . '">' . $langs->trans("Delete") . '</a>' . "\n";
                } else {
                    print '<span class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans("Delete") . '</span>' . "\n";
                }
            }
        }
        print '</div>';

        if ($isinspip == -1) {
            print '<br><br><span class="error">' . $langs->trans('SPIPConnectionFailed') . ': ' . $mailmanspip->error . '</span>';
        }


        // Select mail models is same action as presend
        if (GETPOST('modelselected')) {
            $action = 'presend';
        }

        if ($action != 'presend') {
            print '<div class="fichecenter"><div class="fichehalfleft">';
            print '<a name="builddoc"></a>'; // ancre

            // Generated documents
            $filename = dol_sanitizeFileName($object->ref);
            $filedir = $conf->adherent->dir_output . '/' . get_exdir(0, 0, 0, 1, $object, 'member');
            $urlsource = $_SERVER['PHP_SELF'] . '?id=' . $object->id;
            $genallowed = $user->hasRight('adherent', 'lire');
            $delallowed = $user->hasRight('adherent', 'creer');

            print $formfile->showdocuments('member', $filename, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', (empty($object->default_lang) ? '' : $object->default_lang), '', $object);
            $somethingshown = $formfile->numoffiles;

            // Show links to link elements
            //$linktoelem = $form->showLinkToObjectBlock($object, null, array('subscription'));
            //$somethingshown = $form->showLinkedObjectBlock($object, '');

            // Show links to link elements
            /*$linktoelem = $form->showLinkToObjectBlock($object,array('order'));
             if ($linktoelem) {
                print ($somethingshown?'':'<br>').$linktoelem;
            }
             */

            // Show online payment link
            $useonlinepayment = (isModEnabled('paypal') || isModEnabled('stripe') || isModEnabled('paybox'));

            $parameters = [];
            $reshook = $hookmanager->executeHooks('doShowOnlinePaymentUrl', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
            if ($reshook < 0) {
                setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
            } else {
                $useonlinepayment = $reshook;
            }

            if ($useonlinepayment) {
                print '<br>';
                if (empty($amount)) {   // Take the maximum amount among what the member is supposed to pay / has paid in the past
                    $amount = max($adht->amount, $object->first_subscription_amount, $object->last_subscription_amount);
                }
                if (empty($amount)) {
                    $amount = 0;
                }
                require_once DOL_DOCUMENT_ROOT . '/core/lib/payments.lib.php';
                print showOnlinePaymentUrl('membersubscription', $object->ref, $amount);
            }

            print '</div><div class="fichehalfright">';

            $MAX = 10;

            $morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', DOL_URL_ROOT . '/adherents/agenda.php?id=' . $object->id);

            // List of actions on element
            $formactions = new FormActions($db);
            $somethingshown = $formactions->showactions($object, $object->element, $socid, 1, 'listactions', $MAX, '', $morehtmlcenter);

            print '</div></div>';
        }

        // Presend form
        $modelmail = 'member';
        $defaulttopic = 'CardContent';
        $diroutput = $conf->adherent->dir_output;
        $trackid = 'mem' . $object->id;

        include DOL_DOCUMENT_ROOT . '/core/tpl/card_presend.tpl.php';
    }
}

// End of page
llxFooter();
