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


class Customweb_Datatrans_Authorization_Hidden_ParameterBuilder extends Customweb_Datatrans_Authorization_AbstractParameterBuilder {

	public function buildParameters(){
		$parameters = $this->getAuthorizationParameters();
		
		$parameters['hiddenMode'] = 'yes';
		
		// Add sign depending on the settings
		if ($this->getConfiguration()->getSecurityLevel() != 'level0') {
			$parameters['sign'] = Customweb_Datatrans_Util::getRequestSign($this->getConfiguration(), $parameters);
		}
		
		return $parameters;
	}
}