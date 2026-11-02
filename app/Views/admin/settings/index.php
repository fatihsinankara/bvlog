<?php
$sectionFile = __DIR__ . '/' . ($activeTab ?? 'general') . '.php';
if (is_file($sectionFile)) {
    require $sectionFile;
} else {
    require __DIR__ . '/general.php';
}
