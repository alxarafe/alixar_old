<?php

/* Copyright (C) 2004       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2005       Simon TOSSER            <simon@kornog-computing.com>
 * Copyright (C) 2013-2016  Olivier Geffroy         <jeff@jeffinfo.com>
 * Copyright (C) 2013-2024	Alexandre Spangaro		<aspangaro@easya.solutions>
 * Copyright (C) 2014-2015	Ari Elbaz (elarifr)		<github@accedinfo.com>
 * Copyright (C) 2013-2021	Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2021      	Gauthier VERDOL         <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2014       Juanjo Menent           <jmenent@2byte.es>s
 * Copyright (C) 2016       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Rafael San José         <rsanjose@alxarafe.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace DoliModules\Accounting\Controller;

global $conf;
global $db;
global $user;
global $hookmanager;
global $user;
global $menumanager;
global $langs;
global $mysoc;

// Load Dolibarr environment
require BASE_PATH . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

use AccountingAccount;
use DoliCore\Base\DolibarrController;
use FactureFournisseur;
use Form;
use FormAccounting;
use FormOther;
use Product;
use Societe;
use SupplierInvoiceLine;

class AccountingSupplierController extends DolibarrController
{

    /**
     * \file    htdocs/accountancy/supplier/card.php
     * \ingroup Accountancy (Double entries)
     * \brief   Card supplier ventilation
     */
    public function card()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(array("bills", "accountancy"));

        $action = GETPOST('action', 'aZ09');
        $cancel = GETPOST('cancel', 'alpha');
        $backtopage = GETPOST('backtopage', 'alpha');

        $codeventil = GETPOSTINT('codeventil');
        $id = GETPOSTINT('id');

// Security check
        if (!isModEnabled('accounting')) {
            accessforbidden();
        }
        if ($user->socid > 0) {
            accessforbidden();
        }
        if (!$user->hasRight('accounting', 'mouvements', 'lire')) {
            accessforbidden();
        }


        /*
         * Actions
         */

        if ($action == 'ventil' && $user->hasRight('accounting', 'bind', 'write')) {
            if (!$cancel) {
                if ($codeventil < 0) {
                    $codeventil = 0;
                }

                $sql = " UPDATE " . MAIN_DB_PREFIX . "facture_fourn_det";
                $sql .= " SET fk_code_ventilation = " . ((int) $codeventil);
                $sql .= " WHERE rowid = " . ((int) $id);

                $resql = $db->query($sql);
                if (!$resql) {
                    setEventMessages($db->lasterror(), null, 'errors');
                } else {
                    setEventMessages($langs->trans("RecordModifiedSuccessfully"), null, 'mesgs');
                    if ($backtopage) {
                        header("Location: " . $backtopage);
                        exit();
                    }
                }
            } else {
                header("Location: ./lines.php");
                exit();
            }
        }



        /*
         * View
         */
        $help_url = 'EN:Module_Double_Entry_Accounting|FR:Module_Comptabilit&eacute;_en_Partie_Double#Liaisons_comptables';

        llxHeader("", $langs->trans('FicheVentilation'), $help_url);

        if ($cancel == $langs->trans("Cancel")) {
            $action = '';
        }

// Create
        $form = new Form($db);
        $facturefournisseur_static = new FactureFournisseur($db);
        $formaccounting = new FormAccounting($db);

        if (!empty($id)) {
            $sql = "SELECT f.ref as ref, f.rowid as facid, l.fk_product, l.description, l.rowid, l.fk_code_ventilation, ";
            $sql .= " p.rowid as product_id, p.ref as product_ref, p.label as product_label,";
            if (getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED')) {
                $sql .= " ppe.accountancy_code_buy as code_buy,";
            } else {
                $sql .= " p.accountancy_code_buy as code_buy,";
            }
            $sql .= " aa.account_number, aa.label";
            $sql .= " FROM " . MAIN_DB_PREFIX . "facture_fourn_det as l";
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product as p ON p.rowid = l.fk_product";
            if (getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED')) {
                $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product_perentity as ppe ON ppe.fk_product = p.rowid AND ppe.entity = " . ((int) $conf->entity);
            }
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_account as aa ON l.fk_code_ventilation = aa.rowid";
            $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "facture_fourn as f ON f.rowid = l.fk_facture_fourn ";
            $sql .= " WHERE f.fk_statut > 0 AND l.rowid = " . ((int) $id);
            $sql .= " AND f.entity IN (" . getEntity('facture_fourn', 0) . ")"; // We don't share object for accountancy

            dol_syslog("/accounting/supplier/card.php", LOG_DEBUG);
            $result = $db->query($sql);

            if ($result) {
                $num_lines = $db->num_rows($result);
                $i = 0;

                if ($num_lines) {
                    $objp = $db->fetch_object($result);

                    print '<form action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '" method="post">' . "\n";
                    print '<input type="hidden" name="token" value="' . newToken() . '">';
                    print '<input type="hidden" name="action" value="ventil">';
                    print '<input type="hidden" name="backtopage" value="' . dol_escape_htmltag($backtopage) . '">';

                    print load_fiche_titre($langs->trans('SuppliersVentilation'), '', 'title_accountancy');

                    print dol_get_fiche_head();

                    print '<table class="border centpercent">';

                    // ref invoice
                    print '<tr><td>' . $langs->trans("BillsSuppliers") . '</td>';
                    $facturefournisseur_static->ref = $objp->ref;
                    $facturefournisseur_static->id = $objp->facid;
                    print '<td>' . $facturefournisseur_static->getNomUrl(1) . '</td>';
                    print '</tr>';

                    print '<tr><td width="20%">' . $langs->trans("Line") . '</td>';
                    print '<td>' . stripslashes(nl2br($objp->description)) . '</td></tr>';
                    print '<tr><td width="20%">' . $langs->trans("ProductLabel") . '</td>';
                    print '<td>' . dol_trunc($objp->product_label, 24) . '</td>';
                    print '<tr><td width="20%">' . $langs->trans("Account") . '</td><td>';
                    print $formaccounting->select_account($objp->fk_code_ventilation, 'codeventil', 1);
                    print '</td></tr>';
                    print '</table>';

                    print dol_get_fiche_end();

                    print '<div class="center">';
                    print '<input class="button button-save" type="submit" value="' . $langs->trans("Save") . '">';
                    print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                    print '<input class="button button-cancel" type="submit" name="cancel" value="' . $langs->trans("Cancel") . '">';
                    print '</div>';

                    print '</form>';
                } else {
                    print "Error";
                }
            } else {
                print "Error";
            }
        } else {
            print "Error ID incorrect";
        }

// End of page
        llxFooter();
        $db->close();
    }

    /**
     * \file        htdocs/accountancy/supplier/index.php
     * \ingroup     Accountancy (Double entries)
     * \brief       Home supplier journalization page
     */
    public function index()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(array("compta", "bills", "other", "accountancy"));

        $validatemonth = GETPOSTINT('validatemonth');
        $validateyear = GETPOSTINT('validateyear');

// Security check
        if (!isModEnabled('accounting')) {
            accessforbidden();
        }
        if ($user->socid > 0) {
            accessforbidden();
        }
        if (!$user->hasRight('accounting', 'bind', 'write')) {
            accessforbidden();
        }

        $accountingAccount = new AccountingAccount($db);

        $month_start = getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1);
        if (GETPOSTINT("year")) {
            $year_start = GETPOSTINT("year");
        } else {
            $year_start = dol_print_date(dol_now(), '%Y');
            if (dol_print_date(dol_now(), '%m') < $month_start) {
                $year_start--; // If current month is lower that starting fiscal month, we start last year
            }
        }
        $year_end = $year_start + 1;
        $month_end = $month_start - 1;
        if ($month_end < 1) {
            $month_end = 12;
            $year_end--;
        }
        $search_date_start = dol_mktime(0, 0, 0, $month_start, 1, $year_start);
        $search_date_end = dol_get_last_day($year_end, $month_end);
        $year_current = $year_start;

// Validate History
        $action = GETPOST('action', 'aZ09');

        $chartaccountcode = dol_getIdFromCode($db, getDolGlobalInt('CHARTOFACCOUNTS'), 'accounting_system', 'rowid', 'pcg_version');

// Security check
        if (!isModEnabled('accounting')) {
            accessforbidden();
        }
        if ($user->socid > 0) {
            accessforbidden();
        }
        if (!$user->hasRight('accounting', 'mouvements', 'lire')) {
            accessforbidden();
        }


        /*
         * Actions
         */

        if (($action == 'clean' || $action == 'validatehistory') && $user->hasRight('accounting', 'bind', 'write')) {
            // Clean database
            $db->begin();
            $sql1 = "UPDATE " . $db->prefix() . "facture_fourn_det as fd";
            $sql1 .= " SET fk_code_ventilation = 0";
            $sql1 .= ' WHERE fd.fk_code_ventilation NOT IN';
            $sql1 .= '	(SELECT accnt.rowid ';
            $sql1 .= "	FROM " . $db->prefix() . "accounting_account as accnt";
            $sql1 .= "	INNER JOIN " . $db->prefix() . "accounting_system as syst";
            $sql1 .= "	ON accnt.fk_pcg_version = syst.pcg_version AND syst.rowid = " . getDolGlobalInt('CHARTOFACCOUNTS') . " AND accnt.entity = " . ((int) $conf->entity) . ")";
            $sql1 .= " AND fd.fk_facture_fourn IN (SELECT rowid FROM " . $db->prefix() . "facture_fourn WHERE entity = " . ((int) $conf->entity) . ")";
            $sql1 .= " AND fk_code_ventilation <> 0";

            dol_syslog("htdocs/accountancy/customer/index.php fixaccountancycode", LOG_DEBUG);
            $resql1 = $db->query($sql1);
            if (!$resql1) {
                $error++;
                $db->rollback();
                setEventMessages($db->lasterror(), null, 'errors');
            } else {
                $db->commit();
            }
            // End clean database
        }

        if ($action == 'validatehistory') {
            $error = 0;
            $nbbinddone = 0;
            $nbbindfailed = 0;
            $notpossible = 0;

            $db->begin();

            // Now make the binding. Bind automatically only for product with a dedicated account that exists into chart of account, others need a manual bind
            // Supplier Invoice Lines (must be same request than into page list.php for manual binding)
            $sql = "SELECT f.rowid as facid, f.ref, f.ref_supplier, f.libelle as invoice_label, f.datef, f.type as ftype, f.fk_facture_source,";
            $sql .= " l.rowid, l.fk_product, l.description, l.total_ht, l.fk_code_ventilation, l.product_type as type_l, l.tva_tx as tva_tx_line, l.vat_src_code,";
            $sql .= " p.rowid as product_id, p.ref as product_ref, p.label as product_label, p.fk_product_type as type, p.tva_tx as tva_tx_prod,";
            if (getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED')) {
                $sql .= " ppe.accountancy_code_buy as code_buy, ppe.accountancy_code_buy_intra as code_buy_intra, ppe.accountancy_code_buy_export as code_buy_export,";
            } else {
                $sql .= " p.accountancy_code_buy as code_buy, p.accountancy_code_buy_intra as code_buy_intra, p.accountancy_code_buy_export as code_buy_export,";
            }
            $sql .= " aa.rowid as aarowid, aa2.rowid as aarowid_intra, aa3.rowid as aarowid_export, aa4.rowid as aarowid_thirdparty,";
            $sql .= " co.code as country_code, co.label as country_label,";
            $sql .= " s.tva_intra,";
            if (getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
                $sql .= " spe.accountancy_code_buy as company_code_buy";
            } else {
                $sql .= " s.accountancy_code_buy as company_code_buy";
            }
            $sql .= " FROM " . $db->prefix() . "facture_fourn as f";
            $sql .= " INNER JOIN " . $db->prefix() . "societe as s ON s.rowid = f.fk_soc";
            if (getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
                $sql .= " LEFT JOIN " . $db->prefix() . "societe_perentity as spe ON spe.fk_soc = s.rowid AND spe.entity = " . ((int) $conf->entity);
            }
            $sql .= " LEFT JOIN " . $db->prefix() . "c_country as co ON co.rowid = s.fk_pays ";
            $sql .= " INNER JOIN " . $db->prefix() . "facture_fourn_det as l ON f.rowid = l.fk_facture_fourn";
            $sql .= " LEFT JOIN " . $db->prefix() . "product as p ON p.rowid = l.fk_product";
            if (getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED')) {
                $sql .= " LEFT JOIN " . $db->prefix() . "product_perentity as ppe ON ppe.fk_product = p.rowid AND ppe.entity = " . ((int) $conf->entity);
            }
            $alias_societe_perentity = !getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED') ? "s" : "spe";
            $alias_product_perentity = !getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED') ? "p" : "ppe";
            $sql .= " LEFT JOIN " . $db->prefix() . "accounting_account as aa  ON " . $alias_product_perentity . ".accountancy_code_buy = aa.account_number         AND aa.active = 1  AND aa.fk_pcg_version = '" . $db->escape($chartaccountcode) . "' AND aa.entity = " . $conf->entity;
            $sql .= " LEFT JOIN " . $db->prefix() . "accounting_account as aa2 ON " . $alias_product_perentity . ".accountancy_code_buy_intra = aa2.account_number  AND aa2.active = 1 AND aa2.fk_pcg_version = '" . $db->escape($chartaccountcode) . "' AND aa2.entity = " . $conf->entity;
            $sql .= " LEFT JOIN " . $db->prefix() . "accounting_account as aa3 ON " . $alias_product_perentity . ".accountancy_code_buy_export = aa3.account_number AND aa3.active = 1 AND aa3.fk_pcg_version = '" . $db->escape($chartaccountcode) . "' AND aa3.entity = " . $conf->entity;
            $sql .= " LEFT JOIN " . $db->prefix() . "accounting_account as aa4 ON " . $alias_product_perentity . ".accountancy_code_buy = aa4.account_number        AND aa4.active = 1 AND aa4.fk_pcg_version = '" . $db->escape($chartaccountcode) . "' AND aa4.entity = " . $conf->entity;
            $sql .= " WHERE f.fk_statut > 0 AND l.fk_code_ventilation <= 0";
            $sql .= " AND l.product_type <= 2";
            $sql .= " AND f.entity IN (" . getEntity('facture_fourn', 0) . ")"; // We don't share object for accountancy
            if (getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) {
                $sql .= " AND f.datef >= '" . $db->idate(getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) . "'";
            }
            if ($validatemonth && $validateyear) {
                $sql .= dolSqlDateFilter('f.datef', 0, $validatemonth, $validateyear);
            }

            dol_syslog('htdocs/accountancy/supplier/index.php');

            $result = $db->query($sql);
            if (!$result) {
                $error++;
                setEventMessages($db->lasterror(), null, 'errors');
            } else {
                $num_lines = $db->num_rows($result);

                $isBuyerInEEC = isInEEC($mysoc);

                $thirdpartystatic = new Societe($db);
                $facture_static = new FactureFournisseur($db);
                $facture_static_det = new SupplierInvoiceLine($db);
                $product_static = new Product($db);

                $i = 0;
                while ($i < min($num_lines, 10000)) {   // No more than 10000 at once
                    $objp = $db->fetch_object($result);

                    $thirdpartystatic->id = $objp->socid;
                    $thirdpartystatic->name = $objp->name;
                    $thirdpartystatic->client = $objp->client;
                    $thirdpartystatic->fournisseur = $objp->fournisseur;
                    $thirdpartystatic->code_client = $objp->code_client;
                    $thirdpartystatic->code_compta_client = $objp->code_compta_client;
                    $thirdpartystatic->code_fournisseur = $objp->code_fournisseur;
                    $thirdpartystatic->code_compta_fournisseur = $objp->code_compta_fournisseur;
                    $thirdpartystatic->email = $objp->email;
                    $thirdpartystatic->country_code = $objp->country_code;
                    $thirdpartystatic->tva_intra = $objp->tva_intra;
                    $thirdpartystatic->code_compta_product = $objp->company_code_buy;       // The accounting account for product stored on thirdparty object (for level3 suggestion)

                    $product_static->ref = $objp->product_ref;
                    $product_static->id = $objp->product_id;
                    $product_static->type = $objp->type;
                    $product_static->label = $objp->product_label;
                    $product_static->status = $objp->status;
                    $product_static->status_buy = $objp->status_buy;
                    $product_static->accountancy_code_sell = $objp->code_sell;
                    $product_static->accountancy_code_sell_intra = $objp->code_sell_intra;
                    $product_static->accountancy_code_sell_export = $objp->code_sell_export;
                    $product_static->accountancy_code_buy = $objp->code_buy;
                    $product_static->accountancy_code_buy_intra = $objp->code_buy_intra;
                    $product_static->accountancy_code_buy_export = $objp->code_buy_export;
                    $product_static->tva_tx = $objp->tva_tx_prod;

                    $facture_static->ref = $objp->ref;
                    $facture_static->id = $objp->facid;
                    $facture_static->type = $objp->ftype;
                    $facture_static->ref_supplier = $objp->ref_supplier;
                    $facture_static->label = $objp->invoice_label;
                    $facture_static->date = $db->jdate($objp->datef);
                    $facture_static->fk_facture_source = $objp->fk_facture_source;

                    $facture_static_det->id = $objp->rowid;
                    $facture_static_det->total_ht = $objp->total_ht;
                    $facture_static_det->tva_tx = $objp->tva_tx_line;
                    $facture_static_det->vat_src_code = $objp->vat_src_code;
                    $facture_static_det->product_type = $objp->type_l;
                    $facture_static_det->desc = $objp->description;

                    $accountingAccountArray = array(
                        'dom' => $objp->aarowid,
                        'intra' => $objp->aarowid_intra,
                        'export' => $objp->aarowid_export,
                        'thirdparty' => $objp->aarowid_thirdparty);

                    $code_buy_p_notset = '';
                    $code_buy_t_notset = '';

                    $suggestedid = 0;

                    $return = $accountingAccount->getAccountingCodeToBind($mysoc, $thirdpartystatic, $product_static, $facture_static, $facture_static_det, $accountingAccountArray, 'supplier');
                    if (!is_array($return) && $return < 0) {
                        setEventMessage($accountingAccount->error, 'errors');
                    } else {
                        $suggestedid = $return['suggestedid'];
                        $suggestedaccountingaccountfor = $return['suggestedaccountingaccountfor'];

                        if (!empty($suggestedid) && $suggestedaccountingaccountfor != '' && $suggestedaccountingaccountfor != 'eecwithoutvatnumber') {
                            $suggestedid = $return['suggestedid'];
                        } else {
                            $suggestedid = 0;
                        }
                    }

                    if ($suggestedid > 0) {
                        $sqlupdate = "UPDATE " . $db->prefix() . "facture_fourn_det";
                        $sqlupdate .= " SET fk_code_ventilation = " . ((int) $suggestedid);
                        $sqlupdate .= " WHERE fk_code_ventilation <= 0 AND product_type <= 2 AND rowid = " . ((int) $facture_static_det->id);

                        $resqlupdate = $db->query($sqlupdate);
                        if (!$resqlupdate) {
                            $error++;
                            setEventMessages($db->lasterror(), null, 'errors');
                            $nbbindfailed++;
                            break;
                        } else {
                            $nbbinddone++;
                        }
                    } else {
                        $notpossible++;
                        $nbbindfailed++;
                    }

                    $i++;
                }
                if ($num_lines > 10000) {
                    $notpossible += ($num_lines - 10000);
                }
            }

            if ($error) {
                $db->rollback();
            } else {
                $db->commit();
                setEventMessages($langs->trans('AutomaticBindingDone', $nbbinddone, $notpossible), null, ($notpossible ? 'warnings' : 'mesgs'));
                if ($nbbindfailed) {
                    setEventMessages($langs->trans('DoManualBindingForFailedRecord', $nbbindfailed), null, 'warnings');
                }
            }
        }


        /*
         * View
         */
        $help_url = 'EN:Module_Double_Entry_Accounting|FR:Module_Comptabilit&eacute;_en_Partie_Double#Liaisons_comptables';

        llxHeader('', $langs->trans("SuppliersVentilation"), $help_url);

        $textprevyear = '<a href="' . $_SERVER['PHP_SELF'] . '?year=' . ($year_current - 1) . '">' . img_previous() . '</a>';
        $textnextyear = '&nbsp;<a href="' . $_SERVER['PHP_SELF'] . '?year=' . ($year_current + 1) . '">' . img_next() . '</a>';

        print load_fiche_titre($langs->trans("SuppliersVentilation") . " " . $textprevyear . "&nbsp;" . $langs->trans("Year") . "&nbsp;" . $year_start . "&nbsp;" . $textnextyear, '', 'title_accountancy');

        print '<span class="opacitymedium">' . $langs->trans("DescVentilSupplier") . '</span><br>';
        print '<span class="opacitymedium hideonsmartphone">' . $langs->trans("DescVentilMore", $langs->transnoentitiesnoconv("ValidateHistory"), $langs->transnoentitiesnoconv("ToBind")) . '<br>';
        print '</span><br>';

        $y = $year_current;

        $buttonbind = '<a class="button small" href="' . $_SERVER['PHP_SELF'] . '?action=validatehistory&token=' . newToken() . '">' . img_picto('', 'link', 'class="paddingright fa-color-unset smallpaddingimp"') . $langs->trans("ValidateHistory") . '</a>';


        print_barre_liste(img_picto('', 'unlink', 'class="paddingright fa-color-unset"') . $langs->trans("OverviewOfAmountOfLinesNotBound"), '', '', '', '', '', '', -1, '', '', 0, '', '', 0, 1, 1, 0, $buttonbind);
//print load_fiche_titre($langs->trans("OverviewOfAmountOfLinesNotBound"), $buttonbind, '');

        print '<div class="div-table-responsive-no-min">';
        print '<table class="noborder centpercent">';
        print '<tr class="liste_titre"><td class="minwidth100">' . $langs->trans("Account") . '</td>';
        print '<td>' . $langs->trans("Label") . '</td>';
        for ($i = 1; $i <= 12; $i++) {
            $j = $i + getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) - 1;
            if ($j > 12) {
                $j -= 12;
            }
            $cursormonth = $j;
            if ($cursormonth > 12) {
                $cursormonth -= 12;
            }
            $cursoryear = ($cursormonth < getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1)) ? $y + 1 : $y;
            $tmp = dol_getdate(dol_get_last_day($cursoryear, $cursormonth, 'gmt'), false, 'gmt');

            print '<td width="60" class="right">';
            if (!empty($tmp['mday'])) {
                $param = 'search_date_startday=1&search_date_startmonth=' . $cursormonth . '&search_date_startyear=' . $cursoryear;
                $param .= '&search_date_endday=' . $tmp['mday'] . '&search_date_endmonth=' . $tmp['mon'] . '&search_date_endyear=' . $tmp['year'];
                print '<a href="' . DOL_URL_ROOT . '/accountancy/supplier/list.php?' . $param . '">';
            }
            print $langs->trans('MonthShort' . str_pad((int) $j, 2, '0', STR_PAD_LEFT));
            if (!empty($tmp['mday'])) {
                print '</a>';
            }
            print '</td>';
        }
        print '<td width="60" class="right"><b>' . $langs->trans("Total") . '</b></td></tr>';

        $sql = "SELECT " . $db->ifsql('aa.account_number IS NULL', "'tobind'", 'aa.account_number') . " AS codecomptable,";
        $sql .= "  " . $db->ifsql('aa.label IS NULL', "'tobind'", 'aa.label') . " AS intitule,";
        for ($i = 1; $i <= 12; $i++) {
            $j = $i + getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) - 1;
            if ($j > 12) {
                $j -= 12;
            }
            $sql .= "  SUM(" . $db->ifsql("MONTH(ff.datef) = " . ((int) $j), "ffd.total_ht", "0") . ") AS month" . str_pad((int) $j, 2, "0", STR_PAD_LEFT) . ",";
        }
        $sql .= "  SUM(ffd.total_ht) as total";
        $sql .= " FROM " . $db->prefix() . "facture_fourn_det as ffd";
        $sql .= "  LEFT JOIN " . $db->prefix() . "facture_fourn as ff ON ff.rowid = ffd.fk_facture_fourn";
        $sql .= "  LEFT JOIN " . $db->prefix() . "accounting_account as aa ON aa.rowid = ffd.fk_code_ventilation";
        $sql .= " WHERE ff.datef >= '" . $db->idate($search_date_start) . "'";
        $sql .= "  AND ff.datef <= '" . $db->idate($search_date_end) . "'";
// Define begin binding date
        if (getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) {
            $sql .= " AND ff.datef >= '" . $db->idate(getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) . "'";
        }
        $sql .= "  AND ff.fk_statut > 0";
        $sql .= "  AND ffd.product_type <= 2";
        $sql .= " AND ff.entity IN (" . getEntity('facture_fourn', 0) . ")"; // We don't share object for accountancy
        $sql .= " AND aa.account_number IS NULL";
        if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {
            $sql .= " AND ff.type IN (" . FactureFournisseur::TYPE_STANDARD . "," . FactureFournisseur::TYPE_REPLACEMENT . "," . FactureFournisseur::TYPE_CREDIT_NOTE . ")";
        } else {
            $sql .= " AND ff.type IN (" . FactureFournisseur::TYPE_STANDARD . "," . FactureFournisseur::TYPE_REPLACEMENT . "," . FactureFournisseur::TYPE_CREDIT_NOTE . "," . FactureFournisseur::TYPE_DEPOSIT . ")";
        }
        $sql .= " GROUP BY ffd.fk_code_ventilation,aa.account_number,aa.label";

        dol_syslog('htdocs/accountancy/supplier/index.php', LOG_DEBUG);
        $resql = $db->query($sql);
        if ($resql) {
            $num = $db->num_rows($resql);

            while ($row = $db->fetch_row($resql)) {
                print '<tr class="oddeven">';
                print '<td>';
                if ($row[0] == 'tobind') {
                    print '<span class="opacitymedium">' . $langs->trans("Unknown") . '</span>';
                } else {
                    print length_accountg($row[0]);
                }
                print '</td>';
                print '<td>';
                if ($row[0] == 'tobind') {
                    $startmonth = getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1);
                    if ($startmonth > 12) {
                        $startmonth -= 12;
                    }
                    $startyear = ($startmonth < getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1)) ? $y + 1 : $y;
                    $endmonth = getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) + 11;
                    if ($endmonth > 12) {
                        $endmonth -= 12;
                    }
                    $endyear = ($endmonth < getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1)) ? $y + 1 : $y;
                    print $langs->trans("UseMenuToSetBindindManualy", DOL_URL_ROOT . '/accountancy/supplier/list.php?search_date_startday=1&search_date_startmonth=' . ((int) $startmonth) . '&search_date_startyear=' . ((int) $startyear) . '&search_date_endday=&search_date_endmonth=' . ((int) $endmonth) . '&search_date_endyear=' . ((int) $endyear), $langs->transnoentitiesnoconv("ToBind"));
                } else {
                    print $row[1];
                }
                print '</td>';
                for ($i = 2; $i <= 13; $i++) {
                    $cursormonth = (getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) + $i - 2);
                    if ($cursormonth > 12) {
                        $cursormonth -= 12;
                    }
                    $cursoryear = ($cursormonth < getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1)) ? $y + 1 : $y;
                    $tmp = dol_getdate(dol_get_last_day($cursoryear, $cursormonth, 'gmt'), false, 'gmt');

                    print '<td class="right nowraponall amount">';
                    print price($row[$i]);
                    // Add link to make binding
                    if (!empty(price2num($row[$i]))) {
                        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=validatehistory&year=' . $y . '&validatemonth=' . ((int) $cursormonth) . '&validateyear=' . ((int) $cursoryear) . '&token=' . newToken() . '">';
                        print img_picto($langs->trans("ValidateHistory") . ' (' . $langs->trans('Month' . str_pad($cursormonth, 2, '0', STR_PAD_LEFT)) . ' ' . $cursoryear . ')', 'link', 'class="marginleft2"');
                        print '</a>';
                    }
                    print '</td>';
                }
                print '<td class="right nowraponall amount"><b>' . price($row[14]) . '</b></td>';
                print '</tr>';
            }
            $db->free($resql);

            if ($num == 0) {
                print '<tr class="oddeven"><td colspan="16">';
                print '<span class="opacitymedium">' . $langs->trans("NoRecordFound") . '</span>';
                print '</td></tr>';
            }
        } else {
            print $db->lasterror(); // Show last sql error
        }
        print "</table>\n";
        print '</div>';


        print '<br>';


        print_barre_liste(img_picto('', 'link', 'class="paddingright fa-color-unset"') . $langs->trans("OverviewOfAmountOfLinesBound"), '', '', '', '', '', '', -1, '', '', 0, '', '', 0, 1, 1);
//print load_fiche_titre($langs->trans("OverviewOfAmountOfLinesBound"), '', '');

        print '<div class="div-table-responsive-no-min">';
        print '<table class="noborder centpercent">';
        print '<tr class="liste_titre"><td class="minwidth100">' . $langs->trans("Account") . '</td>';
        print '<td>' . $langs->trans("Label") . '</td>';
        for ($i = 1; $i <= 12; $i++) {
            $j = $i + getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) - 1;
            if ($j > 12) {
                $j -= 12;
            }
            $cursormonth = $j;
            if ($cursormonth > 12) {
                $cursormonth -= 12;
            }
            $cursoryear = ($cursormonth < getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1)) ? $y + 1 : $y;
            $tmp = dol_getdate(dol_get_last_day($cursoryear, $cursormonth, 'gmt'), false, 'gmt');

            print '<td width="60" class="right">';
            if (!empty($tmp['mday'])) {
                $param = 'search_date_startday=1&search_date_startmonth=' . $cursormonth . '&search_date_startyear=' . $cursoryear;
                $param .= '&search_date_endday=' . $tmp['mday'] . '&search_date_endmonth=' . $tmp['mon'] . '&search_date_endyear=' . $tmp['year'];
                print '<a href="' . DOL_URL_ROOT . '/accountancy/supplier/lines.php?' . $param . '">';
            }
            print $langs->trans('MonthShort' . str_pad($j, 2, '0', STR_PAD_LEFT));
            if (!empty($tmp['mday'])) {
                print '</a>';
            }
            print '</td>';
        }
        print '<td width="60" class="right"><b>' . $langs->trans("Total") . '</b></td></tr>';

        $sql = "SELECT " . $db->ifsql('aa.account_number IS NULL', "'tobind'", 'aa.account_number') . " AS codecomptable,";
        $sql .= "  " . $db->ifsql('aa.label IS NULL', "'tobind'", 'aa.label') . " AS intitule,";
        for ($i = 1; $i <= 12; $i++) {
            $j = $i + getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) - 1;
            if ($j > 12) {
                $j -= 12;
            }
            $sql .= "  SUM(" . $db->ifsql("MONTH(ff.datef) = " . ((int) $j), "ffd.total_ht", "0") . ") AS month" . str_pad((int) $j, 2, "0", STR_PAD_LEFT) . ",";
        }
        $sql .= "  SUM(ffd.total_ht) as total";
        $sql .= " FROM " . $db->prefix() . "facture_fourn_det as ffd";
        $sql .= "  LEFT JOIN " . $db->prefix() . "facture_fourn as ff ON ff.rowid = ffd.fk_facture_fourn";
        $sql .= "  LEFT JOIN " . $db->prefix() . "accounting_account as aa ON aa.rowid = ffd.fk_code_ventilation";
        $sql .= " WHERE ff.datef >= '" . $db->idate($search_date_start) . "'";
        $sql .= "  AND ff.datef <= '" . $db->idate($search_date_end) . "'";
// Define begin binding date
        if (getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) {
            $sql .= " AND ff.datef >= '" . $db->idate(getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) . "'";
        }
        $sql .= " AND ff.entity IN (" . getEntity('facture_fourn', 0) . ")"; // We don't share object for accountancy
        $sql .= "  AND ff.fk_statut > 0";
        $sql .= "  AND ffd.product_type <= 2";
        if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {
            $sql .= " AND ff.type IN (" . FactureFournisseur::TYPE_STANDARD . ", " . FactureFournisseur::TYPE_REPLACEMENT . ", " . FactureFournisseur::TYPE_CREDIT_NOTE . ")";
        } else {
            $sql .= " AND ff.type IN (" . FactureFournisseur::TYPE_STANDARD . ", " . FactureFournisseur::TYPE_REPLACEMENT . ", " . FactureFournisseur::TYPE_CREDIT_NOTE . ", " . FactureFournisseur::TYPE_DEPOSIT . ")";
        }
        $sql .= " AND aa.account_number IS NOT NULL";
        $sql .= " GROUP BY ffd.fk_code_ventilation,aa.account_number,aa.label";
        $sql .= ' ORDER BY aa.account_number';

        dol_syslog('htdocs/accountancy/supplier/index.php');
        $resql = $db->query($sql);
        if ($resql) {
            $num = $db->num_rows($resql);

            while ($row = $db->fetch_row($resql)) {
                print '<tr class="oddeven">';
                print '<td>';
                if ($row[0] == 'tobind') {
                    print $langs->trans("Unknown");
                } else {
                    print length_accountg($row[0]);
                }
                print '</td>';

                print '<td class="tdoverflowmax300"' . (empty($row[1]) ? '' : ' title="' . dol_escape_htmltag($row[1]) . '"') . '>';
                if ($row[0] == 'tobind') {
                    print $langs->trans("UseMenuToSetBindindManualy", DOL_URL_ROOT . '/accountancy/supplier/list.php?search_year=' . ((int) $y), $langs->transnoentitiesnoconv("ToBind"));
                } else {
                    print dol_escape_htmltag($row[1]);
                }
                print '</td>';

                for ($i = 2; $i <= 13; $i++) {
                    print '<td class="right nowraponall amount">';
                    print price($row[$i]);
                    print '</td>';
                }
                print '<td class="right nowraponall amount"><b>' . price($row[14]) . '</b></td>';
                print '</tr>';
            }
            $db->free($resql);

            if ($num == 0) {
                print '<tr class="oddeven"><td colspan="16">';
                print '<span class="opacitymedium">' . $langs->trans("NoRecordFound") . '</span>';
                print '</td></tr>';
            }
        } else {
            print $db->lasterror(); // Show last sql error
        }
        print "</table>\n";
        print '</div>';


        if (getDolGlobalString('SHOW_TOTAL_OF_PREVIOUS_LISTS_IN_LIN_PAGE')) { // This part of code looks strange. Why showing a report that should rely on result of this step ?
            print '<br>';
            print '<br>';

            print_barre_liste($langs->trans("OtherInfo"), '', '', '', '', '', '', -1, '', '', 0, '', '', 0, 1, 1);
            //print load_fiche_titre($langs->trans("OtherInfo"), '', '');

            print '<div class="div-table-responsive-no-min">';
            print '<table class="noborder centpercent">';
            print '<tr class="liste_titre"><td>' . $langs->trans("Total") . '</td>';
            for ($i = 1; $i <= 12; $i++) {
                $j = $i + getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) - 1;
                if ($j > 12) {
                    $j -= 12;
                }
                print '<td width="60" class="right">' . $langs->trans('MonthShort' . str_pad((int) $j, 2, '0', STR_PAD_LEFT)) . '</td>';
            }
            print '<td width="60" class="right"><b>' . $langs->trans("Total") . '</b></td></tr>';

            $sql = "SELECT '" . $db->escape($langs->trans("CAHTF")) . "' AS label,";
            for ($i = 1; $i <= 12; $i++) {
                $j = $i + getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) - 1;
                if ($j > 12) {
                    $j -= 12;
                }
                $sql .= "  SUM(" . $db->ifsql("MONTH(ff.datef) = " . ((int) $j), "ffd.total_ht", "0") . ") AS month" . str_pad((int) $j, 2, "0", STR_PAD_LEFT) . ",";
            }
            $sql .= "  SUM(ffd.total_ht) as total";
            $sql .= " FROM " . $db->prefix() . "facture_fourn_det as ffd";
            $sql .= "  LEFT JOIN " . $db->prefix() . "facture_fourn as ff ON ff.rowid = ffd.fk_facture_fourn";
            $sql .= " WHERE ff.datef >= '" . $db->idate($search_date_start) . "'";
            $sql .= "  AND ff.datef <= '" . $db->idate($search_date_end) . "'";
            // Define begin binding date
            if (getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) {
                $sql .= " AND ff.datef >= '" . $db->idate(getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) . "'";
            }
            $sql .= " AND ff.entity IN (" . getEntity('facture_fourn', 0) . ")"; // We don't share object for accountancy
            $sql .= "  AND ff.fk_statut > 0";
            $sql .= "  AND ffd.product_type <= 2";
            if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {
                $sql .= " AND ff.type IN (" . FactureFournisseur::TYPE_STANDARD . ", " . FactureFournisseur::TYPE_REPLACEMENT . ", " . FactureFournisseur::TYPE_CREDIT_NOTE . ")";
            } else {
                $sql .= " AND ff.type IN (" . FactureFournisseur::TYPE_STANDARD . ", " . FactureFournisseur::TYPE_REPLACEMENT . ", " . FactureFournisseur::TYPE_CREDIT_NOTE . ", " . FactureFournisseur::TYPE_DEPOSIT . ")";
            }

            dol_syslog('htdocs/accountancy/supplier/index.php');
            $resql = $db->query($sql);
            if ($resql) {
                $num = $db->num_rows($resql);

                while ($row = $db->fetch_row($resql)) {
                    print '<tr><td>' . $row[0] . '</td>';
                    for ($i = 1; $i <= 12; $i++) {
                        print '<td class="right nowraponall amount">' . price($row[$i]) . '</td>';
                    }
                    print '<td class="right nowraponall amount"><b>' . price($row[13]) . '</b></td>';
                    print '</tr>';
                }
                $db->free($resql);
            } else {
                print $db->lasterror(); // Show last sql error
            }
            print "</table>\n";
            print '</div>';
        }

// End of page
        llxFooter();
        $db->close();
    }

    /**
     * \file        htdocs/accountancy/supplier/lines.php
     * \ingroup     Accountancy (Double entries)
     * \brief       Page of detail of the lines of ventilation of invoices suppliers
     */
    public function lines()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(array("compta", "bills", "other", "accountancy", "productbatch", "products"));

        $optioncss = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')

        $account_parent = GETPOST('account_parent');
        $changeaccount = GETPOST('changeaccount');
// Search Getpost
        $search_societe = GETPOST('search_societe', 'alpha');
        $search_lineid = GETPOSTINT('search_lineid');
        $search_ref = GETPOST('search_ref', 'alpha');
        $search_invoice = GETPOST('search_invoice', 'alpha');
//$search_ref_supplier = GETPOST('search_ref_supplier', 'alpha');
        $search_label = GETPOST('search_label', 'alpha');
        $search_desc = GETPOST('search_desc', 'alpha');
        $search_amount = GETPOST('search_amount', 'alpha');
        $search_account = GETPOST('search_account', 'alpha');
        $search_vat = GETPOST('search_vat', 'alpha');
        $search_date_startday = GETPOSTINT('search_date_startday');
        $search_date_startmonth = GETPOSTINT('search_date_startmonth');
        $search_date_startyear = GETPOSTINT('search_date_startyear');
        $search_date_endday = GETPOSTINT('search_date_endday');
        $search_date_endmonth = GETPOSTINT('search_date_endmonth');
        $search_date_endyear = GETPOSTINT('search_date_endyear');
        $search_date_start = dol_mktime(0, 0, 0, $search_date_startmonth, $search_date_startday, $search_date_startyear);   // Use tzserver
        $search_date_end = dol_mktime(23, 59, 59, $search_date_endmonth, $search_date_endday, $search_date_endyear);
        $search_country = GETPOST('search_country', 'alpha');
        $search_tvaintra = GETPOST('search_tvaintra', 'alpha');

// Load variable for pagination
        $limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : getDolGlobalString('ACCOUNTING_LIMIT_LIST_VENTILATION', $conf->liste_limit);
        $sortfield = GETPOST('sortfield', 'aZ09comma');
        $sortorder = GETPOST('sortorder', 'aZ09comma');
        $page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
        if (empty($page) || $page < 0) {
            $page = 0;
        }
        $offset = $limit * $page;
        $pageprev = $page - 1;
        $pagenext = $page + 1;
        if (!$sortfield) {
            $sortfield = "f.datef, f.ref, l.rowid";
        }
        if (!$sortorder) {
            if (getDolGlobalInt('ACCOUNTING_LIST_SORT_VENTILATION_DONE') > 0) {
                $sortorder = "DESC";
            } else {
                $sortorder = "ASC";
            }
        }

        $formaccounting = new FormAccounting($db);

// Security check
        if (!isModEnabled('accounting')) {
            accessforbidden();
        }
        if ($user->socid > 0) {
            accessforbidden();
        }
        if (!$user->hasRight('accounting', 'mouvements', 'lire')) {
            accessforbidden();
        }


        $formaccounting = new FormAccounting($db);


        /*
         * Actions
         */

// Purge search criteria
        if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
            $search_societe = '';
            $search_lineid = '';
            $search_ref = '';
            $search_invoice = '';
            //$search_ref_supplier = '';
            $search_label = '';
            $search_desc = '';
            $search_amount = '';
            $search_account = '';
            $search_vat = '';
            $search_date_startday = '';
            $search_date_startmonth = '';
            $search_date_startyear = '';
            $search_date_endday = '';
            $search_date_endmonth = '';
            $search_date_endyear = '';
            $search_date_start = '';
            $search_date_end = '';
            $search_country = '';
            $search_tvaintra = '';
        }

        if (is_array($changeaccount) && count($changeaccount) > 0 && $user->hasRight('accounting', 'bind', 'write')) {
            $error = 0;

            if (!(GETPOSTINT('account_parent') >= 0)) {
                $error++;
                setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Account")), null, 'errors');
            }

            if (!$error) {
                $db->begin();

                $sql1 = "UPDATE " . MAIN_DB_PREFIX . "facture_fourn_det";
                $sql1 .= " SET fk_code_ventilation=" . (GETPOSTINT('account_parent') > 0 ? GETPOSTINT('account_parent') : '0');
                $sql1 .= ' WHERE rowid IN (' . $db->sanitize(implode(',', $changeaccount)) . ')';

                dol_syslog('accountancy/supplier/lines.php::changeaccount sql= ' . $sql1);
                $resql1 = $db->query($sql1);
                if (!$resql1) {
                    $error++;
                    setEventMessages($db->lasterror(), null, 'errors');
                }
                if (!$error) {
                    $db->commit();
                    setEventMessages($langs->trans("Save"), null, 'mesgs');
                } else {
                    $db->rollback();
                    setEventMessages($db->lasterror(), null, 'errors');
                }

                $account_parent = ''; // Protection to avoid to mass apply it a second time
            }
        }

        if (GETPOST('sortfield') == 'f.datef, f.ref, l.rowid') {
            $value = (GETPOST('sortorder') == 'asc,asc,asc' ? 0 : 1);
            require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
            $res = dolibarr_set_const($db, "ACCOUNTING_LIST_SORT_VENTILATION_DONE", $value, 'yesno', 0, '', $conf->entity);
        }


        /*
         * View
         */

        $form = new Form($db);
        $formother = new FormOther($db);

        $help_url = 'EN:Module_Double_Entry_Accounting|FR:Module_Comptabilit&eacute;_en_Partie_Double#Liaisons_comptables';

        llxHeader('', $langs->trans("SuppliersVentilation") . ' - ' . $langs->trans("Dispatched"), $help_url);

        print '<script type="text/javascript">
			$(function () {
				$(\'#select-all\').click(function(event) {
				    // Iterate each checkbox
				    $(\':checkbox\').each(function() {
				    	this.checked = true;
				    });
			    });
			    $(\'#unselect-all\').click(function(event) {
				    // Iterate each checkbox
				    $(\':checkbox\').each(function() {
				    	this.checked = false;
				    });
			    });
			});
			 </script>';

        /*
         * Supplier Invoice lines
         */
        $sql = "SELECT f.rowid as facid, f.ref as ref, f.ref_supplier, f.type as ftype, f.libelle as invoice_label, f.datef, f.fk_soc,";
        $sql .= " l.rowid, l.fk_product, l.product_type as line_type, l.description, l.total_ht , l.qty, l.tva_tx, l.vat_src_code,";
        $sql .= " aa.rowid as fk_compte, aa.label as label_account, aa.labelshort as labelshort_account, aa.account_number,";
        $sql .= " p.rowid as product_id, p.fk_product_type as product_type, p.ref as product_ref, p.label as product_label, p.fk_product_type as type, p.tobuy, p.tosell,";
        if (getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED')) {
            $sql .= " ppe.accountancy_code_buy, ppe.accountancy_code_buy_intra, ppe.accountancy_code_buy_export,";
        } else {
            $sql .= " p.accountancy_code_buy, p.accountancy_code_buy_intra, p.accountancy_code_buy_export,";
        }
        $sql .= " co.code as country_code, co.label as country,";
        $sql .= " s.rowid as socid, s.nom as name, s.tva_intra, s.email, s.town, s.zip, s.fk_pays, s.client, s.fournisseur, s.code_client, s.code_fournisseur";
        if (getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
            $sql .= ", spe.accountancy_code_customer as code_compta_client";
            $sql .= ", spe.accountancy_code_supplier as code_compta_fournisseur";
        } else {
            $sql .= ", s.code_compta as code_compta_client";
            $sql .= ", s.code_compta_fournisseur";
        }
        $parameters = array();
        $reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
        $sql .= $hookmanager->resPrint;
        $sql .= " FROM " . MAIN_DB_PREFIX . "facture_fourn_det as l";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product as p ON p.rowid = l.fk_product";
        if (getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED')) {
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product_perentity as ppe ON ppe.fk_product = p.rowid AND ppe.entity = " . ((int) $conf->entity);
        }
        $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "accounting_account as aa ON aa.rowid = l.fk_code_ventilation";
        $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "facture_fourn as f ON f.rowid = l.fk_facture_fourn";
        $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid = f.fk_soc";
        if (getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_perentity as spe ON spe.fk_soc = s.rowid AND spe.entity = " . ((int) $conf->entity);
        }
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_country as co ON co.rowid = s.fk_pays ";
        $sql .= " WHERE f.rowid = l.fk_facture_fourn and f.fk_statut >= 1 AND l.fk_code_ventilation <> 0 ";
// Add search filter like
        if ($search_societe) {
            $sql .= natural_search('s.nom', $search_societe);
        }
        if ($search_lineid) {
            $sql .= natural_search("l.rowid", $search_lineid, 1);
        }
        if (strlen(trim($search_invoice))) {
            $sql .= natural_search(array("f.ref", "f.ref_supplier"), $search_invoice);
        }
        /*if (strlen(trim($search_ref_supplier))) {
            $sql .= natural_search("f.ref_supplier", $search_ref_supplier);
        }*/
        if (strlen(trim($search_label))) {
            $sql .= natural_search("f.libelle", $search_label);
        }
        if (strlen(trim($search_ref))) {
            $sql .= natural_search("p.ref", $search_ref);
        }
        if (strlen(trim($search_desc))) {
            $sql .= natural_search("l.description", $search_desc);
        }
        if (strlen(trim($search_amount))) {
            $sql .= natural_search("l.total_ht", $search_amount, 1);
        }
        if (strlen(trim($search_account))) {
            $sql .= natural_search("aa.account_number", $search_account);
        }
        if (strlen(trim($search_vat))) {
            $sql .= natural_search("l.tva_tx", price2num($search_vat), 1);
        }
        if ($search_date_start) {
            $sql .= " AND f.datef >= '" . $db->idate($search_date_start) . "'";
        }
        if ($search_date_end) {
            $sql .= " AND f.datef <= '" . $db->idate($search_date_end) . "'";
        }
        if (strlen(trim($search_country))) {
            $arrayofcode = getCountriesInEEC();
            $country_code_in_EEC = $country_code_in_EEC_without_me = '';
            foreach ($arrayofcode as $key => $value) {
                $country_code_in_EEC .= ($country_code_in_EEC ? "," : "") . "'" . $value . "'";
                if ($value != $mysoc->country_code) {
                    $country_code_in_EEC_without_me .= ($country_code_in_EEC_without_me ? "," : "") . "'" . $value . "'";
                }
            }
            if ($search_country == 'special_allnotme') {
                $sql .= " AND co.code <> '" . $db->escape($mysoc->country_code) . "'";
            } elseif ($search_country == 'special_eec') {
                $sql .= " AND co.code IN (" . $db->sanitize($country_code_in_EEC, 1) . ")";
            } elseif ($search_country == 'special_eecnotme') {
                $sql .= " AND co.code IN (" . $db->sanitize($country_code_in_EEC_without_me, 1) . ")";
            } elseif ($search_country == 'special_noteec') {
                $sql .= " AND co.code NOT IN (" . $db->sanitize($country_code_in_EEC, 1) . ")";
            } else {
                $sql .= natural_search("co.code", $search_country);
            }
        }
        if (strlen(trim($search_tvaintra))) {
            $sql .= natural_search("s.tva_intra", $search_tvaintra);
        }
        $sql .= " AND f.entity IN (" . getEntity('facture_fourn', 0) . ")"; // We don't share object for accountancy

// Add where from hooks
        $parameters = array();
        $reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
        $sql .= $hookmanager->resPrint;

        $sql .= $db->order($sortfield, $sortorder);

// Count total nb of records
        $nbtotalofrecords = '';
        if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
            $result = $db->query($sql);
            $nbtotalofrecords = $db->num_rows($result);
            if (($page * $limit) > $nbtotalofrecords) { // if total resultset is smaller then paging size (filtering), goto and load page 0
                $page = 0;
                $offset = 0;
            }
        }

        $sql .= $db->plimit($limit + 1, $offset);

        dol_syslog("accountancy/supplier/lines.php", LOG_DEBUG);
        $result = $db->query($sql);
        if ($result) {
            $num_lines = $db->num_rows($result);
            $i = 0;

            $param = '';
            if (!empty($contextpage) && $contextpage != $_SERVER['PHP_SELF']) {
                $param .= '&contextpage=' . urlencode($contextpage);
            }
            if ($limit > 0 && $limit != $conf->liste_limit) {
                $param .= '&limit=' . ((int) $limit);
            }
            if ($search_societe) {
                $param .= "&search_societe=" . urlencode($search_societe);
            }
            if ($search_invoice) {
                $param .= "&search_invoice=" . urlencode($search_invoice);
            }
            if ($search_ref) {
                $param .= "&search_ref=" . urlencode($search_ref);
            }
            /*if ($search_ref_supplier) {
                $param .= '&search_ref_supplier='.urlencode($search_ref_supplier);
            }*/
            if ($search_label) {
                $param .= "&search_label=" . urlencode($search_label);
            }
            if ($search_desc) {
                $param .= "&search_desc=" . urlencode($search_desc);
            }
            if ($search_account) {
                $param .= "&search_account=" . urlencode($search_account);
            }
            if ($search_vat) {
                $param .= "&search_vat=" . urlencode($search_vat);
            }
            if ($search_date_startday) {
                $param .= '&search_date_startday=' . urlencode((string) ($search_date_startday));
            }
            if ($search_date_startmonth) {
                $param .= '&search_date_startmonth=' . urlencode((string) ($search_date_startmonth));
            }
            if ($search_date_startyear) {
                $param .= '&search_date_startyear=' . urlencode((string) ($search_date_startyear));
            }
            if ($search_date_endday) {
                $param .= '&search_date_endday=' . urlencode((string) ($search_date_endday));
            }
            if ($search_date_endmonth) {
                $param .= '&search_date_endmonth=' . urlencode((string) ($search_date_endmonth));
            }
            if ($search_date_endyear) {
                $param .= '&search_date_endyear=' . urlencode((string) ($search_date_endyear));
            }
            if ($search_country) {
                $param .= "&search_country=" . urlencode($search_country);
            }
            if ($search_tvaintra) {
                $param .= "&search_tvaintra=" . urlencode($search_tvaintra);
            }

            print '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">' . "\n";
            print '<input type="hidden" name="action" value="ventil">';
            if ($optioncss != '') {
                print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
            }
            print '<input type="hidden" name="token" value="' . newToken() . '">';
            print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
            print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
            print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
            print '<input type="hidden" name="page" value="' . $page . '">';

            // @phan-suppress-next-line PhanPluginSuspiciousParamOrder
            print_barre_liste($langs->trans("InvoiceLinesDone"), $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, '', $num_lines, $nbtotalofrecords, 'title_accountancy', 0, '', '', $limit);
            print '<span class="opacitymedium">' . $langs->trans("DescVentilDoneSupplier") . '</span><br>';

            print '<br><div class="inline-block divButAction paddingbottom">' . $langs->trans("ChangeAccount") . ' ';
            print $formaccounting->select_account($account_parent, 'account_parent', 2, array(), 0, 0, 'maxwidth300 maxwidthonsmartphone valignmiddle');
            print '<input type="submit" class="button small valignmiddle" value="' . $langs->trans("ChangeBinding") . '"/></div>';

            $moreforfilter = '';

            print '<div class="div-table-responsive">';
            print '<table class="tagtable liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">' . "\n";

            // We add search filter
            print '<tr class="liste_titre_filter">';
            print '<td class="liste_titre"><input type="text" class="flat maxwidth25" name="search_lineid" value="' . dol_escape_htmltag($search_lineid) . '"></td>';
            print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_invoice" value="' . dol_escape_htmltag($search_invoice) . '"></td>';
            //print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_ref_supplier" value="'.dol_escape_htmltag($search_ref_supplier).'"></td>';
            print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_label" value="' . dol_escape_htmltag($search_label) . '"></td>';
            print '<td class="liste_titre center">';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_date_start ? $search_date_start : -1, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
            print '</div>';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_date_end ? $search_date_end : -1, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
            print '</div>';
            print '</td>';
            print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_ref" value="' . dol_escape_htmltag($search_ref) . '"></td>';
            print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_desc" value="' . dol_escape_htmltag($search_desc) . '"></td>';
            print '<td class="liste_titre right"><input type="text" class="right flat maxwidth50" name="search_amount" value="' . dol_escape_htmltag($search_amount) . '"></td>';
            print '<td class="liste_titre right"><input type="text" class="right flat maxwidth50" name="search_vat" placeholder="%" size="1" value="' . dol_escape_htmltag($search_vat) . '"></td>';
            print '<td class="liste_titre"><input type="text" class="flat maxwidth75imp" name="search_societe" value="' . dol_escape_htmltag($search_societe) . '"></td>';
            print '<td class="liste_titre">';
            print $form->select_country($search_country, 'search_country', '', 0, 'maxwidth100', 'code2', 1, 0, 1);
            //  print '<input type="text" class="flat maxwidth50" name="search_country" value="' . dol_escape_htmltag($search_country) . '">';
            print '</td>';
            print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_tvaintra" value="' . dol_escape_htmltag($search_tvaintra) . '"></td>';
            print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_account" value="' . dol_escape_htmltag($search_account) . '"></td>';
            print '<td class="liste_titre center">';
            $searchpicto = $form->showFilterButtons();
            print $searchpicto;
            print "</td></tr>\n";

            print '<tr class="liste_titre">';
            print_liste_field_titre("LineId", $_SERVER['PHP_SELF'], "l.rowid", "", $param, '', $sortfield, $sortorder);
            print_liste_field_titre("Invoice", $_SERVER['PHP_SELF'], "f.ref", "", $param, '', $sortfield, $sortorder);
            //print_liste_field_titre("RefSupplier", $_SERVER['PHP_SELF'], "f.ref_supplier", "", $param, '', $sortfield, $sortorder);
            print_liste_field_titre("InvoiceLabel", $_SERVER['PHP_SELF'], "f.libelle", "", $param, '', $sortfield, $sortorder);
            print_liste_field_titre("Date", $_SERVER['PHP_SELF'], "f.datef, f.ref, l.rowid", "", $param, '', $sortfield, $sortorder, 'center ');
            print_liste_field_titre("ProductRef", $_SERVER['PHP_SELF'], "p.ref", "", $param, '', $sortfield, $sortorder);
            //print_liste_field_titre("ProductLabel", $_SERVER['PHP_SELF'], "p.label", "", $param, '', $sortfield, $sortorder);
            print_liste_field_titre("ProductDescription", $_SERVER['PHP_SELF'], "l.description", "", $param, '', $sortfield, $sortorder);
            print_liste_field_titre("Amount", $_SERVER['PHP_SELF'], "l.total_ht", "", $param, '', $sortfield, $sortorder, 'right ');
            print_liste_field_titre("VATRate", $_SERVER['PHP_SELF'], "l.tva_tx", "", $param, '', $sortfield, $sortorder, 'right ');
            print_liste_field_titre("ThirdParty", $_SERVER['PHP_SELF'], "s.nom", "", $param, '', $sortfield, $sortorder);
            print_liste_field_titre("Country", $_SERVER['PHP_SELF'], "co.label", "", $param, '', $sortfield, $sortorder);
            print_liste_field_titre("VATIntraShort", $_SERVER['PHP_SELF'], "s.tva_intra", "", $param, '', $sortfield, $sortorder);
            print_liste_field_titre("AccountAccounting", $_SERVER['PHP_SELF'], "aa.account_number", "", $param, '', $sortfield, $sortorder);
            $checkpicto = $form->showCheckAddButtons();
            print_liste_field_titre($checkpicto, '', '', '', '', '', '', '', 'center ');
            print "</tr>\n";

            $thirdpartystatic = new Societe($db);
            $facturefournisseur_static = new FactureFournisseur($db);
            $productstatic = new ProductFournisseur($db);
            $accountingaccountstatic = new AccountingAccount($db);

            $i = 0;
            while ($i < min($num_lines, $limit)) {
                $objp = $db->fetch_object($result);

                $facturefournisseur_static->ref = $objp->ref;
                $facturefournisseur_static->id = $objp->facid;
                $facturefournisseur_static->type = $objp->ftype;
                $facturefournisseur_static->ref_supplier = $objp->ref_supplier;
                $facturefournisseur_static->label = $objp->invoice_label;

                $thirdpartystatic->id = $objp->socid;
                $thirdpartystatic->name = $objp->name;
                $thirdpartystatic->client = $objp->client;
                $thirdpartystatic->fournisseur = $objp->fournisseur;
                $thirdpartystatic->code_client = $objp->code_client;
                $thirdpartystatic->code_compta_client = $objp->code_compta_client;
                $thirdpartystatic->code_fournisseur = $objp->code_fournisseur;
                $thirdpartystatic->code_compta_fournisseur = $objp->code_compta_fournisseur;
                $thirdpartystatic->email = $objp->email;
                $thirdpartystatic->country_code = $objp->country_code;

                $productstatic->ref = $objp->product_ref;
                $productstatic->id = $objp->product_id;
                $productstatic->label = $objp->product_label;
                $productstatic->type = $objp->line_type;
                $productstatic->status = $objp->tosell;
                $productstatic->status_buy = $objp->tobuy;
                $productstatic->accountancy_code_buy = $objp->accountancy_code_buy;
                $productstatic->accountancy_code_buy_intra = $objp->accountancy_code_buy_intra;
                $productstatic->accountancy_code_buy_export = $objp->accountancy_code_buy_export;

                $accountingaccountstatic->rowid = $objp->fk_compte;
                $accountingaccountstatic->label = $objp->label_account;
                $accountingaccountstatic->labelshort = $objp->labelshort_account;
                $accountingaccountstatic->account_number = $objp->account_number;

                print '<tr class="oddeven">';

                // Line id
                print '<td>' . $objp->rowid . '</td>';

                // Ref Invoice
                print '<td class="nowraponall">' . $facturefournisseur_static->getNomUrl(1);
                if ($objp->ref_supplier) {
                    print '<br><span class="opacitymedium small">' . dol_escape_htmltag($objp->ref_supplier) . '</span>';
                }
                print '</td>';

                // Ref supplier invoice
                /*
                print '<td class="tdoverflowmax100" title="'.dol_escape_htmltag($objp->ref_supplier).'">';
                print $objp->ref_supplier;
                print '</td>';
                */

                // Supplier invoice label
                print '<td class="tdoverflowonsmartphone small" title="' . dol_escape_htmltag($objp->invoice_label) . '">';
                print $objp->invoice_label;
                print '</td>';

                // Date invoice
                print '<td class="center">' . dol_print_date($db->jdate($objp->datef), 'day') . '</td>';

                // Ref Product
                print '<td class="tdoverflowmax100">';
                if ($productstatic->id > 0) {
                    print $productstatic->getNomUrl(1);
                }
                if ($productstatic->id > 0 && $objp->product_label) {
                    print '<br>';
                }
                if ($objp->product_label) {
                    print '<span class="opacitymedium">' . $objp->product_label . '</span>';
                }
                print '</td>';

                print '<td class="tdoverflowonsmartphone small">';
                $text = dolGetFirstLineOfText(dol_string_nohtmltag($objp->description, 1));
                $trunclength = getDolGlobalInt('ACCOUNTING_LENGTH_DESCRIPTION', 32);
                print $form->textwithtooltip(dol_trunc($text, $trunclength), $objp->description);
                print '</td>';

                print '<td class="right nowraponall amount">' . price($objp->total_ht) . '</td>';

                print '<td class="right">' . vatrate($objp->tva_tx . ($objp->vat_src_code ? ' (' . $objp->vat_src_code . ')' : '')) . '</td>';

                // Thirdparty
                print '<td class="tdoverflowmax100">' . $thirdpartystatic->getNomUrl(1, 'supplier') . '</td>';

                // Country
                print '<td>';
                if ($objp->country_code) {
                    print $langs->trans("Country" . $objp->country_code) . ' (' . $objp->country_code . ')';
                }
                print '</td>';

                print '<td class="tdoverflowmax80" title="' . dol_escape_htmltag($objp->tva_intra) . '">' . dol_escape_htmltag($objp->tva_intra) . '</td>';

                print '<td>';
                print $accountingaccountstatic->getNomUrl(0, 1, 1, '', 1);
                print ' <a class="editfielda" href="./card.php?id=' . $objp->rowid . '&backtopage=' . urlencode($_SERVER['PHP_SELF'] . ($param ? '?' . $param : '')) . '">';
                print img_edit();
                print '</a></td>';
                print '<td class="center"><input type="checkbox" class="checkforaction" name="changeaccount[]" value="' . $objp->rowid . '"/></td>';

                print '</tr>';
                $i++;
            }
            if ($num_lines == 0) {
                print '<tr><td colspan="13"><span class="opacitymedium">' . $langs->trans("NoRecordFound") . '</span></td></tr>';
            }

            print '</table>';
            print "</div>";

            if ($nbtotalofrecords > $limit) {
                print_barre_liste('', $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, '', $num_lines, $nbtotalofrecords, '', 0, '', '', $limit, 1);
            }

            print '</form>';
        } else {
            print $db->lasterror();
        }

// End of page
        llxFooter();
        $db->close();
    }

    /**
     * \file        htdocs/accountancy/supplier/list.php
     * \ingroup     Accountancy (Double entries)
     * \brief       Ventilation page from suppliers invoices
     */
    public function list()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(array("bills", "companies", "compta", "accountancy", "other", "productbatch", "products"));

        $action = GETPOST('action', 'aZ09');
        $massaction = GETPOST('massaction', 'alpha');
        $confirm = GETPOST('confirm', 'alpha');
        $toselect = GETPOST('toselect', 'array');
        $contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'accountancysupplierlist'; // To manage different context of search
        $optioncss = GETPOST('optioncss', 'alpha');

        $default_account = GETPOSTINT('default_account');

// Select Box
        $mesCasesCochees = GETPOST('toselect', 'array');

// Search Getpost
        $search_societe = GETPOST('search_societe', 'alpha');
        $search_lineid = GETPOSTINT('search_lineid');
        $search_ref = GETPOST('search_ref', 'alpha');
        $search_ref_supplier = GETPOST('search_ref_supplier', 'alpha');
        $search_invoice = GETPOST('search_invoice', 'alpha');
        $search_label = GETPOST('search_label', 'alpha');
        $search_desc = GETPOST('search_desc', 'alpha');
        $search_amount = GETPOST('search_amount', 'alpha');
        $search_account = GETPOST('search_account', 'alpha');
        $search_vat = GETPOST('search_vat', 'alpha');
        $search_date_startday = GETPOSTINT('search_date_startday');
        $search_date_startmonth = GETPOSTINT('search_date_startmonth');
        $search_date_startyear = GETPOSTINT('search_date_startyear');
        $search_date_endday = GETPOSTINT('search_date_endday');
        $search_date_endmonth = GETPOSTINT('search_date_endmonth');
        $search_date_endyear = GETPOSTINT('search_date_endyear');
        $search_date_start = dol_mktime(0, 0, 0, $search_date_startmonth, $search_date_startday, $search_date_startyear);   // Use tzserver
        $search_date_end = dol_mktime(23, 59, 59, $search_date_endmonth, $search_date_endday, $search_date_endyear);
        $search_country = GETPOST('search_country', 'alpha');
        $search_tvaintra = GETPOST('search_tvaintra', 'alpha');

// Load variable for pagination
        $limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : getDolGlobalString('ACCOUNTING_LIMIT_LIST_VENTILATION', $conf->liste_limit);
        $sortfield = GETPOST('sortfield', 'aZ09comma');
        $sortorder = GETPOST('sortorder', 'aZ09comma');
        $page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
        if (empty($page) || $page < 0) {
            $page = 0;
        }
        $offset = $limit * $page;
        $pageprev = $page - 1;
        $pagenext = $page + 1;
        if (!$sortfield) {
            $sortfield = "f.datef, f.ref, l.rowid";
        }
        if (!$sortorder) {
            if (getDolGlobalInt('ACCOUNTING_LIST_SORT_VENTILATION_TODO') > 0) {
                $sortorder = "DESC";
            } else {
                $sortorder = "ASC";
            }
        }

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
        $hookmanager->initHooks(array('accountancysupplierlist'));

        $formaccounting = new FormAccounting($db);
        $accountingAccount = new AccountingAccount($db);

        $chartaccountcode = dol_getIdFromCode($db, getDolGlobalInt('CHARTOFACCOUNTS'), 'accounting_system', 'rowid', 'pcg_version');

// Security check
        if (!isModEnabled('accounting')) {
            accessforbidden();
        }
        if ($user->socid > 0) {
            accessforbidden();
        }
        if (!$user->hasRight('accounting', 'mouvements', 'lire')) {
            accessforbidden();
        }

// Define begin binding date
        if (empty($search_date_start) && getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) {
            $search_date_start = $db->idate(getDolGlobalString('ACCOUNTING_DATE_START_BINDING'));
        }


        /*
         * Actions
         */

        if (GETPOST('cancel', 'alpha')) {
            $action = 'list';
            $massaction = '';
        }
        if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
            $massaction = '';
        }

        $parameters = array();
        $reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

        if (empty($reshook)) {
            // Purge search criteria
            if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All test are required to be compatible with all browsers
                $search_societe = '';
                $search_lineid = '';
                $search_ref = '';
                $search_ref_supplier = '';
                $search_invoice = '';
                $search_label = '';
                $search_desc = '';
                $search_amount = '';
                $search_account = '';
                $search_vat = '';
                $search_date_startday = '';
                $search_date_startmonth = '';
                $search_date_startyear = '';
                $search_date_endday = '';
                $search_date_endmonth = '';
                $search_date_endyear = '';
                $search_date_start = '';
                $search_date_end = '';
                $search_country = '';
                $search_tvaintra = '';
            }

            // Mass actions
            $objectclass = 'AccountingAccount';
            $permissiontoread = $user->hasRight('accounting', 'read');
            $permissiontodelete = $user->hasRight('accounting', 'delete');
            $uploaddir = $conf->accounting->dir_output;
            include DOL_DOCUMENT_ROOT . '/core/actions_massactions.inc.php';
        }


        if ($massaction == 'ventil' && $user->hasRight('accounting', 'bind', 'write')) {
            $msg = '';

            if (!empty($mesCasesCochees)) {
                $msg = '<div>' . $langs->trans("SelectedLines") . ': ' . count($mesCasesCochees) . '</div>';
                $msg .= '<div class="detail">';
                $cpt = 0;
                $ok = 0;
                $ko = 0;

                foreach ($mesCasesCochees as $maLigneCochee) {
                    $maLigneCourante = explode("_", $maLigneCochee);
                    $monId = $maLigneCourante[0];
                    $monCompte = GETPOST('codeventil' . $monId);

                    if ($monCompte <= 0) {
                        $msg .= '<div><span class="error">' . $langs->trans("Lineofinvoice") . ' ' . $monId . ' - ' . $langs->trans("NoAccountSelected") . '</span></div>';
                        $ko++;
                    } else {
                        $sql = " UPDATE " . MAIN_DB_PREFIX . "facture_fourn_det";
                        $sql .= " SET fk_code_ventilation = " . ((int) $monCompte);
                        $sql .= " WHERE rowid = " . ((int) $monId);

                        $accountventilated = new AccountingAccount($db);
                        $accountventilated->fetch($monCompte, '', 1);

                        dol_syslog('accountancy/supplier/list.php', LOG_DEBUG);
                        if ($db->query($sql)) {
                            $msg .= '<div><span class="green">' . $langs->trans("Lineofinvoice") . ' ' . $monId . ' - ' . $langs->trans("VentilatedinAccount") . ' : ' . length_accountg($accountventilated->account_number) . '</span></div>';
                            $ok++;
                        } else {
                            $msg .= '<div><span class="error">' . $langs->trans("ErrorDB") . ' : ' . $langs->trans("Lineofinvoice") . ' ' . $monId . ' - ' . $langs->trans("NotVentilatedinAccount") . ' : ' . length_accountg($accountventilated->account_number) . '<br> <pre>' . $sql . '</pre></span></div>';
                            $ko++;
                        }
                    }

                    $cpt++;
                }
                $msg .= '</div>';
                $msg .= '<div>' . $langs->trans("EndProcessing") . '</div>';
            }
        }

        if (GETPOST('sortfield') == 'f.datef, f.ref, l.rowid') {
            $value = (GETPOST('sortorder') == 'asc,asc,asc' ? 0 : 1);
            require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
            $res = dolibarr_set_const($db, "ACCOUNTING_LIST_SORT_VENTILATION_TODO", $value, 'yesno', 0, '', $conf->entity);
        }


        /*
         * View
         */

        $form = new Form($db);
        $formother = new FormOther($db);

        $help_url = 'EN:Module_Double_Entry_Accounting|FR:Module_Comptabilit&eacute;_en_Partie_Double#Liaisons_comptables';

        llxHeader('', $langs->trans("SuppliersVentilation"), $help_url);

        if (empty($chartaccountcode)) {
            print $langs->trans("ErrorChartOfAccountSystemNotSelected");
            // End of page
            llxFooter();
            $db->close();
            exit;
        }

// Supplier Invoice Lines
        $sql = "SELECT f.rowid as facid, f.ref, f.ref_supplier, f.libelle as invoice_label, f.datef, f.type as ftype, f.fk_facture_source,";
        $sql .= " l.rowid, l.fk_product, l.description, l.total_ht, l.fk_code_ventilation, l.product_type as type_l, l.tva_tx as tva_tx_line, l.vat_src_code,";
        $sql .= " p.rowid as product_id, p.ref as product_ref, p.label as product_label, p.fk_product_type as type, p.tva_tx as tva_tx_prod,";
        if (getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED')) {
            $sql .= " ppe.accountancy_code_sell as code_sell, ppe.accountancy_code_sell_intra as code_sell_intra, ppe.accountancy_code_sell_export as code_sell_export,";
            $sql .= " ppe.accountancy_code_buy as code_buy, ppe.accountancy_code_buy_intra as code_buy_intra, ppe.accountancy_code_buy_export as code_buy_export,";
        } else {
            $sql .= " p.accountancy_code_sell as code_sell, p.accountancy_code_sell_intra as code_sell_intra, p.accountancy_code_sell_export as code_sell_export,";
            $sql .= " p.accountancy_code_buy as code_buy, p.accountancy_code_buy_intra as code_buy_intra, p.accountancy_code_buy_export as code_buy_export,";
        }
        $sql .= " p.tosell as status, p.tobuy as status_buy,";
        $sql .= " aa.rowid as aarowid, aa2.rowid as aarowid_intra, aa3.rowid as aarowid_export, aa4.rowid as aarowid_thirdparty,";
        $sql .= " co.code as country_code, co.label as country_label,";
        $sql .= " s.rowid as socid, s.nom as name, s.tva_intra, s.email, s.town, s.zip, s.fk_pays, s.client, s.fournisseur, s.code_client, s.code_fournisseur,";
        if (getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
            $sql .= " spe.accountancy_code_customer as code_compta_client,";
            $sql .= " spe.accountancy_code_supplier as code_compta_fournisseur,";
            $sql .= " spe.accountancy_code_buy as company_code_buy";
        } else {
            $sql .= " s.code_compta as code_compta_client,";
            $sql .= " s.code_compta_fournisseur,";
            $sql .= " s.accountancy_code_buy as company_code_buy";
        }
        $parameters = array();
        $reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
        $sql .= $hookmanager->resPrint;
        $sql .= " FROM " . MAIN_DB_PREFIX . "facture_fourn as f";
        $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid = f.fk_soc";
        if (getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_perentity as spe ON spe.fk_soc = s.rowid AND spe.entity = " . ((int) $conf->entity);
        }
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_country as co ON co.rowid = s.fk_pays ";
        $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "facture_fourn_det as l ON f.rowid = l.fk_facture_fourn";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product as p ON p.rowid = l.fk_product";
        if (getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED')) {
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product_perentity as ppe ON ppe.fk_product = p.rowid AND ppe.entity = " . ((int) $conf->entity);
        }
        $alias_societe_perentity = !getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED') ? "s" : "spe";
        $alias_product_perentity = !getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED') ? "p" : "ppe";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_account as aa  ON " . $alias_product_perentity . ".accountancy_code_buy = aa.account_number         AND aa.active = 1  AND aa.fk_pcg_version = '" . $db->escape($chartaccountcode) . "' AND aa.entity = " . $conf->entity;
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_account as aa2 ON " . $alias_product_perentity . ".accountancy_code_buy_intra = aa2.account_number  AND aa2.active = 1 AND aa2.fk_pcg_version = '" . $db->escape($chartaccountcode) . "' AND aa2.entity = " . $conf->entity;
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_account as aa3 ON " . $alias_product_perentity . ".accountancy_code_buy_export = aa3.account_number AND aa3.active = 1 AND aa3.fk_pcg_version = '" . $db->escape($chartaccountcode) . "' AND aa3.entity = " . $conf->entity;
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_account as aa4 ON " . $alias_societe_perentity . ".accountancy_code_buy = aa4.account_number        AND aa4.active = 1 AND aa4.fk_pcg_version = '" . $db->escape($chartaccountcode) . "' AND aa4.entity = " . $conf->entity;
        $sql .= " WHERE f.fk_statut > 0 AND l.fk_code_ventilation <= 0";
        $sql .= " AND l.product_type <= 2";
// Add search filter like
        if ($search_societe) {
            $sql .= natural_search('s.nom', $search_societe);
        }
        if ($search_lineid) {
            $sql .= natural_search("l.rowid", $search_lineid, 1);
        }
        if (strlen(trim($search_invoice))) {
            $sql .= natural_search(array("f.ref", "f.ref_supplier"), $search_invoice);
        }
        if (strlen(trim($search_ref))) {
            $sql .= natural_search("p.ref", $search_ref);
        }
        /*if (strlen(trim($search_ref_supplier))) {
            $sql .= natural_search("f.ref_supplier", $search_ref_supplier);
        }*/
        if (strlen(trim($search_label))) {
            $sql .= natural_search(array("p.label", "f.libelle"), $search_label);
        }
        if (strlen(trim($search_desc))) {
            $sql .= natural_search("l.description", $search_desc);
        }
        if (strlen(trim($search_amount))) {
            $sql .= natural_search("l.total_ht", $search_amount, 1);
        }
        if (strlen(trim($search_account))) {
            $sql .= natural_search("aa.account_number", $search_account);
        }
        if (strlen(trim($search_vat))) {
            $sql .= natural_search("l.tva_tx", price2num($search_vat), 1);
        }
        if ($search_date_start) {
            $sql .= " AND f.datef >= '" . $db->idate($search_date_start) . "'";
        }
        if ($search_date_end) {
            $sql .= " AND f.datef <= '" . $db->idate($search_date_end) . "'";
        }
        if (strlen(trim($search_country))) {
            $arrayofcode = getCountriesInEEC();
            $country_code_in_EEC = $country_code_in_EEC_without_me = '';
            foreach ($arrayofcode as $key => $value) {
                $country_code_in_EEC .= ($country_code_in_EEC ? "," : "") . "'" . $value . "'";
                if ($value != $mysoc->country_code) {
                    $country_code_in_EEC_without_me .= ($country_code_in_EEC_without_me ? "," : "") . "'" . $value . "'";
                }
            }
            if ($search_country == 'special_allnotme') {
                $sql .= " AND co.code <> '" . $db->escape($mysoc->country_code) . "'";
            } elseif ($search_country == 'special_eec') {
                $sql .= " AND co.code IN (" . $db->sanitize($country_code_in_EEC, 1) . ")";
            } elseif ($search_country == 'special_eecnotme') {
                $sql .= " AND co.code IN (" . $db->sanitize($country_code_in_EEC_without_me, 1) . ")";
            } elseif ($search_country == 'special_noteec') {
                $sql .= " AND co.code NOT IN (" . $db->sanitize($country_code_in_EEC, 1) . ")";
            } else {
                $sql .= natural_search("co.code", $search_country);
            }
        }
        if (strlen(trim($search_tvaintra))) {
            $sql .= natural_search("s.tva_intra", $search_tvaintra);
        }
        if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {
            $sql .= " AND f.type IN (" . FactureFournisseur::TYPE_STANDARD . "," . FactureFournisseur::TYPE_REPLACEMENT . "," . FactureFournisseur::TYPE_CREDIT_NOTE . "," . FactureFournisseur::TYPE_SITUATION . ")";
        } else {
            $sql .= " AND f.type IN (" . FactureFournisseur::TYPE_STANDARD . "," . FactureFournisseur::TYPE_REPLACEMENT . "," . FactureFournisseur::TYPE_CREDIT_NOTE . "," . FactureFournisseur::TYPE_DEPOSIT . "," . FactureFournisseur::TYPE_SITUATION . ")";
        }
        $sql .= " AND f.entity IN (" . getEntity('facture_fourn', 0) . ")"; // We don't share object for accountancy

// Add where from hooks
        $parameters = array();
        $reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
        $sql .= $hookmanager->resPrint;

        $sql .= $db->order($sortfield, $sortorder);

// Count total nb of records
        $nbtotalofrecords = '';
        if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
            $result = $db->query($sql);
            $nbtotalofrecords = $db->num_rows($result);
            if (($page * $limit) > $nbtotalofrecords) { // if total resultset is smaller then paging size (filtering), goto and load page 0
                $page = 0;
                $offset = 0;
            }
        }

        $sql .= $db->plimit($limit + 1, $offset);

        dol_syslog('accountancy/supplier/list.php', LOG_DEBUG);
// MAX_JOIN_SIZE can be very low (ex: 300000) on some limited configurations (ex: https://www.online.net/fr/hosting/online-perso)
// This big SELECT command may exceed the MAX_JOIN_SIZE limit => Therefore we use SQL_BIG_SELECTS=1 to disable the MAX_JOIN_SIZE security
        if ($db->type == 'mysqli') {
            $db->query("SET SQL_BIG_SELECTS=1");
        }

        $result = $db->query($sql);
        if ($result) {
            $num_lines = $db->num_rows($result);
            $i = 0;

            $arrayofselected = is_array($toselect) ? $toselect : array();

            $param = '';
            if (!empty($contextpage) && $contextpage != $_SERVER['PHP_SELF']) {
                $param .= '&contextpage=' . urlencode($contextpage);
            }
            if ($limit > 0 && $limit != $conf->liste_limit) {
                $param .= '&limit=' . ((int) $limit);
            }
            if ($search_societe) {
                $param .= '&search_societe=' . urlencode($search_societe);
            }
            if ($search_lineid) {
                $param .= '&search_lineid=' . urlencode((string) ($search_lineid));
            }
            if ($search_date_startday) {
                $param .= '&search_date_startday=' . urlencode((string) ($search_date_startday));
            }
            if ($search_date_startmonth) {
                $param .= '&search_date_startmonth=' . urlencode((string) ($search_date_startmonth));
            }
            if ($search_date_startyear) {
                $param .= '&search_date_startyear=' . urlencode((string) ($search_date_startyear));
            }
            if ($search_date_endday) {
                $param .= '&search_date_endday=' . urlencode((string) ($search_date_endday));
            }
            if ($search_date_endmonth) {
                $param .= '&search_date_endmonth=' . urlencode((string) ($search_date_endmonth));
            }
            if ($search_date_endyear) {
                $param .= '&search_date_endyear=' . urlencode((string) ($search_date_endyear));
            }
            if ($search_invoice) {
                $param .= '&search_invoice=' . urlencode($search_invoice);
            }
            if ($search_ref) {
                $param .= '&search_ref=' . urlencode($search_ref);
            }
            if ($search_ref_supplier) {
                $param .= '&search_ref_supplier=' . urlencode($search_ref_supplier);
            }
            if ($search_label) {
                $param .= '&search_label=' . urlencode($search_label);
            }
            if ($search_desc) {
                $param .= '&search_desc=' . urlencode($search_desc);
            }
            if ($search_amount) {
                $param .= '&search_amount=' . urlencode($search_amount);
            }
            if ($search_vat) {
                $param .= '&search_vat=' . urlencode($search_vat);
            }
            if ($search_country) {
                $param .= "&search_country=" . urlencode($search_country);
            }
            if ($search_tvaintra) {
                $param .= "&search_tvaintra=" . urlencode($search_tvaintra);
            }

            $arrayofmassactions = array(
                'ventil' => img_picto('', 'check', 'class="pictofixedwidth"') . $langs->trans("Ventilate")
            ,'set_default_account' => img_picto('', 'check', 'class="pictofixedwidth"') . $langs->trans("ConfirmPreselectAccount")
                //'presend'=>img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
                //'builddoc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
            );
            //if ($user->hasRight('mymodule', 'supprimer')) $arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
            //if (in_array($massaction, array('presend','predelete'))) $arrayofmassactions=array();
            if ($massaction !== 'set_default_account') {
                $massactionbutton = $form->selectMassAction('ventil', $arrayofmassactions, 1);
            }

            print '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">' . "\n";
            print '<input type="hidden" name="action" value="ventil">';
            if ($optioncss != '') {
                print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
            }
            print '<input type="hidden" name="token" value="' . newToken() . '">';
            print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
            print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
            print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
            print '<input type="hidden" name="page" value="' . $page . '">';

            // @phan-suppress-next-line PhanPluginSuspiciousParamOrder
            print_barre_liste($langs->trans("InvoiceLines"), $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, $massactionbutton, $num_lines, $nbtotalofrecords, 'title_accountancy', 0, '', '', $limit);

            if ($massaction == 'set_default_account') {
                $formquestion = array();
                $formquestion[] = array('type' => 'other',
                    'name' => 'set_default_account',
                    'label' => $langs->trans("AccountancyCode"),
                    'value' => $formaccounting->select_account('', 'default_account', 1, array(), 0, 0, 'maxwidth200 maxwidthonsmartphone', 'cachewithshowemptyone'));
                print $form->formconfirm($_SERVER['PHP_SELF'], $langs->trans("ConfirmPreselectAccount"), $langs->trans("ConfirmPreselectAccountQuestion", count($toselect)), "confirm_set_default_account", $formquestion, 1, 0, 200, 500, 1);
            }

            print '<span class="opacitymedium">' . $langs->trans("DescVentilTodoCustomer") . '</span></br><br>';

            if (!empty($msg)) {
                print $msg . '<br>';
            }

            $moreforfilter = '';

            print '<div class="div-table-responsive">';
            print '<table class="tagtable liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">' . "\n";

            // We add search filter
            print '<tr class="liste_titre_filter">';
            print '<td class="liste_titre"><input type="text" class="flat maxwidth25" name="search_lineid" value="' . dol_escape_htmltag($search_lineid) . '"></td>';
            print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_invoice" value="' . dol_escape_htmltag($search_invoice) . '"></td>';
            //print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_ref_supplier" value="'.dol_escape_htmltag($search_ref_supplier).'"></td>';
            print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_label" value="' . dol_escape_htmltag($search_label) . '"></td>';
            print '<td class="liste_titre center">';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_date_start ? $search_date_start : -1, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
            print '</div>';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_date_end ? $search_date_end : -1, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
            print '</div>';
            print '</td>';
            print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_ref" value="' . dol_escape_htmltag($search_ref) . '"></td>';
            print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="search_desc" value="' . dol_escape_htmltag($search_desc) . '"></td>';
            print '<td class="liste_titre right"><input type="text" class="flat maxwidth50 right" name="search_amount" value="' . dol_escape_htmltag($search_amount) . '"></td>';
            print '<td class="liste_titre right"><input type="text" class="flat maxwidth50 right" name="search_vat" placeholder="%" size="1" value="' . dol_escape_htmltag($search_vat) . '"></td>';
            print '<td class="liste_titre"><input type="text" class="flat maxwidth75imp" name="search_societe" value="' . dol_escape_htmltag($search_societe) . '"></td>';
            print '<td class="liste_titre">';
            print $form->select_country($search_country, 'search_country', '', 0, 'maxwidth100', 'code2', 1, 0, 1);
            //print '<input type="text" class="flat maxwidth50" name="search_country" value="' . dol_escape_htmltag($search_country) . '">';
            print '</td>';
            print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_tvaintra" value="' . dol_escape_htmltag($search_tvaintra) . '"></td>';
            print '<td class="liste_titre"></td>';
            print '<td class="liste_titre"></td>';
            print '<td class="center liste_titre">';
            $searchpicto = $form->showFilterButtons();
            print $searchpicto;
            print '</td>';
            print "</tr>\n";

            print '<tr class="liste_titre">';
            print_liste_field_titre("LineId", $_SERVER['PHP_SELF'], "l.rowid", "", $param, '', $sortfield, $sortorder);
            print_liste_field_titre("Invoice", $_SERVER['PHP_SELF'], "f.ref", "", $param, '', $sortfield, $sortorder);
            //print_liste_field_titre("RefSupplier", $_SERVER['PHP_SELF'], "f.ref_supplier", "", $param, '', $sortfield, $sortorder);
            print_liste_field_titre("InvoiceLabel", $_SERVER['PHP_SELF'], "f.libelle", "", $param, '', $sortfield, $sortorder);
            print_liste_field_titre("Date", $_SERVER['PHP_SELF'], "f.datef, f.ref, l.rowid", "", $param, '', $sortfield, $sortorder, 'center ');
            print_liste_field_titre("ProductRef", $_SERVER['PHP_SELF'], "p.ref", "", $param, '', $sortfield, $sortorder);
            //print_liste_field_titre("ProductLabel", $_SERVER['PHP_SELF'], "p.label", "", $param, '', $sortfield, $sortorder);
            print_liste_field_titre("ProductDescription", $_SERVER['PHP_SELF'], "l.description", "", $param, '', $sortfield, $sortorder);
            print_liste_field_titre("Amount", $_SERVER['PHP_SELF'], "l.total_ht", "", $param, '', $sortfield, $sortorder, 'right maxwidth50 ');
            print_liste_field_titre("VATRate", $_SERVER['PHP_SELF'], "l.tva_tx", "", $param, '', $sortfield, $sortorder, 'right ', '', 1);
            print_liste_field_titre("ThirdParty", $_SERVER['PHP_SELF'], "s.nom", "", $param, '', $sortfield, $sortorder);
            print_liste_field_titre("Country", $_SERVER['PHP_SELF'], "co.label", "", $param, '', $sortfield, $sortorder);
            print_liste_field_titre("VATIntraShort", $_SERVER['PHP_SELF'], "s.tva_intra", "", $param, '', $sortfield, $sortorder);
            print_liste_field_titre("DataUsedToSuggestAccount", '', '', '', '', '', '', '', 'nowraponall ');
            print_liste_field_titre("AccountAccountingSuggest", '', '', '', '', '', '', '', 'center ');
            $checkpicto = '';
            if ($massactionbutton) {
                $checkpicto = $form->showCheckAddButtons('checkforselect', 1);
            }
            print_liste_field_titre($checkpicto, '', '', '', '', '', '', '', 'center ');
            print "</tr>\n";

            $thirdpartystatic = new Societe($db);
            $facturefourn_static = new FactureFournisseur($db);
            $facturefourn_static_det = new SupplierInvoiceLine($db);
            $product_static = new Product($db);


            $accountingaccount_codetotid_cache = array();

            while ($i < min($num_lines, $limit)) {
                $objp = $db->fetch_object($result);

                // product_type: 0 = service, 1 = product
                // if product does not exist we use the value of product_type provided in facturedet to define if this is a product or service
                // issue : if we change product_type value in product DB it should differ from the value stored in facturedet DB !
                $code_buy_l = '';
                $code_buy_p = '';
                $code_buy_t = '';

                $thirdpartystatic->id = $objp->socid;
                $thirdpartystatic->name = $objp->name;
                $thirdpartystatic->client = $objp->client;
                $thirdpartystatic->fournisseur = $objp->fournisseur;
                $thirdpartystatic->code_client = $objp->code_client;
                $thirdpartystatic->code_compta_client = $objp->code_compta_client;
                $thirdpartystatic->code_fournisseur = $objp->code_fournisseur;
                $thirdpartystatic->code_compta_fournisseur = $objp->code_compta_fournisseur;
                $thirdpartystatic->email = $objp->email;
                $thirdpartystatic->country_code = $objp->country_code;
                $thirdpartystatic->tva_intra = $objp->tva_intra;
                $thirdpartystatic->code_compta_product = $objp->company_code_buy;       // The accounting account for product stored on thirdparty object (for level3 suggestion)

                $product_static->ref = $objp->product_ref;
                $product_static->id = $objp->product_id;
                $product_static->type = $objp->type;
                $product_static->label = $objp->product_label;
                $product_static->status = $objp->status;
                $product_static->status_buy = $objp->status_buy;
                $product_static->accountancy_code_sell = $objp->code_sell;
                $product_static->accountancy_code_sell_intra = $objp->code_sell_intra;
                $product_static->accountancy_code_sell_export = $objp->code_sell_export;
                $product_static->accountancy_code_buy = $objp->code_buy;
                $product_static->accountancy_code_buy_intra = $objp->code_buy_intra;
                $product_static->accountancy_code_buy_export = $objp->code_buy_export;
                $product_static->tva_tx = $objp->tva_tx_prod;

                $facturefourn_static->ref = $objp->ref;
                $facturefourn_static->id = $objp->facid;
                $facturefourn_static->type = $objp->ftype;
                $facturefourn_static->ref_supplier = $objp->ref_supplier;
                $facturefourn_static->label = $objp->invoice_label;
                $facturefourn_static->date = $db->jdate($objp->datef);
                $facturefourn_static->fk_facture_source = $objp->fk_facture_source;

                $facturefourn_static_det->id = $objp->rowid;
                $facturefourn_static_det->total_ht = $objp->total_ht;
                $facturefourn_static_det->tva_tx = $objp->tva_tx_line;
                $facturefourn_static_det->vat_src_code = $objp->vat_src_code;
                $facturefourn_static_det->product_type = $objp->type_l;
                $facturefourn_static_det->desc = $objp->description;

                $accountingAccountArray = array(
                    'dom' => $objp->aarowid,
                    'intra' => $objp->aarowid_intra,
                    'export' => $objp->aarowid_export,
                    'thirdparty' => $objp->aarowid_thirdparty);

                $code_buy_p_notset = '';
                $code_buy_t_notset = '';

                $suggestedid = 0;

                $return = $accountingAccount->getAccountingCodeToBind($mysoc, $thirdpartystatic, $product_static, $facturefourn_static, $facturefourn_static_det, $accountingAccountArray, 'supplier');
                if (!is_array($return) && $return < 0) {
                    setEventMessage($accountingAccount->error, 'errors');
                } else {
                    $suggestedid = $return['suggestedid'];
                    $suggestedaccountingaccountfor = $return['suggestedaccountingaccountfor'];
                    $suggestedaccountingaccountbydefaultfor = $return['suggestedaccountingaccountbydefaultfor'];
                    $code_buy_l = $return['code_l'];
                    $code_buy_p = $return['code_p'];
                    $code_buy_t = $return['code_t'];
                }
                //var_dump($return);

                if (!empty($code_buy_p)) {
                    // Value was defined previously
                } else {
                    $code_buy_p_notset = 'color:orange';
                }
                if (empty($code_buy_l) && empty($code_buy_p)) {
                    $code_buy_p_notset = 'color:red';
                }
                /*if ($suggestedaccountingaccountfor == 'eecwithoutvatnumber' && empty($code_sell_p_notset)) {
                    $code_sell_p_notset = 'color:orange';
                }*/

                // $code_buy_l is now default code of product/service
                // $code_buy_p is now code of product/service
                // $code_buy_t is now code of thirdparty
                //var_dump($code_buy_l.' - '.$code_buy_p.' - '.$code_buy_t.' -> '.$suggestedid.' ('.$suggestedaccountingaccountbydefaultfor.' '.$suggestedaccountingaccountfor.')');

                print '<tr class="oddeven">';

                // Line id
                print '<td>' . $facturefourn_static_det->id . '</td>';

                // Ref Invoice
                print '<td class="nowraponall">' . $facturefourn_static->getNomUrl(1);
                if ($objp->ref_supplier) {
                    print '<br><span class="opacitymedium small">' . dol_escape_htmltag($objp->ref_supplier) . '</span>';
                }
                print '</td>';

                // Ref supplier invoice
                /*print '<td class="tdoverflowmax100" title="'.dol_escape_htmltag($objp->ref_supplier).'">';
                print $objp->ref_supplier;
                print '</td>';*/

                // Supplier invoice label
                print '<td class="tdoverflowmax100 small" title="' . dol_escape_htmltag($objp->invoice_label) . '">';
                print dol_escape_htmltag($objp->invoice_label);
                print '</td>';

                // Date
                print '<td class="center">' . dol_print_date($facturefourn_static->date, 'day') . '</td>';

                // Ref Product
                print '<td class="tdoverflowmax100">';
                if ($product_static->id > 0) {
                    print $product_static->getNomUrl(1);
                }
                if ($product_static->label) {
                    print '<br><span class="opacitymedium small">' . dol_escape_htmltag($product_static->label) . '</span>';
                }
                print '</td>';

                // Description of line
                print '<td class="tdoverflowonsmartphone small">';
                $text = dolGetFirstLineOfText(dol_string_nohtmltag($facturefourn_static_det->desc, 1));
                $trunclength = getDolGlobalInt('ACCOUNTING_LENGTH_DESCRIPTION', 32);
                print $form->textwithtooltip(dol_trunc($text, $trunclength), $facturefourn_static_det->desc);
                print '</td>';

                print '<td class="right nowraponall amount">';
                print price($objp->total_ht);
                print '</td>';

                // Vat rate
                $code_vat_differ = '';
                //if ($objp->vat_tx_l != $objp->vat_tx_p && price2num($objp->vat_tx_p) && price2num($objp->vat_tx_l)) { // Note: having a vat rate of 0 is often the normal case when sells is intra b2b or to export
                //  $code_vat_differ = 'warning bold';
                //}
                print '<td class="right' . ($code_vat_differ ? ' ' . $code_vat_differ : '') . '">';
                print vatrate($facturefourn_static_det->tva_tx . ($facturefourn_static_det->vat_src_code ? ' (' . $facturefourn_static_det->vat_src_code . ')' : ''), false, 0, 0, 1);
                print '</td>';

                // Thirdparty
                print '<td class="tdoverflowmax100">' . $thirdpartystatic->getNomUrl(1, 'supplier') . '</td>';

                // Country
                $labelcountry = ($objp->country_code && ($langs->trans("Country" . $objp->country_code) != "Country" . $objp->country_code)) ? $langs->trans("Country" . $objp->country_code) : $objp->country_label;
                print '<td class="tdoverflowmax100" title="' . dol_escape_htmltag($labelcountry) . '">';
                print dol_escape_htmltag($labelcountry);
                print '</td>';

                // VAT Num
                print '<td class="tdoverflowmax80" title="' . dol_escape_htmltag($objp->tva_intra) . '">' . dol_escape_htmltag($objp->tva_intra) . '</td>';

                // Found accounts
                print '<td class="small">';
                $s = '1. ' . (($facturefourn_static_det->product_type == 1) ? $langs->trans("DefaultForService") : $langs->trans("DefaultForProduct")) . ': ';
                $shelp = '';
                $ttype = 'help';
                if ($suggestedaccountingaccountbydefaultfor == 'eec') {
                    $shelp .= $langs->trans("SaleEEC");
                } elseif ($suggestedaccountingaccountbydefaultfor == 'eecwithvat') {
                    $shelp = $langs->trans("SaleEECWithVAT");
                } elseif ($suggestedaccountingaccountbydefaultfor == 'eecwithoutvatnumber') {
                    $shelp = $langs->trans("SaleEECWithoutVATNumber");
                    $ttype = 'warning';
                } elseif ($suggestedaccountingaccountbydefaultfor == 'export') {
                    $shelp .= $langs->trans("SaleExport");
                }
                $s .= ($code_buy_l > 0 ? length_accountg($code_buy_l) : '<span style="' . $code_buy_p_notset . '">' . $langs->trans("NotDefined") . '</span>');
                print $form->textwithpicto($s, $shelp, 1, $ttype, '', 0, 2, '', 1);
                if ($product_static->id > 0) {
                    print '<br>';
                    $s = '2. ' . (($facturefourn_static_det->product_type == 1) ? $langs->trans("ThisService") : $langs->trans("ThisProduct")) . ': ';
                    $shelp = '';
                    $ttype = 'help';
                    if ($suggestedaccountingaccountfor == 'eec') {
                        $shelp = $langs->trans("SaleEEC");
                    } elseif ($suggestedaccountingaccountfor == 'eecwithvat') {
                        $shelp = $langs->trans("SaleEECWithVAT");
                    } elseif ($suggestedaccountingaccountfor == 'eecwithoutvatnumber') {
                        $shelp = $langs->trans("SaleEECWithoutVATNumber");
                        $ttype = 'warning';
                    } elseif ($suggestedaccountingaccountfor == 'export') {
                        $shelp = $langs->trans("SaleExport");
                    }
                    $s .= (empty($code_buy_p) ? '<span style="' . $code_buy_p_notset . '">' . $langs->trans("NotDefined") . '</span>' : length_accountg($code_buy_p));
                    print $form->textwithpicto($s, $shelp, 1, $ttype, '', 0, 2, '', 1);
                } else {
                    print '<br>';
                    $s = '2. ' . (($objp->type_l == 1) ? $langs->trans("ThisService") : $langs->trans("ThisProduct")) . ': ';
                    $shelp = '';
                    $s .= $langs->trans("NotDefined");
                    print $form->textwithpicto($s, $shelp, 1, 'help', '', 0, 2, '', 1);
                }
                if (getDolGlobalString('ACCOUNTANCY_USE_PRODUCT_ACCOUNT_ON_THIRDPARTY')) {
                    print '<br>';
                    $s = '3. ' . (($facturefourn_static_det->product_type == 1) ? $langs->trans("ServiceForThisThirdparty") : $langs->trans("ProductForThisThirdparty")) . ': ';
                    $shelp = '';
                    $s .= ($code_buy_t > 0 ? length_accountg($code_buy_t) : '<span style="' . $code_buy_t_notset . '">' . $langs->trans("NotDefined") . '</span>');
                    print $form->textwithpicto($s, $shelp, 1, 'help', '', 0, 2, '', 1);
                }
                print '</td>';

                // Suggested accounting account
                print '<td>';
                print $formaccounting->select_account(($default_account > 0 && $confirm === 'yes' && in_array($objp->rowid . "_" . $i, $toselect)) ? $default_account : $suggestedid, 'codeventil' . $facturefourn_static_det->id, 1, array(), 0, 0, 'codeventil maxwidth150 maxwidthonsmartphone', 'cachewithshowemptyone');
                print '</td>';

                // Column with checkbox
                print '<td class="center">';
                $ischecked = 0;
                if (!empty($suggestedid) && $suggestedaccountingaccountfor != '' && $suggestedaccountingaccountfor != 'eecwithoutvatnumber') {
                    $ischecked = 1;
                }

                if (!empty($toselect)) {
                    $ischecked = 0;
                    if (in_array($objp->rowid . "_" . $i, $toselect)) {
                        $ischecked = 1;
                    }
                }

                print '<input type="checkbox" class="flat checkforselect checkforselect' . $facturefourn_static_det->id . '" name="toselect[]" value="' . $facturefourn_static_det->id . "_" . $i . '"' . ($ischecked ? " checked" : "") . '/>';
                print '</td>';

                print '</tr>';
                $i++;
            }
            if ($num_lines == 0) {
                print '<tr><td colspan="14"><span class="opacitymedium">' . $langs->trans("NoRecordFound") . '</span></td></tr>';
            }

            print '</table>';
            print "</div>";

            print '</form>';
        } else {
            print $db->error();
        }
        if ($db->type == 'mysqli') {
            $db->query("SET SQL_BIG_SELECTS=0"); // Enable MAX_JOIN_SIZE limitation
        }

// Add code to auto check the box when we select an account
        print '<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery(".codeventil").change(function() {
		var s=$(this).attr("id").replace("codeventil", "")
		console.log(s+" "+$(this).val());
		if ($(this).val() == -1) jQuery(".checkforselect"+s).prop("checked", false);
		else jQuery(".checkforselect"+s).prop("checked", true);
	});
});
</script>';

// End of page
        llxFooter();
        $db->close();
    }
}
