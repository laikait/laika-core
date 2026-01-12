<?php

/**
 * Laika PHP MVC Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 * This file is part of the Laika PHP MVC Framework.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Laika\Core\Storage;

use Laika\Core\Helper\Directory;
use Aws\Exception\AwsException;
use Laika\Core\Helper\Url;
use RuntimeException;
use Aws\S3\S3Client;
use Exception;

class FileStorage
{
    /**
     * Disk Type. Example local or s3
     * @var string $disk
     */
    protected string $disk;

    /**
     * Disk Config
     * @var array $config
     */
    protected array $config;

    /**
     * S3Client Object
     * @var ?S3Client $config
     */
    protected ?S3Client $s3 = null;

    /**
     * Default Path.
     * @var string $path
     */
    protected string $path;

    /**
     * Public Base Url of Uploaded File
     * @var ?string $publicBaseUrl
     */
    protected ?string $publicBaseUrl;

    /**
     * File Name
     * @var string $name
     */
    protected string $name;

    /**
     * File Mime Type
     * @var string $mime
     */
    protected string $mime;

    ###################################################################
    /*------------------------- PUBLIC API --------------------------*/
    ###################################################################
    /**
     * Only Local & S3 Ar Accepted.
     * All Uplods Will be In uploads folder
     */
    public function __construct(string $disk = 'local', array $config = [], ?string $publicBaseUrl = null)
    {
        // Check Disk is Supported
        $disk = \strtolower($disk);
        if (!\in_array($disk, ['local', 's3'])) {
            throw new RuntimeException("Unsupported Disk [{$disk}]. Accepted Disk Types Are: [local], [s3]");
        }

        $this->disk = $disk;
        $this->config = $config;
        $this->publicBaseUrl = $publicBaseUrl ? \rtrim($publicBaseUrl, '/') . '/' : $publicBaseUrl;

        if ($this->disk === 's3') {
            $this->path = 'lf-storage';
            // Check Config Required Keys are Exists
            if (!isset($this->config['region'])) {
                throw new RuntimeException("'region' Key Missing in Config");
            }
            if (!isset($this->config['key'])) {
                throw new RuntimeException("'key' Key Missing in Config");
            }
            if (!isset($this->config['secret'])) {
                throw new RuntimeException("'secret' Key Missing in Config");
            }
            if (!isset($this->config['bucket'])) {
                throw new RuntimeException("'bucket' Key Missing in Config");
            }

            $this->s3 = new S3Client([
                'region'        =>  $config['region'],
                'version'       =>  'latest',
                'credentials'   =>  [
                    'key'   =>  $config['key'],
                    'secret' =>  $config['secret']
                ]
            ]);
        } else {
            $this->path = \realpath(APP_PATH . '/lf-storage');
            Directory::make($this->path);
        }
    }

    /**
     * Upload file from $_FILES or file path
     * @param array|string $file - $_FILES['file'] or local file path
     * @param ?string $destination - destination folder (e.g. 'images')
     * @return string|false
     */
    public function upload(array|string $file, ?string $destination = null): string|false
    {
        // Normalize destination
        $this->path .= $destination ? '/' . \trim($destination, '/') : \date('/Y/m/d');

        // Determine temp file and name
        if (\is_array($file) && isset($file['tmp_name'])) {
            $tmpFile = $file['tmp_name'];
            $this->name = \basename($file['name']);
        } elseif (\is_string($file) && \file_exists($file)) {
            $tmpFile = $file;
            $this->name = \basename($file);
        } else {
            throw new RuntimeException("Invalid file input. Must be \$_FILES or valid file path.");
        }

        // Safe MIME detection
        $this->mime = \mime_content_type($tmpFile) ?: 'application/octet-stream';

        // Ensure local directory exists
        if ($this->disk === 'local') {
            Directory::make($this->path);
        }

        // Make File Version
        $ext = \pathinfo($this->name, PATHINFO_EXTENSION);
        $base = \pathinfo($this->name, PATHINFO_FILENAME);
        $this->name = $base . '-' . \uniqid() . '-' . \time() . ($ext ? ".{$ext}" : '');

        // Final target path
        $this->path = "{$this->path}/{$this->name}";

        // Handle per disk
        return match ($this->disk) {
            's3' => $this->uploadS3($tmpFile, \ltrim($this->path, '/')),
            'local' => $this->uploadLocal($tmpFile, $this->path),
            default => false,
        };
    }

    /**
     * Delete a File
     * @param string $file File Name With Sub Path. Example: image/sample.png. uploads will auto added
     * @return bool
     */
    public function delete(string $file): bool
    {
        $file = \ltrim($file, '/');
        $path = "{$this->path}/{$file}";
        return match ($this->disk) {
            's3'    => $this->deleteS3($path),
            'local' => $this->deleteLocal($path),
            default => false
        };
    }

    /**
     * Uploaded File Name
     * @return string
     */
    public function name(): string
    {
        return $this->name ?? '';
    }

    /**
     * Uploaded File Path
     * @return string
     */
    public function path(): string
    {
        return \str_replace("/{$this->name()}", '', $this->path);
    }

    /**
     * Mime Type
     * @return string
     */
    public function mime()
    {
        if (!isset($this->mime)) {
            throw new Exception("Please Upload The  File First!");
        }

        return \strtolower($this->mime);
    }

    ###################################################################
    /*------------------------- PRIVATE API -------------------------*/
    ###################################################################

    /**
     * Generate a public URL for stored files
     * @param string $file File Name. Example: image/sample.png. uploads will auto added
     * @return string
     */
    protected function url(string $file): string
    {
        $file = \ltrim($file, '/');
        return match ($this->disk) {
            'local' => \str_replace(ltrim(APP_PATH, '/'), '', ltrim(\do_hook('app.host') . "{$file}", '/')),
            's3'    => $this->publicBaseUrl
                ? $this->publicBaseUrl . $file
                : \sprintf("https://%s.s3.%s.amazonaws.com/%s", $this->config['bucket'], $this->config['region'], $file),
            default => ''
        };
    }

    /**
     * Upload file to local storage
     * @param string $tmpFile uploaded Temp File or Old File Name
     * @param string $destination Destination File Name
     * @return string
     */
    protected function uploadLocal(string $tmpFile, string $destination): string
    {
        // Move uploaded or copy from existing file
        if (\is_uploaded_file($tmpFile)) {
            if (!\move_uploaded_file($tmpFile, $destination)) {
                throw new RuntimeException("Failed to move uploaded file to [{$destination}]");
            }
        } else {
            if (!\copy($tmpFile, $destination)) {
                throw new RuntimeException("Failed to copy file to [{$destination}]");
            }
        }

        return $this->url($destination);
    }

    /**
     * Upload file to S3
     * @param string $tmpFile uploaded Temp File or Old File Name
     * @param string $destination Destination File Name
     * @return string
     */
    protected function uploadS3(string $tmpFile, string $destination): string
    {
        try {
            $this->s3->putObject([
                'Bucket'        =>  $this->config['bucket'],
                'Key'           =>  $destination,
                'SourceFile'    =>  $tmpFile,
                'ACL'           =>  'public-read',
                'ContentType'   =>  $this->mime ?: 'application/octet-stream',
            ]);

            return $this->url($destination);
        } catch (AwsException $e) {
            throw new RuntimeException("S3 Upload failed: {$e->getMessage()}");
        }
    }

    /**
     * @param string $file File Name. Example: /var/www/html/uploads/image/sample.png
     * @return bool
     */
    protected function deleteLocal(string $file): bool
    {
        return \file_exists($file) ? \unlink($file) : false;
    }

    /**
     * @param string $file File Name With Sub Path. Example: uploads/image/sample.png
     * @return bool
     */
    protected function deleteS3(string $path): bool
    {
        try {
            $bucket = $this->config['bucket'] ?? '';
            $this->s3->deleteObject([
                'Bucket'    => $bucket,
                'Key'       => $path
            ]);
            return true;
        } catch (AwsException $e) {
            throw new RuntimeException("Failed to Delete: {$e->getMessage()}");
        }
    }
}
