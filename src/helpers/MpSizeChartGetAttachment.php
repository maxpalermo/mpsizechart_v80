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

namespace MpSoft\MpSizeChart\helpers;

require_once _PS_MODULE_DIR_ . 'mpsizechart/src/models/MpSizeChartModelAttachments.php';

class MpSizeChartGetAttachment
{
    public static function getUploadFolder($url = true)
    {
        if ($url) {
            return \Tools::getShopProtocol() . \Tools::getHttpHost() . __PS_BASE_URI__ . 'upload/mpsizechart/';
        }

        return _PS_UPLOAD_DIR_ . 'mpsizechart/';
    }

    public static function getAttachmentUrl($id_product)
    {
        $folder = self::getUploadFolder();
        $model = new \MpSizeChartModelAttachments($id_product);
        $file = $model->getAttachmentUrl($folder);

        return $file;
    }

    public static function getAttachmentUrlFile($file)
    {
        $folder = self::getUploadFolder();

        return $folder . $file;
    }

    public static function getAttachmentList()
    {
        $folder = self::getUploadFolder(false);
        $files = glob($folder . '*.{pdf}', GLOB_BRACE);
        $list = [];
        foreach ($files as $file) {
            $list[] = [
                'name' => basename($file),
                'href' => self::getUploadFolder() . basename($file),
            ];
        }

        return $list;
    }
}
