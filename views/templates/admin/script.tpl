{**
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
 *}
<style>
    .chosen-container.chosen-container-single {
        width: 100% !important;
    }
</style>
<!-- Modal -->
<div class="modal fade" id="attachmentModal" tabindex="-1" role="dialog" aria-labelledby="attachmentModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="attachmentModalLabel">Aggiungi Allegato</h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="attachmentForm">
                    <input id="attachment-id_product" type="hidden" name="attachment-id_product" value="">
                    <div class="form-group">
                        <label for="existingAttachments">Seleziona Allegato Esistente</label>
                        <select class="form-control chosen" id="existingAttachments">
                            {foreach $attachments as $attachment}
                                <option value="{$attachment.href}">{$attachment.name}</option>
                            {/foreach}
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="newAttachment">Oppure Carica Nuovo Allegato</label>
                        <input type="file" class="form-control form-control-file" id="newAttachment" accept=".pdf">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Chiudi</button>
                <button type="button" class="btn btn-primary" id="saveAttachment">Salva Allegato</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    function getIndexBySearchElement(element_name) {
        let columnIndex = -1;
        $('.table.product thead tr').each(function() {
            let th = $(this).find('th');
            $(th).each(function() {
                let cell = $(this)
                let input = $(cell).find('[name="' + element_name + '"]');
                if (input.length > 0) {
                    columnIndex = $(cell).index();
                    return columnIndex; // Break the loop
                }
            });
        });
        if (columnIndex >= 0) {
            return columnIndex;
        }
        console.error('Column with input[name="' + element_name + '"] not found.');
        return false;
    }

    function getIndexByCellTitle(title) {
        let columnIndex = -1;
        $('.table.product thead tr').each(function() {
            console.log("fetch " + $(this).index() + " row");
            let th = $(this).find('th');
            $(th).each(function() {
                let content = $(this).text().trim();
                if (content === title) {
                    columnIndex = $(this).index();
                    return columnIndex; // Break the loop
                }
            });
        });
        if (columnIndex >= 0) {
            return columnIndex;
        }
        console.error('Column with title ["' + title + '"] not found.');
        return false;
    }

    function getCellValue(element, columnIndex) {
        return $(element).closest('tr').find('td').eq(columnIndex).text().trim();
    }

    function checkAll() {
        var checkboxes = document.getElementsByName('productBox[]');
        for (var checkbox of checkboxes) {
            checkbox.checked = true;
        }
    }

    function uncheckAll() {
        var checkboxes = document.getElementsByName('productBox[]');
        for (var checkbox of checkboxes) {
            checkbox.checked = false;
        }
    }

    $(document).ready(function() {
        $(".edit.btn.btn-default").on("click", function(e) {
            e.preventDefault();
            let urlParams = new URLSearchParams(this.href.split('?')[1]);
            let id_product = urlParams.get('id_product');
            let idx = getIndexBySearchElement('productFilter_pl!name');
            let product_name = getCellValue(this, idx + 1);
            idx = getIndexByCellTitle('File');
            let filename = getCellValue(this, idx);

            $("#attachment-id_product").val(id_product);
            $("#attachmentModalLabel").text("Aggiungi Allegato a " + product_name);
            $.each($("#existingAttachments option"), function() {
                if ($(this).text() === filename) {
                    $(this).prop('selected', true);
                    $(".chosen").trigger("chosen:updated");
                }
            });
            $("#attachmentModal").modal("show");
            return false;
        });

        $("a.delete").on("click", function() {
            let urlParams = new URLSearchParams(this.href.split('?')[1]);
            let id_product = urlParams.get('id_product');
            let idx = getIndexBySearchElement('productFilter_pl!name');
            let product_name = getCellValue(this, idx + 1);
            let idx2 = getIndexByCellTitle('File');
            let filename = getCellValue(this, idx2 + 1);

            $.ajax({
                url: '{$ajax_url}',
                method: 'POST',
                data: {
                    id_product: id_product,
                    filename: filename,
                    ajax: true,
                    action: 'deleteAttachment'
                },
                success: function(response) {
                    if (response.error) {
                        jAlert(response.error);
                        return;
                    }
                    jAlert('Allegato eliminato con successo!');
                    location.reload();
                },
                error: function() {
                    jAlert('Errore durante l\'eliminazione dell\'allegato.');
                }
            });

            return false;
        });

        $("#desc-product-new").on("click", function(e) {
            e.preventDefault();

            let checked = $("input[name='productBox[]']:checked");
            if (checked.length === 0) {
                jAlert("Seleziona almeno un prodotto.");
                return false;
            }
            console.log("CHECKED", checked);
            let id_products = [];
            let products = [];
            $.each(checked, function() {
                id_products.push($(this).val());

                let idx = getIndexBySearchElement('productFilter_pl!name');
                let product_name = getCellValue(this, idx + 1);
                products.push(product_name);
            });

            console.log("ID_PRODUCTS", id_products, "PRODUCTS", products);

            $("#attachment-id_product").val(JSON.stringify(id_products));
            $("#attachmentModalLabel").text("Aggiungi Allegato a " + products.join(", "));
            $("#attachmentModal").modal("show");
            return false;
        });

        $("#saveAttachment").on("click", function(e) {
            e.preventDefault();
            if (!confirm("Sei sicuro di voler salvare l'allegato?")) {
                return false;
            }
            let id_products = $("input[name='productBox[]']:checked");
            let ids = [];
            $.each(id_products, function() {
                ids.push($(this).val());
            });
            let selectedAttachment = $("#existingAttachments").val();
            let newAttachment = $("#newAttachment")[0].files[0];
            let formData = new FormData();
            formData.append("ajax", true);
            formData.append("action", "saveAttachment");
            formData.append("ids", JSON.stringify(ids));

            if (newAttachment) {
                formData.append("fileUpload", newAttachment);
            } else if (selectedAttachment) {
                formData.append("existingAttachment", selectedAttachment);
            }

            $.ajax({
                url: '{$ajax_url}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.error) {
                        jAlert(response.error);
                        return;
                    }
                    jAlert('Allegato salvato con successo. Attendere che la pagina si ricarichi.');
                    $("#attachmentModal").modal("hide");
                    window.location.reload();
                },
                error: function() {
                    jAlert('Errore durante il salvataggio dell\'allegato.');
                }
            });

            return false;
        });

        $("#page-header-desc-product-refresh_data").on("click", function(e) {
            if (!confirm("Sei sicuro di voler aggiornare i dati?")) {
                e.preventDefault();
                return false;
            }
        });

        $("#page-header-desc-product-remove_orphans").on("click", function(e) {
            if (!confirm("Sei sicuro di voler eliminare gli allegati orfani?")) {
                e.preventDefault();
                return false;
            }
        });
    });
</script>