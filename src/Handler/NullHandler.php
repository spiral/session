<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Session\Handler;

/**
 * Blackhole.
 */
final class NullHandler implements \SessionHandlerInterface
{
    /**
     * @inheritdoc
     */
    public function close()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function destroy($session_id)
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function gc($maxlifetime)
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function open($save_path, $session_id)
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function read($session_id)
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function write($session_id, $session_data)
    {
        return true;
    }
}