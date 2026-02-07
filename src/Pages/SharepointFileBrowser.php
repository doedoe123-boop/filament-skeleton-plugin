<?php

namespace Doedoe123boop\Sharepoint\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Doedoe123boop\Sharepoint\SharepointPlugin;
use Doedoe123boop\Sharepoint\Contracts\SharepointServiceInterface;

class SharepointFileBrowser extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cloud';

    protected static string|\UnitEnum|null $navigationGroup = 'SharePoint';

    protected static ?string $slug = 'sharepoint/files';

    protected string $view = 'sharepoint::pages.sharepoint-file-browser';

    public string $currentFolder = '/';

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $files = [];

    /**
     * @var array<int, array{label: string, path: string}>
     */
    public array $breadcrumbs = [];

    public function mount(): void
    {
        $this->currentFolder = $this->getDefaultFolder();
        $this->loadFiles();
    }

    public function getTitle(): string
    {
        return __('sharepoint::sharepoint.page.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('sharepoint::sharepoint.page.navigation_label');
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    /**
     * Navigate into a folder.
     */
    public function openFolder(string $folderName): void
    {
        $this->currentFolder = $this->currentFolder === '/'
            ? $folderName
            : "{$this->currentFolder}/{$folderName}";

        $this->loadFiles();
    }

    /**
     * Navigate to a specific path from breadcrumb.
     */
    public function navigateTo(string $path): void
    {
        $this->currentFolder = $path;
        $this->loadFiles();
    }

    /**
     * Navigate up one level.
     */
    public function goUp(): void
    {
        if ($this->currentFolder === '/') {
            return;
        }

        $parts = explode('/', $this->currentFolder);
        array_pop($parts);
        $this->currentFolder = implode('/', $parts) ?: '/';

        $this->loadFiles();
    }

    /**
     * Load files from SharePoint for the current folder.
     */
    public function loadFiles(): void
    {
        try {
            $service = app(SharepointServiceInterface::class);
            $this->files = $service->listFiles($this->currentFolder);
            $this->buildBreadcrumbs();
        } catch (\Throwable $e) {
            Log::error('SharePoint file listing failed', ['error' => $e->getMessage()]);
            $this->files = [];

            Notification::make()
                ->title(__('sharepoint::sharepoint.notifications.list_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Delete a file by its SharePoint item ID.
     */
    public function deleteItem(string $itemId, string $itemName): void
    {
        try {
            $service = app(SharepointServiceInterface::class);
            $service->deleteFile($itemId);

            Notification::make()
                ->title(__('sharepoint::sharepoint.notifications.deleted', ['name' => $itemName]))
                ->success()
                ->send();

            $this->loadFiles();
        } catch (\Throwable $e) {
            Log::error('SharePoint delete failed', ['error' => $e->getMessage(), 'itemId' => $itemId]);

            Notification::make()
                ->title(__('sharepoint::sharepoint.notifications.delete_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Download a file by its SharePoint item ID.
     */
    public function downloadItem(string $itemId, string $itemName): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $service = app(SharepointServiceInterface::class);
        $stream = $service->downloadFile($itemId);

        return response()->streamDownload(function () use ($stream) {
            echo $stream;
        }, $itemName);
    }

    /**
     * Get header actions (upload + create folder).
     */
    protected function getHeaderActions(): array
    {
        return [
            $this->getUploadAction(),
            $this->getCreateFolderAction(),
            Action::make('refresh')
                ->label(__('sharepoint::sharepoint.actions.refresh'))
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => $this->loadFiles()),
        ];
    }

    protected function getUploadAction(): Action
    {
        return Action::make('upload')
            ->label(__('sharepoint::sharepoint.actions.upload'))
            ->icon('heroicon-o-arrow-up-tray')
            ->form([
                FileUpload::make('file')
                    ->label(__('sharepoint::sharepoint.actions.upload_file_label'))
                    ->required()
                    ->maxSize(50 * 1024) // 50 MB
                    ->storeFiles(false),
            ])
            ->action(function (array $data): void {
                try {
                    $file = $data['file'];
                    $filename = $file->getClientOriginalName();
                    $contents = fopen($file->getRealPath(), 'r');

                    $service = app(SharepointServiceInterface::class);
                    $service->uploadFile($this->currentFolder, $filename, $contents);

                    if (is_resource($contents)) {
                        fclose($contents);
                    }

                    Notification::make()
                        ->title(__('sharepoint::sharepoint.notifications.uploaded', ['name' => $filename]))
                        ->success()
                        ->send();

                    $this->loadFiles();
                } catch (\Throwable $e) {
                    Log::error('SharePoint upload failed', ['error' => $e->getMessage()]);

                    Notification::make()
                        ->title(__('sharepoint::sharepoint.notifications.upload_failed'))
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    protected function getCreateFolderAction(): Action
    {
        return Action::make('createFolder')
            ->label(__('sharepoint::sharepoint.actions.create_folder'))
            ->icon('heroicon-o-folder-plus')
            ->color('gray')
            ->form([
                TextInput::make('folder_name')
                    ->label(__('sharepoint::sharepoint.actions.folder_name_label'))
                    ->required()
                    ->maxLength(255)
                    ->regex('/^[^\\\\\/\:\*\?\"\<\>\|]+$/'),
            ])
            ->action(function (array $data): void {
                try {
                    $service = app(SharepointServiceInterface::class);
                    $service->createFolder($this->currentFolder, $data['folder_name']);

                    Notification::make()
                        ->title(__('sharepoint::sharepoint.notifications.folder_created', ['name' => $data['folder_name']]))
                        ->success()
                        ->send();

                    $this->loadFiles();
                } catch (\Throwable $e) {
                    Log::error('SharePoint create folder failed', ['error' => $e->getMessage()]);

                    Notification::make()
                        ->title(__('sharepoint::sharepoint.notifications.folder_create_failed'))
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * Build breadcrumb trail from the current folder path.
     */
    protected function buildBreadcrumbs(): void
    {
        $this->breadcrumbs = [
            ['label' => __('sharepoint::sharepoint.page.root'), 'path' => '/'],
        ];

        if ($this->currentFolder === '/') {
            return;
        }

        $parts = explode('/', $this->currentFolder);
        $accumulated = '';

        foreach ($parts as $part) {
            $accumulated = $accumulated === '' ? $part : "{$accumulated}/{$part}";
            $this->breadcrumbs[] = ['label' => $part, 'path' => $accumulated];
        }
    }

    protected function getDefaultFolder(): string
    {
        try {
            return SharepointPlugin::get()->getDefaultFolder();
        } catch (\Throwable) {
            return config('sharepoint.default_folder', '/');
        }
    }

    /**
     * Format bytes to a human-readable string.
     */
    public function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024));

        return round($bytes / (1024 ** $i), 2) . ' ' . $units[$i];
    }
}
