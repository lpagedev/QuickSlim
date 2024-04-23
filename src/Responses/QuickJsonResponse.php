<?php
declare(strict_types=1);

namespace QuickSlim\Responses;

use Fig\Http\Message\StatusCodeInterface;

class QuickJsonResponse extends QuickResponse
{
    public function __construct(mixed $content, int $status = StatusCodeInterface::STATUS_OK)
    {
        parent::__construct(json_encode($content) ?? "['data': '$content']", 'application/json', $status);
    }
}
