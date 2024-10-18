{*
* 2007-2016 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author Massimiliano Palermo <info@mpsoft.it>
*  @copyright  2007-2018 Digital Solutions®
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
<style>
    #prop_image
    {
        width: 64px;
        height: 64px;
        object-fit: contain;
    }
    .ui-progressbar 
    {
        position: relative;
    }
    .progress-label 
    {
        position: absolute;
        left: 50%;
        top: 4px;
        font-weight: bold;
        text-shadow: 1px 1px 0 #fff;
    }
    #progressbar 
    {
        display: none;
    }
    .bootstrap .table thead>tr:nth-child(1)>th
    {
        font-weight: bold;
    }
</style>
<div id='errors'></div>
<div id="progressbar"><div class="progress-label">Loading...</div></div>
<div class="panel">
    <div class="panel-heading">
        <i class="icon-list-alt"></i>
        {l s='Product list, found' mod='mpsizechart'} {$total_products|escape:'htmlall':'UTF-8'} {l s='products' mod='mpsizechart'}
    </div>
    <div class='panel-body'>
        <div class="form-group">
            <label class="control-label col-lg-1 text-left">
                {l s='Upload file' mod='mpsizechart'}
            </label>
            <div class="col-sm-6">
                <input id="input_upload_file" type="file" name="input_upload_file" class="hide">
                <div class="dummyfile input-group">
                    <span class="input-group-addon"><i class="icon-file"></i></span>
                    <input id="input_upload_filename" type="text" name="input_upload_filename" readonly="readonly">
                    <span class="input-group-btn">
                        <button id="btn-upload" type="button" name="btn-upload" class="btn btn-default">
                            <i class="icon-folder-open"></i>{l s='Add files' mod='mpsizechart'}</button>
                    </span>
                </div>
                <p class="help-block">
                    {l s='Choose the file you want to show in page product' mod='mpchartsize'}
                </p>
            </div>
        </div>
        <br style="clear: both;">
        <br>
        <label class="control-label col-lg-1 text-left">
                
        </label>
        <a href="#" class="btn btn-default" id="btn-upload-file"'>
            <i class="icon-upload-alt" style="color: #0a5;"></i>
            {l s='Upload File' mod='mpsizechart'}
        </a>
        &nbsp;
        <a href="#" class="btn btn-default" id="btn-delete-file">
            <i class="icon-trash-o" style="color: #dd514c;"></i>
            {l s='Delete File' mod='mpsizechart'}
        </a>
    </div>
    <div class='panel-body' id='panel-list-products' style="overflow-x: auto; max-width: 300%;">
        <table class='table' id='table-list-products' style="width: auto; max-width: 300%;">
            <thead>
                <tr>
                    <th style='width: 16px;'><input type='checkbox' id='checkSelectAll'></th>
                    <th style='width: 24px;'></th>
                    <th style='width: 64px;'>{l s='id' mod='mpsizechart'}</th>
                    <th style='width: 150px;'>{l s='Reference' mod='mpsizechart'}</th>
                    <th style='width: 600px;'>{l s='Name' mod='mpsizechart'}</th>
                    <th style='width: 10em;'></th>
                    <th style='width: 2em;'></th>
                </tr>
            </thead>
            <tbody>
                {foreach $products as $product}
                    <tr>
                        <td><input type='checkbox' name='checkSelect[]' id_product='{$product.id_product|escape:'htmlall':'UTF-8'}'></td>
                        <td style='text-align: center;'><img src='{$product.image_url|escape:'htmlall':'UTF-8'}' id='prop_image'></td>
                        <td {if $product.active==0}style="color: #dd514c;"{/if}>{$product.id_product|escape:'htmlall':'UTF-8'}</td>
                        <td>{$product.reference|escape:'htmlall':'UTF-8'}</td>
                        <td>{$product.name|escape:'htmlall':'UTF-8'}</td>
                        <td>
                            <a href="#" class="btn btn-default" onclick='updateProduct(event, this);'>
                                <i class="icon-upload" style="color: #0a5;"></i>
                            </a>
                            &nbsp;
                            <a href="#" class="btn btn-default" onclick='deleteProductPrice(event, this);'>
                                <i class="icon-trash-o" style="color: #dd514c;"></i>
                            </a>
                        </td>
                        <td></td>
                    </tr>
                {/foreach}
            </tbody>
            <tfoot>
                <th colspan='6'></th>
            </tfoot>
        </table>
    </div>
    <div class='panel-footer'>
        
    </div>
</div>
        
<script type="text/javascript">
    $(document).ready(function(){
        $('#checkSelectAll').on('change', function(){
            var checked = this.checked;
            $('input[name="checkSelect[]"').each(function(){
                this.checked = checked;
            });
        });
        $('#btn-upload').on('click', function(){
            $('#input_upload_file').click();
        });
        $('#input_upload_filename').on('click', function(){
            $('#input_upload_file').click();
        });
        $('#input_upload_file').on('change', function(){
            var filename = $('#input_upload_file').val();
            if (filename.substring(3,11) === 'fakepath') {
                filename = filename.substring(12);
            } // Remove c:\fake at beginning from localhost chrome
            $('#input_upload_filename').val(filename);    
        });
        /**
         * UPLOAD FILE
         */
        $('#btn-upload-file').on('click', function(event){
            event.preventDefault();
            if ($('#input_upload_filename').val()==='') {
                jAlert('{l s='Please select a file to upload.' mod='mpsizechart'}');
                return false;
            }
            jConfirm('{l s='Upload selected file?' mod='mpsizechart'}', '{l s='Upload file' mod='mpsizechart'}', function(answer){
                if (!answer) {
                    return false;
                }
                var checked = $('input[name="checkSelect[]"]:checked');
                console.log('checked: ' + $(checked).length);
                if($(checked).length > 0) {
                    ajaxJQueryProcessUploadFile();
                } else {
                    jAlert('{l s='Please select at least one product from the list.' mod='mpsizechart'}');
                }
            });
        });
        /**
         * DELETE FILE
         */
        $('#btn-delete-file').on('click', function(event){
            event.preventDefault();
            jConfirm('{l s='Delete selected file?' mod='mpsizechart'}', '{l s='Upload file' mod='mpsizechart'}', function(answer){
                if (!answer) {
                    return false;
                }
                var checked = $('input[name="checkSelect[]"]:checked');
                console.log('checked: ' + $(checked).length);
                if($(checked).length > 0) {
                    ajaxJQueryProcessDeleteFile();
                } else {
                    jAlert('{l s='Please select at least one product from the list.' mod='mpsizechart'}');
                }
            });
        });
    });
    
    var listProducts = [];
    var progress_total = 0;
    var progress_current = 0;
    var errors = [];
    var pbar = $("#progressbar");
    var current_row;
    
    function ajaxJQueryProcessUploadFile()
    {
        var input_file = $('#input_upload_file');
        var obj_file;
        if ($(input_file).prop('files').length > 0) {
            var form_data = new FormData();
            obj_file = $(input_file).prop('files')[0];
            
            form_data.append('input_upload_file', obj_file);
            form_data.append('module_name', 'mpsizechart');
            form_data.append('class_name', 'MpSizeChart');
            form_data.append('ajax', true);
            form_data.append('action', 'UploadFile');
        } else {
            return false;
        }
        
        $.ajax({
            type: 'POST',
            data: form_data,
            processData: false,
            contentType: false,
            success: function(response) {
                if (Boolean(response) === true) {
                    ajaxJQueryProcessInsertProductFile();
                } else {
                    console.log('Error response: ' + response);
                    jAlert('{l s='Error uploading file.' mod='mpsizechart'}');
                }
            }
        });
    }
    
    function ajaxJQueryProcessDeleteFile()
    {
        listProducts=[];
        var rows = $('#table-list-products tbody tr');
        $(rows).each(function(){
            var check = $(this).find('input[type="checkbox"]').attr('checked');
            if (check === 'checked') {
                var button = $(this).find('a');
                listProducts.push(getParameters(button));
            }
        });
        initProgressBar();
        ajaxCall('deleteProductFile');
    }
    
    function ajaxJQueryProcessInsertProductFile()
    {
        listProducts=[];
        var rows = $('#table-list-products tbody tr');
        $(rows).each(function(){
            var check = $(this).find('input[type="checkbox"]').attr('checked');
            if (check === 'checked') {
                var button = $(this).find('a');
                listProducts.push(getParameters(button));
            }
        });
        initProgressBar();
        ajaxCall();
    }
    
    function initProgressBar()
    {
        console.log('initProgress');
        progress_total = listProducts.length;
        progress_current = 0;
        errors = [];
        progressLabel = $(".progress-label");
        
        $(pbar).show();
        
        $(pbar).progressbar(
        {
            value: 0,
            change: function() {
                progressLabel.text($(pbar).progressbar( "value" ) + "%" );
            },
            complete: function() {
                progressLabel.text("{l s='Operation done.' mod='mpsizechart'}");
                $(pbar).delay('2000').fadeOut();
            }
        });
    }
    
    function progress() 
    {
        progress_current++;
        if (listProducts.length>0) {
            perc = parseInt(100 * parseInt(progress_current) / parseInt(progress_total));
        } else {
            perc = 100;
        }
        $(pbar).progressbar("value", perc);
        if (progress_current==progress_total) {
            return false;
        } else {
            return true;
        }
    }
    
    function createProductList(event)
    {
        event.preventDefault();
        listProducts=[];
        var rows = $('#table-list-products tbody tr');
        $(rows).each(function(){
            var check = $(this).find('input[type="checkbox"]').attr('checked');
            if (check === 'checked') {
                var button = $(this).find('a');
                listProducts.push(getParameters(button));
            }
        });
        initProgressBar();
        ajaxCall();
    }
    
    function updateProduct(event, button)
    {
        event.preventDefault();
        if (!confirm('{l s='Update selected product?' mod='mpsizechart'}')) {
            return false;
        }
        listProducts = [];
        listProducts.push(getParameters(button));
        initProgressBar();
        ajaxCall();
    }
    
    function getParameters(button)
    {
        var row = $(button).closest('tr');
        var id_product = parseInt(row.find('td:nth-child(3)').text());
        var filename = $('#input_upload_filename').val();
        
        var parameters = [
            id_product,
            filename,
            row
        ];
        
        return parameters;
    }
    
    function ajaxCall(action = '')
    {
        if (action === '') {
            action = 'insertProductFile';
        }
        progress();
        if (listProducts.length===0) {
            if (errors.length) {
                $('#errors').html(errors.join('<br>'));
                jAlert("{l s='Operation done.' mod='mpsizechart'}");
            }
            return true;
        }
        var parameters = listProducts.shift();
        if (action==='insertProductFile') {
            ajaxCallInsertProductFile(parameters, action);
        } else if (action==='deleteProductFile') {
            ajaxCallDeleteProductFile(parameters, action);
        }
    }
    
    function ajaxCallInsertProductFile(parameters, action)
    {
        $.ajax({
                type: 'POST',
                dataType: 'json',
                cache: false,
                data:
                {
                    ajax: true,
                    action: 'insertProductFile',
                    id_product: parameters[0],
                    filename: parameters[1]
                }
            })
            .done(function(json){
                var row = parameters[2];
                if (json.result===false) {
                    errors.push(json.msg_error);
                    $(row).find('td:eq(6)').html('<i class="icon-times-circle" style="color: red;">');                
                } else {
                    $(row).find('td:eq(6)').html('<i class="icon-ok-sign" style="color: green;">');
                }
                ajaxCall(action); 
            })
            .fail(function(response){
                jAlert("{l s='Error during getting values.' mod='mpsizechart'}", '{l s='FAIL' mod='mpsizechart'}');
                console.log(response);
            });
    }
    
    function ajaxCallDeleteProductFile(parameters, action)
    {
        $.ajax({
                type: 'POST',
                dataType: 'json',
                cache: false,
                data:
                {
                    ajax: true,
                    action: 'deleteProductFile',
                    id_product: parameters[0],
                    filename: parameters[1]
                }
            })
            .done(function(json){
                var row = parameters[2];
                if (json.result===false) {
                    errors.push(json.msg_error);
                    $(row).find('td:eq(6)').html('<i class="icon-times-circle" style="color: red;">');                
                } else {
                    $(row).find('td:eq(6)').html('<i class="icon-ok-sign" style="color: green;">');
                }
                ajaxCall(action); 
            })
            .fail(function(response){
                jAlert("{l s='Error during getting values.' mod='mpsizechart'}", '{l s='FAIL' mod='mpsizechart'}');
                console.log(response);
            });
    }
</script>
