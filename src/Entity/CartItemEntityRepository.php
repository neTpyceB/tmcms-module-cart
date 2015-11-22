<?php

namespace TMCms\Modules\Cart\Entity;

use neTpyceB\TMCms\Orm\EntityRepository;

/**
 * Class CartItemEntityRepository
 * @package TMCms\Modules\Cart\Entity
 *
 * @method setWhereCartId(int $id)
 * @method setWhereItemId(int $id)
 * @method setWhereItemType(string $type)
 */
class CartItemEntityRepository extends EntityRepository
{
    protected $db_table = 'm_carts_items';
}