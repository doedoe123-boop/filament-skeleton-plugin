<?php

namespace Doedoe123boop\Sharepoint\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Doedoe123boop\Sharepoint\Contracts\TokenServiceInterface;
use Doedoe123boop\Sharepoint\Exceptions\SharepointApiException;
use Doedoe123boop\Sharepoint\Exceptions\SharepointAuthException;
use Doedoe123boop\Sharepoint\Contracts\SharepointServiceInterface;
use Doedoe123boop\Sharepoint\Exceptions\SharepointFileNotFoundException;

class SharepointService implements SharepointServiceInterface
{
    protected string $siteId;

    protected string $driveId;

    protected string $defaultFolder;

    protected bool $retryOnFailure;

    protected int $maxRetries;

    public function __construct(
        protected TokenServiceInterface $tokenService,
    ) {
        $this->siteId = config('sharepoint.site_id', '');
        $this->driveId = config('sharepoint.drive_id', '');
        $this->defaultFolder = config('sharepoint.default_folder', '/');
        $this->retryOnFailure = (bool) config('sharepoint.retry_on_failure', true);
        $this->maxRetries = (int) config('sharepoint.max_retries', 2);
    }

    public function listFiles(string $folder = '/'): array
    {
        $folder = $this->normalizePath($folder);

        $endpoint = $folder === '/'
            ? $this->driveUrl('/root/children')
            : $this->driveUrl("/root:/{$folder}:/children");

        $response = $this->request('get', $endpoint, [
            'query' => [
                '$select' => 'id,name,size,lastModifiedDateTime,webUrl,file,folder',
                '$orderby' => 'name asc',
            ],
        ]);

        return $this->normalizeListResponse($response->json());
    }

    public function uploadFile(string $folder, string $filename, mixed $contents): array
    {
        $folder = $this->normalizePath($folder);

        $path = $folder === '/'
            ? $filename
            : "{$folder}/{$filename}";

        $endpoint = $this->driveUrl("/root:/{$path}:/content");

        $response = $this->request('put', $endpoint, [
            'body' => is_resource($contents) ? stream_get_contents($contents) : $contents,
            'headers' => [
                'Content-Type' => 'application/octet-stream',
            ],
        ]);

        $data = $response->json();

        return [
            'id' => $data['id'],
            'name' => $data['name'],
            'size' => $data['size'] ?? 0,
            'webUrl' => $data['webUrl'] ?? '',
        ];
    }

    public function deleteFile(string $itemId): bool
    {
        $endpoint = $this->driveUrl("/items/{$itemId}");

        $this->request('delete', $endpoint);

        return true;
    }

    public function downloadFile(string $itemId): mixed
    {
        $endpoint = $this->driveUrl("/items/{$itemId}/content");

        $token = $this->tokenService->getAccessToken();

        $response = Http::withToken($token)
            ->withOptions(['stream' => true])
            ->get($endpoint);

        if ($response->status() === 404) {
            throw SharepointFileNotFoundException::forItemId($itemId);
        }

        if ($response->failed()) {
            $this->handleErrorResponse($response);
        }

        return $response->toPsrResponse()->getBody();
    }

    public function createFolder(string $parentPath, string $folderName): array
    {
        $parentPath = $this->normalizePath($parentPath);

        $endpoint = $parentPath === '/'
            ? $this->driveUrl('/root/children')
            : $this->driveUrl("/root:/{$parentPath}:/children");

        $response = $this->request('post', $endpoint, [
            'json' => [
                'name' => $folderName,
                'folder' => new \stdClass,
                '@microsoft.graph.conflictBehavior' => 'rename',
            ],
        ]);

        $data = $response->json();

        return [
            'id' => $data['id'],
            'name' => $data['name'],
            'webUrl' => $data['webUrl'] ?? '',
        ];
    }

    /**
     * Execute an HTTP request to Microsoft Graph API with automatic retry on 401.
     */
    protected function request(string $method, string $url, array $options = []): Response
    {
        $attempt = 0;

        do {
            $token = $this->tokenService->getAccessToken();

            $pendingRequest = Http::withToken($token);

            if (isset($options['headers'])) {
                $pendingRequest = $pendingRequest->withHeaders($options['headers']);
            }

            $response = match ($method) {
                'get' => $pendingRequest->get($url, $options['query'] ?? []),
                'post' => $pendingRequest->post($url, $options['json'] ?? $options['body'] ?? []),
                'put' => $pendingRequest->withBody(
                    $options['body'] ?? '',
                    $options['headers']['Content-Type'] ?? 'application/octet-stream'
                )->put($url),
                'delete' => $pendingRequest->delete($url),
                'patch' => $pendingRequest->patch($url, $options['json'] ?? []),
                default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
            };

            // If 204 No Content (successful delete), return immediately
            if ($response->status() === 204) {
                return $response;
            }

            // If unauthorized and retries are enabled, refresh token and retry
            if ($response->status() === 401 && $this->retryOnFailure && $attempt < $this->maxRetries) {
                $this->tokenService->refreshToken();
                $attempt++;

                continue;
            }

            // If still 401 after retries, throw auth exception
            if ($response->status() === 401) {
                throw SharepointAuthException::tokenExpiredAfterRetry();
            }

            if ($response->status() === 404) {
                throw SharepointFileNotFoundException::forPath($url);
            }

            if ($response->failed()) {
                $this->handleErrorResponse($response);
            }

            return $response;
        } while ($attempt <= $this->maxRetries);

        throw SharepointAuthException::tokenExpiredAfterRetry();
    }

    /**
     * Build the drive-scoped URL for Microsoft Graph API.
     */
    protected function driveUrl(string $path): string
    {
        return "https://graph.microsoft.com/v1.0/sites/{$this->siteId}/drives/{$this->driveId}{$path}";
    }

    /**
     * Normalize the folder path (strip leading/trailing slashes).
     */
    protected function normalizePath(string $path): string
    {
        $path = trim($path, '/');

        return $path === '' ? '/' : $path;
    }

    /**
     * Normalize the list response from Microsoft Graph into a consistent array format.
     */
    protected function normalizeListResponse(array $data): array
    {
        $items = $data['value'] ?? [];

        return array_map(function (array $item) {
            return [
                'id' => $item['id'],
                'name' => $item['name'],
                'type' => isset($item['folder']) ? 'folder' : 'file',
                'size' => $item['size'] ?? 0,
                'lastModified' => $item['lastModifiedDateTime'] ?? '',
                'webUrl' => $item['webUrl'] ?? '',
                'mimeType' => $item['file']['mimeType'] ?? null,
            ];
        }, $items);
    }

    /**
     * Handle an error response from the Graph API.
     *
     * @throws SharepointApiException
     */
    protected function handleErrorResponse(Response $response): never
    {
        throw SharepointApiException::fromResponse(
            $response->status(),
            $response->json() ?? [],
        );
    }
}
