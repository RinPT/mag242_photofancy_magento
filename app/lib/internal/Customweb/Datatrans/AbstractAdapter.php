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


class Customweb_Datatrans_AbstractAdapter {
	/**
	 *
	 * @var Customweb_Datatrans_Container
	 */
	private $container = null;

	public function __construct(Customweb_DependencyInjection_IContainer $container){
		if (!function_exists('simplexml_load_string')) {
			throw new Exception("The library 'libxml' must be activated in your webserver.");
		}
		
		$this->container = new Customweb_Datatrans_Container($container);
	}

	/**
	 * Returns the configuration object.
	 *
	 * @return Customweb_Datatrans_Configuration
	 */
	public function getConfiguration(){
		return $this->container->getConfiguration();
	}

	/**
	 *
	 * @return Customweb_Datatrans_Container
	 */
	public function getContainer(){
		return $this->container;
	}

	
}