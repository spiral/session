<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Session\Tests;

use PHPUnit\Framework\TestCase;
use Spiral\Session\Handlers\NullHandler;

class NullHandlerTest extends TestCase
{
    public function testNullHandler()
    {
        $handler = new NullHandler();

        $this->assertTrue($handler->destroy('abc'));
        $this->assertTrue($handler->gc(1));
        $this->assertTrue($handler->open('path', 1));
        $this->assertSame('', $handler->read(''));
        $this->assertTrue($handler->write('abc', 'data'));
        $this->assertTrue($handler->close());
    }
}