<?php

/* Copyright (C) 2020   Andreu Bisquerra Gaya <jove@bisquerra.com>
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
 *  \file       htdocs/takepos/smpcb.php
 *  \ingroup    takepos
 *  \brief      Page with the content for smpcb payment
 */

defineIfNotDefined('NOTOKENRENEWAL', '1');
defineIfNotDefined('NOREQUIREMENU', '1');
defineIfNotDefined('NOREQUIREHTML', '1');
defineIfNotDefined('NOREQUIREAJAX', '1');

// Load Dolibarr environment
require BASE_PATH . '/main.inc.php';

if (!$user->hasRight('takepos', 'run')) {
    accessforbidden();
}

if (GETPOSTISSET('status')) {
    die(strtoupper($_SESSION['SMP_CURRENT_PAYMENT']));
}


/*
 * View
 */

top_httphead('text/html', 1);

if (GETPOST('smp-status')) {
    print '<html lang="en">';
    print '<head>';
    print '<meta charset="utf-8">

    <title>The HTML5 Herald</title>
    <meta name="description" content="The HTML5 Herald">
    <meta name="author" content="SitePoint">

    <link rel="stylesheet" href="css/styles.css?v=1.0">';

    print '</head>';

    print '<body>';
    $_SESSION['SMP_CURRENT_PAYMENT'] = GETPOST('smp-status');

    print '<script type="application/javascript">
                window.onload = function() {
                    window.close();
                }
            </script>';

    print "Transaction status registered, you can close this";

    print '</body></html>';
    exit();
}

print 'NOOP';
