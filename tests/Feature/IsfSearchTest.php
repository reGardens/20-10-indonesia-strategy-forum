<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IsfSearchTest extends TestCase
{
    public function test_search_endpoint_returns_suggestions_and_popular_keywords(): void
    {
        $response = $this->getJson('/api/isf/search');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'query',
            'type',
            'total',
            'counts' => ['semua', 'artikel', 'tokoh', 'infografik', 'briefing', 'video'],
            'data',
            'suggestions' => ['Politik', 'Hukum', 'Kebijakan & Ekonomi', 'Geopolitik', 'Tokoh'],
            'popularKeywords',
        ]);

        $json = $response->json();
        $this->assertContains('RUU Perampasan Aset', $json['popularKeywords']);
        $this->assertContains('Hilirisasi Nikel', $json['suggestions']['Kebijakan & Ekonomi']);
    }

    public function test_search_with_query_returns_relevant_aggregated_results(): void
    {
        $response = $this->getJson('/api/isf/search?q=kuhp');

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertSame('kuhp', $json['query']);
        $this->assertGreaterThan(0, $json['total']);

        // Check each result structure
        $first = $json['data'][0];
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('type', $first);
        $this->assertArrayHasKey('typeLabel', $first);
        $this->assertArrayHasKey('title', $first);
        $this->assertArrayHasKey('summary', $first);
        $this->assertArrayHasKey('snippet', $first);
        $this->assertArrayHasKey('to', $first);
    }

    public function test_search_filter_by_content_type(): void
    {
        $response = $this->getJson('/api/isf/search?q=kuhp&type=artikel');

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertSame('artikel', $json['type']);

        foreach ($json['data'] as $item) {
            $this->assertSame('artikel', $item['type']);
        }
    }

    public function test_search_treats_like_wildcards_as_literal_text(): void
    {
        // Sebelum di-escape, "_" adalah wildcard LIKE sehingga cocok dengan hampir semua konten.
        $json = $this->getJson('/api/isf/search?q=' . urlencode('__'))->assertStatus(200)->json();

        $this->assertSame(0, $json['total']);
    }

    public function test_search_query_is_limited_to_100_characters(): void
    {
        $json = $this->getJson('/api/isf/search?q=' . str_repeat('a', 300))->assertStatus(200)->json();

        $this->assertSame(100, mb_strlen($json['query']));
    }

    public function test_search_with_curated_strategic_keyword(): void
    {
        $response = $this->getJson('/api/isf/search?q=nikel');

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertGreaterThan(0, $json['total']);
        $this->assertStringContainsStringIgnoringCase('nikel', json_encode($json['data']));
    }
}

