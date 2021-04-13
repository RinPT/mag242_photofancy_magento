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
 *
 * @category	Customweb
 * @package		Customweb_DatatransCw
 * 
 */

namespace Customweb\DatatransCw\Model\System\Message;

class LicenseFailed implements \Magento\Framework\Notification\MessageInterface
{
	/**
	 * Retrieve unique message identity
     *
     * @return string
     */
    public function getIdentity()
    {
        return md5('datatranscw-license');
    }

    /**
     * Check whether
     *
     * @return bool
     */
        public function isDisplayed()
    {
		
		$arguments = null;
		return \Customweb_Licensing_DatatransCw_License::run('p5i4p5jsoc7fcjts', $this, $arguments);
	}

	final public function call_7b1v5r4fg9jgopi7() {
		$arguments = func_get_args();
		$method = $arguments[0];
		$call = $arguments[1];
		$parameters = array_slice($arguments, 2);
		if ($call == 's') {
			return call_user_func_array(array(get_class($this), $method), $parameters);
		}
		else {
			return call_user_func_array(array($this, $method), $parameters);
		}
		
		
	}

    /**
     * Retrieve message text
     *
     * @return string
     */
        public function getText()
    {
		
		$arguments = null;
		return \Customweb_Licensing_DatatransCw_License::run('72595cj0m7vpufe3', $this, $arguments);
	}

	final public function call_jkilhjreuhhg9qo6() {
		$arguments = func_get_args();
		$method = $arguments[0];
		$call = $arguments[1];
		$parameters = array_slice($arguments, 2);
		if ($call == 's') {
			return call_user_func_array(array(get_class($this), $method), $parameters);
		}
		else {
			return call_user_func_array(array($this, $method), $parameters);
		}
		
		
	}

    /**
     * Retrieve message severity
     *
     * @return int
     */
    public function getSeverity()
    {
        return \Magento\Framework\Notification\MessageInterface::SEVERITY_CRITICAL;
    }
}
####licenseEncrypt####