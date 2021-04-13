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



abstract class Customweb_Datatrans_AbstractMaintenanceAdapter extends Customweb_Datatrans_AbstractAdapter
{
	protected function checkResponseForErrors($response) {
		
		if (!isset($response['body']['@attributes']['status'])) {
			throw new Exception("No 'status' attribute present on the XML response body.");
		}
		
		if ($response['body']['@attributes']['status'] == 'error' || isset($response['body']['error'])) {
			$error = Customweb_I18n_Translation::__("Cancel failed with an unknown error.");
			if (isset($response['body']['error'])) {
				if (isset($response['body']['error']['errorMessage'])) {
					$error = $response['body']['error']['errorMessage'];
				}
				if (isset($response['body']['error']['errorDetail'])) {
					$error .= ' (' . $response['body']['error']['errorDetail'] . ')';
				}
			}
			throw new Exception($error);
		}
		
		
	
		if (!isset($response['body']['transaction']['@attributes']['trxStatus'])) {
			throw new Exception("No 'trxStatus' attributed set on the XML response.");
		}
	
		if ($response['body']['transaction']['@attributes']['trxStatus'] == 'error') {
			$error = Customweb_I18n_Translation::__("Cancel failed with an unknown error.");
			if (isset($response['body']['transaction']['error']['errorMessage'])) {
				$error = $response['body']['transaction']['error']['errorMessage'];
			}
			if (isset($response['body']['transaction']['error']['errorDetail'])) {
				$error .= ' (' . $response['body']['transaction']['error']['errorDetail'] . ')';
			}
			throw new Exception($error);
		}
	
	}
}