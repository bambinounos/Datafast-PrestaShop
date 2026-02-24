<?php


namespace datafast\payment\model;


class Constants
{
    const LOGGER_FOLDER = _PS_ROOT_DIR_ . '/datafastLogs/';
    const LOGGER_FILE = Constants::LOGGER_FOLDER . 'datafast.log';
    const TRANSACTION_APPROVED_TEST = array('000.100.112', '000.100.110');
    const TRANSACTION_APPROVED_PROD = '000.000.000';
}