<?php
declare(strict_types=1);

namespace QuickSlim\Config;

class QuickCorsConfig
{
    private bool $allowCredentials;

    public function __construct(bool $allowCredentials = false)
    {
        $this->allowCredentials = $allowCredentials;
    }

    public function isAllowCredentials(): bool
    {
        return $this->allowCredentials;
    }

    public function setAllowCredentials(bool $allowCredentials): void
    {
        $this->allowCredentials = $allowCredentials;
    }
}
