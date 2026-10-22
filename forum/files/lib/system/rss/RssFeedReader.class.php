<?php

namespace forum\system\rss;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use wcf\system\exception\SystemException;
use wcf\system\io\HttpFactory;
use wcf\util\StringUtil;

/**
 * Downloads and parses RSS 1.0 (RDF), RSS 2.0 and Atom feeds.
 *
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
final class RssFeedReader
{
    private const MAX_SIZE = 5 * 1024 * 1024;

    private const NS_ATOM = 'http://www.w3.org/2005/Atom';

    private const NS_CONTENT = 'http://purl.org/rss/1.0/modules/content/';

    private const NS_DC = 'http://purl.org/dc/elements/1.1/';

    private const NS_RSS1 = 'http://purl.org/rss/1.0/';

    /**
     * Downloads the feed at the given url and returns its entries, newest first.
     *
     * @return RssFeedItem[]
     */
    public function read(string $url): array
    {
        $client = HttpFactory::makeClient([
            RequestOptions::TIMEOUT => 10,
            RequestOptions::STREAM => true,
        ]);

        try {
            $response = $client->send(new Request('GET', $url, [
                'accept' => 'application/rss+xml, application/atom+xml, application/xml;q=0.9, text/xml;q=0.8, */*;q=0.1',
            ]));
        } catch (\Throwable $e) {
            throw new SystemException("Unable to fetch '{$url}': " . $e->getMessage(), 0, '', $e);
        }

        $body = '';
        $stream = $response->getBody();
        while (!$stream->eof()) {
            $body .= $stream->read(8192);
            if (\strlen($body) > self::MAX_SIZE) {
                throw new SystemException("The feed at '{$url}' exceeds the maximum size.");
            }
        }

        return $this->parse($body);
    }

    /**
     * Parses the given feed document.
     *
     * @return RssFeedItem[]
     */
    public function parse(string $xml): array
    {
        $document = new \DOMDocument();
        $useErrors = \libxml_use_internal_errors(true);
        try {
            $loaded = $document->loadXML(\trim($xml), \LIBXML_NONET | \LIBXML_NOCDATA | \LIBXML_COMPACT);
        } finally {
            \libxml_clear_errors();
            \libxml_use_internal_errors($useErrors);
        }

        if (!$loaded || $document->documentElement === null) {
            throw new SystemException('The feed is not a valid XML document.');
        }

        $root = $document->documentElement;
        $items = match ($root->localName) {
            'feed' => $this->parseAtom($root),
            'rss' => $this->parseRss($root),
            'RDF' => $this->parseRdf($root),
            default => throw new SystemException("Unsupported feed format '{$root->localName}'."),
        };

        \usort($items, static fn(RssFeedItem $a, RssFeedItem $b) => $b->time <=> $a->time);

        return $items;
    }

    /**
     * @return RssFeedItem[]
     */
    private function parseRss(\DOMElement $root): array
    {
        $channel = $this->child($root, 'channel');
        if ($channel === null) {
            return [];
        }

        $items = [];
        foreach ($this->children($channel, 'item') as $item) {
            $guid = $this->text($this->child($item, 'guid'));
            $link = $this->text($this->child($item, 'link'));
            if ($link === '' && \str_starts_with($guid, 'http')) {
                $link = $guid;
            }

            $categories = [];
            foreach ($this->children($item, 'category') as $category) {
                $categories[] = $this->text($category);
            }

            $content = $this->text($this->child($item, 'encoded', self::NS_CONTENT))
                ?: $this->text($this->child($item, 'description'));

            $items[] = new RssFeedItem(
                $this->text($this->child($item, 'title')),
                $link,
                $content,
                $guid,
                $this->parseTime(
                    $this->text($this->child($item, 'pubDate'))
                        ?: $this->text($this->child($item, 'date', self::NS_DC))
                ),
                \array_values(\array_filter($categories)),
            );
        }

        return $items;
    }

    /**
     * @return RssFeedItem[]
     */
    private function parseRdf(\DOMElement $root): array
    {
        $items = [];
        foreach ($this->children($root, 'item', self::NS_RSS1) as $item) {
            $link = $this->text($this->child($item, 'link', self::NS_RSS1));
            $categories = [];
            foreach ($this->children($item, 'subject', self::NS_DC) as $subject) {
                $categories[] = $this->text($subject);
            }

            $items[] = new RssFeedItem(
                $this->text($this->child($item, 'title', self::NS_RSS1)),
                $link,
                $this->text($this->child($item, 'encoded', self::NS_CONTENT))
                    ?: $this->text($this->child($item, 'description', self::NS_RSS1)),
                $item->getAttributeNS('http://www.w3.org/1999/02/22-rdf-syntax-ns#', 'about') ?: $link,
                $this->parseTime($this->text($this->child($item, 'date', self::NS_DC))),
                \array_values(\array_filter($categories)),
            );
        }

        return $items;
    }

    /**
     * @return RssFeedItem[]
     */
    private function parseAtom(\DOMElement $root): array
    {
        $items = [];
        foreach ($this->children($root, 'entry', self::NS_ATOM) as $entry) {
            $link = '';
            foreach ($this->children($entry, 'link', self::NS_ATOM) as $element) {
                $rel = $element->getAttribute('rel') ?: 'alternate';
                if ($rel === 'alternate' || $link === '') {
                    $link = $element->getAttribute('href');
                }
            }

            $categories = [];
            foreach ($this->children($entry, 'category', self::NS_ATOM) as $category) {
                $categories[] = $category->getAttribute('label') ?: $category->getAttribute('term');
            }

            $items[] = new RssFeedItem(
                $this->text($this->child($entry, 'title', self::NS_ATOM)),
                $link,
                $this->text($this->child($entry, 'content', self::NS_ATOM))
                    ?: $this->text($this->child($entry, 'summary', self::NS_ATOM)),
                $this->text($this->child($entry, 'id', self::NS_ATOM)),
                $this->parseTime(
                    $this->text($this->child($entry, 'published', self::NS_ATOM))
                        ?: $this->text($this->child($entry, 'updated', self::NS_ATOM))
                ),
                \array_values(\array_filter(\array_map(StringUtil::trim(...), $categories))),
            );
        }

        return $items;
    }

    private function parseTime(string $value): int
    {
        if ($value === '') {
            return 0;
        }

        $time = \strtotime($value);

        return $time === false ? 0 : \min($time, TIME_NOW);
    }

    private function child(\DOMElement $parent, string $name, ?string $namespace = null): ?\DOMElement
    {
        foreach ($this->children($parent, $name, $namespace) as $child) {
            return $child;
        }

        return null;
    }

    /**
     * Yields the direct children with the given local name. Elements without a
     * namespace match if no namespace is requested.
     *
     * @return \Generator<\DOMElement>
     */
    private function children(\DOMElement $parent, string $name, ?string $namespace = null): \Generator
    {
        foreach ($parent->childNodes as $node) {
            if (!($node instanceof \DOMElement) || $node->localName !== $name) {
                continue;
            }

            if ($namespace === null ? ($node->namespaceURI === null || $node->namespaceURI === '') : $node->namespaceURI === $namespace) {
                yield $node;
            }
        }
    }

    private function text(?\DOMElement $element): string
    {
        return $element === null ? '' : StringUtil::trim($element->textContent);
    }
}
