<?php
declare(strict_types=1);

namespace QuickSlim\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use QuickSlim\Config\QuickCorsConfig;
use QuickSlim\Responses\QuickJsonResponse;
use Slim\Psr7\Request;

class QuickCorsMiddleware
{
    private QuickCorsConfig $config;

    public function __construct(QuickCorsConfig $config = null)
    {
        $this->config = $config ?? new QuickCorsConfig();
    }

    public function __invoke(Request $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // The method options is used for CORS, Cross Origin Resource Sharing, should always return something, just never data.
        // https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
        $method = strtolower($request->getMethod());
        if ($method === 'options') $response = new QuickJsonResponse('');
        else $response = $handler->handle($request);

        $requestHeaders = $request->getHeaderLine('Access-Control-Request-Headers');
        $response = $response->withHeader('Access-Control-Allow-Origin', $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? $_SERVER['REMOTE_ADDR']);
        $response = $response->withHeader('Access-Control-Allow-Methods', 'GET,POST,PUT,DELETE,OPTIONS');
        $response = $response->withHeader('Access-Control-Allow-Headers', $requestHeaders);

        // Allow requests with Authorization header
        if ($this->config->isAllowCredentials()) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }
}
