<?php

namespace Trustedshops\Trustedshops\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Cache extends AbstractHelper
{
    const CACHE_FILE_SHOPS = 'trustedshops_shops';

    /**
     * @return string
     */
    protected function getDir()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Cache' . DIRECTORY_SEPARATOR;
    }

    /**
     * @param $name
     * @return string
     */
    protected function getFile($name)
    {
        return $name . '.cache';
    }

    /**
     * @param $name
     * @param string $data
     */
    public function save($name, $data = '')
    {
        $file = $this->getDir() . $this->getFile($name);

        $fh = fopen($file, 'wb');
        fwrite($fh, $data);
        fclose($fh);
    }

    /**
     * @param $name
     * @return bool
     */
    public function remove($name)
    {
        $file = $this->getDir() . $this->getFile($name);

        if (file_exists($file) && is_readable($file)) {
            return unlink($file);
        }

        return false;
    }

    /**
     * @param $name
     * @return false|string|null
     */
    public function get($name)
    {
        $file = $this->getDir() . $this->getFile($name);

        if (file_exists($file) && is_readable($file)) {
            return file_get_contents($file);
        }

        return null;
    }

}
