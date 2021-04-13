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
 * @BackendForm
 */
class Customweb_Datatrans_BackendOperation_Form_Setup extends Customweb_Payment_BackendOperation_Form_Abstract {

	public function getTitle(){
		return Customweb_I18n_Translation::__("Setup");
	}

	public function getElementGroups(){
		return array(
			$this->getSetupGroup(),
			$this->getUrlGroup() 
		);
	}

	private function getSetupGroup(){
		$group = new Customweb_Form_ElementGroup();
		$group->setTitle(Customweb_I18n_Translation::__("Short Installation Instructions:"));
		
		$control = new Customweb_Form_Control_Html('description', 
				Customweb_I18n_Translation::__(
						'This is a brief instruction of the main and most important installation steps, which need to be performed when installing the Datatrans module. For detailed instructions regarding additional and optional settings, please refer to the enclosed instructions in the zip. '));
		$element = new Customweb_Form_WideElement($control);
		$group->addElement($element);
		
		$control = new Customweb_Form_Control_Html('steps', $this->createOrderedList($this->getSteps()));
		
		$element = new Customweb_Form_WideElement($control);
		$group->addElement($element);
		return $group;
	}

	private function getUrlGroup(){
		$group = new Customweb_Form_ElementGroup();
		$group->setTitle('URLs');
		$group->addElement($this->getNotificationUrlElement());
		return $group;
	}

	private function getNotificationUrlElement(){
		$control = new Customweb_Form_Control_Html('notificationURL', $this->getEndpointAdapter()->getUrl('process', 'index'));
		$element = new Customweb_Form_Element(Customweb_I18n_Translation::__("Notification URL"), $control);
		$element->setDescription(
				Customweb_I18n_Translation::__(
						"This URL has to be placed in the backend of Datatrans under UPP Administration > URL Post."));
		return $element;
	}

	private function getSteps(){
		return array(
			Customweb_I18n_Translation::__('Enter the Live MerchantID.'),
			Customweb_I18n_Translation::__(
					'Set the sign1, sign2 and the settlement parameter in the module exactly the same way as in the security configuration at Datatrans.'),
			Customweb_I18n_Translation::__(
					'Copy the URL Post that you find below (notification URL) into the Datatrans Backend (Section UPP Administration > UPP Data).'),
			Customweb_I18n_Translation::__('Activate the payment method and test.') 
		);
	}

	private function createOrderedList(array $steps){
		$list = '<ol>';
		foreach ($steps as $step) {
			$list .= "<li>$step</li>";
		}
		$list .= '</ol>';
		return $list;
	}
}