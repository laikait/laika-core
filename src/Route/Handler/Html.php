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

namespace Laika\Core\Route\Handler;

use DOMDocument;
use Laika\Service\{CSRF, Response};

final class Html
{
    /** @var string CSRF Token */
    private static ?string $csrf = null;

    /**
     * Render Html
     * @param string $str
     * @return void
     */
    public static function render(string $str): void
    {
        // Check $str is Not Empty
        // if (empty($str)) return;
        $dom = new DOMDocument();

        // Suppress warnings caused by invalid HTML
        libxml_use_internal_errors(true);

        $dom->loadHTML($str, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        libxml_clear_errors();

        $forms = $dom->getElementsByTagName('form');

        // Check Empty Form, Send Response
        if (empty($forms)) {
            Response::html($str)->send();
            return;
        }

        // Set Token
        self::$csrf = self::$csrf ?? CSRF::token();

        foreach ($forms as $form) {
            $hasCsrf = false;

            foreach ($form->getElementsByTagName('input') as $input) {
                // Check CSRF Input Field Exists
                if (
                    strtolower($input->getAttribute('type')) == 'hidden' &&
                    $input->getAttribute('name') == CSRF::key()
                ) {
                    $hasCsrf = true;
                    break;
                }
                // Check CSRF Input Type is Hidden
                if (
                    $input->getAttribute('name') == CSRF::key() &&
                    strtolower($input->getAttribute('type'))!= 'hidden'
                ) {
                    Response::json(
                        [
                            "status"        =>  "failed",
                            "message"       =>  "CSRF Input Type Shoud Be [hidden]",
                            "event_time"    =>  Date::toIso8601()
                        ],
                        415)
                    ->send();
                    return;
                }
            }

            // Add CSRF Input if Does Not Exists
            if (!$hasCsrf) {
                $csrf = $dom->createElement('input');

                $csrf->setAttribute('type', 'hidden');
                $csrf->setAttribute('name', CSRF::key());
                $csrf->setAttribute('value', self::$csrf);

                $form->prepend($dom->createTextNode("\n"));
                $form->prepend($csrf);
                $form->prepend($dom->createTextNode("\n"));
            }
        }
        Response::html($dom->saveHTML())->send();
    }
}