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
 * @Bean
 */
class Customweb_Datatrans_Authorization_Ajax_Adapter extends Customweb_Datatrans_Authorization_AbstractRedirectionAdapter implements
		Customweb_Payment_Authorization_Ajax_IAdapter {

	public function getAuthorizationMethodName(){
		return self::AUTHORIZATION_METHOD_NAME;
	}

	public function getAdapterPriority(){
		return 200;
	}

	public function createTransaction(Customweb_Payment_Authorization_Ajax_ITransactionContext $transactionContext, $failedTransaction){
		$this->checkRecurring($transactionContext);
		$transaction = new Customweb_Datatrans_Authorization_Transaction($transactionContext);
		$transaction->setAuthorizationMethod(self::AUTHORIZATION_METHOD_NAME);
		$transaction->createRoutingUrls($this->getContainer());
		$transaction->setLiveTransaction(!$this->getConfiguration()->isTestMode());
		return $transaction;
	}

	public function getAjaxFileUrl(Customweb_Payment_Authorization_ITransaction $transaction){
		return (string) $this->getContainer()->getAssetResolver()->resolveAssetUrl('dummy.js');
	}

	public function getJavaScriptCallbackFunction(Customweb_Payment_Authorization_ITransaction $transaction){
		$builder = new Customweb_Datatrans_Authorization_Ajax_ParameterBuilder($transaction, $this->getContainer(), array());
		$params = $builder->buildParameters();
		$url = $this->getContainer()->getEndpointAdapter()->getUrl('process', 'abortwidget',
				array(
					'cwDataTransId' => $transaction->getExternalTransactionId()
				));

		$execute = 'var form = document.createElement("form");
					form.setAttribute("id","cwPaymentForm");
					form.setAttribute("style", "display:none;");';
		foreach ($params as $key => $value) {
			$execute .= 'form.setAttribute("'.str_replace('"', '\"', $key).'", "'.str_replace('"', '\"', $value).'");';
		}
		$jsUrl = $this->getConfiguration()->getLightBoxJsUrl();
		$jsUrlWithoutFileExtension = substr($jsUrl, 0, -3);


		$fallback = Customweb_Util_JavaScript::loadScript($jsUrl, 'function() { return typeof Datatrans !== "undefined"; }', 'function() {Datatrans.startPayment({
							"form": "#cwPaymentForm",
							"closed" : function(){window.location = "' . $url . '"},
						});}');
		$execute .= 'document.getElementsByTagName("body")[0].appendChild(form);
					if(typeof window.jQuery == "undefined") {
						window.jQuery = cwjQuery;
					}
					if (typeof define === "function" && define.amd && typeof require === "function") {
						window.require.config({
						    paths: {
						        "DatatransCw": "' . $jsUrlWithoutFileExtension. '"
						    }
						});
						window.require(["DatatransCw"], function(Datatrans) {
							Datatrans.startPayment({
								"form": "#cwPaymentForm",
								"closed" : function(){window.location = "' . $url . '"},
							});
						});
					}
					else {
						' . $fallback . '
					}';

		$complete = "function(formFieldValues) { var datatransFormFields = formFieldValues; if(typeof window.jQuery == 'undefined') {" .
				 Customweb_Util_JavaScript::getLoadJQueryCode('1.11.2', 'cwjQuery', 'function(){' . $execute . '}') . '} else {' . $execute . '}}';
		return $complete;
	}

	public function getVisibleFormFields(Customweb_Payment_Authorization_IOrderContext $orderContext, $aliasTransaction, $failedTransaction, $customerPaymentContext){
		return $this->getContainer()->getPaymentMethod($orderContext->getPaymentMethod(), $this->getAuthorizationMethodName())->getFormFields(
				$orderContext, $aliasTransaction, $failedTransaction, $this->getAuthorizationMethodName(), false, $customerPaymentContext);
	}
}
