<?php
/**
 * @copyright 2015 Matt Kynaston <matt@kynx.org>
 * @license BSD-3-Clause
 */

namespace Kynx\Expressive\Handlebars;

use Interop\Container\ContainerInterface;
use Kynx\ZendCache\Psr\CacheItemPoolAdapter;
use Zend\Cache\StorageFactory;
use Zend\Cache\Exception\ExceptionInterface as ZendCacheException;

final class CacheItemPoolFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get(HandlebarsRendererFactory::CONFIG_KEY);
        if (isset($config['cache'])) {
            $config = $config['cache'];
        }
        else {
            throw new Exception\CacheConfigurationException(
                sprintf("Cannot find cache configuration under %s", HandlebarsRendererFactory::CONFIG_KEY)
            );
        }

        try {
            $storage = StorageFactory::factory($config);
        }
        catch (ZendCacheException $e) {
            throw new Exception\CacheConfigurationException($e->getMessage(), $e->getCode(), $e);
        }

        return new CacheItemPoolAdapter($storage);
    }
}
