<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Session\Configs;

use Spiral\Core\Container\Autowire;
use Spiral\Core\InjectableConfig;

/**
 * SessionManager configuration.
 */
class SessionConfig extends InjectableConfig
{
    const CONFIG = 'session';

    /**
     * @var array
     */
    protected $config = [
        'lifetime' => 86400,
        'cookie'   => 'SID',
        'secure'   => false,
        'handler'  => null,
        'handlers' => []
    ];

    /**
     * List of headers to be used for session signature.
     *
     * @return array
     */
    public function getSignatureHeaders(): array
    {
        return [
            'User-Agent',
            'Accept-Language'
        ];
    }

    /**
     * @return int
     */
    public function getLifetime(): int
    {
        return $this->config['lifetime'];
    }

    /**
     * @return string
     */
    public function getCookie(): string
    {
        return $this->config['cookie'];
    }

    /**
     * @return bool
     */
    public function isSecure(): bool
    {
        return $this->config['secure'] ?? false;
    }

    /**
     * Get handler autowire options.
     *
     * @return Autowire
     */
    public function getHandler(): ?Autowire
    {
        if (empty($this->config['handler'])) {
            return null;
        }

        if (class_exists($this->config['handler'])) {
            return new Autowire($this->config['handler']);
        }

        $handler = $this->config['handlers'][$this->config['handler']];

        return new Autowire($handler['class'], $handler['options']);
    }
}