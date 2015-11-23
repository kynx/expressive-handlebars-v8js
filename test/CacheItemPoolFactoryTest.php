<?php
/**
 * @copyright 2015 Matt Kynaston <matt@kynx.org>
 * @license BSD-3-Clause
 */

namespace KynxTest\Expressive\Handlebars;

use Interop\Container\ContainerInterface;
use Kynx\Expressive\Handlebars\CacheItemPoolFactory;
use Kynx\Expressive\Handlebars\HandlebarsRendererFactory;
use Kynx\ZendCache\Psr\CacheItemPoolAdapter;
use PHPUnit_Framework_TestCase as TestCase;

class CacheItemPoolFactoryTest extends TestCase
{
    /**
     * @expectedException \Kynx\Expressive\Handlebars\Exception\CacheConfigurationException
     */
    public function testNoConfig()
    {
        $factory = new CacheItemPoolFactory();
        $prophesy = $this->prophesize(ContainerInterface::class);
        $prophesy->get(HandlebarsRendererFactory::CONFIG_KEY)->willReturn(null);
        $factory($prophesy->reveal());
    }

    /**
     * @expectedException \Kynx\Expressive\Handlebars\Exception\CacheConfigurationException
     */
    public function testNoAdapter()
    {
        $factory = new CacheItemPoolFactory();
        $prophesy = $this->prophesize(ContainerInterface::class);
        $prophesy->get(HandlebarsRendererFactory::CONFIG_KEY)->willReturn(['cache' => []]);
        $factory($prophesy->reveal());
    }

    public function testBlackHoleAdapter()
    {
        $factory = new CacheItemPoolFactory();
        $prophesy = $this->prophesize(ContainerInterface::class);
        $prophesy->get(HandlebarsRendererFactory::CONFIG_KEY)->willReturn(['cache' => ['adapter' => 'blackhole']]);
        $cacheItemPool = $factory($prophesy->reveal());
        $this->assertInstanceOf(CacheItemPoolAdapter::class, $cacheItemPool);
    }
}
