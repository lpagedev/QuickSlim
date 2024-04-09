<?php
declare(strict_types=1);

namespace QuickSlim\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use QuickSlim\Responses\QuickJsonResponse;
use Slim\Exception\HttpNotImplementedException;

class QuickFirewallMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        throw new HttpNotImplementedException($request,'Not Implemented');

        // TODO Create options for implementing a firewall, ip filters and block/allow lists. Origin checking and routing, request type blocking and so forth...
        // return $handler->handle($request);
    }
}
