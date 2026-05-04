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

/*=================================== URL HOOKS ===================================*/
// Get App Host
add_hook('app.host', 'app_host', 1000);

/*=================================== ASSET HOOKS ===================================*/
// Load Template Asset
add_hook('asset.src', 'asset_src', 1000);

// Local Language Value
add_hook('local', 'local', 1000);

/*================================== CSRF HOOKS ==================================*/
// CSRF Token HTML Field
add_hook('csrf.field', 'csrf_field', 1000);

/*================================== ALERT HOOKS ==================================*/
// Set Alert Message
add_hook('alert.set', 'alert_set', 1000);

// Get Alert Message
add_hook('alert.get', 'alert_get', 1000);

/*================================== PAGE HOOKS ==================================*/
// Page Title
add_hook('page.title', 'page_title', 1000);

// Page Number
add_hook('page.number', 'page_number', 1000);

/*================================== REQUEST HOOKS ==================================*/
// Get Request Header Value
add_hook('request.header', 'request_header', 1000);

// Get Request Key Value
add_hook('request.input', 'request_input', 1000);

// Get Request Inputs
add_hook('request.inputs', 'request_inputs', 1000);

// Check Method Request is Post/Get/Put/Patch/Delete/Ajax
add_hook('request.is', 'request_is', 1000);

/*================================== TEMPLATE HOOKS ==================================*/
// Enqueue Meta
add_hook('enqueue.meta', 'enqueue_meta', 1000);

// Print All Meta Tags
add_hook('print.meta', 'print_metas', 1000);

// Enqueue Style
add_hook('enqueue.style', 'enqueue_style', 1000);

// Print All Styles in Template
add_hook('print.styles', 'print_styles', 1000);

// Enqueue Script
add_hook('enqueue.script', 'enqueue_script', 1000);

// Print Scripts
add_hook('print.scripts', 'print_scripts', 1000);

// Header All Meta & Styles in Template (Before </head>)
add_hook('print.header', 'lf_header', 1000);

// Print All Footer Scripts In Template Footer (Before </body>)
add_hook('print.footer', 'lf_footer', 1000);

/*================================== COMMON HOOKS ==================================*/
/** Get All Timezones */
add_hook('time.zones', 'time_zones', 1000);
