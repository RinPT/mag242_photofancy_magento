<?php

namespace Trustedshops\Trustedshops\TrustedshopsApi\Request;

interface RequestDataInterface
{
    /**
     * This method returns the raw request body that will be passed on to the API, already encoded in the correct Content-Type.
     *
     * @return string
     */
    public function getRequestBody();
}