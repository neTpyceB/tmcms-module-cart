<?php

namespace TMCms\Modules\Cart\Entity;

use neTpyceB\TMCms\Orm\Entity;

/**
 * Class CartItemEntity
 * @package TMCms\Modules\Cart\Entity
 *
 * @method int getAmount()
 * @method int getCartId()
 * @method int getItemId()
 * @method string getItemType()
 * @method $this setAmount(int $amount)
 * @method $this setCartId(int $id)
 * @method $this setItemId(int $id)
 * @method $this setItemType(string $type)
 */
class CartItemEntity extends Entity
{
    protected $db_table = 'm_carts_items';
}