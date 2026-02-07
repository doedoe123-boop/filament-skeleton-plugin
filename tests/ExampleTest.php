<?php

use Doedoe123boop\Sharepoint\Contracts\SharepointServiceInterface;
use Doedoe123boop\Sharepoint\Contracts\TokenServiceInterface;

it('resolves the token service from the container', function () {
    expect(app(TokenServiceInterface::class))->toBeInstanceOf(TokenServiceInterface::class);
});

it('resolves the sharepoint service from the container', function () {
    expect(app(SharepointServiceInterface::class))->toBeInstanceOf(SharepointServiceInterface::class);
});

it('loads the sharepoint config', function () {
    expect(config('sharepoint'))->toBeArray()
        ->toHaveKeys(['tenant_id', 'client_id', 'client_secret', 'site_id', 'drive_id']);
});
