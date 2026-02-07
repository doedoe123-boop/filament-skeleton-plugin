<?php

use Doedoe123boop\Sharepoint\Contracts\SharepointServiceInterface;
use Illuminate\Support\Facades\Http;

it('runs the test-connection command successfully', function () {
    $this->mock(SharepointServiceInterface::class, function ($mock) {
        $mock->shouldReceive('listFiles')
            ->with('/')
            ->once()
            ->andReturn([
                [
                    'id' => 'file-1',
                    'name' => 'document.pdf',
                    'type' => 'file',
                    'size' => 2048,
                    'lastModified' => '2025-12-01T10:00:00Z',
                    'webUrl' => 'https://sharepoint.com/document.pdf',
                    'mimeType' => 'application/pdf',
                ],
            ]);
    });

    $this->artisan('sharepoint:test-connection')
        ->assertSuccessful()
        ->expectsOutputToContain('Connected successfully');
});

it('handles connection failure gracefully', function () {
    $this->mock(SharepointServiceInterface::class, function ($mock) {
        $mock->shouldReceive('listFiles')
            ->with('/')
            ->once()
            ->andThrow(new \RuntimeException('Connection refused'));
    });

    $this->artisan('sharepoint:test-connection')
        ->assertFailed()
        ->expectsOutputToContain('Connection failed');
});

it('reports empty folder', function () {
    $this->mock(SharepointServiceInterface::class, function ($mock) {
        $mock->shouldReceive('listFiles')
            ->with('/')
            ->once()
            ->andReturn([]);
    });

    $this->artisan('sharepoint:test-connection')
        ->assertSuccessful()
        ->expectsOutputToContain('empty');
});

it('accepts a custom folder option', function () {
    $this->mock(SharepointServiceInterface::class, function ($mock) {
        $mock->shouldReceive('listFiles')
            ->with('Documents/Reports')
            ->once()
            ->andReturn([]);
    });

    $this->artisan('sharepoint:test-connection --folder=Documents/Reports')
        ->assertSuccessful();
});
