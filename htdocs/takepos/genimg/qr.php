<?php

/* Copyright (C) 2020   Andreu Bisquerra    <jove@bisquerra.com>
 * Copyright (C) 2024	Laurent Destailleur <eldy@users.sourceforge.net>
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

// This page return an image of a QR code of a public link
// Note: Generating a QR code from a string, like done by this script, can be done with any online tool.

defineIfNotDefined('NOLOGIN', '1'); // If this page is public (can be called outside logged session)
defineIfNotDefined('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
defineIfNotDefined('NOREQUIRESOC', '1');
defineIfNotDefined('NOTOKENRENEWAL', '1');
defineIfNotDefined('NOREQUIREMENU', '1');
defineIfNotDefined('NOREQUIREHTML', '1');
defineIfNotDefined('NOREQUIREAJAX', '1');

// Load Dolibarr environment
require BASE_PATH . '/main.inc.php'; // Load $user and permissions
require '../../core/modules/barcode/doc/tcpdfbarcode.modules.php';

$urlwithouturlroot = preg_replace('/' . preg_quote(DOL_URL_ROOT, '/') . '$/i', '', trim($dolibarr_main_url_root));
$urlwithroot = $urlwithouturlroot . DOL_URL_ROOT; // This is to use external domain name found into config file

if (!isModEnabled('takepos')) {
    accessforbidden('Module not enabled');
}


/*
 * View
 */

// The buildBarCode does not include the http headers but this is a page that just return an image.

if (GETPOSTISSET("key")) {
    $key = GETPOST('key');
    $module = new modTcpdfbarcode();
    $result = $module->buildBarCode($urlwithroot . "/takepos/public/auto_order.php?key=" . urlencode($key), 'QRCODE', 'Y');
} else {
    $module = new modTcpdfbarcode();
    $result = $module->buildBarCode($urlwithroot . "/takepos/public/menu.php", 'QRCODE', 'Y');
}
