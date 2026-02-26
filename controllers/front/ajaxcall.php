<?php

use datafast\payment\model\Environment;

if (!defined('_PS_VERSION_')) {
    exit;
}

class datafastAjaxcallModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        header('Content-Type: application/json');

        if (!Tools::getValue('token')) {
            echo 'false';
            exit;
        }

        $token = Tools::getValue('token');

        $sqlCount = 'SELECT COUNT(tkn.id) AS CountToken
            FROM `' . _DB_PREFIX_ . 'datafast_customertoken` tkn
            WHERE tkn.`token` = \'' . pSQL($token) . '\'';
        $countToken = Db::getInstance()->executeS($sqlCount);
        $countTkn = (is_array($countToken) && isset($countToken[0]['CountToken'])) ? (int) $countToken[0]['CountToken'] : 0;

        if ($countTkn > 0) {
            $responseJson = $this->deleteCardRequest($token);
            $response = json_decode($responseJson, true);
            if (is_array($response) && isset($response['result']['code'])
                && in_array($response['result']['code'], [
                    '000.000.000',
                    '000.200.100',
                    '000.100.112',
                    '000.100.110',
                ])) {
                $sqlUpdate = "UPDATE " . _DB_PREFIX_ . "datafast_customertoken
                    SET STATUS = 2
                    WHERE STATUS = 1
                    AND TOKEN = '" . pSQL($token) . "'";
                Db::getInstance()->execute($sqlUpdate);
                echo 'true';
            } else {
                echo 'false';
            }
        } else {
            echo 'false';
        }
        exit;
    }

    private function deleteCardRequest(string $token): string
    {
        $ambiente = Configuration::get('DATAFAST_DEV', null);
        $prodUrl = Configuration::get('DATAFAST_PRODULR', null);
        $devUrl = Configuration::get('DATAFAST_DEVURL', null);
        $entity = Configuration::get('DATAFAST_ENTITY_ID', null);
        $bearer = Configuration::get('DATAFAST_BEARER_TOKEN', null);

        $route = "registrations/" . pSQL($token);
        $url = '';
        if ($ambiente == "1") {
            if (($devUrl ?? '') == '2') {
                $url = Environment::TEST2 . $route;
            } else {
                $url = Environment::TEST . $route;
            }
            $verifyPeer = false;
        } else {
            if (($prodUrl ?? '') == '2') {
                $url = Environment::PRODUCTION2 . $route;
            } else {
                $url = Environment::PRODUCTION . $route;
            }
            $verifyPeer = true;
        }
        $url .= "?entityId=" . urlencode($entity);
        if ($ambiente == "1") {
            $url .= "&testMode=EXTERNAL";
        }
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $bearer]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifyPeer);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $responseData = curl_exec($ch);
        if (curl_errno($ch) || $responseData === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return json_encode(['error' => $error]);
        }
        curl_close($ch);
        return $responseData;
    }
}
