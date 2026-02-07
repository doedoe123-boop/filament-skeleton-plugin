<?php

namespace Doedoe123boop\Sharepoint\Contracts;

interface TokenServiceInterface
{
    /**
     * Get a valid access token for Microsoft Graph API.
     * Returns a cached token if available, otherwise acquires a new one.
     */
    public function getAccessToken(): string;

    /**
     * Force-refresh the access token by clearing the cache and acquiring a new one.
     */
    public function refreshToken(): string;

    /**
     * Clear the cached token.
     */
    public function clearCache(): void;
}
