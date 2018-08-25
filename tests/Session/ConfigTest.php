<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Session\Tests;

use PHPUnit\Framework\TestCase;
use Spiral\Core\Container\Autowire;
use Spiral\Session\Configs\SessionConfig;
use Spiral\Session\Handlers\FileHandler;

class ConfigTest extends TestCase
{
    public function testConfig()
    {
        $c = new SessionConfig([
            'lifetime' => 86400,
            'cookie'   => 'SID',
            'secure'   => false,
            'handler'  => 'files',
            'handlers' => [
                'files' => [
                    'class'   => FileHandler::class,
                    'options' => ['directory' => sys_get_temp_dir()]
                ]
            ]
        ]);

        $this->assertSame([
            'User-Agent',
            'Accept-Language'
        ], $c->getSignatureHeaders());

        $this->assertSame('SID', $c->getCookie());
        $this->assertSame(false, $c->isSecure());
        $this->assertSame(86400, $c->getLifetime());
        $this->assertEquals(new Autowire(FileHandler::class, [
            'directory' => sys_get_temp_dir()
        ]), $c->getHandler());
    }

    public function testConfigAutowired()
    {
        $c = new SessionConfig([
            'lifetime' => 86400,
            'cookie'   => 'SID',
            'secure'   => false,
            'handler'  => new Autowire(FileHandler::class, ['directory' => sys_get_temp_dir()]),
        ]);

        $this->assertEquals(new Autowire(FileHandler::class, [
            'directory' => sys_get_temp_dir()
        ]), $c->getHandler());
    }
}