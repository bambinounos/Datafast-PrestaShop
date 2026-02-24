<?php


namespace datafast\payment\datafast\payment;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use datafast\payment\datafast\payment\model\Message;
use datafast\payment\datafast\payment\model\PaymentResponse;


class datafastDB
{

    
    private function getLogger(): Logger
    {
        $logger = new Logger('datafastDB');
        $logger->pushHandler(new StreamHandler(Constants::LOGGER_FILE, Logger::DEBUG));
        return $logger;
    }


}
