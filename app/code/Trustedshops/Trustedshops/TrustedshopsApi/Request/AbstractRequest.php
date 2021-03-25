<?php

namespace Trustedshops\Trustedshops\TrustedshopsApi\Request;

/**
 * Class AbstractRequest
 * @package Trustedshops\Trustedshops\TrustedshopsApi\Request
 */
abstract class AbstractRequest implements RequestInterface
{
    protected const API_ENDPOINT = '';
    protected const API_URL = 'https://api.trustedshops.com/rest';
    protected const API_URL_QA = 'https://api-qa.trustedshops.com/rest';
    protected const API_METHOD = 'GET';

    /** @var RequestDataInterface $requestData */
    protected $requestData;

    /**
     * @return RequestDataInterface
     */
    public function getRequestData()
    {
        return $this->requestData;
    }

    /**
     * @param RequestDataInterface $requestData
     * @return AbstractRequest
     */
    public function setRequestData($requestData)
    {
        $this->requestData = $requestData;
        return $this;
    }

    /**
     * Returns the API endpoint for the request.
     * You can override either the API_ENDPOINT constant or this method (or both) in the child class
     * @return string
     */
    public function getApiEndpoint()
    {
        return static::API_ENDPOINT;
    }

    /**
     * Returns the API url for the request. (e.g. https://api.trustedshops.com/rest/)
     * You can override either the API_URL constant or this method (or both) in the child class
     * @param bool $production
     * @return string
     */
    public function getApiUrl($production = true)
    {
        if ($production !== true) {
            return static::API_URL_QA;
        }
        return static::API_URL;
    }

    /**
     * Returns the HTTP method for the request. (e.g. 'GET')
     * You can override either the API_METHOD constant or this method (or both) in the child class
     * @return string
     */
    public function getApiMethod()
    {
        return static::API_METHOD;
    }
}