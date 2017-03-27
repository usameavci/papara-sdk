<?php

namespace UsameAvci\PaPara;

/**
* Papara Shopping Boucher Entity Class
*/
class ShoppingVoucherEntity
{
    private $shoppingId;
    private $productName;
    private $quantity;
    private $amount;
    private $totalAmount;
    private $categoryName;

    public function setShoppingId($shoppingId)
    {
        $this->shoppingId = $shoppingId;;
        return $this;
    }
    
    public function setProductName($productName)
    {
        $this->productName = $productName;
        return $this;
    }
    
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
        return $this;
    }
    
    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }
    
    public function setTotalAmount($totalAmount)
    {
        $this->totalAmount = $totalAmount;
        return $this;
    }
    
    public function setCategoryName($categoryName)
    {
        $this->categoryName = $categoryName;
        return $this;
    }

    public function getShoppingId()
    {
        return $this->shoppingId;
    }
    
    public function getProductName()
    {
        return $this->productName;
    }
    
    public function getQuantity()
    {
        return $this->quantity;
    }
    
    public function getAmount()
    {
        return $this->amount;
    }
    
    public function getCategoryName()
    {
        return $this->categoryName;
    }

    public function getTotalAmount()
    {
        if (!$this->totalAmount) {
            return $this->quantity * $this->amount;            
        }

        return $this->totalAmount;
    }
}