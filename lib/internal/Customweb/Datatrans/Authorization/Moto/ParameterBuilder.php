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


class Customweb_Datatrans_Authorization_Moto_ParameterBuilder extends Customweb_Datatrans_Authorization_AbstractParameterBuilder {

	public function buildParameters(){
		$parameters = $this->getAuthorizationParameters();
		
		if ($this->getTransaction()->isHiddenModeUsed()) {
			$parameters['hiddenMode'] = 'yes';
		}
		
		// Prevent any creation of alias 
		if ($this->getTransactionContext()->getAlias() == 'new') {
			unset($parameters['aliasCC']);
			unset($parameters['useAlias']);
		}
		
		// Add sign depending on the settings
		if ($this->getConfiguration()->getSecurityLevel() != 'level0') {
			$parameters['sign'] = Customweb_Datatrans_Util::getRequestSign($this->getConfiguration(), $parameters, true);
		}
		
		return $parameters;
	}

	protected function getReactionUrlParameters(){
		
		// We send the user to the notification URL, because in some error states no
		// callback is done in the background.
		$failedUrl = Customweb_Util_Url::appendParameters($this->getTransaction()->getProcessAuthorizationUrl(), 
				$this->getTransactionContext()->getCustomParameters());
		
		return array(
			'successUrl' => $failedUrl,
			'errorUrl' => $failedUrl,
			'cancelUrl' => $failedUrl 
		);
	}

	protected function getMerchantIdParameters(){
		return array(
			'merchantId' => $this->getConfiguration()->getMotoMerchantId() 
		);
	}
}