<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

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
final class SessionFactory implements SingletonInterface
{
    /** @var SessionConfig */
    private $config;

    /** @var FactoryInterface */
    private $factory;

    /**
     * @param SessionConfig    $config
     * @param FactoryInterface $factory
     */
    public function __construct(SessionConfig $config, FactoryInterface $factory)
    {
        $this->config = $config;
        $this->factory = $factory;
    }

    /**
     * @param string      $clientSignature User specific token, does not provide full security but
     *                                     hardens session transfer.
     * @param string|null $id              When null - expect php to create session automatically.
     * @return SessionInterface
     *
     */
    public function initSession(string $clientSignature, string $id = null): SessionInterface
    {
        if ((int)session_status() === PHP_SESSION_ACTIVE) {
            throw new MultipleSessionException('Unable to initiate session, session already started');
        }

        //Initiating proper session handler
        if ($this->config->getHandler() !== null) {
            try {
                $handler = $this->config->getHandler()->resolve($this->factory);
            } catch (\Throwable | ContainerExceptionInterface $e) {
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
