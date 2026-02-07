<?php

namespace Doedoe123boop\Sharepoint\Contracts;

interface SharepointServiceInterface
{
    /**
     * List files and folders in the given folder path.
     *
     * @return array<int, array{id: string, name: string, type: string, size: int, lastModified: string, webUrl: string, mimeType: ?string}>
     */
    public function listFiles(string $folder = '/'): array;

    /**
     * Upload a file to SharePoint.
     *
     * @param  string  $folder  The target folder path.
     * @param  string  $filename  The name for the uploaded file.
     * @param  resource|string  $contents  File contents as a string or readable stream.
     * @return array{id: string, name: string, size: int, webUrl: string}
     */
    public function uploadFile(string $folder, string $filename, mixed $contents): array;

    /**
     * Delete a file or folder by its item ID.
     */
    public function deleteFile(string $itemId): bool;

    /**
     * Download a file and return its content as a stream.
     *
     * @return \Psr\Http\Message\StreamInterface
     */
    public function downloadFile(string $itemId): mixed;

    /**
     * Create a folder in the given parent path.
     *
     * @return array{id: string, name: string, webUrl: string}
     */
    public function createFolder(string $parentPath, string $folderName): array;
}
