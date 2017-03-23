<?php

namespace TMCms\Modules\Cart\Entity;

use TMCms\Orm\Entity;

/**
 * Class CartEntity
 * @package TMCms\Modules\Cart\Entity
 *
 * @method setClientId(int $client_id)
 * @method setLastActivityTs(int $ts)
 * @method setUid(string $uid)
 */
class CartEntity extends Entity
{
    protected $db_table = 'm_carts';

    protected function beforeDelete()
    {
        // Delete all items from cart
        $items = new CartItemEntityRepository();
        $items->setWhereCartId($this->getId());
        $items->deleteObjectCollection();

        return $this;
    }
}