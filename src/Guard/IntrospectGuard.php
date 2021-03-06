<?php

namespace DesignMyNight\Laravel\OAuth2\Guard;

use DesignMyNight\Laravel\OAuth2\Introspect;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;

class IntrospectGuard implements Guard
{
    protected $user = false;

    public function __construct(Introspect $introspect)
    {
        $this->introspect = $introspect;
    }

    public function authenticate()
    {
        return $this->check();
    }

    public function check()
    {
        return !is_null($this->user());
    }

    public function guest()
    {
        return !$this->check();
    }

    public function id()
    {
        return $this->check() ? $this->user()->getKey() : null;
    }

    public function user()
    {
        if ($this->user === false) {
            try {
                $this->user = $this->introspect
                    ->verifyToken()
                    ->getUser();
            } catch (AuthenticationException $e) {
                $this->user = null;
            }
        }

        return $this->user;
    }

    public function setUser(Authenticatable $user): void
    {
        $this->user = $user;
    }

    public function validate(array $credentials = []): bool
    {
        return true;
    }
}
