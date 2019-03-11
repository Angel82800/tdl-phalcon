<?php

namespace Thrust\Models;

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Validator\Uniqueness;

/**
 * Thrust\Models\EntOrganization
 */
class EntOrganization extends ModelBase
{
    public $pk_id;
    public $fk_attr_industris_id;
    public $fk_attr_regions_id;
    public $name;
    public $zipCode;
    public $employees;
    public $clients;
    public $stripe_customer_id;
    public $is_beta;
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
            __NAMESPACE__ . '\EntUsers',
            'fk_ent_organization_id',
            [
                'alias' => 'users'
            ]
        );
    }
}
