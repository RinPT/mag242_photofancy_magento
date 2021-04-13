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

namespace Customweb\DatatransCw\Model\Config\Source;

class RepaymentType implements \Magento\Framework\Option\ArrayInterface
{
	/**
	 * @return array
	 */
	public function toOptionArray()
	{
		return [
			['value' => '1', 'label' => __('1')],
			['value' => '2', 'label' => __('2')],
			['value' => '3', 'label' => __('3')],
			['value' => '4', 'label' => __('4')],
			['value' => '5', 'label' => __('5')],
			['value' => '6', 'label' => __('6')],
			['value' => '7', 'label' => __('7')],
			['value' => '8', 'label' => __('8')],
			['value' => '9', 'label' => __('9')],
			['value' => '10', 'label' => __('10')],
			['value' => '11', 'label' => __('11')],
			['value' => '12', 'label' => __('12')],
			['value' => '13', 'label' => __('13')],
			['value' => '14', 'label' => __('14')],
			['value' => '15', 'label' => __('15')],
			['value' => '16', 'label' => __('16')],
			['value' => '17', 'label' => __('17')],
			['value' => '18', 'label' => __('18')],
			['value' => '19', 'label' => __('19')],
			['value' => '20', 'label' => __('20')],
		];
	}
}
