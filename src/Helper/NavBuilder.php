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

class NavBuilder
{
   /**
    * @var array $items nav Items
    */
   protected array $items = [];

   /**
    * Add Nav Item
    * @param string $title Nav Title. Example: 'home'
    * @param string $url Nav URL. Example: 'https://google.com'
    * @param ?callable $child Add Child Nav. Example: function(NavBuilder $n){$n->add('title','url')}
    * @param bool $display Display Nav Item or Not. true for has access & false for no access
    * @return self
    */
   public function add(string $title, string $url, ?callable $child = null, bool $display = true): self
   {
      // Check Display Blocked
      if (!$display) {
         return $this;
      }

      // Add Item in Navbar
      $item = [
         'title' => $title,
         'url'   => $url,
         'child' => []
      ];

      if ($child) {
         $obj = new self();
         $child($obj);
         $item['child'] = $obj->items;
      }

      $this->items[] = $item;
      return $this;
   }

   /**
    * Render Navbar
    * @param string $class Navbar Parent Class Name. Default is navbar
    * @return string
    */
   public function render(string $class = 'navbar'): string
   {
      return "<div id=\"{$class}\" class=\"{$class}\">{$this->build($this->items)}</div>";
   }

   /**
    * Items to Build
    * @param array $items Nav List Items
    * @return string
    */
   protected function build(array $items): string
   {
      $html = '<ul>';

      foreach ($items as $item) {
         $title = \htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8');
         $url   = \htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8');

         $html .= '<li>';
         $html .= "<a href=\"{$url}\">{$title}</a>";

         if (!empty($item['child'])) {
            $html .= $this->build($item['child']);
         }

         $html .= '</li>';
      }

      return $html . '</ul>';
   }
}
