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

use MpSoft\MpSizeChart\helpers\MpSizeChartGetAttachment;
use MpSoft\MpSizeChart\helpers\MpSizeChartUploadFile;

class AdminMpSizeChartController extends ModuleAdminController
{
    public $id_lang;
    public $id_shop;
    public $link;
    public $className;
    protected $messages;
    protected $local_path;

    public function __construct()
    {
        $this->translator = Context::getContext()->getTranslator();
        $this->bootstrap = true;
        $this->context = Context::getContext();
        $this->token = Tools::getValue('token', Tools::getAdminTokenLite($this->className));

        $this->lang = true;
        $this->table = 'product';
        $this->identifier = 'id_product';
        $this->className = 'MpSizeChartModelAttachments';

        $this->bulk_actions = [
            'delete_pdf' => [
                'text' => $this->trans('Elimina il PDF allegato dai prodotti selezionati'),
                'confirm' => $this->trans('Confermare la cancellazione dei PDF selezionati?'),
                'icon' => 'icon-trash',
                'href' => $this->context->link->getAdminLink($this->controller_name, true) . '&action=delete',
            ],
        ];

        parent::__construct();

        $this->id_lang = (int) ContextCore::getContext()->language->id;
        $this->id_shop = (int) ContextCore::getContext()->shop->id;
        $this->initList();
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->addJqueryUI('ui.dialog');
        $this->addJqueryUI('ui.progressbar');
        $this->addJqueryUI('ui.draggable');
        $this->addJqueryUI('ui.effect');
        $this->addJqueryUI('ui.effect-slide');
        $this->addJqueryUI('ui.effect-fold');
        $this->addJqueryUI('ui.progressbar');
    }

    public function response($data)
    {
        header('Content-Type: application/json');
        exit(json_encode($data));
    }

    public function initContent()
    {
        $this->getFilters();
        $this->errors = [];
        $this->messages = [];
        $this->content = $this->initFormSearch() . $this->initScript();
        parent::initContent();
    }

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();

        $this->page_header_toolbar_btn['new'] = [
            'href' => 'javascript:$("#desc-product-new").click();',
            'desc' => $this->trans('Aggiungi un allegato'),
            'icon' => 'process-icon-new',
        ];

        $this->page_header_toolbar_btn['remove_orphans'] = [
            'href' => $this->context->link->getAdminLink($this->controller_name, true) . '&action=remove_orphans',
            'desc' => $this->trans('Rimuovi i file orfani'),
            'icon' => 'process-icon-delete text-danger',
        ];

        $this->page_header_toolbar_btn['refresh_data'] = [
            'href' => $this->context->link->getAdminLink($this->controller_name, true) . '&action=refresh_data',
            'desc' => $this->trans('Aggiorna le informazioni della tabella'),
            'icon' => 'process-icon-refresh',
            'confirm' => $this->trans('Sei sicuro di voler aggiornare la tabella? Saranno sovrascritti tutti i dati delle immagini.'),
        ];

        $this->page_header_toolbar_btn['ok'] = [
            'href' => 'javascript:checkAll();',
            'desc' => $this->trans('Seleziona tutto'),
            'icon' => 'process-icon-ok',
        ];
        $this->page_header_toolbar_btn['cancel'] = [
            'href' => 'javascript:uncheckAll();',
            'desc' => $this->trans('Deseleziona tutto'),
            'icon' => 'process-icon-cancel',
        ];
    }

    public function initToolbar()
    {
        parent::initToolbar();
        $this->toolbar_btn['ok'] = [
            'href' => 'javascript:checkAll();',
            'desc' => $this->trans('Seleziona tutto'),
            'icon' => 'process-icon-ok',
        ];
        $this->toolbar_btn['cancel'] = [
            'href' => 'javascript:uncheckAll();',
            'desc' => $this->trans('Deseleziona tutto'),
            'icon' => 'process-icon-cancel',
        ];
    }

    public function processRemoveOrphans()
    {
        $dir = MpSizeChartGetAttachment::getUploadFolder(false);
        $attachments = MpSizeChartGetAttachment::getAttachmentList();
        $total = 0;

        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql->select ('file_name')
            ->from (MpSizeChartModelAttachments::$definition['table'])
            ->where ('file_name IS NOT NULL');
        $files = $db->executeS($sql);
        if ($files) {
            $file_list = array_unique(array_column($files, 'file_name'));
            $file_att = array_unique(array_column($attachments, 'name'));

            asort($file_list);
            asort($file_att);

            if (count($file_list) > count($file_att)) {
                $orphans = array_diff($file_list, $file_att);
            } else {
                $orphans = array_diff($file_att, $file_list);
            }

            foreach ($orphans as $file) {
                $file_path = $dir . $file;
                if (file_exists($file_path)) {
                    unlink($file_path);
                    $total++;
                }
            }
        }

        if ($total) {
            $this->confirmations[] = sprintf($this->trans('Rimossi %d file orfani'), $total);

            return true;
        }

        $this->warnings[] = $this->trans('Nessun file orfano trovato');
    }

    public function processRefreshData()
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql->select('id_product')
            ->from(MpSizeChartModelAttachments::$definition['table']);
        $result = $db->executeS($sql);
        $total = 0;

        if ($result) {
            foreach ($result as $item) {
                $model = new MpSizeChartModelAttachments($item['id_product']);
                $product = new Product($item['id_product'], false, $this->id_lang);
                if (!Validate::isLoadedObject($model)) {
                    continue;
                }
                $filename = $model->file_name;
                $upload_folder = MpSizeChartGetAttachment::getUploadFolder(false);
                $filepath = $upload_folder . $filename;
                if (file_exists($filepath)) {
                    $file_info = new SplFileInfo($filepath);
                    $model->file_size = $file_info->getSize();
                    $model->file_type = mime_content_type($filepath);
                    $model->file_path = rtrim(trim($upload_folder), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

                    try {
                        $model->update();
                        $total++;
                    } catch (\Throwable $th) {
                        $this->errors[] = sprintf(
                            $this->trans('Errore durante l\'aggiornamento del file %s per il prodotto %s: %s'),
                            $filename,
                            $product->reference,
                            $th->getMessage()
                        );
                    }
                } else {
                    $this->warnings[] = sprintf(
                        $this->trans('File %s non trovato per il prodotto %s'),
                        $filename,
                        $product->reference
                    );
                }
            }
        }

        $this->confirmations[] = sprintf($this->trans('Aggiornati %d prodotti'), $total);
    }

    public function processConvertTable()
    {
        $db = Db::getInstance();
        $sql = new DbQuery();
        $sql->select('*')
            ->from('mp_size_chart');

        try {
            $result = $db->executeS($sql);
        } catch (\Throwable $th) {
            $this->errors[] = $th->getMessage();
        }
        $totalSizeMoved = 0;

        $directory = $this->module->getLocalPath() . 'upload/';

        if (!file_exists($directory)) {
            $this->warnings[] = $this->trans('Directory UPLOAD non esistente. Non è possibile effettuare la conversione.');

            return false;
        }

        $totalSizeFolder = 0;
        $totalFiles = 0;

        if (is_dir($directory)) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
            foreach ($files as $file) {
                if ($file->isFile()) {
                    $totalSizeFolder += $file->getSize();
                }
            }
            $totalFiles = iterator_count($files);
        }

        if ($result) {
            foreach ($result as $item) {
                $file_path = $this->module->getLocalPath() . 'upload/' . $item['filename'];

                if (!file_exists($file_path)) {
                    continue;
                }

                $file_info = new SplFileInfo($file_path);
                $mime_type = mime_content_type($file_path);
                $file_size = $file_info->getSize();

                if (!file_exists(_PS_UPLOAD_DIR_ . 'mpsizechart/')) {
                    @mkdir(_PS_UPLOAD_DIR_ . 'mpsizechart/', 0777, true);
                }

                $new_path = _PS_UPLOAD_DIR_ . 'mpsizechart/' . $file_info->getFilename();
                @rename($file_path, $new_path);
                @chmod($new_path, 0777);

                $model = new MpSizeChartModelAttachments($item['id_product']);
                $model->id_product = $item['id_product'];
                $model->file_name = $item['filename'];
                $model->file_path = rtrim(trim(dirname($new_path)), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
                $model->file_type = $mime_type;
                $model->file_size = $file_size;
                if (Validate::isLoadedObject($model)) {
                    $model->update();
                } else {
                    $model->add();
                }
                $totalSizeMoved += $file_size;
            }
        }

        $db->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'mp_size_chart`');
        Tools::deleteDirectory($this->module->getLocalPath() . 'upload/');

        $this->confirmations[] =
            sprintf(
                $this->trans('Convertiti %d file, per un totale di : %s, eliminati %d file, per un totale di : %s'),
                count($result),
                Tools::formatBytes($totalSizeMoved),
                $totalFiles - count($result),
                Tools::formatBytes($totalSizeFolder)
            );
    }

    public function initList()
    {
        $join_table = _DB_PREFIX_ . MpSizeChartModelAttachments::$definition['table'];
        $this->_join .= " LEFT JOIN `{$join_table}` m ON (m.id_product = a.id_product)";
        $this->_select = 'm.file_name, m.file_size, m.file_type, a.id_product as image';

        $this->fields_list = [
            'image' => [
                'title' => $this->trans('Immagine'),
                'align' => 'center',
                'orderby' => false,
                'search' => false,
                'callback' => 'displayImage',
                'type' => 'bool',
                'float' => true,
            ],
            'id_product' => [
                'title' => $this->trans('Id'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'name' => [
                'title' => $this->trans('Nome'),
                'filter_key' => 'pl!name',
            ],
            'reference' => [
                'title' => $this->trans('Riferimento'),
                'filter_key' => 'a!reference',
            ],
            'price' => [
                'title' => $this->trans('Prezzo'),
                'type' => 'price',
                'currency' => true,
                'align' => 'right',
                'filter_key' => 'a!price',
                'class' => 'fixed-width-sm text-right',
            ],
            'active' => [
                'title' => $this->trans('Attivo'),
                'active' => 'status',
                'type' => 'bool',
                'align' => 'center',
                'class' => 'fixed-width-sm',
            ],
            'file_name' => [
                'title' => $this->trans('File'),
                'orderby' => true,
                'search' => true,
                'float' => true,
                'class' => 'text-center',
                'filter_key' => 'm!file_name',
                'callback' => 'displayPdf',
                'remove_onclick' => true,
            ],
            'file_size' => [
                'title' => $this->trans('Peso'),
                'orderby' => false,
                'search' => false,
                'class' => 'fixed-width-md text-right',
                'callback' => 'formatBytes',
            ],
            'file_type' => [
                'title' => $this->trans('Tipo'),
                'orderby' => false,
                'search' => false,
                'class' => 'fixed-width-md text-center',
            ],
        ];

        $this->actions = ['add', 'edit', 'delete'];
    }

    public function formatBytes($value)
    {
        if (!$value) {
            return '--';
        }

        return Tools::formatBytes((int) $value);
    }

    public function displayPdf($value, $row)
    {
        if ($value) {
            $filepath = _PS_UPLOAD_DIR_ . 'mpsizechart/' . $value;
            if (!file_exists($filepath)) {
                $tpl = $this->context->smarty->createTemplate(
                    'module:mpsizechart/views/templates/admin/pdf_no_link.tpl',
                    $this->context->smarty
                );
                $tpl->assign(
                    [
                        'value' => $value,
                    ]
                );

                return $tpl->fetch();
            }
        }

        $id_product = (int) $row['id_product'];
        $tpl = $this->context->smarty->createTemplate(
            'module:mpsizechart/views/templates/admin/pdf_link.tpl',
            $this->context->smarty
        );
        $tpl->assign(
            [
                'url' => $this->context->link->getModuleLink('mpsizechart', 'AjaxDispatch', ['id_product' => $id_product, 'action' => 'getPdf']),
                'value' => $value,
            ]
        );

        return $tpl->fetch();
    }

    protected function initFormSearch()
    {
        $categories = Category::getCategories($this->id_lang, true, false);
        $selected_categories = Tools::getValue('categories', []);
        $tree = new HelperTreeCategories('categories-tree');
        $tree->setUseCheckBox(true)
            ->setUseSearch(true)
            ->setSelectedCategories($selected_categories);

        $this->fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Pannello di ricerca'),
                    'icon' => 'icon-search',
                ],
                'input' => [
                    [
                        'type' => 'categories',
                        'label' => $this->trans('Seleziona le categorie'),
                        'name' => 'categories',
                        'tree' => [
                            'id' => 'categories-tree',
                            'selected_categories' => $selected_categories,
                            'use_search' => true,
                            'use_checkbox' => true,
                            'input_name' => 'categories[]',
                        ],
                        'required' => false,
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('Seleziona i Produttori'),
                        'name' => 'manufacturers',
                        'class' => 'chosen',
                        'options' => [
                            'query' => array_merge([['id_manufacturer' => 0, 'name' => '--']], Manufacturer::getManufacturers()),
                            'id' => 'id_manufacturer',
                            'name' => 'name',
                        ],
                        'multiple' => true,
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('Seleziona i Fornitori'),
                        'name' => 'suppliers',
                        'class' => 'chosen',
                        'options' => [
                            'query' => array_merge([['id_supplier' => 0, 'name' => '--']], Supplier::getSuppliers()),
                            'id' => 'id_supplier',
                            'name' => 'name',
                        ],
                        'multiple' => true,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Nome Prodotto'),
                        'name' => 'product_name',
                    ],
                    [
                        'type' => 'html',
                        'label' => $this->trans('cerca in'),
                        'name' => 'chk_search_in',
                        'html_content' => $this->getButtonsSearchIn(),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Solo i prodotti con allegati'),
                        'name' => 'switch_only_attachments',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'switch_only_attachments_yes',
                                'value' => 1,
                                'label' => $this->trans('SI'),
                            ],
                            [
                                'id' => 'switch_only_attachments_no',
                                'value' => 0,
                                'label' => $this->trans('NO'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Solo i prodotti senza allegati'),
                        'name' => 'switch_no_attachments',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'switch_no_attachments_yes',
                                'value' => 1,
                                'label' => $this->trans('SI'),
                            ],
                            [
                                'id' => 'switch_no_attachments_no',
                                'value' => 0,
                                'label' => $this->trans('NO'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'hidden',
                        'name' => 'action',
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Cerca'),
                    'class' => 'btn btn-default pull-right',
                    'icon' => 'process-icon-search',
                ],
                'buttons' => [
                    [
                        'title' => $this->trans('Reset'),
                        'class' => 'btn btn-default pull-left',
                        'name' => 'submitSearch',
                        'icon' => 'process-icon-cancel',
                        'href' => $this->context->link->getAdminLink($this->controller_name, true, [], ['action' => 'resetSearchFilters']),
                    ],
                ],
            ],
        ];

        $allow_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');

        $helper = new HelperForm();
        $helper->module = $this->module;
        $helper->name_controller = $this->className;
        $helper->token = Tools::getAdminTokenLite($this->controller_name);
        $helper->currentIndex = $this->context->link->getAdminLink($this->controller_name);
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = $allow_form_lang ? $allow_form_lang : 0;
        $helper->title = $this->module->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit' . $this->className;
        $helper->fields_value = $this->getConfigFieldsValuesSearch();

        return $helper->generateForm([$this->fields_form]);
    }

    protected function getButtonsSearchIn()
    {
        $tpl = $this->context->smarty->createTemplate(
            $this->module->getLocalPath() . 'views/templates/admin/search_in.tpl',
            $this->context->smarty
        );

        $tpl->assign(
            [
                'chk_search_in' => json_decode(Tools::getValue('chk_search_in', Configuration::get('MPSIZECHART_CHK_SEARCH_IN'))),
            ]
        );

        return $tpl->fetch();
    }

    protected function getConfigFieldsValuesSearch()
    {
        $fields = [];
        $fields['categories'] = Tools::getValue('categories', json_decode(Configuration::get('MPSIZECHART_CATEGORIES'), true));
        $fields['manufacturers[]'] = Tools::getValue('manufacturers', json_decode(Configuration::get('MPSIZECHART_MANUFACTURERS'), true));
        $fields['suppliers[]'] = Tools::getValue('suppliers', json_decode(Configuration::get('MPSIZECHART_SUPPLIERS'), true));
        $fields['product_name'] = Tools::getValue('product_name', Configuration::get('MPSIZECHART_PRODUCT_NAME'));
        $fields['chk_search_in'] = Tools::getValue('chk_search_in', json_decode(Configuration::get('MPSIZECHART_CHK_SEARCH_IN'), true));
        $fields['switch_only_attachments'] = (int) Tools::getValue('switch_only_attachments', (int) Configuration::get('MPSIZECHART_SWITCH_ONLY_ATTACHMENTS'));
        $fields['switch_no_attachments'] = (int) Tools::getValue('switch_no_attachments', (int) Configuration::get('MPSIZECHART_SWITCH_NO_ATTACHMENTS'));

        $fields['action'] = 'submitSearch';

        return $fields;
    }

    private function initScript()
    {
        $path = $this->module->getLocalPath() . 'views/templates/admin/script.tpl';
        $tpl = $this->context->smarty->createTemplate($path, $this->context->smarty);
        $tpl->assign(
            [
                'ajax_url' => $this->context->link->getAdminLink($this->controller_name),
                // $this->context->link->getModuleLink($this->module->name, 'ajaxDispatcher'),
                'token' => Tools::getAdminTokenLite($this->className),
                'attachments' => MpSizeChartGetAttachment::getAttachmentList(),
            ]
        );

        return $tpl->fetch();
    }

    public function getCategories()
    {
        $db = Db::getInstance();
        $sql = new DbQueryCore();
        $output = [];
        $sql->select('id_category as id')
                ->select('name')
                ->from('category_lang')
                ->where('id_shop = ' . (int) $this->id_shop)
                ->where('id_lang = ' . (int) $this->id_lang)
                ->orderBy('name');
        $result = $db->executeS($sql);
        if (!$result) {
            return [];
        }
        $selected = explode(',', Tools::getValue('input_select_categories', ''));
        foreach ($result as $row) {
            $is_selected = in_array($row['id'], $selected);
            $output[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'selected' => $is_selected,
            ];
        }

        return $output;
    }

    public function getManufacturers()
    {
        $db = Db::getInstance();
        $sql = new DbQueryCore();
        $output = [];
        $sql->select('id_manufacturer as id')
                ->select('name')
                ->from('manufacturer')
                ->orderBy('name');
        $result = $db->executeS($sql);
        if (!$result) {
            return [];
        }
        $selected = explode(',', Tools::getValue('input_select_manufacturers', ''));
        foreach ($result as $row) {
            $is_selected = in_array($row['id'], $selected);
            $output[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'selected' => $is_selected,
            ];
        }

        return $output;
    }

    public function getSuppliers()
    {
        $db = Db::getInstance();
        $sql = new DbQueryCore();
        $output = [];
        $sql->select('id_supplier as id')
                ->select('name')
                ->from('supplier')
                ->orderBy('name');
        $result = $db->executeS($sql);
        if (!$result) {
            return [];
        }
        $selected = explode(',', Tools::getValue('input_select_manufacturers', ''));
        foreach ($result as $row) {
            $is_selected = in_array($row['id'], $selected);
            $output[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'selected' => $is_selected,
            ];
        }

        return $output;
    }

    /**
     * ===========================================================================
     * ========================== AJAX PROCESS ===================================
     * ===========================================================================
     */
    public function ajaxProcessDeleteAttachment()
    {
        $id_product = (int) Tools::getValue('id_product', 0);
        $model = new MpSizeChartModelAttachments($id_product);
        if (!Validate::isLoadedObject($model)) {
            $this->response(['result' => false, 'message' => $this->trans('Allegato non trovato')]);
        }
        $result = $model->delete();
        $this->response(['result' => $result]);
    }

    public function ajaxProcessSaveAttachment()
    {
        $ids = json_decode(Tools::getValue('ids', ''), true);
        $file = Tools::fileAttachment('fileUpload', false);
        $existing_attachment = Tools::getValue('existingAttachment', false);

        if ($file) {
            $uploadClass = new MpSizeChartUploadFile($file);
            $result = $uploadClass->upload();
            if ( $result === true) {
                $fileName = $uploadClass->getFileName();
                $fileType = $uploadClass->getFileType();
                $fileSize = $uploadClass->getFileSize();
                $filePath = $uploadClass->getFilePath();
                foreach ($ids as $id) {
                    $model = new MpSizeChartModelAttachments($id);
                    $model->file_name = $fileName;
                    $model->file_type = $fileType;
                    $model->file_size = $fileSize;
                    $model->file_path = $filePath;

                    try {
                        if (Validate::isLoadedObject($model)) {
                            $model->update();
                        } else {
                            $model->force_id = true;
                            $model->id = $id;
                            $model->add();
                        }
                    } catch (\Throwable $th) {
                        unlink($filePath . $fileName);
                        $this->response(
                            [
                                'result' => false,
                                'message' => $th->getMessage(),
                            ]
                        );
                    }
                }
                $this->response(
                    [
                        'result' => true,
                        'message' => $this->trans('File caricato correttamente'),
                        'file' => [
                            'name' => $fileName,
                            'size' => $fileSize,
                            'type' => $fileType,
                        ],
                        // 'list' => MpSizeChartGetAttachment::getAttachmentList(),
                    ]
                );
            } else {
                $this->response(
                    [
                        'result' => false,
                        'message' => sprintf(
                            $this->trans('Errore durante il caricamento del file: %s'),
                            $result
                        ),
                    ]
                );
            }
        }

        if ($existing_attachment) {
            $filename = basename($existing_attachment);
            foreach ($ids as $id) {
                $model = new MpSizeChartModelAttachments($id);
                $file = _PS_UPLOAD_DIR_ . 'mpsizechart/' . $filename;
                $file_info = new SplFileInfo($file);
                $existing_attachment = [
                    'name' => $file_info->getFilename(),
                    'type' => mime_content_type($file),
                    'size' => $file_info->getSize(),
                    'path' => rtrim(trim(dirname($file)), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR,
                ];

                $model->file_name = $existing_attachment['name'];
                $model->file_type = $existing_attachment['type'];
                $model->file_size = $existing_attachment['size'];
                $model->file_path = $existing_attachment['path'];

                try {
                    if (Validate::isLoadedObject($model)) {
                        $model->update();
                    } else {
                        $model->force_id = true;
                        $model->id = $id;
                        $model->add();
                    }
                } catch (\Throwable $th) {
                    $this->response(
                        [
                            'result' => false,
                            'message' => $th->getMessage(),
                        ]
                    );
                }
            }
            $this->response(
                [
                    'result' => true,
                    'message' => $this->trans('File caricato correttamente'),
                ]
            );
        }

        $this->response(
            [
                'result' => false,
                'message' => $this->trans('Nessun file da caricare.'),
            ]
        );
    }

    public function displayImage($value)
    {
        $id_lang = (int) $this->context->language->id;
        $id_product = (int) $value;
        $id_image = Product::getCover($id_product);
        if (!$id_image) {
            return '';
        }
        $id_image = (int) $id_image['id_image'];
        $image = new Image($id_image, $id_lang);
        if (!Validate::isLoadedObject($image)) {
            return '';
        }
        $folders = Image::getImgFolderStatic($id_image);
        $ssl = (int) Configuration::get('PS_SSL_ENABLED');
        if ($ssl) {
            $base = Tools::getShopDomainSsl(true);
        } else {
            $base = Tools::getShopDomain(true);
        }

        $base .= $this->context->shop->getBaseURI();

        $filename = _PS_IMG_DIR_ . 'p/' . $folders . $id_image . '-small_default';

        if (!file_exists($filename . '.jpg')) {
            $filename .= '.png';
        } else {
            $filename .= '.jpg';
        }

        $url = $base . 'img/p/' . $folders . basename($filename);
        $html = "<img src=\"{$url}\" class=\"thumbnal\" style=\"max-width: 100px; object-fit: contain;\">";

        return $html;
    }

    function getFilters()
    {
        $categories = json_decode(Configuration::get('MPSIZECHART_CATEGORIES'), true);
        $manufacturers = json_decode(Configuration::get('MPSIZECHART_MANUFACTURERS'), true);
        $suppliers = json_decode(Configuration::get('MPSIZECHART_SUPPLIERS'), true);
        $product_name = Configuration::get('MPSIZECHART_PRODUCT_NAME');
        $chk_search_in = json_decode(Configuration::get('MPSIZECHART_CHK_SEARCH_IN'), true);
        $only_attachments = (int) Configuration::get('MPSIZECHART_SWITCH_ONLY_ATTACHMENTS');
        $no_attachments = (int) Configuration::get('MPSIZECHART_SWITCH_NO_ATTACHMENTS');

        $this->filterProducts($categories, $manufacturers, $suppliers, $product_name, $chk_search_in, $only_attachments, $no_attachments);
    }

    function processSubmitSearch()
    {
        $categories = Tools::getValue('categories', []);
        $manufacturers = Tools::getValue('manufacturers', []);
        $suppliers = Tools::getValue('suppliers', []);
        $product_name = Tools::getValue('product_name', '');
        $chk_search_in = Tools::getValue('search_in', []);
        $only_attachments = (int) Tools::getValue('switch_only_attachments', 0);
        $no_attachments = (int) Tools::getValue('switch_no_attachments', 0);

        Configuration::updateValue('MPSIZECHART_CATEGORIES', json_encode($categories));
        Configuration::updateValue('MPSIZECHART_MANUFACTURERS', json_encode($manufacturers));
        Configuration::updateValue('MPSIZECHART_SUPPLIERS', json_encode($suppliers));
        Configuration::updateValue('MPSIZECHART_PRODUCT_NAME', $product_name);
        Configuration::updateValue('MPSIZECHART_CHK_SEARCH_IN', json_encode($chk_search_in));
        Configuration::updateValue('MPSIZECHART_SWITCH_ONLY_ATTACHMENTS', (int) $only_attachments);
        Configuration::updateValue('MPSIZECHART_SWITCH_NO_ATTACHMENTS', (int) $no_attachments);
    }

    public function processResetSearchFilters()
    {
        Configuration::updateValue('MPSIZECHART_CATEGORIES', json_encode([]));
        Configuration::updateValue('MPSIZECHART_MANUFACTURERS', json_encode([]));
        Configuration::updateValue('MPSIZECHART_SUPPLIERS', json_encode([]));
        Configuration::updateValue('MPSIZECHART_PRODUCT_NAME', '');
        Configuration::updateValue('MPSIZECHART_CHK_SEARCH_IN', json_encode([]));
        Configuration::updateValue('MPSIZECHART_SWITCH_ONLY_ATTACHMENTS', 0);
        Configuration::updateValue('MPSIZECHART_SWITCH_NO_ATTACHMENTS', 0);

        $this->confirmations[] = $this->trans('Filtri di ricerca resettati');
    }

    public function filterProducts($categories, $manufacturers, $suppliers, $product_name, $chk_search_in, $only_attachments, $no_attachments)
    {
        if ($categories) {
            $cat_list = implode(',', array_map('intval', $categories));
            $join_table = _DB_PREFIX_ . 'category_product';
            $this->_join .= " LEFT JOIN {$join_table} cp ON (cp.id_product = a.id_product)";
            $this->_where .= " AND (a.id_category_default IN ({$cat_list}) OR cp.id_category IN ({$cat_list}))";
        }
        if ($manufacturers) {
            $this->_where .= ' AND a.id_manufacturer IN (' . implode(',', array_map('intval', $manufacturers)) . ')';
        }
        if ($suppliers) {
            $this->_where .= ' AND a.id_supplier IN (' . implode(',', array_map('intval', $suppliers)) . ')';
        }
        if ($product_name) {
            if (in_array('name', $chk_search_in)) {
                $this->_where .= ' AND b.name LIKE \'%' . pSQL($product_name) . '%\'';
            }
            if (in_array('reference', $chk_search_in)) {
                $this->_where .= ' AND a.reference LIKE \'%' . pSQL($product_name) . '%\'';
            }
            if (in_array('link_rewrite', $chk_search_in)) {
                $this->_where .= ' AND b.link_rewrite LIKE \'%' . pSQL($product_name) . '%\'';
            }
            if (in_array('meta_title', $chk_search_in)) {
                $this->_where .= ' AND b.meta_title LIKE \'%' . pSQL($product_name) . '%\'';
            }
            if (in_array('meta_description', $chk_search_in)) {
                $this->_where .= ' AND b.meta_description LIKE \'%' . pSQL($product_name) . '%\'';
            }
            if (in_array('meta_keywords', $chk_search_in)) {
                $this->_where .= ' AND b.meta_keywords LIKE \'%' . pSQL($product_name) . '%\'';
            }
        }
        if ($only_attachments && !$no_attachments) {
            $this->_where .= ' AND m.id_product IS NOT NULL';
        } elseif (!$only_attachments && $no_attachments) {
            $this->_where .= ' AND m.id_product IS NULL';
        }
    }

    public function processBulkDeletePDF()
    {
        $ids = $this->boxes;
        $messages = [];
        if (!$ids) {
            return;
        }
        foreach ($ids as $id) {
            $model = new MpSizeChartModelAttachments($id);
            $product = new Product($id, false, $this->id_lang);

            if (!Validate::isLoadedObject($model)) {
                $this->warnings[] = sprintf(
                    $this->trans('Allegato non trovato per il prodotto %s'),
                    $product->reference
                );

                continue;
            }

            $attachment = $model->file_name;

            try {
                $model->delete();
                $messages[] = sprintf(
                    $this->trans('Allegato %s rimosso per il prodotto %s'),
                    $attachment,
                    $product->reference
                );
            } catch (\Throwable $th) {
                $this->errors[] = $th->getMessage();
            }
        }

        if ($messages) {
            $this->confirmations[] = implode('<br>', $messages);
        }
    }
}
