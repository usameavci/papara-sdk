# PaPara SDK
Simple integration package for PaPara (Electronic money issuer)

### Installation
``` bash
composer require usameavci/papara-sdk
```

### Getting Started
``` php
    // Require compsoers autoload file
    require 'vendor/autoload.php';

    // Defining namespace usings
    use UsameAvci\PaPara\{ PaPara, ShoppingVoucher, ShoppingVoucherEntity, PaParaException };


    // Set environments [ default: test ]
    PaPara::setEnvironment([
        "test" => [
            "url" => "https://test.papara.com/posservice/ApiRequest.asmx",
            "username" => "...",
            "key" => "...",
            "walletno" => "...",
            "referansId" => "...",
            "secret_key" => "...",
        ],
        "prod" => [
            "url" => "https://account.papara.com/posservice/ApiRequest.asmx",
            "username" => "...",
            "referansId" => "...",
            "walletno" => "...",
            "key" => "...",
            "secret_key" => "...",
        ],
    ]);
```

### Creating Transaction
``` php
    // Creating Papara Instance
    $transaction = new Papara();

    $transaction
        // Setting order id
        ->setOrderId('ORDER-' . rand(1, 9999))
        // Setting discount amount
        ->setDiscount(0)
        // Setting return url
        ->setUrl('https://example.com/payment-result');


        // Creating shopping voucher entitiy
        $shoppingVoucherEntity = new ShoppingVoucherEntity;

            $shoppingVoucherEntity
                // Setting cart id
                ->setShoppingId(rand(1, 100))
                // Setting product name
                ->setProductName($_GET['ProductName'])
                // Setting product quantity
                ->setQuantity($_GET['Quantity'])
                // Setting product amount
                ->setAmount(15)
                // Setting total amount | This method is optional,
                // default value is (quantity * amount) when this method is not used
                ->setTotalAmount(1)
                // Setting product's category name
                ->setCategoryName('Plans');

        // Adding shopping voucher entity to shopping cart
        $shoppingVoucher = new ShoppingVoucher;

            $shoppingVoucher
                // Adding entities to cart
                ->add($shoppingVoucherEntity);

        // Setting shopping vouchers
        $transaction->setShoppingVoucher($shoppingVoucher);
```

### Get Response
``` php
    try {
        $response = $transaction->send();
        var_dump($response);
    } catch (PaParaException $e) {
        echo $e->getMessage();
    }

```
