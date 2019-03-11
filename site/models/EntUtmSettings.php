<?php

namespace Thrust\Models;

/**
 * Thrust\Models\EntUtmSettings
 */
class EntUtmSettings extends ModelBase
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
    public $ent_utm_id;

    /**
     *
     * @var string
     * @Column(type="string", length=45, nullable=false)
     */
    public $platform;

    /**
     *
     * @var string
     * @Column(type="string", length=45, nullable=false)
     */
    public $product;

    /**
     *
     * @var string
     * @Column(type="string", length=50, nullable=false)
     */
    public $shield_release;

    /**
     *
     * @var integer
     * @Column(type="integer", length=1, nullable=false)
     */
    public $service_squid;

    /**
     *
     * @var integer
     * @Column(type="integer", length=1, nullable=false)
     */
    public $service__squidguard;

    /**
     *
     * @var integer
     * @Column(type="integer", length=1, nullable=false)
     */
    public $service__e2guardian;

    /**
     *
     * @var integer
     * @Column(type="integer", length=1, nullable=false)
     */
    public $service__clamav;

    /**
     *
     * @var integer
     * @Column(type="integer", length=1, nullable=false)
     */
    public $service__freshclam;

    /**
     *
     * @var integer
     * @Column(type="integer", length=1, nullable=false)
     */
    public $service__bind;

    /**
     *
     * @var integer
     * @Column(type="integer", length=1, nullable=false)
     */
    public $service__dhcp;

    /**
     *
     * @var integer
     * @Column(type="integer", length=1, nullable=false)
     */
    public $service__nat;

    /**
     *
     * @var integer
     * @Column(type="integer", length=1, nullable=false)
     */
    public $service__snort;

    /**
     *
     * @var integer
     * @Column(type="integer", length=1, nullable=false)
     */
    public $service__lighttpd;

    /**
     *
     * @var integer
     * @Column(type="integer", length=1, nullable=false)
     */
    public $service__openvpn;

    /**
     *
     * @var integer
     * @Column(type="integer", length=1, nullable=false)
     */
    public $filter__adult;

    /**
     *
     * @var integer
     * @Column(type="integer", length=1, nullable=false)
     */
    public $filter__adult__other;

    /**
     *
     * @var integer
     * @Column(type="integer", length=1, nullable=false)
     */
    public $filter__gambling;

    /**
     *
     * @var integer
     * @Column(type="integer", length=1, nullable=false)
     */
    public $filter__violence;

    /**
     *
     * @var integer
     * @Column(type="integer", length=1, nullable=false)
     */
    public $filter__drugs;

    /**
     *
     * @var integer
     * @Column(type="integer", length=1, nullable=false)
     */
    public $filter__ads;

    /**
     *
     * @var integer
     * @Column(type="integer", length=1, nullable=false)
     */
    public $proxy__bypass__ssl;

    /**
     *
     * @var integer
     * @Column(type="integer", length=1, nullable=false)
     */
    public $proxy__bypass__skype;

    /**
     *
     * @var integer
     * @Column(type="integer", length=1, nullable=false)
     */
    public $proxy__bypass__slack;

    /**
     *
     * @var integer
     * @Column(type="integer", length=1, nullable=false)
     */
    public $wireless__enabled;

    /**
     *
     * @var integer
     * @Column(type="integer", length=1, nullable=false)
     */
    public $wireless__ac;

    /**
     *
     * @var string
     * @Column(type="string", length=32, nullable=false)
     */
    public $secure_wireless_name;

    /**
     *
     * @var string
     * @Column(type="string", length=32, nullable=false)
     */
    public $secure_wireless_password;

    /**
     *
     * @var integer
     * @Column(type="integer", length=6, nullable=false)
     */
    public $vpn__port;

    /**
     *
     * @var string
     * @Column(type="string", length=5, nullable=false)
     */
    public $vpn__proto;

    /**
     *
     * @var string
     * @Column(type="string", length=5, nullable=false)
     */
    public $vpn__tunnel;

    /**
     *
     * @var string
     * @Column(type="string", length=15, nullable=false)
     */
    public $vpn__ip;

    /**
     *
     * @var string
     * @Column(type="string", length=15, nullable=false)
     */
    public $vpn__mask;

    /**
     *
     * @var string
     * @Column(type="string", length=18, nullable=false)
     */
    public $vpn__cidr;

    /**
     *
     * @var string
     * @Column(type="string", nullable=true)
     */
    public $datetime_created;

    /**
     *
     * @var string
     * @Column(type="string", nullable=false)
     */
    public $created_by;

    /**
     *
     * @var string
     * @Column(type="string", nullable=true)
     */
    public $datetime_updated;

    /**
     *
     * @var string
     * @Column(type="string", nullable=false)
     */
    public $updated_by;

    /**
     *
     * @var integer
     * @Column(type="integer", length=1, nullable=true)
     */
    public $is_active;

    /**
     *
     * @var integer
     * @Column(type="integer", length=1, nullable=true)
     */
    public $is_deleted;

    public function initialize()
    {
        // Setup the DB connections
        $this->setReadConnectionService('oltp-read');
        $this->setWriteConnectionService('oltp-write');
    }
}
