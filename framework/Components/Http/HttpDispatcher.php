<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 * @copyright ©2009-2015
 */
namespace Spiral\Components\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Spiral\Components\Debug\Snapshot;
use Spiral\Components\Http\Request\InputStream;
use Spiral\Components\Http\Request\Uri;
use Spiral\Core\Component;
use Spiral\Core\Core;
use Spiral\Core\Dispatcher\ClientException;
use Spiral\Core\DispatcherInterface;

class HttpDispatcher extends Component implements DispatcherInterface
{
    /**
     * Required traits.
     */
    use Component\SingletonTrait, Component\LoggerTrait, Component\EventsTrait, Component\ConfigurableTrait;

    /**
     * Declares to IoC that component instance should be treated as singleton.
     */
    const SINGLETON = 'http';

    /**
     * Original server request generated by spiral while starting HttpDispatcher.
     *
     * @var ServerRequest
     */
    protected $baseRequest = null;

    /**
     * New HttpDispatcher instance.
     *
     * @param Core $core
     */
    public function __construct(Core $core)
    {
        //$this->config = $core->loadConfig('http');
    }

    /**
     * Letting dispatcher to control application flow and functionality.
     *
     * @param Core $core
     */
    public function start(Core $core)
    {
        $uri = Uri::castUri($_SERVER);
        $input = new InputStream();

        $core->callAction('Controllers\HomeController', 'index', array(
            'uri'     => $uri,
            'headers' => $this->castHeaders($_SERVER)
        ));

        //    dump($this->castHeaders($_SERVER));

        //echo(StringHelper::formatBytes(memory_get_peak_usage()));

        //Cast request
        //pass to middleware(s) - this is where cookies processed, tokens checked and session handled, like big boys
        //MiddlewareRunner is required
        //perform
        //  route
        //  route specific dispatchers
        //  target controller/closure
        //dispatch
    }

    public function perform(RequestInterface $request)
    {
        //        //Create request scope ? or no?
        //        //if so, scope for what request type, only our? i think yes.
        //
        //        if ($request instanceof RequestInterface)
        //        {
        //            //making scope, maybe make scope INSIDE route with route attached to request AS data chunk?
        //        }
        //
        //        //perform, INNER MIDDLEWARE INSIDE ROUTE! i need RouterTrait! :)
        //        $response = null;
        //
        //        //End request scope ? or no?
        //        if ($request instanceof RequestInterface)
        //        {
        //            //ending scope
        //        }
        //
        //        return $this->wrapResponse($response);
    }

    protected function wrapResponse($response)
    {
        if ($response instanceof ResponseInterface)
        {
            return $response;
        }

        if (is_array($response) || $response instanceof \JsonSerializable)
        {
            //Making json response
            //return new JsonResponse($response); //something like this
        }

        return $response;
        //TODO: MAKE IT WORK
        //Making base response (string)
        //return $response;
    }

    /**
     * Dispatch provided request to client. Application will stop after this method call.
     *
     * @param ResponseInterface $response
     */
    public function dispatch(ResponseInterface $response)
    {
        $statusHeader = "HTTP/{$response->getProtocolVersion()} {$response->getStatusCode()}";
        header(rtrim("{$statusHeader} {$response->getReasonPhrase()}"));

        //Receive all headers but not cookies
        foreach ($response->getHeaders() as $header => $values)
        {
            $replace = true;
            foreach ($values as $value)
            {
                header("{$header}: {$value}", $replace);
                $replace = false;
            }
        }

        //Spiral request stores cookies separately with headers to make them easier to send
        if ($response instanceof Response)
        {
            foreach ($response->getCookies() as $cookie)
            {
                setcookie(
                    $cookie->getName(),
                    $cookie->getValue(),
                    $cookie->getExpire(),
                    $cookie->getPath(),
                    $cookie->getDomain(),
                    $cookie->getSecure(),
                    $cookie->getHttpOnly()
                );
            }
        }

        $stream = $response->getBody();
        header('Content-Size', $stream->getSize());

        // I need self sending requests in future.
        if (!$stream->isSeekable())
        {
            echo $stream->getContents();
        }
        else
        {
            ob_implicit_flush(true);
            while (!$stream->eof())
            {
                echo $stream->read(1024);
            }
        }

        exit();
    }

    /**
     * Generate response to represent specified error code. Response can include pure headers or may have attached view
     * file (based on HttpDispatcher configuration).
     *
     * @param int $code
     * @return ResponseInterface|Response
     */
    protected function errorResponse($code)
    {
        //todo: implement
    }

    /**
     * Every dispatcher should know how to handle exception snapshot provided by Debugger.
     *
     * @param Snapshot $snapshot
     * @return mixed
     */
    public function handleException(Snapshot $snapshot)
    {
        if ($snapshot->getException() instanceof ClientException)
        {
            //Simply showing something
            //$this->dispatch(new Response('ERROR VIEW LAYOUT IF PRESENTED', $snapshot->getException()->getCode()));
        }

        echo $snapshot->renderSnapshot();
        //500 error OR snapshot, based on options
    }

    /**
     * Generate list of incoming headers. getallheaders() function will be used with fallback to _SERVER array parsing.
     *
     * @param array $server
     * @return array
     */
    protected function castHeaders(array $server)
    {
        if (function_exists('getallheaders'))
        {
            $headers = getallheaders();
        }
        else
        {
            $headers = array();
            foreach ($server as $name => $value)
            {
                if ($name == 'HTTP_COOKIE')
                {
                    continue;
                }

                if (strpos($name, 'HTTP_') === 0)
                {
                    $name = str_replace(" ", "-", ucwords(strtolower(str_replace("_", " ", substr($name, 5)))));
                    $headers[$name] = $value;
                }
            }
        }

        unset($headers['Cookie']);

        return $headers;
    }
}