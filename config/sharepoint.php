<?php

// config for Doedoe123boop/Sharepoint

return [

    /*
    |--------------------------------------------------------------------------
    | Azure AD / Entra ID Credentials
    |--------------------------------------------------------------------------
    |
    | These credentials are used for the OAuth 2.0 Client Credentials flow
    | to authenticate with Microsoft Graph API. Register an application in
    | Azure AD and grant it the required permissions (Sites.ReadWrite.All).
    |
    */

    'tenant_id' => env('SHAREPOINT_TENANT_ID', ''),

    'client_id' => env('SHAREPOINT_CLIENT_ID', ''),

    'client_secret' => env('SHAREPOINT_CLIENT_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | SharePoint Site & Drive
    |--------------------------------------------------------------------------
    |
    | The site_id identifies your SharePoint site and the drive_id identifies
    | the document library within that site. You can retrieve these values
    | using Microsoft Graph Explorer.
    |
    | Site ID format: {hostname},{site-collection-id},{web-id}
    | Drive ID can be found via: GET /sites/{site-id}/drives
    |
    */

    'site_id' => env('SHAREPOINT_SITE_ID', ''),

    'drive_id' => env('SHAREPOINT_DRIVE_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | Default Folder
    |--------------------------------------------------------------------------
    |
    | The default folder path to use when browsing files. Use "/" for the
    | root of the document library. Paths are relative to the drive root.
    |
    */

    'default_folder' => env('SHAREPOINT_DEFAULT_FOLDER', '/'),

    /*
    |--------------------------------------------------------------------------
    | Token Cache Settings
    |--------------------------------------------------------------------------
    |
    | Access tokens are cached to minimize token acquisition requests.
    | Microsoft tokens expire after 3600 seconds, so we cache for slightly
    | less to avoid edge-case expiry issues.
    |
    */

    'token_cache_ttl' => env('SHAREPOINT_TOKEN_CACHE_TTL', 3500),

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | When enabled, requests that receive a 401 Unauthorized response will
    | automatically refresh the token and retry. This handles cases where
    | a cached token has been revoked or expired unexpectedly.
    |
    */

    'retry_on_failure' => env('SHAREPOINT_RETRY_ON_FAILURE', true),

    'max_retries' => env('SHAREPOINT_MAX_RETRIES', 2),

];
