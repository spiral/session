<?php

declare(strict_types=1);

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Session\Tests;

use PHPUnit\Framework\TestCase;
use Spiral\Core\Container;
use Spiral\Session\Config\SessionConfig;
use Spiral\Session\Handler\FileHandler;
use Spiral\Session\Session;
use Spiral\Session\SessionFactory;
use Spiral\Session\SessionInterface;

class FactoryTest extends TestCase
{
    public function tearDown(): void
    {
        if (session_status() == PHP_SESSION_ACTIVE) {
            session_abort();
        }
    }

    /**
     * @expectedException \Spiral\Session\Exception\SessionException
     */
    public function testConstructInvalid(): void
    {
        $factory = new SessionFactory(new SessionConfig([
            'lifetime' => 86400,
            'cookie'   => 'SID',
            'secure'   => false,
            'handler'  => FileHandler::class,
            'handlers' => [
                //No directory
            ]
        ]), new Container());

        $session = $factory->initSession('sig', 'sessionid');
    }

    /**
     * @expectedException \Spiral\Session\Exception\SessionException
     */
    public function testAlreadyStarted(): void
    {
        $factory = new SessionFactory(new SessionConfig([
            'lifetime' => 86400,
            'cookie'   => 'SID',
            'secure'   => false,
            'handler'  => FileHandler::class,
            'handlers' => [
                //No directory
            ]
        ]), new Container());

        $session = $factory->initSession('sig', 'sessionid');
    }

    /**
     * @expectedException \Spiral\Session\Exception\SessionException
     * @expectedExceptionMessage Unable to initiate session, session already started
     */
    public function testMultipleSessions(): void
    {
        $factory = new SessionFactory(new SessionConfig([
            'lifetime' => 86400,
            'cookie'   => 'SID',
            'secure'   => false,
            'handler'  => null,
            'handlers' => []
        ]), $c = new Container());

        $c->bind(SessionInterface::class, Session::class);

        $session = $factory->initSession('sig');
        $session->resume();

        $session = $factory->initSession('sig', $session->getID());
    }
}
