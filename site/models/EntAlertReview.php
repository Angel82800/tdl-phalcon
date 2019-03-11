<?php

namespace Thrust\Models;

use Phalcon\Mvc\Model;

/**
 * Thrust\Models\EntAlertReview 
 */
class EntAlertReview extends ModelBase
{
    public $pk_id;
    public $fk_ent_alert_id;
    public $fk_attr_review_classification_id;
    public $reviewed_by;
    public $internal_comments;
    public $user_instructions;
    public $slert_sent;
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

        $this->belongsTo(
            'fk_ent_alert_id',
            __NAMESPACE__ . '\EntAlert',
            'pk_id'
        );

        $this->belongsTo(
            'fk_attr_review_classification_id',
            __NAMESPACE__ . '\AttrReviewClassification',
            'pk_id'
        );
    }
}
