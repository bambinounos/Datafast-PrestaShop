<?php


namespace datafast\payment\datafast\payment\model;


use datafast\payment\model\Amount;
use datafast\payment\model\CustomerInfo;
use datafast\payment\model\CartInfo;
use datafast\payment\model\DatafastRequest;


class Payment
{

    private $request;
    private $amount;
    private $cartInfo;
    private $productInfo;
    private $customerInfo;
    private $shipping;


    /**
     * @return DatafastRequest
     */
    public function getRequest(): DatafastRequest
    {
        return $this->request;
    }

    /**
     * @param DatafastRequest $request
     */
    public function setRequest(DatafastRequest $request): void
    {
        $this->request = $request;
    }

    /**
     * @return Amount
     */
    public function getAmount(): Amount
    {
        return $this->amount;
    }

    /**
     * @param Amount $amount
     */
    public function setAmount(Amount $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @return array
     */
    public function getProductInfo(): array
    {
        return $this->productInfo;
    }

    /**
     * @param array $productInfo
     */
    public function setProductInfo(array $productInfo): void
    {
        $this->productInfo = $productInfo;
    }

    public function getRegistrations(): array
    {
        return $this->registrations;
    }

    /**
     * @param array $productInfo
     */
    public function setRegistrations(array $registrations): void
    {
        $this->registrations = $registrations;
    }
    /**
     * @return CartInfo
     */
    public function getCartInfo(): CartInfo
    {
        return $this->cartInfo;
    }
    /**
     * @param array $cartId
     */
    public function setCartInfo(CartInfo $cartInfo): void
    {
        $this->cartInfo = $cartInfo;
    }


    /**
     * @return CustomerInfo
     */
    public function getCustomerInfo(): CustomerInfo
    {
        return $this->customerInfo;
    }

    /**
     * @param CustomerInfo $customerInfo
     */
    public function setCustomerInfo(CustomerInfo $customerInfo): void
    {
        $this->customerInfo = $customerInfo;
    }

    /**
     * @return Shipping
     */
    public function getShipping(): Shipping
    {
        return $this->shipping;
    }

    /**
     * @param Shipping $shipping
     */
    public function setShipping(Shipping $shipping): void
    {
        $this->shipping = $shipping;
    }


}