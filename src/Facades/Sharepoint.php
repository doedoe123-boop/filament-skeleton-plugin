<?php

namespace Doedoe123boop\Sharepoint\Facades;

use Illuminate\Support\Facades\Facade;
use Doedoe123boop\Sharepoint\Contracts\SharepointServiceInterface;

/**
 * @see \Doedoe123boop\Sharepoint\Services\SharepointService
 *
 * @method static array listFiles(string $folder = '/')
 * @method static array uploadFile(string $folder, string $filename, mixed $contents)
 * @method static bool deleteFile(string $itemId)
 * @method static mixed downloadFile(string $itemId)
 * @method static array createFolder(string $parentPath, string $folderName)
 */
class Sharepoint extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SharepointServiceInterface::class;
    }
}
