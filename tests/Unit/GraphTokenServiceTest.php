<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Doedoe123boop\Sharepoint\Services\GraphTokenService;
use Doedoe123boop\Sharepoint\Exceptions\SharepointAuthException;

beforeEach(function () {
    config([
        'sharepoint.tenant_id' => 'test-tenant-id',
        'sharepoint.client_id' => 'test-client-id',
        'sharepoint.client_secret' => 'test-client-secret',
        'sharepoint.token_cache_ttl' => 3500,
    ]);
});

it('acquires and caches an access token', function () {
    Http::fake([
        'login.microsoftonline.com/*' => Http::response([
            'access_token' => 'test-token-123',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ]),
    ]);

    $service = new GraphTokenService;
    $token = $service->getAccessToken();

    expect($token)->toBe('test-token-123');

    Http::assertSentCount(1);
});

it('returns cached token on subsequent calls', function () {
    Http::fake([
        'login.microsoftonline.com/*' => Http::response([
            'access_token' => 'test-token-cached',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ]),
    ]);

    $service = new GraphTokenService;
    $service->getAccessToken();
    $service->getAccessToken();

    // Only one HTTP call — second was cached
    Http::assertSentCount(1);
});

it('refreshes the token by clearing cache', function () {
    Http::fake([
        'login.microsoftonline.com/*' => Http::sequence()
            ->push(['access_token' => 'token-1', 'token_type' => 'Bearer', 'expires_in' => 3600])
            ->push(['access_token' => 'token-2', 'token_type' => 'Bearer', 'expires_in' => 3600]),
    ]);

    $service = new GraphTokenService;

    $first = $service->getAccessToken();
    expect($first)->toBe('token-1');

    $second = $service->refreshToken();
    expect($second)->toBe('token-2');

    Http::assertSentCount(2);
});

it('clears the cache', function () {
    Cache::put('sharepoint.graph_access_token', 'cached-token', 3500);

    $service = new GraphTokenService;
    $service->clearCache();

    expect(Cache::get('sharepoint.graph_access_token'))->toBeNull();
});

it('throws SharepointAuthException on failed token acquisition', function () {
    Http::fake([
        'login.microsoftonline.com/*' => Http::response([
            'error' => 'invalid_client',
            'error_description' => 'Client secret is invalid.',
        ], 401),
    ]);

    $service = new GraphTokenService;
    $service->getAccessToken();
})->throws(SharepointAuthException::class);

it('throws SharepointAuthException when no access_token in response', function () {
    Http::fake([
        'login.microsoftonline.com/*' => Http::response([
            'token_type' => 'Bearer',
        ]),
    ]);

    $service = new GraphTokenService;
    $service->getAccessToken();
})->throws(SharepointAuthException::class);
