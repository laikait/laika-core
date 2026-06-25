<?php
/**
 * Laika Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 * This file is part of the Laika PHP MMC Framework.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Laika\Core\Console\Commands\Template;

use Laika\Core\Console\Command;
use Laika\Service\{Directory, File};

class Make extends Command
{
    // App View Path
    protected string $path = APP_PATH . '/template';

    // Accepted Regular Expresion
    private string $exp = '/^[\w\-\/]+$/';

    /**
     * Run the command to create a new controller.
     *
     * @param array $params
     * @return void
     */
    public function run(array $params, array $options = []): void
    {
        // Check Parameters
        if (count($params) != 1) {
            $this->error("USAGE: php laika make:template <name> <options>");
            return;
        }

        // Get Extension
        $ext = strtolower($options['short']['e'] ?? 'twig');

        if (!in_array($ext, ['twig', 'html'])) {
            $this->error("Invalid Template File Extension: '{$ext}'. Allowed Extensions Are: ['twig','html']");
            return;
        }

        // Set Path
        if (!empty($options['short']['d'])) {
            // Check Valid Chars
            if (!preg_match('/^[\w\d\/\.\-]+$/', $options['short']['d'])) {
                $this->error("Invalid Subdirectory: [{$options['short']['d']}]");
                return;
            }
            $this->path .= '/' . trim($options['short']['d'], '/');
        }

        if (!preg_match($this->exp, $params[0])) {
            // Invalid Name
            $this->error("Invalid Template Name: '{$params[0]}'");
            return;
        }

        $name = trim($params[0]);

        // Make Directory if Not Exist
        try {
            Directory::make($this->path);
        } catch (\Throwable $th) {
            $this->error($th->getMessage());
            return;
        }

        $file = "{$this->path}/{$name}.{$ext}";

        if (File::exists($file)) {
            $this->error("Template Already Exist: {$file}");
            return;
        }

        // Get Sample Content
        $content = File::read(__DIR__ . '/../../Samples/Template.sample');
        if ($content === false) {
            $this->error("Failed to Read Sample: [{$file}]!");
            return;
        }

        // Replace Placeholders
        if (File::write($content, $file) === false) {
            $this->error("Failed to Create Template: {$file}");
            return;
        }

        $this->success("Template Created Successfully: {$name}.{$ext}");
        return;
    }
}
