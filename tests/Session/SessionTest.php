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
use Spiral\Session\Bootloaders\SessionBootloader;
use Spiral\Session\Config\SessionConfig;
use Spiral\Session\Handler\FileHandler;
use Spiral\Session\SectionInterface;
use Spiral\Session\Session;
use Spiral\Session\SessionFactory;
use Spiral\Session\SessionInterface;
use Spiral\Session\SessionSection;

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

        $this->container->bind(SessionInterface::class, Session::class);
        $this->container->bind(SectionInterface::class, SessionSection::class);

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

    public function testResumeAndID()
    {
        $session = $this->factory->initSession('sig');
        $session->resume();
        $id = $session->getID();

        $this->assertTrue($session->isStarted());
        $session->commit();

        $this->assertFalse($session->isStarted());
        $this->assertSame($id, $session->getID());
        $this->assertFalse($session->isStarted());

        $session->destroy();
        $this->assertSame($id, $session->getID());

        $this->assertSame($id, $session->__debugInfo()['id']);
        $session->regenerateID();
        $this->assertNotSame($id, $session->getID());
    }

    public function testInjection()
    {
        $session = $this->factory->initSession('sig');
        $this->container->runScope([Session::class => $session], function () {
            $section = $this->container->get(SectionInterface::class, "default");
            $this->assertSame("default", $section->getName());
        });
    }

    public function testSignatures()
    {
        $session = $this->factory->initSession('sig');
        $session->getSection()->set("key", "value");
        $session->commit();

        $id = $session->getID();

        $session = $this->factory->initSession('sig', $id);
        $this->assertSame("value", $session->getSection()->get("key"));
        $this->assertSame($id, $session->getID());
        $session->commit();

        $session = $this->factory->initSession('different', $id);
        $this->assertSame(null, $session->getSection()->get("key"));
        $this->assertNotSame($id, $session->getID());
        $session->commit();

        // must be dead
        $session = $this->factory->initSession('sig', $id);
        $this->assertSame(null, $session->getSection()->get("key"));
        $this->assertNotSame($id, $session->getID());
        $session->commit();
    }
}