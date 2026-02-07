<?php

use Doedoe123boop\Sharepoint\Contracts\TokenServiceInterface;
use Doedoe123boop\Sharepoint\Exceptions\SharepointApiException;
use Doedoe123boop\Sharepoint\Exceptions\SharepointAuthException;
use Doedoe123boop\Sharepoint\Exceptions\SharepointFileNotFoundException;
use Doedoe123boop\Sharepoint\Services\SharepointService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'sharepoint.site_id' => 'test-site-id',
        'sharepoint.drive_id' => 'test-drive-id',
        'sharepoint.default_folder' => '/',
        'sharepoint.retry_on_failure' => true,
        'sharepoint.max_retries' => 2,
    ]);

    $this->tokenService = Mockery::mock(TokenServiceInterface::class);
    $this->tokenService->shouldReceive('getAccessToken')->andReturn('test-token');

    $this->service = new SharepointService($this->tokenService);
});

it('lists files in the root folder', function () {
    Http::fake([
        'graph.microsoft.com/*' => Http::response([
            'value' => [
                [
                    'id' => 'folder-1',
                    'name' => 'Documents',
                    'folder' => ['childCount' => 5],
                    'size' => 0,
                    'lastModifiedDateTime' => '2025-12-01T10:00:00Z',
                    'webUrl' => 'https://sharepoint.com/Documents',
                ],
                [
                    'id' => 'file-1',
                    'name' => 'report.pdf',
                    'file' => ['mimeType' => 'application/pdf'],
                    'size' => 1024000,
                    'lastModifiedDateTime' => '2025-12-15T14:30:00Z',
                    'webUrl' => 'https://sharepoint.com/report.pdf',
                ],
            ],
        ]),
    ]);

    $items = $this->service->listFiles('/');

    expect($items)->toHaveCount(2)
        ->and($items[0])->toMatchArray([
            'id' => 'folder-1',
            'name' => 'Documents',
            'type' => 'folder',
            'mimeType' => null,
        ])
        ->and($items[1])->toMatchArray([
            'id' => 'file-1',
            'name' => 'report.pdf',
            'type' => 'file',
            'mimeType' => 'application/pdf',
        ]);
});

it('lists files in a subfolder', function () {
    Http::fake([
        'graph.microsoft.com/*/root:/Documents:/children*' => Http::response([
            'value' => [
                [
                    'id' => 'sub-file-1',
                    'name' => 'notes.txt',
                    'file' => ['mimeType' => 'text/plain'],
                    'size' => 256,
                    'lastModifiedDateTime' => '2025-11-01T08:00:00Z',
                    'webUrl' => 'https://sharepoint.com/Documents/notes.txt',
                ],
            ],
        ]),
    ]);

    $items = $this->service->listFiles('Documents');

    expect($items)->toHaveCount(1)
        ->and($items[0]['name'])->toBe('notes.txt');
});

it('uploads a file', function () {
    Http::fake([
        'graph.microsoft.com/*' => Http::response([
            'id' => 'new-file-id',
            'name' => 'upload.txt',
            'size' => 512,
            'webUrl' => 'https://sharepoint.com/upload.txt',
        ]),
    ]);

    $result = $this->service->uploadFile('/', 'upload.txt', 'file contents here');

    expect($result)->toMatchArray([
        'id' => 'new-file-id',
        'name' => 'upload.txt',
        'size' => 512,
    ]);

    Http::assertSent(function ($request) {
        return $request->method() === 'PUT'
            && str_contains($request->url(), 'upload.txt:/content');
    });
});

it('deletes a file', function () {
    Http::fake([
        'graph.microsoft.com/*' => Http::response(null, 204),
    ]);

    $result = $this->service->deleteFile('item-to-delete');

    expect($result)->toBeTrue();

    Http::assertSent(function ($request) {
        return $request->method() === 'DELETE'
            && str_contains($request->url(), 'items/item-to-delete');
    });
});

it('throws SharepointFileNotFoundException on 404', function () {
    Http::fake([
        'graph.microsoft.com/*' => Http::response([
            'error' => [
                'code' => 'itemNotFound',
                'message' => 'The resource could not be found.',
            ],
        ], 404),
    ]);

    $this->service->listFiles('/nonexistent');
})->throws(SharepointFileNotFoundException::class);

it('retries on 401 and succeeds', function () {
    $this->tokenService = Mockery::mock(TokenServiceInterface::class);
    $this->tokenService->shouldReceive('getAccessToken')->andReturn('test-token');
    $this->tokenService->shouldReceive('refreshToken')->once()->andReturn('new-token');

    $service = new SharepointService($this->tokenService);

    Http::fake([
        'graph.microsoft.com/*' => Http::sequence()
            ->push(['error' => ['code' => 'InvalidAuthenticationToken', 'message' => 'Expired']], 401)
            ->push(['value' => []], 200),
    ]);

    $items = $service->listFiles('/');

    expect($items)->toBeArray()->toBeEmpty();
});

it('throws SharepointAuthException after max retries on 401', function () {
    $this->tokenService = Mockery::mock(TokenServiceInterface::class);
    $this->tokenService->shouldReceive('getAccessToken')->andReturn('test-token');
    $this->tokenService->shouldReceive('refreshToken')->andReturn('still-bad-token');

    $service = new SharepointService($this->tokenService);

    Http::fake([
        'graph.microsoft.com/*' => Http::response(
            ['error' => ['code' => 'InvalidAuthenticationToken', 'message' => 'Expired']],
            401,
        ),
    ]);

    $service->listFiles('/');
})->throws(SharepointAuthException::class);

it('throws SharepointApiException on 500 response', function () {
    Http::fake([
        'graph.microsoft.com/*' => Http::response([
            'error' => [
                'code' => 'generalException',
                'message' => 'Internal server error.',
            ],
        ], 500),
    ]);

    $this->service->listFiles('/');
})->throws(SharepointApiException::class);

it('creates a folder', function () {
    Http::fake([
        'graph.microsoft.com/*' => Http::response([
            'id' => 'new-folder-id',
            'name' => 'New Folder',
            'webUrl' => 'https://sharepoint.com/New%20Folder',
        ]),
    ]);

    $result = $this->service->createFolder('/', 'New Folder');

    expect($result)->toMatchArray([
        'id' => 'new-folder-id',
        'name' => 'New Folder',
    ]);

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && str_contains($request->url(), 'root/children');
    });
});
