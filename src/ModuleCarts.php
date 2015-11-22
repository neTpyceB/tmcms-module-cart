<?php

namespace neTpyceB\TMCms\Modules\Carts;

use neTpyceB\TMCms\Modules\IModule;
use neTpyceB\TMCms\Traits\singletonInstanceTrait;
use TMCms\Modules\Cart\Entity\CartEntity;
use TMCms\Modules\Cart\Entity\CartEntityRepository;
use TMCms\Modules\Cart\Entity\CartItemEntity;
use TMCms\Modules\Cart\Entity\CartItemEntityRepository;

defined('INC') or exit;

class ModuleCarts implements IModule
{
    use singletonInstanceTrait;

    public static $tables = [
        'carts' => 'm_carts',
        'items' => 'm_carts_items',
    ];

    /** CartEntity */
    private static $_cart;

    public static function getCurrentCart()
    {
        // Check local cache
        if (self::$_cart) {
            return self::$_cart;
        }

        self::removeOldCarts();

        // Get existing or create new cart, base on unique visitor's data
        $cart_collection = new CartEntityRepository();
        $cart_collection->setWhereUid(VISITOR_HASH);

        $cart = $cart_collection->getFirstObjectFromCollection();
        if (!$cart) {
            $cart = new CartEntity();
            $cart->setUid(VISITOR_HASH);
        }

        $cart->setLastActivityTs(NOW);
        $cart->save();

        //Save for cache
        self::$_cart = $cart;

        return $cart;
    }

    /**
     * @param string $type
     * @return array of data
     */
    public static function getCurrentCartItems($type = 'product')
    {
        $cart = self::getCurrentCart();

        $product_collection = new CartItemEntityRepository();
        $product_collection->setWhereCartId($cart->getId());
        $product_collection->setWhereItemType($type);

        return $product_collection->getAsArrayOfObjects();
    }

    /**
     * @param CartItemEntity $item
     * @param int $amount to plus or minus
     * @param string $type
     * @return CartItemEntity
     */
    public static function addItem(CartItemEntity $item, $amount = 1, $type = 'product')
    {
        $cart = self::getCurrentCart();

        $product_collection = new CartItemEntityRepository();
        $product_collection->setWhereCartId($cart->getId());
        $product_collection->setWhereItemType($type);
        $product_collection->setWhereItemId($item->getId());

        // Existing product in DB
        $product = $product_collection->getFirstObjectFromCollection();
        /** @var CartItemEntity $product */
        if (!$product) {
            // Or new
            $product = new CartItemEntity();
            $product->setCartId($cart->getId());
            $product->setItemType($type);
            $product->setItemId($item->getId());
        }

        $product->setAmount($product->getAmount() + $amount);
        $product->save();

        // If amount set to zero - remove from cart
        if ($product->getAmount() == 0) {
            $product->deleteObject();
        }

        return $product;
    }

    /**
     * @return array of data
     */
    public static function getCurrentCartProductIds()
    {
        $cart = self::getCurrentCart();

        $product_collection = new CartItemEntityRepository();
        $product_collection->addSimpleSelectFields('id');
        $product_collection->setWhereCartId($cart->getId());

        return $product_collection->getPairs('id', '');
    }

    private static function removeOldCarts()
    {
        if (rand(0, 10000)) {
            return;
        }

        $cart_collection = new CartEntityRepository();
        $cart_collection->setWhereLastActivityTs(NOW - (86400 * 7)); // One week ago
        $cart_collection->deleteObjectCollection();
    }
}