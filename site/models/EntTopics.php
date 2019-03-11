<?php

namespace Thrust\Models;

use Phalcon\Mvc\Model;

/**
 * Thrust\Models\EntTopics
 */
class EntTopics extends ModelBase
{
    public $pk_id;
    public $name;
    public $icon;
    public $datetime_created;
    public $created_by;
    public $datetime_updated;
    public $updated_by;
    public $is_active;
    public $is_deleted;

    public function initialize()
    {
        //Setup the DB connections
        $this->setReadConnectionService('oltp-read');
        $this->setWriteConnectionService('oltp-write');

        $this->hasMany(
            'pk_id',
            __NAMESPACE__ . '\EntArticles',
            'fk_ent_topics_id',
            [
                'alias' => 'articles'
            ]
        );
    }
}
