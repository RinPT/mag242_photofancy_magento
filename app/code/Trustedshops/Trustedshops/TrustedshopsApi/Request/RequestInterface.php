<?php

namespace Trustedshops\Trustedshops\TrustedshopsApi\Request;

/**
 * Interface RequestInterface
 * @package Trustedshops\Trustedshops\TrustedshopsApi\Request
 */
interface RequestInterface
{
    /**
     * Get the request data object from the request
     * @return RequestDataInterface
     */
    public function getRequestData();

    /**
     * Set the request data object that contains the request body
     * @param RequestDataInterface $requestData
     * @return RequestInterface
     */
    public function setRequestData(RequestDataInterface $requestData);

    /**
     * Get the base URL for the API request (e.g. https://api.example.com)
     * @return string
     */
    public function getApiUrl();

    /**
     * Get the URL endpoint for the API request (e.g. `/rest/v2/`)
     * @return string
     */
    public function getApiEndpoint();

    /**
     * Get the HTTP method for the API request
     * @return string
     */
    public function getApiMethod();
}