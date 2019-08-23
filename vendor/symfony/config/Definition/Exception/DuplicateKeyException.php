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
namespace Symfony\Component\Config\Definition\Exception;

/**
 * This exception is thrown whenever the key of an array is not unique. This can
 * only be the case if the configuration is coming from an XML file.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class DuplicateKeyException extends InvalidConfigurationException
{
}

?>