<?php

namespace Doedoe123boop\Sharepoint\Services;

use Doedoe123boop\Sharepoint\Contracts\TokenServiceInterface;
use Doedoe123boop\Sharepoint\Exceptions\SharepointAuthException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GraphTokenService implements TokenServiceInterface
{
    protected string $tenantId;

    protected string $clientId;

    protected string $clientSecret;

    protected int $cacheTtl;

    protected string $cacheKey = 'sharepoint.graph_access_token';

    public function __construct()
    {
        $this->tenantId = config('sharepoint.tenant_id', '');
        $this->clientId = config('sharepoint.client_id', '');
        $this->clientSecret = config('sharepoint.client_secret', '');
        $this->cacheTtl = (int) config('sharepoint.token_cache_ttl', 3500);
    }

    public function getAccessToken(): string
    {
        return Cache::remember($this->cacheKey, $this->cacheTtl, function () {
            return $this->acquireToken();
        });
    }

    public function refreshToken(): string
    {
        $this->clearCache();

        return $this->getAccessToken();
    }

    public function clearCache(): void
    {
        Cache::forget($this->cacheKey);
    }

    protected function acquireToken(): string
    {
        $url = "https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token";

        $response = Http::asForm()->post($url, [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope' => 'https://graph.microsoft.com/.default',
        ]);

        if ($response->failed()) {
            $body = $response->json();
            $error = $body['error_description'] ?? $body['error'] ?? 'Unknown error';

            throw SharepointAuthException::tokenAcquisitionFailed($error);
        }

        $data = $response->json();

        if (empty($data['access_token'])) {
            throw SharepointAuthException::tokenAcquisitionFailed('No access_token in response.');
        }

        return $data['access_token'];
    }
}
