<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Http;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Spiral\Boot\DispatcherInterface;
use Spiral\Boot\FinalizerInterface;
use Spiral\Exceptions\HtmlHandler;
use Spiral\Snapshots\SnapshotInterface;
use Spiral\Snapshots\SnapshotterInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;
use Zend\HttpHandlerRunner\Emitter\EmitterInterface;

final class SapiDispatcher implements DispatcherInterface
{
    /** @var FinalizerInterface */
    private $finalizer;

    /** @var ContainerInterface */
    private $container;

    /**
     * @param FinalizerInterface $finalizer
     * @param ContainerInterface $container
     */
    public function __construct(FinalizerInterface $finalizer, ContainerInterface $container)
    {
        $this->finalizer = $finalizer;
        $this->container = $container;
    }

    /**
     * @inheritdoc
     */
    public function canServe(): bool
    {
        return php_sapi_name() != 'cli';
    }

    /**
     * @inheritdoc
     */
    public function serve(EmitterInterface $emitter = null)
    {
        /**
         * @var HttpCore         $http
         * @var EmitterInterface $emitter
         */
        $http = $this->container->get(HttpCore::class);
        $emitter = $emitter ?? $this->container->get(EmitterInterface::class);

        try {
            $response = $http->handle($this->initRequest());
            $emitter->emit($response);
        } catch (\Throwable $e) {
            $this->handleException($emitter, $e);
        } finally {
            $this->finalizer->finalize(false);
        }
    }

    /**
     * @inheritdoc
     */
    protected function initRequest(): ServerRequestInterface
    {
        return ServerRequestFactory::fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);
    }

    /**
     * @param EmitterInterface $emitter
     * @param \Throwable       $e
     */
    protected function handleException(EmitterInterface $emitter, \Throwable $e)
    {
        $handler = new HtmlHandler(HtmlHandler::INVERTED);

        try {
            /** @var SnapshotInterface $snapshot */
            $this->container->get(SnapshotterInterface::class)->register($e);
        } catch (\Throwable|ContainerExceptionInterface $se) {
            // nothing to report
        }

        // Reporting system (non handled) exception directly to the client
        $response = new Response('php://memory', 500);
        $response->getBody()->write(
            $handler->renderException($e, HtmlHandler::VERBOSITY_VERBOSE)
        );

        $emitter->emit($response);
    }
}