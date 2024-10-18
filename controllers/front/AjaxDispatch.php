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
class MpSizeChartAjaxDispatchModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

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

    public function getChart()
    {
        $id_product = Tools::getValue('id_product');
        $id_product_attribute = Tools::getValue('id_product_attribute');

        $chart = new MpSizeChart();
        $chart->id_product = $id_product;
        $chart->id_product_attribute = $id_product_attribute;

        $this->context->smarty->assign([
            'chart' => $chart->getChart(),
        ]);

        $this->setTemplate('module:mpsizechart/views/templates/front/ajax_chart.tpl');
    }
}
