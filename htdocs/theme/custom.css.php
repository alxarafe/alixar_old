<?php

/**
 * custom.css.php
 *
 * Copyright (c) 2023 Eric Seigne <eric.seigne@cap-rel.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

defineIfNotDefined('NOREQUIRESOC', '1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');  // Not disabled because need to do translations
defineIfNotDefined('NOCSRFCHECK', '1');
defineIfNotDefined('NOTOKENRENEWAL', '1');
defineIfNotDefined('NOLOGIN', '1'); // File must be accessed by logon page so without login.
defineIfNotDefined('NOREQUIREHTML', '1');
defineIfNotDefined('NOREQUIREAJAX', '1');

session_cache_limiter('public');

require_once __DIR__ . '/../main.inc.php'; // __DIR__ allow this script to be included in custom themes
require_once BASE_PATH . '/../Dolibarr/Lib/Functions2.php';

// Define css type
top_httphead('text/css');
// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
if (empty($dolibarr_nocache)) {
    header('Cache-Control: max-age=10800, public, must-revalidate');
} else {
    header('Cache-Control: no-cache');
}


print '/* Here, the content of the common custom CSS defined into Home - Setup - Display - CSS' . "*/\n";
print getDolGlobalString('MAIN_IHM_CUSTOM_CSS');
