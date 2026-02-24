<?php


namespace datafast\payment\datafast\payment\model;


class PaymentCustomer
{

    private $givenName;
    private $surname;
    private $merchantCustomerId;
    private $phone;
    private $email;
    private $identificationDocType;
    private $identificationDocId;
    private $ip;
    private $ipCountry;

    /**
     * PaymentCustomer constructor.
     * @param $givenName
     * @param $surname
     * @param $merchantCustomerId
     * @param $phone
     * @param $email
     * @param $identificationDocType
     * @param $identificationDocId
     * @param $ip
     * @param $ipCountry
     */
    public function __construct($givenName, $surname, $merchantCustomerId, $phone, $email, $identificationDocType, $identificationDocId, $ip, $ipCountry)
    {
        $this->givenName = $givenName;
        $this->surname = $surname;
        $this->merchantCustomerId = $merchantCustomerId;
        $this->phone = $phone;
        $this->email = $email;
        $this->identificationDocType = $identificationDocType;
        $this->identificationDocId = $identificationDocId;
        $this->ip = $ip;
        $this->ipCountry = $ipCountry;
    }

    public static function fromJson($json)
    {
        if (is_null($json)) {
            return new self(
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null
            );
        } else {
            $arr = get_object_vars($json);

            return new self(
                $arr['givenName'],
                $arr['surname'],
                $arr['merchantCustomerId'],
                $arr['phone'],
                $arr['email'],
                $arr['identificationDocType'],
                $arr['identificationDocId'],
                $arr['ip'],
                $arr['ipCountry']
            );
        }
    }

    /**
     * @return mixed
     */
    public function getGivenName(): ?string
    {
        return $this->givenName;
    }

    /**
     * @param mixed $givenName
     */
    public function setGivenName(?string $givenName): void
    {
        $this->givenName = $givenName;
    }

    /**
     * @return mixed
     */
    public function getSurname(): ?string
    {
        return $this->surname;
    }

    /**
     * @param mixed $surname
     */
    public function setSurname(?string $surname): void
    {
        $this->surname = $surname;
    }

    /**
     * @return mixed
     */
    public function getMerchantCustomerId(): ?string
    {
        return $this->merchantCustomerId;
    }

    /**
     * @param mixed $merchantCustomerId
     */
    public function setMerchantCustomerId(?string $merchantCustomerId): void
    {
        $this->merchantCustomerId = $merchantCustomerId;
    }

    /**
     * @return mixed
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * @param mixed $phone
     */
    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }

    /**
     * @return mixed
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getIdentificationDocType(): ?string
    {
        return $this->identificationDocType;
    }

    /**
     * @param mixed $identificationDocType
     */
    public function setIdentificationDocType(?string $identificationDocType): void
    {
        $this->identificationDocType = $identificationDocType;
    }

    /**
     * @return mixed
     */
    public function getIdentificationDocId(): ?string
    {
        return $this->identificationDocId;
    }

    /**
     * @param mixed $identificationDocId
     */
    public function setIdentificationDocId(?string $identificationDocId): void
    {
        $this->identificationDocId = $identificationDocId;
    }

    /**
     * @return mixed
     */
    public function getIp(): ?string
    {
        return $this->ip;
    }

    /**
     * @param mixed $ip
     */
    public function setIp(?string $ip): void
    {
        $this->ip = $ip;
    }

    /**
     * @return mixed
     */
    public function getIpCountry(): ?string
    {
        return $this->ipCountry;
    }

    /**
     * @param mixed $ipCountry
     */
    public function setIpCountry(?string $ipCountry): void
    {
        $this->ipCountry = $ipCountry;
    }


}
