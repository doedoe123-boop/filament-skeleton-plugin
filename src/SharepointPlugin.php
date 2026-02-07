<?php

namespace Doedoe123boop\Sharepoint;

use Doedoe123boop\Sharepoint\Pages\SharepointFileBrowser;
use Filament\Contracts\Plugin;
use Filament\Panel;

class SharepointPlugin implements Plugin
{
    protected ?string $defaultFolder = null;

    public function getId(): string
    {
        return 'sharepoint';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([
            SharepointFileBrowser::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    /**
     * Set the default folder for the file browser.
     */
    public function defaultFolder(string $folder): static
    {
        $this->defaultFolder = $folder;

        return $this;
    }

    /**
     * Get the configured default folder, or fall back to config.
     */
    public function getDefaultFolder(): string
    {
        return $this->defaultFolder ?? config('sharepoint.default_folder', '/');
    }
}
