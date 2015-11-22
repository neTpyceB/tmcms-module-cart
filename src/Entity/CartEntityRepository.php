<?php

namespace TMCms\Modules\Cart\Entity;

use neTpyceB\TMCms\Orm\EntityRepository;

/**
 * Class CartEntityRepository
 * @package TMCms\Modules\Cart\Entity
 *
 * @method setWhereLastActivityTs(int $ts)
 * @method setWhereUid(string $uid)
 */
class CartEntityRepository extends EntityRepository
{
    protected $db_table = 'm_carts';
}