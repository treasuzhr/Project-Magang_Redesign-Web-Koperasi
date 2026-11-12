<?php

namespace Tests\Feature;

use App\Checkout;
use App\MemberOverview;
use App\Models\Anggota;
use App\Models\Pesanan;
use App\Models\PesananItem;
use App\Models\Produk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private Anggota $member;

    private Produk $first;

    private Produk $second;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->member = Anggota::factory()->create(['user_id' => User::factory()->create(['must_change_password' => false])->id]);
        $this->first = Produk::create(['nama' => 'Produk A', 'harga' => 7000, 'aktif' => true]);
        $this->second = Produk::create(['nama' => 'Produk B', 'harga' => 2000, 'aktif' => true]);
        $this->token = str_repeat('a', 40);
        $this->actingAs($this->member->user)->withSession(['checkout.token' => $this->token]);
    }

    private function payload(): array
    {
        return ['checkout_token' => $this->token, 'items' => [['id' => $this->first->id, 'qty' => 2, 'price' => 1], ['id' => $this->second->id, 'qty' => 3]], 'total' => 1, 'catatan' => 'Ambil sendiri'];
    }

    public function test_valid_checkout_snapshots_server_prices_owner_and_null_payment_without_other_dashboard_changes(): void
    {
        $overview = app(MemberOverview::class);
        $before = $overview->loanSummary($this->member);
        $savings = $overview->savings($this->member);
        $other = Anggota::factory()->create();
        $data = $this->payload();
        $data['anggota_id'] = $other->id;
        $data['status'] = 'selesai';
        $response = $this->post('/checkout', $data)->assertRedirect();
        $order = Pesanan::sole();
        $this->assertSame(20000, $order->total);
        $this->assertSame($this->member->id, $order->anggota_id);
        $this->assertSame('baru', $order->status);
        $this->assertNull($order->metode_bayar);
        $this->assertSame('Ambil sendiri', $order->catatan);
        $this->assertSame(2, $order->items()->count());
        $this->assertSame([7000, 2000], $order->items()->orderBy('id')->pluck('harga')->all());
        $this->assertSame(20000, (int) $order->items()->sum('subtotal'));
        $this->assertSame($before, $overview->loanSummary($this->member));
        $this->assertSame($savings, $overview->savings($this->member));
        $response->assertRedirect(route('anggota.pesanan.detail', ['id' => $order->id]));
        $this->get($response->headers->get('Location'))->assertOk()->assertSee('Pesanan berhasil dibuat.')->assertSee('data-checkout-success', false)->assertSee('Rp 20.000');
        $this->get('/anggota/pesanan')->assertOk()->assertSee('Baru')->assertSee('Rp 20.000');
        $this->first->update(['nama' => 'Nama baru', 'harga' => 9000]);
        $this->assertSame('Produk A', $order->items()->orderBy('id')->first()->nama);
    }

    public function test_repeated_post_with_same_token_returns_the_original_order(): void
    {
        $one = $this->post('/checkout', $this->payload())->assertRedirect();
        $two = $this->post('/checkout', $this->payload())->assertRedirect();
        $this->assertSame($one->headers->get('Location'), $two->headers->get('Location'));
        $this->assertDatabaseCount('pesanans', 1);
        $this->assertDatabaseCount('pesanan_items', 2);
    }

    public function test_nonexistent_or_inactive_products_and_invalid_quantities_are_rejected(): void
    {
        $this->second->update(['aktif' => false]);
        $this->post('/checkout', $this->payload())->assertSessionHasErrors('items');
        $data = $this->payload();
        $data['items'] = [['id' => 999999, 'qty' => 1]];
        $this->post('/checkout', $data)->assertSessionHasErrors('items');
        foreach ([0, -1, 100, 1.5, 'abc'] as $qty) {
            $data['items'] = [['id' => $this->first->id, 'qty' => $qty]];
            $this->post('/checkout', $data)->assertSessionHasErrors('items.0.qty');
        }
        $this->assertDatabaseCount('pesanans', 0);
    }

    public function test_transaction_rolls_back_if_an_item_insert_fails(): void
    {
        $counter = 0;
        PesananItem::creating(function () use (&$counter) {
            if (++$counter === 2) {
                throw new \RuntimeException('Simulated item insert failure');
            }
        });
        try {
            app(Checkout::class)->place($this->member, $this->payload()['items'], null);
            $this->fail('Expected the second item to fail');
        } catch (\RuntimeException $e) {
            $this->assertSame('Simulated item insert failure', $e->getMessage());
        } finally {
            PesananItem::flushEventListeners();
        }
        $this->assertDatabaseCount('pesanans', 0);
        $this->assertDatabaseCount('pesanan_items', 0);
    }

    public function test_guest_login_returns_to_cart_and_admin_and_inactive_members_are_denied(): void
    {
        auth()->logout();
        $this->get('/checkout')->assertRedirect('/login')->assertSessionHas('checkout.return_to_cart', true);
        $this->post('/checkout', $this->payload())->assertRedirect('/login');
        $user = $this->member->user;
        $user->forceFill(['password' => Hash::make('PasswordTesting123')])->save();
        $this->withSession(['captcha_answer' => 'ABC123'])->post('/login', ['member_id' => $user->nip, 'password' => 'PasswordTesting123', 'captcha' => 'ABC123'])->assertRedirect('/keranjang');
        $this->actingAs(User::factory()->create(['role' => 'admin', 'must_change_password' => false]))->get('/checkout')->assertForbidden();
        $this->post('/checkout', $this->payload())->assertForbidden();
        $this->member->update(['status' => 'pensiun']);
        $this->actingAs($user)->post('/checkout', $this->payload())->assertRedirect('/login');
        $this->assertDatabaseCount('pesanans', 0);
    }

    public function test_checkout_page_has_csrf_collection_hours_and_neutral_payment_notice(): void
    {
        $this->get('/checkout')->assertOk()->assertSee('name="_token"', false)->assertSee('07:30 sampai 15:00')
            ->assertSee(Checkout::PAYMENT_NOTICE)->assertDontSee('name="alamat"', false)->assertDontSee('Ongkos kirim');
    }

    public function test_checkout_is_throttled_after_ten_attempts(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/checkout', [])->assertUnprocessable();
        }
        $this->postJson('/checkout',[])->assertStatus(429);
        $this->assertDatabaseCount('pesanans',0);
    }
}
