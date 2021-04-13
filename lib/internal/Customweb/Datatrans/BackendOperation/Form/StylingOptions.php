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
final class Customweb_Datatrans_BackendOperation_Form_StylingOptions extends Customweb_Payment_BackendOperation_Form_Abstract {
	const STORAGE_SPACE_KEY = 'StylingOptions';

	public static function getStyleFields(){
		return array(
			'brandColor' => array(
				'type' => 'text',
				'label' => Customweb_I18n_Translation::__('Brand Color'),
				'desc' => Customweb_I18n_Translation::__('The color of your brand. The colors can be specified by their hexadecimal code (#FFFFFF) or empty for default value.'),
				'default' => ''
			),
			'textColor' => array(
				'type' => 'select',
				'label' => Customweb_I18n_Translation::__('Header Color'),
				'desc' => Customweb_I18n_Translation::__('The color of the text in the header bar if no logo is given. Possible Values:  &quot;white&quot; or  &quot;black&quot;'),
				'options' => array('white' => 'white', 'black' => 'black'),
				'default' => 'white'
			),
			'logoType' => array(
				'type' => 'select',
				'label' => Customweb_I18n_Translation::__('Logo Style'),
				'desc' => Customweb_I18n_Translation::__("The header logo's display style. Possible values: &quot;circle&quot; , &quot;rectangle&quot; or  &quot;none&quot;"),
				'options' => array('none' => 'none', 'circle' => 'circle', 'rectangle' => 'rectangle'),
				'default' => 'none'
			),
			'logoBorderColor' => array(
				'type' => 'text',
				'label' => Customweb_I18n_Translation::__('Logo Border Color'),
				'desc' => Customweb_I18n_Translation::__('Decides whether the logo shall be styled with a border around it, if the value is true the default background color is chosen, else the provided string is used as color value. Possible values: colors in hexadecimal code (#FFFFFF) or &quot;true&quot; or &quot;false&quot;'),
				'default' => 'false'
			),
			'logoSrc' => array(
				'type' => 'text',
				'label' => Customweb_I18n_Translation::__('Logo Source'),
				'desc' => Customweb_I18n_Translation::__('An SVG image (scalability) provided by the merchant. The image needs to be uploaded by using the Datatrans Web Administration Tool.'),
				'default' => ''
			),
			'brandButton' => array(
				'type' => 'select',
				'label' => Customweb_I18n_Translation::__('Pay Button Color'),
				'desc' => Customweb_I18n_Translation::__('Decides if the pay button should have the same color as the brandColor. Possible Values: &quot;true&quot; or &quot;false&quot;. If set to false the hex color #01669F will be used as a default.'),
				'options' => array('false' => 'false', 'true' => 'true'),
				'default' => 'false'
			),
			'initialView' => array(
				'type' => 'select',
				'label' => Customweb_I18n_Translation::__('Payment Method Selection Style'),
				'desc' => Customweb_I18n_Translation::__('Wheter the payment page shows the payment method selection as list (default) or as a grid. Possible values: &quot;list&quot; or &quot;grid&quot;'),
				'options' => array('list' => 'list', 'grid' => 'grid'),
				'default' => 'list'
			),
		);
	}

	public function isProcessable(){
		return true;
	}

	public function getTitle(){
		return Customweb_I18n_Translation::__("Styling Options");
	}

	public function getElementGroups(){
		$elementGroups = array();
		$elementGroups[] = $this->paymentPageStyle();
		$elementGroups[] = $this->iframeTheme();
		return $elementGroups;
	}

	private function paymentPageStyle(){
		$paymentPageStyle = new Customweb_Form_ElementGroup();
		$paymentPageStyle->setTitle(Customweb_I18n_Translation::__('Redirect / Lightbox Styling'));
		foreach (self::getStyleFields() as $key => $value) {
			$control = null;
			if($value['type'] == 'text') {
				$control = new Customweb_Form_Control_TextInput($key, $this->getPrefillValue($key, $value['default']));
			}
			elseif($value['type'] == 'select') {
				$control = new Customweb_Form_Control_Select($key, $value['options'], $this->getPrefillValue($key, $value['default']));
			}
			$element = new Customweb_Form_Element($value['label'], $control, $value['desc'], false, !$this->getSettingHandler()->hasCurrentStoreSetting($key));
			$paymentPageStyle->addElement($element);
		}
		return $paymentPageStyle;
	}

	private function iframeTheme(){
		$iframeTheme = new Customweb_Form_ElementGroup();
		$iframeTheme->setTitle(Customweb_I18n_Translation::__('Inline Theme Name'));
		$key = 'iframeTheme';
		$control = new Customweb_Form_Control_TextInput($key, $this->getPrefillValue($key, 'mytheme'));
		$element = new Customweb_Form_Element(Customweb_I18n_Translation::__('Iframe Theme Name'), $control,Customweb_I18n_Translation::__('You have to submit a CSS file to Datatrans where the CSS class selector has to match with this value.'), false, !$this->getSettingHandler()->hasCurrentStoreSetting($key));
		$iframeTheme->addElement($element);

		return $iframeTheme;
	}


	private function getPrefillValue($key, $default){
		$stored = $this->getSettingValue($key);
		if ($stored === null) {
			return $default;
		}
		return $stored;
	}

	/**
	 *
	 * @return Customweb_Storage_IBackend
	 */
	private function getStorageBackend(){
		return $this->getContainer()->getBean('Customweb_Storage_IBackend');
	}

	public function getButtons(){
		return array(
			$this->getSaveButton(),
			$this->getResetButton(),
			$this->getDefaultButton()
		);
	}

	private function getResetButton(){
		$button = new Customweb_Form_Button();
		$button->setMachineName("reset")->setTitle(Customweb_I18n_Translation::__("Reset"))->setType(Customweb_Form_IButton::TYPE_CANCEL);
		return $button;
	}

	private function getDefaultButton(){
		$button = new Customweb_Form_Button();
		$button->setMachineName("default")->setTitle(Customweb_I18n_Translation::__("Default Values"))->setType(Customweb_Form_IButton::TYPE_DEFAULT);
		return $button;
	}

	public function process(Customweb_Form_IButton $pressedButton, array $formData){
		if ($pressedButton->getMachineName() === 'save') {
			$this->getSettingHandler()->processForm($this, $formData);
		}
		elseif ($pressedButton->getMachineName() === 'default') {
			$this->getSettingHandler()->processForm($this, array());
		}
	}
}