<?php

/* Copyright (C) 2007-2010  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2007-2010  Jean Heimburger         <jean@tiaris.info>
 * Copyright (C) 2011       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2012       Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2013       Christophe Battarel     <christophe.battarel@altairis.fr>
 * Copyright (C) 2013-2023  Alexandre Spangaro      <aspangaro@easya.solutions>
 * Copyright (C) 2013-2016  Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2016  Olivier Geffroy         <jeff@jeffinfo.com>
 * Copyright (C) 2014       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2018-2021  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2018       Eric Seigne             <eric.seigne@cap-rel.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
require_once DOL_DOCUMENT_ROOT . '/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT . '/adherents/class/subscription.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/paymentvarious.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT . '/don/class/don.class.php';
require_once DOL_DOCUMENT_ROOT . '/don/class/paymentdonation.class.php';
require_once DOL_DOCUMENT_ROOT . '/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT . '/expensereport/class/paymentexpensereport.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT . '/loan/class/loan.class.php';
require_once DOL_DOCUMENT_ROOT . '/loan/class/paymentloan.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/client.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/salaries/class/paymentsalary.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

use Account;
use AccountingAccount;
use AccountingJournal;
use AccountLine;
use ChargeSociales;
use Client;
use DoliCore\Base\DolibarrController;
use Don;
use ExpenseReport;
use Facture;
use FactureFournisseur;
use Form;
use Fournisseur;
use Loan;
use Paiement;
use PaiementFourn;
use PaymentDonation;
use PaymentExpenseReport;
use PaymentLoan;
use PaymentSalary;
use PaymentVarious;
use Salary;
use Societe;
use DoliModules\Adherent\Model\Subscription;
use Tva;
use User;

class AccountingJournalController extends DolibarrController
{

    public function index()
    {
        return $this->bankjournal();
    }

    /**
     *  \file       htdocs/accountancy/journal/bankjournal.php
     *  \ingroup    Accountancy (Double entries)
     *  \brief      Page with bank journal
     */
    public function bankjournal()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;
        global $mysoc;

// Load translation files required by the page
        $langs->loadLangs(array("companies", "other", "compta", "banks", "bills", "donations", "loan", "accountancy", "trips", "salaries", "hrm", "members"));

// Multi journal
        $id_journal = GETPOSTINT('id_journal');

        $date_startmonth = GETPOSTINT('date_startmonth');
        $date_startday = GETPOSTINT('date_startday');
        $date_startyear = GETPOSTINT('date_startyear');
        $date_endmonth = GETPOSTINT('date_endmonth');
        $date_endday = GETPOSTINT('date_endday');
        $date_endyear = GETPOSTINT('date_endyear');
        $in_bookkeeping = GETPOST('in_bookkeeping', 'aZ09');
        if ($in_bookkeeping == '') {
            $in_bookkeeping = 'notyet';
        }

        $now = dol_now();

        $action = GETPOST('action', 'aZ09');

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

        $error = 0;

        $date_start = dol_mktime(0, 0, 0, $date_startmonth, $date_startday, $date_startyear);
        $date_end = dol_mktime(23, 59, 59, $date_endmonth, $date_endday, $date_endyear);

        if (empty($date_startmonth)) {
            // Period by default on transfer
            $dates = getDefaultDatesForTransfer();
            $date_start = $dates['date_start'];
            $pastmonthyear = $dates['pastmonthyear'];
            $pastmonth = $dates['pastmonth'];
        }
        if (empty($date_endmonth)) {
            // Period by default on transfer
            $dates = getDefaultDatesForTransfer();
            $date_end = $dates['date_end'];
            $pastmonthyear = $dates['pastmonthyear'];
            $pastmonth = $dates['pastmonth'];
        }

        if (!GETPOSTISSET('date_startmonth') && (empty($date_start) || empty($date_end))) { // We define date_start and date_end, only if we did not submit the form
            $date_start = dol_get_first_day($pastmonthyear, $pastmonth, false);
            $date_end = dol_get_last_day($pastmonthyear, $pastmonth, false);
        }

        $sql  = "SELECT b.rowid, b.dateo as do, b.datev as dv, b.amount, b.amount_main_currency, b.label, b.rappro, b.num_releve, b.num_chq, b.fk_type, b.fk_account,";
        $sql .= " ba.courant, ba.ref as baref, ba.account_number, ba.fk_accountancy_journal,";
        $sql .= " soc.rowid as socid, soc.nom as name, soc.email as email, bu1.type as typeop_company,";
        if (getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
            $sql .= " spe.accountancy_code_customer as code_compta,";
            $sql .= " spe.accountancy_code_supplier as code_compta_fournisseur,";
        } else {
            $sql .= " soc.code_compta,";
            $sql .= " soc.code_compta_fournisseur,";
        }
        $sql .= " u.accountancy_code, u.rowid as userid, u.lastname as lastname, u.firstname as firstname, u.email as useremail, u.statut as userstatus,";
        $sql .= " bu2.type as typeop_user,";
        $sql .= " bu3.type as typeop_payment, bu4.type as typeop_payment_supplier";
        $sql .= " FROM " . MAIN_DB_PREFIX . "bank as b";
        $sql .= " JOIN " . MAIN_DB_PREFIX . "bank_account as ba on b.fk_account=ba.rowid";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "bank_url as bu1 ON bu1.fk_bank = b.rowid AND bu1.type='company'";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "bank_url as bu2 ON bu2.fk_bank = b.rowid AND bu2.type='user'";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "bank_url as bu3 ON bu3.fk_bank = b.rowid AND bu3.type='payment'";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "bank_url as bu4 ON bu4.fk_bank = b.rowid AND bu4.type='payment_supplier'";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as soc on bu1.url_id=soc.rowid";
        if (getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_perentity as spe ON spe.fk_soc = soc.rowid AND spe.entity = " . ((int) $conf->entity);
        }
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as u on bu2.url_id=u.rowid";
        $sql .= " WHERE ba.fk_accountancy_journal=" . ((int) $id_journal);
        $sql .= ' AND b.amount <> 0 AND ba.entity IN (' . getEntity('bank_account', 0) . ')'; // We don't share object for accountancy
        if ($date_start && $date_end) {
            $sql .= " AND b.dateo >= '" . $db->idate($date_start) . "' AND b.dateo <= '" . $db->idate($date_end) . "'";
        }
// Define begin binding date
        if (getDolGlobalInt('ACCOUNTING_DATE_START_BINDING')) {
            $sql .= " AND b.dateo >= '" . $db->idate(getDolGlobalInt('ACCOUNTING_DATE_START_BINDING')) . "'";
        }
// Already in bookkeeping or not
        if ($in_bookkeeping == 'already') {
            $sql .= " AND (b.rowid IN (SELECT fk_doc FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping as ab  WHERE ab.doc_type='bank') )";
        }
        if ($in_bookkeeping == 'notyet') {
            $sql .= " AND (b.rowid NOT IN (SELECT fk_doc FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping as ab  WHERE ab.doc_type='bank') )";
        }
        $sql .= " ORDER BY b.datev";
//print $sql;

        $object = new Account($db);
        $paymentstatic = new Paiement($db);
        $paymentsupplierstatic = new PaiementFourn($db);
        $societestatic = new Societe($db);
        $userstatic = new User($db);
        $bankaccountstatic = new Account($db);
        $chargestatic = new ChargeSociales($db);
        $paymentdonstatic = new PaymentDonation($db);
        $paymentvatstatic = new Tva($db);
        $paymentsalstatic = new PaymentSalary($db);
        $paymentexpensereportstatic = new PaymentExpenseReport($db);
        $paymentvariousstatic = new PaymentVarious($db);
        $paymentloanstatic = new PaymentLoan($db);
        $accountLinestatic = new AccountLine($db);
        $paymentsubscriptionstatic = new Subscription($db);

        $tmppayment = new Paiement($db);
        $tmpinvoice = new Facture($db);

        $accountingaccount = new AccountingAccount($db);

// Get code of finance journal
        $accountingjournalstatic = new AccountingJournal($db);
        $accountingjournalstatic->fetch($id_journal);
        $journal = $accountingjournalstatic->code;
        $journal_label = $accountingjournalstatic->label;

        $tabcompany = array();
        $tabuser = array();
        $tabpay = array();
        $tabbq = array();
        $tabtp = array();
        $tabtype = array();
        $tabmoreinfo = array();

        '
@phan-var-force array<array{id:mixed,name:mixed,code_compta:string,email:string}> $tabcompany
@phan-var-force array<array{id:int,name:string,lastname:string,firstname:string,email:string,accountancy_code:string,status:int> $tabuser
@phan-var-force array<int,array{date:string,type_payment:string,ref:string,fk_bank:int,ban_account_ref:string,fk_bank_account:int,lib:string,type:string}> $tabpay
@phan-var-force array<array{lib:string,date?:int|string,type_payment?:string,ref?:string,fk_bank?:int,ban_account_ref?:string,fk_bank_account?:int,type?:string,bank_account_ref?:string,paymentid?:int,paymentsupplierid?:int,soclib?:string,paymentscid?:int,paymentdonationid?:int,paymentsubscriptionid?:int,paymentvatid?:int,paymentsalid?:int,paymentexpensereport?:int,paymentvariousid?:int,account_various?:string,paymentloanid?:int}> $tabtp
';

//print $sql;
        dol_syslog("accountancy/journal/bankjournal.php", LOG_DEBUG);
        $result = $db->query($sql);
        if ($result) {
            $num = $db->num_rows($result);
            //print $sql;

            // Variables
            $account_supplier = getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER', 'NotDefined'); // NotDefined is a reserved word
            $account_customer = getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER', 'NotDefined'); // NotDefined is a reserved word
            $account_employee = getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT', 'NotDefined'); // NotDefined is a reserved word
            $account_pay_vat = getDolGlobalString('ACCOUNTING_VAT_PAY_ACCOUNT', 'NotDefined'); // NotDefined is a reserved word
            $account_pay_donation = getDolGlobalString('DONATION_ACCOUNTINGACCOUNT', 'NotDefined'); // NotDefined is a reserved word
            $account_pay_subscription = getDolGlobalString('ADHERENT_SUBSCRIPTION_ACCOUNTINGACCOUNT', 'NotDefined'); // NotDefined is a reserved word
            $account_transfer = getDolGlobalString('ACCOUNTING_ACCOUNT_TRANSFER_CASH', 'NotDefined'); // NotDefined is a reserved word

            // Loop on each line into llx_bank table. For each line, we should get:
            // one line tabpay = line into bank
            // one line for bank record = tabbq
            // one line for thirdparty record = tabtp
            $i = 0;
            while ($i < $num) {
                $obj = $db->fetch_object($result);

                $lineisapurchase = -1;
                $lineisasale = -1;
                // Old method to detect if it's a sale or purchase
                if ($obj->label == '(SupplierInvoicePayment)' || $obj->label == '(SupplierInvoicePaymentBack)') {
                    $lineisapurchase = 1;
                }
                if ($obj->label == '(CustomerInvoicePayment)' || $obj->label == '(CustomerInvoicePaymentBack)') {
                    $lineisasale = 1;
                }
                // Try a more reliable method to detect if record is a supplier payment or a customer payment
                if ($lineisapurchase < 0) {
                    if ($obj->typeop_payment_supplier == 'payment_supplier') {
                        $lineisapurchase = 1;
                    }
                }
                if ($lineisasale < 0) {
                    if ($obj->typeop_payment == 'payment') {
                        $lineisasale = 1;
                    }
                }
                //var_dump($obj->type_payment); //var_dump($obj->type_payment_supplier);
                //var_dump($lineisapurchase); //var_dump($lineisasale);

                // Set accountancy code for bank
                $compta_bank = $obj->account_number;

                // Set accountancy code for thirdparty (example: '411CU...' or '411' if no subledger account defined on customer)
                $compta_soc = 'NotDefined';
                if ($lineisapurchase > 0) {
                    $compta_soc = (($obj->code_compta_fournisseur != "") ? $obj->code_compta_fournisseur : $account_supplier);
                }
                if ($lineisasale > 0) {
                    $compta_soc = (!empty($obj->code_compta) ? $obj->code_compta : $account_customer);
                }

                $tabcompany[$obj->rowid] = array(
                    'id' => $obj->socid,
                    'name' => $obj->name,
                    'code_compta' => $compta_soc,
                    'email' => $obj->email
                );

                // Set accountancy code for user
                // $obj->accountancy_code is the accountancy_code of table u=user but it is defined only if a link with type 'user' exists)
                $compta_user = (!empty($obj->accountancy_code) ? $obj->accountancy_code : '');

                $tabuser[$obj->rowid] = array(
                    'id' => $obj->userid,
                    'name' => dolGetFirstLastname($obj->firstname, $obj->lastname),
                    'lastname' => $obj->lastname,
                    'firstname' => $obj->firstname,
                    'email' => $obj->useremail,
                    'accountancy_code' => $compta_user,
                    'status' => $obj->userstatus
                );

                // Variable bookkeeping ($obj->rowid is Bank Id)
                $tabpay[$obj->rowid]["date"] = $db->jdate($obj->do);
                $tabpay[$obj->rowid]["type_payment"] = $obj->fk_type; // CHQ, VIR, LIQ, CB, ...
                $tabpay[$obj->rowid]["ref"] = $obj->label; // By default. Not unique. May be changed later
                $tabpay[$obj->rowid]["fk_bank"] = $obj->rowid;
                $tabpay[$obj->rowid]["bank_account_ref"] = $obj->baref;
                $tabpay[$obj->rowid]["fk_bank_account"] = $obj->fk_account;
                $reg = array();
                if (preg_match('/^\((.*)\)$/i', $obj->label, $reg)) {
                    $tabpay[$obj->rowid]["lib"] = $langs->trans($reg[1]);
                } else {
                    $tabpay[$obj->rowid]["lib"] = dol_trunc($obj->label, 60);
                }

                // Load of url links to the line into llx_bank (so load llx_bank_url)
                $links = $object->get_url($obj->rowid); // Get an array('url'=>, 'url_id'=>, 'label'=>, 'type'=> 'fk_bank'=> )

                // By default
                $tabpay[$obj->rowid]['type'] = 'unknown'; // Can be SOLD, miscellaneous entry, payment of patient, or any old record with no links in bank_url.
                $tabtype[$obj->rowid] = 'unknown';
                $tabmoreinfo[$obj->rowid] = array();

                $amounttouse = $obj->amount;
                if (!empty($obj->amount_main_currency)) {
                    // If $obj->amount_main_currency is set, it means that $obj->amount is not in same currency, we must use $obj->amount_main_currency
                    $amounttouse = $obj->amount_main_currency;
                }

                // get_url may return -1 which is not traversable
                if (is_array($links) && count($links) > 0) {
                    // Test if entry is for a social contribution, salary or expense report.
                    // In such a case, we will ignore the bank url line for user
                    $is_sc = false;
                    $is_salary = false;
                    $is_expensereport = false;
                    foreach ($links as $v) {
                        if ($v['type'] == 'sc') {
                            $is_sc = true;
                            break;
                        }
                        if ($v['type'] == 'payment_salary') {
                            $is_salary = true;
                            break;
                        }
                        if ($v['type'] == 'payment_expensereport') {
                            $is_expensereport = true;
                            break;
                        }
                    }
                    // Now loop on each link of record in bank (code similar to bankentries_list.php)
                    foreach ($links as $key => $val) {
                        if ($links[$key]['type'] == 'user' && !$is_sc && !$is_salary && !$is_expensereport) {
                            continue;
                        }
                        if (in_array($links[$key]['type'], array('sc', 'payment_sc', 'payment', 'payment_supplier', 'payment_vat', 'payment_expensereport', 'banktransfert', 'payment_donation', 'member', 'payment_loan', 'payment_salary', 'payment_various'))) {
                            // So we excluded 'company' and 'user' here. We want only payment lines

                            // We save tabtype for a future use, to remember what kind of payment it is
                            $tabpay[$obj->rowid]['type'] = $links[$key]['type'];
                            $tabtype[$obj->rowid] = $links[$key]['type'];
                            /* phpcs:disable -- Code does nothing at this moment -> commented
                            } elseif (in_array($links[$key]['type'], array('company', 'user'))) {
                                if ($tabpay[$obj->rowid]['type'] == 'unknown') {
                                    // We can guess here it is a bank record for a thirdparty company or a user.
                                    // But we won't be able to record somewhere else than into a waiting account, because there is no other journal to record the contreparty.
                                }
                            */ // phpcs::enable
                        }

                        // Special case to ask later to add more request to get information for old links without company link.
                        if ($links[$key]['type'] == 'withdraw') {
                            $tabmoreinfo[$obj->rowid]['withdraw'] = 1;
                        }

                        if ($links[$key]['type'] == 'payment') {
                            $paymentstatic->id = $links[$key]['url_id'];
                            $paymentstatic->ref = $links[$key]['url_id'];
                            $tabpay[$obj->rowid]["lib"] .= ' '.$paymentstatic->getNomUrl(2, '', ''); // TODO Do not include list of invoice in tooltip, the dol_string_nohtmltag is ko with this
                            $tabpay[$obj->rowid]["paymentid"] = $paymentstatic->id;
                        } elseif ($links[$key]['type'] == 'payment_supplier') {
                            $paymentsupplierstatic->id = $links[$key]['url_id'];
                            $paymentsupplierstatic->ref = $links[$key]['url_id'];
                            $tabpay[$obj->rowid]["lib"] .= ' '.$paymentsupplierstatic->getNomUrl(2);
                            $tabpay[$obj->rowid]["paymentsupplierid"] = $paymentsupplierstatic->id;
                        } elseif ($links[$key]['type'] == 'company') {
                            $societestatic->id = $links[$key]['url_id'];
                            $societestatic->name = $links[$key]['label'];
                            $societestatic->email = $tabcompany[$obj->rowid]['email'];
                            $tabpay[$obj->rowid]["soclib"] = $societestatic->getNomUrl(1, '', 30);
                            if ($compta_soc) {
                                if (empty($tabtp[$obj->rowid][$compta_soc])) {
                                    $tabtp[$obj->rowid][$compta_soc] = $amounttouse;
                                } else {
                                    $tabtp[$obj->rowid][$compta_soc] += $amounttouse;
                                }
                            }
                        } elseif ($links[$key]['type'] == 'user') {
                            $userstatic->id = $links[$key]['url_id'];
                            $userstatic->name = $links[$key]['label'];
                            $userstatic->email = $tabuser[$obj->rowid]['email'];
                            $userstatic->firstname = $tabuser[$obj->rowid]['firstname'];
                            $userstatic->lastname = $tabuser[$obj->rowid]['lastname'];
                            $userstatic->statut = $tabuser[$obj->rowid]['status'];
                            $userstatic->status = $tabuser[$obj->rowid]['status'];
                            $userstatic->accountancy_code = $tabuser[$obj->rowid]['accountancy_code'];
                            if ($userstatic->id > 0) {
                                $tabpay[$obj->rowid]["soclib"] = $userstatic->getNomUrl(1, 'accountancy', 0);
                            } else {
                                $tabpay[$obj->rowid]["soclib"] = '???'; // Should not happen, but happens with old data when id of user was not saved on expense report payment.
                            }
                            if ($compta_user) {
                                $tabtp[$obj->rowid][$compta_user] += $amounttouse;
                            }
                        } elseif ($links[$key]['type'] == 'sc') {
                            $chargestatic->id = $links[$key]['url_id'];
                            $chargestatic->ref = $links[$key]['url_id'];

                            $tabpay[$obj->rowid]["lib"] .= ' '.$chargestatic->getNomUrl(2);
                            $reg = array();
                            if (preg_match('/^\((.*)\)$/i', $links[$key]['label'], $reg)) {
                                if ($reg[1] == 'socialcontribution') {
                                    $reg[1] = 'SocialContribution';
                                }
                                $chargestatic->label = $langs->trans($reg[1]);
                            } else {
                                $chargestatic->label = $links[$key]['label'];
                            }
                            $chargestatic->ref = $chargestatic->label;
                            $tabpay[$obj->rowid]["soclib"] = $chargestatic->getNomUrl(1, 30);
                            $tabpay[$obj->rowid]["paymentscid"] = $chargestatic->id;

                            // Retrieve the accounting code of the social contribution of the payment from link of payment.
                            // Note: We have the social contribution id, it can be faster to get accounting code from social contribution id.
                            $sqlmid = "SELECT cchgsoc.accountancy_code";
                            $sqlmid .= " FROM ".MAIN_DB_PREFIX."c_chargesociales cchgsoc";
                            $sqlmid .= " INNER JOIN ".MAIN_DB_PREFIX."chargesociales as chgsoc ON chgsoc.fk_type = cchgsoc.id";
                            $sqlmid .= " INNER JOIN ".MAIN_DB_PREFIX."paiementcharge as paycharg ON paycharg.fk_charge = chgsoc.rowid";
                            $sqlmid .= " INNER JOIN ".MAIN_DB_PREFIX."bank_url as bkurl ON bkurl.url_id=paycharg.rowid AND bkurl.type = 'payment_sc'";
                            $sqlmid .= " WHERE bkurl.fk_bank = ".((int) $obj->rowid);

                            dol_syslog("accountancy/journal/bankjournal.php:: sqlmid=".$sqlmid, LOG_DEBUG);
                            $resultmid = $db->query($sqlmid);
                            if ($resultmid) {
                                $objmid = $db->fetch_object($resultmid);
                                $tabtp[$obj->rowid][$objmid->accountancy_code] += $amounttouse;
                            }
                        } elseif ($links[$key]['type'] == 'payment_donation') {
                            $paymentdonstatic->id = $links[$key]['url_id'];
                            $paymentdonstatic->ref = $links[$key]['url_id'];
                            $paymentdonstatic->fk_donation = $links[$key]['url_id'];
                            $tabpay[$obj->rowid]["lib"] .= ' '.$paymentdonstatic->getNomUrl(2);
                            $tabpay[$obj->rowid]["paymentdonationid"] = $paymentdonstatic->id;
                            $tabtp[$obj->rowid][$account_pay_donation] += $amounttouse;
                        } elseif ($links[$key]['type'] == 'member') {
                            $paymentsubscriptionstatic->id = $links[$key]['url_id'];
                            $paymentsubscriptionstatic->ref = $links[$key]['url_id'];
                            $paymentsubscriptionstatic->label = $links[$key]['label'];
                            $tabpay[$obj->rowid]["lib"] .= ' '.$paymentsubscriptionstatic->getNomUrl(2);
                            $tabpay[$obj->rowid]["paymentsubscriptionid"] = $paymentsubscriptionstatic->id;
                            $paymentsubscriptionstatic->fetch($paymentsubscriptionstatic->id);
                            $tabtp[$obj->rowid][$account_pay_subscription] += $amounttouse;
                        } elseif ($links[$key]['type'] == 'payment_vat') {				// Payment VAT
                            $paymentvatstatic->id = $links[$key]['url_id'];
                            $paymentvatstatic->ref = $links[$key]['url_id'];
                            $paymentvatstatic->label = $links[$key]['label'];
                            $tabpay[$obj->rowid]["lib"] .= ' '.$paymentvatstatic->getNomUrl(2);
                            $tabpay[$obj->rowid]["paymentvatid"] = $paymentvatstatic->id;
                            $tabtp[$obj->rowid][$account_pay_vat] += $amounttouse;
                        } elseif ($links[$key]['type'] == 'payment_salary') {
                            $paymentsalstatic->id = $links[$key]['url_id'];
                            $paymentsalstatic->ref = $links[$key]['url_id'];
                            $paymentsalstatic->label = $links[$key]['label'];
                            $tabpay[$obj->rowid]["lib"] .= ' '.$paymentsalstatic->getNomUrl(2);
                            $tabpay[$obj->rowid]["paymentsalid"] = $paymentsalstatic->id;

                            // This part of code is no more required. it is here to solve case where a link were missing (with v14.0.0) and keep writing in accountancy complete.
                            // Note: A better way to fix this is to delete payment of salary and recreate it, or to fix the bookkeeping table manually after.
                            if (getDolGlobalString('ACCOUNTANCY_AUTOFIX_MISSING_LINK_TO_USER_ON_SALARY_BANK_PAYMENT')) {
                                $tmpsalary = new Salary($db);
                                $tmpsalary->fetch($paymentsalstatic->id);
                                $tmpsalary->fetch_user($tmpsalary->fk_user);

                                $userstatic->id = $tmpsalary->user->id;
                                $userstatic->name = $tmpsalary->user->name;
                                $userstatic->email = $tmpsalary->user->email;
                                $userstatic->firstname = $tmpsalary->user->firstname;
                                $userstatic->lastname = $tmpsalary->user->lastname;
                                $userstatic->statut = $tmpsalary->user->status;
                                $userstatic->accountancy_code = $tmpsalary->user->accountancy_code;

                                if ($userstatic->id > 0) {
                                    $tabpay[$obj->rowid]["soclib"] = $userstatic->getNomUrl(1, 'accountancy', 0);
                                } else {
                                    $tabpay[$obj->rowid]["soclib"] = '???'; // Should not happen
                                }

                                if (empty($obj->typeop_user)) {	// Add test to avoid to add amount twice if a link already exists also on user.
                                    $compta_user = $userstatic->accountancy_code;
                                    if ($compta_user) {
                                        $tabtp[$obj->rowid][$compta_user] += $amounttouse;
                                        $tabuser[$obj->rowid] = array(
                                            'id' => $userstatic->id,
                                            'name' => dolGetFirstLastname($userstatic->firstname, $userstatic->lastname),
                                            'lastname' => $userstatic->lastname,
                                            'firstname' => $userstatic->firstname,
                                            'email' => $userstatic->email,
                                            'accountancy_code' => $compta_user,
                                            'status' => $userstatic->status
                                        );
                                    }
                                }
                            }
                        } elseif ($links[$key]['type'] == 'payment_expensereport') {
                            $paymentexpensereportstatic->id = $links[$key]['url_id'];
                            $tabpay[$obj->rowid]["lib"] .= $paymentexpensereportstatic->getNomUrl(2);
                            $tabpay[$obj->rowid]["paymentexpensereport"] = $paymentexpensereportstatic->id;
                        } elseif ($links[$key]['type'] == 'payment_various') {
                            $paymentvariousstatic->id = $links[$key]['url_id'];
                            $paymentvariousstatic->ref = $links[$key]['url_id'];
                            $paymentvariousstatic->label = $links[$key]['label'];
                            $tabpay[$obj->rowid]["lib"] .= ' '.$paymentvariousstatic->getNomUrl(2);
                            $tabpay[$obj->rowid]["paymentvariousid"] = $paymentvariousstatic->id;
                            $paymentvariousstatic->fetch($paymentvariousstatic->id);
                            $account_various = (!empty($paymentvariousstatic->accountancy_code) ? $paymentvariousstatic->accountancy_code : 'NotDefined'); // NotDefined is a reserved word
                            $account_subledger = (!empty($paymentvariousstatic->subledger_account) ? $paymentvariousstatic->subledger_account : ''); // NotDefined is a reserved word
                            $tabpay[$obj->rowid]["account_various"] = $account_various;
                            $tabtp[$obj->rowid][$account_subledger] += $amounttouse;
                        } elseif ($links[$key]['type'] == 'payment_loan') {
                            $paymentloanstatic->id = $links[$key]['url_id'];
                            $paymentloanstatic->ref = $links[$key]['url_id'];
                            $paymentloanstatic->fk_loan = $links[$key]['url_id'];
                            $tabpay[$obj->rowid]["lib"] .= ' '.$paymentloanstatic->getNomUrl(2);
                            $tabpay[$obj->rowid]["paymentloanid"] = $paymentloanstatic->id;
                            //$tabtp[$obj->rowid][$account_pay_loan] += $amounttouse;
                            $sqlmid = 'SELECT pl.amount_capital, pl.amount_insurance, pl.amount_interest, l.accountancy_account_capital, l.accountancy_account_insurance, l.accountancy_account_interest';
                            $sqlmid .= ' FROM '.MAIN_DB_PREFIX.'payment_loan as pl, '.MAIN_DB_PREFIX.'loan as l';
                            $sqlmid .= ' WHERE l.rowid = pl.fk_loan AND pl.fk_bank = '.((int) $obj->rowid);

                            dol_syslog("accountancy/journal/bankjournal.php:: sqlmid=".$sqlmid, LOG_DEBUG);
                            $resultmid = $db->query($sqlmid);
                            if ($resultmid) {
                                $objmid = $db->fetch_object($resultmid);
                                $tabtp[$obj->rowid][$objmid->accountancy_account_capital] -= $objmid->amount_capital;
                                $tabtp[$obj->rowid][$objmid->accountancy_account_insurance] -= $objmid->amount_insurance;
                                $tabtp[$obj->rowid][$objmid->accountancy_account_interest] -= $objmid->amount_interest;
                            }
                        } elseif ($links[$key]['type'] == 'banktransfert') {
                            $accountLinestatic->fetch($links[$key]['url_id']);
                            $tabpay[$obj->rowid]["lib"] .= ' '.$langs->trans("BankTransfer").'- '.$accountLinestatic ->getNomUrl(1);
                            $tabtp[$obj->rowid][$account_transfer] += $amounttouse;
                            $bankaccountstatic->fetch($tabpay[$obj->rowid]['fk_bank_account']);
                            $tabpay[$obj->rowid]["soclib"] = $bankaccountstatic->getNomUrl(2);
                        }
                    }
                }

                if (empty($tabbq[$obj->rowid][$compta_bank])) {
                    $tabbq[$obj->rowid][$compta_bank] = $amounttouse;
                } else {
                    $tabbq[$obj->rowid][$compta_bank] += $amounttouse;
                }

                // If no links were found to know the amount on thirdparty, we try to guess it.
                // This may happens on bank entries without the links lines to 'company'.
                if (empty($tabtp[$obj->rowid]) && !empty($tabmoreinfo[$obj->rowid]['withdraw'])) {	// If we don't find 'company' link because it is an old 'withdraw' record
                    foreach ($links as $key => $val) {
                        if ($links[$key]['type'] == 'payment') {
                            // Get thirdparty
                            $tmppayment->fetch($links[$key]['url_id']);
                            $arrayofamounts = $tmppayment->getAmountsArray();
                            if (is_array($arrayofamounts)) {
                                foreach ($arrayofamounts as $invoiceid => $amount) {
                                    $tmpinvoice->fetch($invoiceid);
                                    $tmpinvoice->fetch_thirdparty();
                                    if ($tmpinvoice->thirdparty->code_compta_client) {
                                        $tabtp[$obj->rowid][$tmpinvoice->thirdparty->code_compta_client] += $amount;
                                    }
                                }
                            }
                        }
                    }
                }

                // If no links were found to know the amount on thirdparty/user, we init it to account 'NotDefined'.
                if (empty($tabtp[$obj->rowid])) {
                    $tabtp[$obj->rowid]['NotDefined'] = $tabbq[$obj->rowid][$compta_bank];
                }

                // Check account number is ok
                /*if ($action == 'writebookkeeping')		// Make test now in such a case
                {
                    reset($tabbq[$obj->rowid]);
                    $first_key_tabbq = key($tabbq[$obj->rowid]);
                    if (empty($first_key_tabbq))
                    {
                        $error++;
                        setEventMessages($langs->trans('ErrorAccountancyCodeOnBankAccountNotDefined', $obj->baref), null, 'errors');
                    }
                    reset($tabtp[$obj->rowid]);
                    $first_key_tabtp = key($tabtp[$obj->rowid]);
                    if (empty($first_key_tabtp))
                    {
                        $error++;
                        setEventMessages($langs->trans('ErrorAccountancyCodeOnThirdPartyNotDefined'), null, 'errors');
                    }
                }*/

                // if($obj->socid)$tabtp[$obj->rowid][$compta_soc] += $amounttouse;

                $i++;
            }
        } else {
            dol_print_error($db);
        }


//var_dump($tabpay);
//var_dump($tabcompany);
//var_dump($tabbq);
//var_dump($tabtp);
//var_dump($tabtype);

// Write bookkeeping
        if (!$error && $action == 'writebookkeeping') {
            $now = dol_now();

            $accountingaccountcustomer = new AccountingAccount($db);
            $accountingaccountcustomer->fetch(null, getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER'), true);

            $accountingaccountsupplier = new AccountingAccount($db);
            $accountingaccountsupplier->fetch(null, getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER'), true);

            $accountingaccountpayment = new AccountingAccount($db);
            $accountingaccountpayment->fetch(null, getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT'), true);

            $accountingaccountsuspense = new AccountingAccount($db);
            $accountingaccountsuspense->fetch(null, getDolGlobalString('ACCOUNTING_ACCOUNT_SUSPENSE'), true);

            $error = 0;
            foreach ($tabpay as $key => $val) {		// $key is rowid into llx_bank
                $date = dol_print_date($val["date"], 'day');

                $ref = getSourceDocRef($val, $tabtype[$key]);

                $errorforline = 0;

                $totalcredit = 0;
                $totaldebit = 0;

                $db->begin();

                // Introduce a protection. Total of tabtp must be total of tabbq
                //var_dump($tabpay);
                //var_dump($tabtp);
                //var_dump($tabbq);exit;

                // Bank
                if (!$errorforline && is_array($tabbq[$key])) {
                    // Line into bank account
                    foreach ($tabbq[$key] as $k => $mt) {
                        if ($mt) {
                            $accountingaccount->fetch(null, $k, true);	// $k is accounting bank account. TODO We should use a cache here to avoid this fetch
                            $account_label = $accountingaccount->label;

                            $reflabel = '';
                            if (!empty($val['lib'])) {
                                $reflabel .= dol_string_nohtmltag($val['lib'])." - ";
                            }
                            $reflabel .= $langs->trans("Bank").' '.dol_string_nohtmltag($val['bank_account_ref']);
                            if (!empty($val['soclib'])) {
                                $reflabel .= " - ".dol_string_nohtmltag($val['soclib']);
                            }

                            $bookkeeping = new BookKeeping($db);
                            $bookkeeping->doc_date = $val["date"];
                            $bookkeeping->doc_ref = $ref;
                            $bookkeeping->doc_type = 'bank';
                            $bookkeeping->fk_doc = $key;
                            $bookkeeping->fk_docdet = $val["fk_bank"];

                            $bookkeeping->numero_compte = $k;
                            $bookkeeping->label_compte = $account_label;

                            $bookkeeping->label_operation = $reflabel;
                            $bookkeeping->montant = $mt;
                            $bookkeeping->sens = ($mt >= 0) ? 'D' : 'C';
                            $bookkeeping->debit = ($mt >= 0 ? $mt : 0);
                            $bookkeeping->credit = ($mt < 0 ? -$mt : 0);
                            $bookkeeping->code_journal = $journal;
                            $bookkeeping->journal_label = $langs->transnoentities($journal_label);
                            $bookkeeping->fk_user_author = $user->id;
                            $bookkeeping->date_creation = $now;

                            // No subledger_account value for the bank line but add a specific label_operation
                            $bookkeeping->subledger_account = '';
                            $bookkeeping->label_operation = $reflabel;
                            $bookkeeping->entity = $conf->entity;

                            $totaldebit += $bookkeeping->debit;
                            $totalcredit += $bookkeeping->credit;

                            $result = $bookkeeping->create($user);
                            if ($result < 0) {
                                if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists') {	// Already exists
                                    $error++;
                                    $errorforline++;
                                    setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->fk_doc.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
                                } else {
                                    $error++;
                                    $errorforline++;
                                    setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
                                }
                            }
                        }
                    }
                }

                // Third party
                if (!$errorforline) {
                    if (is_array($tabtp[$key])) {
                        // Line into thirdparty account
                        foreach ($tabtp[$key] as $k => $mt) {
                            if ($mt) {
                                $lettering = false;

                                $reflabel = '';
                                if (!empty($val['lib'])) {
                                    $reflabel .= dol_string_nohtmltag($val['lib']).($val['soclib'] ? " - " : "");
                                }
                                if ($tabtype[$key] == 'banktransfert') {
                                    $reflabel .= dol_string_nohtmltag($langs->transnoentitiesnoconv('TransitionalAccount').' '.$account_transfer);
                                } else {
                                    $reflabel .= dol_string_nohtmltag($val['soclib']);
                                }

                                $bookkeeping = new BookKeeping($db);
                                $bookkeeping->doc_date = $val["date"];
                                $bookkeeping->doc_ref = $ref;
                                $bookkeeping->doc_type = 'bank';
                                $bookkeeping->fk_doc = $key;
                                $bookkeeping->fk_docdet = $val["fk_bank"];

                                $bookkeeping->label_operation = $reflabel;
                                $bookkeeping->montant = $mt;
                                $bookkeeping->sens = ($mt < 0) ? 'D' : 'C';
                                $bookkeeping->debit = ($mt < 0 ? -$mt : 0);
                                $bookkeeping->credit = ($mt >= 0) ? $mt : 0;
                                $bookkeeping->code_journal = $journal;
                                $bookkeeping->journal_label = $langs->transnoentities($journal_label);
                                $bookkeeping->fk_user_author = $user->id;
                                $bookkeeping->date_creation = $now;

                                if ($tabtype[$key] == 'payment') {	// If payment is payment of customer invoice, we get ref of invoice
                                    $lettering = true;
                                    $bookkeeping->subledger_account = $k; // For payment, the subledger account is stored as $key of $tabtp
                                    $bookkeeping->subledger_label = $tabcompany[$key]['name']; // $tabcompany is defined only if we are sure there is 1 thirdparty for the bank transaction
                                    $bookkeeping->numero_compte = getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER');
                                    $bookkeeping->label_compte = $accountingaccountcustomer->label;
                                } elseif ($tabtype[$key] == 'payment_supplier') {	// If payment is payment of supplier invoice, we get ref of invoice
                                    $lettering = true;
                                    $bookkeeping->subledger_account = $k; // For payment, the subledger account is stored as $key of $tabtp
                                    $bookkeeping->subledger_label = $tabcompany[$key]['name']; // $tabcompany is defined only if we are sure there is 1 thirdparty for the bank transaction
                                    $bookkeeping->numero_compte = getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER');
                                    $bookkeeping->label_compte = $accountingaccountsupplier->label;
                                } elseif ($tabtype[$key] == 'payment_expensereport') {
                                    $bookkeeping->subledger_account = $tabuser[$key]['accountancy_code'];
                                    $bookkeeping->subledger_label = $tabuser[$key]['name'];
                                    $bookkeeping->numero_compte = getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT');
                                    $bookkeeping->label_compte = $accountingaccountpayment->label;
                                } elseif ($tabtype[$key] == 'payment_salary') {
                                    $bookkeeping->subledger_account = $tabuser[$key]['accountancy_code'];
                                    $bookkeeping->subledger_label = $tabuser[$key]['name'];
                                    $bookkeeping->numero_compte = getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT');
                                    $bookkeeping->label_compte = $accountingaccountpayment->label;
                                } elseif (in_array($tabtype[$key], array('sc', 'payment_sc'))) {   // If payment is payment of social contribution
                                    $bookkeeping->subledger_account = '';
                                    $bookkeeping->subledger_label = '';
                                    $accountingaccount->fetch(null, $k, true);	// TODO Use a cache
                                    $bookkeeping->numero_compte = $k;
                                    $bookkeeping->label_compte = $accountingaccount->label;
                                } elseif ($tabtype[$key] == 'payment_vat') {
                                    $bookkeeping->subledger_account = '';
                                    $bookkeeping->subledger_label = '';
                                    $accountingaccount->fetch(null, $k, true);		// TODO Use a cache
                                    $bookkeeping->numero_compte = $k;
                                    $bookkeeping->label_compte = $accountingaccount->label;
                                } elseif ($tabtype[$key] == 'payment_donation') {
                                    $bookkeeping->subledger_account = '';
                                    $bookkeeping->subledger_label = '';
                                    $accountingaccount->fetch(null, $k, true);		// TODO Use a cache
                                    $bookkeeping->numero_compte = $k;
                                    $bookkeeping->label_compte = $accountingaccount->label;
                                } elseif ($tabtype[$key] == 'member') {
                                    $bookkeeping->subledger_account = '';
                                    $bookkeeping->subledger_label = '';
                                    $accountingaccount->fetch(null, $k, true);		// TODO Use a cache
                                    $bookkeeping->numero_compte = $k;
                                    $bookkeeping->label_compte = $accountingaccount->label;
                                } elseif ($tabtype[$key] == 'payment_loan') {
                                    $bookkeeping->subledger_account = '';
                                    $bookkeeping->subledger_label = '';
                                    $accountingaccount->fetch(null, $k, true);		// TODO Use a cache
                                    $bookkeeping->numero_compte = $k;
                                    $bookkeeping->label_compte = $accountingaccount->label;
                                } elseif ($tabtype[$key] == 'payment_various') {
                                    $bookkeeping->subledger_account = $k;
                                    $bookkeeping->subledger_label = $tabcompany[$key]['name'];
                                    $accountingaccount->fetch(null, $tabpay[$key]["account_various"], true);	// TODO Use a cache
                                    $bookkeeping->numero_compte = $tabpay[$key]["account_various"];
                                    $bookkeeping->label_compte = $accountingaccount->label;
                                } elseif ($tabtype[$key] == 'banktransfert') {
                                    $bookkeeping->subledger_account = '';
                                    $bookkeeping->subledger_label = '';
                                    $accountingaccount->fetch(null, $k, true);		// TODO Use a cache
                                    $bookkeeping->numero_compte = $k;
                                    $bookkeeping->label_compte = $accountingaccount->label;
                                } else {
                                    if ($tabtype[$key] == 'unknown') {	// Unknown transaction, we will use a waiting account for thirdparty.
                                        // Temporary account
                                        $bookkeeping->subledger_account = '';
                                        $bookkeeping->subledger_label = '';
                                        $bookkeeping->numero_compte = getDolGlobalString('ACCOUNTING_ACCOUNT_SUSPENSE');
                                        $bookkeeping->label_compte = $accountingaccountsuspense->label;
                                    }
                                }
                                $bookkeeping->label_operation = $reflabel;
                                $bookkeeping->entity = $conf->entity;

                                $totaldebit += $bookkeeping->debit;
                                $totalcredit += $bookkeeping->credit;

                                $result = $bookkeeping->create($user);
                                if ($result < 0) {
                                    if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists') {	// Already exists
                                        $error++;
                                        $errorforline++;
                                        setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->fk_doc.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
                                    } else {
                                        $error++;
                                        $errorforline++;
                                        setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
                                    }
                                } else {
                                    if ($lettering && getDolGlobalInt('ACCOUNTING_ENABLE_LETTERING') && getDolGlobalInt('ACCOUNTING_ENABLE_AUTOLETTERING')) {
                                        require_once DOL_DOCUMENT_ROOT . '/accountancy/class/lettering.class.php';
                                        $lettering_static = new Lettering($db);
                                        $nb_lettering = $lettering_static->bookkeepingLetteringAll(array($bookkeeping->id));
                                    }
                                }
                            }
                        }
                    } else {	// If thirdparty unknown, output the waiting account
                        foreach ($tabbq[$key] as $k => $mt) {
                            if ($mt) {
                                $reflabel = '';
                                if (!empty($val['lib'])) {
                                    $reflabel .= dol_string_nohtmltag($val['lib'])." - ";
                                }
                                $reflabel .= dol_string_nohtmltag('WaitingAccount');

                                $bookkeeping = new BookKeeping($db);
                                $bookkeeping->doc_date = $val["date"];
                                $bookkeeping->doc_ref = $ref;
                                $bookkeeping->doc_type = 'bank';
                                $bookkeeping->fk_doc = $key;
                                $bookkeeping->fk_docdet = $val["fk_bank"];
                                $bookkeeping->montant = $mt;
                                $bookkeeping->sens = ($mt < 0) ? 'D' : 'C';
                                $bookkeeping->debit = ($mt < 0 ? -$mt : 0);
                                $bookkeeping->credit = ($mt >= 0) ? $mt : 0;
                                $bookkeeping->code_journal = $journal;
                                $bookkeeping->journal_label = $langs->transnoentities($journal_label);
                                $bookkeeping->fk_user_author = $user->id;
                                $bookkeeping->date_creation = $now;
                                $bookkeeping->label_compte = '';
                                $bookkeeping->label_operation = $reflabel;
                                $bookkeeping->entity = $conf->entity;

                                $totaldebit += $bookkeeping->debit;
                                $totalcredit += $bookkeeping->credit;

                                $result = $bookkeeping->create($user);

                                if ($result < 0) {
                                    if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists') {	// Already exists
                                        $error++;
                                        $errorforline++;
                                        setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->fk_doc.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
                                    } else {
                                        $error++;
                                        $errorforline++;
                                        setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
                                    }
                                }
                            }
                        }
                    }
                }

                if (price2num($totaldebit, 'MT') != price2num($totalcredit, 'MT')) {
                    $error++;
                    $errorforline++;
                    setEventMessages('We tried to insert a non balanced transaction in book for '.$ref.'. Canceled. Surely a bug.', null, 'errors');
                }

                if (!$errorforline) {
                    $db->commit();
                } else {
                    //print 'KO for line '.$key.' '.$error.'<br>';
                    $db->rollback();

                    $MAXNBERRORS = 5;
                    if ($error >= $MAXNBERRORS) {
                        setEventMessages($langs->trans("ErrorTooManyErrorsProcessStopped").' (>'.$MAXNBERRORS.')', null, 'errors');
                        break; // Break in the foreach
                    }
                }
            }

            if (empty($error) && count($tabpay) > 0) {
                setEventMessages($langs->trans("GeneralLedgerIsWritten"), null, 'mesgs');
            } elseif (count($tabpay) == $error) {
                setEventMessages($langs->trans("NoNewRecordSaved"), null, 'warnings');
            } else {
                setEventMessages($langs->trans("GeneralLedgerSomeRecordWasNotRecorded"), null, 'warnings');
            }

            $action = '';

            // Must reload data, so we make a redirect
            if (count($tabpay) != $error) {
                $param = 'id_journal='.$id_journal;
                $param .= '&date_startday='.$date_startday;
                $param .= '&date_startmonth='.$date_startmonth;
                $param .= '&date_startyear='.$date_startyear;
                $param .= '&date_endday='.$date_endday;
                $param .= '&date_endmonth='.$date_endmonth;
                $param .= '&date_endyear='.$date_endyear;
                $param .= '&in_bookkeeping='.$in_bookkeeping;
                header("Location: ".$_SERVER['PHP_SELF'].($param ? '?'.$param : ''));
                exit;
            }
        }



// Export
        if ($action == 'exportcsv') {		// ISO and not UTF8 !
            $sep = getDolGlobalString('ACCOUNTING_EXPORT_SEPARATORCSV');

            $filename = 'journal';
            $type_export = 'journal';
            include DOL_DOCUMENT_ROOT.'/accountancy/tpl/export_journal.tpl.php';

            // CSV header line
            print '"'.$langs->transnoentitiesnoconv("BankId").'"'.$sep;
            print '"'.$langs->transnoentitiesnoconv("Date").'"'.$sep;
            print '"'.$langs->transnoentitiesnoconv("PaymentMode").'"'.$sep;
            print '"'.$langs->transnoentitiesnoconv("AccountAccounting").'"'.$sep;
            print '"'.$langs->transnoentitiesnoconv("LedgerAccount").'"'.$sep;
            print '"'.$langs->transnoentitiesnoconv("SubledgerAccount").'"'.$sep;
            print '"'.$langs->transnoentitiesnoconv("Label").'"'.$sep;
            print '"'.$langs->transnoentitiesnoconv("AccountingDebit").'"'.$sep;
            print '"'.$langs->transnoentitiesnoconv("AccountingCredit").'"'.$sep;
            print '"'.$langs->transnoentitiesnoconv("Journal").'"'.$sep;
            print '"'.$langs->transnoentitiesnoconv("Note").'"'.$sep;
            print "\n";

            foreach ($tabpay as $key => $val) {
                $date = dol_print_date($val["date"], 'day');

                $ref = getSourceDocRef($val, $tabtype[$key]);

                // Bank
                foreach ($tabbq[$key] as $k => $mt) {
                    if ($mt) {
                        $reflabel = '';
                        if (!empty($val['lib'])) {
                            $reflabel .= dol_string_nohtmltag($val['lib'])." - ";
                        }
                        $reflabel .= $langs->trans("Bank").' '.dol_string_nohtmltag($val['bank_account_ref']);
                        if (!empty($val['soclib'])) {
                            $reflabel .= " - ".dol_string_nohtmltag($val['soclib']);
                        }

                        print '"'.$key.'"'.$sep;
                        print '"'.$date.'"'.$sep;
                        print '"'.$val["type_payment"].'"'.$sep;
                        print '"'.length_accountg(html_entity_decode($k)).'"'.$sep;
                        print '"'.length_accounta(html_entity_decode($k)).'"'.$sep;
                        print "  ".$sep;
                        print '"'.$reflabel.'"'.$sep;
                        print '"'.($mt >= 0 ? price($mt) : '').'"'.$sep;
                        print '"'.($mt < 0 ? price(-$mt) : '').'"'.$sep;
                        print '"'.$journal.'"'.$sep;
                        print '"'.dol_string_nohtmltag($ref).'"'.$sep;
                        print "\n";
                    }
                }

                // Third party
                if (is_array($tabtp[$key])) {
                    foreach ($tabtp[$key] as $k => $mt) {
                        if ($mt) {
                            $reflabel = '';
                            if (!empty($val['lib'])) {
                                $reflabel .= dol_string_nohtmltag($val['lib']).($val['soclib'] ? " - " : "");
                            }
                            if ($tabtype[$key] == 'banktransfert') {
                                $reflabel .= dol_string_nohtmltag($langs->transnoentitiesnoconv('TransitionalAccount').' '.$account_transfer);
                            } else {
                                $reflabel .= dol_string_nohtmltag($val['soclib']);
                            }

                            print '"'.$key.'"'.$sep;
                            print '"'.$date.'"'.$sep;
                            print '"'.$val["type_payment"].'"'.$sep;
                            print '"'.length_accountg(html_entity_decode($k)).'"'.$sep;
                            if ($tabtype[$key] == 'payment_supplier') {
                                print '"'.getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER').'"'.$sep;
                            } elseif ($tabtype[$key] == 'payment') {
                                print '"'.getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER').'"'.$sep;
                            } elseif ($tabtype[$key] == 'payment_expensereport') {
                                print '"'.getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT').'"'.$sep;
                            } elseif ($tabtype[$key] == 'payment_salary') {
                                print '"'.getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT').'"'.$sep;
                            } else {
                                print '"'.length_accountg(html_entity_decode($k)).'"'.$sep;
                            }
                            print '"'.length_accounta(html_entity_decode($k)).'"'.$sep;
                            print '"'.$reflabel.'"'.$sep;
                            print '"'.($mt < 0 ? price(-$mt) : '').'"'.$sep;
                            print '"'.($mt >= 0 ? price($mt) : '').'"'.$sep;
                            print '"'.$journal.'"'.$sep;
                            print '"'.dol_string_nohtmltag($ref).'"'.$sep;
                            print "\n";
                        }
                    }
                } else {	// If thirdparty unknown, output the waiting account
                    foreach ($tabbq[$key] as $k => $mt) {
                        if ($mt) {
                            $reflabel = '';
                            if (!empty($val['lib'])) {
                                $reflabel .= dol_string_nohtmltag($val['lib'])." - ";
                            }
                            $reflabel .= dol_string_nohtmltag('WaitingAccount');

                            print '"'.$key.'"'.$sep;
                            print '"'.$date.'"'.$sep;
                            print '"'.$val["type_payment"].'"'.$sep;
                            print '"'.length_accountg(getDolGlobalString('ACCOUNTING_ACCOUNT_SUSPENSE')).'"'.$sep;
                            print '"'.length_accounta(getDolGlobalString('ACCOUNTING_ACCOUNT_SUSPENSE')).'"'.$sep;
                            print $sep;
                            print '"'.$reflabel.'"'.$sep;
                            print '"'.($mt < 0 ? price(-$mt) : '').'"'.$sep;
                            print '"'.($mt >= 0 ? price($mt) : '').'"'.$sep;
                            print '"'.$journal.'"'.$sep;
                            print '"'.dol_string_nohtmltag($ref).'"'.$sep;
                            print "\n";
                        }
                    }
                }
            }
        }


        /*
         * View
         */

        $form = new Form($db);

        if (empty($action) || $action == 'view') {
            $invoicestatic = new Facture($db);
            $invoicesupplierstatic = new FactureFournisseur($db);
            $expensereportstatic = new ExpenseReport($db);
            $vatstatic = new Tva($db);
            $donationstatic = new Don($db);
            $loanstatic = new Loan($db);
            $salarystatic = new Salary($db);
            $variousstatic = new PaymentVarious($db);

            $title = $langs->trans("GenerationOfAccountingEntries").' - '.$accountingjournalstatic->getNomUrl(0, 2, 1, '', 1);

            llxHeader('', dol_string_nohtmltag($title));

            $nom = $title;
            $builddate = dol_now();
            //$description = $langs->trans("DescFinanceJournal") . '<br>';
            $description = $langs->trans("DescJournalOnlyBindedVisible").'<br>';

            $listofchoices = array(
                'notyet' => $langs->trans("NotYetInGeneralLedger"),
                'already' => $langs->trans("AlreadyInGeneralLedger")
            );
            $period = $form->selectDate($date_start ? $date_start : -1, 'date_start', 0, 0, 0, '', 1, 0).' - '.$form->selectDate($date_end ? $date_end : -1, 'date_end', 0, 0, 0, '', 1, 0);
            $period .= ' -  '.$langs->trans("JournalizationInLedgerStatus").' '.$form->selectarray('in_bookkeeping', $listofchoices, $in_bookkeeping, 1);

            $varlink = 'id_journal='.$id_journal;
            $periodlink = '';
            $exportlink = '';

            journalHead($nom, '', $period, $periodlink, $description, $builddate, $exportlink, array('action' => ''), '', $varlink);

            $desc = '';

            if (getDolGlobalString('ACCOUNTANCY_FISCAL_PERIOD_MODE') != 'blockedonclosed') {
                // Test that setup is complete (we are in accounting, so test on entity is always on $conf->entity only, no sharing allowed)
                // Fiscal period test
                $sql = "SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX."accounting_fiscalyear WHERE entity = ".((int) $conf->entity);
                $resql = $db->query($sql);
                if ($resql) {
                    $obj = $db->fetch_object($resql);
                    if ($obj->nb == 0) {
                        print '<br><div class="warning">'.img_warning().' '.$langs->trans("TheFiscalPeriodIsNotDefined");
                        $desc = ' : '.$langs->trans("AccountancyAreaDescFiscalPeriod", 4, '{link}');
                        $desc = str_replace('{link}', '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("FiscalPeriod").'</strong>', $desc);
                        print $desc;
                        print '</div>';
                    }
                } else {
                    dol_print_error($db);
                }
            }

            // Bank test
            $sql = "SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX."bank_account WHERE entity = ".((int) $conf->entity)." AND fk_accountancy_journal IS NULL AND clos=0";
            $resql = $db->query($sql);
            if ($resql) {
                $obj = $db->fetch_object($resql);
                if ($obj->nb > 0) {
                    print '<br><div class="warning">'.img_warning().' '.$langs->trans("TheJournalCodeIsNotDefinedOnSomeBankAccount");
                    $desc = ' : '.$langs->trans("AccountancyAreaDescBank", 6, '{link}');
                    $desc = str_replace('{link}', '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("BankAccounts").'</strong>', $desc);
                    print $desc;
                    print '</div>';
                }
            } else {
                dol_print_error($db);
            }


            // Button to write into Ledger
            if (getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER') == "" || getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER') == '-1'
                || getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER') == "" || getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER') == '-1'
                || getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT') == "" || getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT') == '-1') {
                print($desc ? '' : '<br>').'<div class="warning">'.img_warning().' '.$langs->trans("SomeMandatoryStepsOfSetupWereNotDone");
                $desc = ' : '.$langs->trans("AccountancyAreaDescMisc", 4, '{link}');
                $desc = str_replace('{link}', '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("MenuDefaultAccounts").'</strong>', $desc);
                print $desc;
                print '</div>';
            }


            print '<br><div class="tabsAction tabsActionNoBottom centerimp">';

            if (getDolGlobalString('ACCOUNTING_ENABLE_EXPORT_DRAFT_JOURNAL') && $in_bookkeeping == 'notyet') {
                print '<input type="button" class="butAction" name="exportcsv" value="'.$langs->trans("ExportDraftJournal").'" onclick="launch_export();" />';
            }

            if (getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER') == "" || getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER') == '-1'
                || getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER') == "" || getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER') == '-1') {
                print '<input type="button" class="butActionRefused classfortooltip" title="'.dol_escape_htmltag($langs->trans("SomeMandatoryStepsOfSetupWereNotDone")).'" value="'.$langs->trans("WriteBookKeeping").'" />';
            } else {
                if ($in_bookkeeping == 'notyet') {
                    print '<input type="button" class="butAction" name="writebookkeeping" value="'.$langs->trans("WriteBookKeeping").'" onclick="writebookkeeping();" />';
                } else {
                    print '<a class="butActionRefused classfortooltip" name="writebookkeeping">'.$langs->trans("WriteBookKeeping").'</a>';
                }
            }

            print '</div>';

            // TODO Avoid using js. We can use a direct link with $param
            print '
	<script type="text/javascript">
		function launch_export() {
			console.log("Set value into form and submit");
			$("div.fiche form input[name=\"action\"]").val("exportcsv");
			$("div.fiche form input[type=\"submit\"]").click();
			$("div.fiche form input[name=\"action\"]").val("");
		}
		function writebookkeeping() {
			console.log("Set value into form and submit");
			$("div.fiche form input[name=\"action\"]").val("writebookkeeping");
			$("div.fiche form input[type=\"submit\"]").click();
			$("div.fiche form input[name=\"action\"]").val("");
		}
	</script>';

            /*
             * Show result array
             */
            print '<br>';

            $i = 0;
            print '<div class="div-table-responsive">';
            print '<table class="noborder centpercent">';
            print '<tr class="liste_titre">';
            print "<td>".$langs->trans("Date")."</td>";
            print "<td>".$langs->trans("Piece").' ('.$langs->trans("ObjectsRef").")</td>";
            print "<td>".$langs->trans("AccountAccounting")."</td>";
            print "<td>".$langs->trans("SubledgerAccount")."</td>";
            print "<td>".$langs->trans("LabelOperation")."</td>";
            print '<td class="center">'.$langs->trans("PaymentMode")."</td>";
            print '<td class="right">'.$langs->trans("AccountingDebit")."</td>";
            print '<td class="right">'.$langs->trans("AccountingCredit")."</td>";
            print "</tr>\n";

            $r = '';

            foreach ($tabpay as $key => $val) {			  // $key is rowid in llx_bank
                $date = dol_print_date($val["date"], 'day');

                $ref = getSourceDocRef($val, $tabtype[$key]);

                // Bank
                foreach ($tabbq[$key] as $k => $mt) {
                    if ($mt) {
                        $reflabel = '';
                        if (!empty($val['lib'])) {
                            $reflabel .= $val['lib']." - ";
                        }
                        $reflabel .= $langs->trans("Bank").' '.$val['bank_account_ref'];
                        if (!empty($val['soclib'])) {
                            $reflabel .= " - ".$val['soclib'];
                        }

                        //var_dump($tabpay[$key]);
                        print '<!-- Bank bank.rowid='.$key.' type='.$tabpay[$key]['type'].' ref='.$tabpay[$key]['ref'].'-->';
                        print '<tr class="oddeven">';

                        // Date
                        print "<td>".$date."</td>";

                        // Ref
                        print "<td>".dol_escape_htmltag($ref)."</td>";

                        // Ledger account
                        $accounttoshow = length_accountg($k);
                        if (empty($accounttoshow) || $accounttoshow == 'NotDefined') {
                            $accounttoshow = '<span class="error">'.$langs->trans("BankAccountNotDefined").'</span>';
                        }
                        print '<td class="maxwidth300" title="'.dol_escape_htmltag(dol_string_nohtmltag($accounttoshow)).'">';
                        print $accounttoshow;
                        print "</td>";

                        // Subledger account
                        print '<td class="maxwidth300">';
                        /*$accounttoshow = length_accountg($k);
                        if (empty($accounttoshow) || $accounttoshow == 'NotDefined')
                        {
                            print '<span class="error">'.$langs->trans("BankAccountNotDefined").'</span>';
                        }
                        else print $accounttoshow;*/
                        print "</td>";

                        // Label operation
                        print '<td>';
                        print $reflabel;	// This is already html escaped content
                        print "</td>";

                        print '<td class="center">'.$val["type_payment"]."</td>";
                        print '<td class="right nowraponall amount">'.($mt >= 0 ? price($mt) : '')."</td>";
                        print '<td class="right nowraponall amount">'.($mt < 0 ? price(-$mt) : '')."</td>";
                        print "</tr>";

                        $i++;
                    }
                }

                // Third party
                if (is_array($tabtp[$key])) {
                    foreach ($tabtp[$key] as $k => $mt) {
                        if ($mt) {
                            $reflabel = '';
                            if (!empty($val['lib'])) {
                                $reflabel .= $val['lib'].($val['soclib'] ? " - " : "");
                            }
                            if ($tabtype[$key] == 'banktransfert') {
                                $reflabel .= $langs->trans('TransitionalAccount').' '.$account_transfer;
                            } else {
                                $reflabel .= $val['soclib'];
                            }

                            print '<!-- Thirdparty bank.rowid='.$key.' -->';
                            print '<tr class="oddeven">';

                            // Date
                            print "<td>".$date."</td>";

                            // Ref
                            print "<td>".dol_escape_htmltag($ref)."</td>";

                            // Ledger account
                            $account_ledger = $k;
                            // Try to force general ledger account depending on type
                            if ($tabtype[$key] == 'payment') {
                                $account_ledger = getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER');
                            }
                            if ($tabtype[$key] == 'payment_supplier') {
                                $account_ledger = getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER');
                            }
                            if ($tabtype[$key] == 'payment_expensereport') {
                                $account_ledger = getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT');
                            }
                            if ($tabtype[$key] == 'payment_salary') {
                                $account_ledger = getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT');
                            }
                            if ($tabtype[$key] == 'payment_vat') {
                                $account_ledger = getDolGlobalString('ACCOUNTING_VAT_PAY_ACCOUNT');
                            }
                            if ($tabtype[$key] == 'member') {
                                $account_ledger = getDolGlobalString('ADHERENT_SUBSCRIPTION_ACCOUNTINGACCOUNT');
                            }
                            if ($tabtype[$key] == 'payment_various') {
                                $account_ledger = $tabpay[$key]["account_various"];
                            }
                            $accounttoshow = length_accountg($account_ledger);
                            if (empty($accounttoshow) || $accounttoshow == 'NotDefined') {
                                if ($tabtype[$key] == 'unknown') {
                                    // We will accept writing, but into a waiting account
                                    if (!getDolGlobalString('ACCOUNTING_ACCOUNT_SUSPENSE') || getDolGlobalString('ACCOUNTING_ACCOUNT_SUSPENSE') == '-1') {
                                        $accounttoshow = '<span class="error small">'.$langs->trans('UnknownAccountForThirdpartyAndWaitingAccountNotDefinedBlocking').'</span>';
                                    } else {
                                        $accounttoshow = '<span class="warning small">'.$langs->trans('UnknownAccountForThirdparty', length_accountg(getDolGlobalString('ACCOUNTING_ACCOUNT_SUSPENSE'))).'</span>'; // We will use a waiting account
                                    }
                                } else {
                                    // We will refuse writing
                                    $errorstring = 'UnknownAccountForThirdpartyBlocking';
                                    if ($tabtype[$key] == 'payment') {
                                        $errorstring = 'MainAccountForCustomersNotDefined';
                                    }
                                    if ($tabtype[$key] == 'payment_supplier') {
                                        $errorstring = 'MainAccountForSuppliersNotDefined';
                                    }
                                    if ($tabtype[$key] == 'payment_expensereport') {
                                        $errorstring = 'MainAccountForUsersNotDefined';
                                    }
                                    if ($tabtype[$key] == 'payment_salary') {
                                        $errorstring = 'MainAccountForUsersNotDefined';
                                    }
                                    if ($tabtype[$key] == 'payment_vat') {
                                        $errorstring = 'MainAccountForVatPaymentNotDefined';
                                    }
                                    if ($tabtype[$key] == 'member') {
                                        $errorstring = 'MainAccountForSubscriptionPaymentNotDefined';
                                    }
                                    $accounttoshow = '<span class="error small">'.$langs->trans($errorstring).'</span>';
                                }
                            }
                            print '<td class="maxwidth300" title="'.dol_escape_htmltag(dol_string_nohtmltag($accounttoshow)).'">';
                            print $accounttoshow;	// This is a HTML string
                            print "</td>";

                            // Subledger account
                            $accounttoshowsubledger = '';
                            if (in_array($tabtype[$key], array('payment', 'payment_supplier', 'payment_expensereport', 'payment_salary', 'payment_various'))) {	// Type of payments that uses a subledger
                                $accounttoshowsubledger = length_accounta($k);
                                if ($accounttoshow != $accounttoshowsubledger) {
                                    if (empty($accounttoshowsubledger) || $accounttoshowsubledger == 'NotDefined') {
                                        //var_dump($tabpay[$key]);
                                        //var_dump($tabtype[$key]);
                                        //var_dump($tabbq[$key]);
                                        //print '<span class="error">'.$langs->trans("ThirdpartyAccountNotDefined").'</span>';
                                        if (!empty($tabcompany[$key]['code_compta'])) {
                                            if (in_array($tabtype[$key], array('payment_various', 'payment_salary'))) {
                                                // For such case, if subledger is not defined, we won't use subledger accounts.
                                                $accounttoshowsubledger = '<span class="warning small">'.$langs->trans("ThirdpartyAccountNotDefinedOrThirdPartyUnknownSubledgerIgnored").'</span>';
                                            } else {
                                                $accounttoshowsubledger = '<span class="warning small">'.$langs->trans("ThirdpartyAccountNotDefinedOrThirdPartyUnknown", $tabcompany[$key]['code_compta']).'</span>';
                                            }
                                        } else {
                                            $accounttoshowsubledger = '<span class="error small">'.$langs->trans("ThirdpartyAccountNotDefinedOrThirdPartyUnknownBlocking").'</span>';
                                        }
                                    }
                                } else {
                                    $accounttoshowsubledger = '';
                                }
                            }
                            print '<td class="maxwidth300">';
                            print $accounttoshowsubledger;	// This is a html string
                            print "</td>";

                            print "<td>".$reflabel."</td>";

                            print '<td class="center">'.$val["type_payment"]."</td>";

                            print '<td class="right nowraponall amount">'.($mt < 0 ? price(-$mt) : '')."</td>";

                            print '<td class="right nowraponall amount">'.($mt >= 0 ? price($mt) : '')."</td>";

                            print "</tr>";

                            $i++;
                        }
                    }
                } else {	// Waiting account
                    foreach ($tabbq[$key] as $k => $mt) {
                        if ($mt) {
                            $reflabel = '';
                            if (!empty($val['lib'])) {
                                $reflabel .= $val['lib']." - ";
                            }
                            $reflabel .= 'WaitingAccount';

                            print '<!-- Wait bank.rowid='.$key.' -->';
                            print '<tr class="oddeven">';
                            print "<td>".$date."</td>";
                            print "<td>".$ref."</td>";
                            // Ledger account
                            print "<td>";
                            /*if (empty($accounttoshow) || $accounttoshow == 'NotDefined')
                            {
                                print '<span class="error">'.$langs->trans("WaitAccountNotDefined").'</span>';
                            }
                            else */
                            print length_accountg(getDolGlobalString('ACCOUNTING_ACCOUNT_SUSPENSE'));
                            print "</td>";
                            // Subledger account
                            print "<td>";
                            print "</td>";
                            print "<td>".dol_escape_htmltag($reflabel)."</td>";
                            print '<td class="center">'.$val["type_payment"]."</td>";
                            print '<td class="right nowraponall amount">'.($mt < 0 ? price(-$mt) : '')."</td>";
                            print '<td class="right nowraponall amount">'.($mt >= 0 ? price($mt) : '')."</td>";
                            print "</tr>";

                            $i++;
                        }
                    }
                }
            }

            if (!$i) {
                $colspan = 8;
                print '<tr class="oddeven"><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
            }

            print "</table>";
            print '</div>';

            llxFooter();
        }

        $db->close();



        /**
         * Return source for doc_ref of a bank transaction
         *
         * @param 	array 	$val			Array of val
         * @param 	string	$typerecord		Type of record ('payment', 'payment_supplier', 'payment_expensereport', 'payment_vat', ...)
         * @return 	string					A string label to describe a record into llx_bank_url
         */
        function getSourceDocRef($val, $typerecord)
        {
            global $db, $langs;

            // Defined the docref into $ref (We start with $val['ref'] by default and we complete according to other data)
            // WE MUST HAVE SAME REF FOR ALL LINES WE WILL RECORD INTO THE BOOKKEEPING
            $ref = $val['ref'];
            if ($ref == '(SupplierInvoicePayment)' || $ref == '(SupplierInvoicePaymentBack)') {
                $ref = $langs->transnoentitiesnoconv('Supplier');
            }
            if ($ref == '(CustomerInvoicePayment)' || $ref == '(CustomerInvoicePaymentBack)') {
                $ref = $langs->transnoentitiesnoconv('Customer');
            }
            if ($ref == '(SocialContributionPayment)') {
                $ref = $langs->transnoentitiesnoconv('SocialContribution');
            }
            if ($ref == '(DonationPayment)') {
                $ref = $langs->transnoentitiesnoconv('Donation');
            }
            if ($ref == '(SubscriptionPayment)') {
                $ref = $langs->transnoentitiesnoconv('Subscription');
            }
            if ($ref == '(ExpenseReportPayment)') {
                $ref = $langs->transnoentitiesnoconv('Employee');
            }
            if ($ref == '(LoanPayment)') {
                $ref = $langs->transnoentitiesnoconv('Loan');
            }
            if ($ref == '(payment_salary)') {
                $ref = $langs->transnoentitiesnoconv('Employee');
            }

            $sqlmid = '';
            if ($typerecord == 'payment') {
                if (getDolGlobalInt('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
                    $sqlmid = "SELECT payfac.fk_facture as id, ".$db->ifsql('f1.rowid IS NULL', 'f.ref', 'f1.ref')." as ref";
                    $sqlmid .= " FROM ".$db->prefix()."paiement_facture as payfac";
                    $sqlmid .= " LEFT JOIN ".$db->prefix()."facture as f ON f.rowid = payfac.fk_facture";
                    $sqlmid .= " LEFT JOIN ".$db->prefix()."societe_remise_except as sre ON sre.fk_facture_source = payfac.fk_facture";
                    $sqlmid .= " LEFT JOIN ".$db->prefix()."facture as f1 ON f1.rowid = sre.fk_facture";
                    $sqlmid .= " WHERE payfac.fk_paiement=".((int) $val['paymentid']);
                } else {
                    $sqlmid = "SELECT payfac.fk_facture as id, f.ref as ref";
                    $sqlmid .= " FROM ".$db->prefix()."paiement_facture as payfac";
                    $sqlmid .= " INNER JOIN ".$db->prefix()."facture as f ON f.rowid = payfac.fk_facture";
                    $sqlmid .= " WHERE payfac.fk_paiement=".((int) $val['paymentid']);
                }
                $ref = $langs->transnoentitiesnoconv("Invoice");
            } elseif ($typerecord == 'payment_supplier') {
                $sqlmid = 'SELECT payfac.fk_facturefourn as id, f.ref';
                $sqlmid .= " FROM ".MAIN_DB_PREFIX."paiementfourn_facturefourn as payfac, ".MAIN_DB_PREFIX."facture_fourn as f";
                $sqlmid .= " WHERE payfac.fk_facturefourn = f.rowid AND payfac.fk_paiementfourn=".((int) $val["paymentsupplierid"]);
                $ref = $langs->transnoentitiesnoconv("SupplierInvoice");
            } elseif ($typerecord == 'payment_expensereport') {
                $sqlmid = 'SELECT e.rowid as id, e.ref';
                $sqlmid .= " FROM ".MAIN_DB_PREFIX."payment_expensereport as pe, ".MAIN_DB_PREFIX."expensereport as e";
                $sqlmid .= " WHERE pe.rowid=".((int) $val["paymentexpensereport"])." AND pe.fk_expensereport = e.rowid";
                $ref = $langs->transnoentitiesnoconv("ExpenseReport");
            } elseif ($typerecord == 'payment_salary') {
                $sqlmid = 'SELECT s.rowid as ref';
                $sqlmid .= " FROM ".MAIN_DB_PREFIX."payment_salary as s";
                $sqlmid .= " WHERE s.rowid=".((int) $val["paymentsalid"]);
                $ref = $langs->transnoentitiesnoconv("SalaryPayment");
            } elseif ($typerecord == 'sc') {
                $sqlmid = 'SELECT sc.rowid as ref';
                $sqlmid .= " FROM ".MAIN_DB_PREFIX."paiementcharge as sc";
                $sqlmid .= " WHERE sc.rowid=".((int) $val["paymentscid"]);
                $ref = $langs->transnoentitiesnoconv("SocialContribution");
            } elseif ($typerecord == 'payment_vat') {
                $sqlmid = 'SELECT v.rowid as ref';
                $sqlmid .= " FROM ".MAIN_DB_PREFIX."tva as v";
                $sqlmid .= " WHERE v.rowid=".((int) $val["paymentvatid"]);
                $ref = $langs->transnoentitiesnoconv("PaymentVat");
            } elseif ($typerecord == 'payment_donation') {
                $sqlmid = 'SELECT payd.fk_donation as ref';
                $sqlmid .= " FROM ".MAIN_DB_PREFIX."payment_donation as payd";
                $sqlmid .= " WHERE payd.fk_donation=".((int) $val["paymentdonationid"]);
                $ref = $langs->transnoentitiesnoconv("Donation");
            } elseif ($typerecord == 'payment_loan') {
                $sqlmid = 'SELECT l.rowid as ref';
                $sqlmid .= " FROM ".MAIN_DB_PREFIX."payment_loan as l";
                $sqlmid .= " WHERE l.rowid=".((int) $val["paymentloanid"]);
                $ref = $langs->transnoentitiesnoconv("LoanPayment");
            } elseif ($typerecord == 'payment_various') {
                $sqlmid = 'SELECT v.rowid as ref';
                $sqlmid .= " FROM ".MAIN_DB_PREFIX."payment_various as v";
                $sqlmid .= " WHERE v.rowid=".((int) $val["paymentvariousid"]);
                $ref = $langs->transnoentitiesnoconv("VariousPayment");
            }
            // Add warning
            if (empty($sqlmid)) {
                dol_syslog("Found a typerecord=".$typerecord." not supported", LOG_WARNING);
            }

            if ($sqlmid) {
                dol_syslog("accountancy/journal/bankjournal.php::sqlmid=".$sqlmid, LOG_DEBUG);
                $resultmid = $db->query($sqlmid);
                if ($resultmid) {
                    while ($objmid = $db->fetch_object($resultmid)) {
                        $ref .= ' '.$objmid->ref;
                    }
                } else {
                    dol_print_error($db);
                }
            }

            $ref = dol_trunc($langs->transnoentitiesnoconv("BankId").' '.$val['fk_bank'].' - '.$ref, 295); // 295 + 3 dots (...) is < than max size of 300
            return $ref;
        }
    }

    /**
     * \file        htdocs/accountancy/journal/expensereportsjournal.php
     * \ingroup     Accountancy (Double entries)
     * \brief       Page with expense reports journal
     */
    public function expensereportjournal()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;
        global $mysoc;

// Load translation files required by the page
        $langs->loadLangs(array("commercial", "compta", "bills", "other", "accountancy", "trips", "errors"));

        $id_journal = GETPOSTINT('id_journal');
        $action = GETPOST('action', 'aZ09');

        $date_startmonth = GETPOST('date_startmonth');
        $date_startday = GETPOST('date_startday');
        $date_startyear = GETPOST('date_startyear');
        $date_endmonth = GETPOST('date_endmonth');
        $date_endday = GETPOST('date_endday');
        $date_endyear = GETPOST('date_endyear');
        $in_bookkeeping = GETPOST('in_bookkeeping');
        if ($in_bookkeeping == '') {
            $in_bookkeeping = 'notyet';
        }

        $now = dol_now();

        $hookmanager->initHooks(array('expensereportsjournal'));
        $parameters = array();

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

        $error = 0;
        $errorforinvoice = array();


        /*
         * Actions
         */

        $accountingaccount = new AccountingAccount($db);

// Get information of journal
        $accountingjournalstatic = new AccountingJournal($db);
        $accountingjournalstatic->fetch($id_journal);
        $journal = $accountingjournalstatic->code;
        $journal_label = $accountingjournalstatic->label;

        $date_start = dol_mktime(0, 0, 0, $date_startmonth, $date_startday, $date_startyear);
        $date_end = dol_mktime(23, 59, 59, $date_endmonth, $date_endday, $date_endyear);

        if (empty($date_startmonth)) {
            // Period by default on transfer
            $dates = getDefaultDatesForTransfer();
            $date_start = $dates['date_start'];
            $pastmonthyear = $dates['pastmonthyear'];
            $pastmonth = $dates['pastmonth'];
        }
        if (empty($date_endmonth)) {
            // Period by default on transfer
            $dates = getDefaultDatesForTransfer();
            $date_end = $dates['date_end'];
            $pastmonthyear = $dates['pastmonthyear'];
            $pastmonth = $dates['pastmonth'];
        }

        if (!GETPOSTISSET('date_startmonth') && (empty($date_start) || empty($date_end))) { // We define date_start and date_end, only if we did not submit the form
            $date_start = dol_get_first_day($pastmonthyear, $pastmonth, false);
            $date_end = dol_get_last_day($pastmonthyear, $pastmonth, false);
        }

        $sql = "SELECT er.rowid, er.ref, er.date_debut as de,";
        $sql .= " erd.rowid as erdid, erd.comments, erd.total_ht, erd.total_tva, erd.total_localtax1, erd.total_localtax2, erd.tva_tx, erd.total_ttc, erd.fk_code_ventilation, erd.vat_src_code, ";
        $sql .= " u.rowid as uid, u.firstname, u.lastname, u.accountancy_code as user_accountancy_account,";
        $sql .= " f.accountancy_code, aa.rowid as fk_compte, aa.account_number as compte, aa.label as label_compte";
        $parameters = array();
        $reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
        $sql .= $hookmanager->resPrint;
        $sql .= " FROM " . MAIN_DB_PREFIX . "expensereport_det as erd";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_type_fees as f ON f.id = erd.fk_c_type_fees";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_account as aa ON aa.rowid = erd.fk_code_ventilation";
        $sql .= " JOIN " . MAIN_DB_PREFIX . "expensereport as er ON er.rowid = erd.fk_expensereport";
        $sql .= " JOIN " . MAIN_DB_PREFIX . "user as u ON u.rowid = er.fk_user_author";
        $parameters = array();
        $reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters); // Note that $action and $object may have been modified by hook
        $sql .= $hookmanager->resPrint;
        $sql .= " WHERE er.fk_statut > 0";
        $sql .= " AND erd.fk_code_ventilation > 0";
        $sql .= " AND er.entity IN (" . getEntity('expensereport', 0) . ")"; // We don't share object for accountancy
        if ($date_start && $date_end) {
            $sql .= " AND er.date_debut >= '" . $db->idate($date_start) . "' AND er.date_debut <= '" . $db->idate($date_end) . "'";
        }
// Define begin binding date
        if (getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) {
            $sql .= " AND er.date_debut >= '" . $db->idate(getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) . "'";
        }
// Already in bookkeeping or not
        if ($in_bookkeeping == 'already') {
            $sql .= " AND er.rowid IN (SELECT fk_doc FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping as ab  WHERE ab.doc_type='expense_report')";
        }
        if ($in_bookkeeping == 'notyet') {
            $sql .= " AND er.rowid NOT IN (SELECT fk_doc FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping as ab  WHERE ab.doc_type='expense_report')";
        }
        $parameters = array();
        $reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
        $sql .= $hookmanager->resPrint;
        $sql .= " ORDER BY er.date_debut";

        dol_syslog('accountancy/journal/expensereportsjournal.php', LOG_DEBUG);
        $result = $db->query($sql);
        if ($result) {
            $taber = array();
            $tabht = array();
            $tabtva = array();
            $def_tva = array();
            $tabttc = array();
            $tablocaltax1 = array();
            $tablocaltax2 = array();
            $tabuser = array();

            $num = $db->num_rows($result);

            // Variables
            $account_salary = getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT', 'NotDefined');
            $account_vat = getDolGlobalString('ACCOUNTING_VAT_BUY_ACCOUNT', 'NotDefined');

            $i = 0;
            while ($i < $num) {
                $obj = $db->fetch_object($result);

                // Controls
                $compta_user = (!empty($obj->user_accountancy_account)) ? $obj->user_accountancy_account : $account_salary;
                $compta_fees = $obj->compte;

                $vatdata = getTaxesFromId($obj->tva_tx . ($obj->vat_src_code ? ' (' . $obj->vat_src_code . ')' : ''), $mysoc, $mysoc, 0);
                $compta_tva = (!empty($vatdata['accountancy_code_buy']) ? $vatdata['accountancy_code_buy'] : $account_vat);
                $compta_localtax1 = (!empty($vatdata['accountancy_code_buy']) ? $vatdata['accountancy_code_buy'] : $cpttva);
                $compta_localtax2 = (!empty($vatdata['accountancy_code_buy']) ? $vatdata['accountancy_code_buy'] : $cpttva);

                // Define array to display all VAT rates that use this accounting account $compta_tva
                if (price2num($obj->tva_tx) || !empty($obj->vat_src_code)) {
                    $def_tva[$obj->rowid][$compta_tva][vatrate($obj->tva_tx) . ($obj->vat_src_code ? ' (' . $obj->vat_src_code . ')' : '')] = (vatrate($obj->tva_tx) . ($obj->vat_src_code ? ' (' . $obj->vat_src_code . ')' : ''));
                }

                $taber[$obj->rowid]["date"] = $db->jdate($obj->de);
                $taber[$obj->rowid]["ref"] = $obj->ref;
                $taber[$obj->rowid]["comments"] = $obj->comments;
                $taber[$obj->rowid]["fk_expensereportdet"] = $obj->erdid;

                // Avoid warnings
                if (!isset($tabttc[$obj->rowid][$compta_user])) {
                    $tabttc[$obj->rowid][$compta_user] = 0;
                }
                if (!isset($tabht[$obj->rowid][$compta_fees])) {
                    $tabht[$obj->rowid][$compta_fees] = 0;
                }
                if (!isset($tabtva[$obj->rowid][$compta_tva])) {
                    $tabtva[$obj->rowid][$compta_tva] = 0;
                }
                if (!isset($tablocaltax1[$obj->rowid][$compta_localtax1])) {
                    $tablocaltax1[$obj->rowid][$compta_localtax1] = 0;
                }
                if (!isset($tablocaltax2[$obj->rowid][$compta_localtax2])) {
                    $tablocaltax2[$obj->rowid][$compta_localtax2] = 0;
                }

                $tabttc[$obj->rowid][$compta_user] += $obj->total_ttc;
                $tabht[$obj->rowid][$compta_fees] += $obj->total_ht;
                $tabtva[$obj->rowid][$compta_tva] += $obj->total_tva;
                $tablocaltax1[$obj->rowid][$compta_localtax1] += $obj->total_localtax1;
                $tablocaltax2[$obj->rowid][$compta_localtax2] += $obj->total_localtax2;
                $tabuser[$obj->rowid] = array(
                    'id' => $obj->uid,
                    'name' => dolGetFirstLastname($obj->firstname, $obj->lastname),
                    'user_accountancy_code' => $obj->user_accountancy_account
                );

                $i++;
            }
        } else {
            dol_print_error($db);
        }

// Load all unbound lines
        $sql = "SELECT fk_expensereport, COUNT(erd.rowid) as nb";
        $sql .= " FROM " . MAIN_DB_PREFIX . "expensereport_det as erd";
        $sql .= " WHERE erd.fk_code_ventilation <= 0";
        $sql .= " AND erd.total_ttc <> 0";
        $sql .= " AND fk_expensereport IN (" . $db->sanitize(implode(",", array_keys($taber))) . ")";
        $sql .= " GROUP BY fk_expensereport";
        $resql = $db->query($sql);

        $num = $db->num_rows($resql);
        $i = 0;
        while ($i < $num) {
            $obj = $db->fetch_object($resql);
            if ($obj->nb > 0) {
                $errorforinvoice[$obj->fk_expensereport] = 'somelinesarenotbound';
            }
            $i++;
        }

// Bookkeeping Write
        if ($action == 'writebookkeeping' && !$error) {
            $now = dol_now();
            $error = 0;

            $accountingaccountexpense = new AccountingAccount($db);
            $accountingaccountexpense->fetch(null, getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT'), true);

            foreach ($taber as $key => $val) {      // Loop on each expense report
                $errorforline = 0;

                $totalcredit = 0;
                $totaldebit = 0;

                $db->begin();

                // Error if some lines are not binded/ready to be journalized
                if (!empty($errorforinvoice[$key]) && $errorforinvoice[$key] == 'somelinesarenotbound') {
                    $error++;
                    $errorforline++;
                    setEventMessages($langs->trans('ErrorInvoiceContainsLinesNotYetBounded', $val['ref']), null, 'errors');
                }

                // Thirdparty
                if (!$errorforline) {
                    foreach ($tabttc[$key] as $k => $mt) {
                        if ($mt) {
                            $bookkeeping = new BookKeeping($db);
                            $bookkeeping->doc_date = $val["date"];
                            $bookkeeping->doc_ref = $val["ref"];
                            $bookkeeping->date_creation = $now;
                            $bookkeeping->doc_type = 'expense_report';
                            $bookkeeping->fk_doc = $key;
                            $bookkeeping->fk_docdet = $val["fk_expensereportdet"];

                            $bookkeeping->subledger_account = $tabuser[$key]['user_accountancy_code'];
                            $bookkeeping->subledger_label = $tabuser[$key]['name'];

                            $bookkeeping->numero_compte = getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT');
                            $bookkeeping->label_compte = $accountingaccountexpense->label;

                            $bookkeeping->label_operation = $tabuser[$key]['name'];
                            $bookkeeping->montant = $mt;
                            $bookkeeping->sens = ($mt >= 0) ? 'C' : 'D';
                            $bookkeeping->debit = ($mt <= 0) ? -$mt : 0;
                            $bookkeeping->credit = ($mt > 0) ? $mt : 0;
                            $bookkeeping->code_journal = $journal;
                            $bookkeeping->journal_label = $langs->transnoentities($journal_label);
                            $bookkeeping->fk_user_author = $user->id;
                            $bookkeeping->entity = $conf->entity;

                            $totaldebit += $bookkeeping->debit;
                            $totalcredit += $bookkeeping->credit;

                            $result = $bookkeeping->create($user);
                            if ($result < 0) {
                                if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists') {  // Already exists
                                    $error++;
                                    $errorforline++;
                                    $errorforinvoice[$key] = 'alreadyjournalized';
                                    //setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->fk_doc.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
                                } else {
                                    $error++;
                                    $errorforline++;
                                    $errorforinvoice[$key] = 'other';
                                    setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
                                }
                            }
                        }
                    }
                }

                // Fees
                if (!$errorforline) {
                    foreach ($tabht[$key] as $k => $mt) {
                        if ($mt) {
                            // get compte id and label
                            if ($accountingaccount->fetch(null, $k, true)) {
                                $bookkeeping = new BookKeeping($db);
                                $bookkeeping->doc_date = $val["date"];
                                $bookkeeping->doc_ref = $val["ref"];
                                $bookkeeping->date_creation = $now;
                                $bookkeeping->doc_type = 'expense_report';
                                $bookkeeping->fk_doc = $key;
                                $bookkeeping->fk_docdet = $val["fk_expensereportdet"];

                                $bookkeeping->subledger_account = '';
                                $bookkeeping->subledger_label = '';

                                $bookkeeping->numero_compte = $k;
                                $bookkeeping->label_compte = $accountingaccount->label;

                                $bookkeeping->label_operation = $accountingaccount->label;
                                $bookkeeping->montant = $mt;
                                $bookkeeping->sens = ($mt < 0) ? 'C' : 'D';
                                $bookkeeping->debit = ($mt > 0) ? $mt : 0;
                                $bookkeeping->credit = ($mt <= 0) ? -$mt : 0;
                                $bookkeeping->code_journal = $journal;
                                $bookkeeping->journal_label = $langs->transnoentities($journal_label);
                                $bookkeeping->fk_user_author = $user->id;
                                $bookkeeping->entity = $conf->entity;

                                $totaldebit += $bookkeeping->debit;
                                $totalcredit += $bookkeeping->credit;

                                $result = $bookkeeping->create($user);
                                if ($result < 0) {
                                    if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists') {  // Already exists
                                        $error++;
                                        $errorforline++;
                                        $errorforinvoice[$key] = 'alreadyjournalized';
                                        //setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->fk_doc.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
                                    } else {
                                        $error++;
                                        $errorforline++;
                                        $errorforinvoice[$key] = 'other';
                                        setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
                                    }
                                }
                            }
                        }
                    }
                }

                // VAT
                if (!$errorforline) {
                    $listoftax = array(0, 1, 2);
                    foreach ($listoftax as $numtax) {
                        $arrayofvat = $tabtva;
                        if ($numtax == 1) {
                            $arrayofvat = $tablocaltax1;
                        }
                        if ($numtax == 2) {
                            $arrayofvat = $tablocaltax2;
                        }

                        foreach ($arrayofvat[$key] as $k => $mt) {
                            if ($mt) {
                                $accountingaccount->fetch(null, $k, true);  // TODO Use a cache for label
                                $account_label = $accountingaccount->label;

                                // get compte id and label
                                $bookkeeping = new BookKeeping($db);
                                $bookkeeping->doc_date = $val["date"];
                                $bookkeeping->doc_ref = $val["ref"];
                                $bookkeeping->date_creation = $now;
                                $bookkeeping->doc_type = 'expense_report';
                                $bookkeeping->fk_doc = $key;
                                $bookkeeping->fk_docdet = $val["fk_expensereportdet"];

                                $bookkeeping->subledger_account = '';
                                $bookkeeping->subledger_label = '';

                                $bookkeeping->numero_compte = $k;
                                $bookkeeping->label_compte = $account_label;

                                $bookkeeping->label_operation = $langs->trans("VAT") . ' ' . implode(', ', $def_tva[$key][$k]) . ' %';
                                $bookkeeping->montant = $mt;
                                $bookkeeping->sens = ($mt < 0) ? 'C' : 'D';
                                $bookkeeping->debit = ($mt > 0) ? $mt : 0;
                                $bookkeeping->credit = ($mt <= 0) ? -$mt : 0;
                                $bookkeeping->code_journal = $journal;
                                $bookkeeping->journal_label = $langs->transnoentities($journal_label);
                                $bookkeeping->fk_user_author = $user->id;
                                $bookkeeping->entity = $conf->entity;

                                $totaldebit += $bookkeeping->debit;
                                $totalcredit += $bookkeeping->credit;

                                $result = $bookkeeping->create($user);
                                if ($result < 0) {
                                    if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists') {  // Already exists
                                        $error++;
                                        $errorforline++;
                                        $errorforinvoice[$key] = 'alreadyjournalized';
                                        //setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->fk_doc.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
                                    } else {
                                        $error++;
                                        $errorforline++;
                                        $errorforinvoice[$key] = 'other';
                                        setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
                                    }
                                }
                            }
                        }
                    }
                }

                // Protection against a bug on lines before
                if (!$errorforline && (price2num($totaldebit, 'MT') != price2num($totalcredit, 'MT'))) {
                    $error++;
                    $errorforline++;
                    $errorforinvoice[$key] = 'amountsnotbalanced';
                    setEventMessages('We tried to insert a non balanced transaction in book for ' . $val["ref"] . '. Canceled. Surely a bug.', null, 'errors');
                }

                if (!$errorforline) {
                    $db->commit();
                } else {
                    $db->rollback();

                    if ($error >= 10) {
                        setEventMessages($langs->trans("ErrorTooManyErrorsProcessStopped"), null, 'errors');
                        break; // Break in the foreach
                    }
                }
            }

            $tabpay = $taber;

            if (empty($error) && count($tabpay) > 0) {
                setEventMessages($langs->trans("GeneralLedgerIsWritten"), null, 'mesgs');
            } elseif (count($tabpay) == $error) {
                setEventMessages($langs->trans("NoNewRecordSaved"), null, 'warnings');
            } else {
                setEventMessages($langs->trans("GeneralLedgerSomeRecordWasNotRecorded"), null, 'warnings');
            }

            $action = '';

            // Must reload data, so we make a redirect
            if (count($tabpay) != $error) {
                $param = 'id_journal=' . $id_journal;
                $param .= '&date_startday=' . $date_startday;
                $param .= '&date_startmonth=' . $date_startmonth;
                $param .= '&date_startyear=' . $date_startyear;
                $param .= '&date_endday=' . $date_endday;
                $param .= '&date_endmonth=' . $date_endmonth;
                $param .= '&date_endyear=' . $date_endyear;
                $param .= '&in_bookkeeping=' . $in_bookkeeping;

                header("Location: " . $_SERVER['PHP_SELF'] . ($param ? '?' . $param : ''));
                exit;
            }
        }


        /*
         * View
         */

        $form = new Form($db);

        $userstatic = new User($db);

// Export
        if ($action == 'exportcsv' && !$error) {        // ISO and not UTF8 !
            $sep = getDolGlobalString('ACCOUNTING_EXPORT_SEPARATORCSV');

            $filename = 'journal';
            $type_export = 'journal';
            include DOL_DOCUMENT_ROOT . '/accountancy/tpl/export_journal.tpl.php';

            // CSV header line
            print '"' . $langs->transnoentitiesnoconv("Date") . '"' . $sep;
            print '"' . $langs->transnoentitiesnoconv("Piece") . '"' . $sep;
            print '"' . $langs->transnoentitiesnoconv("AccountAccounting") . '"' . $sep;
            print '"' . $langs->transnoentitiesnoconv("LabelOperation") . '"' . $sep;
            print '"' . $langs->transnoentitiesnoconv("AccountingDebit") . '"' . $sep;
            print '"' . $langs->transnoentitiesnoconv("AccountingCredit") . '"' . $sep;
            print "\n";

            foreach ($taber as $key => $val) {
                $date = dol_print_date($val["date"], 'day');

                $userstatic->id = $tabuser[$key]['id'];
                $userstatic->name = $tabuser[$key]['name'];

                // Fees
                foreach ($tabht[$key] as $k => $mt) {
                    $accountingaccount = new AccountingAccount($db);
                    $accountingaccount->fetch(null, $k, true);
                    if ($mt) {
                        print '"' . $date . '"' . $sep;
                        print '"' . $val["ref"] . '"' . $sep;
                        print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
                        print '"' . dol_trunc($accountingaccount->label, 32) . '"' . $sep;
                        print '"' . ($mt >= 0 ? price($mt) : '') . '"' . $sep;
                        print '"' . ($mt < 0 ? price(-$mt) : '') . '"';
                        print "\n";
                    }
                }

                // VAT
                foreach ($tabtva[$key] as $k => $mt) {
                    if ($mt) {
                        print '"' . $date . '"' . $sep;
                        print '"' . $val["ref"] . '"' . $sep;
                        print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
                        print '"' . dol_trunc($langs->trans("VAT")) . '"' . $sep;
                        print '"' . ($mt >= 0 ? price($mt) : '') . '"' . $sep;
                        print '"' . ($mt < 0 ? price(-$mt) : '') . '"';
                        print "\n";
                    }
                }

                // Third party
                foreach ($tabttc[$key] as $k => $mt) {
                    print '"' . $date . '"' . $sep;
                    print '"' . $val["ref"] . '"' . $sep;
                    print '"' . length_accounta(html_entity_decode($k)) . '"' . $sep;
                    print '"' . dol_trunc($userstatic->name) . '"' . $sep;
                    print '"' . ($mt < 0 ? price(-$mt) : '') . '"' . $sep;
                    print '"' . ($mt >= 0 ? price($mt) : '') . '"';
                }
                print "\n";
            }
        }

        if (empty($action) || $action == 'view') {
            $title = $langs->trans("GenerationOfAccountingEntries") . ' - ' . $accountingjournalstatic->getNomUrl(0, 2, 1, '', 1);

            llxHeader('', dol_string_nohtmltag($title));

            $nom = $title;
            $nomlink = '';
            $periodlink = '';
            $exportlink = '';
            $builddate = dol_now();
            $description = $langs->trans("DescJournalOnlyBindedVisible") . '<br>';

            $listofchoices = array('notyet' => $langs->trans("NotYetInGeneralLedger"), 'already' => $langs->trans("AlreadyInGeneralLedger"));
            $period = $form->selectDate($date_start ? $date_start : -1, 'date_start', 0, 0, 0, '', 1, 0) . ' - ' . $form->selectDate($date_end ? $date_end : -1, 'date_end', 0, 0, 0, '', 1, 0);
            $period .= ' -  ' . $langs->trans("JournalizationInLedgerStatus") . ' ' . $form->selectarray('in_bookkeeping', $listofchoices, $in_bookkeeping, 1);

            $varlink = 'id_journal=' . $id_journal;

            journalHead($nom, $nomlink, $period, $periodlink, $description, $builddate, $exportlink, array('action' => ''), '', $varlink);

            if (getDolGlobalString('ACCOUNTANCY_FISCAL_PERIOD_MODE') != 'blockedonclosed') {
                // Test that setup is complete (we are in accounting, so test on entity is always on $conf->entity only, no sharing allowed)
                // Fiscal period test
                $sql = "SELECT COUNT(rowid) as nb FROM " . MAIN_DB_PREFIX . "accounting_fiscalyear WHERE entity = " . ((int) $conf->entity);
                $resql = $db->query($sql);
                if ($resql) {
                    $obj = $db->fetch_object($resql);
                    if ($obj->nb == 0) {
                        print '<br><div class="warning">' . img_warning() . ' ' . $langs->trans("TheFiscalPeriodIsNotDefined");
                        $desc = ' : ' . $langs->trans("AccountancyAreaDescFiscalPeriod", 4, '{link}');
                        $desc = str_replace('{link}', '<strong>' . $langs->transnoentitiesnoconv("MenuAccountancy") . '-' . $langs->transnoentitiesnoconv("Setup") . "-" . $langs->transnoentitiesnoconv("FiscalPeriod") . '</strong>', $desc);
                        print $desc;
                        print '</div>';
                    }
                } else {
                    dol_print_error($db);
                }
            }

            // Button to write into Ledger
            if (!getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT') || getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT') == '-1') {
                print '<br><div class="warning">' . img_warning() . ' ' . $langs->trans("SomeMandatoryStepsOfSetupWereNotDone");
                $desc = ' : ' . $langs->trans("AccountancyAreaDescMisc", 4, '{link}');
                $desc = str_replace('{link}', '<strong>' . $langs->transnoentitiesnoconv("MenuAccountancy") . '-' . $langs->transnoentitiesnoconv("Setup") . "-" . $langs->transnoentitiesnoconv("MenuDefaultAccounts") . '</strong>', $desc);
                print $desc;
                print '</div>';
            }
            print '<br><div class="tabsAction tabsActionNoBottom centerimp">';

            if (getDolGlobalString('ACCOUNTING_ENABLE_EXPORT_DRAFT_JOURNAL') && $in_bookkeeping == 'notyet') {
                print '<input type="button" class="butAction" name="exportcsv" value="' . $langs->trans("ExportDraftJournal") . '" onclick="launch_export();" />';
            }
            if (!getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT') || getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT') == '-1') {
                print '<input type="button" class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans("SomeMandatoryStepsOfSetupWereNotDone")) . '" value="' . $langs->trans("WriteBookKeeping") . '" />';
            } else {
                if ($in_bookkeeping == 'notyet') {
                    print '<input type="button" class="butAction" name="writebookkeeping" value="' . $langs->trans("WriteBookKeeping") . '" onclick="writebookkeeping();" />';
                } else {
                    print '<a href="#" class="butActionRefused classfortooltip" name="writebookkeeping">' . $langs->trans("WriteBookKeeping") . '</a>';
                }
            }
            print '</div>';

            // TODO Avoid using js. We can use a direct link with $param
            print '
	<script type="text/javascript">
		function launch_export() {
			$("div.fiche form input[name=\"action\"]").val("exportcsv");
			$("div.fiche form input[type=\"submit\"]").click();
			$("div.fiche form input[name=\"action\"]").val("");
		}
		function writebookkeeping() {
			console.log("click on writebookkeeping");
			$("div.fiche form input[name=\"action\"]").val("writebookkeeping");
			$("div.fiche form input[type=\"submit\"]").click();
			$("div.fiche form input[name=\"action\"]").val("");
		}
	</script>';

            /*
             * Show result array
             */
            print '<br>';

            $i = 0;
            print '<div class="div-table-responsive">';
            print "<table class=\"noborder\" width=\"100%\">";
            print "<tr class=\"liste_titre\">";
            print "<td>" . $langs->trans("Date") . "</td>";
            print "<td>" . $langs->trans("Piece") . ' (' . $langs->trans("ExpenseReportRef") . ")</td>";
            print "<td>" . $langs->trans("AccountAccounting") . "</td>";
            print "<td>" . $langs->trans("SubledgerAccount") . "</td>";
            print "<td>" . $langs->trans("LabelOperation") . "</td>";
            print '<td class="right">' . $langs->trans("AccountingDebit") . "</td>";
            print '<td class="right">' . $langs->trans("AccountingCredit") . "</td>";
            print "</tr>\n";

            $i = 0;

            $expensereportstatic = new ExpenseReport($db);
            $expensereportlinestatic = new ExpenseReportLine($db);

            foreach ($taber as $key => $val) {
                $expensereportstatic->id = $key;
                $expensereportstatic->ref = $val["ref"];
                $expensereportlinestatic->comments = html_entity_decode(dol_trunc($val["comments"], 32));

                $date = dol_print_date($val["date"], 'day');

                if ($errorforinvoice[$key] == 'somelinesarenotbound') {
                    print '<tr class="oddeven">';
                    print "<!-- Some lines are not bound -->";
                    print "<td>" . $date . "</td>";
                    print "<td>" . $expensereportstatic->getNomUrl(1) . "</td>";
                    // Account
                    print "<td>";
                    print '<span class="error">' . $langs->trans('ErrorInvoiceContainsLinesNotYetBoundedShort', $val['ref']) . '</span>';
                    print '</td>';
                    // Subledger account
                    print "<td>";
                    print '</td>';
                    print "<td>";
                    print "</td>";
                    print '<td class="right"></td>';
                    print '<td class="right"></td>';
                    print "</tr>";

                    $i++;
                }

                // Fees
                foreach ($tabht[$key] as $k => $mt) {
                    $accountingaccount = new AccountingAccount($db);
                    $accountingaccount->fetch(null, $k, true);

                    if ($mt) {
                        print '<tr class="oddeven">';
                        print "<!-- Fees -->";
                        print "<td>" . $date . "</td>";
                        print "<td>" . $expensereportstatic->getNomUrl(1) . "</td>";
                        $userstatic->id = $tabuser[$key]['id'];
                        $userstatic->name = $tabuser[$key]['name'];
                        // Account
                        print "<td>";
                        $accountoshow = length_accountg($k);
                        if (($accountoshow == "") || $accountoshow == 'NotDefined') {
                            print '<span class="error">' . $langs->trans("FeeAccountNotDefined") . '</span>';
                        } else {
                            print $accountoshow;
                        }
                        print '</td>';
                        // Subledger account
                        print "<td>";
                        print '</td>';
                        $userstatic->id = $tabuser[$key]['id'];
                        $userstatic->name = $tabuser[$key]['name'];
                        print "<td>" . $userstatic->getNomUrl(0, 'user', 16) . ' - ' . $accountingaccount->label . "</td>";
                        print '<td class="right nowraponall amount">' . ($mt >= 0 ? price($mt) : '') . "</td>";
                        print '<td class="right nowraponall amount">' . ($mt < 0 ? price(-$mt) : '') . "</td>";
                        print "</tr>";

                        $i++;
                    }
                }

                // Third party
                foreach ($tabttc[$key] as $k => $mt) {
                    $userstatic->id = $tabuser[$key]['id'];
                    $userstatic->name = $tabuser[$key]['name'];

                    print '<tr class="oddeven">';
                    print "<!-- Thirdparty -->";
                    print "<td>" . $date . "</td>";
                    print "<td>" . $expensereportstatic->getNomUrl(1) . "</td>";
                    // Account
                    print "<td>";
                    $accountoshow = length_accountg(getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT'));
                    if (($accountoshow == "") || $accountoshow == 'NotDefined') {
                        print '<span class="error">' . $langs->trans("MainAccountForUsersNotDefined") . '</span>';
                    } else {
                        print $accountoshow;
                    }
                    print "</td>";
                    // Subledger account
                    print "<td>";
                    $accountoshow = length_accounta($k);
                    if (($accountoshow == "") || $accountoshow == 'NotDefined') {
                        print '<span class="error">' . $langs->trans("UserAccountNotDefined") . '</span>';
                    } else {
                        print $accountoshow;
                    }
                    print '</td>';
                    print "<td>" . $userstatic->getNomUrl(0, 'user', 16) . ' - ' . $langs->trans("SubledgerAccount") . "</td>";
                    print '<td class="right nowraponall amount">' . ($mt < 0 ? price(-$mt) : '') . "</td>";
                    print '<td class="right nowraponall amount">' . ($mt >= 0 ? price($mt) : '') . "</td>";
                    print "</tr>";

                    $i++;
                }

                // VAT
                $listoftax = array(0, 1, 2);
                foreach ($listoftax as $numtax) {
                    $arrayofvat = $tabtva;
                    if ($numtax == 1) {
                        $arrayofvat = $tablocaltax1;
                    }
                    if ($numtax == 2) {
                        $arrayofvat = $tablocaltax2;
                    }

                    foreach ($arrayofvat[$key] as $k => $mt) {
                        if ($mt) {
                            print '<tr class="oddeven">';
                            print "<!-- VAT -->";
                            print "<td>" . $date . "</td>";
                            print "<td>" . $expensereportstatic->getNomUrl(1) . "</td>";
                            // Account
                            print "<td>";
                            $accountoshow = length_accountg($k);
                            if (($accountoshow == "") || $accountoshow == 'NotDefined') {
                                print '<span class="error">' . $langs->trans("VATAccountNotDefined") . '</span>';
                            } else {
                                print $accountoshow;
                            }
                            print "</td>";
                            // Subledger account
                            print "<td>";
                            print '</td>';
                            print "<td>" . $userstatic->getNomUrl(0, 'user', 16) . ' - ' . $langs->trans("VAT") . ' ' . implode(', ', $def_tva[$key][$k]) . ' %' . ($numtax ? ' - Localtax ' . $numtax : '');
                            print "</td>";
                            print '<td class="right nowraponall amount">' . ($mt >= 0 ? price($mt) : '') . "</td>";
                            print '<td class="right nowraponall amount">' . ($mt < 0 ? price(-$mt) : '') . "</td>";
                            print "</tr>";

                            $i++;
                        }
                    }
                }
            }

            if (!$i) {
                $colspan = 7;
                print '<tr class="oddeven"><td colspan="' . $colspan . '"><span class="opacitymedium">' . $langs->trans("NoRecordFound") . '</span></td></tr>';
            }

            print "</table>";
            print '</div>';

            // End of page
            llxFooter();
        }
        $db->close();
    }

    /**
     * \file        htdocs/accountancy/journal/purchasesjournal.php
     * \ingroup     Accountancy (Double entries)
     * \brief       Page with purchases journal
     */
    public function purchasesjournal()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;
        global $mysoc;

// Load translation files required by the page
        $langs->loadLangs(array("commercial", "compta", "bills", "other", "accountancy", "errors"));

        $id_journal = GETPOSTINT('id_journal');
        $action = GETPOST('action', 'aZ09');

        $date_startmonth = GETPOST('date_startmonth');
        $date_startday = GETPOST('date_startday');
        $date_startyear = GETPOST('date_startyear');
        $date_endmonth = GETPOST('date_endmonth');
        $date_endday = GETPOST('date_endday');
        $date_endyear = GETPOST('date_endyear');
        $in_bookkeeping = GETPOST('in_bookkeeping');
        if ($in_bookkeeping == '') {
            $in_bookkeeping = 'notyet';
        }

        $now = dol_now();

        $hookmanager->initHooks(array('purchasesjournal'));
        $parameters = array();

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

        $error = 0;


        /*
         * Actions
         */

        $reshook = $hookmanager->executeHooks('doActions', $parameters, $user, $action); // Note that $action and $object may have been modified by some hooks

        $accountingaccount = new AccountingAccount($db);

// Get information of journal
        $accountingjournalstatic = new AccountingJournal($db);
        $accountingjournalstatic->fetch($id_journal);
        $journal = $accountingjournalstatic->code;
        $journal_label = $accountingjournalstatic->label;

        $date_start = dol_mktime(0, 0, 0, $date_startmonth, $date_startday, $date_startyear);
        $date_end = dol_mktime(23, 59, 59, $date_endmonth, $date_endday, $date_endyear);

        if (empty($date_startmonth)) {
            // Period by default on transfer
            $dates = getDefaultDatesForTransfer();
            $date_start = $dates['date_start'];
            $pastmonthyear = $dates['pastmonthyear'];
            $pastmonth = $dates['pastmonth'];
        }
        if (empty($date_endmonth)) {
            // Period by default on transfer
            $dates = getDefaultDatesForTransfer();
            $date_end = $dates['date_end'];
            $pastmonthyear = $dates['pastmonthyear'];
            $pastmonth = $dates['pastmonth'];
        }

        if (!GETPOSTISSET('date_startmonth') && (empty($date_start) || empty($date_end))) { // We define date_start and date_end, only if we did not submit the form
            $date_start = dol_get_first_day($pastmonthyear, $pastmonth, false);
            $date_end = dol_get_last_day($pastmonthyear, $pastmonth, false);
        }

        $sql = "SELECT f.rowid, f.ref as ref, f.type, f.datef as df, f.libelle as label, f.ref_supplier, f.date_lim_reglement as dlr, f.close_code, f.vat_reverse_charge,";
        $sql .= " fd.rowid as fdid, fd.description, fd.product_type, fd.total_ht, fd.tva as total_tva, fd.total_localtax1, fd.total_localtax2, fd.tva_tx, fd.total_ttc, fd.vat_src_code, fd.info_bits,";
        $sql .= " p.default_vat_code AS product_buy_default_vat_code, p.tva_tx as product_buy_vat, p.localtax1_tx as product_buy_localvat1, p.localtax2_tx as product_buy_localvat2,";
        $sql .= " co.code as country_code, co.label as country_label,";
        $sql .= " s.rowid as socid, s.nom as name, s.fournisseur, s.code_client, s.code_fournisseur, s.fk_pays,";
        if (getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
            $sql .= " spe.accountancy_code_customer as code_compta,";
            $sql .= " spe.accountancy_code_supplier as code_compta_fournisseur,";
        } else {
            $sql .= " s.code_compta as code_compta,";
            $sql .= " s.code_compta_fournisseur,";
        }
        if (getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED')) {
            $sql .= " ppe.accountancy_code_buy,";
        } else {
            $sql .= " p.accountancy_code_buy,";
        }
        $sql .= " aa.rowid as fk_compte, aa.account_number as compte, aa.label as label_compte";
        $parameters = array();
        $reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
        $sql .= $hookmanager->resPrint;
        $sql .= " FROM " . MAIN_DB_PREFIX . "facture_fourn_det as fd";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product as p ON p.rowid = fd.fk_product";
        if (getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED')) {
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product_perentity as ppe ON ppe.fk_product = p.rowid AND ppe.entity = " . ((int) $conf->entity);
        }
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_account as aa ON aa.rowid = fd.fk_code_ventilation";
        $sql .= " JOIN " . MAIN_DB_PREFIX . "facture_fourn as f ON f.rowid = fd.fk_facture_fourn";
        $sql .= " JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid = f.fk_soc";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_country as co ON co.rowid = s.fk_pays ";
        if (getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_perentity as spe ON spe.fk_soc = s.rowid AND spe.entity = " . ((int) $conf->entity);
        }
        $parameters = array();
        $reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters); // Note that $action and $object may have been modified by hook
        $sql .= $hookmanager->resPrint;
        $sql .= " WHERE f.fk_statut > 0";
        $sql .= " AND fd.fk_code_ventilation > 0";
        $sql .= " AND f.entity IN (" . getEntity('facture_fourn', 0) . ")"; // We don't share object for accountancy
        if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {
            $sql .= " AND f.type IN (" . FactureFournisseur::TYPE_STANDARD . "," . FactureFournisseur::TYPE_REPLACEMENT . "," . FactureFournisseur::TYPE_CREDIT_NOTE . "," . FactureFournisseur::TYPE_SITUATION . ")";
        } else {
            $sql .= " AND f.type IN (" . FactureFournisseur::TYPE_STANDARD . "," . FactureFournisseur::TYPE_REPLACEMENT . "," . FactureFournisseur::TYPE_CREDIT_NOTE . "," . FactureFournisseur::TYPE_DEPOSIT . "," . FactureFournisseur::TYPE_SITUATION . ")";
        }
        if ($date_start && $date_end) {
            $sql .= " AND f.datef >= '" . $db->idate($date_start) . "' AND f.datef <= '" . $db->idate($date_end) . "'";
        }
// Define begin binding date
        if (getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) {
            $sql .= " AND f.datef >= '" . $db->idate(getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) . "'";
        }
// Already in bookkeeping or not
        if ($in_bookkeeping == 'already') {
            $sql .= " AND f.rowid IN (SELECT fk_doc FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping as ab WHERE ab.doc_type='supplier_invoice')";
        }
        if ($in_bookkeeping == 'notyet') {
            $sql .= " AND f.rowid NOT IN (SELECT fk_doc FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping as ab WHERE ab.doc_type='supplier_invoice')";
        }
        $parameters = array();
        $reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
        $sql .= $hookmanager->resPrint;
        $sql .= " ORDER BY f.datef";

        dol_syslog('accountancy/journal/purchasesjournal.php', LOG_DEBUG);
        $result = $db->query($sql);
        if ($result) {
            $tabfac = array();
            $tabht = array();
            $tabtva = array();
            $def_tva = array();
            $tabttc = array();
            $tablocaltax1 = array();
            $tablocaltax2 = array();
            $tabcompany = array();
            $tabother = array();
            $tabrctva = array();
            $tabrclocaltax1 = array();
            $tabrclocaltax2 = array();
            $vatdata_cache = array();

            $num = $db->num_rows($result);

            // Variables
            $cptfour = getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER', 'NotDefined');
            $cpttva = getDolGlobalString('ACCOUNTING_VAT_BUY_ACCOUNT', 'NotDefined');
            $rcctva = getDolGlobalString('ACCOUNTING_VAT_BUY_REVERSE_CHARGES_CREDIT', 'NotDefined');
            $rcdtva = getDolGlobalString('ACCOUNTING_VAT_BUY_REVERSE_CHARGES_DEBIT', 'NotDefined');
            $country_code_in_EEC = getCountriesInEEC();     // This make a database call but there is a cache done into $conf->cache['country_code_in_EEC']

            $i = 0;
            while ($i < $num) {
                $obj = $db->fetch_object($result);

                // Controls
                $compta_soc = ($obj->code_compta_fournisseur != "") ? $obj->code_compta_fournisseur : $cptfour;

                $compta_prod = $obj->compte;
                if (empty($compta_prod)) {
                    if ($obj->product_type == 0) {
                        $compta_prod = getDolGlobalString('ACCOUNTING_PRODUCT_BUY_ACCOUNT', 'NotDefined');
                    } else {
                        $compta_prod = getDolGlobalString('ACCOUNTING_SERVICE_BUY_ACCOUNT', 'NotDefined');
                    }
                }

                $tax_id = $obj->tva_tx . ($obj->vat_src_code ? ' (' . $obj->vat_src_code . ')' : '');
                if (array_key_exists($tax_id, $vatdata_cache)) {
                    $vatdata = $vatdata_cache[$tax_id];
                } else {
                    $vatdata = getTaxesFromId($tax_id, $mysoc, $mysoc, 0);
                    $vatdata_cache[$tax_id] = $vatdata;
                }
                $compta_tva = (!empty($vatdata['accountancy_code_buy']) ? $vatdata['accountancy_code_buy'] : $cpttva);
                $compta_localtax1 = (!empty($vatdata['accountancy_code_buy']) ? $vatdata['accountancy_code_buy'] : $cpttva);
                $compta_localtax2 = (!empty($vatdata['accountancy_code_buy']) ? $vatdata['accountancy_code_buy'] : $cpttva);
                $compta_counterpart_tva_npr = getDolGlobalString('ACCOUNTING_COUNTERPART_VAT_NPR', 'NotDefined');

                // Define array to display all VAT rates that use this accounting account $compta_tva
                if (price2num($obj->tva_tx) || !empty($obj->vat_src_code)) {
                    $def_tva[$obj->rowid][$compta_tva][vatrate($obj->tva_tx) . ($obj->vat_src_code ? ' (' . $obj->vat_src_code . ')' : '')] = (vatrate($obj->tva_tx) . ($obj->vat_src_code ? ' (' . $obj->vat_src_code . ')' : ''));
                }

                //$line = new SupplierInvoiceLine($db);
                //$line->fetch($obj->fdid);

                $tabfac[$obj->rowid]["date"] = $db->jdate($obj->df);
                $tabfac[$obj->rowid]["datereg"] = $db->jdate($obj->dlr);
                $tabfac[$obj->rowid]["ref"] = $obj->ref_supplier . ' (' . $obj->ref . ')';
                $tabfac[$obj->rowid]["refsologest"] = $obj->ref;
                $tabfac[$obj->rowid]["refsuppliersologest"] = $obj->ref_supplier;
                $tabfac[$obj->rowid]["type"] = $obj->type;
                $tabfac[$obj->rowid]["description"] = $obj->description;
                $tabfac[$obj->rowid]["close_code"] = $obj->close_code; // close_code = 'replaced' for replacement invoices (not used in most european countries)
                //$tabfac[$obj->rowid]["fk_facturefourndet"] = $obj->fdid;

                // Avoid warnings
                if (!isset($tabttc[$obj->rowid][$compta_soc])) {
                    $tabttc[$obj->rowid][$compta_soc] = 0;
                }
                if (!isset($tabht[$obj->rowid][$compta_prod])) {
                    $tabht[$obj->rowid][$compta_prod] = 0;
                }
                if (!isset($tabtva[$obj->rowid][$compta_tva])) {
                    $tabtva[$obj->rowid][$compta_tva] = 0;
                }
                if (!isset($tablocaltax1[$obj->rowid][$compta_localtax1])) {
                    $tablocaltax1[$obj->rowid][$compta_localtax1] = 0;
                }
                if (!isset($tablocaltax2[$obj->rowid][$compta_localtax2])) {
                    $tablocaltax2[$obj->rowid][$compta_localtax2] = 0;
                }

                // VAT Reverse charge
                if (($mysoc->country_code == 'FR' || getDolGlobalString('ACCOUNTING_FORCE_ENABLE_VAT_REVERSE_CHARGE')) && $obj->vat_reverse_charge == 1 && in_array($obj->country_code, $country_code_in_EEC)) {
                    $rcvatdata = getTaxesFromId($obj->product_buy_vat . ($obj->product_buy_default_vat_code ? ' (' . $obj->product_buy_default_vat_code . ')' : ''), $mysoc, $mysoc, 0);
                    $rcc_compta_tva = (!empty($vatdata['accountancy_code_vat_reverse_charge_credit']) ? $vatdata['accountancy_code_vat_reverse_charge_credit'] : $rcctva);
                    $rcd_compta_tva = (!empty($vatdata['accountancy_code_vat_reverse_charge_debit']) ? $vatdata['accountancy_code_vat_reverse_charge_debit'] : $rcdtva);
                    $rcc_compta_localtax1 = (!empty($vatdata['accountancy_code_vat_reverse_charge_credit']) ? $vatdata['accountancy_code_vat_reverse_charge_credit'] : $rcctva);
                    $rcd_compta_localtax1 = (!empty($vatdata['accountancy_code_vat_reverse_charge_debit']) ? $vatdata['accountancy_code_vat_reverse_charge_debit'] : $rcdtva);
                    $rcc_compta_localtax2 = (!empty($vatdata['accountancy_code_vat_reverse_charge_credit']) ? $vatdata['accountancy_code_vat_reverse_charge_credit'] : $rcctva);
                    $rcd_compta_localtax2 = (!empty($vatdata['accountancy_code_vat_reverse_charge_debit']) ? $vatdata['accountancy_code_vat_reverse_charge_debit'] : $rcdtva);
                    if (price2num($obj->product_buy_vat) || !empty($obj->product_buy_default_vat_code)) {
                        $vat_key = vatrate($obj->product_buy_vat) . ($obj->product_buy_default_vat_code ? ' (' . $obj->product_buy_default_vat_code . ')' : '');
                        $val_value = $vat_key;
                        $def_tva[$obj->rowid][$rcc_compta_tva][$vat_key] = $val_value;
                        $def_tva[$obj->rowid][$rcd_compta_tva][$vat_key] = $val_value;
                    }

                    if (!isset($tabrctva[$obj->rowid][$rcc_compta_tva])) {
                        $tabrctva[$obj->rowid][$rcc_compta_tva] = 0;
                    }
                    if (!isset($tabrctva[$obj->rowid][$rcd_compta_tva])) {
                        $tabrctva[$obj->rowid][$rcd_compta_tva] = 0;
                    }
                    if (!isset($tabrclocaltax1[$obj->rowid][$rcc_compta_localtax1])) {
                        $tabrclocaltax1[$obj->rowid][$rcc_compta_localtax1] = 0;
                    }
                    if (!isset($tabrclocaltax1[$obj->rowid][$rcd_compta_localtax1])) {
                        $tabrclocaltax1[$obj->rowid][$rcd_compta_localtax1] = 0;
                    }
                    if (!isset($tabrclocaltax2[$obj->rowid][$rcc_compta_localtax2])) {
                        $tabrclocaltax2[$obj->rowid][$rcc_compta_localtax2] = 0;
                    }
                    if (!isset($tabrclocaltax2[$obj->rowid][$rcd_compta_localtax2])) {
                        $tabrclocaltax2[$obj->rowid][$rcd_compta_localtax2] = 0;
                    }

                    $rcvat = (float) price2num($obj->total_ttc * $obj->product_buy_vat / 100, 'MT');
                    $rclocalvat1 = (float) price2num($obj->total_ttc * $obj->product_buy_localvat1 / 100, 'MT');
                    $rclocalvat2 = (float) price2num($obj->total_ttc * $obj->product_buy_localvat2 / 100, 'MT');

                    $tabrctva[$obj->rowid][$rcd_compta_tva] += $rcvat;
                    $tabrctva[$obj->rowid][$rcc_compta_tva] -= $rcvat;
                    $tabrclocaltax1[$obj->rowid][$rcd_compta_localtax1] += $rclocalvat1;
                    $tabrclocaltax1[$obj->rowid][$rcc_compta_localtax1] -= $rclocalvat1;
                    $tabrclocaltax2[$obj->rowid][$rcd_compta_localtax2] += $rclocalvat2;
                    $tabrclocaltax2[$obj->rowid][$rcc_compta_localtax2] -= $rclocalvat2;
                }

                $tabttc[$obj->rowid][$compta_soc] += $obj->total_ttc;
                $tabht[$obj->rowid][$compta_prod] += $obj->total_ht;
                $tabtva[$obj->rowid][$compta_tva] += $obj->total_tva;
                $tva_npr = ((($obj->info_bits & 1) == 1) ? 1 : 0);
                if ($tva_npr) { // If NPR, we add an entry for counterpartWe into tabother
                    $tabother[$obj->rowid][$compta_counterpart_tva_npr] += $obj->total_tva;
                }
                $tablocaltax1[$obj->rowid][$compta_localtax1] += $obj->total_localtax1;
                $tablocaltax2[$obj->rowid][$compta_localtax2] += $obj->total_localtax2;
                $tabcompany[$obj->rowid] = array(
                    'id' => $obj->socid,
                    'name' => $obj->name,
                    'code_fournisseur' => $obj->code_fournisseur,
                    'code_compta_fournisseur' => $compta_soc
                );

                $i++;
            }
        } else {
            dol_print_error($db);
        }

// Check for too many invoices first.
        if (count($tabfac) > 10000) { // Global config in htdocs/admin/const.php???
            $error++;
            setEventMessages("TooManyInvoicesToProcessPleaseUseAMoreSelectiveFilter", null, 'errors');
        }

        $errorforinvoice = array();

        /*
        // Old way, 1 query for each invoice
        // Loop in invoices to detect lines with not binding lines
        foreach ($tabfac as $key => $val) {     // Loop on each invoice
            $sql = "SELECT COUNT(fd.rowid) as nb";
            $sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn_det as fd";
            $sql .= " WHERE fd.product_type <= 2 AND fd.fk_code_ventilation <= 0";
            $sql .= " AND fd.total_ttc <> 0 AND fk_facture_fourn = ".((int) $key);
            $resql = $db->query($sql);
            if ($resql) {
                $obj = $db->fetch_object($resql);
                if ($obj->nb > 0) {
                    $errorforinvoice[$key] = 'somelinesarenotbound';
                }
            } else {
                dol_print_error($db);
            }
        }
        */
// New way, single query, load all unbound lines
        $sql = "
SELECT
    fk_facture_fourn,
    COUNT(fd.rowid) as nb
FROM
    llx_facture_fourn_det as fd
WHERE
    fd.product_type <= 2
    AND fd.fk_code_ventilation <= 0
    AND fd.total_ttc <> 0
	AND fk_facture_fourn IN (" . $db->sanitize(implode(",", array_keys($tabfac))) . ")
GROUP BY fk_facture_fourn
";
        $resql = $db->query($sql);

        $num = $db->num_rows($resql);
        $i = 0;
        while ($i < $num) {
            $obj = $db->fetch_object($resql);
            if ($obj->nb > 0) {
                $errorforinvoice[$obj->fk_facture_fourn] = 'somelinesarenotbound';
            }
            $i++;
        }
//var_dump($errorforinvoice);exit;



// Bookkeeping Write
        if ($action == 'writebookkeeping' && !$error) {
            $now = dol_now();
            $error = 0;

            $companystatic = new Societe($db);
            $invoicestatic = new FactureFournisseur($db);
            $accountingaccountsupplier = new AccountingAccount($db);

            $accountingaccountsupplier->fetch(null, getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER'), true);

            foreach ($tabfac as $key => $val) {     // Loop on each invoice
                $errorforline = 0;

                $totalcredit = 0;
                $totaldebit = 0;

                $db->begin();

                $companystatic->id = $tabcompany[$key]['id'];
                $companystatic->name = $tabcompany[$key]['name'];
                $companystatic->code_compta_fournisseur = $tabcompany[$key]['code_compta_fournisseur'];
                $companystatic->code_fournisseur = $tabcompany[$key]['code_fournisseur'];
                $companystatic->fournisseur = 1;

                $invoicestatic->id = $key;
                $invoicestatic->ref = (string) $val["refsologest"];
                $invoicestatic->ref_supplier = $val["refsuppliersologest"];
                $invoicestatic->type = $val["type"];
                $invoicestatic->description = html_entity_decode(dol_trunc($val["description"], 32));
                $invoicestatic->close_code = $val["close_code"];

                $date = dol_print_date($val["date"], 'day');

                // Is it a replaced invoice ? 0=not a replaced invoice, 1=replaced invoice not yet dispatched, 2=replaced invoice dispatched
                $replacedinvoice = 0;
                if ($invoicestatic->close_code == FactureFournisseur::CLOSECODE_REPLACED) {
                    $replacedinvoice = 1;
                    $alreadydispatched = $invoicestatic->getVentilExportCompta(); // Test if replaced invoice already into bookkeeping.
                    if ($alreadydispatched) {
                        $replacedinvoice = 2;
                    }
                }

                // If not already into bookkeeping, we won't add it. If yes, do nothing (should not happen because creating replacement not possible if invoice is accounted)
                if ($replacedinvoice == 1) {
                    $db->rollback();
                    continue;
                }

                // Error if some lines are not binded/ready to be journalized
                if ($errorforinvoice[$key] == 'somelinesarenotbound') {
                    $error++;
                    $errorforline++;
                    setEventMessages($langs->trans('ErrorInvoiceContainsLinesNotYetBounded', $val['ref']), null, 'errors');
                }

                // Thirdparty
                if (!$errorforline) {
                    foreach ($tabttc[$key] as $k => $mt) {
                        $bookkeeping = new BookKeeping($db);
                        $bookkeeping->doc_date = $val["date"];
                        $bookkeeping->date_lim_reglement = $val["datereg"];
                        $bookkeeping->doc_ref = $val["refsologest"];
                        $bookkeeping->date_creation = $now;
                        $bookkeeping->doc_type = 'supplier_invoice';
                        $bookkeeping->fk_doc = $key;
                        $bookkeeping->fk_docdet = 0; // Useless, can be several lines that are source of this record to add
                        $bookkeeping->thirdparty_code = $companystatic->code_fournisseur;

                        $bookkeeping->subledger_account = $tabcompany[$key]['code_compta_fournisseur'];
                        $bookkeeping->subledger_label = $tabcompany[$key]['name'];

                        $bookkeeping->numero_compte = getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER');
                        $bookkeeping->label_compte = $accountingaccountsupplier->label;

                        $bookkeeping->label_operation = dol_trunc($companystatic->name, 16) . ' - ' . $invoicestatic->ref_supplier . ' - ' . $langs->trans("SubledgerAccount");
                        $bookkeeping->montant = $mt;
                        $bookkeeping->sens = ($mt >= 0) ? 'C' : 'D';
                        $bookkeeping->debit = ($mt <= 0) ? -$mt : 0;
                        $bookkeeping->credit = ($mt > 0) ? $mt : 0;
                        $bookkeeping->code_journal = $journal;
                        $bookkeeping->journal_label = $langs->transnoentities($journal_label);
                        $bookkeeping->fk_user_author = $user->id;
                        $bookkeeping->entity = $conf->entity;

                        $totaldebit += $bookkeeping->debit;
                        $totalcredit += $bookkeeping->credit;

                        $result = $bookkeeping->create($user);
                        if ($result < 0) {
                            if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists') {  // Already exists
                                $error++;
                                $errorforline++;
                                $errorforinvoice[$key] = 'alreadyjournalized';
                                //setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->fk_doc.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
                            } else {
                                $error++;
                                $errorforline++;
                                $errorforinvoice[$key] = 'other';
                                setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
                            }
                        } else {
                            if (getDolGlobalInt('ACCOUNTING_ENABLE_LETTERING') && getDolGlobalInt('ACCOUNTING_ENABLE_AUTOLETTERING')) {
                                require_once DOL_DOCUMENT_ROOT . '/accountancy/class/lettering.class.php';
                                $lettering_static = new Lettering($db);

                                $nb_lettering = $lettering_static->bookkeepingLettering(array($bookkeeping->id));
                            }
                        }
                    }
                }

                // Product / Service
                if (!$errorforline) {
                    foreach ($tabht[$key] as $k => $mt) {
                        $resultfetch = $accountingaccount->fetch(null, $k, true);   // TODO Use a cache
                        $label_account = $accountingaccount->label;

                        // get compte id and label
                        if ($resultfetch > 0) {
                            $bookkeeping = new BookKeeping($db);
                            $bookkeeping->doc_date = $val["date"];
                            $bookkeeping->date_lim_reglement = $val["datereg"];
                            $bookkeeping->doc_ref = $val["refsologest"];
                            $bookkeeping->date_creation = $now;
                            $bookkeeping->doc_type = 'supplier_invoice';
                            $bookkeeping->fk_doc = $key;
                            $bookkeeping->fk_docdet = 0; // Useless, can be several lines that are source of this record to add
                            $bookkeeping->thirdparty_code = $companystatic->code_fournisseur;

                            if (getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER_USE_AUXILIARY_ON_DEPOSIT')) {
                                if ($k == getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER_DEPOSIT')) {
                                    $bookkeeping->subledger_account = $tabcompany[$key]['code_compta'];
                                    $bookkeeping->subledger_label = $tabcompany[$key]['name'];
                                } else {
                                    $bookkeeping->subledger_account = '';
                                    $bookkeeping->subledger_label = '';
                                }
                            } else {
                                $bookkeeping->subledger_account = '';
                                $bookkeeping->subledger_label = '';
                            }

                            $bookkeeping->numero_compte = $k;
                            $bookkeeping->label_compte = $label_account;

                            $bookkeeping->label_operation = dol_trunc($companystatic->name, 16) . ' - ' . $invoicestatic->ref_supplier . ' - ' . $label_account;
                            $bookkeeping->montant = $mt;
                            $bookkeeping->sens = ($mt < 0) ? 'C' : 'D';
                            $bookkeeping->debit = ($mt > 0) ? $mt : 0;
                            $bookkeeping->credit = ($mt <= 0) ? -$mt : 0;
                            $bookkeeping->code_journal = $journal;
                            $bookkeeping->journal_label = $langs->transnoentities($journal_label);
                            $bookkeeping->fk_user_author = $user->id;
                            $bookkeeping->entity = $conf->entity;

                            $totaldebit += $bookkeeping->debit;
                            $totalcredit += $bookkeeping->credit;

                            $result = $bookkeeping->create($user);
                            if ($result < 0) {
                                if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists') {  // Already exists
                                    $error++;
                                    $errorforline++;
                                    $errorforinvoice[$key] = 'alreadyjournalized';
                                    //setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->fk_doc.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
                                } else {
                                    $error++;
                                    $errorforline++;
                                    $errorforinvoice[$key] = 'other';
                                    setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
                                }
                            }
                        }
                    }
                }

                // VAT
                // var_dump($tabtva);
                if (!$errorforline) {
                    $listoftax = array(0, 1, 2);
                    foreach ($listoftax as $numtax) {
                        $arrayofvat = $tabtva;
                        if ($numtax == 1) {
                            $arrayofvat = $tablocaltax1;
                        }
                        if ($numtax == 2) {
                            $arrayofvat = $tablocaltax2;
                        }

                        // VAT Reverse charge
                        if ($mysoc->country_code == 'FR' || getDolGlobalString('ACCOUNTING_FORCE_ENABLE_VAT_REVERSE_CHARGE')) {
                            $has_vat = false;
                            foreach ($arrayofvat[$key] as $k => $mt) {
                                if ($mt) {
                                    $has_vat = true;
                                }
                            }

                            if (!$has_vat) {
                                $arrayofvat = $tabrctva;
                                if ($numtax == 1) {
                                    $arrayofvat = $tabrclocaltax1;
                                }
                                if ($numtax == 2) {
                                    $arrayofvat = $tabrclocaltax2;
                                }
                                if (!is_array($arrayofvat[$key])) {
                                    $arrayofvat[$key] = array();
                                }
                            }
                        }

                        foreach ($arrayofvat[$key] as $k => $mt) {
                            if ($mt) {
                                $accountingaccount->fetch(null, $k, true);      // TODO Use a cache for label
                                $label_account = $accountingaccount->label;

                                $bookkeeping = new BookKeeping($db);
                                $bookkeeping->doc_date = $val["date"];
                                $bookkeeping->date_lim_reglement = $val["datereg"];
                                $bookkeeping->doc_ref = $val["refsologest"];
                                $bookkeeping->date_creation = $now;
                                $bookkeeping->doc_type = 'supplier_invoice';
                                $bookkeeping->fk_doc = $key;
                                $bookkeeping->fk_docdet = 0; // Useless, can be several lines that are source of this record to add
                                $bookkeeping->thirdparty_code = $companystatic->code_fournisseur;

                                $bookkeeping->subledger_account = '';
                                $bookkeeping->subledger_label = '';

                                $bookkeeping->numero_compte = $k;
                                $bookkeeping->label_compte = $label_account;

                                $bookkeeping->label_operation = dol_trunc($companystatic->name, 16) . ' - ' . $invoicestatic->ref_supplier . ' - ' . $langs->trans("VAT") . ' ' . implode(', ', $def_tva[$key][$k]) . ' %' . ($numtax ? ' - Localtax ' . $numtax : '');
                                $bookkeeping->montant = $mt;
                                $bookkeeping->sens = ($mt < 0) ? 'C' : 'D';
                                $bookkeeping->debit = ($mt > 0) ? $mt : 0;
                                $bookkeeping->credit = ($mt <= 0) ? -$mt : 0;
                                $bookkeeping->code_journal = $journal;
                                $bookkeeping->journal_label = $langs->transnoentities($journal_label);
                                $bookkeeping->fk_user_author = $user->id;
                                $bookkeeping->entity = $conf->entity;

                                $totaldebit += $bookkeeping->debit;
                                $totalcredit += $bookkeeping->credit;

                                $result = $bookkeeping->create($user);
                                if ($result < 0) {
                                    if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists') {  // Already exists
                                        $error++;
                                        $errorforline++;
                                        $errorforinvoice[$key] = 'alreadyjournalized';
                                        //setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->fk_doc.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
                                    } else {
                                        $error++;
                                        $errorforline++;
                                        $errorforinvoice[$key] = 'other';
                                        setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
                                    }
                                }
                            }
                        }
                    }
                }

                // Counterpart of VAT for VAT NPR
                // var_dump($tabother);
                if (!$errorforline && is_array($tabother[$key])) {
                    foreach ($tabother[$key] as $k => $mt) {
                        if ($mt) {
                            $bookkeeping = new BookKeeping($db);
                            $bookkeeping->doc_date = $val["date"];
                            $bookkeeping->date_lim_reglement = $val["datereg"];
                            $bookkeeping->doc_ref = $val["refsologest"];
                            $bookkeeping->date_creation = $now;
                            $bookkeeping->doc_type = 'supplier_invoice';
                            $bookkeeping->fk_doc = $key;
                            $bookkeeping->fk_docdet = 0; // Useless, can be several lines that are source of this record to add
                            $bookkeeping->thirdparty_code = $companystatic->code_fournisseur;

                            $bookkeeping->subledger_account = '';
                            $bookkeeping->subledger_label = '';

                            $bookkeeping->numero_compte = $k;

                            $bookkeeping->label_operation = dol_trunc($companystatic->name, 16) . ' - ' . $invoicestatic->ref_supplier . ' - ' . $langs->trans("VAT") . ' NPR';
                            $bookkeeping->montant = $mt;
                            $bookkeeping->sens = ($mt < 0) ? 'C' : 'D';
                            $bookkeeping->debit = ($mt > 0) ? $mt : 0;
                            $bookkeeping->credit = ($mt <= 0) ? -$mt : 0;
                            $bookkeeping->code_journal = $journal;
                            $bookkeeping->journal_label = $langs->transnoentities($journal_label);
                            $bookkeeping->fk_user_author = $user->id;
                            $bookkeeping->entity = $conf->entity;

                            $totaldebit += $bookkeeping->debit;
                            $totalcredit += $bookkeeping->credit;

                            $result = $bookkeeping->create($user);
                            if ($result < 0) {
                                if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists') {  // Already exists
                                    $error++;
                                    $errorforline++;
                                    $errorforinvoice[$key] = 'alreadyjournalized';
                                    //setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->fk_doc.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
                                } else {
                                    $error++;
                                    $errorforline++;
                                    $errorforinvoice[$key] = 'other';
                                    setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
                                }
                            }
                        }
                    }
                }

                // Protection against a bug on lines before
                if (!$errorforline && (price2num($totaldebit, 'MT') != price2num($totalcredit, 'MT'))) {
                    $error++;
                    $errorforline++;
                    $errorforinvoice[$key] = 'amountsnotbalanced';
                    setEventMessages('We tried to insert a non balanced transaction in book for ' . $invoicestatic->ref . '. Canceled. Surely a bug.', null, 'errors');
                }

                if (!$errorforline) {
                    $db->commit();
                } else {
                    $db->rollback();

                    if ($error >= 10) {
                        setEventMessages($langs->trans("ErrorTooManyErrorsProcessStopped"), null, 'errors');
                        break; // Break in the foreach
                    }
                }
            }

            $tabpay = $tabfac;

            if (empty($error) && count($tabpay) > 0) {
                setEventMessages($langs->trans("GeneralLedgerIsWritten"), null, 'mesgs');
            } elseif (count($tabpay) == $error) {
                setEventMessages($langs->trans("NoNewRecordSaved"), null, 'warnings');
            } else {
                setEventMessages($langs->trans("GeneralLedgerSomeRecordWasNotRecorded"), null, 'warnings');
            }

            $action = '';

            // Must reload data, so we make a redirect
            if (count($tabpay) != $error) {
                $param = 'id_journal=' . $id_journal;
                $param .= '&date_startday=' . $date_startday;
                $param .= '&date_startmonth=' . $date_startmonth;
                $param .= '&date_startyear=' . $date_startyear;
                $param .= '&date_endday=' . $date_endday;
                $param .= '&date_endmonth=' . $date_endmonth;
                $param .= '&date_endyear=' . $date_endyear;
                $param .= '&in_bookkeeping=' . $in_bookkeeping;
                header("Location: " . $_SERVER['PHP_SELF'] . ($param ? '?' . $param : ''));
                exit;
            }
        }

        /*
         * View
         */

        $form = new Form($db);

// Export
        if ($action == 'exportcsv' && !$error) {        // ISO and not UTF8 !
            $sep = getDolGlobalString('ACCOUNTING_EXPORT_SEPARATORCSV');

            $filename = 'journal';
            $type_export = 'journal';
            include DOL_DOCUMENT_ROOT . '/accountancy/tpl/export_journal.tpl.php';

            $companystatic = new Fournisseur($db);
            $invoicestatic = new FactureFournisseur($db);

            foreach ($tabfac as $key => $val) {
                $companystatic->id = $tabcompany[$key]['id'];
                $companystatic->name = $tabcompany[$key]['name'];
                $companystatic->code_compta_fournisseur = $tabcompany[$key]['code_compta_fournisseur'];
                $companystatic->code_fournisseur = $tabcompany[$key]['code_fournisseur'];
                $companystatic->fournisseur = 1;

                $invoicestatic->id = $key;
                $invoicestatic->ref = $val["refsologest"];
                $invoicestatic->ref_supplier = $val["refsuppliersologest"];
                $invoicestatic->type = $val["type"];
                $invoicestatic->description = dol_trunc(html_entity_decode($val["description"]), 32);
                $invoicestatic->close_code = $val["close_code"];

                $date = dol_print_date($val["date"], 'day');

                // Is it a replaced invoice ? 0=not a replaced invoice, 1=replaced invoice not yet dispatched, 2=replaced invoice dispatched
                $replacedinvoice = 0;
                if ($invoicestatic->close_code == FactureFournisseur::CLOSECODE_REPLACED) {
                    $replacedinvoice = 1;
                    $alreadydispatched = $invoicestatic->getVentilExportCompta(); // Test if replaced invoice already into bookkeeping.
                    if ($alreadydispatched) {
                        $replacedinvoice = 2;
                    }
                }

                // If not already into bookkeeping, we won't add it. If yes, do nothing (should not happen because creating replacement not possible if invoice is accounted)
                if ($replacedinvoice == 1) {
                    continue;
                }

                // Third party
                foreach ($tabttc[$key] as $k => $mt) {
                    //if ($mt) {
                    print '"' . $key . '"' . $sep;
                    print '"' . $date . '"' . $sep;
                    print '"' . $val["refsologest"] . '"' . $sep;
                    print '"' . mb_convert_encoding(dol_trunc($companystatic->name, 32), 'ISO-8859-1') . '"' . $sep;
                    print '"' . length_accounta(html_entity_decode($k)) . '"' . $sep;
                    print '"' . length_accountg(getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER')) . '"' . $sep;
                    print '"' . length_accounta(html_entity_decode($k)) . '"' . $sep;
                    print '"' . $langs->trans("Thirdparty") . '"' . $sep;
                    print '"' . mb_convert_encoding(dol_trunc($companystatic->name, 16), 'ISO-8859-1') . ' - ' . $val["refsuppliersologest"] . ' - ' . $langs->trans("Thirdparty") . '"' . $sep;
                    print '"' . ($mt < 0 ? price(-$mt) : '') . '"' . $sep;
                    print '"' . ($mt >= 0 ? price($mt) : '') . '"' . $sep;
                    print '"' . $journal . '"';
                    print "\n";
                    //}
                }

                // Product / Service
                foreach ($tabht[$key] as $k => $mt) {
                    $accountingaccount = new AccountingAccount($db);
                    $accountingaccount->fetch(null, $k, true);
                    //if ($mt) {
                    print '"' . $key . '"' . $sep;
                    print '"' . $date . '"' . $sep;
                    print '"' . $val["refsologest"] . '"' . $sep;
                    print '"' . mb_convert_encoding(dol_trunc($companystatic->name, 32), 'ISO-8859-1') . '"' . $sep;
                    print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
                    print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
                    print '""' . $sep;
                    print '"' . mb_convert_encoding(dol_trunc($accountingaccount->label, 32), 'ISO-8859-1') . '"' . $sep;
                    print '"' . mb_convert_encoding(dol_trunc($companystatic->name, 16), 'ISO-8859-1') . ' - ' . $val["refsuppliersologest"] . ' - ' . dol_trunc($accountingaccount->label, 32) . '"' . $sep;
                    print '"' . ($mt >= 0 ? price($mt) : '') . '"' . $sep;
                    print '"' . ($mt < 0 ? price(-$mt) : '') . '"' . $sep;
                    print '"' . $journal . '"';
                    print "\n";
                    //}
                }

                // VAT
                $listoftax = array(0, 1, 2);
                foreach ($listoftax as $numtax) {
                    $arrayofvat = $tabtva;
                    if ($numtax == 1) {
                        $arrayofvat = $tablocaltax1;
                    }
                    if ($numtax == 2) {
                        $arrayofvat = $tablocaltax2;
                    }

                    // VAT Reverse charge
                    if ($mysoc->country_code == 'FR' || getDolGlobalString('ACCOUNTING_FORCE_ENABLE_VAT_REVERSE_CHARGE')) {
                        $has_vat = false;
                        foreach ($arrayofvat[$key] as $k => $mt) {
                            if ($mt) {
                                $has_vat = true;
                            }
                        }

                        if (!$has_vat) {
                            $arrayofvat = $tabrctva;
                            if ($numtax == 1) {
                                $arrayofvat = $tabrclocaltax1;
                            }
                            if ($numtax == 2) {
                                $arrayofvat = $tabrclocaltax2;
                            }
                            if (!is_array($arrayofvat[$key])) {
                                $arrayofvat[$key] = array();
                            }
                        }
                    }

                    foreach ($arrayofvat[$key] as $k => $mt) {
                        if ($mt) {
                            print '"' . $key . '"' . $sep;
                            print '"' . $date . '"' . $sep;
                            print '"' . $val["refsologest"] . '"' . $sep;
                            print '"' . mb_convert_encoding(dol_trunc($companystatic->name, 32), 'ISO-8859-1') . '"' . $sep;
                            print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
                            print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
                            print '""' . $sep;
                            print '"' . $langs->trans("VAT") . ' - ' . implode(', ', $def_tva[$key][$k]) . ' %"' . $sep;
                            print '"' . mb_convert_encoding(dol_trunc($companystatic->name, 16), 'ISO-8859-1') . ' - ' . $val["refsuppliersologest"] . ' - ' . $langs->trans("VAT") . implode(', ', $def_tva[$key][$k]) . ' %' . ($numtax ? ' - Localtax ' . $numtax : '') . '"' . $sep;
                            print '"' . ($mt >= 0 ? price($mt) : '') . '"' . $sep;
                            print '"' . ($mt < 0 ? price(-$mt) : '') . '"' . $sep;
                            print '"' . $journal . '"';
                            print "\n";
                        }
                    }

                    // VAT counterpart for NPR
                    if (is_array($tabother[$key])) {
                        foreach ($tabother[$key] as $k => $mt) {
                            if ($mt) {
                                print '"' . $key . '"' . $sep;
                                print '"' . $date . '"' . $sep;
                                print '"' . $val["refsologest"] . '"' . $sep;
                                print '"' . mb_convert_encoding(dol_trunc($companystatic->name, 32), 'ISO-8859-1') . '"' . $sep;
                                print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
                                print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
                                print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
                                print '"' . $langs->trans("Thirdparty") . '"' . $sep;
                                print '"' . mb_convert_encoding(dol_trunc($companystatic->name, 16), 'ISO-8859-1') . ' - ' . $val["refsuppliersologest"] . ' - ' . $langs->trans("VAT") . ' NPR"' . $sep;
                                print '"' . ($mt < 0 ? price(-$mt) : '') . '"' . $sep;
                                print '"' . ($mt >= 0 ? price($mt) : '') . '"' . $sep;
                                print '"' . $journal . '"';
                                print "\n";
                            }
                        }
                    }
                }
            }
        }

        if (empty($action) || $action == 'view') {
            $title = $langs->trans("GenerationOfAccountingEntries") . ' - ' . $accountingjournalstatic->getNomUrl(0, 2, 1, '', 1);

            llxHeader('', dol_string_nohtmltag($title));

            $nom = $title;
            $nomlink = '';
            $periodlink = '';
            $exportlink = '';
            $builddate = dol_now();
            $description = $langs->trans("DescJournalOnlyBindedVisible") . '<br>';
            if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {
                $description .= $langs->trans("DepositsAreNotIncluded");
            } else {
                $description .= $langs->trans("DepositsAreIncluded");
            }

            $listofchoices = array('notyet' => $langs->trans("NotYetInGeneralLedger"), 'already' => $langs->trans("AlreadyInGeneralLedger"));
            $period = $form->selectDate($date_start ? $date_start : -1, 'date_start', 0, 0, 0, '', 1, 0) . ' - ' . $form->selectDate($date_end ? $date_end : -1, 'date_end', 0, 0, 0, '', 1, 0);
            $period .= ' -  ' . $langs->trans("JournalizationInLedgerStatus") . ' ' . $form->selectarray('in_bookkeeping', $listofchoices, $in_bookkeeping, 1);

            $varlink = 'id_journal=' . $id_journal;

            journalHead($nom, $nomlink, $period, $periodlink, $description, $builddate, $exportlink, array('action' => ''), '', $varlink);

            if (getDolGlobalString('ACCOUNTANCY_FISCAL_PERIOD_MODE') != 'blockedonclosed') {
                // Test that setup is complete (we are in accounting, so test on entity is always on $conf->entity only, no sharing allowed)
                // Fiscal period test
                $sql = "SELECT COUNT(rowid) as nb FROM " . MAIN_DB_PREFIX . "accounting_fiscalyear WHERE entity = " . ((int) $conf->entity);
                $resql = $db->query($sql);
                if ($resql) {
                    $obj = $db->fetch_object($resql);
                    if ($obj->nb == 0) {
                        print '<br><div class="warning">' . img_warning() . ' ' . $langs->trans("TheFiscalPeriodIsNotDefined");
                        $desc = ' : ' . $langs->trans("AccountancyAreaDescFiscalPeriod", 4, '{link}');
                        $desc = str_replace('{link}', '<strong>' . $langs->transnoentitiesnoconv("MenuAccountancy") . '-' . $langs->transnoentitiesnoconv("Setup") . "-" . $langs->transnoentitiesnoconv("FiscalPeriod") . '</strong>', $desc);
                        print $desc;
                        print '</div>';
                    }
                } else {
                    dol_print_error($db);
                }
            }

            // Button to write into Ledger
            $acctSupplierNotConfigured = in_array(getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER'), ['','-1']);
            if ($acctSupplierNotConfigured) {
                print '<br><div class="warning">' . img_warning() . ' ' . $langs->trans("SomeMandatoryStepsOfSetupWereNotDone");
                $desc = ' : ' . $langs->trans("AccountancyAreaDescMisc", 4, '{link}');
                $desc = str_replace('{link}', '<strong>' . $langs->transnoentitiesnoconv("MenuAccountancy") . '-' . $langs->transnoentitiesnoconv("Setup") . "-" . $langs->transnoentitiesnoconv("MenuDefaultAccounts") . '</strong>', $desc);
                print $desc;
                print '</div>';
            }
            print '<br><div class="tabsAction tabsActionNoBottom centerimp">';
            if (getDolGlobalString('ACCOUNTING_ENABLE_EXPORT_DRAFT_JOURNAL') && $in_bookkeeping == 'notyet') {
                print '<input type="button" class="butAction" name="exportcsv" value="' . $langs->trans("ExportDraftJournal") . '" onclick="launch_export();" />';
            }
            if ($acctSupplierNotConfigured) {
                print '<input type="button" class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans("SomeMandatoryStepsOfSetupWereNotDone")) . '" value="' . $langs->trans("WriteBookKeeping") . '" />';
            } else {
                if ($in_bookkeeping == 'notyet') {
                    print '<input type="button" class="butAction" name="writebookkeeping" value="' . $langs->trans("WriteBookKeeping") . '" onclick="writebookkeeping();" />';
                } else {
                    print '<a href="#" class="butActionRefused classfortooltip" name="writebookkeeping">' . $langs->trans("WriteBookKeeping") . '</a>';
                }
            }
            print '</div>';

            // TODO Avoid using js. We can use a direct link with $param
            print '
	<script type="text/javascript">
		function launch_export() {
			$("div.fiche form input[name=\"action\"]").val("exportcsv");
			$("div.fiche form input[type=\"submit\"]").click();
			$("div.fiche form input[name=\"action\"]").val("");
		}
		function writebookkeeping() {
			console.log("click on writebookkeeping");
			$("div.fiche form input[name=\"action\"]").val("writebookkeeping");
			$("div.fiche form input[type=\"submit\"]").click();
			$("div.fiche form input[name=\"action\"]").val("");
		}
	</script>';

            /*
             * Show result array
             */
            print '<br>';

            print '<div class="div-table-responsive">';
            print "<table class=\"noborder\" width=\"100%\">";
            print "<tr class=\"liste_titre\">";
            print "<td>" . $langs->trans("Date") . "</td>";
            print "<td>" . $langs->trans("Piece") . ' (' . $langs->trans("InvoiceRef") . ")</td>";
            print "<td>" . $langs->trans("AccountAccounting") . "</td>";
            print "<td>" . $langs->trans("SubledgerAccount") . "</td>";
            print "<td>" . $langs->trans("LabelOperation") . "</td>";
            print '<td class="center">' . $langs->trans("AccountingDebit") . "</td>";
            print '<td class="center">' . $langs->trans("AccountingCredit") . "</td>";
            print "</tr>\n";

            $i = 0;

            $invoicestatic = new FactureFournisseur($db);
            $companystatic = new Fournisseur($db);

            foreach ($tabfac as $key => $val) {
                $companystatic->id = $tabcompany[$key]['id'];
                $companystatic->name = $tabcompany[$key]['name'];
                $companystatic->code_compta_fournisseur = $tabcompany[$key]['code_compta_fournisseur'];
                $companystatic->code_fournisseur = $tabcompany[$key]['code_fournisseur'];
                $companystatic->fournisseur = 1;

                $invoicestatic->id = $key;
                $invoicestatic->ref = $val["refsologest"];
                $invoicestatic->ref_supplier = $val["refsuppliersologest"];
                $invoicestatic->type = $val["type"];
                $invoicestatic->description = dol_trunc(html_entity_decode($val["description"]), 32);
                $invoicestatic->close_code = $val["close_code"];

                $date = dol_print_date($val["date"], 'day');

                // Is it a replaced invoice ? 0=not a replaced invoice, 1=replaced invoice not yet dispatched, 2=replaced invoice dispatched
                $replacedinvoice = 0;
                if ($invoicestatic->close_code == FactureFournisseur::CLOSECODE_REPLACED) {
                    $replacedinvoice = 1;
                    $alreadydispatched = $invoicestatic->getVentilExportCompta(); // Test if replaced invoice already into bookkeeping.
                    if ($alreadydispatched) {
                        $replacedinvoice = 2;
                    }
                }

                // If not already into bookkeeping, we won't add it, if yes, add the counterpart ???.
                if ($replacedinvoice == 1) {
                    print '<tr class="oddeven">';
                    print "<!-- Replaced invoice -->";
                    print "<td>" . $date . "</td>";
                    print "<td><strike>" . $invoicestatic->getNomUrl(1) . "</strike></td>";
                    // Account
                    print "<td>";
                    print $langs->trans("Replaced");
                    print '</td>';
                    // Subledger account
                    print "<td>";
                    print '</td>';
                    print "<td>";
                    print "</td>";
                    print '<td class="right"></td>';
                    print '<td class="right"></td>';
                    print "</tr>";

                    $i++;
                    continue;
                }
                if ($errorforinvoice[$key] == 'somelinesarenotbound') {
                    print '<tr class="oddeven">';
                    print "<!-- Some lines are not bound -->";
                    print "<td>" . $date . "</td>";
                    print "<td>" . $invoicestatic->getNomUrl(1) . "</td>";
                    // Account
                    print "<td>";
                    print '<span class="error">' . $langs->trans('ErrorInvoiceContainsLinesNotYetBoundedShort', $val['ref']) . '</span>';
                    print '</td>';
                    // Subledger account
                    print "<td>";
                    print '</td>';
                    print "<td>";
                    print "</td>";
                    print '<td class="right"></td>';
                    print '<td class="right"></td>';
                    print "</tr>";

                    $i++;
                }

                // Third party
                foreach ($tabttc[$key] as $k => $mt) {
                    print '<tr class="oddeven">';
                    print "<!-- Thirdparty -->";
                    print "<td>" . $date . "</td>";
                    print "<td>" . $invoicestatic->getNomUrl(1) . "</td>";
                    // Account
                    print "<td>";
                    $accountoshow = length_accountg(getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER'));
                    if (($accountoshow == "") || $accountoshow == 'NotDefined') {
                        print '<span class="error">' . $langs->trans("MainAccountForSuppliersNotDefined") . '</span>';
                    } else {
                        print $accountoshow;
                    }
                    print '</td>';
                    // Subledger account
                    print "<td>";
                    $accountoshow = length_accounta($k);
                    if (($accountoshow == "") || $accountoshow == 'NotDefined') {
                        print '<span class="error">' . $langs->trans("ThirdpartyAccountNotDefined") . '</span>';
                    } else {
                        print $accountoshow;
                    }
                    print '</td>';
                    print "<td>" . $companystatic->getNomUrl(0, 'supplier', 16) . ' - ' . $invoicestatic->ref_supplier . ' - ' . $langs->trans("SubledgerAccount") . "</td>";
                    print '<td class="right nowraponall amount">' . ($mt < 0 ? price(-$mt) : '') . "</td>";
                    print '<td class="right nowraponall amount">' . ($mt >= 0 ? price($mt) : '') . "</td>";
                    print "</tr>";

                    $i++;
                }

                // Product / Service
                foreach ($tabht[$key] as $k => $mt) {
                    $accountingaccount = new AccountingAccount($db);
                    $accountingaccount->fetch(null, $k, true);

                    print '<tr class="oddeven">';
                    print "<!-- Product -->";
                    print "<td>" . $date . "</td>";
                    print "<td>" . $invoicestatic->getNomUrl(1) . "</td>";
                    // Account
                    print "<td>";
                    $accountoshow = length_accountg($k);
                    if (($accountoshow == "") || $accountoshow == 'NotDefined') {
                        print '<span class="error">' . $langs->trans("ProductAccountNotDefined") . '</span>';
                    } else {
                        print $accountoshow;
                    }
                    print "</td>";
                    // Subledger account
                    print "<td>";
                    if (getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER_USE_AUXILIARY_ON_DEPOSIT')) {
                        if ($k == getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER_DEPOSIT')) {
                            print length_accounta($tabcompany[$key]['code_compta']);
                        }
                    } elseif (($accountoshow == "") || $accountoshow == 'NotDefined') {
                        print '<span class="error">' . $langs->trans("ThirdpartyAccountNotDefined") . '</span>';
                    }
                    print '</td>';
                    $companystatic->id = $tabcompany[$key]['id'];
                    $companystatic->name = $tabcompany[$key]['name'];
                    print "<td>" . $companystatic->getNomUrl(0, 'supplier', 16) . ' - ' . $invoicestatic->ref_supplier . ' - ' . $accountingaccount->label . "</td>";
                    print '<td class="right nowraponall amount">' . ($mt >= 0 ? price($mt) : '') . "</td>";
                    print '<td class="right nowraponall amount">' . ($mt < 0 ? price(-$mt) : '') . "</td>";
                    print "</tr>";

                    $i++;
                }

                // VAT
                $listoftax = array(0, 1, 2);
                foreach ($listoftax as $numtax) {
                    $arrayofvat = $tabtva;
                    if ($numtax == 1) {
                        $arrayofvat = $tablocaltax1;
                    }
                    if ($numtax == 2) {
                        $arrayofvat = $tablocaltax2;
                    }

                    // VAT Reverse charge
                    if ($mysoc->country_code == 'FR' || getDolGlobalString('ACCOUNTING_FORCE_ENABLE_VAT_REVERSE_CHARGE')) {
                        $has_vat = false;
                        foreach ($arrayofvat[$key] as $k => $mt) {
                            if ($mt) {
                                $has_vat = true;
                            }
                        }

                        if (!$has_vat) {
                            $arrayofvat = $tabrctva;
                            if ($numtax == 1) {
                                $arrayofvat = $tabrclocaltax1;
                            }
                            if ($numtax == 2) {
                                $arrayofvat = $tabrclocaltax2;
                            }
                            if (!is_array($arrayofvat[$key])) {
                                $arrayofvat[$key] = array();
                            }
                        }
                    }

                    foreach ($arrayofvat[$key] as $k => $mt) {
                        if ($mt) {
                            print '<tr class="oddeven">';
                            print "<!-- VAT -->";
                            print "<td>" . $date . "</td>";
                            print "<td>" . $invoicestatic->getNomUrl(1) . "</td>";
                            // Account
                            print "<td>";
                            $accountoshow = length_accountg($k);
                            if (($accountoshow == "") || $accountoshow == 'NotDefined') {
                                print '<span class="error">' . $langs->trans("VATAccountNotDefined") . ' (' . $langs->trans("AccountingJournalType3") . ')</span>';
                            } else {
                                print $accountoshow;
                            }
                            print "</td>";
                            // Subledger account
                            print "<td>";
                            print '</td>';
                            print "<td>";
                            print $companystatic->getNomUrl(0, 'supplier', 16) . ' - ' . $invoicestatic->ref_supplier . ' - ' . $langs->trans("VAT") . ' ' . implode(', ', $def_tva[$key][$k]) . ' %' . ($numtax ? ' - Localtax ' . $numtax : '');
                            print "</td>";
                            print '<td class="right nowraponall amount">' . ($mt >= 0 ? price($mt) : '') . "</td>";
                            print '<td class="right nowraponall amount">' . ($mt < 0 ? price(-$mt) : '') . "</td>";
                            print "</tr>";

                            $i++;
                        }
                    }
                }

                // VAT counterpart for NPR
                if (is_array($tabother[$key])) {
                    foreach ($tabother[$key] as $k => $mt) {
                        if ($mt) {
                            print '<tr class="oddeven">';
                            print '<!-- VAT counterpart NPR -->';
                            print "<td>" . $date . "</td>";
                            print "<td>" . $invoicestatic->getNomUrl(1) . "</td>";
                            // Account
                            print '<td>';
                            $accountoshow = length_accountg($k);
                            if ($accountoshow == '' || $accountoshow == 'NotDefined') {
                                print '<span class="error">' . $langs->trans("VATAccountNotDefined") . ' (' . $langs->trans("NPR counterpart") . '). Set ACCOUNTING_COUNTERPART_VAT_NPR to the subvention account</span>';
                            } else {
                                print $accountoshow;
                            }
                            print '</td>';
                            // Subledger account
                            print "<td>";
                            print '</td>';
                            print "<td>" . $companystatic->getNomUrl(0, 'supplier', 16) . ' - ' . $invoicestatic->ref_supplier . ' - ' . $langs->trans("VAT") . " NPR (counterpart)</td>";
                            print '<td class="right nowraponall amount">' . ($mt < 0 ? price(-$mt) : '') . "</td>";
                            print '<td class="right nowraponall amount">' . ($mt >= 0 ? price($mt) : '') . "</td>";
                            print "</tr>";

                            $i++;
                        }
                    }
                }
            }

            if (!$i) {
                print '<tr class="oddeven"><td colspan="7"><span class="opacitymedium">' . $langs->trans("NoRecordFound") . '</span></td></tr>';
            }

            print "</table>";
            print '</div>';

            // End of page
            llxFooter();
        }
        $db->close();
    }

    /**
     * \file        htdocs/accountancy/journal/sellsjournal.php
     * \ingroup     Accountancy (Double entries)
     * \brief       Page with sells journal
     */
    public function sellsjournal()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;
        global $mysoc;

// Load translation files required by the page
        $langs->loadLangs(array("commercial", "compta", "bills", "other", "accountancy", "errors"));

        $id_journal = GETPOSTINT('id_journal');
        $action = GETPOST('action', 'aZ09');

        $date_startmonth = GETPOST('date_startmonth');
        $date_startday = GETPOST('date_startday');
        $date_startyear = GETPOST('date_startyear');
        $date_endmonth = GETPOST('date_endmonth');
        $date_endday = GETPOST('date_endday');
        $date_endyear = GETPOST('date_endyear');
        $in_bookkeeping = GETPOST('in_bookkeeping');
        if ($in_bookkeeping == '') {
            $in_bookkeeping = 'notyet';
        }

        $now = dol_now();

        $hookmanager->initHooks(array('sellsjournal'));
        $parameters = array();

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

        $error = 0;


        /*
         * Actions
         */

        $reshook = $hookmanager->executeHooks('doActions', $parameters, $user, $action); // Note that $action and $object may have been modified by some hooks

        $accountingaccount = new AccountingAccount($db);

// Get information of journal
        $accountingjournalstatic = new AccountingJournal($db);
        $accountingjournalstatic->fetch($id_journal);
        $journal = $accountingjournalstatic->code;
        $journal_label = $accountingjournalstatic->label;

        $date_start = dol_mktime(0, 0, 0, $date_startmonth, $date_startday, $date_startyear);
        $date_end = dol_mktime(23, 59, 59, $date_endmonth, $date_endday, $date_endyear);

        if (empty($date_startmonth)) {
            // Period by default on transfer
            $dates = getDefaultDatesForTransfer();
            $date_start = $dates['date_start'];
            $pastmonthyear = $dates['pastmonthyear'];
            $pastmonth = $dates['pastmonth'];
        }
        if (empty($date_endmonth)) {
            // Period by default on transfer
            $dates = getDefaultDatesForTransfer();
            $date_end = $dates['date_end'];
            $pastmonthyear = $dates['pastmonthyear'];
            $pastmonth = $dates['pastmonth'];
        }
        if (getDolGlobalString('ACCOUNTANCY_JOURNAL_USE_CURRENT_MONTH')) {
            $pastmonth += 1;
        }

        if (!GETPOSTISSET('date_startmonth') && (empty($date_start) || empty($date_end))) { // We define date_start and date_end, only if we did not submit the form
            $date_start = dol_get_first_day($pastmonthyear, $pastmonth, false);
            $date_end = dol_get_last_day($pastmonthyear, $pastmonth, false);
        }

        $sql = "SELECT f.rowid, f.ref, f.type, f.situation_cycle_ref, f.datef as df, f.ref_client, f.date_lim_reglement as dlr, f.close_code, f.retained_warranty, f.revenuestamp,";
        $sql .= " fd.rowid as fdid, fd.description, fd.product_type, fd.total_ht, fd.total_tva, fd.total_localtax1, fd.total_localtax2, fd.tva_tx, fd.total_ttc, fd.situation_percent, fd.vat_src_code, fd.info_bits,";
        $sql .= " s.rowid as socid, s.nom as name, s.code_client, s.code_fournisseur,";
        if (getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
            $sql .= " spe.accountancy_code_customer as code_compta,";
            $sql .= " spe.accountancy_code_supplier as code_compta_fournisseur,";
        } else {
            $sql .= " s.code_compta as code_compta,";
            $sql .= " s.code_compta_fournisseur,";
        }
        $sql .= " p.rowid as pid, p.ref as pref, aa.rowid as fk_compte, aa.account_number as compte, aa.label as label_compte,";
        if (getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED')) {
            $sql .= " ppe.accountancy_code_sell";
        } else {
            $sql .= " p.accountancy_code_sell";
        }
        $parameters = array();
        $reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
        $sql .= $hookmanager->resPrint;
        $sql .= " FROM " . MAIN_DB_PREFIX . "facturedet as fd";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product as p ON p.rowid = fd.fk_product";
        if (getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED')) {
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product_perentity as ppe ON ppe.fk_product = p.rowid AND ppe.entity = " . ((int) $conf->entity);
        }
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_account as aa ON aa.rowid = fd.fk_code_ventilation";
        $sql .= " JOIN " . MAIN_DB_PREFIX . "facture as f ON f.rowid = fd.fk_facture";
        $sql .= " JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid = f.fk_soc";
        if (getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_perentity as spe ON spe.fk_soc = s.rowid AND spe.entity = " . ((int) $conf->entity);
        }
        $parameters = array();
        $reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters); // Note that $action and $object may have been modified by hook
        $sql .= $hookmanager->resPrint;
        $sql .= " WHERE fd.fk_code_ventilation > 0";
        $sql .= " AND f.entity IN (" . getEntity('invoice', 0) . ')'; // We don't share object for accountancy, we use source object sharing
        $sql .= " AND f.fk_statut > 0";
        if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) { // Non common setup
            $sql .= " AND f.type IN (" . Facture::TYPE_STANDARD . "," . Facture::TYPE_REPLACEMENT . "," . Facture::TYPE_CREDIT_NOTE . "," . Facture::TYPE_SITUATION . ")";
        } else {
            $sql .= " AND f.type IN (" . Facture::TYPE_STANDARD . "," . Facture::TYPE_REPLACEMENT . "," . Facture::TYPE_CREDIT_NOTE . "," . Facture::TYPE_DEPOSIT . "," . Facture::TYPE_SITUATION . ")";
        }
        $sql .= " AND fd.product_type IN (0,1)";
        if ($date_start && $date_end) {
            $sql .= " AND f.datef >= '" . $db->idate($date_start) . "' AND f.datef <= '" . $db->idate($date_end) . "'";
        }
// Define begin binding date
        if (getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) {
            $sql .= " AND f.datef >= '" . $db->idate(getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) . "'";
        }
// Already in bookkeeping or not
        if ($in_bookkeeping == 'already') {
            $sql .= " AND f.rowid IN (SELECT fk_doc FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping as ab WHERE ab.doc_type='customer_invoice')";
            //  $sql .= " AND fd.rowid IN (SELECT fk_docdet FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping as ab WHERE ab.doc_type='customer_invoice')";     // Useless, we save one line for all products with same account
        }
        if ($in_bookkeeping == 'notyet') {
            $sql .= " AND f.rowid NOT IN (SELECT fk_doc FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping as ab WHERE ab.doc_type='customer_invoice')";
            // $sql .= " AND fd.rowid NOT IN (SELECT fk_docdet FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping as ab WHERE ab.doc_type='customer_invoice')";      // Useless, we save one line for all products with same account
        }
        $parameters = array();
        $reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
        $sql .= $hookmanager->resPrint;
        $sql .= " ORDER BY f.datef, f.ref";
//print $sql;

        dol_syslog('accountancy/journal/sellsjournal.php', LOG_DEBUG);
        $result = $db->query($sql);
        if ($result) {
            $tabfac = array();
            $tabht = array();
            $tabtva = array();
            $def_tva = array();
            $tabwarranty = array();
            $tabrevenuestamp = array();
            $tabttc = array();
            $tablocaltax1 = array();
            $tablocaltax2 = array();
            $tabcompany = array();
            $vatdata_cache = array();

            $num = $db->num_rows($result);

            // Variables
            $cptcli = getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER', 'NotDefined');
            $cpttva = getDolGlobalString('ACCOUNTING_VAT_SOLD_ACCOUNT', 'NotDefined');

            $i = 0;
            while ($i < $num) {
                $obj = $db->fetch_object($result);

                // Controls
                $compta_soc = (!empty($obj->code_compta)) ? $obj->code_compta : $cptcli;

                $compta_prod = $obj->compte;
                if (empty($compta_prod)) {
                    if ($obj->product_type == 0) {
                        $compta_prod = getDolGlobalString('ACCOUNTING_PRODUCT_SOLD_ACCOUNT', 'NotDefined');
                    } else {
                        $compta_prod = getDolGlobalString('ACCOUNTING_SERVICE_SOLD_ACCOUNT', 'NotDefined');
                    }
                }

                //$compta_revenuestamp = getDolGlobalString('ACCOUNTING_REVENUESTAMP_SOLD_ACCOUNT', 'NotDefined');

                $tax_id = $obj->tva_tx . ($obj->vat_src_code ? ' (' . $obj->vat_src_code . ')' : '');
                if (array_key_exists($tax_id, $vatdata_cache)) {
                    $vatdata = $vatdata_cache[$tax_id];
                } else {
                    $vatdata = getTaxesFromId($tax_id, $mysoc, $mysoc, 0);
                    $vatdata_cache[$tax_id] = $vatdata;
                }
                $compta_tva = (!empty($vatdata['accountancy_code_sell']) ? $vatdata['accountancy_code_sell'] : $cpttva);
                $compta_localtax1 = (!empty($vatdata['accountancy_code_sell']) ? $vatdata['accountancy_code_sell'] : $cpttva);
                $compta_localtax2 = (!empty($vatdata['accountancy_code_sell']) ? $vatdata['accountancy_code_sell'] : $cpttva);

                // Define the array to store the detail of each vat rate and code for lines
                if (price2num($obj->tva_tx) || !empty($obj->vat_src_code)) {
                    $def_tva[$obj->rowid][$compta_tva][vatrate($obj->tva_tx) . ($obj->vat_src_code ? ' (' . $obj->vat_src_code . ')' : '')] = (vatrate($obj->tva_tx) . ($obj->vat_src_code ? ' (' . $obj->vat_src_code . ')' : ''));
                }

                // Create a compensation rate for situation invoice.
                $situation_ratio = 1;
                if (getDolGlobalInt('INVOICE_USE_SITUATION') == 1) {
                    if ($obj->situation_cycle_ref) {
                        // Avoid divide by 0
                        if ($obj->situation_percent == 0) {
                            $situation_ratio = 0;
                        } else {
                            $line = new FactureLigne($db);
                            $line->fetch($obj->fdid);

                            // Situation invoices handling
                            $prev_progress = $line->get_prev_progress($obj->rowid);

                            $situation_ratio = ($obj->situation_percent - $prev_progress) / $obj->situation_percent;
                        }
                    }
                }

                $revenuestamp = (float) price2num($obj->revenuestamp, 'MT');

                // Invoice lines
                $tabfac[$obj->rowid]["date"] = $db->jdate($obj->df);
                $tabfac[$obj->rowid]["datereg"] = $db->jdate($obj->dlr);
                $tabfac[$obj->rowid]["ref"] = $obj->ref;
                $tabfac[$obj->rowid]["type"] = $obj->type;
                $tabfac[$obj->rowid]["description"] = $obj->label_compte;
                $tabfac[$obj->rowid]["close_code"] = $obj->close_code; // close_code = 'replaced' for replacement invoices (not used in most european countries)
                $tabfac[$obj->rowid]["revenuestamp"] = $revenuestamp;
                //$tabfac[$obj->rowid]["fk_facturedet"] = $obj->fdid;

                // Avoid warnings
                if (!isset($tabttc[$obj->rowid][$compta_soc])) {
                    $tabttc[$obj->rowid][$compta_soc] = 0;
                }
                if (!isset($tabht[$obj->rowid][$compta_prod])) {
                    $tabht[$obj->rowid][$compta_prod] = 0;
                }
                if (!isset($tabtva[$obj->rowid][$compta_tva])) {
                    $tabtva[$obj->rowid][$compta_tva] = 0;
                }
                if (!isset($tablocaltax1[$obj->rowid][$compta_localtax1])) {
                    $tablocaltax1[$obj->rowid][$compta_localtax1] = 0;
                }
                if (!isset($tablocaltax2[$obj->rowid][$compta_localtax2])) {
                    $tablocaltax2[$obj->rowid][$compta_localtax2] = 0;
                }

                // Compensation of data for invoice situation by using $situation_ratio. This works (nearly) for invoice that was not correctly recorded
                // but it may introduces an error for situation invoices that were correctly saved. There is still rounding problem that differs between
                // real data we should have stored and result obtained with a compensation.
                // It also seems that credit notes on situation invoices are correctly saved (but it depends on the version used in fact).
                // For credit notes, we hope to have situation_ratio = 1 so the compensation has no effect to avoid introducing troubles with credit notes.
                if (getDolGlobalInt('INVOICE_USE_SITUATION') == 1) {
                    $total_ttc = $obj->total_ttc * $situation_ratio;
                } else {
                    $total_ttc = $obj->total_ttc;
                }

                // Move a part of the retained warrenty into the account of warranty
                if (getDolGlobalString('INVOICE_USE_RETAINED_WARRANTY') && $obj->retained_warranty > 0) {
                    $retained_warranty = (float) price2num($total_ttc * $obj->retained_warranty / 100, 'MT');   // Calculate the amount of warrenty for this line (using the percent value)
                    $tabwarranty[$obj->rowid][$compta_soc] += $retained_warranty;
                    $total_ttc -= $retained_warranty;
                }

                $tabttc[$obj->rowid][$compta_soc] += $total_ttc;
                $tabht[$obj->rowid][$compta_prod] += $obj->total_ht * $situation_ratio;
                $tva_npr = ((($obj->info_bits & 1) == 1) ? 1 : 0);
                if (!$tva_npr) { // We ignore line if VAT is a NPR
                    $tabtva[$obj->rowid][$compta_tva] += $obj->total_tva * $situation_ratio;
                }
                $tablocaltax1[$obj->rowid][$compta_localtax1] += $obj->total_localtax1 * $situation_ratio;
                $tablocaltax2[$obj->rowid][$compta_localtax2] += $obj->total_localtax2 * $situation_ratio;

                $compta_revenuestamp = 'NotDefined';
                if (!empty($revenuestamp)) {
                    $sqlrevenuestamp = "SELECT accountancy_code_sell FROM " . MAIN_DB_PREFIX . "c_revenuestamp";
                    $sqlrevenuestamp .= " WHERE fk_pays = " . ((int) $mysoc->country_id);
                    $sqlrevenuestamp .= " AND taux = " . ((float) $revenuestamp);
                    $sqlrevenuestamp .= " AND active = 1";
                    $resqlrevenuestamp = $db->query($sqlrevenuestamp);

                    if ($resqlrevenuestamp) {
                        $num_rows_revenuestamp = $db->num_rows($resqlrevenuestamp);
                        if ($num_rows_revenuestamp > 1) {
                            dol_print_error($db, 'Failed 2 or more lines for the revenue stamp of your country. Check the dictionary of revenue stamp.');
                        } else {
                            $objrevenuestamp = $db->fetch_object($resqlrevenuestamp);
                            if ($objrevenuestamp) {
                                $compta_revenuestamp = $objrevenuestamp->accountancy_code_sell;
                            }
                        }
                    }
                }

                if (empty($tabrevenuestamp[$obj->rowid][$compta_revenuestamp]) && !empty($revenuestamp)) {
                    // The revenue stamp was never seen for this invoice id=$obj->rowid
                    $tabttc[$obj->rowid][$compta_soc] += $obj->revenuestamp;
                    $tabrevenuestamp[$obj->rowid][$compta_revenuestamp] = $obj->revenuestamp;
                }

                $tabcompany[$obj->rowid] = array(
                    'id' => $obj->socid,
                    'name' => $obj->name,
                    'code_client' => $obj->code_client,
                    'code_compta' => $compta_soc
                );

                $i++;
            }

            // After the loop on each line
        } else {
            dol_print_error($db);
        }

// Check for too many invoices first.
        if (count($tabfac) > 10000) {
            $error++;
            setEventMessages("TooManyInvoicesToProcessPleaseUseAMoreSelectiveFilter", null, 'errors');
        }

        $errorforinvoice = array();

        /*
        // Old way, 1 query for each invoice
        // Loop on all invoices to detect lines without binded code (fk_code_ventilation <= 0)
        foreach ($tabfac as $key => $val) {     // Loop on each invoice
            $sql = "SELECT COUNT(fd.rowid) as nb";
            $sql .= " FROM ".MAIN_DB_PREFIX."facturedet as fd";
            $sql .= " WHERE fd.product_type <= 2 AND fd.fk_code_ventilation <= 0";
            $sql .= " AND fd.total_ttc <> 0 AND fk_facture = ".((int) $key);
            $resql = $db->query($sql);
            if ($resql) {
                $obj = $db->fetch_object($resql);
                if ($obj->nb > 0) {
                    $errorforinvoice[$key] = 'somelinesarenotbound';
                }
            } else {
                dol_print_error($db);
            }
        }
        */
// New way, single query, load all unbound lines

        $sql = "
SELECT
    fk_facture,
    COUNT(fd.rowid) as nb
FROM
	" . MAIN_DB_PREFIX . "facturedet as fd
WHERE
    fd.product_type <= 2
    AND fd.fk_code_ventilation <= 0
    AND fd.total_ttc <> 0
	AND fk_facture IN (" . $db->sanitize(implode(",", array_keys($tabfac))) . ")
GROUP BY fk_facture
";
        $resql = $db->query($sql);

        $num = $db->num_rows($resql);
        $i = 0;
        while ($i < $num) {
            $obj = $db->fetch_object($resql);
            if ($obj->nb > 0) {
                $errorforinvoice[$obj->fk_facture_fourn] = 'somelinesarenotbound';
            }
            $i++;
        }
//var_dump($errorforinvoice);exit;

// Bookkeeping Write
        if ($action == 'writebookkeeping' && !$error) {
            $now = dol_now();
            $error = 0;

            $companystatic = new Societe($db);
            $invoicestatic = new Facture($db);
            $accountingaccountcustomer = new AccountingAccount($db);

            $accountingaccountcustomer->fetch(null, getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER'), true);

            $accountingaccountcustomerwarranty = new AccountingAccount($db);

            $accountingaccountcustomerwarranty->fetch(null, getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER_RETAINED_WARRANTY'), true);

            foreach ($tabfac as $key => $val) {     // Loop on each invoice
                $errorforline = 0;

                $totalcredit = 0;
                $totaldebit = 0;

                $db->begin();

                $companystatic->id = $tabcompany[$key]['id'];
                $companystatic->name = $tabcompany[$key]['name'];
                $companystatic->code_compta = $tabcompany[$key]['code_compta'];
                $companystatic->code_compta_client = $tabcompany[$key]['code_compta'];
                $companystatic->code_client = $tabcompany[$key]['code_client'];
                $companystatic->client = 3;

                $invoicestatic->id = $key;
                $invoicestatic->ref = (string) $val["ref"];
                $invoicestatic->type = $val["type"];
                $invoicestatic->close_code = $val["close_code"];

                $date = dol_print_date($val["date"], 'day');

                // Is it a replaced invoice ? 0=not a replaced invoice, 1=replaced invoice not yet dispatched, 2=replaced invoice dispatched
                $replacedinvoice = 0;
                if ($invoicestatic->close_code == Facture::CLOSECODE_REPLACED) {
                    $replacedinvoice = 1;
                    $alreadydispatched = $invoicestatic->getVentilExportCompta(); // Test if replaced invoice already into bookkeeping.
                    if ($alreadydispatched) {
                        $replacedinvoice = 2;
                    }
                }

                // If not already into bookkeeping, we won't add it. If yes, do nothing (should not happen because creating replacement not possible if invoice is accounted)
                if ($replacedinvoice == 1) {
                    $db->rollback();
                    continue;
                }

                // Error if some lines are not binded/ready to be journalized
                if ($errorforinvoice[$key] == 'somelinesarenotbound') {
                    $error++;
                    $errorforline++;
                    setEventMessages($langs->trans('ErrorInvoiceContainsLinesNotYetBounded', $val['ref']), null, 'errors');
                }

                // Warranty
                if (!$errorforline) {
                    if (is_array($tabwarranty[$key])) {
                        foreach ($tabwarranty[$key] as $k => $mt) {
                            $bookkeeping = new BookKeeping($db);
                            $bookkeeping->doc_date = $val["date"];
                            $bookkeeping->date_lim_reglement = $val["datereg"];
                            $bookkeeping->doc_ref = $val["ref"];
                            $bookkeeping->date_creation = $now;
                            $bookkeeping->doc_type = 'customer_invoice';
                            $bookkeeping->fk_doc = $key;
                            $bookkeeping->fk_docdet = 0; // Useless, can be several lines that are source of this record to add
                            $bookkeeping->thirdparty_code = $companystatic->code_client;

                            $bookkeeping->subledger_account = $tabcompany[$key]['code_compta'];
                            $bookkeeping->subledger_label = $tabcompany[$key]['name'];

                            $bookkeeping->numero_compte = getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER_RETAINED_WARRANTY');
                            $bookkeeping->label_compte = $accountingaccountcustomerwarranty->label;

                            $bookkeeping->label_operation = dol_trunc($companystatic->name, 16) . ' - ' . $invoicestatic->ref . ' - ' . $langs->trans("Retainedwarranty");
                            $bookkeeping->montant = $mt;
                            $bookkeeping->sens = ($mt >= 0) ? 'D' : 'C';
                            $bookkeeping->debit = ($mt >= 0) ? $mt : 0;
                            $bookkeeping->credit = ($mt < 0) ? -$mt : 0;
                            $bookkeeping->code_journal = $journal;
                            $bookkeeping->journal_label = $langs->transnoentities($journal_label);
                            $bookkeeping->fk_user_author = $user->id;
                            $bookkeeping->entity = $conf->entity;

                            $totaldebit += $bookkeeping->debit;
                            $totalcredit += $bookkeeping->credit;

                            $result = $bookkeeping->create($user);
                            if ($result < 0) {
                                if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists') {    // Already exists
                                    $error++;
                                    $errorforline++;
                                    $errorforinvoice[$key] = 'alreadyjournalized';
                                    //setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->fk_doc.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
                                } else {
                                    $error++;
                                    $errorforline++;
                                    $errorforinvoice[$key] = 'other';
                                    setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
                                }
                            }
                        }
                    }
                }

                // Thirdparty
                if (!$errorforline) {
                    foreach ($tabttc[$key] as $k => $mt) {
                        $bookkeeping = new BookKeeping($db);
                        $bookkeeping->doc_date = $val["date"];
                        $bookkeeping->date_lim_reglement = $val["datereg"];
                        $bookkeeping->doc_ref = $val["ref"];
                        $bookkeeping->date_creation = $now;
                        $bookkeeping->doc_type = 'customer_invoice';
                        $bookkeeping->fk_doc = $key;
                        $bookkeeping->fk_docdet = 0; // Useless, can be several lines that are source of this record to add
                        $bookkeeping->thirdparty_code = $companystatic->code_client;

                        $bookkeeping->subledger_account = $tabcompany[$key]['code_compta'];
                        $bookkeeping->subledger_label = $tabcompany[$key]['name'];

                        $bookkeeping->numero_compte = getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER');
                        $bookkeeping->label_compte = $accountingaccountcustomer->label;

                        $bookkeeping->label_operation = dol_trunc($companystatic->name, 16) . ' - ' . $invoicestatic->ref . ' - ' . $langs->trans("SubledgerAccount");
                        $bookkeeping->montant = $mt;
                        $bookkeeping->sens = ($mt >= 0) ? 'D' : 'C';
                        $bookkeeping->debit = ($mt >= 0) ? $mt : 0;
                        $bookkeeping->credit = ($mt < 0) ? -$mt : 0;
                        $bookkeeping->code_journal = $journal;
                        $bookkeeping->journal_label = $langs->transnoentities($journal_label);
                        $bookkeeping->fk_user_author = $user->id;
                        $bookkeeping->entity = $conf->entity;

                        $totaldebit += $bookkeeping->debit;
                        $totalcredit += $bookkeeping->credit;

                        $result = $bookkeeping->create($user);
                        if ($result < 0) {
                            if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists') {  // Already exists
                                $error++;
                                $errorforline++;
                                $errorforinvoice[$key] = 'alreadyjournalized';
                                //setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->fk_doc.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
                            } else {
                                $error++;
                                $errorforline++;
                                $errorforinvoice[$key] = 'other';
                                setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
                            }
                        } else {
                            if (getDolGlobalInt('ACCOUNTING_ENABLE_LETTERING') && getDolGlobalInt('ACCOUNTING_ENABLE_AUTOLETTERING')) {
                                require_once DOL_DOCUMENT_ROOT . '/accountancy/class/lettering.class.php';
                                $lettering_static = new Lettering($db);

                                $nb_lettering = $lettering_static->bookkeepingLettering(array($bookkeeping->id));
                            }
                        }
                    }
                }

                // Product / Service
                if (!$errorforline) {
                    foreach ($tabht[$key] as $k => $mt) {
                        $resultfetch = $accountingaccount->fetch(null, $k, true);   // TODO Use a cache
                        $label_account = $accountingaccount->label;

                        // get compte id and label
                        if ($resultfetch > 0) {
                            $bookkeeping = new BookKeeping($db);
                            $bookkeeping->doc_date = $val["date"];
                            $bookkeeping->date_lim_reglement = $val["datereg"];
                            $bookkeeping->doc_ref = $val["ref"];
                            $bookkeeping->date_creation = $now;
                            $bookkeeping->doc_type = 'customer_invoice';
                            $bookkeeping->fk_doc = $key;
                            $bookkeeping->fk_docdet = 0; // Useless, can be several lines that are source of this record to add
                            $bookkeeping->thirdparty_code = $companystatic->code_client;

                            if (getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER_USE_AUXILIARY_ON_DEPOSIT')) {
                                if ($k == getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER_DEPOSIT')) {
                                    $bookkeeping->subledger_account = $tabcompany[$key]['code_compta'];
                                    $bookkeeping->subledger_label = $tabcompany[$key]['name'];
                                } else {
                                    $bookkeeping->subledger_account = '';
                                    $bookkeeping->subledger_label = '';
                                }
                            } else {
                                $bookkeeping->subledger_account = '';
                                $bookkeeping->subledger_label = '';
                            }

                            $bookkeeping->numero_compte = $k;
                            $bookkeeping->label_compte = $label_account;

                            $bookkeeping->label_operation = dol_trunc($companystatic->name, 16) . ' - ' . $invoicestatic->ref . ' - ' . $label_account;
                            $bookkeeping->montant = $mt;
                            $bookkeeping->sens = ($mt < 0) ? 'D' : 'C';
                            $bookkeeping->debit = ($mt < 0) ? -$mt : 0;
                            $bookkeeping->credit = ($mt >= 0) ? $mt : 0;
                            $bookkeeping->code_journal = $journal;
                            $bookkeeping->journal_label = $langs->transnoentities($journal_label);
                            $bookkeeping->fk_user_author = $user->id;
                            $bookkeeping->entity = $conf->entity;

                            $totaldebit += $bookkeeping->debit;
                            $totalcredit += $bookkeeping->credit;

                            $result = $bookkeeping->create($user);
                            if ($result < 0) {
                                if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists') {  // Already exists
                                    $error++;
                                    $errorforline++;
                                    $errorforinvoice[$key] = 'alreadyjournalized';
                                    //setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->fk_doc.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
                                } else {
                                    $error++;
                                    $errorforline++;
                                    $errorforinvoice[$key] = 'other';
                                    setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
                                }
                            }
                        }
                    }
                }

                // VAT
                if (!$errorforline) {
                    $listoftax = array(0, 1, 2);
                    foreach ($listoftax as $numtax) {
                        $arrayofvat = $tabtva;
                        if ($numtax == 1) {
                            $arrayofvat = $tablocaltax1;
                        }
                        if ($numtax == 2) {
                            $arrayofvat = $tablocaltax2;
                        }

                        foreach ($arrayofvat[$key] as $k => $mt) {
                            if ($mt) {
                                $accountingaccount->fetch(null, $k, true);  // TODO Use a cache for label
                                $label_account = $accountingaccount->label;

                                $bookkeeping = new BookKeeping($db);
                                $bookkeeping->doc_date = $val["date"];
                                $bookkeeping->date_lim_reglement = $val["datereg"];
                                $bookkeeping->doc_ref = $val["ref"];
                                $bookkeeping->date_creation = $now;
                                $bookkeeping->doc_type = 'customer_invoice';
                                $bookkeeping->fk_doc = $key;
                                $bookkeeping->fk_docdet = 0; // Useless, can be several lines that are source of this record to add
                                $bookkeeping->thirdparty_code = $companystatic->code_client;

                                $bookkeeping->subledger_account = '';
                                $bookkeeping->subledger_label = '';

                                $bookkeeping->numero_compte = $k;
                                $bookkeeping->label_compte = $label_account;


                                $bookkeeping->label_operation = dol_trunc($companystatic->name, 16) . ' - ' . $invoicestatic->ref;
                                $tmpvatrate = (empty($def_tva[$key][$k]) ? (empty($arrayofvat[$key][$k]) ? '' : $arrayofvat[$key][$k]) : implode(', ', $def_tva[$key][$k]));
                                $bookkeeping->label_operation .= ' - ' . $langs->trans("Taxes") . ' ' . $tmpvatrate . ' %';
                                $bookkeeping->label_operation .= ($numtax ? ' - Localtax ' . $numtax : '');

                                $bookkeeping->montant = $mt;
                                $bookkeeping->sens = ($mt < 0) ? 'D' : 'C';
                                $bookkeeping->debit = ($mt < 0) ? -$mt : 0;
                                $bookkeeping->credit = ($mt >= 0) ? $mt : 0;
                                $bookkeeping->code_journal = $journal;
                                $bookkeeping->journal_label = $langs->transnoentities($journal_label);
                                $bookkeeping->fk_user_author = $user->id;
                                $bookkeeping->entity = $conf->entity;

                                $totaldebit += $bookkeeping->debit;
                                $totalcredit += $bookkeeping->credit;

                                $result = $bookkeeping->create($user);
                                if ($result < 0) {
                                    if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists') {  // Already exists
                                        $error++;
                                        $errorforline++;
                                        $errorforinvoice[$key] = 'alreadyjournalized';
                                        //setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->fk_doc.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
                                    } else {
                                        $error++;
                                        $errorforline++;
                                        $errorforinvoice[$key] = 'other';
                                        setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
                                    }
                                }
                            }
                        }
                    }
                }

                // Revenue stamp
                if (!$errorforline) {
                    if (is_array($tabrevenuestamp[$key])) {
                        foreach ($tabrevenuestamp[$key] as $k => $mt) {
                            if ($mt) {
                                $accountingaccount->fetch(null, $k, true);    // TODO Use a cache for label
                                $label_account = $accountingaccount->label;

                                $bookkeeping = new BookKeeping($db);
                                $bookkeeping->doc_date = $val["date"];
                                $bookkeeping->date_lim_reglement = $val["datereg"];
                                $bookkeeping->doc_ref = $val["ref"];
                                $bookkeeping->date_creation = $now;
                                $bookkeeping->doc_type = 'customer_invoice';
                                $bookkeeping->fk_doc = $key;
                                $bookkeeping->fk_docdet = 0; // Useless, can be several lines that are source of this record to add
                                $bookkeeping->thirdparty_code = $companystatic->code_client;

                                $bookkeeping->subledger_account = '';
                                $bookkeeping->subledger_label = '';

                                $bookkeeping->numero_compte = $k;
                                $bookkeeping->label_compte = $label_account;

                                $bookkeeping->label_operation = dol_trunc($companystatic->name, 16) . ' - ' . $invoicestatic->ref . ' - ' . $langs->trans("RevenueStamp");
                                $bookkeeping->montant = $mt;
                                $bookkeeping->sens = ($mt < 0) ? 'D' : 'C';
                                $bookkeeping->debit = ($mt < 0) ? -$mt : 0;
                                $bookkeeping->credit = ($mt >= 0) ? $mt : 0;
                                $bookkeeping->code_journal = $journal;
                                $bookkeeping->journal_label = $langs->transnoentities($journal_label);
                                $bookkeeping->fk_user_author = $user->id;
                                $bookkeeping->entity = $conf->entity;

                                $totaldebit += $bookkeeping->debit;
                                $totalcredit += $bookkeeping->credit;

                                $result = $bookkeeping->create($user);
                                if ($result < 0) {
                                    if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists') {    // Already exists
                                        $error++;
                                        $errorforline++;
                                        $errorforinvoice[$key] = 'alreadyjournalized';
                                        //setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->fk_doc.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
                                    } else {
                                        $error++;
                                        $errorforline++;
                                        $errorforinvoice[$key] = 'other';
                                        setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
                                    }
                                }
                            }
                        }
                    }
                }

                // Protection against a bug on lines before
                if (!$errorforline && (price2num($totaldebit, 'MT') != price2num($totalcredit, 'MT'))) {
                    $error++;
                    $errorforline++;
                    $errorforinvoice[$key] = 'amountsnotbalanced';
                    setEventMessages('We Tried to insert a non balanced transaction in book for ' . $invoicestatic->ref . '. Canceled. Surely a bug.', null, 'errors');
                }

                if (!$errorforline) {
                    $db->commit();
                } else {
                    $db->rollback();

                    if ($error >= 10) {
                        setEventMessages($langs->trans("ErrorTooManyErrorsProcessStopped"), null, 'errors');
                        break; // Break in the foreach
                    }
                }
            }

            $tabpay = $tabfac;

            if (empty($error) && count($tabpay) > 0) {
                setEventMessages($langs->trans("GeneralLedgerIsWritten"), null, 'mesgs');
            } elseif (count($tabpay) == $error) {
                setEventMessages($langs->trans("NoNewRecordSaved"), null, 'warnings');
            } else {
                setEventMessages($langs->trans("GeneralLedgerSomeRecordWasNotRecorded"), null, 'warnings');
            }

            $action = '';

            // Must reload data, so we make a redirect
            if (count($tabpay) != $error) {
                $param = 'id_journal=' . $id_journal;
                $param .= '&date_startday=' . $date_startday;
                $param .= '&date_startmonth=' . $date_startmonth;
                $param .= '&date_startyear=' . $date_startyear;
                $param .= '&date_endday=' . $date_endday;
                $param .= '&date_endmonth=' . $date_endmonth;
                $param .= '&date_endyear=' . $date_endyear;
                $param .= '&in_bookkeeping=' . $in_bookkeeping;
                header("Location: " . $_SERVER['PHP_SELF'] . ($param ? '?' . $param : ''));
                exit;
            }
        }



        /*
         * View
         */

        $form = new Form($db);

// Export
        if ($action == 'exportcsv' && !$error) {        // ISO and not UTF8 !
            // Note that to have the button to get this feature enabled, you must enable ACCOUNTING_ENABLE_EXPORT_DRAFT_JOURNAL
            $sep = getDolGlobalString('ACCOUNTING_EXPORT_SEPARATORCSV');

            $filename = 'journal';
            $type_export = 'journal';
            include DOL_DOCUMENT_ROOT . '/accountancy/tpl/export_journal.tpl.php';

            $companystatic = new Client($db);
            $invoicestatic = new Facture($db);

            foreach ($tabfac as $key => $val) {
                $companystatic->id = $tabcompany[$key]['id'];
                $companystatic->name = $tabcompany[$key]['name'];
                $companystatic->code_compta = $tabcompany[$key]['code_compta'];             // deprecated
                $companystatic->code_compta_client = $tabcompany[$key]['code_compta'];
                $companystatic->code_client = $tabcompany[$key]['code_client'];
                $companystatic->client = 3;

                $invoicestatic->id = $key;
                $invoicestatic->ref = (string) $val["ref"];
                $invoicestatic->type = $val["type"];
                $invoicestatic->close_code = $val["close_code"];

                $date = dol_print_date($val["date"], 'day');

                // Is it a replaced invoice ? 0=not a replaced invoice, 1=replaced invoice not yet dispatched, 2=replaced invoice dispatched
                $replacedinvoice = 0;
                if ($invoicestatic->close_code == Facture::CLOSECODE_REPLACED) {
                    $replacedinvoice = 1;
                    $alreadydispatched = $invoicestatic->getVentilExportCompta(); // Test if replaced invoice already into bookkeeping.
                    if ($alreadydispatched) {
                        $replacedinvoice = 2;
                    }
                }

                // If not already into bookkeeping, we won't add it. If yes, do nothing (should not happen because creating replacement not possible if invoice is accounted)
                if ($replacedinvoice == 1) {
                    continue;
                }

                // Warranty
                foreach ($tabwarranty[$key] as $k => $mt) {
                    //if ($mt) {
                    print '"' . $key . '"' . $sep;
                    print '"' . $date . '"' . $sep;
                    print '"' . $val["ref"] . '"' . $sep;
                    print '"' . mb_convert_encoding(dol_trunc($companystatic->name, 32), 'ISO-8859-1') . '"' . $sep;
                    print '"' . length_accounta(html_entity_decode($k)) . '"' . $sep;
                    print '"' . length_accountg(getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER_RETAINED_WARRANTY')) . '"' . $sep;
                    print '"' . length_accounta(html_entity_decode($k)) . '"' . $sep;
                    print '"' . $langs->trans("Thirdparty") . '"' . $sep;
                    print '"' . mb_convert_encoding(dol_trunc($companystatic->name, 16), 'ISO-8859-1') . ' - ' . $invoicestatic->ref . ' - ' . $langs->trans("Retainedwarranty") . '"' . $sep;
                    print '"' . ($mt >= 0 ? price($mt) : '') . '"' . $sep;
                    print '"' . ($mt < 0 ? price(-$mt) : '') . '"' . $sep;
                    print '"' . $journal . '"';
                    print "\n";
                    //}
                }

                // Third party
                foreach ($tabttc[$key] as $k => $mt) {
                    //if ($mt) {
                    print '"' . $key . '"' . $sep;
                    print '"' . $date . '"' . $sep;
                    print '"' . $val["ref"] . '"' . $sep;
                    print '"' . mb_convert_encoding(dol_trunc($companystatic->name, 32), 'ISO-8859-1') . '"' . $sep;
                    print '"' . length_accounta(html_entity_decode($k)) . '"' . $sep;
                    print '"' . length_accountg(getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER')) . '"' . $sep;
                    print '"' . length_accounta(html_entity_decode($k)) . '"' . $sep;
                    print '"' . $langs->trans("Thirdparty") . '"' . $sep;
                    print '"' . mb_convert_encoding(dol_trunc($companystatic->name, 16), 'ISO-8859-1') . ' - ' . $invoicestatic->ref . ' - ' . $langs->trans("Thirdparty") . '"' . $sep;
                    print '"' . ($mt >= 0 ? price($mt) : '') . '"' . $sep;
                    print '"' . ($mt < 0 ? price(-$mt) : '') . '"' . $sep;
                    print '"' . $journal . '"';
                    print "\n";
                    //}
                }

                // Product / Service
                foreach ($tabht[$key] as $k => $mt) {
                    $accountingaccount = new AccountingAccount($db);
                    $accountingaccount->fetch(null, $k, true);
                    //if ($mt) {
                    print '"' . $key . '"' . $sep;
                    print '"' . $date . '"' . $sep;
                    print '"' . $val["ref"] . '"' . $sep;
                    print '"' . mb_convert_encoding(dol_trunc($companystatic->name, 32), 'ISO-8859-1') . '"' . $sep;
                    print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
                    print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
                    print '""' . $sep;
                    print '"' . mb_convert_encoding(dol_trunc($accountingaccount->label, 32), 'ISO-8859-1') . '"' . $sep;
                    print '"' . mb_convert_encoding(dol_trunc($companystatic->name, 16), 'ISO-8859-1') . ' - ' . dol_trunc($accountingaccount->label, 32) . '"' . $sep;
                    print '"' . ($mt < 0 ? price(-$mt) : '') . '"' . $sep;
                    print '"' . ($mt >= 0 ? price($mt) : '') . '"' . $sep;
                    print '"' . $journal . '"';
                    print "\n";
                    //}
                }

                // VAT
                $listoftax = array(0, 1, 2);
                foreach ($listoftax as $numtax) {
                    $arrayofvat = $tabtva;
                    if ($numtax == 1) {
                        $arrayofvat = $tablocaltax1;
                    }
                    if ($numtax == 2) {
                        $arrayofvat = $tablocaltax2;
                    }

                    foreach ($arrayofvat[$key] as $k => $mt) {
                        if ($mt) {
                            print '"' . $key . '"' . $sep;
                            print '"' . $date . '"' . $sep;
                            print '"' . $val["ref"] . '"' . $sep;
                            print '"' . mb_convert_encoding(dol_trunc($companystatic->name, 32), 'ISO-8859-1') . '"' . $sep;
                            print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
                            print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
                            print '""' . $sep;
                            print '"' . $langs->trans("VAT") . ' - ' . implode(', ', $def_tva[$key][$k]) . ' %"' . $sep;
                            print '"' . mb_convert_encoding(dol_trunc($companystatic->name, 16), 'ISO-8859-1') . ' - ' . $invoicestatic->ref . ' - ' . $langs->trans("VAT") . implode(', ', $def_tva[$key][$k]) . ' %' . ($numtax ? ' - Localtax ' . $numtax : '') . '"' . $sep;
                            print '"' . ($mt < 0 ? price(-$mt) : '') . '"' . $sep;
                            print '"' . ($mt >= 0 ? price($mt) : '') . '"' . $sep;
                            print '"' . $journal . '"';
                            print "\n";
                        }
                    }
                }

                // Revenue stamp
                foreach ($tabrevenuestamp[$key] as $k => $mt) {
                    //if ($mt) {
                    print '"' . $key . '"' . $sep;
                    print '"' . $date . '"' . $sep;
                    print '"' . $val["ref"] . '"' . $sep;
                    print '"' . mb_convert_encoding(dol_trunc($companystatic->name, 32), 'ISO-8859-1') . '"' . $sep;
                    print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
                    print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
                    print '""' . $sep;
                    print '"' . $langs->trans("RevenueStamp") . '"' . $sep;
                    print '"' . mb_convert_encoding(dol_trunc($companystatic->name, 16), 'ISO-8859-1') . ' - ' . $invoicestatic->ref . ' - ' . $langs->trans("RevenueStamp") . '"' . $sep;
                    print '"' . ($mt < 0 ? price(-$mt) : '') . '"' . $sep;
                    print '"' . ($mt >= 0 ? price($mt) : '') . '"' . $sep;
                    print '"' . $journal . '"';
                    print "\n";
                    //}
                }
            }
        }



        if (empty($action) || $action == 'view') {
            $title = $langs->trans("GenerationOfAccountingEntries") . ' - ' . $accountingjournalstatic->getNomUrl(0, 2, 1, '', 1);

            llxHeader('', dol_string_nohtmltag($title));

            $nom = $title;
            $nomlink = '';
            $periodlink = '';
            $exportlink = '';
            $builddate = dol_now();
            $description = $langs->trans("DescJournalOnlyBindedVisible") . '<br>';
            if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
                $description .= $langs->trans("DepositsAreNotIncluded");
            } else {
                $description .= $langs->trans("DepositsAreIncluded");
            }

            $listofchoices = array('notyet' => $langs->trans("NotYetInGeneralLedger"), 'already' => $langs->trans("AlreadyInGeneralLedger"));
            $period = $form->selectDate($date_start ? $date_start : -1, 'date_start', 0, 0, 0, '', 1, 0) . ' - ' . $form->selectDate($date_end ? $date_end : -1, 'date_end', 0, 0, 0, '', 1, 0);
            $period .= ' -  ' . $langs->trans("JournalizationInLedgerStatus") . ' ' . $form->selectarray('in_bookkeeping', $listofchoices, $in_bookkeeping, 1);

            $varlink = 'id_journal=' . $id_journal;

            journalHead($nom, $nomlink, $period, $periodlink, $description, $builddate, $exportlink, array('action' => ''), '', $varlink);

            if (getDolGlobalString('ACCOUNTANCY_FISCAL_PERIOD_MODE') != 'blockedonclosed') {
                // Test that setup is complete (we are in accounting, so test on entity is always on $conf->entity only, no sharing allowed)
                // Fiscal period test
                $sql = "SELECT COUNT(rowid) as nb FROM " . MAIN_DB_PREFIX . "accounting_fiscalyear WHERE entity = " . ((int) $conf->entity);
                $resql = $db->query($sql);
                if ($resql) {
                    $obj = $db->fetch_object($resql);
                    if ($obj->nb == 0) {
                        print '<br><div class="warning">' . img_warning() . ' ' . $langs->trans("TheFiscalPeriodIsNotDefined");
                        $desc = ' : ' . $langs->trans("AccountancyAreaDescFiscalPeriod", 4, '{link}');
                        $desc = str_replace('{link}', '<strong>' . $langs->transnoentitiesnoconv("MenuAccountancy") . '-' . $langs->transnoentitiesnoconv("Setup") . "-" . $langs->transnoentitiesnoconv("FiscalPeriod") . '</strong>', $desc);
                        print $desc;
                        print '</div>';
                    }
                } else {
                    dol_print_error($db);
                }
            }

            // Button to write into Ledger
            $acctCustomerNotConfigured = in_array(getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER'), ['','-1']);
            if ($acctCustomerNotConfigured) {
                print '<br><div class="warning">' . img_warning() . ' ' . $langs->trans("SomeMandatoryStepsOfSetupWereNotDone");
                $desc = ' : ' . $langs->trans("AccountancyAreaDescMisc", 4, '{link}');
                $desc = str_replace('{link}', '<strong>' . $langs->transnoentitiesnoconv("MenuAccountancy") . '-' . $langs->transnoentitiesnoconv("Setup") . "-" . $langs->transnoentitiesnoconv("MenuDefaultAccounts") . '</strong>', $desc);
                print $desc;
                print '</div>';
            }
            print '<br><div class="tabsAction tabsActionNoBottom centerimp">';
            if (getDolGlobalString('ACCOUNTING_ENABLE_EXPORT_DRAFT_JOURNAL') && $in_bookkeeping == 'notyet') {
                print '<input type="button" class="butAction" name="exportcsv" value="' . $langs->trans("ExportDraftJournal") . '" onclick="launch_export();" />';
            }
            if ($acctCustomerNotConfigured) {
                print '<input type="button" class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans("SomeMandatoryStepsOfSetupWereNotDone")) . '" value="' . $langs->trans("WriteBookKeeping") . '" />';
            } else {
                if ($in_bookkeeping == 'notyet') {
                    print '<input type="button" class="butAction" name="writebookkeeping" value="' . $langs->trans("WriteBookKeeping") . '" onclick="writebookkeeping();" />';
                } else {
                    print '<a href="#" class="butActionRefused classfortooltip" name="writebookkeeping">' . $langs->trans("WriteBookKeeping") . '</a>';
                }
            }
            print '</div>';

            // TODO Avoid using js. We can use a direct link with $param
            print '
	<script type="text/javascript">
		function launch_export() {
			$("div.fiche form input[name=\"action\"]").val("exportcsv");
			$("div.fiche form input[type=\"submit\"]").click();
			$("div.fiche form input[name=\"action\"]").val("");
		}
		function writebookkeeping() {
			console.log("click on writebookkeeping");
			$("div.fiche form input[name=\"action\"]").val("writebookkeeping");
			$("div.fiche form input[type=\"submit\"]").click();
			$("div.fiche form input[name=\"action\"]").val("");
		}
	</script>';

            /*
             * Show result array
             */
            print '<br>';

            print '<div class="div-table-responsive">';
            print "<table class=\"noborder\" width=\"100%\">";
            print "<tr class=\"liste_titre\">";
            print "<td>" . $langs->trans("Date") . "</td>";
            print "<td>" . $langs->trans("Piece") . ' (' . $langs->trans("InvoiceRef") . ")</td>";
            print "<td>" . $langs->trans("AccountAccounting") . "</td>";
            print "<td>" . $langs->trans("SubledgerAccount") . "</td>";
            print "<td>" . $langs->trans("LabelOperation") . "</td>";
            print '<td class="center">' . $langs->trans("AccountingDebit") . "</td>";
            print '<td class="center">' . $langs->trans("AccountingCredit") . "</td>";
            print "</tr>\n";

            $i = 0;

            $companystatic = new Client($db);
            $invoicestatic = new Facture($db);

            foreach ($tabfac as $key => $val) {
                $companystatic->id = $tabcompany[$key]['id'];
                $companystatic->name = $tabcompany[$key]['name'];
                $companystatic->code_compta = $tabcompany[$key]['code_compta'];
                $companystatic->code_compta_client = $tabcompany[$key]['code_compta'];
                $companystatic->code_client = $tabcompany[$key]['code_client'];
                $companystatic->client = 3;

                $invoicestatic->id = $key;
                $invoicestatic->ref = (string) $val["ref"];
                $invoicestatic->type = $val["type"];
                $invoicestatic->close_code = $val["close_code"];

                $date = dol_print_date($val["date"], 'day');

                // Is it a replaced invoice ? 0=not a replaced invoice, 1=replaced invoice not yet dispatched, 2=replaced invoice dispatched
                $replacedinvoice = 0;
                if ($invoicestatic->close_code == Facture::CLOSECODE_REPLACED) {
                    $replacedinvoice = 1;
                    $alreadydispatched = $invoicestatic->getVentilExportCompta(); // Test if replaced invoice already into bookkeeping.
                    if ($alreadydispatched) {
                        $replacedinvoice = 2;
                    }
                }

                // If not already into bookkeeping, we won't add it, if yes, add the counterpart ???.
                if ($replacedinvoice == 1) {
                    print '<tr class="oddeven">';
                    print "<!-- Replaced invoice -->";
                    print "<td>" . $date . "</td>";
                    print "<td><strike>" . $invoicestatic->getNomUrl(1) . "</strike></td>";
                    // Account
                    print "<td>";
                    print $langs->trans("Replaced");
                    print '</td>';
                    // Subledger account
                    print "<td>";
                    print '</td>';
                    print "<td>";
                    print "</td>";
                    print '<td class="right"></td>';
                    print '<td class="right"></td>';
                    print "</tr>";

                    $i++;
                    continue;
                }
                if ($errorforinvoice[$key] == 'somelinesarenotbound') {
                    print '<tr class="oddeven">';
                    print "<!-- Some lines are not bound -->";
                    print "<td>" . $date . "</td>";
                    print "<td>" . $invoicestatic->getNomUrl(1) . "</td>";
                    // Account
                    print "<td>";
                    print '<span class="error">' . $langs->trans('ErrorInvoiceContainsLinesNotYetBoundedShort', $val['ref']) . '</span>';
                    print '</td>';
                    // Subledger account
                    print "<td>";
                    print '</td>';
                    print "<td>";
                    print "</td>";
                    print '<td class="right"></td>';
                    print '<td class="right"></td>';
                    print "</tr>";

                    $i++;
                }

                // Warranty
                if (is_array($tabwarranty[$key])) {
                    foreach ($tabwarranty[$key] as $k => $mt) {
                        print '<tr class="oddeven">';
                        print "<!-- Thirdparty warranty -->";
                        print "<td>" . $date . "</td>";
                        print "<td>" . $invoicestatic->getNomUrl(1) . "</td>";
                        // Account
                        print "<td>";
                        $accountoshow = length_accountg(getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER_RETAINED_WARRANTY'));
                        if (($accountoshow == "") || $accountoshow == 'NotDefined') {
                            print '<span class="error">' . $langs->trans("MainAccountForRetainedWarrantyNotDefined") . '</span>';
                        } else {
                            print $accountoshow;
                        }
                        print '</td>';
                        // Subledger account
                        print "<td>";
                        $accountoshow = length_accounta($k);
                        if (($accountoshow == "") || $accountoshow == 'NotDefined') {
                            print '<span class="error">' . $langs->trans("ThirdpartyAccountNotDefined") . '</span>';
                        } else {
                            print $accountoshow;
                        }
                        print '</td>';
                        print "<td>" . $companystatic->getNomUrl(0, 'customer', 16) . ' - ' . $invoicestatic->ref . ' - ' . $langs->trans("Retainedwarranty") . "</td>";
                        print '<td class="right nowraponall amount">' . ($mt >= 0 ? price($mt) : '') . "</td>";
                        print '<td class="right nowraponall amount">' . ($mt < 0 ? price(-$mt) : '') . "</td>";
                        print "</tr>";
                    }
                }

                // Third party
                foreach ($tabttc[$key] as $k => $mt) {
                    print '<tr class="oddeven">';
                    print "<!-- Thirdparty -->";
                    print "<td>" . $date . "</td>";
                    print "<td>" . $invoicestatic->getNomUrl(1) . "</td>";
                    // Account
                    print "<td>";
                    $accountoshow = length_accountg(getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER'));
                    if (($accountoshow == "") || $accountoshow == 'NotDefined') {
                        print '<span class="error">' . $langs->trans("MainAccountForCustomersNotDefined") . '</span>';
                    } else {
                        print $accountoshow;
                    }
                    print '</td>';
                    // Subledger account
                    print "<td>";
                    $accountoshow = length_accounta($k);
                    if (($accountoshow == "") || $accountoshow == 'NotDefined') {
                        print '<span class="error">' . $langs->trans("ThirdpartyAccountNotDefined") . '</span>';
                    } else {
                        print $accountoshow;
                    }
                    print '</td>';
                    print "<td>" . $companystatic->getNomUrl(0, 'customer', 16) . ' - ' . $invoicestatic->ref . ' - ' . $langs->trans("SubledgerAccount") . "</td>";
                    print '<td class="right nowraponall amount">' . ($mt >= 0 ? price($mt) : '') . "</td>";
                    print '<td class="right nowraponall amount">' . ($mt < 0 ? price(-$mt) : '') . "</td>";
                    print "</tr>";

                    $i++;
                }

                // Product / Service
                foreach ($tabht[$key] as $k => $mt) {
                    $accountingaccount = new AccountingAccount($db);
                    $accountingaccount->fetch(null, $k, true);

                    print '<tr class="oddeven">';
                    print "<!-- Product -->";
                    print "<td>" . $date . "</td>";
                    print "<td>" . $invoicestatic->getNomUrl(1) . "</td>";
                    // Account
                    print "<td>";
                    $accountoshow = length_accountg($k);
                    if (($accountoshow == "") || $accountoshow == 'NotDefined') {
                        print '<span class="error">' . $langs->trans("ProductNotDefined") . '</span>';
                    } else {
                        print $accountoshow;
                    }
                    print "</td>";
                    // Subledger account
                    print "<td>";
                    if (getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER_USE_AUXILIARY_ON_DEPOSIT')) {
                        if ($k == getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER_DEPOSIT')) {
                            print length_accounta($tabcompany[$key]['code_compta']);
                        }
                    } elseif (($accountoshow == "") || $accountoshow == 'NotDefined') {
                        print '<span class="error">' . $langs->trans("ThirdpartyAccountNotDefined") . '</span>';
                    }
                    print '</td>';
                    $companystatic->id = $tabcompany[$key]['id'];
                    $companystatic->name = $tabcompany[$key]['name'];
                    print "<td>" . $companystatic->getNomUrl(0, 'customer', 16) . ' - ' . $invoicestatic->ref . ' - ' . $accountingaccount->label . "</td>";
                    print '<td class="right nowraponall amount">' . ($mt < 0 ? price(-$mt) : '') . "</td>";
                    print '<td class="right nowraponall amount">' . ($mt >= 0 ? price($mt) : '') . "</td>";
                    print "</tr>";

                    $i++;
                }

                // VAT
                $listoftax = array(0, 1, 2);
                foreach ($listoftax as $numtax) {
                    $arrayofvat = $tabtva;
                    if ($numtax == 1) {
                        $arrayofvat = $tablocaltax1;
                    }
                    if ($numtax == 2) {
                        $arrayofvat = $tablocaltax2;
                    }

                    // $key is id of invoice
                    foreach ($arrayofvat[$key] as $k => $mt) {
                        if ($mt) {
                            print '<tr class="oddeven">';
                            print "<!-- VAT -->";
                            print "<td>" . $date . "</td>";
                            print "<td>" . $invoicestatic->getNomUrl(1) . "</td>";
                            // Account
                            print "<td>";
                            $accountoshow = length_accountg($k);
                            if (($accountoshow == "") || $accountoshow == 'NotDefined') {
                                print '<span class="error">' . $langs->trans("VATAccountNotDefined") . ' (' . $langs->trans("AccountingJournalType2") . ')</span>';
                            } else {
                                print $accountoshow;
                            }
                            print "</td>";
                            // Subledger account
                            print "<td>";
                            print '</td>';
                            print "<td>" . $companystatic->getNomUrl(0, 'customer', 16) . ' - ' . $invoicestatic->ref;
                            // $def_tva is array[invoiceid][accountancy_code_sell_of_vat_rate_found][vatrate]=vatrate
                            //var_dump($arrayofvat[$key]); //var_dump($key); //var_dump($k);
                            $tmpvatrate = (empty($def_tva[$key][$k]) ? (empty($arrayofvat[$key][$k]) ? '' : $arrayofvat[$key][$k]) : implode(', ', $def_tva[$key][$k]));
                            print ' - ' . $langs->trans("Taxes") . ' ' . $tmpvatrate . ' %';
                            print($numtax ? ' - Localtax ' . $numtax : '');
                            print "</td>";
                            print '<td class="right nowraponall amount">' . ($mt < 0 ? price(-$mt) : '') . "</td>";
                            print '<td class="right nowraponall amount">' . ($mt >= 0 ? price($mt) : '') . "</td>";
                            print "</tr>";

                            $i++;
                        }
                    }
                }

                // Revenue stamp
                if (is_array($tabrevenuestamp[$key])) {
                    foreach ($tabrevenuestamp[$key] as $k => $mt) {
                        print '<tr class="oddeven">';
                        print "<!-- Thirdparty revenuestamp -->";
                        print "<td>" . $date . "</td>";
                        print "<td>" . $invoicestatic->getNomUrl(1) . "</td>";
                        // Account
                        print "<td>";
                        $accountoshow = length_accountg($k);
                        if (($accountoshow == "") || $accountoshow == 'NotDefined') {
                            print '<span class="error">' . $langs->trans("MainAccountForRevenueStampSaleNotDefined") . '</span>';
                        } else {
                            print $accountoshow;
                        }
                        print '</td>';
                        // Subledger account
                        print "<td>";
                        print '</td>';
                        print "<td>" . $companystatic->getNomUrl(0, 'customer', 16) . ' - ' . $invoicestatic->ref . ' - ' . $langs->trans("RevenueStamp") . "</td>";
                        print '<td class="right nowraponall amount">' . ($mt < 0 ? price(-$mt) : '') . "</td>";
                        print '<td class="right nowraponall amount">' . ($mt >= 0 ? price($mt) : '') . "</td>";
                        print "</tr>";
                    }
                }
            }

            if (!$i) {
                print '<tr class="oddeven"><td colspan="7"><span class="opacitymedium">' . $langs->trans("NoRecordFound") . '</span></td></tr>';
            }

            print "</table>";
            print '</div>';

            // End of page
            llxFooter();
        }

        $db->close();
    }

    /**
     * \file        htdocs/accountancy/journal/variousjournal.php
     * \ingroup     Accountancy (Double entries)
     * \brief       Page of a journal
     */
    public function variousjournal()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;
        global $mysoc;

// Load translation files required by the page
        $langs->loadLangs(array("banks", "accountancy", "compta", "other", "errors"));

        $id_journal = GETPOSTINT('id_journal');
        $action = GETPOST('action', 'aZ09');

        $date_startmonth = GETPOST('date_startmonth');
        $date_startday = GETPOST('date_startday');
        $date_startyear = GETPOST('date_startyear');
        $date_endmonth = GETPOST('date_endmonth');
        $date_endday = GETPOST('date_endday');
        $date_endyear = GETPOST('date_endyear');
        $in_bookkeeping = GETPOST('in_bookkeeping');
        if ($in_bookkeeping == '') {
            $in_bookkeeping = 'notyet';
        }

// Get information of journal
        $object = new AccountingJournal($db);
        $result = $object->fetch($id_journal);
        if ($result > 0) {
            $id_journal = $object->id;
        } elseif ($result < 0) {
            dol_print_error(null, $object->error, $object->errors);
        } elseif ($result == 0) {
            accessforbidden('ErrorRecordNotFound');
        }

        $hookmanager->initHooks(array('globaljournal', $object->nature . 'journal'));
        $parameters = array();

        $date_start = dol_mktime(0, 0, 0, $date_startmonth, $date_startday, $date_startyear);
        $date_end = dol_mktime(23, 59, 59, $date_endmonth, $date_endday, $date_endyear);

        if (empty($date_startmonth)) {
            // Period by default on transfer
            $dates = getDefaultDatesForTransfer();
            $date_start = $dates['date_start'];
            $pastmonthyear = $dates['pastmonthyear'];
            $pastmonth = $dates['pastmonth'];
        }
        if (empty($date_endmonth)) {
            // Period by default on transfer
            $dates = getDefaultDatesForTransfer();
            $date_end = $dates['date_end'];
            $pastmonthyear = $dates['pastmonthyear'];
            $pastmonth = $dates['pastmonth'];
        }

        if (!GETPOSTISSET('date_startmonth') && (empty($date_start) || empty($date_end))) { // We define date_start and date_end, only if we did not submit the form
            $date_start = dol_get_first_day($pastmonthyear, $pastmonth, false);
            $date_end = dol_get_last_day($pastmonthyear, $pastmonth, false);
        }

        $data_type = 'view';
        if ($action == 'writebookkeeping') {
            $data_type = 'bookkeeping';
        }
        if ($action == 'exportcsv') {
            $data_type = 'csv';
        }
        $journal_data = $object->getData($user, $data_type, $date_start, $date_end, $in_bookkeeping);
        if (!is_array($journal_data)) {
            setEventMessages($object->error, $object->errors, 'errors');
        }

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

        $reshook = $hookmanager->executeHooks('doActions', $parameters, $user, $action); // Note that $action and $object may have been modified by some hooks

        $reload = false;

// Bookkeeping Write
        if ($action == 'writebookkeeping') {
            $error = 0;

            $result = $object->writeIntoBookkeeping($user, $journal_data);
            if ($result < 0) {
                setEventMessages($object->error, $object->errors, 'errors');
                $error = abs($result);
            }

            $nb_elements = count($journal_data);
            if (empty($error) && $nb_elements > 0) {
                setEventMessages($langs->trans("GeneralLedgerIsWritten"), null, 'mesgs');
            } elseif ($nb_elements == $error) {
                setEventMessages($langs->trans("NoNewRecordSaved"), null, 'warnings');
            } else {
                setEventMessages($langs->trans("GeneralLedgerSomeRecordWasNotRecorded"), null, 'warnings');
            }

            $reload = true;
        } elseif ($action == 'exportcsv') {
            // Export CSV
            $result = $object->exportCsv($journal_data, $date_end);
            if ($result < 0) {
                setEventMessages($object->error, $object->errors, 'errors');
                $reload = true;
            } else {
                $filename = 'journal';
                $type_export = 'journal';

                require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
                include DOL_DOCUMENT_ROOT . '/accountancy/tpl/export_journal.tpl.php';

                print $result;

                $db->close();
                exit();
            }
        }

// Must reload data, so we make a redirect
        if ($reload) {
            $param = 'id_journal=' . $id_journal;
            $param .= '&date_startday=' . $date_startday;
            $param .= '&date_startmonth=' . $date_startmonth;
            $param .= '&date_startyear=' . $date_startyear;
            $param .= '&date_endday=' . $date_endday;
            $param .= '&date_endmonth=' . $date_endmonth;
            $param .= '&date_endyear=' . $date_endyear;
            $param .= '&in_bookkeeping=' . $in_bookkeeping;
            header("Location: " . $_SERVER['PHP_SELF'] . ($param ? '?' . $param : ''));
            exit;
        }


        /*
         * View
         */

        $form = new Form($db);

        if ($object->nature == 2) {
            $some_mandatory_steps_of_setup_were_not_done = getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER') == "" || getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER') == '-1';
            $account_accounting_not_defined = getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER') == "" || getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER') == '-1';
        } elseif ($object->nature == 3) {
            $some_mandatory_steps_of_setup_were_not_done = getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER') == "" || getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER') == '-1';
            $account_accounting_not_defined = getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER') == "" || getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER') == '-1';
        } elseif ($object->nature == 4) {
            $some_mandatory_steps_of_setup_were_not_done = getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER') == "" || getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER') == '-1'
                || getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER') == "" || getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER') == '-1'
                || !getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT') || getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT') == '-1';
            $account_accounting_not_defined = getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER') == "" || getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER') == '-1'
                || getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER') == "" || getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER') == '-1';
        } elseif ($object->nature == 5) {
            $some_mandatory_steps_of_setup_were_not_done = !getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT') || getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT') == '-1';
            $account_accounting_not_defined = !getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT') || getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT') == '-1';
        } else {
            $title = $object->getLibType();
            $some_mandatory_steps_of_setup_were_not_done = false;
            $account_accounting_not_defined = false;
        }

        $title = $langs->trans("GenerationOfAccountingEntries") . ' - ' . $object->getNomUrl(0, 2, 1, '', 1);

        llxHeader('', dol_string_nohtmltag($title));

        $nom = $title;
        $nomlink = '';
        $periodlink = '';
        $exportlink = '';
        $builddate = dol_now();
        $description = $langs->trans("DescJournalOnlyBindedVisible") . '<br>';
        if ($object->nature == 2 || $object->nature == 3) {
            if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
                $description .= $langs->trans("DepositsAreNotIncluded");
            } else {
                $description .= $langs->trans("DepositsAreIncluded");
            }
            if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {
                $description .= $langs->trans("SupplierDepositsAreNotIncluded");
            }
        }

        $listofchoices = array('notyet' => $langs->trans("NotYetInGeneralLedger"), 'already' => $langs->trans("AlreadyInGeneralLedger"));
        $period = $form->selectDate($date_start ? $date_start : -1, 'date_start', 0, 0, 0, '', 1, 0) . ' - ' . $form->selectDate($date_end ? $date_end : -1, 'date_end', 0, 0, 0, '', 1, 0);
        $period .= ' -  ' . $langs->trans("JournalizationInLedgerStatus") . ' ' . $form->selectarray('in_bookkeeping', $listofchoices, $in_bookkeeping, 1);

        $varlink = 'id_journal=' . $id_journal;

        journalHead($nom, $nomlink, $period, $periodlink, $description, $builddate, $exportlink, array('action' => ''), '', $varlink);

        if (getDolGlobalString('ACCOUNTANCY_FISCAL_PERIOD_MODE') != 'blockedonclosed') {
            // Test that setup is complete (we are in accounting, so test on entity is always on $conf->entity only, no sharing allowed)
            // Fiscal period test
            $sql = "SELECT COUNT(rowid) as nb FROM " . MAIN_DB_PREFIX . "accounting_fiscalyear WHERE entity = " . ((int) $conf->entity);
            $resql = $db->query($sql);
            if ($resql) {
                $obj = $db->fetch_object($resql);
                if ($obj->nb == 0) {
                    print '<br><div class="warning">' . img_warning() . ' ' . $langs->trans("TheFiscalPeriodIsNotDefined");
                    $desc = ' : ' . $langs->trans("AccountancyAreaDescFiscalPeriod", 4, '{link}');
                    $desc = str_replace('{link}', '<strong>' . $langs->transnoentitiesnoconv("MenuAccountancy") . '-' . $langs->transnoentitiesnoconv("Setup") . "-" . $langs->transnoentitiesnoconv("FiscalPeriod") . '</strong>', $desc);
                    print $desc;
                    print '</div>';
                }
            } else {
                dol_print_error($db);
            }
        }

        if ($object->nature == 4) { // Bank journal
            // Test that setup is complete (we are in accounting, so test on entity is always on $conf->entity only, no sharing allowed)
            $sql = "SELECT COUNT(rowid) as nb";
            $sql .= " FROM " . MAIN_DB_PREFIX . "bank_account";
            $sql .= " WHERE entity = " . (int) $conf->entity;
            $sql .= " AND fk_accountancy_journal IS NULL";
            $sql .= " AND clos=0";
            $resql = $db->query($sql);
            if ($resql) {
                $obj = $db->fetch_object($resql);
                if ($obj->nb > 0) {
                    print '<br>' . img_warning() . ' ' . $langs->trans("TheJournalCodeIsNotDefinedOnSomeBankAccount");
                    print ' : ' . $langs->trans("AccountancyAreaDescBank", 9, '<strong>' . $langs->transnoentitiesnoconv("MenuAccountancy") . '-' . $langs->transnoentitiesnoconv("Setup") . "-" . $langs->transnoentitiesnoconv("BankAccounts") . '</strong>');
                }
            } else {
                dol_print_error($db);
            }
        }

// Button to write into Ledger
        if ($some_mandatory_steps_of_setup_were_not_done) {
            print '<br><div class="warning">' . img_warning() . ' ' . $langs->trans("SomeMandatoryStepsOfSetupWereNotDone");
            print ' : ' . $langs->trans("AccountancyAreaDescMisc", 4, '<strong>' . $langs->transnoentitiesnoconv("MenuAccountancy") . '-' . $langs->transnoentitiesnoconv("Setup") . "-" . $langs->transnoentitiesnoconv("MenuDefaultAccounts") . '</strong>');
            print '</div>';
        }
        print '<br><div class="tabsAction tabsActionNoBottom centerimp">';
        if (getDolGlobalString('ACCOUNTING_ENABLE_EXPORT_DRAFT_JOURNAL') && $in_bookkeeping == 'notyet') {
            print '<input type="button" class="butAction" name="exportcsv" value="' . $langs->trans("ExportDraftJournal") . '" onclick="launch_export();" />';
        }
        if ($account_accounting_not_defined) {
            print '<input type="button" class="butActionRefused classfortooltip" title="' . dol_escape_htmltag($langs->trans("SomeMandatoryStepsOfSetupWereNotDone")) . '" value="' . $langs->trans("WriteBookKeeping") . '" />';
        } else {
            if ($in_bookkeeping == 'notyet') {
                print '<input type="button" class="butAction" name="writebookkeeping" value="' . $langs->trans("WriteBookKeeping") . '" onclick="writebookkeeping();" />';
            } else {
                print '<a href="#" class="butActionRefused classfortooltip" name="writebookkeeping">' . $langs->trans("WriteBookKeeping") . '</a>';
            }
        }
        print '</div>';

// TODO Avoid using js. We can use a direct link with $param
        print '
	<script type="text/javascript">
		function launch_export() {
			$("div.fiche form input[name=\"action\"]").val("exportcsv");
			$("div.fiche form input[type=\"submit\"]").click();
			$("div.fiche form input[name=\"action\"]").val("");
		}
		function writebookkeeping() {
			console.log("click on writebookkeeping");
			$("div.fiche form input[name=\"action\"]").val("writebookkeeping");
			$("div.fiche form input[type=\"submit\"]").click();
			$("div.fiche form input[name=\"action\"]").val("");
		}
	</script>';

        $object_label = $langs->trans("ObjectsRef");
        if ($object->nature == 2 || $object->nature == 3) {
            $object_label = $langs->trans("InvoiceRef");
        }
        if ($object->nature == 5) {
            $object_label = $langs->trans("ExpenseReportRef");
        }


// Show result array
        $i = 0;

        print '<br>';

        print '<div class="div-table-responsive">';
        print '<table class="noborder centpercent">';
        print '<tr class="liste_titre">';
        print '<td>' . $langs->trans("Date") . '</td>';
        print '<td>' . $langs->trans("Piece") . ' (' . $object_label . ')</td>';
        print '<td>' . $langs->trans("AccountAccounting") . '</td>';
        print '<td>' . $langs->trans("SubledgerAccount") . '</td>';
        print '<td>' . $langs->trans("LabelOperation") . '</td>';
        if ($object->nature == 4) {
            print '<td class="center">' . $langs->trans("PaymentMode") . '</td>';
        } // bank
        print '<td class="right">' . $langs->trans("AccountingDebit") . '</td>';
        print '<td class="right">' . $langs->trans("AccountingCredit") . '</td>';
        print "</tr>\n";

        if (is_array($journal_data) && !empty($journal_data)) {
            foreach ($journal_data as $element_id => $element) {
                foreach ($element['blocks'] as $lines) {
                    foreach ($lines as $line) {
                        print '<tr class="oddeven">';
                        print '<td>' . $line['date'] . '</td>';
                        print '<td>' . $line['piece'] . '</td>';
                        print '<td>' . $line['account_accounting'] . '</td>';
                        print '<td>' . $line['subledger_account'] . '</td>';
                        print '<td>' . $line['label_operation'] . '</td>';
                        if ($object->nature == 4) {
                            print '<td class="center">' . $line['payment_mode'] . '</td>';
                        }
                        print '<td class="right nowraponall">' . $line['debit'] . '</td>';
                        print '<td class="right nowraponall">' . $line['credit'] . '</td>';
                        print '</tr>';

                        $i++;
                    }
                }
            }
        }

        if (!$i) {
            $colspan = 7;
            if ($object->nature == 4) {
                $colspan++;
            }
            print '<tr class="oddeven"><td colspan="' . $colspan . '"><span class="opacitymedium">' . $langs->trans("NoRecordFound") . '</span></td></tr>';
        }

        print '</table>';
        print '</div>';

        llxFooter();

        $db->close();
    }
}
