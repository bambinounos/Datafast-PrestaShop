<?php


namespace datafast\payment\model;


class CartInfo
{
    private $cartId; 
    /**
     * CartInfo constructor.
     * @param $cartId
     */
    public function __construct($cartId)
    {
        $this->cartId = $cartId;
        
    }

    /**
     * @return mixed
     */
    public function getCartId()
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
}