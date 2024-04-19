<?php

// NO_USER_REQUIRED!
defineIfNotDefined('NOREQUIREUSER', '1');
if (!defined('NOREQUIREDB')) {
    define('NOREQUIREDB', '1');
}
defineIfNotDefined('NOREQUIRESOC', '1');
defineIfNotDefined('NOREQUIRETRAN', '1');
if (!defined('NOSTYLECHECK')) {
    define('NOSTYLECHECK', '1'); // Do not check style html tag into posted data
}
defineIfNotDefined('NOREQUIREMENU', '1'); // If there is no need to load and show top and left menu
defineIfNotDefined('NOREQUIREHTML', '1'); // If we don't need to load the html.form.class.php
defineIfNotDefined('NOREQUIREAJAX', '1'); // Do not load ajax.lib.php library
defineIfNotDefined('NOLOGIN', '1'); // If this page is public (can be called outside logged session)
// If you don't need session management (can't be logged if no session used). You must also set
// NOCSRFCHECK, NOTOKENRENEWAL, NOLOGIN
// Disable module with GETPOST('disablemodules') won't work. Variable 'dol_...' will not be set.
// $_SESSION are then simple vars if sessions are not active.
// TODO We can close session with session_write_close() as soon as we just need read access everywhere in code.
defineIfNotDefined('NOSESSION', '1');


// Special
// We add header and output some content before the include of main.inc.php !!
// Because we need to So we can make
header("Content-type: text/html; charset=UTF8");

// Security options
header("X-Content-Type-Options: nosniff"); // With the nosniff option, if the server says the content is text/html, the browser will render it as text/html (note that most browsers now force this option to on)
header("X-Frame-Options: SAMEORIGIN"); // Frames allowed only if on same domain (stop some XSS attacks)


print "Legend:<br>\n";
print 'PHP_SESSION_DISABLED=' . PHP_SESSION_DISABLED . "<br>\n";
print 'PHP_SESSION_NONE=' . PHP_SESSION_NONE . "<br>\n";
print 'PHP_SESSION_ACTIVE=' . PHP_SESSION_ACTIVE . "<br>\n";
print '<br>';

print 'session_status=' . session_status() . ' (before main.inc.php)';
print '<br>';

// Load Dolibarr environment
require BASE_PATH . '/main.inc.php';

// Security
if ($dolibarr_main_prod) {
    accessforbidden();
}


/*
 * View
 */

print 'session_status=' . session_status() . ' (after main.inc.php)';
print '<br>';

//print 'a'.$_SESSION['disablemodules'].'b';

print "\n<br>This page is visible. It means you are not locked by another page called in same session.";

//session_write_close();
