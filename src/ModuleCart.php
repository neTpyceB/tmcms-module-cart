<?php
declare(strict_types=1);

namespace TMCms\Modules\Cart;

use TMCms\Modules\Cart\Entity\CartEntity;
use TMCms\Modules\Cart\Entity\CartEntityRepository;
use TMCms\Modules\Cart\Entity\CartItemEntity;
use TMCms\Modules\Cart\Entity\CartItemEntityRepository;
use TMCms\Orm\Entity;
use TMCms\Traits\singletonInstanceTrait;

\defined('INC') or exit;

/**
 * Class ModuleCart
 * @package TMCms\Modules\Cart
 */
class ModuleCart
{
    use singletonInstanceTrait;

    /** CartEntity */
    private static $_cart;

    /**
     * @param Entity|string $product_entity_or_type
     *
     * @return CartItemEntityRepository
     * @throws \Exception
     */
    public static function getCurrentCartItems($product_entity_or_type): CartItemEntityRepository
    {
        $type = \is_string($product_entity_or_type) ? $product_entity_or_type : $product_entity_or_type->getUnqualifiedShortClassName();

        $cart = self::getCurrentCart();

        $product_collection = new CartItemEntityRepository();
        $product_collection->setWhereCartId($cart->getId());
        $product_collection->setWhereItemType($type);

        return $product_collection;
    }

    /**
     * @param int $client_id
     *
     * @return CartEntity
     * @throws \Exception
     */
    public static function getCurrentCart($client_id = 0): CartEntity
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

    /**
     * @throws \Exception
     */
    private static function removeOldCarts()
    {
        if (random_int(0, 10000)) {
            return;
        }

        $cart_collection = new CartEntityRepository();
        $cart_collection->setWhereLastActivityTs(NOW - (86400 * 7)); // One week ago
        $cart_collection->deleteObjectCollection();
    }

    /**
     * @param Entity $product
     *
     * @return CartItemEntity
     * @throws \Exception
     */
    public static function getCurrentCartItem($product, $type=''): CartItemEntity
    {
        $cart = self::getCurrentCart();

        if($product instanceof Entity){
            $product_id = $product->getId();
            $type = \is_string($type) ? $type : $product->getUnqualifiedShortClassName();
        }else{
            $product_id = (int)$product;
        }
        $product_collection = new CartItemEntityRepository();
        $product_collection->setWhereCartId($cart->getId());
        $product_collection->setWhereItemType($type);
        $product_collection->setWhereItemId($product);

        /** @var CartItemEntity $cart_item */
        $cart_item = $product_collection->getFirstObjectFromCollection();
        if (!$cart_item) {
            $cart_item = new CartItemEntity;
            $cart_item->setCartId($cart->getId());
            $cart_item->setItemId($product_id);
            $cart_item->setItemType($type);
            $cart_item->save();
        }

        return $cart_item;
    }

    /**
     * @param CartItemEntity $cart_item
     * @param int $amount
     *
     * @return CartItemEntity
     * @throws \Exception
     */
    public static function addItem(CartItemEntity $cart_item, $amount = 0): CartItemEntity
    {
        $cart = self::getCurrentCart();

        // For easier way
        if ($amount) {
            $cart_item->setAmount($amount);
        }

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

        // Set total amount after calculations
        if ($cart_item->getAmount()) {
            $product->setAmount($product->getAmount() + $cart_item->getAmount());
        }

        $product->save();

        // If amount set to zero - remove from cart
        if ($cart_item->getAmount() <= 0) {
            $product->deleteObject();
        }

        return $product;
    }

    /**
     * @param Entity|null $product
     *
     * @return array of data
     * @throws \Exception
     */
    public static function getCurrentCartProductIds(Entity $product = null): array
    {
        $cart = self::getCurrentCart();

        $product_collection = new CartItemEntityRepository();
        $product_collection->setWhereCartId($cart->getId());
        if ($product) {
            $product_collection->setWhereItemType($product->getUnqualifiedShortClassName());
        }

        return $product_collection->getPairs('item_id');
    }

    /**
     * @param CartItemEntity $cart_item
     *
     * @return CartItemEntity
     * @throws \Exception
     */
    public static function setItemInCart(CartItemEntity $cart_item): CartItemEntity
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

    /**
     * @throws \Exception
     */
    public static function clearCurrentCart() {
        $cart = self::getCurrentCart();

        $cart->deleteObject();
    }

    /**
     * @param Entity $product
     *
     * @return CartItemEntity
     */
    public static function createCartItemFromProductObject(Entity $product): CartItemEntity
    {
        $cart_item = new CartItemEntity;
        $cart_item->setItemId($product->getId());
        $cart_item->setItemType($product->getUnqualifiedShortClassName());

        return $cart_item;
    }
}
