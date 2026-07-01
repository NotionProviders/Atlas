<?php

namespace App\Console\Commands;

use App\Services\Notion\NotionClient;
use App\Services\Notion\WorkspaceCrawler;
use App\Services\Notion\WorkspaceMapRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use JsonException;
use Throwable;

class AtlasIngest extends Command
{
    protected $signature = 'atlas:ingest
        {--name= : Display name for the workspace map}
        {--token= : Notion API token (defaults to NOTION_API_KEY)}
        {--teamspaces= : Path to a JSON file of teamspace roots, or an inline JSON array}
        {--discover : Auto-discover roots via the Notion search API when no teamspaces are given}
        {--max-depth= : Override the crawl depth limit}
        {--max-nodes= : Override the crawl node limit}';

    protected $description = 'Recursively map a Notion workspace (teamspaces → pages → databases → ...) into an Atlas map';

    public function handle(WorkspaceMapRepository $maps): int
    {
        $token = $this->option('token') ?: (string) config('notion.token');
        if (trim($token) === '') {
            $this->error('No Notion token. Pass --token or set NOTION_API_KEY in your .env.');

            return self::FAILURE;
        }

        if ($this->option('max-depth')) {
            config(['notion.crawl.max_depth' => (int) $this->option('max-depth')]);
        }
        if ($this->option('max-nodes')) {
            config(['notion.crawl.max_nodes' => (int) $this->option('max-nodes')]);
        }

        try {
            $client = NotionClient::fromConfig($token);
            $me = $client->whoAmI();
            $this->line('Connected to Notion as: <info>'.($me['name'] ?? $me['bot']['owner']['type'] ?? 'integration').'</info>');
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $name = (string) ($this->option('name') ?: 'Notion Workspace');

        try {
            $teamspaces = $this->resolveTeamspaces($client);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($teamspaces === []) {
            $this->error('No teamspace roots found. Provide --teamspaces or use --discover.');
            $this->line('  Teamspaces JSON shape: [{"id":"<page-or-db-id>","name":"My Team","type":"page"}]');

            return self::FAILURE;
        }

        $this->line('Mapping '.count($teamspaces).' root(s)...');

        $crawler = (new WorkspaceCrawler($client))
            ->onProgress(function (string $message) {
                if ($this->output->isVerbose()) {
                    $this->line('  '.$message);
                }
            });

        $map = $crawler->crawl($name, $teamspaces);

        $slug = $maps->save($name, $map);

        $this->newLine();
        $this->info("Mapped {$map['meta']['nodeCount']} nodes across {$map['meta']['teamspaces']} teamspace(s).");
        $this->line("Saved as map '<info>{$slug}</info>'. View it at /?w={$slug}");

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{id: string, name?: string, type?: string}>
     */
    private function resolveTeamspaces(NotionClient $client): array
    {
        $option = $this->option('teamspaces');

        if ($option) {
            $raw = File::exists($option) ? File::get($option) : $option;
            try {
                $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new \RuntimeException('Could not parse --teamspaces JSON: '.$e->getMessage());
            }

            return is_array($decoded) ? array_values($decoded) : [];
        }

        if ($this->option('discover')) {
            return $this->discoverRoots($client);
        }

        return [];
    }

    /**
     * Find top-level objects (parent is the workspace) via the search API.
     * The API can't see teamspace grouping, so these come back flat — still a
     * useful auto-seed when explicit teamspace ids aren't supplied.
     *
     * @return array<int, array{id: string, name: string, type: string}>
     */
    private function discoverRoots(NotionClient $client): array
    {
        $roots = [];
        foreach ($client->search() as $object) {
            $parentType = $object['parent']['type'] ?? '';
            if ($parentType !== 'workspace') {
                continue;
            }

            $type = ($object['object'] ?? '') === 'database' ? 'database' : 'page';
            $roots[] = [
                'id' => $object['id'],
                'name' => $this->objectTitle($object),
                'type' => $type,
            ];
        }

        return $roots;
    }

    private function objectTitle(array $object): string
    {
        if (($object['object'] ?? '') === 'database') {
            return $this->plain($object['title'] ?? []) ?: 'Untitled database';
        }

        foreach (($object['properties'] ?? []) as $prop) {
            if (($prop['type'] ?? null) === 'title') {
                return $this->plain($prop['title'] ?? []) ?: 'Untitled';
            }
        }

        return 'Untitled';
    }

    private function plain(array $richText): string
    {
        return trim(implode('', array_map(
            fn ($s) => $s['plain_text'] ?? ($s['text']['content'] ?? ''),
            $richText
        )));
    }
}
