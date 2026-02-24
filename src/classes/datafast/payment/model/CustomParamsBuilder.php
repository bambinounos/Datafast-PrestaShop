<?php


namespace datafast\payment\datafast\payment\model;


use datafast\payment\model\Amount;

class CustomParamsBuilder
{
    private $ivaField = "004";
    private $ivaFieldLength = "012";

    private $subTotal0Field = "052";
    private $subTotal0Length = "012";

    private $subTotal12Field = "053";
    private $subTotal12Length = "012";


    private $eCommerceField = "003";
    private $eCommerceLength = "007";
    private $eCommerceValue = "0103910";


    private $serviceProviderField = "051";
    private $serviceProviderLength = "008";
    private $serviceProviderValue = "17913101";


    private $customParamValue = "0081";


    public function buildCustomParams(Amount $amount): string
    {
		 
        return $this->getCustomParamValue()
            . $this->getECommerceField()
            . $this->getECommerceLength()
            . $this->getECommerceValue()
            . $this->getIvaField()
            . $this->getIvaFieldLength()
            . $this->toIvaValue($amount)
            . $this->getServiceProviderField()
            . $this->getServiceProviderLength()
            . $this->getServiceProviderValue()
            . $this->getSubTotal0Field()
            . $this->getSubTotal0Length()
            . $this->toSubtotal0Value($amount)
            . $this->getSubTotal12Field()
            . $this->getSubTotal12Length()
            . $this->toSubtotal12Value($amount);

    }

    /**
     * @return string
     */
    public function getCustomParamValue(): string
    {
        return $this->customParamValue;
    }

    /**
     * @return string
     */
    public function getECommerceField(): string
    {
        return $this->eCommerceField;
    }

    /**
     * @return string
     */
    public function getECommerceLength(): string
    {
        return $this->eCommerceLength;
    }

    /**
     * @return string
     */
    public function getECommerceValue(): string
    {
        return $this->eCommerceValue;
    }

    /**
     * @return string
     */
    public function getIvaField(): string
    {
        return $this->ivaField;
    }

    /**
     * @return string
     */
    public function getIvaFieldLength(): string
    {
        return $this->ivaFieldLength;
    }

    private function toIvaValue(Amount $amount): string
    {
        $length = intval($this->getIvaFieldLength());
        return $this->toZeroPad($amount->getIva(), $length);
    }

    private function toZeroPad(float $value, int $length): string
    {
        $stringNumber = str_replace(".", "", sprintf("%.2f", $value));
        return str_pad($stringNumber, $length, '0', STR_PAD_LEFT);
    }

    /**
     * @return string
     */
    public function getServiceProviderField(): string
    {
        return $this->serviceProviderField;
    }

    /**
     * @return string
     */
    public function getServiceProviderLength(): string
    {
        return $this->serviceProviderLength;
    }

    /**
     * @return string
     */
    public function getServiceProviderValue(): string
    {
        return $this->serviceProviderValue;
    }

    /**
     * @return string
     */
    public function getSubTotal0Field(): string
    {
        return $this->subTotal0Field;
    }

    /**
     * @return string
     */
    public function getSubTotal0Length(): string
    {
        return $this->subTotal0Length;
    }

    private function toSubtotal0Value(Amount $amount): string
    {
        $length = intval($this->getSubTotal0Length());
        return $this->toZeroPad($amount->getSubtotalIVA0(), $length);
    }

    /**
     * @return string
     */
    public function getSubTotal12Field(): string
    {
        return $this->subTotal12Field;
    }

    /**
     * @return string
     */
    public function getSubTotal12Length(): string
    {
        return $this->subTotal12Length;
    }

    private function toSubtotal12Value(Amount $amount): string
    {
        $length = intval($this->getSubTotal12Length());
        return $this->toZeroPad($amount->getSubtotalIVA(), $length);

    }


}