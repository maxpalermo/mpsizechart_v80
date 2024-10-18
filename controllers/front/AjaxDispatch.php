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

use MpSoft\MpSizeChart\helpers\LoadClass;

class MpSizeChartAjaxDispatchModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        (new LoadClass($this->module))->load('MpSizeChartModelAttachments', 'src/models');

        $this->ajax = true;

        $action = Tools::getValue('action');

        if (method_exists($this, $action)) {
            $this->{$action}();
        } else {
            die('Invalid action');
        }
    }

    protected function response($params)
    {
        header('Content-Type: application/json; charset=utf-8');
        exit(json_encode($params));
    }

    public function getPdf()
    {
        $id_product = (int) Tools::getValue('id_product');
        $model = new MpSizeChartModelAttachments($id_product);
        if (!Validate::isLoadedObject($model)) {
            $this->response(['error' => 'Model not found']);
        }

        $filename = $model->file_name;
        $path = _PS_UPLOAD_DIR_ . 'mpsizechart/' . $filename;
        $content = Tools::file_get_contents($path);

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . Tools::strlen($content));
        header('Content-Transfer-Encoding: binary');
        header('Accept-Ranges: bytes');
        echo $content;
        exit;
    }
}
