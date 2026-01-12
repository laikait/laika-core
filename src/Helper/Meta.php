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

namespace Laika\Core\Helper;

use InvalidArgumentException;

class Meta
{
   /**
    * Get Version Info from PHP File
    * @param string $path - Required A Path of PHP File
    * @return array - Returns an associative array of meta information extracted from the PHP file's doc comments
    */
   public static function version(string $path): array
   {
      if (!\is_file($path)) {
         throw new InvalidArgumentException("Invalid file path: $path");
      }
      $meta = [];
      $tokens = \token_get_all(\file_get_contents($path));
      foreach ($tokens as $token) {
         if (isset($token[0], $token[1]) && $token[0] === T_DOC_COMMENT) {
            $comments = \explode('*', $token[1]);
            foreach ($comments as $value) {
               if (\str_contains($value, ':')) {
                  $parts = \explode(':', $value, 2);
                  $key = \strtolower(\str_replace(' ', '-', \trim($parts[0])));
                  $value = \trim($parts[1] ?? '');
                  $meta[$key] = $value;
               }
            }
            break;
         }
      }
      return $meta;
   }
}
