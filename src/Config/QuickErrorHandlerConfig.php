<?php
declare(strict_types=1);

namespace QuickSlim\Config;

class QuickErrorHandlerConfig
{
    private string $_replacementText;
    private string $_errorText;

    public function __construct(string $errorText, string $replacementText)
    {
        $this->_errorText = $errorText;
        $this->_replacementText = $replacementText;
    }

    public function getReplacementText(): string
    {
        return $this->_replacementText;
    }

    public function getErrorText(): string
    {
        return $this->_errorText;
    }
}
