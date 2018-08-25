<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Session\Tests;

use PHPUnit\Framework\TestCase;
use Spiral\Core\Container;
use Spiral\Files\Files;
use Spiral\Files\FilesInterface;
use Spiral\Session\Configs\SessionConfig;
use Spiral\Session\Handlers\FileHandler;
use Spiral\Session\SessionFactory;

class SessionTest extends TestCase
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var SessionFactory
     */
    private $factory;

    public function setUp()
    {
        $this->container = new Container();
        $this->container->bind(FilesInterface::class, Files::class);

        $this->factory = new SessionFactory(new SessionConfig([
            'lifetime' => 86400,
            'cookie'   => 'SID',
            'secure'   => false,
            'handler'  => new Container\Autowire(FileHandler::class, [
                'directory' => sys_get_temp_dir()
            ]),
        ]), $this->container);
    }

    public function tearDown()
    {
        if (session_status() == PHP_SESSION_ACTIVE) {
            session_abort();
        }
    }

    public function testValueDestroy()
    {
        $session = $this->factory->initSession('sig');
        $session->getSection()->set('key', 'value');

        $this->assertSame('value', $session->getSection()->get('key'));

        $session->destroy();
        $session->resume();

        $this->assertSame(null, $session->getSection()->get('key'));
    }

    public function testValueRestart()
    {
        $session = $this->factory->initSession('sig');
        $session->getSection()->set('key', 'value');

        $this->assertSame('value', $session->getSection()->get('key'));
        $id = $session->getID();
        $session->commit();

        $session = $this->factory->initSession('sig', $id);
        $this->assertSame('value', $session->getSection()->get('key'));
    }

    public function testValueNewID()
    {
        $session = $this->factory->initSession('sig');
        $session->getSection()->set('key', 'value');

        $this->assertSame('value', $session->getSection()->get('key'));
        $id = $session->regenerateID()->getID();
        $session->commit();

        $session = $this->factory->initSession('sig', $id);
        $this->assertSame('value', $session->getSection()->get('key'));
    }

    public function testSection()
    {
        $session = $this->factory->initSession('sig');
        $section = $session->getSection('default');

        $this->assertSame("default", $section->getName());

        $section->set("key", "value");
        foreach ($section as $key => $value) {
            $this->assertSame("key", $key);
            $this->assertSame("value", $value);
        }

        $this->assertSame("key", $key);
        $this->assertSame("value", $value);

        $this->assertSame("value", $section->pull("key"));
        $this->assertSame(null, $section->pull("key"));
    }

    public function testSectionClear()
    {
        $session = $this->factory->initSession('sig');
        $section = $session->getSection('default');

        $section->set("key", "value");
        $section->clear();
        $this->assertSame(null, $section->pull("key"));
    }

    public function testSectionArrayAccess()
    {
        $session = $this->factory->initSession('sig');
        $section = $session->getSection('default');

        $section['key'] = 'value';
        $this->assertSame('value', $section['key']);
        $section->key = 'new value';
        $this->assertSame('new value', $section->key);
        $this->assertTrue(isset($section['key']));
        $this->assertTrue(isset($section->key));

        $section->delete('key');
        $this->assertFalse(isset($section['key']));
        $this->assertFalse(isset($section->key));

        $section->key = 'new value';
        unset($section->key);
        $this->assertFalse(isset($section->key));


        $section->key = 'new value';
        unset($section['key']);
        $this->assertFalse(isset($section->key));

        $section->new = "another";

        $session->commit();

        $this->assertSame(null, $section->get("key"));
        $this->assertSame("another", $section->get("new"));
    }

//    public function testSectionByInjection()
//    {
//        $this->http->riseMiddleware(SessionStarter::class);
//
//        $this->http->setEndpoint(function () {
//            $this->assertSame(
//                'cli',
//                $this->container->get(SectionInterface::class, 'cli')->getName()
//            );
//        });
//
//        $result = $this->get('/');
//        $this->assertSame(200, $result->getStatusCode());
//
//        $cookies = $this->fetchCookies($result->getHeader('Set-Cookie'));
//
//        //Session never started!
//        $this->assertArrayNotHasKey('SID', $cookies);
//    }
//
//    public function testSessionResume()
//    {
//        $this->http->riseMiddleware(SessionStarter::class);
//
//        $this->http->setEndpoint(function () {
//            return ++$this->session->getSection('cli')->value;
//        });
//
//        $result = $this->get('/');
//        $this->assertSame(200, $result->getStatusCode());
//        $this->assertSame('1', $result->getBody()->__toString());
//
//        $this->assertFalse($this->container->hasInstance(SessionInterface::class));
//
//        $cookies = $this->fetchCookies($result->getHeader('Set-Cookie'));
//        $this->assertArrayHasKey('SID', $cookies);
//
//        $result = $this->get('/', [], [], [
//            'SID' => $cookies['SID']
//        ]);
//        $this->assertSame(200, $result->getStatusCode());
//        $this->assertSame('2', $result->getBody()->__toString());
//
//        $result = $this->get('/', [], [], [
//            'SID' => $cookies['SID']
//        ]);
//        $this->assertSame(200, $result->getStatusCode());
//        $this->assertSame('3', $result->getBody()->__toString());
//    }
//
//    public function testSessionRegenerateId()
//    {
//        $this->http->riseMiddleware(SessionStarter::class);
//
//        $this->http->setEndpoint(function () {
//            return ++$this->session->getSection('cli')->value;
//        });
//
//        $result = $this->get('/');
//        $this->assertSame(200, $result->getStatusCode());
//        $this->assertSame('1', $result->getBody()->__toString());
//
//        $this->assertFalse($this->container->hasInstance(SessionInterface::class));
//
//        $cookies = $this->fetchCookies($result->getHeader('Set-Cookie'));
//        $this->assertArrayHasKey('SID', $cookies);
//
//        $result = $this->get('/', [], [], [
//            'SID' => $cookies['SID']
//        ]);
//        $this->assertSame(200, $result->getStatusCode());
//        $this->assertSame('2', $result->getBody()->__toString());
//
//        $this->http->setEndpoint(function () {
//            $this->session->regenerateID(false);
//
//            return ++$this->session->getSection('cli')->value;
//        });
//
//        $result = $this->get('/', [], [], [
//            'SID' => $cookies['SID']
//        ]);
//
//        $newCookies = $this->fetchCookies($result->getHeader('Set-Cookie'));
//        $this->assertArrayHasKey('SID', $newCookies);
//
//        $this->assertNotEquals($cookies['SID'], $newCookies['SID']);
//
//        $this->assertSame(200, $result->getStatusCode());
//        $this->assertSame('3', $result->getBody()->__toString());
//    }
//
//    public function testSetSidWithCookieManager()
//    {
//        $this->http->riseMiddleware(SessionStarter::class);
//        $this->http->riseMiddleware(CookieManager::class);
//
//        $this->http->setEndpoint(function () {
//            return $this->session->getSection('cli')->value++;
//        });
//
//        $result = $this->get('/');
//        $this->assertSame(200, $result->getStatusCode());
//
//        $cookies = $this->fetchCookies($result->getHeader('Set-Cookie'));
//        $this->assertArrayHasKey('SID', $cookies);
//    }
//
//    public function testSetSidWithCookieManagerResume()
//    {
//        $this->http->riseMiddleware(SessionStarter::class);
//        $this->http->riseMiddleware(CookieManager::class);
//
//        $this->http->setEndpoint(function () {
//            $this->assertInternalType('array', $this->session->__debugInfo());
//
//            return ++$this->session->getSection('cli')->value;
//        });
//
//        $result = $this->get('/');
//        $this->assertSame(200, $result->getStatusCode());
//
//        $cookies = $this->fetchCookies($result->getHeader('Set-Cookie'));
//        $this->assertArrayHasKey('SID', $cookies);
//
//        $result = $this->get('/', [], [], [
//            'SID' => $cookies['SID']
//        ]);
//        $this->assertSame(200, $result->getStatusCode());
//        $this->assertSame('2', $result->getBody()->__toString());
//
//
//        $result = $this->get('/', [], [], [
//            'SID' => $cookies['SID']
//        ]);
//        $this->assertSame(200, $result->getStatusCode());
//        $this->assertSame('3', $result->getBody()->__toString());
//    }
//
//    public function testDestroySession()
//    {
//        $this->http->riseMiddleware(SessionStarter::class);
//        $this->http->riseMiddleware(CookieManager::class);
//
//        $this->http->setEndpoint(function () {
//            $this->assertInternalType('array', $this->session->__debugInfo());
//
//            return ++$this->session->getSection('cli')->value;
//        });
//
//        $result = $this->get('/');
//        $this->assertSame(200, $result->getStatusCode());
//
//        $cookies = $this->fetchCookies($result->getHeader('Set-Cookie'));
//        $this->assertArrayHasKey('SID', $cookies);
//
//        $result = $this->get('/', [], [], [
//            'SID' => $cookies['SID']
//        ]);
//        $this->assertSame(200, $result->getStatusCode());
//        $this->assertSame('2', $result->getBody()->__toString());
//
//        $this->http->setEndpoint(function () {
//            $this->session->destroy();
//            $this->assertFalse($this->session->isStarted());
//
//            return ++$this->session->getSection('cli')->value;
//        });
//
//        $result = $this->get('/', [], [], [
//            'SID' => $cookies['SID']
//        ]);
//        $this->assertSame(200, $result->getStatusCode());
//        $this->assertSame('1', $result->getBody()->__toString());
//    }


////    public function tearDown()
////    {
////        if (session_status() == PHP_SESSION_ACTIVE) {
////            session_abort();
////        }
////    }
////
////    public function testSessionResumeButSessionSignatureChanged()
////    {
////        $this->http->riseMiddleware(SessionStarter::class);
////
////        $this->http->setEndpoint(function () {
////            return ++$this->session->getSection('cli')->value;
////        });
////
////        $result = $this->get('/');
////        $this->assertSame(200, $result->getStatusCode());
////        $this->assertSame('1', $result->getBody()->__toString());
////
////        $this->assertFalse($this->container->hasInstance(SessionInterface::class));
////
////        $cookies = $this->fetchCookies($result->getHeader('Set-Cookie'));
////        $this->assertArrayHasKey('SID', $cookies);
////
////        $oldSID = $cookies['SID'];
////
////        $result = $this->get('/', [], [
////            'User-Agent' => 'new client'
////        ], [
////            'SID' => $cookies['SID']
////        ]);
////        $this->assertSame(200, $result->getStatusCode());
////        $this->assertSame('1', $result->getBody()->__toString());
////
////        $cookies = $this->fetchCookies($result->getHeader('Set-Cookie'));
////        $this->assertArrayHasKey('SID', $cookies);
////
////        $this->assertNotEquals($oldSID, $cookies['SID']);
////
////        //WILL DESTROY OLD SESSION DATA AND MAKE NEW SESSION
////
////        $result = $this->get('/', [], [
////            'User-Agent' => 'new client'
////        ], [
////            'SID' => $cookies['SID']
////        ]);
////        $this->assertSame(200, $result->getStatusCode());
////        $this->assertSame('1', $result->getBody()->__toString());
////
////        //Old session is destroyed
////        $result = $this->get('/', [], [], [
////            'SID' => $oldSID
////        ]);
////        $this->assertSame(200, $result->getStatusCode());
////        $this->assertSame('1', $result->getBody()->__toString());
////    }
}