<?php
include_once __DIR__ . '/../src/classes/datafast/payment/model/Environment.php';
use datafast\payment\model\Environment;
$pro = $_GET["pro"];
if (!isset($pro)) 
{
      return false;
}   
echo testConection($pro);
function testConection($data)
{ 
    if (!isset($data)) {
        return false;
    }
    $pro = $data=='0';
    $route = "checkouts";
    if ($pro) {
      $url = Environment::TEST.$route;  
    } else if ($data=='1') {
        $url = Environment::PRODUCTION.$route;  
    } else if ($data=='2'){
        $url = Environment::PRODUCTION2.$route;  
    }else if ($data=='3'){
        $url = Environment::TEST2.$route;  
    }  
    $data = "entityId=8a829418533cf31d01533d06f2ee06fa" .
        "&amount=1.00" .
        "&currency=USD" .
        "&paymentType=DB";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization:Bearer OGE4Mjk0MTg1MzNjZjMxZDAxNTMzZDA2ZmQwNDA3NDh8WHQ3RjIyUUVOWA=='
    ));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, !$pro);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $responseData = curl_exec($ch);
    if (curl_errno($ch)) {
        return json_encode(['error'=>curl_error($ch)],true);
    }
    curl_close($ch);
    return $responseData;
}