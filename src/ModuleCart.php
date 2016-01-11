<?php

namespace TMCms\Modules\Cart;

use TMCms\Traits\singletonInstanceTrait;
use TMCms\Modules\Cart\Entity\CartEntity;
use TMCms\Modules\Cart\Entity\CartEntityRepository;
use TMCms\Modules\Cart\Entity\CartItemEntity;
use TMCms\Modules\Cart\Entity\CartItemEntityRepository;

defined('INC') or exit;

class ModuleCart
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
     * @return CartItemEntity
     */
    public static function addItem(CartItemEntity $item)
    {
        $cart = self::getCurrentCart();

        $product_collection = new CartItemEntityRepository();
        $product_collection->setWhereCartId($cart->getId());
        $product_collection->setWhereItemType($item->getItemType());
        $product_collection->setWhereItemId($item->getId());

        // Existing product in DB
        $product = $product_collection->getFirstObjectFromCollection();
        /** @var CartItemEntity $product */
        if (!$product) {
            // Or new
            $product = new CartItemEntity();
            $product->setCartId($cart->getId());
            $product->setItemType($item->getItemType());
            $product->setItemId($item->getId());
        }

        $product->setAmount($product->getAmount() + $item->getAmount());
        $product->save();
        // If amount set to zero - remove from cart
        if ($item->getAmount() == 0) {
            $product->deleteObject();
        }

        return $product;
    }

    /**
     * @return array of data
     */
    public static function getCurrentCartProductIds($type = '')
    {
        $cart = self::getCurrentCart();

        $product_collection = new CartItemEntityRepository();
        $product_collection->addSimpleSelectFields('id', 'item_id', 'item_type');
        $product_collection->setWhereCartId($cart->getId());
        if ($type) {
            $product_collection->setWhereItemType($type);
        }

        return $product_collection->getAsArrayOfObjects();
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