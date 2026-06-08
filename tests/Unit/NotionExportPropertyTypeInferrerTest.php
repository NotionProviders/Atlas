<?php

namespace Tests\Unit;

use App\Services\NotionExportPropertyTypeInferrer;
use Tests\TestCase;

class NotionExportPropertyTypeInferrerTest extends TestCase
{
    private NotionExportPropertyTypeInferrer $inferrer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inferrer = new NotionExportPropertyTypeInferrer;
    }

    public function test_infers_types_from_notion_csv_value_patterns(): void
    {
        $this->assertSame('title', $this->inferrer->infer('Name', ['Acme'], true));
        $this->assertSame('checkbox', $this->inferrer->infer('Archive', ['No', 'Yes']));
        $this->assertSame('email', $this->inferrer->infer('Company Email', ['team@example.com']));
        $this->assertSame('phone_number', $this->inferrer->infer('Company Phone', ['+1 410-417-7775']));
        $this->assertSame('rollup', $this->inferrer->infer('Contact Count', ['0 Contacts - ☎']));
        $this->assertSame('created_by', $this->inferrer->infer('Created by', ['Bradley Hubbard']));
        $this->assertSame('created_time', $this->inferrer->infer('Created time', ['November 1, 2025 1:29 PM']));
        $this->assertSame('last_edited_time', $this->inferrer->infer('Last edited', ['November 1, 2025 1:29 PM']));
        $this->assertSame('last_edited_by', $this->inferrer->infer('Last edited by', ['Bradley Hubbard']));
        $this->assertSame('number', $this->inferrer->infer('Project Value', ['$0.00', '0']));
        $this->assertSame('url', $this->inferrer->infer('Website', ['https://example.com']));
        $this->assertSame('relation', $this->inferrer->infer('Self Card', [
            'Acme (Acme%2029e46b5708ad81a689b8ddb21b0d24cc.md)',
        ]));
        $this->assertSame('relation', $this->inferrer->infer('👩‍💼 Business Type', [
            'Service Provider (Service%20Provider%2029e46b5708ad8189b3d2e856b36a1c3e.md)',
        ]));
        $this->assertSame('relation', $this->inferrer->infer('🏷️ Business Tags', [
            'Partner (Partner%2029e46b5708ad814f80f5f39973579f70.md), Technology Provider (Technology%20Provider%2029e46b5708ad816ca1f0d193268c1ea4.md)',
        ]));
        $this->assertSame('relation', $this->inferrer->infer('☎️ Contacts', [
            'Untitled (https://www.notion.so/2863fbf56de181d19040ef8430121e67?pvs=21)',
        ]));
    }

    public function test_empty_relation_columns_default_to_relation(): void
    {
        $this->assertSame('relation', $this->inferrer->infer('🔨 Client Projects', []));
        $this->assertSame('rollup', $this->inferrer->infer('Companies Count', []));
    }
}
