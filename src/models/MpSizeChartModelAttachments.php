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
class MpSizeChartModelAttachments extends ObjectModel
{
    public $file_name;
    public $file_path;
    public $file_type;
    public $file_size;
    public $date_add;
    public $date_upd;
    public static $definition = [
        'table' => 'product_size_chart_attachment',
        'primary' => 'id_product',
        'fields' => [
            'file_name' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 64],
            'file_path' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 255],
            'file_type' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 32],
            'file_size' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    public static function getAttachments()
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'product_size_chart_attachment';

        return Db::getInstance()->executeS($sql);
    }

    public static function getAttachmentsFileNames()
    {
        $list = self::getAttachments();
        $file_names = [];

        foreach ($list as $item) {
            $file_names[] = $item['file_name'];
        }

        return $file_names;
    }

    public static function getAttachment($id_product)
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'product_size_chart_attachment WHERE id_product = ' . (int) $id_product;

        return Db::getInstance()->executeS($sql);
    }

    public static function deleteAttachment($id_product)
    {
        $sql = 'DELETE FROM ' . _DB_PREFIX_ . 'product_size_chart_attachment WHERE id_product = ' . (int) $id_product;

        return Db::getInstance()->execute($sql);
    }

    public static function addAttachment($id_product, $file_name, $file_path, $file_type, $file_size)
    {
        $sql = 'INSERT REPLACE INTO ' . _DB_PREFIX_ . 'product_size_chart_attachment (id_product, file_name, file_path, file_type, file_size, date_add, date_upd) VALUES (' . (int) $id_product . ', "' . pSQL($file_name) . '", "' . pSQL($file_path) . '", "' . pSQL($file_type) . '", ' . (int) $file_size . ', NOW(), NOW())';

        return Db::getInstance()->execute($sql);
    }

    public static function updateAttachment($id_product, $file_name, $file_path, $file_type, $file_size)
    {
        $sql = 'UPDATE ' . _DB_PREFIX_ . 'product_size_chart_attachment SET file_name = "' . pSQL($file_name) . '", file_path = "' . pSQL($file_path) . '", file_type = "' . pSQL($file_type) . '", file_size = ' . (int) $file_size . ', date_upd = NOW() WHERE id_product = ' . (int) $id_product;

        return Db::getInstance()->execute($sql);
    }

    public static function getAttachmentsByFileName($file_name)
    {
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'product_size_chart_attachment WHERE file_name = "' . pSQL($file_name) . '"';

        return Db::getInstance()->executeS($sql);
    }

    public function getAttachmentUrl($base_uri = false)
    {
        if (!$base_uri) {
            return $this->file_path . $this->file_name;
        }

        return $base_uri . $this->file_name;
    }
}