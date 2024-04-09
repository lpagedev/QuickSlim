<?php
declare(strict_types=1);

namespace QuickSlim\Responses;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Headers;
use Slim\Psr7\Response;

class QuickResponse extends Response implements ResponseInterface
{
    public function __construct(mixed $content, string $contentType = 'text/plain', int $status = StatusCodeInterface::STATUS_OK)
    {
        $headers = new Headers();
        $headers->addHeader('Content-Type', $contentType);
        parent::__construct($status, $headers);
        $this->getBody()->write($content);
    }
}
