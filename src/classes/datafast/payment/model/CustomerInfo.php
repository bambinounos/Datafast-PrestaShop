<?php


namespace datafast\payment\model;


class CustomerInfo
{

    const identificationDocType = "IDCARD";
    private $givenName;
    private $middleName;
    private $surname;
    private $ip;
    private $merchantCustomerId;
    private $merchantTransactionId;
    private $email;
    private $identificationDocId;
    private $phoneNumber;
    private $shippingAddress;
	private $billingAddress;
    private $country;
	private $billingCountry;
	private $billingPostCode;

    /**
     * CustomerInfo constructor.
     * @param $givenName
     * @param $middleName
     * @param $surname
     * @param $ip
     * @param $merchantCustomerId
     * @param $merchantTransactionId
     * @param $email
     * @param $identificationDocId
     * @param $phoneNumber
     * @param $shippingAddress
	 * @param $billingAddress
     * @param $country
     */
    public function __construct($givenName, $middleName, $surname, $ip, $merchantCustomerId, $merchantTransactionId, $email, $identificationDocId, $phoneNumber, $shippingAddress, $country, $billingAddress,$billingCountry,$billingPostCode )
    {
        $this->givenName = $givenName;
        $this->middleName = $middleName;
        $this->surname = $surname;
        $this->ip = $ip;
        $this->merchantCustomerId = $merchantCustomerId;
        $this->merchantTransactionId = $merchantTransactionId;
        $this->email = $email;
        $this->identificationDocId = $identificationDocId;
        $this->phoneNumber = $phoneNumber;
        $this->shippingAddress = $shippingAddress;
		$this->billingAddress = $billingAddress;
        $this->country = $country;
		$this->billingCountry = $billingCountry;
		$this->billingPostCode = $billingPostCode;
    }


    /**
     * @return mixed
     */
    public function getGivenName()
    {
        return $this->givenName;
    }

    /**
     * @param mixed $givenName
     */
    public function setGivenName($givenName): void
    {
        $this->givenName = $givenName;
    }

    /**
     * @return mixed
     */
    public function getMiddleName()
    {
        return $this->middleName;
    }

    /**
     * @param mixed $middleName
     */
    public function setMiddleName($middleName): void
    {
        $this->middleName = $middleName;
    }

    /**
     * @return mixed
     */
    public function getSurname()
    {
        return $this->surname;
    }

    /**
     * @param mixed $surname
     */
    public function setSurname($surname): void
    {
        $this->surname = $surname;
    }

    /**
     * @return mixed
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param mixed $ip
     */
    public function setIp($ip): void
    {
        $this->ip = $ip;
    }

    /**
     * @return mixed
     */
    public function getMerchantCustomerId()
    {
        return $this->merchantCustomerId;
    }

    /**
     * @param mixed $merchantCustomerId
     */
    public function setMerchantCustomerId($merchantCustomerId): void
    {
        $this->merchantCustomerId = $merchantCustomerId;
    }

    /**
     * @return mixed
     */
    public function getMerchantTransactionId()
    {
        return $this->merchantTransactionId;
    }

    /**
     * @param mixed $merchantTransactionId
     */
    public function setMerchantTransactionId($merchantTransactionId): void
    {
        $this->merchantTransactionId = $merchantTransactionId;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email): void
    {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getIdentificationDocId()
    {
        return $this->identificationDocId;
    }

    /**
     * @param mixed $identificationDocId
     */
    public function setIdentificationDocId($identificationDocId): void
    {
        $this->identificationDocId = $identificationDocId;
    }

    /**
     * @return mixed
     */
    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    /**
     * @param mixed $phoneNumber
     */
    public function setPhoneNumber($phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }

    /**
     * @return mixed
     */
    public function getShippingAddress()
    {
        return $this->shippingAddress;
    }

    /**
     * @param mixed $shippingAddress
     */
    public function setShippingAddress($shippingAddress): void
    {
        $this->shippingAddress = $shippingAddress;
    }

	  /**
     * @return mixed
     */
    public function getBillingAddress()
    {
        return $this->billingAddress;
    }

  /**
     * @param mixed $billingCountry
     */
    public function setBillingCountry($billingCountry): void
    {
        $this->billingCountry = $billingCountry;
    }

	  /**
     * @return mixed
     */
    public function getBillingCountry()
    {
        return $this->billingCountry;
    }
	
	 /**
     * @param mixed $billingPostCode
     */
    public function setBillingPostCode($billingPostCode): void
    {
        $this->billingPostCode = $billingPostCode;
    }

	  /**
     * @return mixed
     */
    public function getBillingPostCode()
    {
        return $this->billingPostCode;
    }
	
	
	
    /**
     * @param mixed $shippingAddress
     */
    public function setBillingAddress($billingAddress): void
    {
        $this->billingAddress = $billingAddress;
    }


    /**
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param mixed $country
     */
    public function setCountry($country): void
    {
        $this->country = $country;
    }


}