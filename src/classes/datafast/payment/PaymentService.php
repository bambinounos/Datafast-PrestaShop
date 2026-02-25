<?php

namespace datafast\payment;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use datafast\payment\model\Constants;
use datafast\payment\model\CustomerInfo;
use datafast\payment\model\DatafastRequest;
use datafast\payment\model\DatafastRequestRefund;
use datafast\payment\datafast\payment\model\CustomParamsBuilder;
use datafast\payment\datafast\payment\model\Payment;
use datafast\payment\datafast\payment\model\PaymentResponse;
use datafast\payment\datafast\payment\Config;

class PaymentService
{


    public function requestCheckoutId(Payment $payment): string
    {
        $checkoutId = "";

        try {

            $datafastRequest = $payment->getRequest();

            $checkOutUri = $datafastRequest->getUrlRequest() . "checkouts";

            $authbearer = $this->getAuth($datafastRequest);
            $initBody = $this->buildInitialBody($payment);
            $itemsBody = $this->addItemsToBody($payment);
            $registrations = $this->getRegistrations($payment);

            $ambiente = $datafastRequest->isTestMode();
            if ($ambiente == "1") {
                $verifyPeer = false;
                $riskBody = $this->getRiskParams($payment);
                $body = array_merge($initBody, $itemsBody, $riskBody,$registrations);
              } else {
                $verifyPeer = true;
                $body = array_merge($initBody, $itemsBody,$registrations);
              }

            $body = http_build_query($body);
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $checkOutUri);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array($authbearer));
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,$verifyPeer	);// this should be set to true in production
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$response = curl_exec($ch);

			if(curl_errno($ch))
			{
				return curl_error($ch);
			}
			curl_close($ch);

            $this->safeLog('debug', "Response Body", (array)$response);

			$objRequest =  json_decode($response, true);
			$resultCode = $objRequest["result"]["code"];

            if ($resultCode == "000.200.100" || $resultCode == "000.000.000" ) {
                $checkoutId = $objRequest["id"];
            }

        } catch (\Exception $e) {
            $this->safeLog('error', "Error trying to get checkoutId.", $e->getTrace());
        }
        return $checkoutId;
    }



    private function getAuth(DatafastRequest $datafastRequest): string
    {
        return "Authorization: Bearer " . $datafastRequest->getBearerToken();
    }

    /**
     * @param Payment $payment
     * @return array
     */
    protected function buildInitialBody(Payment $payment): array
    {

        $amount = $payment->getAmount();
        $customer = $payment->getCustomerInfo();

        $datafastRequest = $payment->getRequest();

        return [
                'entityId' => $datafastRequest->getEntityId(),
                'amount' => $this->toDecimalNumber($amount->getTotal()),
                'currency' => DatafastRequest::CURRENCY,
                'paymentType' => DatafastRequest::PAYMENT_TYPE,
                'customer.givenName' => $customer->getGivenName(),
                'customer.middleName' => $customer->getMiddleName(),
                'customer.surname' => $customer->getSurname(),
                'customer.ip' => $_SERVER['REMOTE_ADDR'],
                'customer.merchantCustomerId' => $customer->getMerchantCustomerId(),
                'merchantTransactionId' => $datafastRequest->getPrefijoTrx() . date('YmdHisv'),
                'customer.email' => $customer->getEmail(),
                'customer.identificationDocType' => "IDCARD",
                'customer.identificationDocId' => $customer->getIdentificationDocId(),
                'customer.phone' => $customer->getPhoneNumber(),

                'billing.street1' => $customer->getShippingAddress(),
                'billing.country' => $customer->getBillingCountry(),
                'billing.postcode' => $customer->getBillingPostcode(),
                'shipping.street1' => $customer->getBillingAddress(),
                'shipping.country' => $customer->getCountry(),
                'risk.parameters[USER_DATA2]' => $datafastRequest->getRisk(),

                'customParameters[SHOPPER_MID]'=> $datafastRequest->getMid(),
                'customParameters[SHOPPER_TID]'=> $datafastRequest->getTid(),
                'customParameters[SHOPPER_ECI]'=>$datafastRequest->getEci(),
                'customParameters[SHOPPER_PSERV]'=>$datafastRequest->getProveedor(),

                'customParameters[SHOPPER_VAL_BASE0]'=> $amount->getSubtotalIVA0(),
                'customParameters[SHOPPER_VAL_BASEIMP]'=> $amount->getSubtotalIVA(),
                'customParameters[SHOPPER_VAL_IVA]'=> $amount->getIva(),
                'customParameters[SHOPPER_VERSIONDF]'=> '2'
        ];
    }

     /**
     * @param Payment $payment
     * @return array
     */
    protected function buildInitialBodyRefund(DatafastRequest $request): array
    {
        return [
            'entityId' => $request->getEntityId(),
            'paymentType' => DatafastRequest::PAYMENT_TYPEREFUND,
            'currency' => DatafastRequest::CURRENCY,
            'amount' => $this->toDecimalNumber( $request->getAmount()),
            'customParameters[SHOPPER_VERSIONDF]'=> '2'
        ];
    }


    private function toDecimalNumber(float $number): string
    {
        return number_format($number, 2, '.', '');
    }

    /**
     * @param Payment $payment
     * @return array
     */
    protected function addItemsToBody(Payment $payment): array
    {
        $body = [];
        $productCount = 0;
        foreach ($payment->getProductInfo() as $allProducts) {
            foreach ($allProducts as $key => $product) {
                $body['cart.items[' . $productCount . '].name'] = $product->getName();
                $body['cart.items[' . $productCount . '].description'] = strip_tags($product->getDescription());
                $body['cart.items[' . $productCount . '].price'] = $this->toDecimalNumber($product->getPrice());
                $body['cart.items[' . $productCount . '].quantity'] = $product->getQuantity();
                $productCount++;
            }
        }
        return $body;
    }


    private function getRiskParams(Payment $payment): array
    {
        $request = $payment->getRequest();
        return  [
                    'testMode'=> 'EXTERNAL'
                ];
    }

    private function getRiskParamsRefund(DatafastRequest $request): array
    {
        $request = $request->getRisk();
        return  [
                    'testMode'=> 'EXTERNAL'
                ];
    }

    private function getRegistrations(Payment $payment): array
    {
        $registrations = [];
        $registrationsCount = 0;

        foreach ($payment->getRegistrations() as $allRegistrations) {
            foreach ($allRegistrations as $key => $registration) {

                $registrations['registrations[' . $registrationsCount . '].id'] = $registration;
                $registrationsCount++;
            }
        }
        return $registrations;
    }
    /**
     * @return Logger
     */
    private function getLogger(): ?Logger
    {
        try {
            $logFolder = Constants::LOGGER_FOLDER;
            if (!file_exists($logFolder)) {
                mkdir($logFolder, 0777, true);
            }
            $logger = new Logger('PaymentService');
            $logger->pushHandler(new StreamHandler(Constants::LOGGER_FILE, Logger::DEBUG));
            return $logger;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function safeLog(string $level, string $message, array $context = []): void
    {
        try {
            $logger = $this->getLogger();
            if ($logger) {
                $logger->$level($message, $context);
            }
        } catch (\Throwable $e) {
            // Logger no disponible
        }
    }

    public function processPayment(DatafastRequest $datafastRequest): string
    {
        try {

            $resourcePathUri = $datafastRequest->getResourcePathUri() . "?entityId=" . $datafastRequest->getEntityId();
            $authBearer = $this->getAuth($datafastRequest);


            $ambiente = $datafastRequest->isTestMode();
            if ($ambiente == "1") {
                $verifyPeer = false;
              } else {
                $verifyPeer = true;
              }

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $resourcePathUri);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array($authBearer));
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,$verifyPeer);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$response = curl_exec($ch);
			if(curl_errno($ch))
			{
				return curl_error($ch);
			}
			curl_close($ch);

			return $response;
        } catch (\Exception $e) {
            $this->safeLog('error', "Error trying to get checkoutId.", $e->getTrace());
            return '';
        }

    }


    public function requestRefund(DatafastRequest $datafastRequest): string
    {
        $response = '';

        try {

            $checkOutUri = $datafastRequest->getResourcePathUriRefund()  ."/".$datafastRequest->getTransactionId();
            $authbearer   = $this->getAuth($datafastRequest);
            $body = $this->buildInitialBodyRefund($datafastRequest);



            $ambiente = $datafastRequest->isTestMode();
            if ($ambiente == "1") {
                $verifyPeer = false;
                $riskBody = $this->getRiskParamsRefund($datafastRequest);
                $body = array_merge($body,$riskBody);
              } else {
                $verifyPeer = true;
              }

            $body = http_build_query($body);
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $checkOutUri);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array($authbearer));
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,$verifyPeer	);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$response = curl_exec($ch);

			if(curl_errno($ch))
			{
				return curl_error($ch);
			}
			curl_close($ch);

            $this->safeLog('debug', "Response Body", (array)$response);

        } catch (\Exception $e) {
            $this->safeLog('error', "Error trying to get checkoutId.", $e->getTrace());
        }
        return $response;
    }

}
