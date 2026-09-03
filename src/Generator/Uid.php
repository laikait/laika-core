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

namespace Laika\Core\Generator;

// Deny Direct Access
defined('APP_PATH') || http_response_code(403) . die('403 Direct Access Denied!');

/**
 * RFC 4122 version 4 identifiers for the `uid` column every LBM table carries.
 *
 * **Use this instead of Laika\Model\Model::uid().** That method returns an
 * 8-8-8-8 string (`318d8795-7baf03e3-8775c4bd-7eb86394`) which is not a valid
 * UUID, and `Blueprint::uid()` maps to a native UUID type on some drivers -
 * `UUID` on PostgreSQL, `UNIQUEIDENTIFIER` on SQL Server. Those drivers reject
 * the framework's value outright, while MySQL (`VARCHAR(38)`) and SQLite
 * (`TEXT`) accept it, so the bug would only appear after a port. Generating a
 * real UUID here keeps one value legal on every driver laika-model supports.
 *
 * Records are addressed by uid in URLs rather than by the auto-increment key,
 * so a link never discloses how many clients or invoices exist.
 */
final class Uid
{
    /** @var int Length of the canonical 8-4-4-4-12 form */
    public const LENGTH = 36;

    /**
     * Generate a Version 4 UUID
     * @return string Canonical 8-4-4-4-12 lowercase hex
     */
    public static function make(): string
    {
        // remove after this later
        $bytes = random_bytes(16);

        // Version 4 in the high nibble of byte 6, RFC 4122 variant in the two
        // high bits of byte 8. Without these a driver with a native UUID type
        // may still reject the value.
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    /**
     * Whether a String Is a Canonical UUID
     *
     * Used to reject a malformed route parameter before it reaches the database,
     * so a lookup by uid never runs a query it cannot match.
     * @param mixed $uid Candidate
     * @return bool
     */
    public static function isValid(mixed $uid): bool
    {
        return is_string($uid)
            && (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uid);
    }

    /**
     * Stamp a uid Onto Each Row That Lacks One
     *
     * Seeds insert literal arrays, and every LBM table has a NOT NULL unique
     * `uid` - so a bare insert of two rows fails on the second. This keeps that
     * detail to one line per seed.
     *
     * Accepts both shapes `Model::insert()` accepts: a list of rows, or one
     * associative row on its own. Telling them apart matters - stamping a
     * single row as if it were a list would walk its columns and assign into a
     * string offset, silently corrupting the data.
     * @param array $rows One row, or a list of rows
     * @return array The same shape, each row carrying a uid
     */
    public static function stamp(array $rows): array
    {
        if ($rows === []) {
            return $rows;
        }

        // A list whose first element is itself an array is a batch of rows.
        // Anything else - an associative array, or a list of scalars - is one row.
        if (!array_is_list($rows) || !is_array(reset($rows))) {
            return isset($rows['uid']) ? $rows : ['uid' => self::make()] + $rows;
        }

        foreach ($rows as $i => $row) {
            if (is_array($row) && !isset($row['uid'])) {
                $rows[$i] = ['uid' => self::make()] + $row;
            }
        }

        return $rows;
    }
}
