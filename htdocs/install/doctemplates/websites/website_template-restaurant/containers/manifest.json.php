<?php // BEGIN PHP
$websitekey = basename(__DIR__);
if (! defined('USEDOLIBARRSERVER') && ! defined('USEDOLIBARREDITOR')) { require_once __DIR__ . '/master.inc.php'; 
} // Load env if not already loaded
require_once BASE_PATH . '/../Dolibarr/Lib/WebSite.php';
require_once DOL_DOCUMENT_ROOT . '/core/website.inc.php';
ob_start();
header('Cache-Control: max-age=3600, public, must-revalidate');
header('Content-type: application/manifest+json');
// END PHP ?>

<?php // BEGIN PHP
$tmp = ob_get_contents(); ob_end_clean(); dolWebsiteOutput($tmp, "manifest");
// END PHP 
