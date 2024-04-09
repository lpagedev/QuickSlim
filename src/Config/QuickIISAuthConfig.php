<?php

namespace QuickSlim\Config;

class QuickIISAuthConfig
{
    private string|array|null $domain;

    public function __construct(array|string $domain = null)
    {
        $this->domain = $domain;
    }

    public function hasValidDomain(string $auth_user): bool
    {
        if ($this->domain === null) return true;
        if ($this->domain === $auth_user) return true;
        if (str_contains($auth_user, '\\') and explode('\\', $_SERVER['AUTH_USER'])[0] === $auth_user) return true;
        if (is_array($this->domain) && in_array($auth_user, $this->domain)) return true;
        return false;
    }
}
