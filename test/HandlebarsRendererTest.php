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

class HandlebarsRendererTest extends TestCase
{
    private $handlebars;

    public function setUp()
    {

    }

    /**
     * @expectedException \Kynx\Expressive\Handlebars\Exception\TemplateCompilationException
     */
    public function testRenderInvalidTemplate()
    {

    }

    public function testRenderCachedTemplate()
    {

    }

    /**
     * @expectedException \Kynx\Expressive\Handlebars\Exception\TemplateExecutionException
     */
    public function testRenderInvalidCachedTemplate()
    {

    }

    public function testAddPath()
    {

    }

    public function testGetPaths()
    {

    }
}
