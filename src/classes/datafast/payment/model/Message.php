<?php


namespace datafast\payment\datafast\payment\model;


class Message
{
    private static $messageCodes = [
        '000.000.000' => 'Transacción exitosa.',
        '000.100.112' => 'Solicitud procesada con éxito.',
        '000.100.110' => 'Solicitud procesada con éxito.',
        '600.200.201' => 'Comercion no configurado para este método de pago.',
        '800.100.171' => 'Transacción rechazada (retener tarjeta).',
        '200.100.101' => 'Tú banco encontró un problema con la tarjeta, por favor comunícate con tu banco para consultar los detalles.',
        '200.100.103' => 'La solicitud contiene errores estructurales.',
        '700.300.700' => 'Reverso declinado.',
        '800.100.100' => 'Transacción  rechazada por su banco, llamar a su banco y consultar.',
        '800.100.174' => 'Monto invalido.',
        '800.100.151' => 'Número de tarjeta invalido.',
        '800.100.402' => 'Titular de cuenta no es válida.',
        '800.100.190' => 'Transacción declinada, tipo de crédito (diferido) no esta autorizado por su banco.',
        '800.100.197' => 'Transacción cancelada por el cliente.',
        '800.100.176' => 'Transacción rechazada, por favor intente nuevamente.',
        '100.400.311' => 'Transacción rechazada (error de formato).',
        '100.100.100' => 'Sin cuenta de crédito.',
        '800.100.165' => 'Tarjeta perdida.',
        '800.100.159' => 'Tarjeta robada.',
        '800.100.155' => 'Fondos insuficientes.',
        '100.150.100' => 'Sin cuenta corriente.',
        '100.150.205' => 'Sin cuenta de ahorro.',
        '100.100.303' => 'Tarjeta expirada.',
        '800.100.170' => 'Transacción no permitida.',
        '100.550.310' => 'El Monto excede el cupo permitido.',
        '800.100.168' => 'Su tarjeta está restringida para realizar está transacción.',
        '800.100.179' => 'Excede el límite de frecuencia de retiro.',
        '500.100.201' => 'Verifique Codigo Establecimiento.',
        '100.100.402' => 'Titular de cuenta bancaria no es válido.',
        '600.200.100' => 'Método de pago no válido.',
        '700.100.200' => 'Verifique el interés.',
        '800.100.157' => 'Transacción rechazada (fecha de vencimiento incorrecta).',
        '800.100.501' => 'Establecimiento cancelado.',
        '100.380.306' => 'Número de autorización no existe.',
        '900.100.201' => 'En este momento su banco no se encuentra en línea.',
        '900.100.300' => 'Tiempo de espera de la transacción superado, por favor comuníquese con su banco.',
        '100.400.147' => 'La transacción infringió una regla antifraude. Estas reglas fueron definidas por el comercio al momento de firmar..',
        '800.100.152' => 'La información proporcionada de su tarjeta es incorrecta',
        '800.100.156' => 'Monto inválido, el monto es inferior al mínimo permitido'
    ];

    private static $clientCodes = array('800.100.156', '800.100.152', '200.100.101', '800.100.100', '800.100.174', '800.100.151', '800.100.402', '800.100.197', '800.100.176', '800.100.155', '800.100.170', '100.100.303', '100.550.310', '800.100.168', '800.100.179', '100.100.402', '900.100.201', '900.100.300', '800.100.157', '600.200.100');

    public static function getClientMessageDescription(string $code): string
    {
        $value = null;
        if (in_array($code, self::$clientCodes)) {
            $value = self::getMessageDescription($code);
        } else {
            $value = self::getMessageDescription('200.100.101');
        }
        return $value;
    }

    public static function getMessageDescription(string $code): string
    {
        $value = null;
        if (array_key_exists($code, self::$messageCodes)) {
            $value = self::$messageCodes[$code];
        } else {
            $value = self::$messageCodes['200.100.101'];
        }
        return $value;
    }

}