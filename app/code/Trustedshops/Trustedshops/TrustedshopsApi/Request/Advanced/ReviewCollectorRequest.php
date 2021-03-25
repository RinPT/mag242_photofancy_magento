<?php

namespace Trustedshops\Trustedshops\TrustedshopsApi\Request\Advanced;

use Trustedshops\Trustedshops\TrustedshopsApi\Request\AbstractRequest;

/**
 * Class ReviewCollectorRequest
 * @package Trustedshops\Trustedshops\TrustedshopsApi\Request\Advanced
 */
class ReviewCollectorRequest extends AbstractRequest
{
    protected const API_ENDPOINT = '/restricted/v2/shops/{tsId}/reviewcollector';
    protected const API_METHOD = 'POST';

    /**
     * Override of API endpoint getter to include the Trusted Shops ID
     * @return string
     */
    public function getApiEndpoint()
    {
        return str_replace('{tsId}', $this->getRequestData()->getTsId(), self::API_ENDPOINT);
    }
}