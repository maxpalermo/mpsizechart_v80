<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    Massimiliano Palermo <maxx.palermo@gmail.com>
 * @copyright Since 2016 Massimiliano Palermo
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
function addIndex($base, $folder, &$index)
{
    $current = $base . $folder . '/';

    if (file_exists($current . 'index.php')) {
        unlink($current . 'index.php');
    }
    copy($index, $current . 'index.php');
    echo "Inserito index.php in $current\n";

    $dirs = glob($current . '*');
    foreach ($dirs as $dir) {
        $dir = basename($dir);
        if (is_dir($current . $dir)) {
            addIndex($current, $dir, $index);
        }
    }
}

$folders = glob('*');
$base = dirname(__FILE__) . '/';
$index = $base . 'index.php';
foreach ($folders as $folder) {
    if (is_dir($folder)) {
        addIndex($base, $folder, $index);
    }
}
echo 'END';
