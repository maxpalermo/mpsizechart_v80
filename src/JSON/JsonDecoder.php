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

namespace MpSoft\MpSizeChart\JSON;

class JsonDecoder
{
    private $error = null;
    private $decodedData = null;
    private $associative = true;
    private $depth = 512;
    private $flags = JSON_BIGINT_AS_STRING;

    /**
     * Costruttore della classe
     * 
     * @param bool $associative Se true, restituisce array associativi invece di oggetti
     * @param int $depth Profondità massima di decodifica
     * @param int $flags Bitmask di opzioni JSON
     */
    public function __construct(bool $associative = true, int $depth = 512, int $flags = JSON_BIGINT_AS_STRING)
    {
        $this->associative = $associative;
        $this->depth = $depth;
        $this->flags = $flags;
    }

    /**
     * Decodifica una stringa JSON
     * 
     * @param string $jsonString La stringa JSON da decodificare
     *
     * @return mixed|false I dati decodificati o false in caso di errore
     */
    public function decode(string $jsonString)
    {
        // Reset dello stato precedente
        $this->error = null;
        $this->decodedData = null;

        // Controllo se la stringa è vuota
        if (empty($jsonString)) {
            $this->error = 'La stringa JSON è vuota';

            return false;
        }

        // Decodifica con gestione errori
        if (PHP_VERSION_ID >= 70300) {
            // PHP 7.3+ con JSON_THROW_ON_ERROR
            try {
                $this->decodedData = json_decode(
                    $jsonString,
                    $this->associative,
                    $this->depth,
                    $this->flags | JSON_THROW_ON_ERROR
                );
            } catch (\JsonException $e) {
                $this->error = $e->getMessage();

                return false;
            }
        } else {
            // PHP < 7.3
            $this->decodedData = json_decode($jsonString, $this->associative, $this->depth, $this->flags);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error = json_last_error_msg();

                return false;
            }
        }

        return $this->decodedData;
    }

    /**
     * Restituisce l'ultimo errore verificatosi
     * 
     * @return string|null Il messaggio di errore o null se nessun errore
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Restituisce i dati decodificati (anche dopo un fallimento)
     * 
     * @return mixed|null I dati decodificati o null se non presenti
     */
    public function getDecodedData()
    {
        return $this->decodedData;
    }

    /**
     * Decodifica una stringa JSON
     * 
     * @param string $jsonString La stringa JSON da decodificare
     * @param mixed $default Valore di default in caso di errore
     * 
     * @return mixed|false I dati decodificati o false in caso di errore
     */
    public static function decodeJson(string $jsonString, $default = null)
    {
        $class = new self();
        $class->decode($jsonString);

        if (!$class->getError()) {
            return $class->getDecodedData();
        }

        if ($default !== null) {
            return $default;
        }

        return false;
    }

    /**
     * Verifica se una stringa è un JSON valido (metodo statico)
     * 
     * @param mixed $jsonString La stringa da verificare
     *
     * @return bool True se è un JSON valido, false altrimenti
     */
    public static function isJson($jsonString): bool
    {
        if (is_array($jsonString)) {
            return false;
        }

        if ($jsonString === null || trim($jsonString) === '') {
            return false;
        }

        return PHP_VERSION_ID >= 80300 ? json_validate($jsonString)
            : (PHP_VERSION_ID >= 70300 ? self::validateWithException($jsonString)
            : self::validateWithLastError($jsonString));
    }

    private static function validateWithException(string $json): bool
    {
        try {
            json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            return true;
        } catch (\JsonException) {
            return false;
        }
    }

    private static function validateWithLastError(string $json): bool
    {
        json_decode($json);

        return (json_last_error() === JSON_ERROR_NONE);
    }
}
