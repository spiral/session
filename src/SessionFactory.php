<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Session;

use Psr\Container\ContainerExceptionInterface;
use Spiral\Core\Container\SingletonInterface;
use Spiral\Core\FactoryInterface;
use Spiral\Session\Config\SessionConfig;
use Spiral\Session\Exception\MultipleSessionException;
use Spiral\Session\Exception\SessionException;

/**
 * Initiates session instance and configures session handlers.
 */
class SessionFactory implements SingletonInterface
{
    /**
     * @var \Spiral\Session\Config\SessionConfig
     */
    private $config;

    /**
     * @var \Spiral\Core\FactoryInterface
     */
    private $factory;

    /**
     * @param \Spiral\Session\Config\SessionConfig $config
     * @param \Spiral\Core\FactoryInterface        $factory
     */
    public function __construct(SessionConfig $config, FactoryInterface $factory)
    {
        $this->config = $config;
        $this->factory = $factory;
    }

    /**
     * @param string $clientSignature User specific token, does not provide full security but
     *                                hardens session transfer.
     * @param string $id              When null - expect php to create session automatically.
     *
     * @return \Spiral\Session\SessionInterface
     *
     * @throws \Spiral\Session\Exception\MultipleSessionException
     */
    public function initSession(string $clientSignature, string $id = null): SessionInterface
    {
        if (session_status() == PHP_SESSION_ACTIVE) {
            throw new MultipleSessionException("Unable to initiate session, session already started");
        }

        // disable native cookies
        ini_set('session.use_cookies', false);

        //Initiating proper session handler
        if (!empty($this->config->getHandler())) {
            try {
                $handler = $this->config->getHandler()->resolve($this->factory);
            } catch (\Throwable|ContainerExceptionInterface $e) {
                throw new SessionException($e->getMessage(), $e->getCode(), $e);
            }

            session_set_save_handler($handler, true);
        }

        return $this->factory->make(Session::class, [
            'clientSignature' => $clientSignature,
            'lifetime'        => $this->config->getLifetime(),
            'id'              => $id
        ]);
    }
}