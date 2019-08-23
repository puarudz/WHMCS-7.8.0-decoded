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
namespace Symfony\Component\Finder\Tests\FakeAdapter;

use Symfony\Component\Finder\Adapter\AbstractAdapter;
/**
 * @author Jean-François Simon <contact@jfsimon.fr>
 */
class NamedAdapter extends AbstractAdapter
{
    /**
     * @var string
     */
    private $name;
    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }
    /**
     * {@inheritdoc}
     */
    public function searchInDirectory($dir)
    {
        return new \ArrayIterator(array());
    }
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }
    /**
     * {@inheritdoc}
     */
    protected function canBeUsed()
    {
        return true;
    }
}

?>