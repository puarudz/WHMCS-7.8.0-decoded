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
namespace Symfony\Component\Console\Descriptor;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Exception\InvalidArgumentException;
/**
 * @author Jean-François Simon <jeanfrancois.simon@sensiolabs.com>
 *
 * @internal
 */
abstract class Descriptor implements DescriptorInterface
{
    /**
     * @var OutputInterface
     */
    private $output;
    /**
     * {@inheritdoc}
     */
    public function describe(OutputInterface $output, $object, array $options = array())
    {
        $this->output = $output;
        switch (true) {
            case $object instanceof InputArgument:
                $this->describeInputArgument($object, $options);
                break;
            case $object instanceof InputOption:
                $this->describeInputOption($object, $options);
                break;
            case $object instanceof InputDefinition:
                $this->describeInputDefinition($object, $options);
                break;
            case $object instanceof Command:
                $this->describeCommand($object, $options);
                break;
            case $object instanceof Application:
                $this->describeApplication($object, $options);
                break;
            default:
                throw new InvalidArgumentException(sprintf('Object of type "%s" is not describable.', get_class($object)));
        }
    }
    /**
     * Writes content to output.
     *
     * @param string $content
     * @param bool   $decorated
     */
    protected function write($content, $decorated = false)
    {
        $this->output->write($content, false, $decorated ? OutputInterface::OUTPUT_NORMAL : OutputInterface::OUTPUT_RAW);
    }
    /**
     * Describes an InputArgument instance.
     *
     * @param InputArgument $argument
     * @param array         $options
     *
     * @return string|mixed
     */
    protected abstract function describeInputArgument(InputArgument $argument, array $options = array());
    /**
     * Describes an InputOption instance.
     *
     * @param InputOption $option
     * @param array       $options
     *
     * @return string|mixed
     */
    protected abstract function describeInputOption(InputOption $option, array $options = array());
    /**
     * Describes an InputDefinition instance.
     *
     * @param InputDefinition $definition
     * @param array           $options
     *
     * @return string|mixed
     */
    protected abstract function describeInputDefinition(InputDefinition $definition, array $options = array());
    /**
     * Describes a Command instance.
     *
     * @param Command $command
     * @param array   $options
     *
     * @return string|mixed
     */
    protected abstract function describeCommand(Command $command, array $options = array());
    /**
     * Describes an Application instance.
     *
     * @param Application $application
     * @param array       $options
     *
     * @return string|mixed
     */
    protected abstract function describeApplication(Application $application, array $options = array());
}

?>