<?php

namespace Thrust\Models;

use Phalcon\Mvc\Model;

/**
 * Thrust\Models\EntArticles
 */
class EntArticles extends ModelBase
{
    public $pk_id;
    public $fk_ent_topics_id;
    public $title;
    public $content;
    public $datetime_created;
    public $created_by;
    public $datetime_updated;
    public $updated_by;
    public $is_active;
    public $is_deleted;

    public function initialize()
    {
        // Setup the DB connections
        $this->setReadConnectionService('oltp-read');
        $this->setWriteConnectionService('oltp-write');

        $this->hasOne(
            'fk_ent_topics_id',
            __NAMESPACE__ . '\EntTopics',
            'pk_id',
            [
                'alias'     => 'topic',
            ]
        );
    }
}
