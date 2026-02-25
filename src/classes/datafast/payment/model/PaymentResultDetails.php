<?php


namespace datafast\payment\datafast\payment\model;


class PaymentResultDetails
{
    private $AuthCode;
    private $ConnectorTxID1;
    private $RiskFraudStatusCode;
    private $RequestId;
    private $ReferenceNbr;
    private $EXTERNAL_SYSTEM_LINK;
    private $OrderId;
    private $RiskStatusCode;
    private $ExtendedDescription;
    private $clearingInstituteName;
    private $RiskResponseCode;
    private $action;
    private $AcquirerResponse;
    private $RiskOrderId;

    /**
     * PaymentResultDetails constructor.
     * @param $AuthCode
     * @param $ConnectorTxID1
     * @param $RiskFraudStatusCode
     * @param $RequestId
     * @param $ReferenceNbr
     * @param $EXTERNAL_SYSTEM_LINK
     * @param $OrderId
     * @param $RiskStatusCode
     * @param $ExtendedDescription
     * @param $clearingInstituteName
     * @param $RiskResponseCode
     * @param $action
     * @param $AcquirerResponse
     * @param $RiskOrderId
     */
    public function __construct($AuthCode, $ConnectorTxID1, $RiskFraudStatusCode, $RequestId, $ReferenceNbr, $EXTERNAL_SYSTEM_LINK, $OrderId, $RiskStatusCode, $ExtendedDescription, $clearingInstituteName, $RiskResponseCode, $action, $AcquirerResponse, $RiskOrderId)
    {
        $this->AuthCode = $AuthCode;
        $this->ConnectorTxID1 = $ConnectorTxID1;
        $this->RiskFraudStatusCode = $RiskFraudStatusCode;
        $this->RequestId = $RequestId;
        $this->ReferenceNbr = $ReferenceNbr;
        $this->EXTERNAL_SYSTEM_LINK = $EXTERNAL_SYSTEM_LINK;
        $this->OrderId = $OrderId;
        $this->RiskStatusCode = $RiskStatusCode;
        $this->ExtendedDescription = $ExtendedDescription;
        $this->clearingInstituteName = $clearingInstituteName;
        $this->RiskResponseCode = $RiskResponseCode;
        $this->action = $action;
        $this->AcquirerResponse = $AcquirerResponse;
        $this->RiskOrderId = $RiskOrderId;
    }


    public static function fromJson($json)
    {
        $arr = get_object_vars($json);

        $authCode = null;
        if (array_key_exists('AuthCode', $arr)) {
            $authCode = $arr['AuthCode'];
        }

        $connectorTxID1 = null;
        if (array_key_exists('ConnectorTxID1', $arr)) {
            $connectorTxID1 = $arr['ConnectorTxID1'];
        }

        $riskFraudStatusCode = null;
        if (array_key_exists('RiskFraudStatusCode', $arr)) {
            $riskFraudStatusCode = $arr['RiskFraudStatusCode'];
        }

        $requestId = null;
        if (array_key_exists('RequestId', $arr)) {
            $requestId = $arr['RequestId'];
        }

        $referenceNbr = null;
        if (array_key_exists('ReferenceNbr', $arr)) {
            $referenceNbr = $arr['ReferenceNbr'];
        }


        $externalSystemLink = null;
        if (array_key_exists('EXTERNAL_SYSTEM_LINK', $arr)) {
            $externalSystemLink = $arr['EXTERNAL_SYSTEM_LINK'];
        }

        $orderId = null;
        if (array_key_exists('OrderId', $arr)) {
            $orderId = $arr['OrderId'];
        }

        $riskStatusCode = null;
        if (array_key_exists('RiskStatusCode', $arr)) {
            $riskStatusCode = $arr['RiskStatusCode'];
        }

        $extendedDescription = null;
        if (array_key_exists('ExtendedDescription', $arr)) {
            $extendedDescription = $arr['ExtendedDescription'];
        }

        $clearingInstituteName = null;
        if (array_key_exists('clearingInstituteName', $arr)) {
            $clearingInstituteName = $arr['clearingInstituteName'];
        }

        $riskResponseCode = null;
        if (array_key_exists('RiskResponseCode', $arr)) {
            $riskResponseCode = $arr['RiskResponseCode'];
        }

        $action = null;
        if (array_key_exists('action', $arr)) {
            $action = $arr['action'];
        }

        $acquirerResponse = null;
        if (array_key_exists('AcquirerResponse', $arr)) {
            $acquirerResponse = $arr['AcquirerResponse'];
        }

        $riskOrderId = null;
        if (array_key_exists('RiskOrderId', $arr)) {
            $riskOrderId = $arr['RiskOrderId'];
        }

        return new self(
            $authCode,
            $connectorTxID1,
            $riskFraudStatusCode,
            $requestId,
            $referenceNbr,
            $externalSystemLink,
            $orderId,
            $riskStatusCode,
            $extendedDescription,
            $clearingInstituteName,
            $riskResponseCode,
            $action,
            $acquirerResponse,
            $riskOrderId
        );
    }

    /**
     * @return mixed
     */
    public function getAuthCode(): ?string
    {
        return $this->AuthCode;
    }

    /**
     * @param mixed $AuthCode
     */
    public function setAuthCode(?string $AuthCode): void
    {
        $this->AuthCode = $AuthCode;
    }

    /**
     * @return mixed
     */
    public function getConnectorTxID1(): ?string
    {
        return $this->ConnectorTxID1;
    }

    /**
     * @param mixed $ConnectorTxID1
     */
    public function setConnectorTxID1(?string $ConnectorTxID1): void
    {
        $this->ConnectorTxID1 = $ConnectorTxID1;
    }

    /**
     * @return mixed
     */
    public function getRiskFraudStatusCode(): ?string
    {
        return $this->RiskFraudStatusCode;
    }

    /**
     * @param mixed $RiskFraudStatusCode
     */
    public function setRiskFraudStatusCode(?string $RiskFraudStatusCode): void
    {
        $this->RiskFraudStatusCode = $RiskFraudStatusCode;
    }

    /**
     * @return mixed
     */
    public function getRequestId(): ?string
    {
        return $this->RequestId;
    }

    /**
     * @param mixed $RequestId
     */
    public function setRequestId(?string $RequestId): void
    {
        $this->RequestId = $RequestId;
    }

    /**
     * @return mixed
     */
    public function getReferenceNbr(): ?string
    {
        return $this->ReferenceNbr;
    }

    /**
     * @param mixed $ReferenceNbr
     */
    public function setReferenceNbr(?string $ReferenceNbr): void
    {
        $this->ReferenceNbr = $ReferenceNbr;
    }

    /**
     * @return mixed
     */
    public function getEXTERNALSYSTEMLINK(): ?string
    {
        return $this->EXTERNAL_SYSTEM_LINK;
    }

    /**
     * @param mixed $EXTERNAL_SYSTEM_LINK
     */
    public function setEXTERNALSYSTEMLINK(?string $EXTERNAL_SYSTEM_LINK): void
    {
        $this->EXTERNAL_SYSTEM_LINK = $EXTERNAL_SYSTEM_LINK;
    }

    /**
     * @return mixed
     */
    public function getOrderId(): ?string
    {
        return $this->OrderId;
    }

    /**
     * @param mixed $OrderId
     */
    public function setOrderId(?string $OrderId): void
    {
        $this->OrderId = $OrderId;
    }

    /**
     * @return mixed
     */
    public function getRiskStatusCode(): ?string
    {
        return $this->RiskStatusCode;
    }

    /**
     * @param mixed $RiskStatusCode
     */
    public function setRiskStatusCode(?string $RiskStatusCode): void
    {
        $this->RiskStatusCode = $RiskStatusCode;
    }

    /**
     * @return mixed
     */
    public function getExtendedDescription(): ?string
    {
        return $this->ExtendedDescription;
    }

    /**
     * @param mixed $ExtendedDescription
     */
    public function setExtendedDescription(?string $ExtendedDescription): void
    {
        $this->ExtendedDescription = $ExtendedDescription;
    }

    /**
     * @return mixed
     */
    public function getClearingInstituteName(): ?string
    {
        return $this->clearingInstituteName;
    }

    /**
     * @param mixed $clearingInstituteName
     */
    public function setClearingInstituteName(?string $clearingInstituteName): void
    {
        $this->clearingInstituteName = $clearingInstituteName;
    }

    /**
     * @return mixed
     */
    public function getRiskResponseCode(): ?string
    {
        return $this->RiskResponseCode;
    }

    /**
     * @param mixed $RiskResponseCode
     */
    public function setRiskResponseCode(?string $RiskResponseCode): void
    {
        $this->RiskResponseCode = $RiskResponseCode;
    }

    /**
     * @return mixed
     */
    public function getAction(): ?string
    {
        return $this->action;
    }

    /**
     * @param mixed $action
     */
    public function setAction(?string $action): void
    {
        $this->action = $action;
    }

    /**
     * @return mixed
     */
    public function getAcquirerResponse(): ?string
    {
        return $this->AcquirerResponse;
    }

    /**
     * @param mixed $AcquirerResponse
     */
    public function setAcquirerResponse(?string $AcquirerResponse): void
    {
        $this->AcquirerResponse = $AcquirerResponse;
    }

    /**
     * @return mixed
     */
    public function getRiskOrderId(): ?string
    {
        return $this->RiskOrderId;
    }

    /**
     * @param mixed $RiskOrderId
     */
    public function setRiskOrderId(?string $RiskOrderId): void
    {
        $this->RiskOrderId = $RiskOrderId;
    }


}
