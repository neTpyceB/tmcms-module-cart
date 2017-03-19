<?php

namespace TMCms\Modules\Cart\Entity;

use TMCms\Orm\EntityRepository;

/**
 * Class CartEntityRepository
 * @package TMCms\Modules\Cart\Entity
 *
 * @method setWhereClientId(int $client_id)
 * @method setWhereLastActivityTs(int $ts)
 * @method setWhereUid(string $uid)
 */
class CartEntityRepository extends EntityRepository
{
    protected $db_table = 'm_carts';
    protected $table_structure = [
        'fields' => [
            'client_id' => [
                'type' => 'index',
            ],
            'uid' => [
                'type' => 'char',
                'length' => 32,
            ],
            'last_activity_ts' => [
                'type' => 'int',
                'unsigned' => true,
            ],
        ],
    ];
}