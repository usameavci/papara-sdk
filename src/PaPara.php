<?php

namespace UsameAvci\PaPara;

use SimpleXMLElement;

/**
* PaPara Api Class
*/
class PaPara
{

    private $mode;
    private static $environments;
    private $order_id;
    private $amount;
    private $discount;
    private $shoppingVoucher;
    private $url;
    
    public function __construct($mode = 'test')
    {
        if (!in_array($mode, ['test', 'prod'])) {
            throw new Exception("Api mode not valid! Available modes is only test or prod. ", 1);       
        }
        $this->mode = $mode;
    }

    public static function setEnvironment($environments)
    {
        self::$environments = $environments;
    }

    public function setOrderId($orderId)
    {
        $this->order_id = $orderId;
        return $this;
    }

    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }

    public function setDiscount($discount)
    {
        $this->discount = $discount;
        return $this;
    }

    public function setShoppingVoucher($shoppingVoucher)
    {
        $this->shoppingVoucher = $shoppingVoucher;
        return $this;
    }

    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    public function getOrderId()
    {
        return $this->order_id;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function getDiscount()
    {
        return $this->discount;
    }

    public function getShoppingVoucher()
    {
        return $this->shoppingVoucher;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getRequestBody()
    {
        if (!$this->amount) $this->amount = $this->shoppingVoucher->getTotalAmount();

        $environments = self::$environments[$this->mode];

        $xmlHeader = '<?xml version="1.0"?><soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope"><soap12:Body>';
        $xmlFooter = '</soap12:Body></soap12:Envelope>';
        $TransactionRequest = new SimpleXMLElement('<TransactionRequest/>');

            $TransactionRequest->addAttribute('xmlns', 'http://tempuri.org/');
            $TransactionRequest->addChild('username', $environments['username']);
            $TransactionRequest->addChild('key', $environments['key']);
            $TransactionRequest->addChild('walletno', $environments['walletno']);
            $TransactionRequest->addChild('referansId', $environments['referansId']);
            $TransactionRequest->addChild('url', $environments['url']);
            $TransactionRequest->addChild('secret_key', $environments['secret_key']);

            $TransactionRequest->addChild('order_id', $this->getOrderId());
            $TransactionRequest->addChild('amount', $this->getAmount());
            $TransactionRequest->addChild('discount', $this->getAmount());

            $shoppingVoucher = $TransactionRequest->addChild('shoppingVoucher');
            foreach ($this->shoppingVoucher->getItems() as $entity) {
                $shoppingVoucherEntity = $shoppingVoucher->addChild('ShoppingVoucherEntity');
                    $shoppingVoucherEntity->addChild('ShoppingId', $entity->getShoppingId());
                    $shoppingVoucherEntity->addChild('ProductName', $entity->getProductName());
                    $shoppingVoucherEntity->addChild('Quantity', $entity->getQuantity());
                    $shoppingVoucherEntity->addChild('Amount', $entity->getAmount());
                    $shoppingVoucherEntity->addChild('TotalAmount', $entity->getTotalAmount());
                    $shoppingVoucherEntity->addChild('CategoryName', $entity->getCategoryName());
            }

        $xmlBody = $TransactionRequest->asXML();
        $xmlBody = str_replace("<?xml version=\"1.0\"?>\n", "", $xmlBody);

        return $xmlHeader . $xmlBody . $xmlFooter;
    }

    public function send()
    {
        $environments = self::$environments[$this->mode];

        $requestBody = $this->getRequestBody();

        $headers = [
            'Content-type: text/xml; charset="utf-8"',
            'Host: ' . $_SERVER['HTTP_HOST'],
            'Content-length: ' . strlen($requestBody),
            'POST /posservice/ApiRequest.asmx HTTP/1.1',
        ];


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_URL, $environments['url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        curl_close($ch);

        $response = simplexml_load_string($this->cleanResponse($response));

        return (object) [
            'message' => (string) $response->TransactionRequestResult->ResultMessage[0],
            'code' => (string) $response->TransactionRequestResult->ResultCode[0],
            'status' => (string) $response->TransactionRequestResult->ResultStatus,
        ];
    }

    public function cleanResponse($response)
    {
        $response = str_replace("<soap:Body>", "", $response);
        $response = str_replace("</soap:Body>", "", $response);
        $response = str_replace('<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">', "", $response);
        $response = str_replace("</soap:Envelope>", "", $response);
        return $response;
    }
}