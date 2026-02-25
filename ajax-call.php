<?php
include_once __DIR__ . '/src/classes/datafast/payment/model/Environment.php';
use datafast\payment\model\Environment;
require_once(dirname(__FILE__).'/../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../init.php');

if (!isset($_GET["token"]))
{
      return false;
}
$token = pSQL($_GET["token"]);

$sqlCount = 'SELECT COUNT(tkn.id)    AS CountToken
			FROM `' . _DB_PREFIX_ . 'datafast_customertoken` tkn
			WHERE tkn.`token` = \'' .$token . '\'';
			$countToken = Db::getInstance()->executeS($sqlCount);
				$countTkn = $countToken[0]['CountToken'];
if ($countTkn > 0)
{
	
	$responseJson = deleteCardRequest($token);  
	$response =  json_decode($responseJson, true);  
	if((isset($response['result']['code'])&&
	(
			$response['result']['code'] == "000.000.000" ||
			$response['result']['code'] == "000.200.100" ||
			$response['result']['code'] == "000.100.112" ||
			$response['result']['code'] == "000.100.110"))
	)
	{ 
		$sqlUpdate = "UPDATE  " . _DB_PREFIX_ . "datafast_customertoken
		SET		STATUS 	= 2
		WHERE	STATUS 	= 1
		AND 	TOKEN	= '". pSQL($token) . "'";
		$exUpdate = Db::getInstance()->executeS($sqlUpdate);   
		echo "true";
	}
	else
	{
		echo "false";
	}
}

	 
function deleteCardRequest($token)
  {  
	$ambiente = Configuration::get('DATAFAST_DEV', null);
	$prodUrl = Configuration::get('DATAFAST_PRODULR', null);
	$devUrl = Configuration::get('DATAFAST_DEVURL', null);
	$entity = Configuration::get('DATAFAST_ENTITY_ID', null);
	$bearer = Configuration::get('DATAFAST_BEARER_TOKEN', null);
  
    $route = "registrations/$token";
    if ($ambiente == "1") {
		if($devUrl=='1')	
      		$url = Environment::TEST.$route; 
		if($devUrl=='2')	
      		$url = Environment::TEST2.$route; 
      $verifyPeer = false;
    } else {
		if($prodUrl=='1')	
			$url = Environment::PRODUCTION.$route; 
		if($prodUrl=='2')	
			$url = Environment::PRODUCTION2.$route; 
		$verifyPeer = true;
    }
    $url .= "?entityId=".$entity;
    if($ambiente == "1")
	{
        $url .= "&testMode=EXTERNAL"; 
	}
    $ch = curl_init();
	 
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$bearer));
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');  
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifyPeer);  
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $responseData = curl_exec($ch); 
    if (curl_errno($ch)) {
      return curl_error($ch);
    }
    curl_close($ch);
    return  $responseData;   
  }  