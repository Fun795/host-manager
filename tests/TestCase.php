<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function createUserWithToken(array $attributes = [], array $abilities = []): array
    {
        $user = User::factory()->create($attributes);
        $token = $user->createToken('test-token', $abilities);

        return [
            'user' => $user,
            'token' => $token->plainTextToken,
            'headers' => [
                'Authorization' => 'Bearer ' . $token->plainTextToken,
                'Accept' => 'application/json',
            ],
        ];
    }

    protected function createAdminWithToken(): array
    {
        return $this->createUserWithToken(
            ['is_admin' => true],
            ['rename-host']
        );
    }
}
