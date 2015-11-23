<?php
/**
 * @copyright 2015 Matt Kynaston <matt@kynx.org>
 * @license BSD-3-Clause
 */

namespace Kynx\Expressive\Handlebars;

use Interop\Container\ContainerInterface;
use Kynx\Expressive\Handlebars\Exception\InvalidSourceException;
use Kynx\Expressive\Handlebars\Exception\SourceNotFoundException;
use Kynx\Template\Resolver\AggregateResolver;
use Kynx\Template\Resolver\FilesystemResolver;
use Kynx\Template\Resolver\ResolverInterface;
use Kynx\Template\Resolver\SavingResolverInterface;
use Kynx\V8js\Handlebars;

final class HandlebarsRendererFactory
{
    const CONFIG_KEY = 'kynx-expressive-handlebars-v8js';
    const NS = '__SYSTEM__';

    /**
     * @param ContainerInterface $container
     * @return HandlebarsRenderer
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get(self::CONFIG_KEY);
        $source = isset($config['source']) ? $config['source'] : './vendor/components/handlebars.js/handlebars.js';

        $resolver = $container->get(ResolverInterface::class);
        $handlebars = $this->getHandlebars($resolver, $source);

        $partialsNamespace = $config['partials-namespace'] ? $config['partials-namespace'] : 'partials';
        $this->injectPartials($resolver, $handlebars, $partialsNamespace);

        $jsHelpers = $config['js-helpers'] ? (array) $config['js-helpers'] : [];
        $this->injectJsHelpers($resolver, $handlebars, $jsHelpers);

        $phpHelpers = $config['php-helpers'] ? (array) $config['php-helpers'] : [];
        $this->injectPhpHelpers($handlebars, $phpHelpers);

        return new HandlebarsRenderer($handlebars, $resolver);
    }

    private function getHandlebars($resolver, $sourceFile)
    {
        $source = $this->getHandlebarsSource($resolver, $sourceFile);

        // @fixme - this should probably be handled by v8js-handlebars
        // If the handlebars extension does not compile this bars text that even ob_start() won't catch, as well
        // as throwing an E_WARNING :(
        set_error_handler(function($errno, $errstr) use ($sourceFile) {
            restore_error_handler();
            throw new InvalidSourceException(
                sprintf("An error occurred creating handlebars instance from source '%s': %s", $sourceFile, $errstr)
            );
        }, E_WARNING);

        if (! Handlebars::isRegistered()) {
            Handlebars::registerHandlebarsExtension($source);
        }
        $handlebars = new Handlebars();

        restore_error_handler();
        return $handlebars;
    }

    private function getHandlebarsSource(ResolverInterface $resolver, $source)
    {
        $key = self::NS . '::handlebars';
        $handlebars = $resolver->resolve($key);
        if (! $handlebars) {
            $handlebars = @file_get_contents($source);
            if (! $handlebars) {
                throw new SourceNotFoundException("Couldn't load Handlebars from '$source'");
            }
            if ($resolver instanceof SavingResolverInterface) {
                $resolver->save($key, $handlebars);
            }
        }
        return $handlebars;
    }

    private function injectPartials(ResolverInterface $resolver, Handlebars $handlebars, $namespace)
    {
        $key = self::NS . '::partials';
        $partials = $resolver->resolve($key);
        if (! $partials) {
            $partials = $this->resolvePartials($resolver, $namespace);
            if ($resolver instanceof SavingResolverInterface) {
                $resolver->save($key, $partials);
            }
        }
        $this->registerPartials($resolver, $handlebars, $partials);
    }

    private function resolvePartials(ResolverInterface $resolver, $namespace)
    {
        $partials = [];
        $filesystem = $resolver;
        if ($resolver instanceof AggregateResolver) {
            $filesystem = $resolver->fetchByType(FilesystemResolver::class);
        }
        if ($filesystem instanceof FilesystemResolver) {
            $extension = $filesystem->getExtension();
            $paths = $filesystem->getPaths();
            $partialPaths = isset($paths[$namespace]) ? $paths[$namespace] : [];
            foreach ($partialPaths as $path) {
                $it = new \GlobIterator($path . '/*.' . $extension, \FilesystemIterator::CURRENT_AS_PATHNAME);
                foreach ($it as $filePath) {
                    $name = substr($filePath, strlen($path), strlen($filePath) - strlen($extension) - 1);
                    $partials[$name] = $namespace . '::' . $name;
                }
            }
        }
        return $partials;
    }

    private function registerPartials(ResolverInterface $resolver, Handlebars $handlebars, $partials)
    {
        foreach ($partials as $name => $partial) {
            $template = $resolver->resolve($partial);
            if ($template) {
                if ($template->isCompiled()) {
                    $compiled = $template;
                } else {
                    $compiled = $handlebars->precompile((string) $template);
                    if ($resolver instanceof SavingResolverInterface) {
                        $resolver->save($partial, $compiled);
                    }
                }
                $handlebars->registerPartial($name, $handlebars->template($compiled));
            }
        }
    }

    private function injectJsHelpers(ResolverInterface $resolver, Handlebars $handlebars, $helpers)
    {
        foreach ($helpers as $fileName) {
            $helper = $resolver->resolve(self::NS . '::' . $fileName);
            if (! $helper) {
                $helper = @file_get_contents($fileName);
                if (! $helper) {
                    throw new Exception\HelperNotFoundException("Couldn't load helper from '$fileName'");
                }
                if ($resolver instanceof SavingResolverInterface) {
                    $resolver->save(self::NS . '::' . $fileName, $helper);
                }
            }
            $handlebars->registerHelper($this->getName($fileName), $helper);
        }
    }

    /**
     * We don't cache PHP helpers ourselves, but rely on bytecode cache instead
     * @param Handlebars $handlebars
     * @param array $helpers
     */
    private function injectPhpHelpers(Handlebars $handlebars, $helpers)
    {
        $phpHelpers = [];
        foreach ($helpers as $fileName) {
            $helper = include $fileName;
            $handlebars->registerHelper($this->getName($fileName), $helper);
        }
        $handlebars->registerHelper($phpHelpers);
    }

    private function getName($fileName)
    {
        $baseName = basename($fileName);
        return substr($baseName, 0, strpos($baseName, '.'));
    }
}
