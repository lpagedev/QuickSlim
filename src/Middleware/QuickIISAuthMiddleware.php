<?php
declare(strict_types=1);

namespace QuickSlim\Middleware;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use QuickSlim\Config\QuickIISAuthConfig;
use QuickSlim\Responses\QuickJsonResponse;

class QuickIISAuthMiddleware implements MiddlewareInterface
{
    private QuickIISAuthConfig $config;

    public function __construct(QuickIISAuthConfig $config = null)
    {
        $this->config = $config ?? new QuickIISAuthConfig();
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface|QuickJsonResponse
    {
        // If IIS has authenticated the user hand off the request.
        if (isset($_SERVER['AUTH_TYPE']) && in_array($_SERVER['AUTH_TYPE'], ['Negotiate', 'NTLM'])) {
            if (isset($_SERVER['AUTH_USER']) && strlen($_SERVER['AUTH_USER']) > 0) {
                if ($this->config->hasValidDomain($_SERVER['AUTH_USER'])) return $handler->handle($request);
            }
        }
        return new QuickJsonResponse("401 - Not Authorized", StatusCodeInterface::STATUS_UNAUTHORIZED);
    }
}
