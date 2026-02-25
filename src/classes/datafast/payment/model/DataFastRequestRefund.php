<?php


namespace datafast\payment\model;


class DatafastRequestRefund
{
    const PAYMENT_TYPEREFUND = "RF";
    const CURRENCY = "USD";

    private $urlRequest = '';
    private $entityId = '';
    private $bearerToken = '';
    private $amount = '';
    private $risk = '';
    private $testMode = false;
    private $resourcePathUri = '';

    /**
     * @return string
     */
    public function getUrlRequest(): string
    {
        return $this->urlRequest;
    }

    /**
     * @param string $urlRequest
     */
    public function setUrlRequest(string $urlRequest): void
    {
        $this->urlRequest = $urlRequest;
    }

    /**
     * @return string
     */
    public function getEntityId(): string
    {
        return $this->entityId;
    }

    /**
     * @param string $entityId
     */
    public function setEntityId(string $entityId): void
    {
        $this->entityId = $entityId;
    }

    /**
     * @return string
     */
    public function getBearerToken(): string
    {
        return $this->bearerToken;
    }

    /**
     * @param string $bearerToken
     */
    public function setBearerToken(string $bearerToken): void
    {
        $this->bearerToken = $bearerToken;
    }

    
    /**
     * @return bool
     */
    public function isTestMode(): bool
    {
        return $this->testMode;
    }

    /**
     * @param bool $testMode
     */
    public function setTestMode(bool $testMode): void
    {
        $this->testMode = $testMode;
    }

   /**
     * @return string
     */
    public function getRisk(): string
    {
        return $this->risk;
    }

    /**
     * @param string $risk
     */
    public function setRisk(string $risk): void
    {
        $this->risk = $risk;
    }
     
    

    /** @return string
    */
   public function  getAmount(): string
   {
       return $this->amount;
   }

   /**
    * @param string $proveedor
    */
   public function setAmount(string $amount): void
   {
       $this->amount = $amount;
   }

    
    /**
     * @return mixed
     */
    public function getResourcePathUri()
    {
        return $this->resourcePathUri;
    }

    /**
     * @param mixed $resourcePathUri
     */
    public function setResourcePathUri($resourcePathUri): void
    {
        $this->resourcePathUri = $resourcePathUri;
    }


}