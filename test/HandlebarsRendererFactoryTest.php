<?php
/**
 * @copyright 2015 Matt Kynaston <matt@kynx.org>
 * @license BSD-3-Clause
 */

namespace KynxTest\Expressive\Handlebars;

use Interop\Container\ContainerInterface;
use Kynx\Expressive\Handlebars\HandlebarsRendererFactory;
use Kynx\Expressive\Handlebars\ResolverFactory;
use Kynx\Template\Resolver\ResolverInterface;
use Kynx\ZendCache\Psr\CacheItem;
use Kynx\ZendCache\Psr\CacheItemPoolAdapter;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;

class HandlebarsRendererFactoryTest extends TestCase
{
    /**
     * @var \Prophecy\Prophecy\ObjectProphecy
     */
    private $container;

    public function setUp()
    {
        $cacheItemPool = $this->prophesize(CacheItemPoolAdapter::class);
        $cacheItemPool->getItem(Argument::type('string'))
            ->willReturn(new CacheItem('foo', null, false));
        $cacheItemPool->save(Argument::type(CacheItem::class))
            ->willReturn(true);

        $this->container = $this->prophesize(ContainerInterface::class);
        $this->container->get('\Psr\Cache\CacheItemPoolInterface')
            ->willReturn($cacheItemPool->reveal());
        $this->container->get(ResolverInterface::class)
            ->will(function() {
                $factory = new ResolverFactory();
                return $factory($this->reveal());
            });
    }

    /**
     * @expectedException \Kynx\Expressive\Handlebars\Exception\SourceNotFoundException
     * @runInSeparateProcess
     */
    public function testNonexistentSource()
    {
        $config = [
            'source' => __DIR__ . '/js/nonexistent-handlebars.js'
        ];
        $factory = new HandlebarsRendererFactory();
        $this->container->get($factory::CONFIG_KEY)->willReturn($config);

        $factory($this->container->reveal());
    }

    /**
     * @expectedException \Kynx\Expressive\Handlebars\Exception\InvalidSourceException
     * @runInSeparateProcess
     */
    public function testInvalidSource()
    {
        $this->markTestSkipped("Can't catch error thrown here");
        $config = [
            'source' => __DIR__ . '/js/invalid-handlebars.js'
        ];
        $factory = new HandlebarsRendererFactory();
        $this->container->get($factory::CONFIG_KEY)->willReturn($config);

        $factory($this->container->reveal());
    }

    /**
     * @expectedException \Kynx\Expressive\Handlebars\Exception\TemplateNotFoundException
     */
    public function testNoConfig()
    {
        $factory = new HandlebarsRendererFactory();
        $this->container->get($factory::CONFIG_KEY)->willReturn(null);

        $renderer = $factory($this->container->reveal());
        $renderer->render('nonexistent');
    }


}
