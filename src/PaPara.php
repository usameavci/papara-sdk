<?php

namespace UsameAvci\PaPara;

use Exception;
use SimpleXMLElement;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException as GuzzleClientException;
use GuzzleHttp\Exception\ServerException as GuzzleServerException;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;

/**
* PaPara Api Class
*/
class PaPara
{

    private static $mode = "test";
    private static $environments;
    private $order_id;
    private $amount;
    private $discount;
    private $shoppingVoucher;
    private $url;
    
    public function __construct($mode = "test")
    {
        if (count(self::$environments) < 1) {
            throw new PaParaException("No api environments specified", 1);       
        }

        if (!in_array($mode, array_keys(self::$environments))) {
            throw new PaParaException("Api mode not valid! Available modes is only test or prod. ", 1);       
        }

        self::$mode = $mode;
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

        $environments = self::$environments[self::$mode];

        $xmlHeader = '<?xml version="1.0"?><soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope"><soap12:Body>';
        $xmlFooter = '</soap12:Body></soap12:Envelope>';
        $TransactionRequest = new SimpleXMLElement('<TransactionRequest/>');

            $TransactionRequest->addAttribute('xmlns', 'http://tempuri.org/');
            $TransactionRequest->addChild('username', $environments['username']);
            $TransactionRequest->addChild('key', $environments['key']);
            $TransactionRequest->addChild('walletno', $environments['walletno']);
            $TransactionRequest->addChild('referansId', $environments['referansId']);
            $TransactionRequest->addChild('secret_key', $environments['secret_key']);

            $TransactionRequest->addChild('order_id', $this->getOrderId());
            $TransactionRequest->addChild('amount', $this->getAmount());
            $TransactionRequest->addChild('discount', $this->getAmount());
            $TransactionRequest->addChild('url', $this->url);

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
        $environments = self::$environments[self::$mode];

        $requestBody = $this->getRequestBody();

        $requestHeaders = array(
            'Content-Type' => 'text/xml; charset="utf-8"'
        );

        try {
            $client = new GuzzleClient;
            $request = new GuzzleRequest('POST', $environments['url'], $requestHeaders, $requestBody);
            $response = $client->send($request);

            $responseBody = $response->getBody()->getContents();
            $response = $this->parseResponse($responseBody);

            if ($response->ResultCode == '100') {
                header('Location: ' . $response->ResultObject);
            } else if ($response->ResultCode == '200') {
                return $response;
            } else {
                throw new PaParaException($response->ResultMessage);
            }
        } catch (GuzzleClientException $e) {
            $statusCode = $e->getResponse()->getStatusCode();
            if ($statusCode == 415) {
                throw new PaParaException($e->getResponse()->getBody()->getContents());
            } else if ($statusCode == 404) {
                throw new PaParaException($e->getResponse()->getReasonPhrase());
            }
        } catch(GuzzleServerException $e){
            $exceptionBody = $e->getResponse()->getBody()->getContents();
            preg_match_all('#<soap:Text xml:lang="en">(.*)</soap:Text>#', $exceptionBody, $matches);
            throw new PaParaException($matches[1][0]);
        } catch (GuzzleRequestException $e) {
            throw new PaParaException("Unknown error");
        }

    }

    public function parseResponse($response)
    {
        $response = str_replace("<soap:Body>", "", $response);
        $response = str_replace("</soap:Body>", "", $response);
        $response = str_replace('<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">', "", $response);
        $response = str_replace("</soap:Envelope>", "", $response);
        $response = simplexml_load_string($response);
        $response = (object) json_decode(json_encode($response->TransactionRequestResult), 1);
        return $response;
    }

    public static function validate($param1, $param2 = "")
    {
        $environments = self::$environments[self::$mode];
        if (!$environments)
            throw new PaParaException('[' . self::$mode . '] environment not defined');

        $secretKey = $environments['secret_key'];

        if (is_array($param1)) {
            $pre1 = $param1['pre1'];
            $pre2 = $param1['pre2'];
        }else{
            $pre1 = $param1;
            $pre2 = $param2;
        }

        $pre1_decoded   = base64_decode($pre1);
        $orderId        = explode('+', $pre1_decoded)[0];
        $statusCode     = explode('+', $pre1_decoded)[1];

        $crypted_step1  = base64_encode($orderId . '+' . $statusCode);
        $crypted_finish = strtoupper(md5($crypted_step1 . $secretKey));

        if ($crypted_finish != $pre2) {
            return false;
        }
        return true;
    }
}