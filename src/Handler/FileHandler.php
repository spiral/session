<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Session\Handler;

use Spiral\Files\FilesInterface;

/**
 * Stores session data in file.
 */
final class FileHandler implements \SessionHandlerInterface
{
    /**
     * @var FilesInterface
     */
    protected $files;

    /**
     * @var string
     */
    protected $directory = '';

    /**
     * @param FilesInterface $files
     * @param string         $directory
     * @param int            $lifetime Default session lifetime.
     */
    public function __construct(FilesInterface $files, string $directory, int $lifetime = 0)
    {
        $this->files = $files;
        $this->directory = $directory;
    }

    /**
     * @inheritdoc
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     * @codeCoverageIgnore
     */
    public function destroy($session_id): bool
    {
        return $this->files->delete($this->getFilename($session_id));
    }

    /**
     * @inheritdoc
     * @codeCoverageIgnore
     */
    public function gc($maxlifetime): void
    {
        foreach ($this->files->getFiles($this->directory) as $filename) {
            if ($this->files->time($filename) < time() - $maxlifetime) {
                $this->files->delete($filename);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function open($save_path, $session_id): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function read($session_id): string
    {
        return $this->files->exists($this->getFilename($session_id))
            ? $this->files->read($this->getFilename($session_id))
            : '';
    }

    /**
     * @inheritdoc
     */
    public function write($session_id, $session_data): bool
    {
        return $this->files->write(
            $this->getFilename($session_id),
            $session_data,
            FilesInterface::RUNTIME,
            true
        );
    }

    /**
     * Session data filename.
     *
     * @param string $session_id
     *
     * @return string
     */
    protected function getFilename($session_id): string
    {
        return "{$this->directory}/{$session_id}";
    }
}
