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

define([
	'jquery',
	'mage/storage',
	'Magento_Checkout/js/model/url-builder',
	'Magento_Customer/js/model/customer',
	'Magento_Checkout/js/model/quote',
	'Customweb_DatatransCw/js/checkout',
	'Magento_Checkout/js/model/full-screen-loader'
], function(
	$,
	storage,
	urlBuilder,
	customer,
	quote,
	Form,
	fullScreenLoader
) {
	/**
	 * Abstract Authorization Method Class
	 * 
	 * @param string formElement
	 * @param string authorizationUrl
	 */
	var AuthorizationMethod = function(formElement, authorizationUrl) {
		/**
		 * @param int orderId
		 * @return void
		 */
		this.redirect = function(orderId) {
			var serviceUrl,
				payload = {
					orderId: orderId,
					formValues: this.getFormValuesAsMap()
				};
			if (!customer.isLoggedIn()) {
				serviceUrl = urlBuilder.createUrl('/guest-carts/:cartId/datatranscw/checkout/authorize', {
					cartId: quote.getQuoteId()
				});
			} else {
				serviceUrl = urlBuilder.createUrl('/carts/mine/datatranscw/checkout/authorize', {});
			}
			return storage.post(
				serviceUrl, JSON.stringify(payload)
			).done(function(response) {
				if (response.redirection_url) {
					window.location.replace(response.redirection_url);
				} else {
					var fields = {};
					$.each(response.hidden_form_fields, function(index, field) {
						fields[field.key] = field.value;
					});
					var form = new Form(response.form_action_url, fields);
					form.submit();
				}
			});
		}

		/**
		 * @return boolean
		 */
		this.formDataProtected = function() {
			return false;
		}

		/**
		 * @return object
		 */
		this.getFormValues = function() {
			return Form.getValues($(formElement), this.formDataProtected());
		}

		/**
		 * @return object
		 */
		this.getFormValuesAsMap = function() {
			var map = [];
			$.each(this.getFormValues(), function(key, value) {
				map.push({
					key: key,
					value: value
				});
			});
			return map;
		}

		/**
		 * @param int orderId
		 * @return void
		 */
		this.authorize = function(orderId) {
			throw 'Not implemented';
		}
	}

	
	/**
	 * Ajax Authorization Method Class
	 * 
	 * @param string formElement
	 * @param string authorizationUrl
	 */
	AuthorizationMethod.AjaxAuthorization = function(formElement, authorizationUrl) {
		AuthorizationMethod.call(this, formElement, authorizationUrl);

		/**
		 * @override
		 */
		this.formDataProtected = function() {
			return true;
		}

		/**
		 * @override
		 */
		this.authorize = function(orderId) {
			var self = this;

			var serviceUrl;
			if (!customer.isLoggedIn()) {
				serviceUrl = urlBuilder.createUrl('/guest-carts/:cartId/datatranscw/checkout/authorize', {
					cartId: quote.getQuoteId()
				});
			} else {
				serviceUrl = urlBuilder.createUrl('/carts/mine/datatranscw/checkout/authorize', {});
			}
			return storage.post(
				serviceUrl, JSON.stringify({
					orderId: orderId
				})
			).done(function(response) {
				$.getScript(response.ajax_file_url, function() {
					fullScreenLoader.stopLoader();
					var callbackFunction = eval("(" + response.java_script_callback_function + ")");
					callbackFunction(self.getFormValues());
				});
			});
		}
	}
	

	
	/**
	 * Hidden Authorization Method Class
	 * 
	 * @param string formElement
	 * @param string authorizationUrl
	 */
	AuthorizationMethod.HiddenAuthorization = function(formElement, authorizationUrl) {
		AuthorizationMethod.call(this, formElement, authorizationUrl);

		/**
		 * @override
		 */
		this.formDataProtected = function() {
			return true;
		}

		/**
		 * @override
		 */
		this.authorize = function(orderId) {
			var self = this;

			var serviceUrl;
			if (!customer.isLoggedIn()) {
				serviceUrl = urlBuilder.createUrl('/guest-carts/:cartId/datatranscw/checkout/authorize', {
					cartId: quote.getQuoteId()
				});
			} else {
				serviceUrl = urlBuilder.createUrl('/carts/mine/datatranscw/checkout/authorize', {});
			}
			return storage.post(
				serviceUrl, JSON.stringify({
					orderId: orderId
				})
			).done(function(response) {
				var hiddenFields = {};
				$.each(response.hidden_form_fields, function(index, field) {
					hiddenFields[field.key] = field.value;
				});
				var fields = $.extend({}, hiddenFields, self.getFormValues());
				var form = new Form(response.form_action_url, fields);
				form.submit();
			});
		}
	}
	

	
	/**
	 * Iframe Authorization Method Class
	 * 
	 * @param string formElement
	 * @param string authorizationUrl
	 */
	AuthorizationMethod.IframeAuthorization = function(formElement, authorizationUrl) {
		AuthorizationMethod.call(this, formElement, authorizationUrl);

		/**
		 * @override
		 */
		this.authorize = function(orderId) {
			return this.redirect(orderId);
		}
	}
	

	
	/**
	 * Payment Page Authorization Method Class
	 * 
	 * @param string formElement
	 * @param string authorizationUrl
	 */
	AuthorizationMethod.PaymentPage = function(formElement, authorizationUrl) {
		AuthorizationMethod.call(this, formElement, authorizationUrl);

		/**
		 * @override
		 */
		this.authorize = function(orderId) {
			return this.redirect(orderId);
		}
	}
	

	
	/**
	 * Server Authorization Method Class
	 * 
	 * @param string formElement
	 * @param string authorizationUrl
	 */
	AuthorizationMethod.ServerAuthorization = function(formElement, authorizationUrl) {
		AuthorizationMethod.call(this, formElement, authorizationUrl);

		/**
		 * @override
		 */
		this.authorize = function(orderId) {
			var form = new Form(authorizationUrl, this.getFormValues());
			form.submit();
			return $.Deferred();
		}
	}

	

	/**
	 * Authorization Method Collection Function
	 * 
	 * @param string authorizationMethod
	 * @param string formElement
	 * @param string authorizationUrl
	 * @return AuthorizationMethod
	 */
	var Collection = function(authorizationMethod, formElement, authorizationUrl) {
		if (!AuthorizationMethod[authorizationMethod]) {
			throw "No authorization method named '" + authorizationMethod + "' found.";
		}
		return new AuthorizationMethod[authorizationMethod](formElement, authorizationUrl);
	}

	return Collection;
});