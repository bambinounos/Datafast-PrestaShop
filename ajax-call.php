<?php
include_once __DIR__ . '/src/classes/datafast/payment/model/Environment.php';
use datafast\payment\model\Environment;
require_once __DIR__ . '/../../config/config.inc.php';
require_once __DIR__ . '/../../init.php';

if (!isset($_GET["token"]))
{
      echo "false";
      exit;
}
$token = $_GET["token"];

$sqlCount = 'SELECT COUNT(tkn.id) AS CountToken
			FROM `' . _DB_PREFIX_ . 'datafast_customertoken` tkn
			WHERE tkn.`token` = \'' . pSQL($token) . '\'';
$countToken = Db::getInstance()->executeS($sqlCount);
$countTkn = (is_array($countToken) && isset($countToken[0]['CountToken'])) ? (int)$countToken[0]['CountToken'] : 0;

if ($countTkn > 0)
{
	$responseJson = deleteCardRequest($token);
	$response = json_decode($responseJson, true);
	if(is_array($response) && isset($response['result']['code']) &&
	(
			$response['result']['code'] == "000.000.000" ||
			$response['result']['code'] == "000.200.100" ||
			$response['result']['code'] == "000.100.112" ||
			$response['result']['code'] == "000.100.110"))
	{
		$sqlUpdate = "UPDATE " . _DB_PREFIX_ . "datafast_customertoken
		SET		STATUS 	= 2
		WHERE	STATUS 	= 1
		AND 	TOKEN	= '" . pSQL($token) . "'";
		Db::getInstance()->execute($sqlUpdate);
		echo "true";
	}
	else
	{
		echo "false";
	}
}
else
{
	echo "false";
}
exit;

function deleteCardRequest($token)
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
    if ($ambiente == "1")
	{
        $url .= "&testMode=EXTERNAL";
	}
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $bearer));
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
