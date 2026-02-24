<?php


namespace datafast\payment\model;

class Amount
{

    private $subtotalIVA = 0;
    private $subtotalIVA0 = 0;
    private $total = 0;
    private $ivaRate =0;


    /**
     * @return float
     */
    public function getSubtotalIVA(): float
    {
        return $this->subtotalIVA;
    }

    /**
     * @param float $subtotalIVA
     */
    public function setSubtotalIVA(float $subtotalIVA): void
    {
        $this->subtotalIVA = $subtotalIVA;
    }

    /**
     * @return mixed
     */
    public function getIvaRate(): float
    {
        return $this->ivaRate;
    }

    /**
     * @param mixed $ivaRate
     */
    public function setIvaRate(float $ivaRate): void
    {
        $this->ivaRate = $ivaRate;
    }

    /**
     * @return mixed
     */
    public function getTotal(): float
    {
        return $this->total;
    }

    /**
     * @param mixed $total
     */
    public function setTotal(float $total): void
    {
        $this->total = $total;
    }

    /**
     * @return mixed
     */
    public function getIva(): float
    {
        return $this->total - $this->subtotalIVA - $this->getSubtotalIVA0();
    }

    /**
     * @return float
     */
    public function getSubtotalIVA0(): float
    {
        return $this->subtotalIVA0;
    }

    /**
     * @param float $subtotalIVA0
     */
    public function setSubtotalIVA0(float $subtotalIVA0): void
    {
        $this->subtotalIVA0 = $subtotalIVA0;
    }

}
