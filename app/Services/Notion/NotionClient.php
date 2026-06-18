<?php

namespace App\Services\Notion;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin wrapper over the Notion REST API (api.notion.com).
 *
 * Only the read endpoints needed to recursively map a workspace are exposed:
 * search, page/database retrieval, block-children listing, and database
 * querying. All list endpoints handle Notion's cursor pagination internally.
 */
class NotionClient
{
    public function __construct(
        private readonly string $token,
        private readonly string $version = '2022-06-28',
        private readonly string $baseUrl = 'https://api.notion.com/v1',
    ) {
        if (trim($this->token) === '') {
            throw new RuntimeException('A Notion API token is required. Set NOTION_API_KEY or pass --token.');
        }
    }

    public static function fromConfig(?string $token = null): self
    {
        return new self(
            token: $token ?? (string) config('notion.token'),
            version: (string) config('notion.version', '2022-06-28'),
            baseUrl: (string) config('notion.base_url', 'https://api.notion.com/v1'),
        );
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken($this->token)
            ->withHeaders(['Notion-Version' => $this->version])
            ->acceptJson()
            ->retry(3, 500, throw: false)
            ->timeout(30);
    }

    /**
     * Confirm the token works and return the bot/user it belongs to.
     */
    public function whoAmI(): array
    {
        $res = $this->request()->get('/users/me');

        if ($res->failed()) {
            throw new RuntimeException('Notion token check failed: '.$this->errorMessage($res->json(), $res->status()));
        }

        return $res->json();
    }

    /**
     * Search the workspace. Without a query this returns every page and
     * database the integration can access, which is how we discover roots
     * (their parent is the workspace itself).
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(?string $query = null, ?string $filterType = null): array
    {
        $payload = ['page_size' => 100];

        if ($query !== null && $query !== '') {
            $payload['query'] = $query;
        }

        if ($filterType !== null) {
            $payload['filter'] = ['property' => 'object', 'value' => $filterType];
        }

        return $this->paginate('/search', $payload);
    }

    public function retrievePage(string $id): ?array
    {
        return $this->getOrNull("/pages/{$id}");
    }

    public function retrieveDatabase(string $id): ?array
    {
        return $this->getOrNull("/databases/{$id}");
    }

    /**
     * List the immediate block children of a page or block.
     *
     * @return array<int, array<string, mixed>>
     */
    public function blockChildren(string $blockId): array
    {
        return $this->paginate("/blocks/{$blockId}/children", ['page_size' => 100], 'get');
    }

    /**
     * Query the rows (pages) of a database.
     *
     * @return array<int, array<string, mixed>>
     */
    public function queryDatabase(string $databaseId): array
    {
        return $this->paginate("/databases/{$databaseId}/query", ['page_size' => 100]);
    }

    /**
     * Walk a Notion cursor-paginated endpoint and collect every result.
     *
     * @return array<int, array<string, mixed>>
     */
    private function paginate(string $path, array $payload, string $method = 'post'): array
    {
        $results = [];
        $cursor = null;

        do {
            $body = $payload;
            if ($cursor !== null) {
                $body['start_cursor'] = $cursor;
            }

            $res = $method === 'get'
                ? $this->request()->get($path, $body)
                : $this->request()->post($path, $body);

            if ($res->status() === 404) {
                // Object exists but the integration lacks access — skip quietly.
                return $results;
            }

            if ($res->failed()) {
                throw new RuntimeException(
                    "Notion request to {$path} failed: ".$this->errorMessage($res->json(), $res->status())
                );
            }

            $json = $res->json();
            foreach (($json['results'] ?? []) as $row) {
                $results[] = $row;
            }

            $cursor = ($json['has_more'] ?? false) ? ($json['next_cursor'] ?? null) : null;

            $throttle = (int) config('notion.crawl.throttle_ms', 0);
            if ($throttle > 0 && $cursor !== null) {
                usleep($throttle * 1000);
            }
        } while ($cursor !== null);

        return $results;
    }

    private function getOrNull(string $path): ?array
    {
        $res = $this->request()->get($path);

        if ($res->status() === 404) {
            return null;
        }

        if ($res->failed()) {
            throw new RuntimeException("Notion request to {$path} failed: ".$this->errorMessage($res->json(), $res->status()));
        }

        return $res->json();
    }

    private function errorMessage(mixed $json, int $status): string
    {
        if (is_array($json) && isset($json['message'])) {
            return "[{$status}] ".$json['message'];
        }

        return "HTTP {$status}";
    }
}
