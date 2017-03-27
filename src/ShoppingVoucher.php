<?php

namespace UsameAvci\PaPara;

/**
* PaPara Shopping Voucher Class
*/
class ShoppingVoucher
{
    private $items;

    public function add(ShoppingVoucherEntity $entity)
    {
        $this->items[] = $entity;

        return $this;
    }

    public function getItems()
    {
        return $this->items;
    }

    public function getTotalAmount()
    {
        $total = 0;
        foreach ($this->items as $entity) {
            $total = $total + $entity->getTotalAmount();
        }
        return $total;
    }
}