<?php
/**
 * @copyright 2015 Matt Kynaston <matt@kynx.org>
 * @license BSD-3-Clause
 */

namespace KynxTest\Expressive\Handlebars;

use Interop\Container\ContainerInterface;
use Kynx\Expressive\Handlebars\HandlebarsRendererFactory;
use Kynx\Expressive\Handlebars\ResolverFactory;
use Kynx\Template\Resolver\AggregateResolver;
use Kynx\Template\Resolver\CacheResolver;
use Kynx\Template\Resolver\FilesystemResolver;
use Kynx\ZendCache\Psr\CacheItemPoolAdapter;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;

class ResolverFactoryTest extends TestCase
{
    /**
     * @var \Prophecy\Prophecy\ObjectProphecy
     */
    private $container;

    public function setUp()
    {
        $cacheItemPool = $this->prophesize(CacheItemPoolAdapter::class);

        $this->container = $this->prophesize(ContainerInterface::class);
        $this->container->get('\Psr\Cache\CacheItemPoolInterface')
            ->willReturn($cacheItemPool->reveal());
    }

    public function testNoConfig()
    {
        $factory = new ResolverFactory();
        $this->container->get(HandlebarsRendererFactory::CONFIG_KEY)
            ->willReturn(null);

        /* @var AggregateResolver $resolver */
        $resolver = $factory($this->container->reveal());
        $this->assertInstanceOf(AggregateResolver::class, $resolver);
        $this->assertTrue($resolver->hasType(FilesystemResolver::class));
        $this->assertTrue($resolver->hasType(CacheResolver::class));

        /* @var FilesystemResolver $filesystemResolver */
        $filesystemResolver = $resolver->fetchByType(FilesystemResolver::class);
        $this->assertEquals('hbs', $filesystemResolver->getExtension());
        $this->assertEquals('/', $filesystemResolver->getSeparator());
        $this->assertEquals([], $filesystemResolver->getPaths());

        /* @var CacheResolver $cacheResolver */
        $cacheResolver = $resolver->fetchByType(CacheResolver::class);
        $this->assertTrue($cacheResolver->isCompiled());
    }

    public function testWithConfig()
    {
        $paths = [
            'ns1' => [__DIR__ . '/partials'],
            'ns2' => [__DIR__ . '/templates']
        ];
        $config = [
            'extension' => 'handlebars',
            'separator' => '.',
            'paths' => $paths
        ];
        $factory = new ResolverFactory();
        $this->container->get(HandlebarsRendererFactory::CONFIG_KEY)
            ->willReturn($config);

        /* @var AggregateResolver $resolver */
        $resolver = $factory($this->container->reveal());

        /* @var FilesystemResolver $filesystemResolver */
        $filesystemResolver = $resolver->fetchByType(FilesystemResolver::class);
        $this->assertEquals('handlebars', $filesystemResolver->getExtension());
        $this->assertEquals('.', $filesystemResolver->getSeparator());
        $paths = $filesystemResolver->getPaths();
        $this->assertArrayHasKey('ns1', $paths);
        $this->assertArrayHasKey('ns2', $paths);
    }
}
