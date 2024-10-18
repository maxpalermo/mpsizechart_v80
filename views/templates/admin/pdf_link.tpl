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

{if $value == ''}
    <div class="alert alert-warning">
        {l s='Nessun pdf caricato'}
    </div>
{else}
    <a class="btn btn-info" href="{$url}" target="_blank" style="width: 100%; color: var(--edition-white); font-weight: 400;">
        <div class="material-icons mr-2 float-left" style="float:left; margin-left: 4px;">picture_as_pdf</div>
        <div style="float:left; margin-left: 8px;">{$value}</div>
    </a>
{/if}