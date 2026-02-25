<?php

use Monolog\Handler\StreamHandler;
use Monolog\Logger;  
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use datafast\payment\model\Amount;
use datafast\payment\model\Constants;
use datafast\payment\model\CustomerInfo;
use datafast\payment\model\CartInfo;
use datafast\payment\model\DatafastRequest;
use datafast\payment\model\ProductInfo;
use datafast\payment\PaymentService;
use datafast\payment\datafast\payment\Config; 
use datafast\payment\datafast\payment\model\Payment;
use datafast\payment\model\Environment;

include_once __DIR__ . '/src/classes/datafast/payment/model/Constants.php';
include_once __DIR__ . '/src/classes/datafast/payment/model/Environment.php';
include_once __DIR__ . '/src/classes/datafast/payment/model/Amount.php';
include_once __DIR__ . '/src/classes/datafast/payment/model/CardBrands.php';
include_once __DIR__ . '/src/classes/datafast/payment/model/Payment.php';
include_once __DIR__ . '/src/classes/datafast/payment/model/ProductInfo.php';
include_once __DIR__ . '/src/classes/datafast/payment/model/CartInfo.php';
include_once __DIR__ . '/src/classes/datafast/payment/model/CustomerInfo.php';
include_once __DIR__ . '/src/classes/datafast/payment/model/DatafastRequest.php';
include_once __DIR__ . '/src/classes/datafast/payment/model/DatafastInstallments.php';
include_once __DIR__ . '/src/classes/datafast/payment/PaymentService.php';
include_once __DIR__ . '/src/classes/datafast/payment/datafastDB.php';
include_once __DIR__ . '/src/classes/datafast/payment/Config.php';


if (!defined('_PS_VERSION_')) {
    exit;
}


class datafast extends PaymentModule
{

    private $entityId;
    private $bearerToken;
    private $mid;
    private $tid;
    private $proveedor;
    private $prefijo_trx;
	private $installments;
    private $dev;
    private $produrl;
    private $devurl;


    public function __construct()
    {
        $this->name = 'datafast';
        $this->tab = 'payments_gateways';
        $this->version = '2.1.0';
        $this->author = 'Sismetic';
        $this->need_instance = 0;
        $this->is_configurable = 1;

        $this->ps_versions_compliancy = array('min' => '1.7.0', 'max' => _PS_VERSION_);


        parent::__construct();

        $this->displayName = $this->l('Botón de Datafast');
        $this->description = $this->l('Módulo de pagos de Datafast');
        $this->confirmUninstall = $this->l('Está seguro que desea desinstalar el módulo de pagos de Datafast?');


        $this->bootstrap = true;
        $this->checkIfConfigurationIsProvided();
        $this->checkForCurrency();
        $this->checkForLogsFolder();

    }

    public function checkIfConfigurationIsProvided(): void
    {
        if (!isset($this->entityId)
            || !isset($this->dev)
            || !isset($this->produrl)
            || !isset($this->bearerToken)
            || !isset($this->mid)
            || !isset($this->tid)
            || !isset($this->risk)
            || !isset($this->proveedor)
            || !isset($this->prefijo_trx)
            || empty($this->dev)
            || empty($this->produrl)
            || empty($this->entityId)
            || empty($this->bearerToken)
            || empty($this->mid)
            || empty($this->tid)
            || empty($this->risk)
            || empty($this->proveedor)
            || empty($this->prefijo_trx)
        ) {
            $this->warning = 'Toda la información debe ser configurada antes de utilizar el módulo.';
            $this->status_module = false;
        }
    }

    public function checkForCurrency(): void
    {
        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->l('No currency has been set for this module.');
        }
    }

    public function checkForLogsFolder(): void
    {
        $logFolder = Constants::LOGGER_FOLDER;
        if (!file_exists($logFolder)) {
            mkdir($logFolder, 0777, true);
        }
    }

    public function install()
    {
        if (extension_loaded('curl') == false) {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }
        Configuration::updateValue('DATAFAST_VERSION', $this->version);
        Configuration::updateValue('DATAFAST_DEV', true);

        PrestaShopLogger::addLog('Instalación de módulo de pagos de Datafast', 2);


        if (!$this->createTableDatafast()) {
            $this->_errors[] = $this->l('No se pudo crear la base de datos para el módulo de Datafast');
            return false;
        }else{
            Db::getInstance()->execute('ALTER TABLE '._DB_PREFIX_.'datafast_transactions MODIFY COLUMN `request_json` TEXT DEFAULT NULL');
            Db::getInstance()->execute('ALTER TABLE '._DB_PREFIX_.'datafast_transactions MODIFY COLUMN `response_json` TEXT DEFAULT NULL');
            Db::getInstance()->execute('ALTER TABLE '._DB_PREFIX_.'datafast_transactions MODIFY COLUMN `customer_id` VARCHAR (100) DEFAULT NULL');
            Db::getInstance()->execute('ALTER TABLE '._DB_PREFIX_.'datafast_customertoken MODIFY COLUMN `customer_id` VARCHAR (100) DEFAULT NULL');
        }
        
        if (!$this->createTableInstallments()) {
            $this->_errors[] = $this->l('No se pudo crear la tabla para almacenar la configuración de tipos de creditos para el módulo de Datafast');
            return false;
        } 

		if (!$this->createTableTermType()) {
            $this->_errors[] = $this->l('No se pudo crear la tabla de tipos de creditos para el módulo de Datafast');
            return false;
        }
         
            $this->insertDataTermType();
        



        if (!$this->createTableCustomerToken()) {
            $this->_errors[] = $this->l('No se pudo crear la tabla de tokenizacion de clientes para el módulo de Datafast');
            return false;
        }
        
        
        if (parent::install()
            && $this->registerHook('paymentOptions')
            && $this->registerHook('paymentReturn')
            && $this->registerHook('displayHeader')
			&& $this->registerHook('actionOrderStatusUpdate'))

			{
            

            $this->addOrderState($this->l('Reembolso - Datafast'));

            return true;
        } else {
            $this->_errors[] = $this->l('No se pudo registrar los payments hooks en el botón de Datafast');
            return false;
        }


        return true;
    }

    public function addOrderState($name)
    {
        $state_exist = false;
        $states = OrderState::getOrderStates((int)$this->context->language->id);
 
        // check if order state exist
        foreach ($states as $state) {
            if (in_array($name, $state)) {
                $state_exist = true;
                break;
            }
        }
 

        if (!$state_exist) {
            $order_state = new OrderState();
            $order_state->color = '#00ac72';
            $order_state->send_email = true;
            $order_state->module_name = 'Datafast';
            $order_state->template = 'Datafast';
            $order_state->name = array();
            $languages = Language::getLanguages(false);
            foreach ($languages as $language)
                $order_state->name[ $language['id_lang'] ] = $name;

            $order_state->add();
        }
 
        return true;
    }


    public function createTableDatafast()
    {
        return Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'datafast_transactions(
            `id` INTEGER(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
            `cart_id` INTEGER(11) DEFAULT NULL,
            `customer_id` VARCHAR (100) DEFAULT NULL,          
            `checkout_id` VARCHAR (100) DEFAULT NULL,
            `result_description` VARCHAR (100) DEFAULT NULL,
            `transaction_id` VARCHAR (100) DEFAULT NULL,
            `payment_type` VARCHAR (100) DEFAULT NULL,
            `payment_brand` VARCHAR (100) DEFAULT NULL,
            `amount` FLOAT(11) DEFAULT NULL,
            `merchant_transactionId` VARCHAR (100) DEFAULT NULL,
            `result_code` VARCHAR (100) DEFAULT NULL,
            `extended_description` VARCHAR (100) DEFAULT NULL,
            `acquirer_response` VARCHAR (5) DEFAULT NULL,
            `auth_code` VARCHAR (20) DEFAULT NULL,
            `response` VARCHAR (5) DEFAULT NULL,
            `acquirer_code` VARCHAR (20) DEFAULT NULL,
            `batch_no` VARCHAR (20) DEFAULT NULL,
            `interest` FLOAT(11) DEFAULT NULL,
            `total_amount` FLOAT(11) DEFAULT NULL,
            `reference_no` VARCHAR (20) DEFAULT NULL,
            `bin` VARCHAR (20) DEFAULT NULL,
            `last_4_Digits` VARCHAR (4) DEFAULT NULL,
            `email` VARCHAR (200) DEFAULT NULL,
            `shopper_mid` VARCHAR (200) DEFAULT NULL,
            `shopper_tid` VARCHAR (200) DEFAULT NULL,
            `timestamp` VARCHAR (200) DEFAULT NULL,
            `request_json` TEXT DEFAULT NULL,
            `response_json` TEXT DEFAULT NULL,
            `status` TINYINT DEFAULT NULL,
            `updated_at` DATETIME DEFAULT NULL)
            ENGINE = ' . _MYSQL_ENGINE_ . ' '
        );
    }
 
	public function createTableInstallments()
    {
        return Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'datafast_installments (
            `id_installment` INTEGER(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
            `name` VARCHAR (200) ,
            `id_termtype` INTEGER(11) DEFAULT NULL,
            `installments` INTEGER(11) DEFAULT NULL,
            `active` TINYINT,
            `deleted` TINYINT,
            `updated_at` DATETIME DEFAULT NULL)
            ENGINE = ' . _MYSQL_ENGINE_ . ' '
        );
    }


	public function createTableTermType()
    {
        return Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'datafast_termtype (
            `id` INTEGER(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
            `name` VARCHAR (200) ,
			`code` VARCHAR (100) ,
            `active` TINYINT)
            ENGINE = ' . _MYSQL_ENGINE_ . ' '
        );
    }

	public function insertDataTermType()
    {
       
            $query00 =  " INSERT INTO " . _DB_PREFIX_ . "datafast_termtype(name,code,active) ".
                        " SELECT 'Corriente','00',1 FROM (SELECT 1) t ".
                        " WHERE NOT EXISTS (SELECT code FROM  " . _DB_PREFIX_ . "datafast_termtype ".
                        " WHERE code='00')";
            Db::getInstance()->execute($query00);
			 
            $query01 = " INSERT INTO " . _DB_PREFIX_ . "datafast_termtype(name,code,active) ".
						" SELECT 'Diferido corriente','01',1 FROM (SELECT 1) t".
						" WHERE NOT EXISTS (SELECT code FROM  " . _DB_PREFIX_ . "datafast_termtype ".
						" WHERE code='01')";
            Db::getInstance()->execute($query01);

            $query02 = " INSERT INTO " . _DB_PREFIX_ . "datafast_termtype(name,code,active) ".
						" SELECT 'Diferido con Interés','02',1 FROM (SELECT 1) t ".
						" WHERE NOT EXISTS (SELECT code FROM  " . _DB_PREFIX_ . "datafast_termtype ".
						" WHERE code='02')";
            Db::getInstance()->execute($query02);

            $query03 = " INSERT INTO " . _DB_PREFIX_ . "datafast_termtype(name,code,active) ".
						" SELECT 'Diferido sin Interés','03',1 FROM (SELECT 1) t".
						" WHERE NOT EXISTS (SELECT code FROM  " . _DB_PREFIX_ . "datafast_termtype ".
						" WHERE code='03')";
            Db::getInstance()->execute($query03);

            $query07 = " INSERT INTO " . _DB_PREFIX_ . "datafast_termtype(name,code,active) ".
						" SELECT 'Diferido con Interés + Meses de Gracia','07',1 FROM (SELECT 1) t".
						" WHERE NOT EXISTS (SELECT code FROM  " . _DB_PREFIX_ . "datafast_termtype ".
						" WHERE code='07')";
            Db::getInstance()->execute($query07);
    
            $query09 = " INSERT INTO " . _DB_PREFIX_ . "datafast_termtype(name,code,active) ".
						" SELECT 'Diferido sin Interés + Meses de Gracia','09',1 FROM (SELECT 1) t".
						" WHERE NOT EXISTS (SELECT code FROM  " . _DB_PREFIX_ . "datafast_termtype ".
						" WHERE code='09')";
            Db::getInstance()->execute($query09);

            $query21 = " INSERT INTO " . _DB_PREFIX_ . "datafast_termtype(name,code,active) ".
						" SELECT 'Diferido Plus','21',1 FROM (SELECT 1) t".
						" WHERE NOT EXISTS (SELECT code FROM  " . _DB_PREFIX_ . "datafast_termtype ".
						" WHERE code='21')";
            Db::getInstance()->execute($query21);

            $query22 = " INSERT INTO " . _DB_PREFIX_ . "datafast_termtype(name,code,active) ".
						" SELECT 'Diferido','22',1 FROM (SELECT 1) t".
						" WHERE NOT EXISTS (SELECT code FROM  " . _DB_PREFIX_ . "datafast_termtype ".
						" WHERE code='22')";
            Db::getInstance()->execute($query22);
			

    }
    public function createTableCustomerToken()
    {
        return Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'datafast_customertoken (
            `id` INTEGER(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
            `customer_id` VARCHAR (100) DEFAULT NULL,
            `token` VARCHAR (200) ,
            `status` VARCHAR (100),
            `updated_at` DATETIME DEFAULT NULL)
            ENGINE = ' . _MYSQL_ENGINE_ . ' '
        );
    }

   

    public function uninstall()
    {
        Configuration::deleteByName('DATAFAST_DEV');
        Configuration::deleteByName('DATAFAST_ENTITY_ID');
        Configuration::deleteByName('DATAFAST_BEARER_TOKEN');
        Configuration::deleteByName('DATAFAST_MID');
        Configuration::deleteByName('DATAFAST_TID');
        Configuration::deleteByName('DATAFAST_RISK');
        Configuration::deleteByName('DATAFAST_PROVEEDOR');
        Configuration::deleteByName('DATAFAST_ECI');
        Configuration::deleteByName('DATAFAST_PREFIJOTRX');
		Configuration::deleteByName('DATAFAST_CUSTOMERTOKEN');
        Configuration::deleteByName('DATAFAST_STYLE');
        Configuration::deleteByName('DATAFAST_CVV');
        Configuration::deleteByName('DATAFAST_PRODULR');
        Configuration::deleteByName('DATAFAST_DEVURL');
        Configuration::deleteByName('DATAFAST_VERSION');


        PrestaShopLogger::addLog('Desinstalando el módulo de pagos de Datafast', 2);

        return parent::uninstall();
    }

    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        $formUpdate = 0;
        if (Tools::isSubmit('submitUpdate')) {
            
           
            Configuration::updateValue('DATAFAST_DEV', Tools::getValue('DATAFAST_DEV'));
            Configuration::updateValue('DATAFAST_ENTITY_ID', Tools::getValue('DATAFAST_ENTITY_ID'));
            Configuration::updateValue('DATAFAST_BEARER_TOKEN', Tools::getValue('DATAFAST_BEARER_TOKEN'));
            Configuration::updateValue('DATAFAST_MID', Tools::getValue('DATAFAST_MID'));
            Configuration::updateValue('DATAFAST_TID', Tools::getValue('DATAFAST_TID'));
            Configuration::updateValue('DATAFAST_RISK', Tools::getValue('DATAFAST_RISK'));
            Configuration::updateValue('DATAFAST_PROVEEDOR', Tools::getValue('DATAFAST_PROVEEDOR'));
            Configuration::updateValue('DATAFAST_ECI', Tools::getValue('DATAFAST_ECI'));
            Configuration::updateValue('DATAFAST_PREFIJOTRX', Tools::getValue('DATAFAST_PREFIJOTRX'));
            Configuration::updateValue('DATAFAST_CUSTOMERTOKEN', Tools::getValue('DATAFAST_CUSTOMERTOKEN'));
            Configuration::updateValue('DATAFAST_STYLE', Tools::getValue('DATAFAST_STYLE'));
            Configuration::updateValue('DATAFAST_CVV', Tools::getValue('DATAFAST_CVV'));
            Configuration::updateValue('DATAFAST_PRODULR', Tools::getValue('DATAFAST_PRODULR'));
            Configuration::updateValue('DATAFAST_DEVURL', Tools::getValue('DATAFAST_DEVURL'));

 

            $conditions = array();
            $languages = Language::getLanguages(false);
            foreach ($languages as $lang) {
                if (Tools::getIsset('NW_CONDITIONS_' . $lang['id_lang'])) {
                    $conditions[$lang['id_lang']] = Tools::getValue('NW_CONDITIONS_' . $lang['id_lang']);
                }
            }

            Configuration::updateValue('NW_CONDITIONS', $conditions, true);
            $voucher = Tools::getValue('NW_VOUCHER_CODE');

            if ($voucher && !Validate::isDiscountName($voucher)) {
                $this->_html .= $this->displayError($this->trans('The voucher code is invalid.', array(), 'Admin.Notifications.Error'));
            } else {
                Configuration::updateValue('NW_VOUCHER_CODE', pSQL($voucher));
                $this->_html .= $this->displayConfirmation($this->trans('Settings updated', array(), 'Admin.Notifications.Success'));
            }

        } 
        else if (Tools::isSubmit('updatedatafast')) {
            $formUpdate=1;
            $this->_html .= $this->renderInstallmentsForm((int) Tools::getValue('id_installment'));
        } 
        else if (Tools::isSubmit('updateInstallments')) {
            $formUpdate=1;
            $this->_html .= $this->renderInstallmentsForm((int) Tools::getValue(0));
        } 
        else if (Tools::isSubmit('deletedatafast')) {
            $formUpdate = 0;
            $installments = new DatafastInstallments((int) Tools::getValue('id_installment'));
            if ($installments->id_installment > 0)
            {
                if ($installments->deleteInstallment()) {
                    $this->_html .= '<div class="conf confirm alert alert-success">Financiamiento eliminado.</div>';
                }
            }
        } 
        elseif (Tools::isSubmit('submitEditInstallments')) { 
  
            $installments = new DatafastInstallments((int) Tools::getValue('id_installment'));

            $installments->name = Tools::getValue('name_1');
            $installments->installments = Tools::getValue('installments');
            $installments->id_termtype = (int) Tools::getValue('id_termtype');
            $installments->active = Tools::getValue('active');
            $installments->deleted =  0;
           
                if ($installments->id_installment > 0)
                {
                    if ($installments->updateInstallment()) {
                        $this->_html .= '<div class="conf confirm alert alert-success">Financiamiento actualizado.</div>';
                    }
                }
                else
                {
                  
                    if ($installments->insertInstallment()) {
                        $this->_html .= '<div class="conf confirm alert alert-success">Financiamiento creado.</div>';
                    }
                }
        }
        elseif (Tools::isSubmit('viewTransactions')) { 
  
            $formUpdate=1;
            $this->_html .= $this->getTransactionsForm();
        }        
        elseif (Tools::isSubmit('viewDetails')) { 
  
            $formUpdate=1;
            $this->_html .= $this->getTransactionsForm();
           }
        elseif (Tools::isSubmit('viewDetailsTransaction')||Tools::isSubmit('refundDatafast')) { 

            $thisYear = (int) Date("Y");
            $years = '';
            for($i = $thisYear; $i >= $thisYear -10 ; $i--){
                $years .= "<option>$i</option>";
            }
            $months = '';
            for($i = 1; $i <= 12 ; $i++){
                $months .= "<option>$i</option>";
            }
            
         
            $messagerefundDatafast= "";
            $id_transaction = (int) Tools::getValue('id_transaction');
            $transactions = $this->getTransactions($id_transaction,0,0);

                $cart_id = $transactions[0]['cart_id'];
                $checkout_id = $transactions[0]['checkout_id'];
                $updated_at = $transactions[0]['updated_at'];
                $customer_id = $transactions[0]['customer_id'];
                $merchantTransactionid = $transactions[0]['merchantTransactionid'];
                $amount = round($transactions[0]['amount'] ?? 0,2);
                $result_code = $transactions[0]['result_code'];
                $result_description = $transactions[0]['result_description'];
                $extended_description = $transactions[0]['extended_description'];
                $auth_code = $transactions[0]['auth_code'];
                $batch_no = $transactions[0]['batch_no'];
                $payment_type = $transactions[0]['payment_type'];
                $reference_no = $transactions[0]['reference_no'];
                $acquirer_code = $transactions[0]['acquirer_code'];
                $interest = round($transactions[0]['interest'] ?? 0,2);
                $total_amount = round($transactions[0]['total_amount'] ?? 0,2);
                $transaction_id = $transactions[0]['transaction_id'];
                $response = $transactions[0]['response'];
                $acquirer_response = $transactions[0]['acquirer_response'];
                $response_json = $transactions[0]['response_json'];
                $status = $transactions[0]['status']; 
                $status_name = $transactions[0]['status_name']; 
            if (Tools::isSubmit('refundDatafast'))
            {
 
                $request = $this->getDatafastRequest(); 
              
                $request->setAmount( $transactions[0]['amount']);
                $request->setTransactionId( $transactions[0]['transaction_id']);
              
                $paymentService = new PaymentService();
              
                $refundResp = $paymentService->requestRefund($request);
                $objResponseRefund =  json_decode($refundResp, true);

                if (!is_array($objResponseRefund)) {
                    $objResponseRefund = [];
                }

                $refund_id= $objResponseRefund['id'] ?? '';
                $refund_referencedId= $objResponseRefund['referencedId'] ?? '';
                $refund_paymentType= $objResponseRefund['paymentType'] ?? '';
                $refund_amount= $objResponseRefund['amount'] ?? '';
                $refund_currency= $objResponseRefund['currency'] ?? '';
                $refund_descriptor= $objResponseRefund['descriptor'] ?? '';
                $refund_merchantTransactionId= $objResponseRefund['merchantTransactionId'] ?? '';
                $refund_resultCode= $objResponseRefund['result']['code'] ?? '';
                $refund_description= $objResponseRefund['result']['description'] ?? '';
                $refund_ExtendedDescription= $objResponseRefund['resultDetails']['ExtendedDescription'] ?? '';
                if ($refund_ExtendedDescription == "")
                {
                    $refund_ExtendedDescription = $refund_description;
                }
                $refund_clearingInstituteName= $objResponseRefund['resultDetails']['clearingInstituteName'] ?? '';
                $refund_ConnectorTxID1= $objResponseRefund['resultDetails']['ConnectorTxID1'] ?? '';
                $refund_ReferenceNbr = $objResponseRefund['resultDetails']['ReferenceNo'] ?? '';
                $refund_BatchNo = $objResponseRefund['resultDetails']['BatchNo'] ?? '';
                $refund_response = $objResponseRefund['resultDetails']['Response'] ?? '';
                $refund_authcode = $objResponseRefund['resultDetails']['AuthCode'] ?? '';
                $refund_acquirercode = $objResponseRefund['resultDetails']['AcquirerCode'] ?? '';
                $refund_totalamount = $objResponseRefund['resultDetails']['TotalAmount'] ?? '';
                $refund_interest = $objResponseRefund['resultDetails']['Interest'] ?? '';
                $refund_timestamp= $objResponseRefund['timestamp'] ?? '';
                $refund_status = "1";
                $refund_resp= $refundResp;


                $accepted = $this->validateTransactionRefund($refund_resultCode);

                if ($accepted==1)
                {

                        $query = 'UPDATE ' . _DB_PREFIX_ . 'datafast_transactions
                                    SET     status      =    2
                                    WHERE   id          =   ' . (int)$id_transaction.'
                                    AND     status       =   1';
                        Db::getInstance()->execute($query);

                        $messagerefundDatafast = '<div class="conf confirm alert alert-success">Registro reversado exitosamente.</div>';
                        $status  = "2";
                }
                else
                {
                        $messagerefundDatafast = '<div class="conf confirm alert alert-danger">Registro no reversado. Mensaje del banco: '.htmlspecialchars($refund_ExtendedDescription, ENT_QUOTES, 'UTF-8').'.</div>';
                }


                                $query = 'INSERT INTO ' . _DB_PREFIX_ . 'datafast_transactions
                                (`cart_id`, 
                                `customer_id`, 
                                `transaction_id`, 
                                `payment_type`,
                                `amount`,
                                `merchant_transactionId`,
                                `result_code`,
                                `result_description`,
                                `extended_description`,
                                `reference_no`,
                                `batch_no`,
                                `response`,
                                `auth_code`,
                                `acquirer_code`,
                                `total_amount`,
                                `interest`,
                                `timestamp`,
                                `response_json`,
                                `status`,
                                `updated_at`)
                                    VALUES (
                                    ' . (int)$cart_id . ',
                                    \'' . pSQL($customer_id) . '\',
                                    \'' . pSQL($refund_id) . '\',
                                    \'' . pSQL($refund_paymentType) . '\',
                                    \'' . pSQL($refund_amount) . '\',
                                    \'' . pSQL($refund_merchantTransactionId) . '\',
                                    \'' . pSQL($refund_resultCode) . '\',
                                    \'' . pSQL($refund_description) . '\',
                                    \'' . pSQL($refund_ExtendedDescription) . '\',
                                    \'' . pSQL($refund_ReferenceNbr) . '\',
                                    \'' . pSQL($refund_BatchNo) . '\',
                                    \'' . pSQL($refund_response) . '\',
                                    \'' . pSQL($refund_authcode) . '\',
                                    \'' . pSQL($refund_acquirercode) . '\',
                                    \'' . pSQL($refund_totalamount) . '\',
                                    \'' . pSQL($refund_interest) . '\',
                                    \'' . pSQL($refund_timestamp) . '\',
                                    \'' . pSQL($refund_resp) . '\',
                                    \'' . pSQL($refund_status) . '\',
                                    \'' . date('Y-m-d H:i:s') . '\'
                            )';
  
                      
                Db::getInstance()->execute($query);
               
            } 

            $formUpdate=1;
 
            $href_return = AdminController::$currentIndex . '&configure=' . $this->name.'&viewTransactions&token=' .Tools::getAdminTokenLite('AdminModules');
            $href_refund = AdminController::$currentIndex . '&configure=' . $this->name.'&refundDatafast&token=' .Tools::getAdminTokenLite('AdminModules'). '&id_transaction=' .$id_transaction;
 
            $this->smarty->assign(array(
                'cart_id' => $cart_id,
                'checkout_id' => $checkout_id,
                'updated_at' => $updated_at,
                'merchantTransactionid' => $merchantTransactionid,
                'amount' => $amount,
                'result_code' =>$result_code,
                'result_description' => $result_description,
                'extended_description' => $extended_description,
                'auth_code' => $auth_code,
                'batch_no' => $batch_no,
                'reference_no' => $reference_no,
                'acquirer_code' => $acquirer_code,
                'interest' => $interest,
                'total_amount' => $total_amount,
                'transaction_id' => $transaction_id,
                'payment_type' => $payment_type,
                'response' => $response,
                'acquirer_response' => $acquirer_response,
                'response_json' => $response_json,
                'href_return' => $href_return,
                'href_refund' => $href_refund,
                'status' => $status,
                'messagerefundDatafast' =>$messagerefundDatafast,
                'allYearOptions' => $years,
                'allMonthOptions' => $months 
            )); 
            return $this->display(__FILE__, 'views/templates/admin/transactionsDetail.tpl');

        }         
        else if (((bool)Tools::isSubmit('submitDatafastPaymentModule')) == true) {
            $this->postProcess();
            
        }
        elseif (Tools::isSubmit('info_testing')) {  
            $href_return = AdminController::$currentIndex . '&configure=' . $this->name.'&token=' .Tools::getAdminTokenLite('AdminModules');
            $IPServidor = gethostbyname($this->urlServer());
            $IPCliente =$this->get_client_ip();
            $this->smarty->assign(array(
                'IPServidor' => $IPServidor,
                'IPCliente' => $IPCliente,
                'base_url' => $this->context->shop->getBaseURL(true),
                'href_return' => $href_return
            )); 
            return $this->display(__FILE__, 'views/templates/admin/info_testing.tpl'); 
        }  
        elseif (Tools::isSubmit('recoverTransactions')) {  
            $href_return = AdminController::$currentIndex . '&configure=' . $this->name.'&token=' .Tools::getAdminTokenLite('AdminModules');
            $IPServidor = gethostbyname($this->urlServer());
            $IPCliente =$this->get_client_ip();
            $duplicates = [];
            $errorsTrxs = [];
            $success = [];
            if(isset($_POST['trxs']))
                $trxs = explode(';',$_POST['trxs']);
            if(isset($trxs)){
                foreach ($trxs as $key => $value) {  
                    $table_name = _DB_PREFIX_  . 'datafast_transactions';  
                    $sqlCount = '
                    SELECT COUNT(id)    AS Count
                    FROM '.$table_name.'
                    WHERE transaction_id = \''.pSQL($value).'\'';
                    $count = Db::getInstance()->executeS($sqlCount);  
                        $count = $count[0]['Count']; 
                    if ($count > 0)
                        $duplicates[] = $value;
                    else{
                        $response = $this->searchTransactionByPaymentId($value); 
                        if(!isset($response['id']))
                            $errorsTrxs[] = $value;
                        else{
                            $success[] = $value; 
                            $query = 'INSERT INTO ' . _DB_PREFIX_ . 'datafast_transactions
                                                 ( 
                                                 `customer_id`, 
                                                 `transaction_id`,  
                                                 `payment_type`,
                                                 `payment_brand`,
                                                 `amount`,
                                                 `merchant_transactionId`,
                                                 `result_code`,
                                                 `result_description`,
                                                 `extended_description`,
                                                 `acquirer_response`,
                                                 `response`,
                                                 `auth_code`,
                                                 `acquirer_code`,
                                                 `batch_no`,
                                                 `interest`,
                                                 `total_amount`,
                                                 `reference_no`,
                                                 `bin`,
                                                 `last_4_Digits`,
                                                 `email`,
                                                 `shopper_mid`,
                                                 `shopper_tid`,
                                                 `timestamp`, 
                                                 `response_json`,
                                                 `status`,
                                                 `updated_at` )
                                                     VALUES (
                                                        \'' . pSQL($response['customer']['merchantCustomerId'] ?? '') . '\',
                                                        \'' . pSQL($response['id'] ?? '') . '\',
                                                        \'' . pSQL($response['paymentType'] ?? '') . '\',
                                                        \'' . pSQL($response['paymentBrand'] ?? '') . '\',
                                                        \'' . pSQL(str_replace(',', '', $response['amount'] ?? '')) . '\',
                                                        \'' . pSQL($response['merchantTransactionId'] ?? '') . '\',
                                                        \'' . pSQL($response['result']['code'] ?? '') . '\',
                                                        \'' . pSQL($response['result']['description'] ?? '') . '\',
                                                        \'' . pSQL($response['resultDetails']['ExtendedDescription'] ?? '') . '\',
                                                        \'' . pSQL($response['resultDetails']['AcquirerResponse'] ?? '') . '\',
                                                        \'' . pSQL($response['resultDetails']['response'] ?? '') . '\',
                                                        \'' . pSQL($response['resultDetails']['AuthCode'] ?? '') . '\',
                                                        \'' . pSQL($response['resultDetails']['AcquirerCode'] ?? '') . '\',
                                                        \'' . pSQL($response['resultDetails']['BatchNo'] ?? '') . '\',
                                                        \'' . pSQL(str_replace(',', '', $response['resultDetails']['Interest'] ?? '')) . '\',
                                                        \'' . pSQL(str_replace(',', '', $response['resultDetails']['TotalAmount'] ?? '')) . '\',
                                                        \'' . pSQL($response['resultDetails']['ReferenceNo'] ?? '') . '\',
                                                        \'' . pSQL($response['card']['bin'] ?? '') . '\',
                                                        \'' . pSQL($response['card']['last4Digits'] ?? '') . '\',
                                                        \'' . pSQL($response['customer']['email'] ?? '') . '\',
                                                        \'' . pSQL($response['customParameters']['SHOPPER_MID'] ?? '') . '\',
                                                        \'' . pSQL($response['customParameters']['SHOPPER_TID'] ?? '') . '\',
                                                        \'' . pSQL($response['timestamp'] ?? '') . '\',
                                                        \'' . pSQL(json_encode($response, true)) . '\',
                                                        \'' . ((isset($response['result']['code']) &&
                                                        ($response['result']['code'] == "000.000.000" ||
                                                        $response['result']['code'] == "000.200.100" ||
                                                        $response['result']['code'] == "000.100.112" ||
                                                        $response['result']['code'] == "000.100.110") ? 1 : 0)) . '\',
                                                        \'' . date('Y-m-d H:i:s') . '\'
                                             )';
                            Db::getInstance()->execute($query);
                        }
                    }
                }  
            }
            $this->smarty->assign(array(
                'IPServidor' => $IPServidor,
                'IPCliente' => $IPCliente, 
                'href_return' => $href_return,
                'errorsTrxs' => $errorsTrxs,
                'success' => $success,
                'duplicates' => $duplicates
            )); 
            return $this->display(__FILE__, 'views/templates/admin/recoverTransactions.tpl'); 
        }   

        if ($formUpdate<>"1")
        {        
            $this->context->smarty->assign('module_dir', $this->_path);
            $this->context->smarty->assign('web_url', $this->context->link->getModuleLink($this->name, 'status', array(), true));
            $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');
            
            $this->_html .= $this->getTransactionsPreview();
            $this->_html .= $this->getConfigForm();
            $this->_html .= $this->getConfigFormInstallments();
            $this->_html .= $this->getInfoBannerPreview();
            $this->_html .= $this->getRecoverTransactionsBannerPreview();
            
		    
        }
        return $this->_html;
    }

    private function urlServer(): string
    {
        $url = str_replace(array('https://','http://'),'', Tools::getShopDomainSsl(true) ); 
        return $url;
    } 
    private function get_client_ip(): string
    {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if(isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    } 

    private function validateTransactionRefund(string $resultCode): bool
    {
		
        $testMode = Configuration::get('DATAFAST_DEV', null);
        $validTransaction = false;

        if (in_array($resultCode, Constants::TRANSACTION_APPROVED_TEST) && $testMode) {
            $validTransaction = true;
        }
        if ($resultCode == Constants::TRANSACTION_APPROVED_PROD && !$testMode) {
            $validTransaction = true;
        }
        return $validTransaction;
    }


    public function renderInstallmentsForm($id_installment = 0)
    {
        $types = datafastInstallments::getTermTypes();
        $query = [];
        foreach ($types as $key => $value) {
            $query[] = [
                    'idTermType' => $key,
                    'nameTermType' => $value,
                ];
        } 
        
        echo "<script type='text/javascript'>
                window.addEventListener('load', (event) => {
                    console.log('Running...');

                    /*  Asignar atributo type = number para el input Meses */
                    jQuery('#installments').attr('type', 'number');

                    /*  Bloquear Meses si al iniciar pagina Tipo de Credito es Corriente */
                    var type_credit = $('#id_termtype').val();
                    if(type_credit == '1'){
                        jQuery('#installments').attr('readonly', true)
                    }  
                    
                    /* Si se modifica no se manda Meses a 0, en caso de que sea vaya a crear Meses sera 0  */
                    if(jQuery('#installments').val().length==0){
                        jQuery('#installments').val(0);
                    }

                    /* Si Tipo de Credito es Corriente se bloquea los Meses y los Meses se setea 0  */
                    jQuery('#id_termtype').on('change', function() {
                        var campo = document.getElementById('installments');
                        if(this.value=='1'){
                            jQuery('#installments').attr('readonly', true)
                            jQuery('#installments').val(0);
                            campo.style.border = '1px solid #bbcdd2';
                        }
                        else {
                            jQuery('#installments').attr('readonly', false)
                        }
                    })

                    /*  Validación (borde y no dejar guardar) campo Meses si esta vacio */
                    $('document').ready(function() {
                        $('#datafast_form_submit_btn').click(function(e){  
                            var campoEtiqueta = document.getElementById('name_1');  
                            if ($('#name_1').val().length == 0) {
                                campoEtiqueta.style.border = '1px solid red';
                            return false;
                            }
                        });                  
                    });

                    /*  Validación (borde y no dejar guardar) campo Etiqueta si esta vacio */
                    $('document').ready(function() {
                        $('#datafast_form_submit_btn').click(function(e){  
                            var campoMeses = document.getElementById('installments');                        
                            if ($('#installments').val().length == 0) {
                                campoMeses.style.border = '1px solid red';
                                return false;
                            }
                        });                  
                    });

                    /*  Eventos Keyup para Etiqueta, sacar borde cuando escriba una letra en el input */
                    document.getElementById('name_1').addEventListener('keyup', myFunction);
                    function myFunction() {
                        var campo = document.getElementById('name_1');
                    if (campo.value != '') {
                        campo.style.border = '1px solid #bbcdd2';
                        }
                    }
                    /*  Eventos Keyup para Meses, sacar borde cuando escriba una letra en el input */
                    document.getElementById('installments').addEventListener('keyup', myFunction1);
                        function myFunction1() {
                            var campo = document.getElementById('installments');
                        if (campo.value != '') {                        
                            campo.style.border = '1px solid #bbcdd2';
                        }
                    }
                    /*  Sacar el borde cuando ingrese un numero por el contador  */
                    jQuery('#installments').on('change', function() {
                        var campo = document.getElementById('installments');
                        if(this.value!=''){
                            campo.style.border = '1px solid #bbcdd2';
                        }
                    })
            });
            </script>"; 

        $fields_form_1 = [
            'form' => [
                'legend' => [
                    'title' => 'TIPOS DE CRÉDITO HABILITADOS',
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'hidden',
                        'name' => 'id_installment',
                    ],
                    [
                        'type' => 'text', 
                        'label' => 'Etiqueta',
                        'name' => 'name_1',
                        'desc' => 'Nombre de etiqueta del tipo de crédito',
                        'class' => 'form-control'
                    ],
                    [
                        'type' => 'text', 
                        'label' => 'Meses de Crédito',
                        'name' => 'installments', 
                        'desc' => 'Ingrese los meses de crédito que se habilitará',
                        'class' => 'form-control'

                    ],
                    [
                        'type' => 'select',
                        'name' => 'id_termtype',
                        'label' => 'Tipo de Crédito',
                        'options' => [
                                        'query' => $query,
                                        'id' => 'idTermType',
                                        'name' => 'nameTermType',
                                    ],
                    ],
                    [
                        'type' => 'switch',
                        'is_bool' => true, //retro compat 1.5
                        'label' => 'Activo',
                        'name' => 'active',
                        'values' => [
                                        [
                                            'id' => 'active_on',
                                            'value' => 1,
                                            'label' => 'Si',
                                        ],
                                        [
                                            'id' => 'active_off',
                                            'value' => 0,
                                            'label' => 'No',
                                        ],
                                    ],
                    ],
                ],
            'submit' => [
                'title' => 'Guardar',
                'class' => 'btn btn-default pull-right',
                'name' => 'submitEditInstallments',
                ],
            'buttons' => array(
                array(
                    'href' => $this->context->link->getAdminLink('AdminModules', false, [], ['configure' => $this->name, 'tab_module' => $this->tab, 'module_name' => $this->name]) . '&token=' .Tools::getAdminTokenLite('AdminModules'),
                    'title' => $this->l('Regresar'),
                    'icon' => 'process-icon-back'
                ))
            ]
        ];

        
        $installments = new datafastInstallments((int) $id_installment);

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->name;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->module = $this;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitEditInstallments';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false, [], ['configure' => $this->name, 'tab_module' => $this->tab, 'module_name' => $this->name]);
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        
        
      
        $helper->tpl_vars = [
            'fields_value' => $this->getInstallmentFieldsValues($id_installment),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];
        return $helper->generateForm([$fields_form_1]);
    }

    public function getInstallmentFieldsValues($id_installment = 0)
    {
        
        $installment = new DatafastInstallments($id_installment);
        return [
                    'id_installment' => $installment->id_installment,
                    'id_termtype' => $installment->id_termtype,
                    'installments' => $installment->installments,
                    'name_1' => $installment->name,
                    'installments_1' => $installment->installments,
                    'active' => $installment->active
                ];
    }

 

    /**
     * Save form data.
     */
    protected function postProcess()
    {

        $logger = $this->getLogger();

        $logger->info('Configuración del módulo de Datafast cambiado!');

        PrestaShopLogger::addLog('Parámetros del módulo de Datafast modificados', 2);
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
     * @return Logger
     */
    private function getLogger(): Logger
    {
        $logger = new Logger('Configuration');
        $logger->pushHandler(new StreamHandler(Constants::LOGGER_FILE, Logger::DEBUG));
        return $logger;
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'DATAFAST_DEV' => Configuration::get('DATAFAST_DEV', true),
            'DATAFAST_ENTITY_ID' => Configuration::get('DATAFAST_ENTITY_ID', null),
            'DATAFAST_BEARER_TOKEN' => Configuration::get('DATAFAST_BEARER_TOKEN', null),
            'DATAFAST_MID' => Configuration::get('DATAFAST_MID', null),
            'DATAFAST_TID' => Configuration::get('DATAFAST_TID', null),
            'DATAFAST_RISK' => Configuration::get('DATAFAST_RISK', null),
            'DATAFAST_PROVEEDOR' => Configuration::get('DATAFAST_PROVEEDOR', null),
            'DATAFAST_ECI' => Configuration::get('DATAFAST_ECI', null),
            'DATAFAST_PREFIJOTRX' => Configuration::get('DATAFAST_PREFIJOTRX', null),
			'DATAFAST_CUSTOMERTOKEN' => Configuration::get('DATAFAST_CUSTOMERTOKEN', null),
            'DATAFAST_STYLE' => Configuration::get('DATAFAST_STYLE', null),
            'DATAFAST_CVV' => Configuration::get('DATAFAST_CVV', null),
            'DATAFAST_PRODULR' => Configuration::get('DATAFAST_PRODULR', null),
            'DATAFAST_DEVURL' => Configuration::get('DATAFAST_DEVURL', null),
            'DATAFAST_VERSION' => $this->version
        );
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitDatafastPaymentModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        if (Currency::getDefaultCurrency()->iso_code == 'USD') {
            return $helper->generateForm(array($this->getConfigForm(),$this->getConfigFormInstallments(),$this->getTransactionsForm()));
        } else {
            exit;
        }

    }

    /**
     * Create the structure of your form USD.
     */
    protected function getConfigForm()
    {
         $fields_form =  array(
            'form' => array(
                'legend' => array(
									'title' => $this->trans('Configuracion', array(), 'Admin.Global'), 
									'icon' => 'icon-cogs',
								 ),
				'input' => array(
                                    array(
                                        'col' => 3,
                                        'type' => 'text',
                                        'desc' => $this->l('Versión del Plugin'),
                                        'name' => 'DATAFAST_VERSION',
                                        'readonly' => 'readonly',
                                        'label' => $this->l('Versión'),
                                    ),
									array(
										'type' => 'switch',
										'label' => $this->l('Ambiente de Pruebas'),
										'name' => 'DATAFAST_DEV',
										'is_bool' => true,
										'desc' => $this->l('Usar el módulo en ambiente de pruebas'),
										'values' => array(
											array(
												'id' => 'active_on',
												'value' => true,
												'label' => $this->l('Enabled'),
											),
											array(
												'id' => 'active_off',
												'value' => false,
												'label' => $this->l('Disabled')
											)
										),
									),
									array(
										'col' => 3,
										'type' => 'text',
										'desc' => $this->l('Ingrese el valor de Entity ID'),
										'name' => 'DATAFAST_ENTITY_ID',
										'label' => $this->l('Entity ID'),
									),
									array(
										'col' => 3,
										'type' => 'text',
										'desc' => $this->l('Ingrese el valor de Authorization Bearer'),
										'name' => 'DATAFAST_BEARER_TOKEN',
										'label' => $this->l('Authorization'),
									),
									array(
										'col' => 3,
										'type' => 'text',
										'desc' => $this->l('Ingrese el valor de MID'),
										'name' => 'DATAFAST_MID',
										'label' => $this->l('MID'),
									),
									array(
										'col' => 3,
										'type' => 'text',
										'desc' => $this->l('Ingrese el valor de TID'),
										'name' => 'DATAFAST_TID',
										'label' => $this->l('TID'),
									),
									array(
										'col' => 3,
										'type' => 'text',
										'desc' => $this->l('Ingrese el valor de RISK'),
										'name' => 'DATAFAST_RISK',
										'label' => $this->l('RISK'),
									)
									,
									array(
										'col' => 3,
										'type' => 'text',
										'desc' => $this->l('Ingrese el valor de PROVEEDOR (SHOPPER_PSERV)'),
										'name' => 'DATAFAST_PROVEEDOR',
										'label' => $this->l('PROVEEDOR'),
									)
									,
									array(
										'col' => 3,
										'type' => 'text',
										'desc' => $this->l('Ingrese el valor de ECI (SHOPPER_ECI)'),
										'name' => 'DATAFAST_ECI',
										'label' => $this->l('ECI'),
									)
									,
									array(
										'col' => 3,
										'type' => 'text',
										'desc' => $this->l('Ingrese el prefijo de las transacciones'),
										'name' => 'DATAFAST_PREFIJOTRX',
										'label' => $this->l('PREFIJO TRX'),
									)
									 ,
									 array(
										'type' => 'switch',
										'label' => $this->l('Tokeniza tarjetas'),
										'name' => 'DATAFAST_CUSTOMERTOKEN',
										'is_bool' => true,
										'desc' => $this->l('Tokenizar las tarjetas de los clientes'),
										'values' => array(
											array(
												'id' => 'active_on',
												'value' => true,
												'label' => $this->l('Enabled'),
											),
											array(
												'id' => 'active_off',
												'value' => false,
												'label' => $this->l('Disabled')
											)
										)),
                                        array(
                                           'type' => 'switch',
                                           'label' => $this->l('Diseño Default'),
                                           'name' => 'DATAFAST_STYLE',
                                           'is_bool' => true,
                                           'desc' => $this->l('Selecciona el diseño default o plain del botón'),
                                           'values' => array(
                                               array(
                                                   'id' => 'active_on',
                                                   'value' => true,
                                                   'label' => $this->l('Enabled'),
                                               ),
                                               array(
                                                   'id' => 'active_off',
                                                   'value' => false,
                                                   'label' => $this->l('Disabled')
                                               )
                                            )), 
                                        array(
                                           'type' => 'switch',
                                           'label' => $this->l('Requiere CVV'),
                                           'name' => 'DATAFAST_CVV',
                                           'is_bool' => true,
                                           'desc' => $this->l('Selecciona si requiere CVV'),
                                           'values' => array(
                                               array(
                                                   'id' => 'active_on',
                                                   'value' => true,
                                                   'label' => $this->l('Enabled'),
                                               ),
                                               array(
                                                   'id' => 'active_off',
                                                   'value' => false,
                                                   'label' => $this->l('Disabled')
                                               )
                                               )),
                                        array(
                                            'type' => 'select', 
                                            'lang' => true,
                                            'label' => $this->l('Url de Desarrollo'),
                                            'name' => 'DATAFAST_DEVURL', 
                                            'desc' =>  $this->l('Url de Desarrollo'),
                                            'options' => array(
                                                'query' => array( 
                                                    array( 
                                                      'id_option' => 2, 
                                                      'name' => Environment::TEST2
                                                    ), 
                                                    array( 
                                                      'id_option' => 1,  
                                                      'name' =>  Environment::TEST 
                                                    ), 
                                                  ), 
                                                'id' => 'id_option',  
                                                'name' => 'name' 
                                            ),  
                                        ),
                                        array(
                                            'type' => 'select', 
                                            'lang' => true,
                                            'label' => $this->l('Url de Producción'),
                                            'name' => 'DATAFAST_PRODULR', 
                                            'desc' =>  $this->l('Url de Producción'),
                                            'options' => array(
                                                'query' => array( 
                                                    array( 
                                                      'id_option' => 2, 
                                                      'name' => Environment::PRODUCTION2
                                                    ), 
                                                    array( 
                                                      'id_option' => 1,  
                                                      'name' =>  Environment::PRODUCTION 
                                                    ), 
                                                  ), 
                                                'id' => 'id_option',  
                                                'name' => 'name' 
                                            ),  
                                        )
									
								 
							),
				 
                            'submit' => array(
                                'title' => $this->trans('Save', array(), 'Admin.Actions'),
                                'class' => 'btn btn-default pull-right',
					),
            ), 
        );
		
		$helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;

        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitUpdate';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

	  return $helper->generateForm(array($fields_form));
    }

    protected function getTransactionsPreview()
    {
        
        $this->smarty->assign(array(
            'href' => $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&viewTransactions&token=' .Tools::getAdminTokenLite('AdminModules'),
            'action' => $this->trans('View', array(), 'Admin.Actions'),
            'disable' => false,
        ));


        return $this->display(__FILE__, 'views/templates/admin/transactions.tpl');
    }
    protected function getInfoBannerPreview()
    {

        $this->smarty->assign(array(
            'href' => $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&info_testing&token=' .Tools::getAdminTokenLite('AdminModules'),
            'action' => $this->trans('View', array(), 'Admin.Actions'),
            'disable' => false,
        ));
  

        return $this->display(__FILE__, 'views/templates/admin/infoBanner.tpl');
    }
    protected function getRecoverTransactionsBannerPreview()
    {
        
        $this->smarty->assign(array(
            'href' => $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&recoverTransactions&token=' .Tools::getAdminTokenLite('AdminModules'),
            'action' => $this->trans('View', array(), 'Admin.Actions'),
            'disable' => false,
        ));


        return $this->display(__FILE__, 'views/templates/admin/recoverTransactionsBanner.tpl');
    }
    

	 public function getConfigFieldsValues()
    {
        $conditions = array();
        $languages = Language::getLanguages(false);
        foreach ($languages as $lang) {
            $conditions[$lang['id_lang']] = Tools::getValue(
                'NW_CONDITIONS_' . $lang['id_lang'],
                Configuration::get('NW_CONDITIONS', $lang['id_lang']
                )
            );
        }

        return array(
            'NW_VERIFICATION_EMAIL' => Tools::getValue('NW_VERIFICATION_EMAIL', Configuration::get('NW_VERIFICATION_EMAIL')),
            'NW_CONFIRMATION_EMAIL' => Tools::getValue('NW_CONFIRMATION_EMAIL', Configuration::get('NW_CONFIRMATION_EMAIL')),
            'NW_VOUCHER_CODE' => Tools::getValue('NW_VOUCHER_CODE', Configuration::get('NW_VOUCHER_CODE')),
            'NW_CONDITIONS' => $conditions,
            'COUNTRY' => Tools::getValue('COUNTRY'),
            'SUSCRIBERS' => Tools::getValue('SUSCRIBERS'),
            'OPTIN' => Tools::getValue('OPTIN'),
            'action' => 'customers',
        );
    }
    public function displayViewCustomerLink($id, $token = null, $name = null)
    {
        $this->smarty->assign(array(
            'href' => 'index.php?controller=AdminCustomers&id_customer=' . (int) $id . '&updatecustomer&token=' . Tools::getAdminTokenLite('AdminModules'),
            'action' => $this->trans('View', array(), 'Admin.Actions'),
            'disable' => !((int) $id > 0),
        ));
  

        return $this->display(__FILE__, 'views/templates/admin/list_action_viewtype.tpl');
    }

	 protected function getConfigFormInstallments()
    {
        /* Retrieve list data */
        $subscribers = $this->getInstallments();
        $helper_list = new HelperList();
        $helper_list->listTotal = count($subscribers);

        $fields_list = array(
            'id_installment' => array(
                'title' => $this->trans('ID', array(), 'Admin.Global'),
                'name' => 'id_installment',
                'search' => false,
            ),
            'name' => array(
                'title' => $this->trans('Etiqueta', array(), 'Admin.Global'),
                'name' => 'name',
                'search' => false,
            ),
            'installments' => array(
                'title' => $this->trans('Meses', array(), 'Admin.Global'),
                'name' => 'installments',
                'search' => false,
            ),
            'id_termtype' => array(
                'title' => $this->trans('Tipo de Crédito', array(), 'Admin.Global'),
                'name' => 'id_termtype',
                'search' => false,
            )
        );

        if (!Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE')) {
            unset($fields_list['shop_name']);
        }

        
        $helper_list->shopLinkType = '';
        $helper_list->simple_header = false;
        $helper_list->actions = ['edit', 'delete'];
        $helper_list->show_toolbar = true;
        $helper_list->toolbar_btn['new'] = [
            'href' => $this->context->link->getAdminLink('AdminModules', true
                                                        ,[]
                                                        ,[
                                                            'configure' => $this->name
                                                            , 'module_name' => $this->name
                                                            , 'updateInstallments' => ''
                                                        ]),
            'desc' => "Añadir nuevo tipo de crédito",
        ];
        $helper_list->module = $this;
        $helper_list->identifier = 'id_installment';

        $helper_list->title = 'Tipos de Crédito Habilitados';
        $helper_list->table = $this->name ;
        $helper_list->token = Tools::getAdminTokenLite('AdminModules');
        $helper_list->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
 
        return $helper_list->generateList($subscribers, $fields_list);
    }


    protected function getTransactionsForm()
    {
       
        $thisYear = (int) Date("Y");
        $thisMonth = (int) Date("m");

        $txtOrden = '';
        $txtIdTrx = '';
        $action = Tools::getValue('subaction');
        if ($action =="viewData")
        {
            $yearSearch = Tools::getValue('year');
            $monthSearch = Tools::getValue('month');
            $txtOrden = Tools::getValue('txtOrden');
            $txtIdTrx = Tools::getValue('txtIdTrx');
        }
        else
        {
            $yearSearch = $thisYear;
            $monthSearch = $thisMonth;
        }
        

        $years = '<option value="0">-Todos-</option>';
        for($i = $thisYear; $i >= $thisYear -3 ; $i--){
            $selectedYear ="";
            if  ($yearSearch == $i)
            {
                $selectedYear ="selected";
            }

            $years .= "<option ".$selectedYear ."  value='".$i."'>".$i."</option>";
        }
        $months = '<option value="0">-Todos-</option>';
        for($i = 1; $i <= 12 ; $i++){
            $selectedMonth ="";
            if  ($monthSearch == $i)
            {
                $selectedMonth ="selected";
            }
            $months .= "<option ".$selectedMonth ."  value='".$i."'>".$i."</option>";
        }
        
        
     

        $transactions = $this->getTransactions(0,$yearSearch,$monthSearch,$txtOrden,$txtIdTrx);
        $idxTrx=0;
        $data = [];

        if ($transactions) {
            foreach ($transactions as $row) {
                $data[$idxTrx]['id_transaction'] =$row['id_transaction'];     
                $data[$idxTrx]['cart_id'] =$row['cart_id'];     
                $data[$idxTrx]['updated_at'] =$row['updated_at'];     
                $data[$idxTrx]['checkout_id'] =$row['checkout_id'];   
                $data[$idxTrx]['payment_type'] =$row['payment_type'];   
                $data[$idxTrx]['amount'] =$row['amount'];     
                $data[$idxTrx]['result_code'] =$row['result_code'];     
                $data[$idxTrx]['result_description'] =$row['result_description'];     
                $data[$idxTrx]['extended_description'] =$row['extended_description'];     
                $data[$idxTrx]['auth_code'] =$row['auth_code'];     
                $data[$idxTrx]['batch_no'] =$row['batch_no'];     
                $data[$idxTrx]['reference_no'] =$row['reference_no'];     
                $data[$idxTrx]['interest'] =$row['interest'];     
                $data[$idxTrx]['total_amount'] =$row['total_amount'];     
                $data[$idxTrx]['payment_type'] =$row['payment_type'];     
                $data[$idxTrx]['transaction_id'] =$row['transaction_id'];     
                $data[$idxTrx]['response'] =$row['response'];     
                $data[$idxTrx]['status'] =$row['status'];     
                $data[$idxTrx]['status_name'] =$row['status_name'];   
                $data[$idxTrx]['href_detail']  = AdminController::$currentIndex . '&configure=' . $this->name.'&viewDetailsTransaction&token=' .Tools::getAdminTokenLite('AdminModules')."&id_transaction=".$row['id_transaction'];    
                $idxTrx++;
            }
        } 

        $href_return = AdminController::$currentIndex . '&configure=' . $this->name.'&token=' .Tools::getAdminTokenLite('AdminModules');
        $href_detail = AdminController::$currentIndex . '&configure=' . $this->name.'&viewDetailsTransaction&token=' .Tools::getAdminTokenLite('AdminModules');
        $this->context->smarty->assign(
            array(
                'allYearOptions' => $years,
                'allMonthOptions' => $months,
                'txtOrden' => $txtOrden,
                'txtIdTrx' => $txtIdTrx,
                'href_return' => $href_return,
                'href_detail' => $href_detail,
                'data' => $data
               
            )
        );

        return $this->display(__FILE__, 'views/templates/admin/transactionsGrid.tpl');
    }

 

    public function hookPaymentReturn($params)
    {
		
        if ($this->active == false) {
            return;
        }

        $order = $params['order'];

        if ($order->getCurrentOrderState()->id != Configuration::get('PS_OS_ERROR')) {


            $datafastBrand = Context::getContext()->cookie->datafastBrand;
            $datafastAmount = Context::getContext()->cookie->datafastAmount;
            $datafastAuth = Context::getContext()->cookie->datafastAuth;
            $datafastCardHolder = Context::getContext()->cookie->datafastCardHolder;
			$datafastExtendedDescripcion = Context::getContext()->cookie->datafastExtendedDescripcion;

            $status_map = array(
                $this->getConfig('PS_OS_PAYMENT') => 'ok',
                $this->getConfig('PS_OS_OUTOFSTOCK') => 'ok',
                $this->getConfig('PS_OS_OUTOFSTOCK_PAID') => 'ok',
                $this->getConfig('PS_OS_CANCELED') => 'cancel',
            );

            $status = isset($status_map[$order->getCurrentState()]) ? $status_map[$order->getCurrentState()] : 'error';

 
				$this->context->smarty->assign(array(
                'this_path' => $this->getPath(),
                'status' => $status,
                'shop_name' => $this->context->shop->name,
                'datafastBrand' => $datafastBrand,
                'datafastAmount' => $datafastAmount,
                'datafastAuth' => $datafastAuth,
                'datafastCardHolder' => $datafastCardHolder,
				'datafastExtendedDescripcion' => $datafastExtendedDescripcion
            ));

        }

        return $this->fetch('module:datafast/views/templates/hook/confirmation.tpl');
    }

   
    public function getInstallments()
    {
        $dbquery = new DbQuery();
        $dbquery->select('i.id_installment AS id_installment
                        , i.name AS name
                        , i.installments AS installments
                        , t.name AS id_termtype');
        $dbquery->from( 'datafast_installments', 'i');
        $dbquery->leftJoin('datafast_termtype', 't', 'i.id_termtype = t.id');
        $dbquery->where("i.deleted  = 0 ");

        $customers = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($dbquery->build());
  
        return $customers;
    }

    public function getTransactions($id_transaction = 0 ,$year = 0, $month = 0,$orden = '',$id_trx = '')
    {
        $dbquery = new DbQuery();
        $dbquery->select('trx.id                        AS id_transaction
                        , trx.cart_id                   AS cart_id
                        , trx.customer_id               AS customer_id
                        , trx.updated_at                AS updated_at
                        , trx.checkout_id               AS checkout_id
                        , trx.merchant_Transactionid    AS merchantTransactionid
                        , trx.amount                    AS amount
                        , trx.result_code               AS result_code
                        , trx.result_description        AS result_description
                        , trx.extended_description      AS extended_description
                        , trx.auth_code                 AS auth_code
                        , trx.batch_no                  AS batch_no
                        , trx.reference_no              AS reference_no
                        , trx.acquirer_code             AS acquirer_code
                        , trx.interest                  AS interest
                        , trx.total_amount              AS total_amount
                        , trx.transaction_id            AS transaction_id
                        , trx.payment_type              AS payment_type
                        , trx.response                  AS response
                        , trx.acquirer_response         AS acquirer_response
                        , trx.status                    AS status
                        , CASE trx.status   WHEN 0 THEN     \'No Valido\'
                                            WHEN 1 THEN     \'Procesado\'
                                            WHEN 2 THEN     \'Reversado\'
                         END                            AS status_name  
                        , trx.response_json             AS response_json');
        $dbquery->from( 'datafast_transactions', 'trx');

       if  ($id_transaction > 0 )
       {
            $dbquery->where("trx.id =".$id_transaction);
       }
       
       if  ($year > 0 )
       {
            $dbquery->where("YEAR(timestamp) =".$year);
       }
       if  ($month > 0 )
       {
            $dbquery->where("MONTH(timestamp) =".$month);
       }
       if  ($orden <> '' )
       {
            $dbquery->where("trx.cart_id ='".$orden."'");
       }
       if  ($id_trx <> '' )
       {
            $dbquery->where("trx.transaction_id ='".$id_trx."'");
       }

        $transactions = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($dbquery->build());
  
        return $transactions;
    }

    public function paginateSubscribers($subscribers, $page = 1, $pagination = 50)
    {
        if (count($subscribers) > $pagination) {
            $subscribers = array_slice($subscribers, $pagination * ($page - 1), $pagination);
        }

        return $subscribers;
    }
    
    private function getConfig($key)
    {
        return Configuration::get($key);
    }

    private function getPath()
    {
        return 'modules/datafast';
    }

    public function hookPaymentOptions($params)
    {
        try {
            $payment = new Payment();

            $request = $this->getDatafastRequest();

            $productInfo[] = $this->getProductInfo();
            $customerInfo = $this->getCustomerInfo();

            $registrations = $this->getCustomerRegistrations();
            $cartInfo = $this->getCartInfo();
            $amount = $this->getAmount();

            $payment->setCartInfo($cartInfo);
            $payment->setProductInfo($productInfo);
            $payment->setCustomerInfo($customerInfo);
            $payment->setRegistrations($registrations);
            $payment->setAmount($amount);
            $payment->setRequest($request);


            $paymentService = new PaymentService();

            $checkOutId = $paymentService->requestCheckoutId($payment);

            if (empty($checkOutId)) {
                $this->getLogger()->error("No se pudo obtener checkoutId de Datafast.");
                return [];
            }

            $checkScript = $request->getCheckoutScript() . $checkOutId;

            $action = $this->context->link->getModuleLink($this->name, 'result', array('datafastId' => $checkOutId), true);

            $this->smarty->assign('action', $action);
            $this->smarty->assign('checkScript', $checkScript);
            $this->smarty->assign('checkOutId', $checkOutId);

            if ($this->context->customer->isLogged())
            {
			    $this->smarty->assign('customertoken', Configuration::get('DATAFAST_CUSTOMERTOKEN', null));
            }
            else
            {
                $this->smarty->assign('customertoken', 'false');
            }

			if (Configuration::get('DATAFAST_STYLE',null) == "1")
			{
				$style="card";
			}
			else
			{
				$style="plain";
			}

            if (Configuration::get('DATAFAST_CVV',null) == "1")
			{
				$requirecvv="true";
			}
			else
			{
				$requirecvv="false";
			}

			$this->smarty->assign('style', $style);
            $this->smarty->assign('requirecvv', $requirecvv);


            $arr = [];
            $defaultTermType = '';
            $defaultInstallments = '';
            $termtypes = datafastInstallments::getsInstallmentsTermTypeConfiguration();
            if ($termtypes) {
                $i=0;
                foreach ($termtypes as $row) {
                    $arr[$i]['name'] =$row['name'];
                    $arr[$i]['code'] =$row['code'];
                    if ($i==0)
                    {
                        $defaultTermType =$row['codeTermType'];
                        $defaultInstallments =$row['installments'];
                    }
                    $i++;
                }
            }


            $removetoken = AdminController::$currentIndex . '&configure=' . $this->name.'&viewTransactions&token=' .Tools::getAdminTokenLite('AdminModules');

            $this->smarty->assign("termtypes",$arr);
            $this->smarty->assign("defaultTermType",$defaultTermType);
            $this->smarty->assign("defaultInstallments",$defaultInstallments);
            $this->smarty->assign("removetoken", $removetoken);
            $setAdditionalInformation = $this->fetch('module:datafast/views/templates/hook/datafastPayment.tpl');

            $newOption = new PaymentOption();
            $newOption->setModuleName($this->name)
                ->setCallToActionText($this->trans('Pago con Datafast', array(), 'Pago con Datafast'))
                ->setAction($action)
                ->setAdditionalInformation($setAdditionalInformation);
            return [$newOption];

        } catch (\Exception $e) {
            $this->getLogger()->error("Error al mostrar opción de pago Datafast: " . $e->getMessage());
            return [];
        } catch (\Error $e) {
            $this->getLogger()->error("Error fatal al mostrar opción de pago Datafast: " . $e->getMessage());
            return [];
        }
    }

    /**
     * @return DatafastRequest
     */
    protected function getDatafastRequest(): DatafastRequest
    {
        $config = new Config();
		$data['DATAFAST_DEV']=Configuration::get('DATAFAST_DEV', null);
		$data['DATAFAST_BEARER_TOKEN']=Configuration::get('DATAFAST_BEARER_TOKEN', null);
		$data['DATAFAST_ENTITY_ID']=Configuration::get('DATAFAST_ENTITY_ID', null);
		$data['DATAFAST_MID']=Configuration::get('DATAFAST_MID', null);
		$data['DATAFAST_TID']=Configuration::get('DATAFAST_TID', null);
		$data['DATAFAST_RISK']=Configuration::get('DATAFAST_RISK', null);
        $data['DATAFAST_PROVEEDOR']=Configuration::get('DATAFAST_PROVEEDOR', null);
        $data['DATAFAST_ECI']=Configuration::get('DATAFAST_ECI', null);
        $data['DATAFAST_PREFIJOTRX']=Configuration::get('DATAFAST_PREFIJOTRX', null);
		$data['DATAFAST_CUSTOMERTOKEN']=Configuration::get('DATAFAST_CUSTOMERTOKEN', null);
        $data['DATAFAST_STYLE']=Configuration::get('DATAFAST_STYLE', null);
        $data['DATAFAST_CVV']=Configuration::get('DATAFAST_CVV', null);
        $data['DATAFAST_PRODULR']=Configuration::get('DATAFAST_PRODULR', null);
        $data['DATAFAST_DEVURL']=Configuration::get('DATAFAST_DEVURL', null);
        return $config->getDatafastRequest($data);
    }

    private function getProductInfo(): array
    {
        $products = $this->context->cart->getProducts();

        $productList = [];
        foreach ($products as $key => $product) {
            $productInfo = new ProductInfo($product['name'], $product['description_short'], $product['price_wt'], $product['quantity']);
            array_push($productList, $productInfo);
        }
        return $productList;
    }
    
    public function getCustomerRegistrations()
    {
        $dbquery = new DbQuery();
        $dbquery->select('dct.token AS token');
        $dbquery->from( 'datafast_customertoken', 'dct');
        $dbquery->where("dct.customer_id = '". $this->context->customer->id."'");
		$dbquery->where("dct.token <>''");
        $customersRegistrations = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($dbquery->build());
        return $customersRegistrations;
    }
 

    /**
     * @return CustomerInfo
     */
    private function getCustomerInfo(): CustomerInfo
    {
        $cart = $this->context->cart;
		
        $transactionId = $cart->id; 
        $customerId = $this->context->customer->id;
        $firstName = $this->context->customer->firstname;
        $lastName = $this->context->customer->lastname;
        $email = $this->context->customer->email;

        $address = new Address((int)$cart->id_address_delivery);

        $customerIp = $_SERVER['REMOTE_ADDR'];
        $shippingAddress = $address->address1;
		$shippingCountry = "EC";
		
		
		$billing = new Address((int)$cart->id_address_invoice);
		 
		$dni = $billing->vat_number;
        $mobile = $billing->phone;
		$billingAddress = $billing->address1;
		$billingCountry = "EC";
		$billingPostCode = $billing->postcode;
        return new CustomerInfo($firstName, "", $lastName, $customerIp, $customerId, $transactionId, $email, $dni, $mobile, $shippingAddress, $shippingCountry,$billingAddress,$billingCountry,$billingPostCode);
    }

        /**
     * @return CartInfo
     */
    private function getCartInfo(): CartInfo
    {
        $cart = $this->context->cart;
        $cart_id =  $cart->id; //$cart->cartId;

        
        return new CartInfo($cart_id);
    }


    /**
     * @return Amount
     */
    protected function getAmount(): Amount
    {
        $cart = $this->context->cart; 
        $amount = new Amount();
        
        //total a pagar por la orde
        $totalOrder = $cart->getOrderTotal(true, Cart::BOTH); 
        $amount->setTotal($totalOrder);
        
        //calculo de subtotales sin iva y sin descuento
        $subtotalIVA = 0.0;
        $subtotalIVA0 = 0.0;  
        foreach ($cart->getProducts() as $product) 
        {
            if ($product['rate'] > 0) 
                $subtotalIVA += $product['total'];   
            else 
                $subtotalIVA0 += $product['total'];  
        }    

        //obtener descuento sin iva 
        $total_discounts = $cart->getOrderTotal(false, Cart::ONLY_DISCOUNTS);

        //calculo de valores por envio
        $envio_subtotal= Context::getContext()->cart->getTotalShippingCost(null, false);
        $envio_total=   Context::getContext()->cart->getTotalShippingCost(); 
        $envio_imp = 0;
        $envio_sinimp = 0;
        if ($envio_subtotal  == $envio_total) 
            $envio_sinimp =  $envio_subtotal; 
        else 
            $envio_imp =  $envio_subtotal; 
        
        //obtener razon del descuento con respecto a los subtotales
        $subtotalSum = $subtotalIVA0 + $subtotalIVA;
        $razon = ($subtotalSum > 0) ? $total_discounts / $subtotalSum : 0;  

        //Calculo de descuentos a los subtotales sin envio
        $descuentoIVA = $subtotalIVA * $razon;
        $descuentoIVA0 = $subtotalIVA0 * $razon;   

        //calulo de subtotales quitadoles el descuendo y agregando el valor del envio hasta este punto porque los descuentos
        //no aplican en los envios.
        $subtotalIVA = $subtotalIVA - $descuentoIVA + $envio_imp;
        $subtotalIVA0 = $subtotalIVA0 - $descuentoIVA0 + $envio_sinimp;

        //agrego valores fromateados
        $amount->setSubtotalIVA(round($subtotalIVA,2));
        $amount->setSubtotalIVA0(round($subtotalIVA0,2));  
         
        return $amount;
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency((int)($cart->id_currency));
        $currencies_module = $this->getCurrency((int)$cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    public function hookDisplayHeader($params)
    {

    }

    public function buildConfigInfo(): void
    {
        $config = Configuration::getMultiple(array('DATAFAST_DEV'
                                                ,'DATAFAST_ENTITY_ID'
                                                , 'DATAFAST_BEARER_TOKEN'
                                                , 'DATAFAST_MID'
                                                , 'DATAFAST_TID'
                                                , 'DATAFAST_RISK'
                                                , 'DATAFAST_PROVEEDOR'
                                                , 'DATAFAST_ECI'
                                                , 'DATAFAST_PREFIJOTRX'
                                                ,'DATAFAST_CUSTOMERTOKEN'
                                                ,'DATAFAST_STYLE'
                                                ,'DATAFAST_CVV'
                                                ,'DATAFAST_DEVURL'
                                                ,'DATAFAST_PRODULR'));

        if (isset($config['DATAFAST_ENTITY_ID'])) {
            $this->entityId = $config['DATAFAST_ENTITY_ID'];
        }

        if (isset($config['DATAFAST_BEARER_TOKEN'])) {
            $this->bearerToken = $config['DATAFAST_BEARER_TOKEN'];
        }

        if (isset($config['DATAFAST_MID'])) {
            $this->mid = $config['DATAFAST_MID'];
        }

        if (isset($config['DATAFAST_TID'])) {
            $this->tid = $config['DATAFAST_TID'];
        }

        if (isset($config['DATAFAST_RISK'])) {
            $this->risk = $config['DATAFAST_RISK'];
        }

        if (isset($config['DATAFAST_PROVEEDOR'])) {
            $this->proveedor = $config['DATAFAST_PROVEEDOR'];
        }

        if (isset($config['DATAFAST_PREFIJOTRX'])) {
            $this->prefijo_trx = $config['DATAFAST_PREFIJOTRX'];
        }
		
		if (isset($config['DATAFAST_CUSTOMERTOKEN'])) {
            $this->customer_token = $config['DATAFAST_CUSTOMERTOKEN'];
        }
        
        if (isset($config['DATAFAST_STYLE'])) {
            $this->style = $config['DATAFAST_STYLE'];
        }

        if (isset($config['DATAFAST_CVV'])) {
            $this->requirecvv = $config['DATAFAST_CVV'];
        }

        if (isset($config['DATAFAST_DEV'])) {
            $this->dev = $config['DATAFAST_DEV'];
        }

        if (isset($config['DATAFAST_PRODULR'])) {
            $this->produrl = $config['DATAFAST_PRODULR'];
        }

        if (isset($config['DATAFAST_DEVURL'])) {
            $this->devurl = $config['DATAFAST_DEVURL'];
        }

    }
    function searchTransactionByPaymentId($data)
    { 
        if (!isset($data)) {
            return false;
        }   
        $config['DATAFAST_DEV']=Configuration::get('DATAFAST_DEV', null);
        $config['DATAFAST_BEARER_TOKEN']=Configuration::get('DATAFAST_BEARER_TOKEN', null);
        $config['DATAFAST_ENTITY_ID']=Configuration::get('DATAFAST_ENTITY_ID', null); 
        $config['DATAFAST_PRODULR']=Configuration::get('DATAFAST_PRODULR', null);
        $config['DATAFAST_DEVURL']=Configuration::get('DATAFAST_DEVURL', null);

	    if ($config['DATAFAST_DEV']) {
            if($config['DATAFAST_DEVURL']=='1')	
                  $url = Environment::TEST; 
            if($config['DATAFAST_DEVURL']=='2')	
                  $url = Environment::TEST2; 
            $verifyPeer = false;
        } else {
            if($config['DATAFAST_PRODULR']=='1')	
                $url = Environment::PRODUCTION; 
            if($config['DATAFAST_PRODULR']=='2')	
                $url = Environment::PRODUCTION2;  
            $verifyPeer = true;
        }    
        $url = $url."query/".$data;  
        $url .= "?entityId=".$config['DATAFAST_ENTITY_ID']; 
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$config['DATAFAST_BEARER_TOKEN']));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');  
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifyPeer); // this should be set to true in production
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $responseData = curl_exec($ch); 
        if (curl_errno($ch)) {
          return curl_error($ch);
        }
        curl_close($ch);
        return  json_decode($responseData,true);
    }
}



