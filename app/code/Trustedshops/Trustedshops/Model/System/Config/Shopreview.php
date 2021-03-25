<?php
/**
 * @category  Trustedshops
 * @package   Trustedshops\Trustedshops
 * @author    Trusted Shops GmbH
 * @copyright 2016 Trusted Shops GmbH
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      http://www.trustedshops.de/
 */

namespace Trustedshops\Trustedshops\Model\System\Config;

use Magento\Framework\App\ObjectManager;

class Shopreview extends \Magento\Framework\App\Config\Value
{
    /**
     * disallow saving of empty expert code
     *
     * @return mixed
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        $value = trim($value);
        if (empty($value)) {
            $value = $this->getDefault();
            $this->setValue($value);
        }

        return parent::beforeSave();
    }

    public function getDefault()
    {
        return <<<HTML
<script type="text/javascript">
    (function () {
        _tsRatingConfig = {
            'tsid': '%tsid%',
            'variant': 'testimonial',
            'theme': 'light',
            'reviews': '5',
            'betterThan': '3.5',
            'richSnippets': 'on',
            'backgroundColor': '#ffdc0f',
            'linkColor': '#000000',
            'quotationMarkColor': '#FFFFFF',
            'fontFamily': 'Arial',
            'reviewMinLength': '10'
        };
        var scripts = document.getElementsByTagName('SCRIPT'),
            me = scripts[scripts.length - 1];
        var _ts = document.createElement('SCRIPT');
        _ts.type = 'text/javascript';
        _ts.async = true;
        _ts.src = '//widgets.trustedshops.com/reviews/tsSticker/tsSticker.js';
        me.parentNode.insertBefore(_ts, me);
        _tsRatingConfig.script = _ts;
    })();
</script>
HTML;
    }
}
