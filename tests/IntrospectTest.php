<?php

namespace DesignMyNight\Laravel\OAuth2\Tests;

use DesignMyNight\Laravel\OAuth2\Introspect;
use DesignMyNight\Laravel\OAuth2\IntrospectClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request as PsrRequest;
use GuzzleHttp\Psr7\Response;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Orchestra\Testbench\TestCase;
use Throwable;

class IntrospectTest extends TestCase
{
    public function test_client_error_response_with_error_title_throws_authentication_exception_with_title(): void
    {
        $exception = new ClientException(
            'Unauthorized',
            new PsrRequest('POST', 'https://auth.test/introspect'),
            new Response(401, [], json_encode(['error' => ['title' => 'Token revoked']]))
        );

        $this->assertAuthenticationException($exception, 'Token revoked');
    }

    public function test_server_error_response_throws_authentication_exception(): void
    {
        $exception = new ServerException(
            'Server error',
            new PsrRequest('POST', 'https://auth.test/introspect'),
            new Response(500, [], 'Internal Server Error')
        );

        $this->assertAuthenticationException($exception, 'Server error');
    }

    public function test_request_exception_without_response_throws_authentication_exception(): void
    {
        $exception = new RequestException('Request failed', new PsrRequest('POST', 'https://auth.test/introspect'));

        $this->assertAuthenticationException($exception, 'Request failed');
    }

    private function assertAuthenticationException(Throwable $clientException, string $expectedMessage): void
    {
        $client = $this->createStub(IntrospectClient::class);
        $client->method('introspect')->willThrowException($clientException);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer invalid-token');

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage($expectedMessage);

        (new Introspect($client, $request))->tokenIsActive();
    }
}
