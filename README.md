# SharePoint for Filament

[![Latest Version on Packagist](https://img.shields.io/packagist/v/doedoe123-boop/sharepoint.svg?style=flat-square)](https://packagist.org/packages/doedoe123-boop/sharepoint)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/doedoe123-boop/sharepoint/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/doedoe123-boop/sharepoint/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/doedoe123-boop/sharepoint.svg?style=flat-square)](https://packagist.org/packages/doedoe123-boop/sharepoint)

A SharePoint integration plugin for [Filament](https://filamentphp.com) Admin Panels. Browse, upload, download, and delete files directly on SharePoint via Microsoft Graph API — **files are never stored locally**.

## Features

- **File Browser** — Full Filament page with folder navigation, breadcrumbs, and file listing
- **Upload** — Stream files directly to SharePoint via modal action
- **Download** — Stream files from SharePoint without local storage
- **Delete** — Remove files with confirmation
- **Create Folders** — Create new folders in any location
- **OAuth 2.0** — Client Credentials flow with automatic token caching & refresh
- **Configurable** — All credentials via `.env`, per-panel overrides available
- **Translatable** — All UI strings use Laravel translation files
- **Testable** — Full test suite with mocked HTTP, architecture tests

## Requirements

- PHP 8.1+
- Laravel 10+
- Filament 3.x
- An Azure AD / Entra ID App Registration with `Sites.ReadWrite.All` application permission

## Installation

```bash
composer require doedoe123-boop/sharepoint
```

Publish the config file:

```bash
php artisan vendor:publish --tag="sharepoint-config"
```

## Configuration

Add the following to your `.env` file:

```env
SHAREPOINT_TENANT_ID=your-azure-tenant-id
SHAREPOINT_CLIENT_ID=your-app-client-id
SHAREPOINT_CLIENT_SECRET=your-app-client-secret
SHAREPOINT_SITE_ID=your-sharepoint-site-id
SHAREPOINT_DRIVE_ID=your-document-library-drive-id
SHAREPOINT_DEFAULT_FOLDER=/
```

### Finding your Site ID and Drive ID

Use [Microsoft Graph Explorer](https://developer.microsoft.com/en-us/graph/graph-explorer) to look up your IDs:

```
GET https://graph.microsoft.com/v1.0/sites/{hostname}:/sites/{site-path}
GET https://graph.microsoft.com/v1.0/sites/{site-id}/drives
```

## Usage

### Register the Plugin

Add the plugin to your Filament panel provider:

```php
use Doedoe123boop\Sharepoint\SharepointPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(
            SharepointPlugin::make()
                ->defaultFolder('Documents') // optional
        );
}
```

### Test the Connection

Verify your configuration works:

```bash
php artisan sharepoint:test-connection
```

### Using the Facade

```php
use Doedoe123boop\Sharepoint\Facades\Sharepoint;

// List files
$files = Sharepoint::listFiles('/');

// Upload a file
Sharepoint::uploadFile('Documents', 'report.pdf', $fileContents);

// Delete a file
Sharepoint::deleteFile($itemId);

// Download a file
$stream = Sharepoint::downloadFile($itemId);

// Create a folder
Sharepoint::createFolder('Documents', 'New Folder');
```

### Using Dependency Injection

```php
use Doedoe123boop\Sharepoint\Contracts\SharepointServiceInterface;

class MyController
{
    public function index(SharepointServiceInterface $sharepoint)
    {
        $files = $sharepoint->listFiles('/');
    }
}
```

## Architecture

```
Filament UI → Pages/Actions → Services → Microsoft Graph API
```

| Layer | Responsibility |
|---|---|
| `Pages/` | Filament Page with Livewire, UI only |
| `Services/` | Business logic, API communication. Framework-agnostic. |
| `Contracts/` | Interfaces for services (DI, testing) |
| `Exceptions/` | Typed exceptions for error handling |
| `Facades/` | Static proxy to `SharepointServiceInterface` |
| `Commands/` | Artisan CLI for connection testing |

## Testing

```bash
composer test
```

All tests use `Http::fake()` — no real Microsoft credentials needed.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Nelson](https://github.com/doedoe123-boop)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
