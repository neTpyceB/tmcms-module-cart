<?php

namespace TMCms\Modules\Cart;

use TMCms\Orm\Entity;
use TMCms\Traits\singletonInstanceTrait;
use TMCms\Modules\Cart\Entity\CartEntity;
use TMCms\Modules\Cart\Entity\CartEntityRepository;
use TMCms\Modules\Cart\Entity\CartItemEntity;
use TMCms\Modules\Cart\Entity\CartItemEntityRepository;

defined('INC') or exit;

class ModuleCart
{
    use singletonInstanceTrait;

    /** CartEntity */
    private static $_cart;

    /**
     * @param string $product_type
     * @return array
     */
    public static function getCurrentCartItems($product_type = 'product')
    {
        $cart = self::getCurrentCart();

        $product_collection = new CartItemEntityRepository();
        $product_collection->setWhereCartId($cart->getId());
        $product_collection->setWhereItemType($product_type);

        /** @var array $cart_items */
        $cart_items = $product_collection->getAsArrayOfObjects();

        return $cart_items;
    }

    /**
     * @param int $client_id
     * @return CartEntity
     */
    public static function getCurrentCart($client_id = 0)
    {
        // Check local cache
        if (self::$_cart) {
            return self::$_cart;
        }

        self::removeOldCarts();

        // Get existing or create new cart, base on unique visitor's data
        $cart_collection = new CartEntityRepository();
        if ($client_id) {
            // By client
            $cart_collection->setWhereClientId($client_id);
        } else {
            // By browser
            $cart_collection->setWhereUid(VISITOR_HASH);
        }

        $cart = $cart_collection->getFirstObjectFromCollection();
        if (!$cart) {
            $cart = new CartEntity();
            $cart->setUid(VISITOR_HASH);
            if ($client_id) {
                $cart->setClientId($client_id);
            }
        }

        $cart->setLastActivityTs(NOW);
        $cart->save();

        //Save for cache
        self::$_cart = $cart;
        return $cart;
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

    public static function getCurrentCartItem($product_id, $product_type = 'product')
    {
        $cart = self::getCurrentCart();

        $product_collection = new CartItemEntityRepository();
        $product_collection->setWhereCartId($cart->getId());
        $product_collection->setWhereItemType($product_type);
        $product_collection->setWhereItemId($product_id);

        /** @var CartItemEntity $cart_item */
        $cart_item = $product_collection->getFirstObjectFromCollection();

        return $cart_item;
    }

    /**
     * @param CartItemEntity $cart_item
     * @return CartItemEntity
     */
    public static function addItem(CartItemEntity $cart_item)
    {
        $cart = self::getCurrentCart();

        $product_collection = new CartItemEntityRepository();
        $product_collection->setWhereCartId($cart->getId());
        $product_collection->setWhereItemType($cart_item->getItemType());
        $product_collection->setWhereItemId($cart_item->getItemId());

        // Existing product in DB
        $product = $product_collection->getFirstObjectFromCollection();

        /** @var CartItemEntity $product */
        if (!$product) {
            // Or new
            $product = new CartItemEntity();
            $product->setCartId($cart->getId());
            $product->setItemType($cart_item->getItemType());
            $product->setItemId($cart_item->getItemId());
        }

        // Set total amount
        if ($cart_item->getAmount()) {
            $product->setAmount($cart_item->getAmount());
        }

        $product->save();
        // If amount set to zero - remove from cart
        if ($cart_item->getAmount() <= 0) {
            $product->deleteObject();
        }

        return $product;
    }

    /**
     * @param string $product_type
     * @return array of data
     */
    public static function getCurrentCartProductIds($product_type = '')
    {
        $cart = self::getCurrentCart();

        $product_collection = new CartItemEntityRepository();
        $product_collection->setWhereCartId($cart->getId());
        if ($product_type) {
            $product_collection->setWhereItemType($product_type);
        }

        return $product_collection->getPairs('item_id');
    }

    /**
     * @param CartItemEntity $cart_item
     * @return CartItemEntity|\TMCms\Orm\Entity
     */
    public static function setItemInCart(CartItemEntity $cart_item)
    {
        $cart = self::getCurrentCart();

        $product_collection = new CartItemEntityRepository();
        $product_collection->setWhereCartId($cart->getId());
        $product_collection->setWhereItemType($cart_item->getItemType());
        $product_collection->setWhereItemId($cart_item->getItemId());

        // Existing product in DB
        $product = $product_collection->getFirstObjectFromCollection();

        /** @var CartItemEntity $product */
        if (!$product) {
            // Or new
            $product = new CartItemEntity();
            $product->setCartId($cart->getId());
            $product->setItemType($cart_item->getItemType());
            $product->setItemId($cart_item->getItemId());
        }

        // Set exact amount
        $product->setAmount($cart_item->getAmount());
        $product->save();
        // If amount set to zero - remove from cart
        if ($cart_item->getAmount() <= 0) {
            $product->deleteObject();
        }

        return $product;
    }
}