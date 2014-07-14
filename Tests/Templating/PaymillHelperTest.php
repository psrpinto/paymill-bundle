<?php

namespace Memeoirs\PaymillBundle\Tests\Templating;

use Memeoirs\PaymillBundle\Templating\PaymillHelper;
use Mockery as m;

/**
 * @author Tobias Nyholm
 */
class PaymillHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testInitialize()
    {
        $expected = 'foobar';
        $template = 'tmpl';
        $options = array('foo'=>'bar');
        $engine = m::mock('Symfony\Component\Templating\EngineInterface')
            ->shouldReceive('render')->with($template, $options)->once()->andReturn($expected)
            ->getMock();

        $helper = new PaymillHelper($engine, $template);
        $result=$helper->initialize($options);

        $this->assertEquals($expected, $result);
    }

    public function testGetName()
    {
        $engine = m::mock('Symfony\Component\Templating\EngineInterface');
        $helper = new PaymillHelper($engine, 'foo');

        $this->assertEquals('paymill', $helper->getName());
    }
}
