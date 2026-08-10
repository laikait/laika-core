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

namespace Laika\Core\Exceptions;

use RuntimeException;

class ResourceException extends RuntimeException
{
    protected int $statusCode;

    public function __construct(string $message, $code = 0, ?Throwable $previous = null)
    {
        $this->statusCode = $code;
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    ######################################################################################
    ## --------------------------------- NAMED ERRORS --------------------------------- ##
    ######################################################################################

    /**
     * Invalid Resource Name
     * @param string $name
     * @return self
     */
    public static function invalidName(string $name): self
    {
        return new self(
            "Invalid Resource Name [{$name}]. Names must start with a letter and contain only letters, digits or underscores."
        );
    }

    /**
     * Invalid Base Namespace
     * @param string $namespace
     * @return self
     */
    public static function invalidNamespace(string $namespace): self
    {
        return new self(
            "Invalid Resource Class Base Namespace [{$namespace}]. Use backslash separated PSR-4 segments, e.g. App\\Model."
        );
    }

    /**
     * Resource Directory Is Missing
     * @param string $name
     * @param string $path
     * @return self
     */
    public static function pathNotFound(string $name, string $path): self
    {
        return new self("Resource [{$name}] points at a directory that does not exist [{$path}].");
    }

    /**
     * Resource File Does Not Declare Its Expected Class
     * @param string $class
     * @param string $name
     * @return self
     */
    public static function classNotFound(string $class, string $name): self
    {
        return new self(
            "Resource [{$name}] expected class [{$class}]. Check the file name matches the class name and the namespace matches the directory."
        );
    }

    /**
     * Resource Class Does Not Satisfy Its Contract
     * @param string $class
     * @param string $contract
     * @return self
     */
    public static function notInstanceOf(string $class, string $contract): self
    {
        return new self("[{$class}] is not a child class of [{$contract}].");
    }

    /**
     * Resource Holds File Paths, Not Class Names
     * @param string $name
     * @return self
     */
    public static function notClassMap(string $name): self
    {
        return new self(
            "Resource [{$name}] holds file paths, not class names. Read it with getFiles(), or declare a 'namespace' for it."
        );
    }

    /**
     * Unknown Resource Name
     * @param string $name
     * @param string[] $known
     * @return self
     */
    public static function unknownResource(string $name, array $known): self
    {
        $list = $known ? implode(', ', $known) : 'none';
        return new self("Unknown Resource [{$name}]. Registered resources: {$list}.");
    }
}
