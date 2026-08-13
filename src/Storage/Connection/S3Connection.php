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

namespace Laika\Core\Storage\Connection;

use Aws\S3\S3Client;
use Laika\Core\Exceptions\ExtensionException;
use RuntimeException;

/**
 * S3 Connection Factory
 *
 * Builds a Configured S3Client From lf-config/s3.php.
 * The Bucket, Key Prefix & Public Url Are Not Handled Here. Those Belong to The
 * Caller, So a Storage Class Managing its Own Keys Gets The Same Client Handling
 * as Anything Else Talking to S3.
 *
 * Nothing Connects Here. The SDK Opens a Connection on The First Request, So a
 * Bad Region or Credential Surfaces Later as an AwsException.
 */
class S3Connection
{
    /**
     * Config Keys Required to Build a Client
     */
    protected const REQUIRED = ['region', 'key', 'secret'];

    /**
     * Build a Configured Client
     * @param array $overrides Explicit Values That Win Over lf-config/s3.php
     * @return S3Client
     * @throws ExtensionException|RuntimeException
     */
    public static function make(array $overrides = []): S3Client
    {
        // Check Package Installed
        if (!\class_exists(S3Client::class)) {
            throw new ExtensionException("Package Not Installed: [aws/aws-sdk-php]!");
        }

        $missing = [];
        foreach (self::REQUIRED as $key) {
            if ((string) self::value($overrides, $key, '') === '') {
                $missing[] = $key;
            }
        }

        if ($missing !== []) {
            throw new RuntimeException("Missing S3 Config Key(s): [" . \implode('], [', $missing) . "]");
        }

        $args = [
            'region'        =>  (string) self::value($overrides, 'region', ''),
            'version'       =>  (string) self::value($overrides, 'version', 'latest'),
            'credentials'   =>  [
                'key'       =>  (string) self::value($overrides, 'key', ''),
                'secret'    =>  (string) self::value($overrides, 'secret', ''),
            ],
        ];

        // S3 Compatible Services Need an Endpoint & Usually Path Style Addressing
        $endpoint = (string) self::value($overrides, 'endpoint', '');

        if ($endpoint !== '') {
            $args['endpoint'] = $endpoint;
            $args['use_path_style_endpoint'] = (bool) self::value($overrides, 'path_style', true);
        }

        return new S3Client($args);
    }

    /**
     * An Override Wins, Otherwise Fall Back to lf-config/s3.php
     * @param array $overrides
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function value(array $overrides, string $key, mixed $default): mixed
    {
        if (isset($overrides[$key]) && $overrides[$key] !== null && $overrides[$key] !== '') {
            return $overrides[$key];
        }

        return config('s3', $key, $default);
    }
}
