<?php

// PHPStan icin calisma zamani sabitleri. Yalnizca statik analizde kullanilir.
if (PHP_SAPI !== 'cli') {
    exit;
}

if (!defined('BVLOG_ROOT')) {
    define('BVLOG_ROOT', dirname(__DIR__));
}
