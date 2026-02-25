<?php


namespace datafast\payment\datafast\payment\model;


class PaymentCard
{

    private $bin;
    private $binCountry;
    private $last4Digits;
    private $holder;
    private $expiryMonth;
    private $expiryYear;

    /**
     * PaymentCard constructor.
     * @param $bin
     * @param $binCountry
     * @param $last4Digits
     * @param $holder
     * @param $expiryMonth
     * @param $expiryYear
     */
    public function __construct($bin, $binCountry, $last4Digits, $holder, $expiryMonth, $expiryYear)
    {
        $this->bin = $bin;
        $this->binCountry = $binCountry;
        $this->last4Digits = $last4Digits;
        $this->holder = $holder;
        $this->expiryMonth = $expiryMonth;
        $this->expiryYear = $expiryYear;
    }

    public static function fromJson($json)
    {
        $arr = get_object_vars($json);

        return new self(
            $arr['bin'] ?? null,
            $arr['binCountry'] ?? null,
            $arr['last4Digits'] ?? null,
            $arr['holder'] ?? null,
            $arr['expiryMonth'] ?? null,
            $arr['expiryYear'] ?? null
        );
    }

    /**
     * @return mixed
     */
    public function getBin(): ?string
    {
        return $this->bin;
    }

    /**
     * @param mixed $bin
     */
    public function setBin(?string $bin): void
    {
        $this->bin = $bin;
    }

    /**
     * @return mixed
     */
    public function getBinCountry(): ?string
    {
        return $this->binCountry;
    }

    /**
     * @param mixed $binCountry
     */
    public function setBinCountry(?string $binCountry): void
    {
        $this->binCountry = $binCountry;
    }

    /**
     * @return mixed
     */
    public function getLast4Digits(): ?string
    {
        return $this->last4Digits;
    }

    /**
     * @param mixed $last4Digits
     */
    public function setLast4Digits(?string $last4Digits): void
    {
        $this->last4Digits = $last4Digits;
    }

    /**
     * @return mixed
     */
    public function getHolder(): ?string
    {
        return $this->holder;
    }

    /**
     * @param mixed $holder
     */
    public function setHolder(?string $holder): void
    {
        $this->holder = $holder;
    }

    /**
     * @return mixed
     */
    public function getExpiryMonth(): ?string
    {
        return $this->expiryMonth;
    }

    /**
     * @param mixed $expiryMonth
     */
    public function setExpiryMonth(?string $expiryMonth): void
    {
        $this->expiryMonth = $expiryMonth;
    }

    /**
     * @return mixed
     */
    public function getExpiryYear(): ?string
    {
        return $this->expiryYear;
    }

    /**
     * @param mixed $expiryYear
     */
    public function setExpiryYear(?string $expiryYear): void
    {
        $this->expiryYear = $expiryYear;
    }

}
