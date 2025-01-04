<?php
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Loaded Configuration File: " . php_ini_loaded_file() . "\n";
echo "Additional .ini files parsed: " . php_ini_scanned_files() . "\n";
echo "\nExtension directory: " . get_cfg_var('extension_dir') . "\n";

echo "\nLoaded Extensions:\n";
$extensions = get_loaded_extensions();
foreach ($extensions as $ext) {
    echo "- $ext\n";
}

echo "\nPDO Drivers:\n";
print_r(PDO::getAvailableDrivers());
?>
