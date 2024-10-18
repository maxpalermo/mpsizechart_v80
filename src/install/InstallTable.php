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

namespace MpSoft\MpSizeChart\install;

if (!defined('_PS_VERSION_')) {
    exit;
}

class InstallTable
{
    protected $module;

    public function __construct($module)
    {
        $this->module = $module;
    }

    /**
     * installFromSqlFile
     * Legge il contenuto del file sql e lo esegue
     *
     * @param string $name Il nome del file sql da installare senza estensione
     * @param string $path Il percorso del file sql da installare. Se non specificato, verrà cercato nella cartella sql del modulo
     *
     * @return bool
     */
    public function installFromSqlFile($name, $path = null)
    {
        if (!$name) {
            return false;
        }

        if (!$path) {
            $path = $this->module->getLocalPath() . 'sql/' . $name . '.sql';
        } else {
            $path = rtrim(trim($path), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name . '.sql';
        }

        if (!file_exists($path)) {
            return false;
        }

        $sql = file_get_contents($path);

        if (!$sql) {
            return false;
        }

        $sql = str_replace(['{PREFIX}', '{ENGINE_TYPE}'], [_DB_PREFIX_, _MYSQL_ENGINE_], $sql);

        $sql = preg_split("/;\s*[\r\n]+/", $sql);

        foreach ($sql as $query) {
            if ($query) {
                if (!\Db::getInstance()->execute(trim($query))) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Summary of installFromDefinition
     * Legge l'array $definition e compila la query di creazione della tabella
     *
     * @param array $definition La definizione della tabella dalla classe ObjectModel
     *
     * @return bool
     */
    public function installFromDefinition($definition)
    {
        // TODO: Leggere la definizione di una tabella e compilare la query per la creazione della tabella
        return true;
    }

    /**
     * Summary of dropTable
     * Elimina una tabella dal database se esiste. Se non esiste, restituisce comunque true per non 
     * bloccare l'installazione del modulo
     * 
     * @param string $table la tabella da eliminare
     *
     * @return bool
     */
    public function dropTable($table)
    {
        if (!$table) {
            return true;
        }

        return \Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . pSQL($table) . '`');
    }

    /**
     * Summary of backupTable
     * Crea un file sql con la definizione della tabella e il suo contenuto
     *
     * @param string $table
     *
     * @return array|bool|string|null
     */
    public function backupTable($table)
    {
        if (!$table) {
            return false;
        }

        $sql = 'SHOW CREATE TABLE `' . _DB_PREFIX_ . pSQL($table) . '`';

        try {
            $result = \Db::getInstance()->executeS($sql);
        } catch (\Throwable $th) {
            return $this->module->displayError($th->getMessage());
        }

        if (!$result) {
            return false;
        }

        $result = array_shift($result);

        $sql = $result['Create Table'];

        $sql = preg_replace('/AUTO_INCREMENT=\d+\s+/', '', $sql);
        $sql = preg_replace('/CREATE TABLE/', 'CREATE TABLE IF NOT EXISTS', $sql);

        $dataSql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . pSQL($table) . '`;' . PHP_EOL;
        $dataSql .= $sql . ';' . PHP_EOL;

        $insert = '';
        $rows = \Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . pSQL($table) . '`');
        if ($rows) {
            foreach ($rows as $row) {
                $columns = array_keys($row);
                if (!$insert) {
                    $insert = 'INSERT INTO `' . _DB_PREFIX_ . pSQL($table) . '` (`' . implode('`, `', $columns) . '`) VALUES ' . PHP_EOL;
                    $dataSql .= $insert;
                }
                $values = array_map(function ($value) {
                    return is_null($value) ? 'NULL' : "'" . pSQL($value, true) . "'";
                }, array_values($row));

                $dataSql .= '(' . implode(', ', $values) . ');' . PHP_EOL;
            }

            return [
                'tablename' => $table,
                'structure' => $sql,
                'content' => $dataSql,
            ];
        }
    }
}
