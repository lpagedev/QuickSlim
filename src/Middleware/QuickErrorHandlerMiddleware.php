<?php
declare(strict_types=1);

namespace QuickSlim\Middleware;

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
            $requestHeaders = $request->getHeaderLine('Access-Control-Request-Headers');

            header('Content-Type: application/json', true, 500);
            header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? $_SERVER['REMOTE_ADDR']);
            header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
            header('Access-Control-Allow-Headers: ' . $requestHeaders);
            header('Access-Control-Allow-Credentials: true');

            $message = $exception->getMessage();
            try {
                $message = $this->ReplaceError($exception->getMessage());
            } catch (Throwable $exception) {
                $message .= PHP_EOL . $exception->getMessage();
            }

            if ($this->_showErrors) print json_encode(['message' => $message, 'exception' => $exception->getMessage(), 'file' => $exception->getFile(), 'line' => $exception->getLine(), 'code' => $exception->getCode(), 'trace' => $exception->getTrace()]); else
                print json_encode(['message' => $message, 'exception' => $exception->getMessage(), 'file' => '', 'line' => 0, 'code' => '', 'trace' => []]);
            exit();
        }
    }

    private function ReplaceError(string $errorMessage = ""): string
    {
        foreach ($this->_config as $replacement) {
            if (str_contains($errorMessage, $replacement->getErrorText())) return $replacement->getReplacementText();

            $regexMatches = [];
            if (preg_match("/{$replacement->getErrorText()}/", $errorMessage, $regexMatches)) {
                $replacementText = $replacement->getReplacementText();
                for ($count = 1; $count < count($regexMatches); $count++) {
                    $replacementText = preg_replace('/([^\\\]?\$\d+)/', $regexMatches[$count], $replacementText, 1);
                }
                return $replacementText;
            }

        }
        return $errorMessage;
    }
}
