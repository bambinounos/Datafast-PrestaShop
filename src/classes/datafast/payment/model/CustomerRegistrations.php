<?php


namespace datafast\payment\model;


class CustomerRegistrations
{
 
    private $id;
    
    /**
     * CustomerRegistrations constructor.
     * @param $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }


    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $givenName
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    
}