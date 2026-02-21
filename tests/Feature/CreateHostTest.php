<?php

namespace Tests\Feature;

use App\Models\Host;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateHostTest extends TestCase
{
    use RefreshDatabase;

    public function test_hostname_must_be_unique(): void
    {
        Host::factory()->create(['hostname' => 'app-01']);

        $response = $this->postJson(route('hosts.create'), [
            'hostname' => 'app-01',
            'ip' => '10.0.1.6',
        ]);

        $response->assertStatus(409);
    }

    public function test_hostname_validation_format(): void
    {
        $this->withExceptionHandling();

        $invalidHostnames = [
            'INVALID_UPPERCASE',
            '-starts-with-dash',
            'ends-with-dash-',
            'has_underscore',
            'has spaces',
            '',
            '@',
            'кириллица'
        ];

        foreach ($invalidHostnames as $hostname) {
            $response = $this->postJson(route('hosts.create'), [
                'hostname' => $hostname,
                'ip' => '10.0.1.5',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors('hostname');
        }
    }

    public function test_hostname_success_create(): void
    {
        $response = $this->postJson(route('hosts.create'), [
            'hostname' => 'app-01',
            'ip' => '10.0.1.7',
        ]);

        $response->assertStatus(201);
    }

    public function test_ip_validation(): void
    {
        $invalidIps = [
            'not-an-ip',
            '256.256.256.256',
            '10.0.1',
            '',
        ];

        foreach ($invalidIps as $ip) {
            $response = $this->postJson(route('hosts.create'), [
                'hostname' => 'app-01',
                'ip' => $ip,
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors('ip');
        }
    }

    public function test_ip_valid_ipv4(): void
    {
        $validIps = [
            '10.0.1.5',
            '192.168.1.1',
        ];

        foreach ($validIps as $index => $ip) {
            $response = $this->postJson('/api/hosts', [
                'hostname' => 'app-' . $index,
                'ip' => $ip,
            ]);

            $response->assertStatus(201);
        }
    }
}
