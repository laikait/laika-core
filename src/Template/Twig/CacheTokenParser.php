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

use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * {% cache 'key' %}...{% endcache %}
 * {% cache 'sidebar-' ~ user.id 600 %}...{% endcache %}
 *
 * The key is any expression. The optional second expression is a TTL in
 * seconds; without one the cache's configured default applies.
 */
final class CacheTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): Node
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $key = $this->parser->parseExpression();
        $ttl = $stream->test(Token::BLOCK_END_TYPE) ? null : $this->parser->parseExpression();

        $stream->expect(Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideCacheEnd'], true);
        $stream->expect(Token::BLOCK_END_TYPE);

        return new CacheNode($key, $ttl, $body, $lineno);
    }

    public function decideCacheEnd(Token $token): bool
    {
        return $token->test('endcache');
    }

    public function getTag(): string
    {
        return 'cache';
    }
}
