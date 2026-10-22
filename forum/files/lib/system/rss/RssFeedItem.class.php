<?php

namespace forum\system\rss;

/**
 * Represents a single entry of an RSS or Atom feed.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class RssFeedItem
{
    /**
     * @param string[] $categories
     */
    public function __construct(
        public readonly string $title,
        public readonly string $link,
        public readonly string $content,
        public readonly string $guid,
        public readonly int $time,
        public readonly array $categories,
    ) {}

    /**
     * Returns a stable identifier of this entry that is used to detect duplicates.
     */
    public function getHash(): string
    {
        return \sha1($this->guid ?: ($this->link ?: $this->title));
    }
}
