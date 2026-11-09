<?php

namespace Tests;

use App\Models\Anggota;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Auth;

abstract class TestCase extends BaseTestCase
{
    protected function memberSession(array $session = []): static
    {
        if (($session['member.authenticated'] ?? true) === false) {
            Auth::logout();

            return $this->withSession($session);
        }
        if (! auth()->check()) {
            $user = User::factory()->create(['name' => 'Anggota Demo', 'must_change_password' => false]);
            Anggota::factory()->create(['user_id' => $user->id]);
            $this->actingAs($user);
        }
        unset($session['member.authenticated']);

        return $this->withSession($session);
    }
}
