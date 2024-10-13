<?php
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

// The absolute minimum version on which to install SuiteCRM
<<<<<<< HEAD:php_version.php
define('SUITECRM_PHP_MIN_VERSION', '7.4.0');

// The minimum recommended version on which to install SuiteCRM
define('SUITECRM_PHP_REC_VERSION', '7.4.0');
=======
if (!defined('SUITECRM_PHP_MIN_VERSION')){
    define('SUITECRM_PHP_MIN_VERSION', '8.1.0');
}

// The minimum recommended version on which to install SuiteCRM
if (!defined('SUITECRM_PHP_REC_VERSION')){
    define('SUITECRM_PHP_REC_VERSION', '8.2.0');
}
>>>>>>> 8cba9e2418 (feat: update data source configuration):public/legacy/php_version.php
