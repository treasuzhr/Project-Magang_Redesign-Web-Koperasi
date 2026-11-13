<?php

namespace Tests\Feature;

use App\Models\Anggota;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginCaptchaTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_displays_a_six_character_alphanumeric_captcha(): void
    {
        $response = $this->get('/login');
        $captchaCode = session('captcha_answer');

        $response->assertOk()->assertSee('NIP/NRP')->assertSee('id="captchaCode"', false)->assertSee(route('login.captcha', ['image' => 1]), false);
        $this->assertMatchesRegularExpression('/\A[A-HJ-NP-Z2-9]{6}\z/', $captchaCode);
    }

    public function test_refresh_returns_and_stores_a_new_alphanumeric_captcha(): void
    {
        $response = $this->getJson('/login/captcha');
        $captchaCode = $response->json('code');

        $response->assertOk()->assertJsonStructure(['code']);
        $this->assertMatchesRegularExpression('/\A[A-HJ-NP-Z2-9]{6}\z/', $captchaCode);
        $this->assertSame($captchaCode, session('captcha_answer'));
    }

    public function test_login_rejects_an_incorrect_captcha(): void
    {
        $this->withSession(['captcha_answer' => 'AB2CDE'])
            ->from('/login')
            ->post('/login', [
                'member_id' => 'TEST-NRP',
                'password' => 'secret',
                'captcha' => 'ZZ9XYZ',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('captcha')
            ->assertSessionMissing('member.authenticated');
    }

    public function test_login_accepts_the_captcha_without_case_sensitivity(): void
    {
        $member = Anggota::factory()->create();
        $member->user->update(['must_change_password' => false]);
        $this->withSession(['captcha_answer' => 'AB2CDE'])
            ->post('/login', [
                'member_id' => $member->user->nip,
                'password' => 'password',
                'captcha' => 'ab2cde',
            ])
            ->assertRedirect('/anggota')
            ->assertSessionMissing('captcha_answer');
    }
}
