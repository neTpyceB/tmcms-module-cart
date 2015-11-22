<?php

namespace TMCms\Modules\Cart\Entity;

use neTpyceB\TMCms\Orm\EntityRepository;

class CartEntityRepository extends EntityRepository {
    protected $db_table = 'm_carts';
}