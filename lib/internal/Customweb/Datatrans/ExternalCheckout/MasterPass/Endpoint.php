<?php

/**
 *  * You are allowed to use this API in your web application.
 *
 * Copyright (C) 2018 by customweb GmbH
 *
 * This program is licenced under the customweb software licence. With the
 * purchase or the installation of the software in your application you
 * accept the licence agreement. The allowed usage is outlined in the
 * customweb software licence which can be found under
 * http://www.sellxed.com/en/software-license-agreement
 *
 * Any modification or distribution is strictly forbidden. The license
 * grants you the installation in one application. For multiuse you will need
 * to purchase further licences at http://www.sellxed.com/shop.
 *
 * See the customweb software licence agreement for more details.
 *
 */




/**
 * @Controller("masterpass")
 */
class Customweb_Datatrans_ExternalCheckout_MasterPass_Endpoint extends Customweb_Payment_ExternalCheckout_AbstractCheckoutEndpoint {
	
	/**
	 *
	 * @var Customweb_Datatrans_Container
	 */
	private $container;

	public function __construct(Customweb_DependencyInjection_IContainer $container){
		parent::__construct($container);
		$this->container = new Customweb_Datatrans_Container($container);
	}

	

	/**
	 * @Action("update-context")
	 */
	public function updateContextAction(Customweb_Core_Http_IRequest $request){
		
		$parameters = $request->getParameters();
		$checkoutService = $this->container->getCheckoutService();
		$this->getTransactionHandler()->beginTransaction();
		$context = $this->loadContextFromRequest($request);
		try{
			$this->checkContextTokenInRequest($request, $context);
		}catch(Customweb_Payment_Exception_ExternalCheckoutTokenExpiredException $e) {
			$this->getCheckoutService()->markContextAsFailed($context, $e->getMessage());
			$this->getTransactionHandler()->commitTransaction();
			return Customweb_Core_Http_Response::redirect($context->getCartUrl());
		}
		try {
			
			// We set already here the payment method to be able to access the
			// setting data in the redirection parameter builder.
			$checkoutService = $this->container->getCheckoutService();
			foreach ($checkoutService->getPossiblePaymentMethods($context) as $method) {
				if (strtolower($method->getPaymentMethodName()) == 'masterpass') {
					$checkoutService->updatePaymentMethod($context, $method);
					break;
				}
			}
			
			if (!isset($parameters['uppTransactionId'])){
				throw new Exception(Customweb_I18n_Translation::__("The checkout process was not successful. Please try again."));
			}
						
			$checkoutService->updateProviderData($context, array('uppTransactionId' => $parameters['uppTransactionId']));

			$billingAddress = $this->getBillingAddressFromParameters($parameters);
			$checkoutService->updateBillingAddress($context, $billingAddress);
			
			$shippingAddress = $this->getShippingAddressFromParameters($parameters);
			$checkoutService->updateShippingAddress($context, $shippingAddress);
			
			$emailAddress = $this->getEmailAddress($parameters);
			
			$this->getTransactionHandler()->commitTransaction();
			
			
			return $checkoutService->authenticate($context, $emailAddress, $this->getConfirmationPageUrl($context, $this->getSecurityTokenFromRequest($request)));
		}
		catch(Exception $e) {
			$this->getCheckoutService()->markContextAsFailed($context, $e->getMessage());
			$this->getTransactionHandler()->commitTransaction();
			return Customweb_Core_Http_Response::redirect($context->getCartUrl());
		}
	}

	/**
	 * @Action("confirmation")
	 */
	public function confirmationAction(Customweb_Core_Http_IRequest $request){
		$context = $this->loadContextFromRequest($request);
		try{
			$this->checkContextTokenInRequest($request, $context);
		}catch(Customweb_Payment_Exception_ExternalCheckoutTokenExpiredException $e) {
			$this->getCheckoutService()->markContextAsFailed($context, $e->getMessage());
			return Customweb_Core_Http_Response::redirect($context->getCartUrl());
		}
		try {
			
			$checkoutService = $this->container->getCheckoutService();
			$parameters = $request->getParameters();
			
			$templateContext = new Customweb_Mvc_Template_RenderContext();
			$confirmationErrorMessage = null;
			$shippingMethodErrorMessage = null;
			$additionalFormErrorMessage = null;
			if (isset($parameters['masterpass_update_shipping'])) {
				try {
					$checkoutService->updateShippingMethod($context, $request);
				}
				catch (Exception $e) {
					$shippingMethodErrorMessage = $e->getMessage();
				}
			}
			else if (isset($parameters['masterpass_confirmation'])) {
				try {
					$checkoutService->processAdditionalFormElements($context, $request);
				} catch (Exception $e) {
					$additionalFormErrorMessage = $e->getMessage();
				}
				if ($additionalFormErrorMessage === null) {
					try {
						$checkoutService->validateReviewForm($context, $request);
						
						$transaction = $checkoutService->createOrder($context);
						if (!$transaction->isAuthorized() && !$transaction->isAuthorizationFailed()) {
							$this->authorizeTransaction($context, $transaction);
						}
						if ($transaction->isAuthorizationFailed()) {
							$confirmationErrorMessage = current($transaction->getErrorMessages());
						}
						else {
							return Customweb_Core_Http_Response::redirect($transaction->getSuccessUrl());
						}
					}
					catch (Exception $e) {
						
						$confirmationErrorMessage = $e->getMessage();
					}
				}
			}
			
			$templateContext->setSecurityPolicy(new Customweb_Mvc_Template_SecurityPolicy());
			$templateContext->setTemplate('checkout/masterpass/confirmation');
			
			$templateContext->addVariable('additionalFormElements', $checkoutService->renderAdditionalFormElements($context, $additionalFormErrorMessage));
			$templateContext->addVariable('shippingPane', $checkoutService->renderShippingMethodSelectionPane($context, $shippingMethodErrorMessage));
			$templateContext->addVariable('reviewPane', $checkoutService->renderReviewPane($context, true, $confirmationErrorMessage));
			$templateContext->addVariable('confirmationPageUrl', $this->getConfirmationPageUrl($context, $this->getSecurityTokenFromRequest($request)));
			$templateContext->addVariable('javascript', $this->getAjaxJavascript('.datatrans-masterpass-shipping-pane', '.datatrans-masterpass-confirmation-pane'));
			
			$content = $this->getTemplateRenderer()->render($templateContext);
			
			$layoutContext = new Customweb_Mvc_Layout_RenderContext();
			$layoutContext->setTitle(Customweb_I18n_Translation::__('MasterPass: Order Confirmation'));
			$layoutContext->setMainContent($content);
			return $this->getLayoutRenderer()->render($layoutContext);
			
		}
		catch(Exception $e) {
			$this->getCheckoutService()->markContextAsFailed($context, $e->getMessage());
			return Customweb_Core_Http_Response::redirect($context->getCartUrl());
		}
	}

	private function authorizeTransaction(Customweb_Payment_ExternalCheckout_IContext $context, Customweb_Datatrans_Authorization_Transaction $transaction){
		$this->getTransactionHandler()->beginTransaction();
		try {
			$providerData = $context->getProviderData();
			$transaction->setPaymentId($providerData['uppTransactionId']);
			$builder = new Customweb_Datatrans_ExternalCheckout_MasterPass_ConfirmationRequestParameterBuilder($this->container, $context, $transaction);
			$body = $builder->build();
			$url = $this->container->getConfiguration()->getBaseUrlJsp().'XML_authorizeMpw.jsp';
			
			$response = $this->sendXMLToRemoteHost($url, $body);
			// Parse response:
			$errorHandler = set_error_handler(array('Customweb_Datatrans_Util', 'xmlParserErrorHandler'));
			$responseArray = Customweb_Datatrans_Util::convertXmlObjectToArray(simplexml_load_string(trim($response->getBody())));
			// Check if something goes horribly wrong
			if (isset($responseArray['error'])) {
				$errorMessage = 'No error details provided.';
				if (isset($response['error']['errorMessage'])) {
					$errorMessage = $responseArray['error']['errorMessage'];
					if (isset($response['error']['errorDetail'])) {
						$errorMessage .= ' (' . $responseArray['error']['errorDetail'] . ')';
					}
				}
				throw new Exception("XML Request failed: " . $errorMessage);
			}
			// Set back to the original handler
			restore_error_handler();
			
			$authParameters = Customweb_Datatrans_Util::getAuthorizationParametersFromXmlResponse($responseArray);
			$authParameters['merchantId'] = $this->container->getConfiguration()->getMerchantId();
			$authParameters['refno'] = $context->getContextId();
			$transaction->setAuthorizationParameters($authParameters);
			
			if($authParameters['status'] != 'success') {
				$failedMessage = '';
				if (isset($authParameters['errorMessage'])) {
					$failedMessage = Customweb_I18n_Translation::__("Payment Error Message: !message",
							array(
								'!message' => $authParameters['errorMessage']
							));
					if (isset($authParameters['errorDetail'])) {
						$failedMessage .= ' (' . $authParameters['errorDetail'] . ')';
					}
				}
				else {
					$failedMessage = Customweb_I18n_Translation::__("The transaction failed with an unkown error.");
				}
				$transaction->setAuthorizationFailed(new Customweb_Payment_Authorization_ErrorMessage(Customweb_I18n_Translation::__("The transaction was declined."), $failedMessage));
				$transaction->setUpdateExecutionDate(null);
			}
			else {
				$transaction->authorize();
				$transaction->setUpdateExecutionDate(null);
				if ($transaction->isDirectCapturingActive()) {
					$transaction->capture();
				}
				
			}
		}
		catch (Exception $e) {
			$transaction->setAuthorizationFailed(new Customweb_Payment_Authorization_ErrorMessage(Customweb_I18n_Translation::__("The transaction was declined."), $e->getMessage()));
		}
		$this->getTransactionHandler()->persistTransactionObject($transaction);
		$this->getTransactionHandler()->commitTransaction();
	}


	private function getShippingAddressFromParameters(array $parameters){
	
		$requiredParameters = array(
			'uppShippingCity',
			'uppShippingCountry',
			'uppShippingStreet',
			'uppShippingZipCode',
			'uppShippingFirstName',
			'uppShippingLastName',
			'uppCustomerEmail',
			'uppCustomerPhone'
		);
		foreach ($requiredParameters as $parameterName) {
			if (!isset($parameters[$parameterName])) {
				throw new Exception("Parameter $parameterName is missing.");
			}
		}
		
		$shippingAddress = new Customweb_Payment_Authorization_OrderContext_Address_Default();
				
		// @formatter:off
		$shippingAddress
		->setFirstName($parameters['uppShippingFirstName'])
		->setLastName($parameters['uppShippingLastName'])
		->setStreet($parameters['uppShippingStreet'])
		->setCity($parameters['uppShippingCity'])
		->setCountryIsoCode($parameters['uppShippingCountry'])
		->setPostCode($parameters['uppShippingZipCode'])
		->setPhoneNumber($parameters['uppCustomerPhone'])
		->setMobilePhoneNumber($parameters['uppCustomerPhone'])
		->setEMailAddress($parameters['uppCustomerEmail']);	
		// @formatter:on
		
		if(isset($parameters['uppShippingCountrySubdivision'])) {
			$subDivisions = explode('-', $parameters['uppShippingCountrySubdivision']);
			$shippingAddress->setState(end($subDivisions));
		}
		
		return $shippingAddress;
	}

	private function getBillingAddressFromParameters(array $parameters){
		
		$requiredParameters = array(
			'uppBillingCity',
			'uppBillingCountry',
			'uppBillingStreet',
			'uppBillingZipCode',
			'uppCustomerFirstName',
			'uppCustomerLastName',
			'uppCustomerEmail',
			'uppCustomerPhone'
		);
		foreach ($requiredParameters as $parameterName) {
			if (!isset($parameters[$parameterName])) {
				throw new Exception("Parameter $parameterName is missing.");
			}
		}
		
		$billingAddress = new Customweb_Payment_Authorization_OrderContext_Address_Default();
		
		// @formatter:off
		$billingAddress
		->setFirstName($parameters['uppCustomerFirstName'])
		->setLastName($parameters['uppCustomerLastName'])
		->setStreet($parameters['uppBillingStreet'])
		->setCity($parameters['uppBillingCity'])
		->setCountryIsoCode($parameters['uppBillingCountry'])
		->setPostCode($parameters['uppBillingZipCode'])
		->setPhoneNumber($parameters['uppCustomerPhone'])
		->setMobilePhoneNumber($parameters['uppCustomerPhone'])
		->setEMailAddress($parameters['uppCustomerEmail']);	
		// @formatter:on
		
		if(isset($parameters['uppBillingCountrySubdivision'])) {
			$subDivisions = explode('-', $parameters['uppBillingCountrySubdivision']);
			$billingAddress->setState(end($subDivisions));
		}
		
		return $billingAddress;
		
	}
	
	private function getEmailAddress(array $parameters) {
		if(!isset($parameters['uppCustomerEmail'])) {
			throw new Exception("Parameter uppCustomerEmail is missing.");
		}
		return $parameters['uppCustomerEmail'];
	}

	private function getConfirmationPageUrl(Customweb_Payment_ExternalCheckout_IContext $context, $token){
		return $this->getUrl('masterpass', 'confirmation',
				array(
					'context-id' => $context->getContextId(),
					'token' => $token,
				));
	}
	
	/**
	 * @return Customweb_Datatrans_Method_Factory
	 */
	protected function getMethodFactory() {
		return $this->getContainer()->getBean('Customweb_Datatrans_Method_Factory');
	}
	
	protected function getPaymentMethodByTransaction(Customweb_Datatrans_Authorization_Transaction $transaction){
		return $this->getMethodFactory()->getPaymentMethod($transaction->getTransactionContext()->getOrderContext()->getPaymentMethod(), $transaction->getAuthorizationMethod());
	}
	
	private function sendXMLToRemoteHost($url, $body) {
		$request = new Customweb_Core_Http_Request($url);
		$request->setMethod('POST');
		$request->setBody($body);
		$request->setContentType('application/xml');

		$client = Customweb_Core_Http_Client_Factory::createClient();
		
		$response = $client->send($request);
		return $response;
	}
	
	/**
	 * @Action("dummy")
	 */
	public function dummyAction(Customweb_Core_Http_IRequest $request){
		return Customweb_Core_Http_Response::_('Unkown Error');
	}
	
	/**
	 * @Action("error")
	 */
	public function errorAction(Customweb_Core_Http_IRequest $request){
		
		$parameters = $request->getParameters();
		$checkoutService = $this->container->getCheckoutService();
		$this->getTransactionHandler()->beginTransaction();
		$context = $this->loadContextFromRequest($request);
		try{
			$this->checkContextTokenInRequest($request, $context);
		}catch(Customweb_Payment_Exception_ExternalCheckoutTokenExpiredException $e) {
			//Ignore expired exception
		}
		$message = Customweb_I18n_Translation::__("An error ocurred.");
		if(isset($parameters['errorMessage'])){
			$message = $parameters['errorMessage'];
		}
		if(isset($parameters['errorCode'])){
			$message .= ' '.Customweb_I18n_Translation::__('ErrorCode: !code', array('!code' => $parameters['errorCode']));
		}
		$this->getCheckoutService()->markContextAsFailed($context, $message);
		$this->getTransactionHandler()->commitTransaction();
		return Customweb_Core_Http_Response::redirect($context->getCartUrl());
	}
}