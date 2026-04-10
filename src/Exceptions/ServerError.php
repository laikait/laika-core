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

class ServerError
{
    public static function show(): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
            <head>
                <meta charset="UTF-8">
                <title>5xx Internal Server Error!</title>
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <style>
                    body {
                        margin: 0;
                        padding: 0;
                        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                        background: #000000;
                        color: #c2c2c2;
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
                        font-size: 3rem;
                        font-weight: bold;
                        color: #501f1f;
                        margin: 0;
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="error-code">5xx Internal Server Error</div>
                </div>
            </body>
        </html>
        HTML;
    }
}
