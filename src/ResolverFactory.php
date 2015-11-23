<?php
/**
 * @copyright 2015 Matt Kynaston <matt@kynx.org>
 * @license BSD-3-Clause
 */

namespace Kynx\Expressive\Handlebars;

use Interop\Container\ContainerInterface;
use Kynx\Template\Resolver\AggregateResolver;
use Kynx\Template\Resolver\CacheResolver;
use Kynx\Template\Resolver\FilesystemResolver;

final class ResolverFactory
{
    /**
     * @param ContainerInterface $container
     * @return AggregateResolver
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get(HandlebarsRendererFactory::CONFIG_KEY);
        $filesystemResolver = $this->filesystemResolverFactory($config, $container);
        $cacheResolver = $this->cacheResolverFactory($config, $container);
        $resolver = new AggregateResolver();
        $resolver->attach($filesystemResolver, 0);
        $resolver->attach($cacheResolver, 1);
        return $resolver;
    }

    private function filesystemResolverFactory($config, ContainerInterface $container)
    {
        $resolver = new FilesystemResolver();
        $resolver->setExtension(isset($config['extension']) ? $config['extension'] : 'hbs')
            ->setSeparator(isset($config['separator']) ? $config['separator'] : '/');
        $templatePaths = isset($config['paths']) ? $config['paths'] : [];
        foreach ($templatePaths as $namespace => $paths) {
            foreach ((array) $paths as $path) {
                $resolver->addPath($path, $namespace ? $namespace : null);
            }
        }
        return $resolver;
    }

    private function cacheResolverFactory($config, ContainerInterface $container)
    {
        $cachePool = $container->get('\Psr\Cache\CacheItemPoolInterface');
        $resolver = new CacheResolver($cachePool);
        $resolver->setIsCompiled(true);
        return $resolver;
    }
}
