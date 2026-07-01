<?php

namespace Tests\Unit;

use App\Services\Migration\MarkdownCleaner;
use PHPUnit\Framework\TestCase;

class MarkdownCleanerTest extends TestCase
{
    public function test_collapses_double_spaced_bullets(): void
    {
        $out = MarkdownCleaner::clean("- a\n\n- b\n\n- c", 'Title');
        $this->assertStringContainsString("- a\n- b\n- c", $out);
    }

    public function test_unescapes_punctuation_and_strips_repeated_title(): void
    {
        $out = MarkdownCleaner::clean("Title\n\nA \\– B", 'Title');
        $this->assertStringContainsString('A – B', $out);
        $this->assertStringNotContainsString("\\–", $out);
        // The stray repeated title line is removed.
        $this->assertStringNotContainsString("Title\n", $out."\n");
    }

    public function test_fix_source_links_annotates_and_collects_artifacts(): void
    {
        $md = "See [the deck](https://contoso.sharepoint.com/:p:/x) and\nhttps://loop.cloud.microsoft/abc";
        [$fixed, $artifacts] = MarkdownCleaner::fixSourceLinks($md, 'Page');

        $this->assertCount(2, $artifacts);
        $this->assertStringContainsString('re-attach in Notion', $fixed);
        $this->assertSame('PowerPoint', $artifacts[0]['kind']);
    }

    public function test_safe_filename_strips_urls_and_reserved_chars(): void
    {
        $this->assertSame('ABC', MarkdownCleaner::safeFilename('A/B:C?'));
        $this->assertSame('Notes', MarkdownCleaner::safeFilename('Notes https://loop.cloud.microsoft/x'));
        $this->assertSame('Untitled', MarkdownCleaner::safeFilename('  ...  '));
    }

    public function test_empty_page_detection(): void
    {
        $this->assertTrue(MarkdownCleaner::isEmptyPage("# T\n\n"));
        $this->assertTrue(MarkdownCleaner::isEmptyPage("# T\n\nStart from scratch"));
        $this->assertFalse(MarkdownCleaner::isEmptyPage("# T\n\nThis is a real page with a proper sentence of content."));
    }
}
