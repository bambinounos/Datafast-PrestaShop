<?php


namespace datafast\payment\model;


class DatafastRequest
{
    const PAYMENT_TYPE = "DB";
    const PAYMENT_TYPEREFUND = "RF";
    const CURRENCY = "USD";

    private $urlRequest = '';
    private $entityId = '';
    private $bearerToken = '';
    private $mid = '';
    private $tid = '';
    private $risk = '';
    private $testMode = false;
    private $riskParameters = '';
    private $checkoutScript;
    private $resourcePathUri;
    private $resourcePathUriRefund;
    private $proveedor = '';
    private $eci = '';
    private $transaction_id = '';
    private $amount = 0.0;
    private $prefijoTrx = '';

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
    public function setUrlRequest(?string $urlRequest): void
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
    public function setEntityId(?string $entityId): void
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
    public function setBearerToken(?string $bearerToken): void
    {
        $this->bearerToken = $bearerToken;
    }

    /**
     * @return string
     */
    public function getMid(): string
    {
        return $this->mid;
    }

    /**
     * @param string $mid
     */
    public function setMid(?string $mid): void
    {
        $this->mid = $mid;
    }

    /**
     * @return string
     */
    public function getTid(): string
    {
        return $this->tid;
    }

    /**
     * @param string $tid
     */
    public function setTid(?string $tid): void
    {
        $this->tid = $tid;
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
    public function setRisk(?string $risk): void
    {
        $this->risk = $risk;
    }

     /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @param mixed $amount
     */
    public function setAmount($amount): void
    {
        $this->amount = (float)$amount;
    }

     /**
     * @return float
     */
    public function getTransactionId(): string
    {
        return $this->transaction_id;
    }

    /**
     * @param string $amount
     */
    public function setTransactionId(?string $transaction_id): void
    {
        $this->transaction_id = $transaction_id;
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
    public function getRiskParameters(): string
    {
        return $this->riskParameters;
    }

    /**
     * @param string $riskParameters
     */
    public function setRiskParameters(?string $riskParameters): void
    {
        $this->riskParameters = $riskParameters;
    }


    /**
     * @return string
     */
    public function getProveedor(): string
    {
        return $this->proveedor;
    }

    /**
     * @param string $proveedor
     */
    public function setProveedor(?string $proveedor): void
    {
        $this->proveedor = $proveedor;
    }

    /**
     * @return string
     */
    public function getEci(): string
    {
        return $this->eci;
    }

    /**
     * @param string $eci
     */
    public function setEci(?string $eci): void
    {
        $this->eci = $eci;
    }

    /**
     * @return string
     */
    public function getPrefijoTrx(): string
    {
        return $this->prefijoTrx;
    }

    /**
     * @param string $proveedor
     */
    public function setPrefijoTrx(?string $prefijoTrx): void
    {
        $this->prefijoTrx = $prefijoTrx;
    }

    /**
     * @return mixed
     */
    public function getCheckoutScript()
    {
        return $this->checkoutScript;
    }

    /**
     * @param mixed $checkoutScript
     */
    public function setCheckoutScript($checkoutScript): void
    {
        $this->checkoutScript = $checkoutScript;
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

    /**
     * @return mixed
     */
    public function getResourcePathUriRefund()
    {
        return $this->resourcePathUriRefund;
    }

    /**
     * @param mixed $resourcePathUriRefund
     */
    public function setResourcePathUriRefund($resourcePathUriRefund): void
    {
        $this->resourcePathUriRefund = $resourcePathUriRefund;
    }


}
