<?php

namespace App\Support;

class HtmlToMarkdown
{
    /**
     * Convert HTML to Markdown, removing layout/visual noise.
     *
     * @param string $html
     * @return string
     */
    public static function convert(string $html): string
    {
        // Suppress HTML5 parsing warnings
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        // Load with UTF-8 encoding
        $doc->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        // 1. Remove unwanted elements (visual headers, footers, navs, scripts, styles, forms, etc.)
        $xpath = new \DOMXPath($doc);
        $unwanted = $xpath->query('//script | //style | //nav | //header | //footer | //form | //iframe | //noscript');
        foreach ($unwanted as $element) {
            $element->parentNode->removeChild($element);
        }

        // 2. Find the core content container: main, article, or fallback to body, or the document element
        $contentNode = null;
        foreach (['main', 'article', 'body'] as $tagName) {
            $nodes = $doc->getElementsByTagName($tagName);
            if ($nodes->length > 0) {
                $contentNode = $nodes->item(0);
                break;
            }
        }
        if (!$contentNode) {
            $contentNode = $doc->documentElement;
        }

        if (!$contentNode) {
            return '';
        }

        // 3. Convert recursively
        $markdown = self::nodeToMarkdown($contentNode);

        // Clean up excessive whitespace and newlines
        $markdown = preg_replace("/\n{3,}/", "\n\n", $markdown);
        return trim($markdown);
    }

    /**
     * Convert DOMNode to Markdown string.
     */
    private static function nodeToMarkdown(\DOMNode $node): string
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            $text = $node->textContent;
            if (self::isInsidePre($node)) {
                return $text;
            }
            // Collapse multiple spaces/tabs into one
            $text = preg_replace('/\s+/', ' ', $text);
            return $text;
        }

        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return '';
        }

        $tagName = strtolower($node->nodeName);
        $childrenMarkdown = '';
        foreach ($node->childNodes as $child) {
            $childrenMarkdown .= self::nodeToMarkdown($child);
        }

        switch ($tagName) {
            case 'h1':
                return "\n# " . trim($childrenMarkdown) . "\n\n";
            case 'h2':
                return "\n## " . trim($childrenMarkdown) . "\n\n";
            case 'h3':
                return "\n### " . trim($childrenMarkdown) . "\n\n";
            case 'h4':
                return "\n#### " . trim($childrenMarkdown) . "\n\n";
            case 'h5':
                return "\n##### " . trim($childrenMarkdown) . "\n\n";
            case 'h6':
                return "\n###### " . trim($childrenMarkdown) . "\n\n";
            case 'p':
                return "\n" . trim($childrenMarkdown) . "\n\n";
            case 'br':
                return "\n";
            case 'strong':
            case 'b':
                $trimmed = trim($childrenMarkdown);
                return $trimmed !== '' ? "**{$trimmed}**" : '';
            case 'em':
            case 'i':
                $trimmed = trim($childrenMarkdown);
                return $trimmed !== '' ? "*{$trimmed}*" : '';
            case 'code':
                if (self::isInsidePre($node)) {
                    return $childrenMarkdown;
                }
                $trimmed = trim($childrenMarkdown);
                return $trimmed !== '' ? "`{$trimmed}`" : '';
            case 'pre':
                return "\n```\n" . $childrenMarkdown . "\n```\n\n";
            case 'a':
                $href = $node->getAttribute('href');
                $text = trim($childrenMarkdown);
                if (empty($href) || $href === '#') {
                    return $text;
                }
                if ($text === '') {
                    $text = $href;
                }
                return "[{$text}]({$href})";
            case 'img':
                $src = $node->getAttribute('src');
                $alt = $node->getAttribute('alt') ?: 'image';
                if (empty($src)) {
                    return '';
                }
                return "![{$alt}]({$src})";
            case 'ul':
                return "\n" . self::processList($node, false) . "\n";
            case 'ol':
                return "\n" . self::processList($node, true) . "\n";
            case 'li':
                return trim($childrenMarkdown) . "\n";
            case 'blockquote':
                $lines = explode("\n", trim($childrenMarkdown));
                $quoted = array_map(fn($line) => '> ' . $line, $lines);
                return "\n" . implode("\n", $quoted) . "\n\n";
            case 'table':
                return "\n" . self::processTable($node) . "\n\n";
            case 'div':
            case 'section':
            case 'article':
            case 'main':
                return "\n" . $childrenMarkdown . "\n";
            default:
                return $childrenMarkdown;
        }
    }

    /**
     * Check if node is inside a pre element.
     */
    private static function isInsidePre(\DOMNode $node): bool
    {
        $current = $node->parentNode;
        while ($current) {
            if (strtolower($current->nodeName) === 'pre') {
                return true;
            }
            $current = $current->parentNode;
        }
        return false;
    }

    /**
     * Process list elements recursively.
     */
    private static function processList(\DOMNode $listNode, bool $ordered): string
    {
        $markdown = '';
        $index = 1;
        foreach ($listNode->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE && strtolower($child->nodeName) === 'li') {
                $itemText = '';
                foreach ($child->childNodes as $liChild) {
                    $itemText .= self::nodeToMarkdown($liChild);
                }
                $itemText = trim($itemText);
                if ($itemText !== '') {
                    $prefix = $ordered ? "{$index}. " : "* ";
                    $markdown .= "{$prefix}{$itemText}\n";
                    $index++;
                }
            }
        }
        return $markdown;
    }

    /**
     * Process table element.
     */
    private static function processTable(\DOMNode $tableNode): string
    {
        $rows = [];
        $hasHeaders = false;
        
        foreach ($tableNode->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $name = strtolower($child->nodeName);
                if ($name === 'tr') {
                    $rows[] = self::processRow($child, $hasHeaders);
                } elseif ($name === 'thead' || $name === 'tbody' || $name === 'tfoot') {
                    foreach ($child->childNodes as $subChild) {
                        if ($subChild->nodeType === XML_ELEMENT_NODE && strtolower($subChild->nodeName) === 'tr') {
                            $rows[] = self::processRow($subChild, $hasHeaders);
                        }
                    }
                }
            }
        }

        if (empty($rows)) {
            return '';
        }

        $markdown = '';
        $headerRow = $rows[0];
        $markdown .= '| ' . implode(' | ', $headerRow) . " |\n";
        
        $separator = [];
        foreach ($headerRow as $cell) {
            $separator[] = '---';
        }
        $markdown .= '| ' . implode(' | ', $separator) . " |\n";

        for ($i = 1; $i < count($rows); $i++) {
            $markdown .= '| ' . implode(' | ', $rows[$i]) . " |\n";
        }

        return $markdown;
    }

    /**
     * Process row element.
     */
    private static function processRow(\DOMNode $rowNode, &$hasHeaders): array
    {
        $cells = [];
        foreach ($rowNode->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $name = strtolower($child->nodeName);
                if ($name === 'td' || $name === 'th') {
                    if ($name === 'th') {
                        $hasHeaders = true;
                    }
                    $cellText = '';
                    foreach ($child->childNodes as $cellChild) {
                        $cellText .= self::nodeToMarkdown($cellChild);
                    }
                    $cells[] = str_replace('|', '\\|', trim($cellText));
                }
            }
        }
        return $cells;
    }
}
