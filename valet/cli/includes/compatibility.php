<?php

/**
 * Check the system's compatibility with Valet.
 */
$inTestingEnvironment = strpos($_SERVER['SCRIPT_NAME'], 'phpunit') !== false;

if (version_compare(PHP_VERSION, '5.6.0', '<')) {
    echo "Valet requires PHP 5.6 or later.";

    exit(1);
}
