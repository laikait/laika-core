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

use Aws\S3\S3Client;
use RuntimeException;
use Aws\Exception\AwsException;
use Laika\Core\Storage\Connection\S3Connection;

/**
 * Amazon S3 Storage
 *
 * Reads lf-config/s3.php. Objects Are Keyed Under a Prefix, Defaulting to
 * lf-storage. The Prefix Never Moves, So The Same Instance Can Upload More
 * Than Once. name(), path(), url() & mime() Describe The Most Recent Upload.
 */
class S3Storage
{
    /**
     * @var S3Client $client
     */
    protected S3Client $client;

    /**
     * Bucket Every Object Lands In
     * @var string $bucket
     */
    protected string $bucket;

    /**
     * Region, Kept For Building The Default Bucket Url
     * @var string $region
     */
    protected string $region;

    /**
     * Key Prefix Every Object Sits Under. Never Mutated By an Upload
     * @var string $root
     */
    protected string $root;

    /**
     * Public Base Url of Uploaded Files
     * @var ?string $baseUrl
     */
    protected ?string $baseUrl;

    /**
     * Canned ACL Applied to Uploads
     * @var string $acl
     */
    protected string $acl;

    /**
     * Last Uploaded File Name
     * @var string $name
     */
    protected string $name = '';

    /**
     * Last Uploaded Object Key
     * @var string $path
     */
    protected string $path = '';

    /**
     * Last Uploaded Mime Type
     * @var string $mime
     */
    protected string $mime = '';

    /**
     * @param array $overrides Explicit Values That Win Over lf-config/s3.php
     * @param ?string $publicBaseUrl CDN or Custom Domain. Falls Back to The 'url' Config, Then The Bucket Url
     * @throws RuntimeException
     */
    public function __construct(array $overrides = [], ?string $publicBaseUrl = null)
    {
        // Region, Credentials & Endpoint Are The Factory's Business
        $this->client = S3Connection::make($overrides);

        $this->bucket = (string) S3Connection::value($overrides, 'bucket', '');

        if ($this->bucket === '') {
            throw new RuntimeException("Missing S3 Config Key(s): [bucket]");
        }

        $this->region  = (string) S3Connection::value($overrides, 'region', '');
        $this->root    = \trim((string) S3Connection::value($overrides, 'root', 'lf-storage'), '/');
        $this->acl     = (string) S3Connection::value($overrides, 'acl', 'public-read');

        $baseUrl = $publicBaseUrl ?? (string) S3Connection::value($overrides, 'url', '');
        $this->baseUrl = $baseUrl !== '' ? \rtrim($baseUrl, '/') . '/' : null;
    }

    ###################################################################
    /*------------------------- PUBLIC API --------------------------*/
    ###################################################################

    /**
     * Upload a File From $_FILES or a Local Path
     * @param array|string $file $_FILES['file'] or a Readable File Path
     * @param ?string $destination Key Sub Folder. Example: images. Defaults to a Y/m/d Folder
     * @return string Public Url of The Stored Object
     * @throws RuntimeException
     */
    public function upload(array|string $file, ?string $destination = null): string
    {
        [$tmpFile, $original] = $this->source($file);

        $this->mime = \mime_content_type($tmpFile) ?: 'application/octet-stream';
        $this->name = $this->version($original);

        // Built Fresh Every Call, So Uploading Twice Doesn't Compound The Key
        $folder = $destination !== null ? \trim($destination, '/') : \date('Y/m/d');
        $this->path = $this->key("{$folder}/{$this->name}");

        try {
            $this->client->putObject([
                'Bucket'        =>  $this->bucket,
                'Key'           =>  $this->path,
                'SourceFile'    =>  $tmpFile,
                'ACL'           =>  $this->acl,
                'ContentType'   =>  $this->mime,
            ]);
        } catch (AwsException $e) {
            throw new RuntimeException("S3 Upload failed: {$e->getMessage()}", 0, $e);
        }

        return $this->url($this->path);
    }

    /**
     * Delete a Stored Object
     * @param string $file Key Relative to The Root. Example: images/sample.png
     * @return bool
     * @throws RuntimeException
     */
    public function delete(string $file): bool
    {
        try {
            $this->client->deleteObject([
                'Bucket'    =>  $this->bucket,
                'Key'       =>  $this->key($file),
            ]);

            return true;
        } catch (AwsException $e) {
            throw new RuntimeException("Failed to Delete: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Public Url For a Stored Object
     * @param string $file Full Object Key, or a Key Relative to The Root
     * @return string
     */
    public function url(string $file): string
    {
        $key = $this->key($file);

        if ($this->baseUrl !== null) {
            return $this->baseUrl . $key;
        }

        return \sprintf(
            'https://%s.s3.%s.amazonaws.com/%s',
            $this->bucket,
            $this->region,
            $key
        );
    }

    /**
     * Key Prefix Every Object Sits Under
     * @return string
     */
    public function root(): string
    {
        return $this->root;
    }

    /**
     * Last Uploaded File Name
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Object Key of The Last Upload
     * @return string
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * Mime Type of The Last Upload
     * @return string
     * @throws RuntimeException
     */
    public function mime(): string
    {
        if ($this->mime === '') {
            throw new RuntimeException("Please Upload The File First!");
        }

        return \strtolower($this->mime);
    }

    ###################################################################
    /*------------------------- INTERNAL API ------------------------*/
    ###################################################################

    /**
     * Prefix a Key With The Root, Unless it Already Carries it
     * @param string $file
     * @return string
     */
    protected function key(string $file): string
    {
        $file = \trim(\str_replace('\\', '/', $file), '/');

        if ($this->root === '') {
            return $file;
        }

        return \str_starts_with($file, "{$this->root}/") ? $file : "{$this->root}/{$file}";
    }

    /**
     * Resolve The Input to [temp path, original name]
     * @param array|string $file
     * @return array{0:string,1:string}
     * @throws RuntimeException
     */
    protected function source(array|string $file): array
    {
        if (\is_array($file) && isset($file['tmp_name'])) {
            return [$file['tmp_name'], \basename((string) ($file['name'] ?? $file['tmp_name']))];
        }

        if (\is_string($file) && \is_file($file)) {
            return [$file, \basename($file)];
        }

        throw new RuntimeException("Invalid file input. Must be \$_FILES or valid file path.");
    }

    /**
     * Add a Version Suffix So an Upload Never Overwrites an Existing Object
     * @param string $name
     * @return string
     */
    protected function version(string $name): string
    {
        $ext  = \pathinfo($name, PATHINFO_EXTENSION);
        $base = \pathinfo($name, PATHINFO_FILENAME);

        return $base . '-' . \uniqid() . '-' . \time() . ($ext ? ".{$ext}" : '');
    }
}
