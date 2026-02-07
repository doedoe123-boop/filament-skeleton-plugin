<?php

namespace Doedoe123boop\Sharepoint\Commands;

use Illuminate\Console\Command;
use Doedoe123boop\Sharepoint\Contracts\SharepointServiceInterface;

class SharepointCommand extends Command
{
    public $signature = 'sharepoint:test-connection {--folder=/ : The folder path to list}';

    public $description = 'Test the SharePoint connection by listing folder contents.';

    public function handle(SharepointServiceInterface $sharepoint): int
    {
        $folder = $this->option('folder');

        $this->info("Connecting to SharePoint...");
        $this->newLine();

        try {
            $items = $sharepoint->listFiles($folder);
        } catch (\Throwable $e) {
            $this->error("Connection failed: {$e->getMessage()}");

            return self::FAILURE;
        }

        if (empty($items)) {
            $this->warn("Connected successfully, but the folder '{$folder}' is empty.");

            return self::SUCCESS;
        }

        $this->info("Connected successfully! Found " . count($items) . " item(s) in '{$folder}':");
        $this->newLine();

        $this->table(
            ['Type', 'Name', 'Size', 'Last Modified'],
            array_map(fn (array $item) => [
                $item['type'] === 'folder' ? '📁' : '📄',
                $item['name'],
                $item['type'] === 'folder' ? '-' : $this->formatBytes($item['size']),
                $item['lastModified'] ? date('Y-m-d H:i', strtotime($item['lastModified'])) : '-',
            ], $items),
        );

        return self::SUCCESS;
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024));

        return round($bytes / (1024 ** $i), 2) . ' ' . $units[$i];
    }
}
