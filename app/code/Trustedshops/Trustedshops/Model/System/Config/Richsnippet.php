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

class Richsnippet extends \Magento\Framework\App\Config\Value
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
<script type="application/ld+json">
{"@context": "http://schema.org",
"@type": "Organization",
"name": "%shopname%",
"aggregateRating" : {
"@type": "AggregateRating",
"ratingValue" : "%result%",
"bestRating" : "%max%",
"ratingCount" : "%count%"
}}
</script>
HTML;
    }
}
