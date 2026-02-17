<?php

/**
 * Laika PHP Micro Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 * This file is part of the Laika PHP Micro Framework.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Laika\Core\App\Route;

class _404
{
    public static function show(): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
            <head>
                <meta charset="UTF-8">
                <title>404 Page Not Found!</title>
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <style>
                    body {
                        margin: 0;
                        padding: 0;
                        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                        background: #f5f7fa;
                        color: #333;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        height: 100vh;
                    }

                    .container {
                        text-align: center;
                        padding: 2rem;
                    }

                    .error-code {
                        font-size: 8rem;
                        font-weight: bold;
                        color: #0b5394;
                        margin: 0;
                    }

                    .message {
                        font-size: 1.5rem;
                        color: #555;
                        margin: 1rem 0;
                    }

                    .note {
                        margin-top: 1rem;
                        font-size: 0.9rem;
                        color: #999;
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="error-code">404</div>
                    <div class="message">Oops! The page you're looking for doesn't exist.</div>
                    <div class="note">If you believe this is an error, please contact the site administrator.</div>
                </div>
            </body>
        </html>
        HTML;
    }
}
