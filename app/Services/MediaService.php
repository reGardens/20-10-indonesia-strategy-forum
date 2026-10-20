<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MediaService
{
    /**
     * Allowed image MIME types.
     */
    protected array $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    /**
     * Allowed file extensions.
     */
    protected array $allowedExtensions = [
        'jpg',
        'jpeg',
        'png',
        'webp',
    ];

    /**
     * Maximum file size in kilobytes (2MB).
     */
    protected int $maxSizeKb = 2048;

    /**
     * Upload an image file to storage.
     *
     * @param UploadedFile $file The uploaded file
     * @param string $directory The storage directory (relative to public disk)
     * @return string The relative path of the stored file
     *
     * @throws \InvalidArgumentException If the file fails validation
     */
    public function upload(UploadedFile $file, string $directory = 'uploads'): string
    {
        if (!$this->validate($file)) {
            throw new \InvalidArgumentException(
                'File tidak valid. Format harus JPG, PNG, atau WebP dengan ukuran maksimal 2MB.'
            );
        }

        $path = $file->store($directory, 'public');

        return $path;
    }

    /**
     * Validate an uploaded file for format and size.
     *
     * @param UploadedFile $file The uploaded file to validate
     * @return bool True if the file passes validation
     */
    public function validate(UploadedFile $file): bool
    {
        return $this->validateFormat($file) && $this->validateSize($file);
    }

    /**
     * Validate the file format (MIME type and extension).
     *
     * @param UploadedFile $file The uploaded file
     * @return bool True if the format is valid
     */
    protected function validateFormat(UploadedFile $file): bool
    {
        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());

        return in_array($mimeType, $this->allowedMimeTypes, true)
            && in_array($extension, $this->allowedExtensions, true);
    }

    /**
     * Validate the file size (max 2MB).
     *
     * @param UploadedFile $file The uploaded file
     * @return bool True if the size is within limits
     */
    protected function validateSize(UploadedFile $file): bool
    {
        // getSize() returns bytes, convert maxSizeKb to bytes
        return $file->getSize() <= ($this->maxSizeKb * 1024);
    }

    /**
     * Delete a file from storage.
     *
     * @param string $path The relative path of the file to delete
     * @return bool True if the file was deleted successfully
     */
    public function delete(string $path): bool
    {
        if (empty($path)) {
            return false;
        }

        return Storage::disk('public')->delete($path);
    }
}
