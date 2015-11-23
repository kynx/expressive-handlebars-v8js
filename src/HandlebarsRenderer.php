<?php
/**
 * @copyright 2015 Matt Kynaston <matt@kynx.org>
 * @license BSD-3-Clause
 */

namespace Kynx\Expressive\Handlebars;

use Kynx\Template\Resolver\AbstractResolver;
use Kynx\Template\Resolver\PathedResolverInterface;
use Kynx\Template\Resolver\ResolverInterface;
use Kynx\Template\Resolver\SavingResolverInterface;
use Kynx\V8js\Handlebars;
use Zend\Expressive\Template\DefaultParamsTrait;
use Zend\Expressive\Template\TemplatePath;
use Zend\Expressive\Template\TemplateRendererInterface;

final class HandlebarsRenderer implements TemplateRendererInterface
{
    use DefaultParamsTrait;

    /**
     * @var Handlebars
     */
    private $handlebars;
    /**
     * @var ResolverInterface
     */
    private $resolver;

    /**
     * V8JsTemplate constructor.
     * @param Handlebars $handlebars
     * @param PathedResolverInterface $resolver
     */
    public function __construct(Handlebars $handlebars, PathedResolverInterface $resolver)
    {
        $this->handlebars = $handlebars;
        $this->resolver = $resolver;
    }

    public function render($name, $params = [])
    {
        $params = $this->mergeParams($name, $params);
        $template = $this->resolver->resolve($name);
        if (!$template) {
            throw new Exception\TemplateNotFoundException("Couldn't resolve '$name'");
        }

        if ($template->isCompiled()) {
            $compiled = (string) $template;
        } else {
            try {
                $compiled = $this->handlebars->precompile((string) $template);
            } catch (\V8JsScriptException $e) {
                throw new Exception\TemplateCompilationException("Error compiling '$name'", 0, $e);
            }

            if ($this->resolver instanceof SavingResolverInterface) {
                $this->resolver->save($template->getKey(), $compiled);
            }
        }

        try {
            $template = $this->handlebars->template($compiled);
            return $template($params);
        } catch (\V8JsScriptException $e) {
            throw new Exception\TemplateExecutionException("Error running '$name'", 0, $e);
        }
    }

    public function addPath($path, $namespace = null)
    {
        return $this->resolver->addPath($path, $namespace);
    }

    /**
     * Retrieve configured paths from the engine.
     *
     * @return TemplatePath[]
     */
    public function getPaths()
    {
        $templatePaths = [];
        foreach ($this->resolver->getPaths() as $namespace => $paths) {
            $namespace = $namespace == AbstractResolver::DEFAULT_NAMESPACE ? null : $namespace;
            foreach ($paths as $path) {
                $templatePaths[] = new TemplatePath($path, $namespace);
            }
        }
        return $templatePaths;
    }
}
