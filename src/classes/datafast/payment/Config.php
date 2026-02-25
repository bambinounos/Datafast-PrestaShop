<?php
namespace datafast\payment\datafast\payment;
use datafast\payment\model\DatafastRequest;
use datafast\payment\model\Environment;

class Config
{

    public function getDatafastRequest($data): DatafastRequest
    {
        $request = new DatafastRequest();

	    if ($data['DATAFAST_DEV']) {
            if(($data['DATAFAST_DEVURL'] ?? '')=='1')
                $url = Environment::TEST;
            if(($data['DATAFAST_DEVURL'] ?? '')=='2')
                $url = Environment::TEST2;
            $request->setTestMode(true);
        } else {
            if(($data['DATAFAST_PRODULR'] ?? '')=='1')
                $url = Environment::PRODUCTION;
            if(($data['DATAFAST_PRODULR'] ?? '')=='2')
                $url = Environment::PRODUCTION2;
            $request->setTestMode(false);
        }

        $url = $url ?? '';
        $request->setUrlRequest($url);
		$request->setBearerToken((string)($data['DATAFAST_BEARER_TOKEN'] ?? ''));

        $request->setEntityId((string)($data['DATAFAST_ENTITY_ID'] ?? ''));
        $request->setMid((string)($data['DATAFAST_MID'] ?? ''));
        $request->setTid((string)($data['DATAFAST_TID'] ?? ''));
		$request->setRisk((string)($data['DATAFAST_RISK'] ?? ''));
        $request->setProveedor((string)($data['DATAFAST_PROVEEDOR'] ?? ''));
		$request->setEci((string)($data['DATAFAST_ECI'] ?? ''));
		$request->setPrefijoTrx((string)($data['DATAFAST_PREFIJOTRX'] ?? ''));

        $request->setCheckoutScript($url . 'paymentWidgets.js?checkoutId=');
        $request->setResourcePathUri($url . 'checkouts/{id}/payment');
        $request->setResourcePathUriRefund($url . 'payments');
        return $request;
    }

}
