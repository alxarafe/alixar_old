<?php

/*
 * Copyright (C) 2020       Laurent Destailleur     <eldy@users.sourceforge.net>
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

/**
 *  \file       htdocs/public/ticket/ajax/ajax.php
 *  \brief      Ajax component for Ticket.
 *
 *  This ajax component is called only by the create ticket public page. And only if TICKET_CREATE_THIRD_PARTY_WITH_CONTACT_IF_NOT_EXIST is set.
 *  This option TICKET_CREATE_THIRD_PARTY_WITH_CONTACT_IF_NOT_EXIST has been removed because it is a security hole.
 */

use DoliModules\Ticket\Model\Ticket;

if (!defined('NOTOKENRENEWAL')) {
    define('NOTOKENRENEWAL', '1'); // Disables token renewal
}
defineIfNotDefined('NOREQUIREHTML', '1');
defineIfNotDefined('NOREQUIREAJAX', '1');
defineIfNotDefined('NOREQUIRESOC', '1');
// You can get information if module "Agenda" has been enabled by reading the
defineIfNotDefined('NOREQUIREMENU', '1');
if (!defined("NOLOGIN")) {
    define("NOLOGIN", '1');
}
defineIfNotDefined('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
defineIfNotDefined('NOBROWSERNOTIF', '1');

include_once '../../../main.inc.php'; // Load $user and permissions

$action = GETPOST('action', 'aZ09');
$id = GETPOSTINT('id');
$email = GETPOST('email', 'custom', 0, FILTER_VALIDATE_EMAIL);


if (!isModEnabled('ticket')) {
    httponly_accessforbidden('Module Ticket not enabled');
}

if (!getDolGlobalString('TICKET_CREATE_THIRD_PARTY_WITH_CONTACT_IF_NOT_EXIST')) {
    httponly_accessforbidden('Option TICKET_CREATE_THIRD_PARTY_WITH_CONTACT_IF_NOT_EXIST of module ticket is not enabled');
}


/*
 * View
 */

top_httphead();

if ($action == 'getContacts') {
    $return = array(
        'contacts' => array(),
        'error' => '',
    );

    if (!empty($email)) {
        $ticket = new Ticket($db);
        $arrayofcontacts = $ticket->searchContactByEmail($email);
        if (is_array($arrayofcontacts)) {
            $arrayofminimalcontacts = array();
            foreach ($arrayofcontacts as $tmpval) {
                $tmpresult = new stdClass();
                $tmpresult->id = $tmpval->id;
                $tmpresult->firstname = $tmpval->firstname;
                $tmpresult->lastname = $tmpval->lastname;
                $arrayofminimalcontacts[] = $tmpresult;
            }

            $return['contacts'] = $arrayofminimalcontacts;
        } else {
            $return['error'] = $ticket->errorsToString();
        }
    }

    echo json_encode($return);
    exit();
}
