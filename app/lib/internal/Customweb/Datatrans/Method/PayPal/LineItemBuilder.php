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
 *
 * @author Sebastian Bossert
 */
class Customweb_Datatrans_Method_PayPal_LineItemBuilder {
	private $lineItems;
	private $currency;

	public function __construct(array $lineItems, $currency){
		$this->lineItems = $lineItems;
		$this->currency = $currency;
	}

	public function build(){
		$parameters = array();
		$i = 0;
		$shipping = 0;
		// sum total and tax to avoid rounding errors
		$total = 0;
		$tax = 0;
		foreach ($this->lineItems as $item) {
			/* @var $item Customweb_Payment_Authorization_IInvoiceItem */
			if ($item->getType() != Customweb_Payment_Authorization_IInvoiceItem::TYPE_SHIPPING) {
				$price = Customweb_Util_Currency::formatAmount($item->getAmountExcludingTax(), $this->currency, '');
				$price = $item->getType() == Customweb_Payment_Authorization_IInvoiceItem::TYPE_DISCOUNT ? -abs($price) : $price;
				$total += $price;
				$parameters["L_AMT$i"] = $price;
				
				$taxAmount = Customweb_Util_Currency::formatAmount($item->getTaxAmount(), $this->currency, '');
				$taxAmount = $item->getType() == Customweb_Payment_Authorization_IInvoiceItem::TYPE_DISCOUNT ? -abs($taxAmount) : $taxAmount;
				$tax += $taxAmount;
				$parameters["L_TAXAMT$i"] = $taxAmount;
				
				$parameters["L_NAME$i"] = Customweb_Core_String::_($item->getName())->substring(0, 20)->toString();
				$parameters["L_Number$i"] = $i;
				$parameters["L_Desc$i"] = Customweb_Core_String::_(round($item->getQuantity(), 2) . " " . $item->getName())->substring(0, 20)->toString();
				$i++;
			}
			else {
				$shipping += $item->getAmountIncludingTax();
			}
		}
		$shipping = Customweb_Util_Currency::formatAmount($shipping, $this->currency, '');
		
		$shipping = ltrim($shipping, "0");
		if (empty($shipping)) {
			$shipping = 0;
		}
		$allInclusive = Customweb_Util_Currency::formatAmount(Customweb_Util_Invoice::getTotalAmountIncludingTax($this->lineItems), $this->currency, 
				'');
		
		$adjustmentAmount = $allInclusive - $shipping - $tax - $total;
		if ($adjustmentAmount != 0) {
			$parameters["L_AMT$i"] = $adjustmentAmount;
			$parameters["L_TAXAMT$i"] = 0;
			$parameters["L_NAME$i"] = Customweb_I18n_Translation::__("Rounding adjustment")->toString();
			$parameters["L_Number$i"] = $i;
			$parameters["L_Desc$i"] = Customweb_I18n_Translation::__("Rounding adjustment.")->toString();
			$total += $adjustmentAmount;
		}
		
		$parameters["SHIPPINGAMT"] = $shipping;
		$parameters['ITEMAMT'] = ltrim($total, "0");
		$parameters['TAXAMT'] = ltrim($tax, "0");
		
		if (Customweb_Util_Currency::compareAmount($shipping + $total + $tax, $allInclusive, $this->currency) != 0) {
			// should no longer be possible, but is better than the alternative
			throw new Exception(Customweb_I18n_Translation::__("The total amount could not be correctly calculated. Please contact the shop owner."));
		}

		return $parameters;
	}
}