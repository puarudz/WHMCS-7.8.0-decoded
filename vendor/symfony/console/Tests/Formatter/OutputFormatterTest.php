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
namespace Symfony\Component\Console\Tests\Formatter;

use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
class OutputFormatterTest extends \PHPUnit_Framework_TestCase
{
    public function testEmptyTag()
    {
        $formatter = new OutputFormatter(true);
        $this->assertEquals('foo<>bar', $formatter->format('foo<>bar'));
    }
    public function testLGCharEscaping()
    {
        $formatter = new OutputFormatter(true);
        $this->assertEquals('foo<bar', $formatter->format('foo\\<bar'));
        $this->assertEquals('<info>some info</info>', $formatter->format('\\<info>some info\\</info>'));
        $this->assertEquals('\\<info>some info\\</info>', OutputFormatter::escape('<info>some info</info>'));
        $this->assertEquals("\33[33mSymfony\\Component\\Console does work very well!\33[39m", $formatter->format('<comment>Symfony\\Component\\Console does work very well!</comment>'));
    }
    public function testBundledStyles()
    {
        $formatter = new OutputFormatter(true);
        $this->assertTrue($formatter->hasStyle('error'));
        $this->assertTrue($formatter->hasStyle('info'));
        $this->assertTrue($formatter->hasStyle('comment'));
        $this->assertTrue($formatter->hasStyle('question'));
        $this->assertEquals("\33[37;41msome error\33[39;49m", $formatter->format('<error>some error</error>'));
        $this->assertEquals("\33[32msome info\33[39m", $formatter->format('<info>some info</info>'));
        $this->assertEquals("\33[33msome comment\33[39m", $formatter->format('<comment>some comment</comment>'));
        $this->assertEquals("\33[30;46msome question\33[39;49m", $formatter->format('<question>some question</question>'));
    }
    public function testNestedStyles()
    {
        $formatter = new OutputFormatter(true);
        $this->assertEquals("\33[37;41msome \33[39;49m\33[32msome info\33[39m\33[37;41m error\33[39;49m", $formatter->format('<error>some <info>some info</info> error</error>'));
    }
    public function testAdjacentStyles()
    {
        $formatter = new OutputFormatter(true);
        $this->assertEquals("\33[37;41msome error\33[39;49m\33[32msome info\33[39m", $formatter->format('<error>some error</error><info>some info</info>'));
    }
    public function testStyleMatchingNotGreedy()
    {
        $formatter = new OutputFormatter(true);
        $this->assertEquals("(\33[32m>=2.0,<2.3\33[39m)", $formatter->format('(<info>>=2.0,<2.3</info>)'));
    }
    public function testStyleEscaping()
    {
        $formatter = new OutputFormatter(true);
        $this->assertEquals("(\33[32mz>=2.0,<<<a2.3\\\33[39m)", $formatter->format('(<info>' . $formatter->escape('z>=2.0,<\\<<a2.3\\') . '</info>)'));
        $this->assertEquals("\33[32m<error>some error</error>\33[39m", $formatter->format('<info>' . $formatter->escape('<error>some error</error>') . '</info>'));
    }
    public function testDeepNestedStyles()
    {
        $formatter = new OutputFormatter(true);
        $this->assertEquals("\33[37;41merror\33[39;49m\33[32minfo\33[39m\33[33mcomment\33[39m\33[37;41merror\33[39;49m", $formatter->format('<error>error<info>info<comment>comment</info>error</error>'));
    }
    public function testNewStyle()
    {
        $formatter = new OutputFormatter(true);
        $style = new OutputFormatterStyle('blue', 'white');
        $formatter->setStyle('test', $style);
        $this->assertEquals($style, $formatter->getStyle('test'));
        $this->assertNotEquals($style, $formatter->getStyle('info'));
        $style = new OutputFormatterStyle('blue', 'white');
        $formatter->setStyle('b', $style);
        $this->assertEquals("\33[34;47msome \33[39;49m\33[34;47mcustom\33[39;49m\33[34;47m msg\33[39;49m", $formatter->format('<test>some <b>custom</b> msg</test>'));
    }
    public function testRedefineStyle()
    {
        $formatter = new OutputFormatter(true);
        $style = new OutputFormatterStyle('blue', 'white');
        $formatter->setStyle('info', $style);
        $this->assertEquals("\33[34;47msome custom msg\33[39;49m", $formatter->format('<info>some custom msg</info>'));
    }
    public function testInlineStyle()
    {
        $formatter = new OutputFormatter(true);
        $this->assertEquals("\33[34;41msome text\33[39;49m", $formatter->format('<fg=blue;bg=red>some text</>'));
        $this->assertEquals("\33[34;41msome text\33[39;49m", $formatter->format('<fg=blue;bg=red>some text</fg=blue;bg=red>'));
    }
    public function testNonStyleTag()
    {
        $formatter = new OutputFormatter(true);
        $this->assertEquals("\33[32msome \33[39m\33[32m<tag>\33[39m\33[32m \33[39m\33[32m<setting=value>\33[39m\33[32m styled \33[39m\33[32m<p>\33[39m\33[32msingle-char tag\33[39m\33[32m</p>\33[39m", $formatter->format('<info>some <tag> <setting=value> styled <p>single-char tag</p></info>'));
    }
    public function testFormatLongString()
    {
        $formatter = new OutputFormatter(true);
        $long = str_repeat('\\', 14000);
        $this->assertEquals("\33[37;41msome error\33[39;49m" . $long, $formatter->format('<error>some error</error>' . $long));
    }
    public function testFormatToStringObject()
    {
        $formatter = new OutputFormatter(false);
        $this->assertEquals('some info', $formatter->format(new TableCell()));
    }
    public function testNotDecoratedFormatter()
    {
        $formatter = new OutputFormatter(false);
        $this->assertTrue($formatter->hasStyle('error'));
        $this->assertTrue($formatter->hasStyle('info'));
        $this->assertTrue($formatter->hasStyle('comment'));
        $this->assertTrue($formatter->hasStyle('question'));
        $this->assertEquals('some error', $formatter->format('<error>some error</error>'));
        $this->assertEquals('some info', $formatter->format('<info>some info</info>'));
        $this->assertEquals('some comment', $formatter->format('<comment>some comment</comment>'));
        $this->assertEquals('some question', $formatter->format('<question>some question</question>'));
        $formatter->setDecorated(true);
        $this->assertEquals("\33[37;41msome error\33[39;49m", $formatter->format('<error>some error</error>'));
        $this->assertEquals("\33[32msome info\33[39m", $formatter->format('<info>some info</info>'));
        $this->assertEquals("\33[33msome comment\33[39m", $formatter->format('<comment>some comment</comment>'));
        $this->assertEquals("\33[30;46msome question\33[39;49m", $formatter->format('<question>some question</question>'));
    }
    public function testContentWithLineBreaks()
    {
        $formatter = new OutputFormatter(true);
        $this->assertEquals(<<<EOF
\33[32m
some text\33[39m
EOF
, $formatter->format(<<<'EOF'
<info>
some text</info>
EOF
));
        $this->assertEquals(<<<EOF
\33[32msome text
\33[39m
EOF
, $formatter->format(<<<'EOF'
<info>some text
</info>
EOF
));
        $this->assertEquals(<<<EOF
\33[32m
some text
\33[39m
EOF
, $formatter->format(<<<'EOF'
<info>
some text
</info>
EOF
));
        $this->assertEquals(<<<EOF
\33[32m
some text
more text
\33[39m
EOF
, $formatter->format(<<<'EOF'
<info>
some text
more text
</info>
EOF
));
    }
}
class TableCell
{
    public function __toString()
    {
        return '<info>some info</info>';
    }
}

?>