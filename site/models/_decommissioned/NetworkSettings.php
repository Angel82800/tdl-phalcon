<?php

namespace Thrust\Models;

use Phalcon\Mvc\Model;
use Thrust\Saltstack\Api;

/**
 * Thrust\Models\NetworkSettings
 * Security alerts for a user.
 */
class NetworkSettings extends Model
{
    /**
     * ID.
     *
     * @var int
     */
    public $id;

    public $accountId;

    public $modifiedAt;

    public $publicNetworkName;

    public $privateNetworkName;

    public $publicNetworkPassword;

    public $filteredContent;

    /**
     * Define relationships to Accounts.
     */
    public function initialize()
    {
        $this->hasOne(
            'accountId',
            "Thrust\Models\Accounts",
            'id',
             array(
                'foreignKey' => array(
                    'message' => 'The accountId does not exist on the Accounts model'
                )
            )
        );
    }

    public function beforeValidationOnCreate()
    {
        $this->modifiedAt = time();
    }

    public function beforeValidationOnUpdate()
    {
        $this->modifiedAt = time();
    }

    public function updatePublicName($newNetworkName)
    {
        $saltApi = new Api();

        return $saltApi->updateWirelessName($newNetworkName);
    }

    public function updatePublicPassword($newNetworkPassword)
    {
        $saltApi = new Api();

        return $saltApi->updateWirelessPassword($newNetworkPassword);
    }

    /**
     * Takes array of filter changes to apply and loops through them and makes Salt calls
     * for each one to apply grain change and then updates SquidGuard to apply changes.
     *
     * @param array $updatedFilters  - filter/value pair of new filter changes to apply
     * @param array $originalFilters - original filter/value pairs
     */
    public function updateFilteredContent($updatedFilters, $originalFilters)
    {
        $saltApi = new Api();
        $logger = $this->_di->get('logger');

        $success = true;
        foreach ($updatedFilters as $filter => $value) {
            $logger->info('Updated filter: ' . $filter . ' with value: ' . $value);

            $success = $success && $saltApi->updateFilteredContent($filter, $value);

            // TODO: Rollback previous calls
            if (!$success) {
                $logger->error('Error updating filters: ' . json_encode($updatedFilters));

                return false;
            }
        }

        // Update SquidGuard and turn on changes
        return $saltApi->applyFilterControlState();

        // TODO: Figure out rollback state on SquidGuard failure
    }
}
