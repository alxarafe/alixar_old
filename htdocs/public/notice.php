<?php

/* Copyright (C) 2016-2021  Laurent Destailleur     <eldy@users.sourceforge.net>
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
 *  \file       htdocs/public/notice.php
 *  \brief      Dolibarr public page to show a notice.
 *              Default notice is a message to say network connection is off. Some parameters can be used to show another message:
 *              /public/notice.php?lang=xx_XX&transkey=translation_key          (key must be inside file main.lang, error.lang or other.lang)
 *              /public/notice.php?transphrase=sentence_to_show
 */

defineIfNotDefined('NOLOGIN', '1'); // This means this output page does not require to be logged.
defineIfNotDefined('NOCSRFCHECK', '1'); // We accept to go on this page from external web site.
defineIfNotDefined('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
defineIfNotDefined('NOSESSION', '1');
defineIfNotDefined('NOBROWSERNOTIF', '1');

// Load Dolibarr environment
require BASE_PATH . '/main.inc.php';


/**
 * View
 */

if (!GETPOST('transkey', 'alphanohtml') && !GETPOST('transphrase', 'alphanohtml')) {
    print 'Sorry, it seems your internet connection is off.<br>';
    print 'You need to be connected to network to use this software.<br>';
} else {
    $langs->loadLangs(array("error", "other"));

    if (GETPOST('transphrase', 'alphanohtml')) {
        print dol_escape_htmltag(GETPOST('transphrase', 'alphanohtml'));
    } elseif (GETPOST('transkey', 'alphanohtml')) {
        print dol_escape_htmltag($langs->trans(GETPOST('transkey', 'alphanohtml')));
    }
}

$db->close();
