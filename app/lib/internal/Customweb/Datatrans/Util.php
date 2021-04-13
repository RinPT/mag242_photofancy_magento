<?php
/**
  * You are allowed to use this API in your web application.
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
 * This util class some basic functions for Datatrans.
 *
 * @author Thomas Hunziker
 *
 */
final class Customweb_Datatrans_Util {

	private function __construct() {
		// prevent any instantiation of this class
	}


	public static function formatAmount($amount, $currency) {
		return Customweb_Util_Currency::formatAmount($amount, $currency, '', '');
	}

	public static function getFloatFromCallbackAmount($amount, $currency) {
		$decimalPlaces = Customweb_Util_Currency::getDecimalPlaces($currency);
		$amount = floatval($amount);
		return $amount / (pow(10, $decimalPlaces));
	}


	public static function getCleanLanguageCode($lang) {
		$supportedLanguages = array('de_DE','en_US','fr_FR','da_DK',
			'es_ES','it_IT','no_NO','el_GR', 'ja_JP'
		);
		return substr(Customweb_Payment_Util::getCleanLanguageCode($lang, $supportedLanguages), 0, 2);
	}

	public static function isAliasManagerActive($aliasTransaction, Customweb_Payment_Authorization_IOrderContext $currentOrderContext,
			Customweb_Datatrans_Configuration $configuration) {
				if ($aliasTransaction === null) {
					return false;
				}

				if (is_object($aliasTransaction) && $currentOrderContext->getPaymentMethod()->existsPaymentMethodConfigurationValue('user_identification') &&
						$currentOrderContext->getPaymentMethod()->getPaymentMethodConfigurationValue('user_identification') == 'shipping') {
							$initialOrderContext = $aliasTransaction->getTransactionContext()->getOrderContext();
							if (!Customweb_Util_Address::compareShippingAddresses($initialOrderContext, $currentOrderContext)) {
								return false;
							}
						}

						return true;
	}

	public static function getRequestSign(Customweb_Datatrans_Configuration $configuration, array $parameters, $isMoto = false) {

		// Use the merchant id in the $parameters, if it is set.
		if (isset($parameters['merchantId'])) {
			$merchantId = $parameters['merchantId'];
		}
		else {
			if ($isMoto) {
				$merchantId = $configuration->getMotoMerchantId();
			}
			else {
				$merchantId = $configuration->getMerchantId();
			}
		}
		$password = $configuration->getSignByMerchantId($merchantId);

		$level = strtolower($configuration->getSecurityLevel());
		switch($level) {
			case 'level0':
				throw new Exception("For security level 0 the request can not be signed.");
			case 'level1':
				return $password;
			case 'level2':
			case 'level2sha256':
				$currency = '';
				if(isset($parameters['currency'])){
					$currency = $parameters['currency'];
				}
				$refno = '';
				if(isset($parameters['refno'])) {
					$refno = $parameters['refno'];
				}
				$hashMethod = 'sha256';
				$stringToHash = $merchantId . $parameters['amount'] . $currency . $refno ;
				return hash_hmac($hashMethod, $stringToHash, pack("H*", $password));
			default:
				throw new Exception("Unkown security level.");
		}
	}

	public static function getResponseSign(Customweb_Datatrans_Configuration $configuration, array $parameters, $isMoto = false) {
		$level = strtolower($configuration->getSecurityLevel());
		if (isset($parameters['merchantId'])) {
			$merchantId = $parameters['merchantId'];
		}
		else {
			if ($isMoto) {
				$merchantId = $configuration->getMotoMerchantId();
			}
			else {
				$merchantId = $configuration->getMerchantId();
			}
		}
		$password = $configuration->getSign2ByMerchantId($merchantId);
		if (empty($password)) {
			$password = $configuration->getSignByMerchantId($merchantId);
		}

		switch($level) {
			case 'level0':
				throw new Exception("For security level 0 the response can not be signed.");
			case 'level1':
				return $password;
			case 'level2':
			case 'level2sha256':
				$currency = '';
				if(isset($parameters['currency'])){
					$currency = $parameters['currency'];
				}
				$uppTransactionId = '';
				if(isset($parameters['uppTransactionId'])) {
					$uppTransactionId = $parameters['uppTransactionId'];
				}

				$hashMethod = 'sha256';
				$stringToHash = $merchantId . $parameters['amount'] . $currency . $uppTransactionId ;
				return hash_hmac($hashMethod, $stringToHash, pack("H*", $password));
			default:
				throw new Exception("Unkown security level.");
		}
	}

	public static function signXmlRequest(Customweb_Datatrans_Configuration $configuration, Customweb_Datatrans_XmlRequest $xmlRequest) {
		if ($configuration->isSettlementSignActive()) {
			$parameters = $xmlRequest->getParameters();
			$parameters['refno'] = $xmlRequest->getReferenceNumber();
			$parameters['merchantId'] = $xmlRequest->getMerchantId();
			if(strtolower($configuration->getSecurityLevel()) != 'level0') {
				$sign = self::getRequestSign($configuration, $parameters);
				$xmlRequest->addParameter('sign', $sign);
			}
		}
	}

	public static function getAuthorizationParametersFromXmlResponse($response){
		$authorizationParameters = array();
		if (!isset($response['body']['@attributes']['status'])) {
			throw new Exception("No 'status' attribute present on the XML response body.");
		}

		if ($response['body']['@attributes']['status'] == 'error' || isset($response['body']['transaction']['error'])) {
			$authorizationParameters['status'] = 'error';
			if (isset($response['body']['transaction']['error'])) {
				$authorizationParameters = array_merge($authorizationParameters, $response['body']['transaction']['error']);
			}
		}
		else if ($response['body']['@attributes']['status'] == 'accepted') {
			$authorizationParameters['status'] = 'success';

			if (isset($response['body']['transaction']['request'])) {
				$request = $response['body']['transaction']['request'];

				// Remove critical data
				unset($request['bankaccount']);
				unset($request['bankrouting']);
				unset($request['cardno']);
				unset($request['cvv']);

				$authorizationParameters = array_merge($authorizationParameters, $request);
			}

			if (isset($response['body']['transaction']['response'])) {
				$authorizationParameters = array_merge($authorizationParameters, $response['body']['transaction']['response']);
			}
		}

		return $authorizationParameters;
	}

	public static function sendXmlRequest($url, Customweb_Datatrans_XmlRequest $xmlRequest) {
		$logger = Customweb_Core_Logger_Factory::getLogger(get_class($xmlRequest));
		
		$request = new Customweb_Http_Request($url);
		$request->setBody($xmlRequest->toXml());
		$request->setMethod('POST');
		$request->appendCustomHeaders(array('Content-Type' => 'application/xml'));
		$logger->logDebug("Sending XML request", $request);
		$request->send();
		$handler = $request->getResponseHandler();
		if ($handler->getStatusCode() != '200') {
			throw new Exception("The server response with a invalid HTTP status code (status code != 200).");
		}

		$body = $handler->getBody();
		$logger->logDebug("Processing XML response", $body);

		// Parse response:
		$errorHandler = set_error_handler(array('Customweb_Datatrans_Util', 'xmlParserErrorHandler'));

		$response = self::convertXmlObjectToArray(simplexml_load_string(trim($body)));

		// Check if something goes horribly wrong
		if (isset($response['error'])) {
			$errorMessage = 'No error details provided.';
			if (isset($response['error']['errorMessage'])) {
				$errorMessage = $response['error']['errorMessage'];
				if (isset($response['error']['errorDetail'])) {
					$errorMessage .= ' (' . $response['error']['errorDetail'] . ')';
				}
			}
			throw new Exception("XML Request failed: " . $errorMessage);
		}

		// Set back to the original handler
		restore_error_handler();

		return $response;
	}

	public static function convertXmlObjectToArray($object) {
		$array = (array)$object;
		foreach ($array as $key => $element) {
			if ($element instanceof SimpleXMLElement) {
				$array[$key] = self::convertXmlObjectToArray($element);
			}
			if (is_array($array[$key]) && count($array[$key]) <= 0) {
				unset($array[$key]);
			}
		}
		return $array;
	}

	public static function xmlParserErrorHandler($errno, $errstr, $errfile, $errline) {
		throw new Exception("Failed to parse XML Response. Error Message: " . $errstr);
	}


	public static function fixCustomerParametersForRemoteRequest($parameters){
		if(isset($parameters['uppCustomerDetails'])){
			$parameters['uppCustomerDetails'] = array();
		}
		// We have to move the customer data into the tag 'uppCustomerDetails':
		foreach ($parameters as $key => $value) {
			if ((strstr($key, 'uppCustomer') && $key !== 'uppCustomerDetails') ||
					(strstr($key, "uppShipping"))) {
						unset($parameters[$key]);
						$parameters['uppCustomerDetails'][$key] = $value;
					}
		}
		return $parameters;
	}


	public static function ensureStringInArray(array $input){
		$output = array();
		foreach($input as $key => $data){
			if(is_array($data)){
				$output[$key] = self::ensureStringInArray($data);
			}
			elseif(!is_string($data)){
				$output[$key] = (string) $data;
			}
			else{
				$output[$key] = $data;
			}
		}
		return $output;

	}


}