<?php
use MpSoft\MpSizeChart\helpers\LoadClass;

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
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/vendor/autoload.php';

use MpSoft\MpSizeChart\helpers\MpSizeChartGetAttachment;
use MpSoft\MpSizeChart\install\InstallMenu;
use MpSoft\MpSizeChart\install\InstallTable;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class MpSizeChart extends Module implements WidgetInterface
{
    protected $config_form = false;
    protected $adminClassName = 'AdminMpSizeChart';

    protected $upload_dir;

    public $id_lang;
    public $id_shop;
    public $tablename;

    public function __construct()
    {
        $this->name = 'mpsizechart';
        $this->tab = 'front-office-features';
        $this->version = '2.1.0';
        $this->author = 'Massimiliano Palermo';
        $this->need_instance = 0;
        $this->module_key = '';
        $this->bootstrap = true;

        parent::__construct();

        (new LoadClass($this))->load('MpSizeChartModelAttachments', 'src/models');

        $this->displayName = $this->l('MP Tabella delle Taglie');
        $this->description = $this->l('Con questo modulo puoi visualizzare le informazioni sulle taglie tramite PDF.');
        $this->confirmUninstall = $this->l('Disinstallare il modulo?');
        $this->ps_versions_compliancy = ['min' => '8.0', 'max' => _PS_VERSION_];
        $this->upload_dir = Tools::getShopProtocol() . Tools::getHttpHost() . __PS_BASE_URI__ . 'upload/mpsizechart/';
        $this->id_lang = (int) Context::getContext()->language->id;
        $this->id_shop = (int) Context::getContext()->shop->id;
    }

    public function renderWidget($hookName, array $configuration)
    {
        if (!$this->isCached('module:mpsizechart/views/templates/front/displayButton.tpl', $this->getCacheId('mpsizechart'))) {
            $this->smarty->assign($this->getWidgetVariables($hookName, $configuration));
        }

        if ($hookName === 'displayProductPriceBlock') {
            return $this->fetch('module:mpsizechart/views/templates/front/displayButton.tpl');
        }

        if (!isset($configuration['product'])) {
            return;
        }

        $id_product = (int) $configuration['product']['id_product'];
        $filename_url = MpSizeChartGetAttachment::getAttachmentUrl($id_product);
        $filename = basename($filename_url);

        $file_location = _PS_UPLOAD_DIR_ . 'mpsizechart/' . $filename;
        if (!file_exists($file_location)) {
            return;
        }

        $this->smarty->assign([
            'id_product' => $id_product,
            'url' => $filename_url,
        ]);

        return $this->fetch('module:mpsizechart/views/templates/front/displayButton.tpl');
    }

    public function getWidgetVariables($hookName, array $configuration)
    {
        if (!isset($configuration['product'])) {
            return [];
        }

        if ($hookName === 'displayProductPriceBlock') {
            return [];
        }

        $id_product = (int) $configuration['product']['id_product'];
        $filename_url = MpSizeChartGetAttachment::getAttachmentUrl($id_product);
        $filename = basename($filename_url);

        $file_location = _PS_UPLOAD_DIR_ . 'mpsizechart/' . $filename;
        if (!file_exists($file_location)) {
            return [];
        }

        return [
            'id_product' => $id_product,
            'url' => $filename_url,
        ];
    }

    public function install()
    {
        $installMenu = new InstallMenu($this);
        $installTable = new InstallTable($this);

        $hooks = [
            'displayAfterDescriptionShort',
            'actionAdminControllerSetMedia',
            'actionFrontControllerSetMedia',
            'displayLeftColumnProduct',
            'displayProductPriceBlock',
            'displayRightColumnProduct',
        ];
        $res = parent::install() && $this->registerHook($hooks);

        $menu = $installMenu->installMenu(
            $this->adminClassName,
            'MP Tabella delle Taglie',
            'AdminCatalog',
            'straighten',
        );
        $table = $installTable->installFromSqlFile('product_size_chart_attachments');

        if ($res && $menu && $table) {
            $upload_dir = _PS_UPLOAD_DIR_ . 'mpsizechart';
            if (!file_exists($upload_dir)) {
                @mkdir($upload_dir, 0777, true);
            }
        }

        return $res && $menu && $table;
    }

    public function uninstall()
    {
        $installMenu = new InstallMenu($this);

        return parent::uninstall()
            && $installMenu->uninstallMenu($this->adminClassName);
    }

    public function hookActionAdminControllerSetMedia()
    {
        // $this->context->controller->addCSS($this->getLocalPath() . 'views/css/icon-menu.css');
        // $this->context->controller->registerStylesheet('mpsizechart-icon-menu', 'module:mpsizechart/views/css/icon-menu.css');
    }

    public function hookActionFrontControllerSetMedia()
    {
        $this->context->controller->registerJavascript('mpsizechart-button', 'module:mpsizechart/views/js/button-script.js');
    }

    public function hookDisplayProductPriceBlock($params)
    {
        if (isset($params['type']) && $params['type'] === 'after_price') {
            return $this->hookDispatch((int) $params['product']->id);
        }
    }

    public function hookDisplayRightColumnProduct($params)
    {
        return $this->hookDispatch($params);
    }

    public function hookDisplayLeftColumnProduct($params)
    {
        return $this->hookDispatch($params);
    }

    public function hookDisplayAfterDescriptionShort($params)
    {
        return $this->hookDispatch($params);
    }

    public function hookDispatch($id_product)
    {
        $filename_url = MpSizeChartGetAttachment::getAttachmentUrl($id_product);
        $filename = basename($filename_url);

        $file_location = _PS_UPLOAD_DIR_ . 'mpsizechart/' . $filename;
        if (!file_exists($file_location)) {
            return;
        }

        $tpl_file = $this->getLocalPath() . '/views/templates/front/displayButton.tpl';
        $tpl = $this->context->smarty->createTemplate($tpl_file);
        $tpl->assign(
            [
                'id_product' => $id_product,
                'url' => $filename_url,
                'pdf' => $this->context->link->getModuleLink('mpsizechart', 'AjaxDispatch', ['id_product' => $id_product, 'action' => 'getPdf']),
                'class' => 'mpsizechart-button btn-warning text-white',
            ]
        );

        return $tpl->fetch();
    }
}
