<?php

namespace Trustedshops\Trustedshops\TrustedshopsApi;

use Exception;
use Magento\Framework\Exception\AuthorizationException;
use Trustedshops\Trustedshops\Helper\Data;
use Trustedshops\Trustedshops\TrustedshopsApi\Request\RequestInterface;
use Zend\Http\Client;
use Zend\Http\Response;

/**
 * Class ApiClient
 * @package Trustedshops\Trustedshops\Api
 */
class ApiClient
{
    /** @var Client $client */
    protected $client;

    /** @var string $user */
    private $user;

    /** @var string $password */
    private $password;

    /** @var Data $helper */
    private $helper;

    /**
     * ApiClient constructor.
     * @param Client $zendClient
     * @param Data $helper
     */
    public function __construct(Client $zendClient, Data $helper)
    {
        $this->client = $zendClient;
        $this->helper = $helper;
    }

    /**
     * Main function for sending the request to the API.
     * This takes the request object that has to be present and
     * @param RequestInterface $request
     * @return Response
     */
    public function execute($request)
    {
        $response = null;
        try {
            $this->client->reset();
            $this->client->setHeaders(['Accept' => 'application/json']);
            $this->client->setEncType(Client::ENC_FORMDATA);
            $this->client->setAuth($this->getUser(), $this->getPassword());
            $this->client->setUri($request->getApiUrl() . $request->getApiEndpoint());
            $this->client->setMethod($request->getApiMethod());

            if (null !== $request->getRequestData() && null !== $request->getRequestData()->getRequestBody()) {
                $this->client->setHeaders(['Content-Type' => 'application/json']);
                $this->client->setRawBody($request->getRequestData()->getRequestBody());
            }

            $response = $this->client->send();

            if ((int)$response->getStatusCode() === 401) {
                throw new AuthorizationException(__('The MyTS credentials used for authentication with the TrustedShops API are incorrect. Ensure you are logged into MyTS within the Magento 2 Backend using the correct credentials of your TrustedShops account. If your MyTS password was recently changed, try logging out and back in.'));
            }
        } catch (Exception $e) {
            $this->helper->logException($e);
        }
        return $response;

    }

    /**
     * Used to manually set the authentication.
     * @param $user
     * @param $password
     */
    public function setAuth($user, $password)
    {
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getUser()
    {
        if (!isset($this->user)) {
            $this->user = $this->helper->getUserInfoEmail();
        }
        return $this->user;
    }

    /**
     * @param string $user
     * @return ApiClient
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        if (!isset($this->password)) {
            $this->password = $this->helper->getUserInfoPassword();
        }
        return $this->password;
    }

    /**
     * @param string $password
     * @return ApiClient
     */
    public function setPassword($password): ApiClient
    {
        $this->password = $password;
        return $this;
    }

}
