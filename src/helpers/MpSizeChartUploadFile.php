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
class MpSizeChartUploadFile
{
    protected $file;
    protected $upload_dir;
    protected $file_name;
    protected $file_path;
    protected $file_type;
    protected $file_size;
    protected $file_error;
    protected $content;

    public function __construct($file)
    {
        $this->file = $file;
        $this->upload_dir = _PS_UPLOAD_DIR_ . 'mpsizechart/';
        $this->file_name = $file['name'];
        $this->file_path = $this->upload_dir . $this->file_name;
        $this->file_type = $file['mime'];
        $this->file_size = $file['size'];
        $this->file_error = $file['error'];
        $this->content = file_get_contents($file['tmp_name']);
    }

    public function upload()
    {
        if ($this->file_error) {
            return $this->file_error;
        }
        if (!file_exists($this->upload_dir)) {
            mkdir($this->upload_dir, 0777, true);
        }

        if (move_uploaded_file($this->file['tmp_name'], $this->file_path)) {
            chmod ($this->file_path, 0777);

            return true;
        }

        return false;
    }

    public function getFileName()
    {
        return $this->file_name;
    }

    public function getFileSize()
    {
        return $this->file_size;
    }

    public function getFileType()
    {
        return $this->file_type;
    }

    public function getFilePath()
    {
        return $this->upload_dir;
    }
}
