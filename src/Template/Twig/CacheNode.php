<?php

/**
 * Laika Framework
 * Author: Showket Ahmed
 * Email: riyadhtayf@gmail.com
 * License: MIT
 * This file is part of the Laika Framework.
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Laika\Core\Template\Twig;

use Laika\Core\Template\FragmentCache;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\CaptureNode;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Node;

/**
 * Compiles {% cache %}
 *
 * On a hit the stored HTML is output and the body never runs -- including any
 * query or function call inside it, which is the point. On a miss the body is
 * captured, stored, then output.
 *
 * The body is captured with Twig's own CaptureNode, which works whether the
 * environment renders with echo or with yield. Captured output is already
 * escaped, so it is output as-is.
 */
#[YieldReady]
final class CacheNode extends Node
{
    public function __construct(AbstractExpression $key, ?AbstractExpression $ttl, Node $body, int $lineno)
    {
        $nodes = ['key' => $key, 'body' => $body];

        if ($ttl !== null) {
            $nodes['ttl'] = $ttl;
        }

        parent::__construct($nodes, [], $lineno);
    }

    public function compile(Compiler $compiler): void
    {
        $key = $compiler->getVarName();
        $ttl = $compiler->getVarName();
        $html = $compiler->getVarName();
        $issued = $compiler->getVarName();
        $cache = '\\' . FragmentCache::class;

        $capture = new CaptureNode($this->getNode('body'), $this->getTemplateLine());
        $capture->setAttribute('raw', true);

        $compiler
            ->addDebugInfo($this)
            ->write('$' . $key . ' = (string) ')
            ->subcompile($this->getNode('key'))
            ->raw(";\n")
            ->write('$' . $ttl . ' = ');

        if ($this->hasNode('ttl')) {
            $compiler->subcompile($this->getNode('ttl'));
        } else {
            $compiler->raw('null');
        }

        $compiler
            ->raw(";\n")
            ->write('$' . $html . ' = ' . $cache . '::get($' . $key . ");\n")
            ->write('if (null === $' . $html . ") {\n")
            ->indent()
            ->write('$' . $issued . ' = ' . $cache . "::issued();\n")
            ->write('$' . $html . ' = ')
            ->subcompile($capture)
            ->raw("\n")
            ->write($cache . '::put($' . $key . ', $' . $html . ', $' . $ttl . ', $' . $issued . ");\n")
            ->outdent()
            ->write("}\n")
            ->write('yield $' . $html . ";\n");
    }
}
