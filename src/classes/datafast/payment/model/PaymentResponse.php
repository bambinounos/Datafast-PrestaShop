<?php


namespace datafast\payment\datafast\payment\model;


use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use datafast\payment\model\Constants;
use datafast\payment\datafast\payment\Config;

class PaymentResponse
{

    private $id;
    private $paymentType;
    private $paymentBrand;
    private $amount;
    private $currency;
    private $descriptor;
    private $merchantTransactionId;
    private $result;
    private $resultDetails;
    private $card;
    private $customer;

    private $cartId;
    private $customerId;
    private $accepted;

    private $customParameters;

    /**
     * PaymentResponse constructor.
     * @param $id
     * @param $paymentType
     * @param $paymentBrand
     * @param $amount
     * @param $currency
     * @param $descriptor
     * @param $merchantTransactionId
     * @param $result
     * @param $resultDetails
     * @param $card
     * @param $customer
     */
    public function __construct($id = null, $paymentType = null, $paymentBrand = null, $amount = null, $currency = null, $descriptor = null, $merchantTransactionId = null, $result = null, $resultDetails = null, $card = null, $customer = null, $customParameters = null)
    {
        $this->id = $id;
        $this->paymentType = $paymentType;
        $this->paymentBrand = $paymentBrand;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->descriptor = $descriptor;
        $this->merchantTransactionId = $merchantTransactionId;
        $this->result = $result;
        $this->resultDetails = $resultDetails;
        $this->card = $card;
        $this->customer = $customer;
        $this->customParameters = $customParameters;
    }


    public static function fromJson($json)
    {

        $arr = get_object_vars($json);
        $customer = null;
        if (array_key_exists('$customer', $arr)) {
            $customer = PaymentCustomer::fromJson($arr['$customer']);
        }

        $request = Config::getDatafastRequest();

        $mid = $request->getMid();
        $tid = $request->getTid();

        $logger = new Logger('PaymentResponse');
        $logger->pushHandler(new StreamHandler(Constants::LOGGER_FILE, Logger::DEBUG));
        $customArrayResponse = get_object_vars($arr['customParameters']);
        $midTid = $mid . "_" . $tid;
        $customParameters = $customArrayResponse[$midTid];

        return new self(
            $arr['id'],
            $arr['paymentType'],
            $arr['paymentBrand'],
            $arr['amount'],
            $arr['currency'],
            $arr['descriptor'],
            $arr['merchantTransactionId'],
            PaymentResult::fromJson($arr['result']),
            PaymentResultDetails::fromJson($arr['resultDetails']),
            PaymentCard::fromJson($arr['card']),
            $customer,
            $customParameters
        );
    }

    public static function paymentError(string $message = ""): PaymentResponse
    {
        return new PaymentResponse(null, null, null, null, null, null, null, null, null, null, null, null);
    }

    /**
     * @return mixed
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getPaymentType(): ?string
    {
        return $this->paymentType;
    }

    /**
     * @param mixed $paymentType
     */
    public function setPaymentType(?string $paymentType): void
    {
        $this->paymentType = $paymentType;
    }

    /**
     * @return mixed
     */
    public function getPaymentBrand(): ?string
    {
        return $this->paymentBrand;
    }

    /**
     * @param mixed $paymentBrand
     */
    public function setPaymentBrand(?string $paymentBrand): void
    {
        $this->paymentBrand = $paymentBrand;
    }

    /**
     * @return mixed
     */
    public function getAmount(): ?string
    {
        return $this->amount;
    }

    /**
     * @param mixed $amount
     */
    public function setAmount(?string $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @return mixed
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * @param mixed $currency
     */
    public function setCurrency(?string $currency): void
    {
        $this->currency = $currency;
    }

    /**
     * @return mixed
     */
    public function getDescriptor(): ?string
    {
        return $this->descriptor;
    }

    /**
     * @param mixed $descriptor
     */
    public function setDescriptor(?string $descriptor): void
    {
        $this->descriptor = $descriptor;
    }

    /**
     * @return mixed
     */
    public function getMerchantTransactionId(): ?string
    {
        return $this->merchantTransactionId;
    }

    /**
     * @param mixed $merchantTransactionId
     */
    public function setMerchantTransactionId(?string $merchantTransactionId): void
    {
        $this->merchantTransactionId = $merchantTransactionId;
    }

    /**
     * @return mixed
     */
    public function getResult(): ?PaymentResult
    {
        return $this->result;
    }

    /**
     * @param mixed $result
     */
    public function setResult(?PaymentResult $result): void
    {
        $this->result = $result;
    }

    /**
     * @return mixed
     */
    public function getResultDetails(): ?PaymentResultDetails
    {
        return $this->resultDetails;
    }

    /**
     * @param mixed $resultDetails
     */
    public function setResultDetails(?PaymentResultDetails $resultDetails): void
    {
        $this->resultDetails = $resultDetails;
    }

    /**
     * @return mixed
     */
    public function getCard(): ?PaymentCard
    {
        return $this->card;
    }

    /**
     * @param mixed $card
     */
    public function setCard(?PaymentCard $card): void
    {
        $this->card = $card;
    }

    /**
     * @return mixed
     */
    public function getCustomer(): ?PaymentCustomer
    {
        return $this->customer;
    }

    /**
     * @param mixed $customer
     */
    public function setCustomer(?PaymentCustomer $customer): void
    {
        $this->customer = $customer;
    }

    /**
     * @return mixed
     */
    public function getCartId(): ?string
    {
        return $this->cartId;
    }

    /**
     * @param mixed $cartId
     */
    public function setCartId($cartId): void
    {
        $this->cartId = $cartId;
    }

    /**
     * @return mixed
     */
    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    /**
     * @param mixed $customerId
     */
    public function setCustomerId($customerId): void
    {
        $this->customerId = $customerId;
    }

    /**
     * @return mixed
     */
    public function isAccepted(): ?bool
    {
        return $this->accepted;
    }

    /**
     * @param mixed $accepted
     */
    public function setAccepted($accepted): void
    {
        $this->accepted = $accepted;
    }

    /**
     * @return mixed
     */
    public function getCustomParameters()
    {
        return $this->customParameters;
    }


}
