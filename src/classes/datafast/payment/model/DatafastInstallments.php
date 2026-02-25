<?php

class DatafastInstallments extends ObjectModel
{
    const NAME_MAX_LENGTH = 10;

    public $id_installment;
    public $id_termtype;
    public $installments;
    public $name;
    public $active = true;
    public $id_transaction;
    public $deleted;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'datafast_installments',
        'primary' => 'id_installment',
        'fields' => [
            'name' => ['type' => self::TYPE_STRING],
            'id_termtype' => ['type' => self::TYPE_INT],
            'installments' => ['type' => self::TYPE_INT],
            'updated_at' => ['type' => self::TYPE_DATE],
            'active' => ['type' => self::TYPE_BOOL],
            'deleted' => ['type' => self::TYPE_INT]
    ]];

    
    
    public function updateInstallment()
    {
       
        return Db::getInstance()->execute('
			UPDATE `' . _DB_PREFIX_ . 'datafast_installments`
            SET `name` = \''. pSQL($this->name).'\'
            ,`id_termtype` = ' . (int) $this->id_termtype.'
            ,`installments` = ' . (int) $this->installments.'
            ,`active` = ' . (int) $this->active.'
			WHERE `id_installment` = ' . (int) $this->id_installment);
    }

    
    public function insertInstallment()
    {
  
        return Db::getInstance()->execute('
        INSERT INTO `' . _DB_PREFIX_ . 'datafast_installments`
        (name,id_termtype,installments,active,deleted)
        VALUES (\''. pSQL($this->name).'\'
        ,'. (int)$this->id_termtype.'
        ,'. (int)$this->installments.'
        ,'. (int)$this->active.'
        ,'. (int)$this->deleted.')');
    }

    public function deleteInstallment()
    {
       
        return Db::getInstance()->execute('
			UPDATE `' . _DB_PREFIX_ . 'datafast_installments`
            SET     deleted  = \'1\'
			WHERE `id_installment` = ' . (int) $this->id_installment);
    }

    public function update($nullValues = false)
    {
        $previousUpdate = new self((int) $this->id_installment);
        if (!parent::update($nullValues)) {
            return false;
        }
 
        return true;
    }
 
    
    public static function getsInstallmentsTermTypeConfiguration()
    {
        $res = Db::getInstance()->executeS('
        SELECT DISTINCT CONCAT(tt.name , \' - Cuotas: \' , it.installments )   AS name
                        ,CONCAT(tt.code,\'|\',it.installments)  AS code
                        ,tt.code            AS codeTermType
                        ,it.installments    AS installments
        FROM `' . _DB_PREFIX_ . 'datafast_installments` it
        JOIN `' . _DB_PREFIX_ . 'datafast_termtype` tt
        ON      it.id_termtype = tt.id
        WHERE   it.deleted  = \'0\'
        AND     it.active   = \'1\'
        ORDER BY 1 ASC');
       
        return $res;
    }
 
    public static function getsInstallmentsConfiguration()
    {
        $res = Db::getInstance()->executeS('
        SELECT DISTINCT it.installments   AS code
        FROM `' . _DB_PREFIX_ . 'datafast_installments` it
        WHERE   it.deleted  = \'0\'
        AND     it.active   = \'1\'
        ORDER BY 1 ASC');
       
        return $res;
    }


 public static function getTermTypes()
    {
        $res = Db::getInstance()->executeS('
        SELECT tt.name  AS nameTermType
            , tt.id     AS idTermType
        FROM `' . _DB_PREFIX_ . 'datafast_termtype` tt
        WHERE tt.active  = \'1\'');
        $termtypes = [];
        if ($res) {
            foreach ($res as $row) {
                $termtypes[ $row['idTermType']] =$row['nameTermType'];
            }
        } 
        return $termtypes;
    }
}
