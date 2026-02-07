<?php

namespace Doedoe123boop\Sharepoint;

use Doedoe123boop\Sharepoint\Commands\SharepointCommand;
use Doedoe123boop\Sharepoint\Contracts\SharepointServiceInterface;
use Doedoe123boop\Sharepoint\Contracts\TokenServiceInterface;
use Doedoe123boop\Sharepoint\Services\GraphTokenService;
use Doedoe123boop\Sharepoint\Services\SharepointService;
use Doedoe123boop\Sharepoint\Testing\TestsSharepoint;
use Livewire\Features\SupportTesting\Testable;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SharepointServiceProvider extends PackageServiceProvider
{
    public static string $name = 'sharepoint';

    public static string $viewNamespace = 'sharepoint';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasCommands($this->getCommands())
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->askToStarRepoOnGitHub('doedoe123-boop/sharepoint');
            });

        $configFileName = $package->shortName();

        if (file_exists($package->basePath("/../config/{$configFileName}.php"))) {
            $package->hasConfigFile();
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(TokenServiceInterface::class, GraphTokenService::class);
        $this->app->singleton(SharepointServiceInterface::class, SharepointService::class);
    }

    public function packageBooted(): void
    {
        // Testing
        Testable::mixin(new TestsSharepoint);
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            SharepointCommand::class,
        ];
    }
}
