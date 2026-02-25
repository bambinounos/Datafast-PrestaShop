<?php


namespace datafast\payment\datafast\payment\model;


class PaymentResult
{

    private $code;
    private $description;
    private $friendlyDescription;

    /**
     * PaymentResult constructor.
     * @param $code
     * @param $description
     */
    public function __construct($code, $description)
    {
        $this->code = $code;
        $this->description = $description;
    }

    public static function fromJson($json)
    {
        $arr = get_object_vars($json);

        return new self(
            $arr['code'] ?? null,
            $arr['description'] ?? null
        );
    }

    /**
     * @return mixed
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * @param mixed $code
     */
    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    /**
     * @return mixed
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getFriendlyDescription()
    {
        if (!is_null($this->code)) {
            $this->friendlyDescription = Message::getMessageDescription($this->code);
        }
        return $this->friendlyDescription;
    }

}
