<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Symfony\Component\Config;

/**
 * Interface for a ConfigCache factory. This factory creates
 * an instance of ConfigCacheInterface and initializes the
 * cache if necessary.
 *
 * @author Matthias Pigulla <mp@webfactory.de>
 */
interface ConfigCacheFactoryInterface
{
    /**
     * Creates a cache instance and (re-)initializes it if necessary.
     *
     * @param string   $file     The absolute cache file path
     * @param callable $callable The callable to be executed when the cache needs to be filled (i. e. is not fresh). The cache will be passed as the only parameter to this callback
     *
     * @return ConfigCacheInterface $configCache The cache instance
     */
    public function cache($file, $callable);
}

?>