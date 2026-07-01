<?php

namespace App\Services\Migration;

/**
 * Post-processing for markdown produced by a source connector.
 *
 * This is a faithful PHP port of the Python loop-migration exporter's cleanup
 * (`_clean_markdown`, `fix_sharepoint_links`, `_safe_filename`, `_is_empty_page`):
 * the extension converts a page's DOM to markdown, and the heavy-handed tidy —
 * unescaping punctuation, collapsing double-spaced bullets, neutralising Loop /
 * SharePoint links that would 404 in Notion — happens here, server-side, so
 * every connector benefits from the same battle-tested pass.
 */
class MarkdownCleaner
{
    /** Loop/SharePoint URL fragment => human label. */
    private const SP_EXTS = [
        '/:x:/' => 'Excel spreadsheet',
        '/:w:/' => 'Word document',
        '/:p:/' => 'PowerPoint',
        '/:f:/' => 'Folder',
        '/:fl:/' => 'Loop component',
        '.loop' => 'Loop component',
    ];

    /**
     * General markdown tidy. `$title` is the page title, stripped when it shows
     * up as a stray repeated line (Loop echoes it in nav + canvas).
     */
    public static function clean(string $md, string $title = ''): string
    {
        // Invisible/zero-width unicode.
        $md = preg_replace('/[\x{200B}\x{200C}\x{200D}\x{2060}\x{FEFF}\x{00AD}]/u', '', $md);
        // Unescape punctuation html-to-markdown needlessly escapes.
        $md = preg_replace('/\\\\([–—•·~`\-])/u', '$1', $md);
        // Trailing spaces.
        $md = preg_replace('/ {2,}$/m', '', $md);
        // [Loading] placeholders and blob/CDN images that slipped through.
        $md = preg_replace('/\[Loading\]\([^)]*\)/', '', $md);
        $md = preg_replace('/!\[[^\]]*\]\(blob:[^)]*\)/', '', $md);
        $md = preg_replace('/!\[[^\]]*\]\(https:\/\/res\.cdn\.office\.net[^)]*\)/', '', $md);
        // Lines that are just underscores (icon/button artifacts).
        $md = preg_replace('/^\s*_{1,2}\s*$/m', '', $md);
        // Repeated page-title line.
        if ($title !== '') {
            $md = preg_replace('/^'.preg_quote($title, '/').'\s*$/mu', '', $md);
        }
        // Empty bullet lines.
        $md = preg_replace('/^[ \t]*[-*+]\s*$\n?/m', '', $md);
        // Blank lines between consecutive list items (bulleted, then numbered).
        $md = preg_replace('/^([ \t]*[-*+] .+)\n\n(?=[ \t]*[-*+] )/m', "$1\n", $md);
        $md = preg_replace('/^([ \t]*\d+\. .+)\n\n(?=[ \t]*\d+\. )/m', "$1\n", $md);
        // Collapse 3+ blank lines.
        $md = preg_replace('/\n{3,}/', "\n\n", $md);
        // Per-line right-trim, then tidy the ends.
        $md = implode("\n", array_map('rtrim', explode("\n", $md)));
        $md = trim($md);

        return (string) preg_replace('/\n{3,}/', "\n\n", $md);
    }

    /**
     * Replace Loop / SharePoint links (which will 404 after a Notion import)
     * with a visible "re-attach in Notion" annotation, and collect the list of
     * artifacts so the console can render a re-attach manifest.
     *
     * @return array{0: string, 1: array<int, array{page: string, label: string, kind: string, url: string}>}
     */
    public static function fixSourceLinks(string $md, string $pageTitle): array
    {
        $artifacts = [];

        // 1. Inline markdown links: [label](sharepoint/loop url)
        $md = preg_replace_callback(
            '/\[([^\]]*)\]\(<?(https?:\/\/(?:[^\s)>]*\.sharepoint\.com|loop\.cloud\.microsoft)[^)>]*?)>?\)/',
            function (array $m) use (&$artifacts, $pageTitle): string {
                [$label, $url] = [$m[1], $m[2]];
                $kind = self::linkLabel($url);
                $artifacts[] = ['page' => $pageTitle, 'label' => $label ?: $kind, 'kind' => $kind, 'url' => $url];
                $display = ($label !== '' && $label !== 'Link' && $label !== $url) ? $label : $kind;

                return "**{$display}** _(⚠ {$kind} — re-attach in Notion)_";
            },
            $md
        );

        // 2. Bare URLs on their own line.
        $md = preg_replace_callback(
            '/^(https?:\/\/(?:[^\s]*\.sharepoint\.com|loop\.cloud\.microsoft)[^\s]*)$/m',
            function (array $m) use (&$artifacts, $pageTitle): string {
                $url = $m[1];
                $kind = self::linkLabel($url);
                $artifacts[] = ['page' => $pageTitle, 'label' => $kind, 'kind' => $kind, 'url' => $url];

                return "> ⚠ **{$kind}** — re-attach in Notion\n> _{$url}_";
            },
            $md
        );

        return [$md, $artifacts];
    }

    private static function linkLabel(string $url): string
    {
        foreach (self::SP_EXTS as $pat => $label) {
            if (str_contains($url, $pat)) {
                return $label;
            }
        }

        return str_contains($url, 'sharepoint.com') ? 'SharePoint file' : 'Loop link';
    }

    /**
     * Turn a page title into a filesystem-safe file/dir name, stripping
     * embedded URLs and reserved characters. Mirrors the Python `_safe_filename`.
     */
    public static function safeFilename(string $name): string
    {
        $name = preg_replace('/https?:\/\/\S+/', '', $name);
        $name = preg_replace('/\bhttps?\S*(?:sharepoint|loop\.cloud\.microsoft|microsoftonline)\S*/i', '', $name);
        $name = preg_replace('/[\\\\\/*?:"<>|]/', '', $name);
        $name = preg_replace('/[\r\n\t]+/', ' ', $name);
        $name = trim($name, '. ');
        $name = trim((string) preg_replace('/\s+/', ' ', $name));
        $name = mb_substr($name, 0, 200);

        return $name !== '' ? $name : 'Untitled';
    }

    /**
     * A page is "empty" if it has no body beyond its H1, or a tiny body that is
     * just Loop's blank-page chrome ("Start from scratch", "Template Gallery"…).
     */
    public static function isEmptyPage(string $md): bool
    {
        $body = trim((string) preg_replace('/^#[^\n]*\n/', '', $md, 1));
        if ($body === '') {
            return true;
        }

        $signals = ['Start from scratch', 'Template Gallery', 'Blank page', 'Add icon Add cover'];
        if (mb_strlen($body) < 80) {
            foreach ($signals as $sig) {
                if (str_contains($body, $sig)) {
                    return true;
                }
            }
        }

        return false;
    }
}
