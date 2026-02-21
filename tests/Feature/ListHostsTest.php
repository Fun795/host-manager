<?php

namespace Tests\Feature;

use App\Models\Host;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListHostsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_all_hosts(): void
    {
        Host::factory()->count(5)->create();

        $response = $this->getJson(route('hosts.list'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['items'],
            ])
            ->assertJsonCount(5, 'data.items');
    }

    public function test_search_by_hostname_partial_match(): void
    {
        Host::factory()->create(['hostname' => 'app-01']);
        Host::factory()->create(['hostname' => '2-app-03']);
        Host::factory()->create(['hostname' => 'db-01.prod']);

        $response = $this->getJson(route('hosts.list', ['q' => 'app']));

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.items')
            ->assertJsonFragment(['hostname' => 'app-01'])
            ->assertJsonFragment(['hostname' => '2-app-03'])
            ->assertJsonMissing(['hostname' => 'db-01.prod']);
    }

    public function test_search_by_exact_ip(): void
    {
        Host::factory()->create(['hostname' => 'app-01', 'ip' => '10.0.1.5']);
        Host::factory()->create(['hostname' => 'app-02', 'ip' => '10.0.1.6']);

        $response = $this->getJson(route('hosts.list', ['q' => '10.0.1.5']));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.hostname', 'app-01');
    }

    public function test_empty_search_returns_empty_array(): void
    {
        $response = $this->getJson(route('hosts.list', ['q' => 'nonexistent']));

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data.items');
    }

    public function test_pagination_size(): void
    {
        Host::factory()->count(25)->create();

        $response = $this->getJson(route('hosts.list', ['page' => ['size' => 10]]));

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data.items')
            ->assertJsonPath('data.page.size', 10);
    }

    public function test_cursor_pagination_works(): void
    {
        Host::factory()->count(15)->create();

        // Начальная страница
        $response = $this->getJson(route('hosts.list') . '?page[size]=10');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'items',
                    'page' => [
                        'size',
                        'previous',
                        'next',
                    ],
                ],
            ])
            ->assertJsonPath('data.page.next', fn($cursor) => $cursor !== null) // Следующий не пустой
            ->assertJsonPath('data.page.previous', null); // Предыдущий пустой

        // Следующая страница
        $next = $response->json('data.page.next');
        $responseNext = $this->getJson(route('hosts.list') . '?page[size]=10&page[after]=' . $next);
        $responseNext->assertStatus(200)
            ->assertJsonCount(5, 'data.items')
            ->assertJsonPath('data.page.next', null) // Следующий пустой
            ->assertJsonPath('data.page.previous', fn($cursor) => $cursor !== null); // Предыдущий не пустой

        // Собираем id с обеих страниц
        $firstPageIds = collect($response->json('data.items'))->pluck('id');
        $secondPageIds = collect($responseNext->json('data.items'))->pluck('id');

        // Проверяем дубликатов
        $this->assertEquals(
            0,
            $firstPageIds->intersect($secondPageIds)->count()
        );

        // Возврат назад
        $previousCursor = $responseNext->json('data.page.previous');
        $responsePrevious = $this->getJson(route('hosts.list') . '?page[size]=10&page[after]=' . $previousCursor);

        $responsePrevious->assertStatus(200)
            ->assertJsonPath('data.items.0.id', $firstPageIds->first());
    }
}
