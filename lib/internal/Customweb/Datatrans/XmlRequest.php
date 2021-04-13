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

class Customweb_Datatrans_XmlRequest
{
	private $merchantId;
	private $referenceNumber = NULL;
	private $parameters = array();
	private $testOnly = 'no';
	private $authorization = false;

	public function __construct($merchantId) {
		$this->merchantId = $merchantId;
	}
	
	public function setTestOnly($testOnly = 'yes') {
		$this->testOnly = $testOnly;
		return $this;
	}
	
	public function getTestOnly() {
		return $this->testOnly;
	}
	
	public function isAuthorizationRequest() {
		return $this->authorization;
	}
	
	public function setAuthorizationRequest($authorization = true) {
		$this->authorization = $authorization;
		return $this;
	}

	/**
	 * @return String
	 */
	public function getMerchantId() {
		return $this->merchantId;
	}

	/**
	 * @param string $referenceNumber
	 * @return Customweb_Datatrans_XmlRequest
	 */
	public function setReferenceNumber($referenceNumber) {
		$this->referenceNumber = $referenceNumber;
		return $this;
	}

	public function getReferenceNumber() {
		return $this->referenceNumber;
	}
	
	public function getParameters() {
		return $this->parameters;
	}

	/**
	 * @param array $parameters
	 * @return Customweb_Datatrans_XmlRequest
	 */
	public function setParameters(array $parameters) {
		$this->parameters = $parameters;
		return $this;
	}
	
	/**
	 * 
	 * @param string $key
	 * @param string|array $value
	 * @return Customweb_Datatrans_XmlRequest
	 */
	public function addParameter($key, $value) {
		$this->parameters[$key] = $value;
		return $this;
	}
	
	public function toXml() {
		$output = '<?xml version="1.0" encoding="UTF-8" ?>';
		if ($this->isAuthorizationRequest()) {
			$output .= '<authorizationService version="1">';
		}
		else {
			$output .= '<paymentService version="1">';
		}
		
		$output .= '<body merchantId="' . $this->getMerchantId() . '" testOnly="' . $this->getTestOnly() . '">';
		if ($this->getReferenceNumber() !== NULL) {
			$output .= '<transaction refno="' . $this->getReferenceNumber() . '">';
		}
		else {
			$output .= '<transaction>';
		}
		$output .= '<request>';
		
		$output .= self::arrayToXml($this->getParameters());
		
		$output .= '</request>';
		$output .= '</transaction>';
		$output .= '</body>';
		
		if ($this->isAuthorizationRequest()) {
			$output .= '</authorizationService>';
		}
		else {
			$output .= '</paymentService>';
		}
		

		return $output;
	}
	
	private static function arrayToXml(array $params) {
		$return = '';
		
		foreach ($params as $key => $value) {
			$return .= '<' . $key . '>';
			if (is_array($value)) {
				$return .= self::arrayToXml($value);
			}
			else {
				$return .= '<![CDATA[' . $value . ']]>';
			}
			$return .= '</' . $key . '>';
		}
		
		return $return;
	}


}