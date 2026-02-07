<?php

namespace Doedoe123boop\Sharepoint\Exceptions;

class SharepointFileNotFoundException extends SharepointException
{
    public static function forPath(string $path): static
    {
        return new static("The file or folder was not found on SharePoint: {$path}");
    }

    public static function forItemId(string $itemId): static
    {
        return new static("The item with ID '{$itemId}' was not found on SharePoint.");
    }
}
