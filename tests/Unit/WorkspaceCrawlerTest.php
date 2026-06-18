<?php

namespace Tests\Unit;

use App\Services\Notion\NotionClient;
use App\Services\Notion\WorkspaceCrawler;
use Tests\TestCase;

class WorkspaceCrawlerTest extends TestCase
{
    public function test_it_expands_pages_before_databases_and_recurses_database_rows(): void
    {
        $client = $this->fakeClient();

        $map = (new WorkspaceCrawler($client))->crawl('Test WS', [
            ['id' => 'rootpage', 'name' => 'Team', 'type' => 'page'],
        ]);

        $team = $map['tree']['children'][0];
        $this->assertSame('Team', $team['label']);
        $this->assertSame('teamspace', $team['t']);

        // Root page has one child page then one child database — pages first.
        $children = $team['children'];
        $this->assertSame('page', $children[0]['t']);
        $this->assertSame('Sub Page', $children[0]['label']);
        $this->assertSame('database', $children[1]['t']);

        // The database's row is itself a page, tinted as a row.
        $row = $children[1]['children'][0];
        $this->assertSame('row', $row['t']);
        $this->assertSame('Row One', $row['label']);

        $this->assertSame(4, $map['meta']['nodeCount']);
    }

    public function test_visited_guard_prevents_infinite_recursion(): void
    {
        // A page whose block child points back at itself must not loop forever.
        $client = new class('test') extends NotionClient
        {
            public function retrievePage(string $id): ?array
            {
                return ['url' => "https://n/{$id}", 'properties' => [
                    'Name' => ['type' => 'title', 'title' => [['plain_text' => $id]]],
                ]];
            }

            public function blockChildren(string $blockId): array
            {
                return [['id' => 'loop', 'type' => 'child_page', 'has_children' => true]];
            }

            public function retrieveDatabase(string $id): ?array
            {
                return null;
            }

            public function queryDatabase(string $databaseId): array
            {
                return [];
            }
        };

        $map = (new WorkspaceCrawler($client))->crawl('Loop', [['id' => 'loop', 'type' => 'page']]);

        $this->assertLessThanOrEqual(2, $map['meta']['nodeCount']);
    }

    private function fakeClient(): NotionClient
    {
        return new class('test') extends NotionClient
        {
            public function retrievePage(string $id): ?array
            {
                $titles = [
                    'rootpage' => 'Root Page',
                    'subpage' => 'Sub Page',
                    'row1' => 'Row One',
                ];

                return [
                    'url' => "https://n/{$id}",
                    'properties' => ['Name' => ['type' => 'title', 'title' => [['plain_text' => $titles[$id] ?? $id]]]],
                ];
            }

            public function blockChildren(string $blockId): array
            {
                if ($blockId === 'rootpage') {
                    return [
                        ['id' => 'subpage', 'type' => 'child_page'],
                        ['id' => 'db1', 'type' => 'child_database'],
                    ];
                }

                return [];
            }

            public function retrieveDatabase(string $id): ?array
            {
                return ['url' => "https://n/{$id}", 'title' => [['plain_text' => 'A Database']]];
            }

            public function queryDatabase(string $databaseId): array
            {
                return [['id' => 'row1']];
            }
        };
    }
}
