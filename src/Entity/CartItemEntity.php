<?php

namespace TMCms\Modules\Cart\Entity;

use neTpyceB\TMCms\Orm\Entity;

/**
 * Class CartItemEntity
 * @package TMCms\Modules\Cart\Entity
 *
 * @method setCartId(int $id)
 * @method int getAmount()
 * @method setAmount(int $amount)
 * @method setItemId(int $id)
 * @method setItemType(string $type)
 */
class CartItemEntity extends Entity
{
    protected $db_table = 'm_carts_items';
}