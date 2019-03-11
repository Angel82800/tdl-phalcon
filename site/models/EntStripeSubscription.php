<?php

namespace Thrust\Models;

/**
 * Thrust\Models\EntStripeSubscription
 */
class EntStripeSubscription extends ModelBase
{

    /**
     *
     * @var integer
     * @Primary
     * @Identity
     * @Column(type="integer", length=20, nullable=false)
     */
    public $pk_id;

    /**
     *
     * @var integer
     * @Column(type="integer", length=20, nullable=false)
     */
    public $subscriber_id;

    /**
     *
     * @var string
     * @Column(type="string", nullable=false)
     */
    public $subscribe_level;

    /**
     *
     * @var string
     * @Column(type="string", nullable=false)
     */
    public $stripe_customer_id;

    /**
     *
     * @var string
     * @Column(type="string", nullable=false)
     */
    public $stripe_subscription_id;

    /**
     *
     * @var string
     * @Column(type="string", nullable=false)
     */
    public $plan_key;

    /**
     *
     * @var string
     * @Column(type="string", nullable=false)
     */
    public $plan_name;

    /**
     *
     * @var string
     * @Column(type="string", nullable=true)
     */
    public $coupon;

    /**
     *
     * @var string
     * @Column(type="string", nullable=false)
     */
    public $created_by;

    /**
     *
     * @var string
     * @Column(type="string", nullable=false)
     */
    public $updated_by;

    /**
     *
     * @var string
     * @Column(type="string", nullable=true)
     */
    public $datetime_created;

    /**
     *
     * @var string
     * @Column(type="string", nullable=true)
     */
    public $datetime_updated;

    /**
     *
     * @var integer
     * @Column(type="integer", length=1, nullable=true)
     */
    public $is_active;

    /**
     *
     * @var integer
     * @Column(type="integer", length=4, nullable=true)
     */
    public $is_deleted;

    public function initialize()
    {
        // Setup the DB connections
        $this->setReadConnectionService('oltp-read');
        $this->setWriteConnectionService('oltp-write');
    }

}
