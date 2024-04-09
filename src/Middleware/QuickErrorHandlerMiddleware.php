<?php
declare(strict_types=1);

namespace QuickSlim\Middleware;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use QuickSlim\Config\QuickErrorHandlerConfig;
use QuickSlim\Responses\QuickJsonResponse;
use Throwable;

class QuickErrorHandlerMiddleware implements MiddlewareInterface
{
    private mixed $_showErrors;
    private array $_config;

    /**
     * @param QuickErrorHandlerConfig[] $config
     * @param bool $showErrors
     */
    public function __construct(array $config, bool $showErrors = false)
    {
        $this->_showErrors = $showErrors;
        $this->_config = $config;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface|QuickJsonResponse
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $exception) {
            if ($this->_showErrors) return new QuickJsonResponse(['message' => $this->ReplaceError($exception->getMessage()), 'exception' => $exception->getMessage(), 'file' => $exception->getFile(), 'line' => $exception->getLine(), 'code' => $exception->getCode(), 'trace' => $exception->getTrace(),], StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
            return new QuickJsonResponse(['message' => $this->ReplaceError($exception->getMessage())], StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    private function ReplaceError(string $errorMessage = ""): string
    {
        foreach ($this->_config as $replacement) {
            if (str_contains($errorMessage, $replacement['error'])) return $replacement['replacement'];
            $regexMatches = [];
            $regexReplacementMatches = [];
            if (preg_match("/{$replacement->getErrorText()}/", $errorMessage, $regexMatches)) if (preg_match('(\$(\d+))', $replacement->getReplacementText(), $regexReplacementMatches)) for ($count = 0; $count < count($regexReplacementMatches); $count++) $replacement['replacement'] = str_replace($regexReplacementMatches[$count], $regexMatches[(int)$regexReplacementMatches[1]], $replacement->getReplacementText()); else
                return $replacement['replacement'];

        }
        return $errorMessage;
    }
}
