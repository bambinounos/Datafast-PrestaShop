<?php


namespace datafast\payment\datafast\payment\model;


class CardBrands
{
    private static $brands = [
        'MASTER' => 'Mastercard',
        'VISA' => 'Visa',
        'AMEX' => 'American Express',
        'DINERS' => 'Diners Club',
        'DISCOVER' => 'Discover'];


    public static function getBrand(string $code): ?string
    {
        $value = null;
        if (array_key_exists($code, self::$brands)) {
            $value = self::$brands[$code];
        }

        return $value;
    }
}