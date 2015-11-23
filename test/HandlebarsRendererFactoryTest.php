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

    public function testTemplate()
    {
        $config = [
            'paths' => [
                __DIR__ . '/templates'
            ]
        ];
        $this->container->get(HandlebarsRendererFactory::CONFIG_KEY)
            ->willReturn($config);

        $factory = new HandlebarsRendererFactory();

        $renderer = $factory($this->container->reveal());
        $result = $renderer->render('template1');
        $this->assertEquals("Hello World\n", $result);
    }

    public function testPartialsDefaultNamespace()
    {
        $config = [
            'paths' => [
                __DIR__ . '/templates',
                'partials' => __DIR__ . '/partials',
            ]
        ];
        $this->container->get(HandlebarsRendererFactory::CONFIG_KEY)
            ->willReturn($config);

        $factory = new HandlebarsRendererFactory();
        $renderer = $factory($this->container->reveal());
        $result = $renderer->render('with_partials');

        $this->assertEquals("First: Partial1\nSecond: Partial2\n", $result);
    }

    public function testPartialsCustomNamespace()
    {
        $config = [
            'paths' => [
                __DIR__ . '/templates',
                'foo' => __DIR__ . '/partials',
            ],
            'partials-namespace' => 'foo'
        ];
        $this->container->get(HandlebarsRendererFactory::CONFIG_KEY)
            ->willReturn($config);


        $factory = new HandlebarsRendererFactory();
        $renderer = $factory($this->container->reveal());
        $result = $renderer->render('with_partials');

        $this->assertEquals("First: Partial1\nSecond: Partial2\n", $result);
    }

    public function testJsHelper()
    {
        $config = [
            'paths' => [ __DIR__ . '/templates' ],
            'js-helpers' => [ __DIR__ . '/js/helper.js' ]
        ];
        $this->container->get(HandlebarsRendererFactory::CONFIG_KEY)
            ->willReturn($config);


        $factory = new HandlebarsRendererFactory();
        $renderer = $factory($this->container->reveal());
        $result = $renderer->render('with_helper');

        $this->assertEquals("Helper: Hello World", $result);
    }

    public function testPhpHelper()
    {
        $config = [
            'paths' => [ __DIR__ . '/templates' ],
            'php-helpers' => [ __DIR__ . '/php/helper.php' ]
        ];
        $this->container->get(HandlebarsRendererFactory::CONFIG_KEY)
            ->willReturn($config);


        $factory = new HandlebarsRendererFactory();
        $renderer = $factory($this->container->reveal());
        $result = $renderer->render('with_helper');

        $this->assertEquals("Helper: Hello World", $result);
    }
}
