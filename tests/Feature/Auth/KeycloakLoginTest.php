<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

/**
 * Faza 6 — logowanie panelu wyłącznie przez Keycloak (dopasowanie po
 * keycloak_sub, NIGDY po e-mailu — patrz KeycloakController::callback()).
 * Socialite jest fake'owany (Provider::user()), bez realnego Keycloaka.
 *
 * BEZ RefreshDatabase/pełnej migracji: moduły Gateway i Storefront mają
 * NIEZALEŻNIE OD SIEBIE tabelę "events" (osobne bazy MySQL w produkcji —
 * to nigdy nie koliduje), ale `php artisan migrate` łączy migracje ze
 * WSZYSTKICH modułów w jeden przebieg — na współdzielonej sqlite :memory:
 * testów to zderzenie nazw ("table events already exists"), pre-istniejąca
 * luka w testowalności repo (dziś zero testów dotyka bazy), niezwiązana z
 * Fazą 6. Zamiast pełnej migracji: budujemy RĘCZNIE tylko `users` — dokładny
 * odpowiednik dzisiejszego schematu (bazowa migracja + is_admin/sections +
 * Faza 6 keycloak_sub) — wystarczające dla logiki pod testem.
 */
class KeycloakLoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('keycloak_sub')->nullable()->unique();
            $table->string('name');
            $table->string('handle')->nullable()->unique();
            $table->boolean('is_admin')->default(false);
            $table->json('enabled_sections')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    private function fakeKeycloakUser(string $sub, string $email, string $name): void
    {
        $socialiteUser = SocialiteUser::fake([
            'id' => $sub,
            'name' => $name,
            'email' => $email,
        ]);

        $driver = Mockery::mock();
        $driver->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('keycloak')->andReturn($driver);
    }

    public function test_new_identity_creates_storefront_account(): void
    {
        $this->fakeKeycloakUser('sub-new', 'nowy@example.com', 'Nowy Użytkownik');

        $response = $this->get('http://localhost/panel/auth/callback');

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'nowy@example.com',
            'keycloak_sub' => 'sub-new',
        ]);
        $this->assertAuthenticated();
    }

    public function test_same_keycloak_sub_reuses_existing_account_without_duplicate(): void
    {
        $existing = User::factory()->create(['keycloak_sub' => 'sub-existing', 'email' => 'stary@example.com']);

        $this->fakeKeycloakUser('sub-existing', 'stary@example.com', 'Stary Użytkownik');

        $this->get('http://localhost/panel/auth/callback');

        $this->assertAuthenticatedAs($existing);
        $this->assertSame(1, User::where('keycloak_sub', 'sub-existing')->count());
    }

    /**
     * Test regresyjny na decyzję o BRAKU auto-linkowania po e-mailu (realm ma
     * verifyEmail:false — auto-link po e-mailu byłby przejęciem konta).
     * `email` w users jest unique, więc "osobne konto" nie jest tu opcją —
     * czysta odmowa (409), zero nowego/zmodyfikowanego konta, NIGDY ciche
     * zalogowanie w konto $existing.
     */
    public function test_matching_email_without_keycloak_sub_is_rejected_not_auto_linked(): void
    {
        $existing = User::factory()->create(['keycloak_sub' => null, 'email' => 'takisam@example.com']);

        $this->fakeKeycloakUser('sub-other', 'takisam@example.com', 'Ktoś Inny');

        $response = $this->get('http://localhost/panel/auth/callback');

        $response->assertStatus(409);
        $this->assertGuest();
        $this->assertSame(1, User::where('email', 'takisam@example.com')->count());
        $this->assertNull($existing->fresh()->keycloak_sub);
    }

    public function test_gateway_rejects_unmatched_identity_without_creating_account(): void
    {
        $this->fakeKeycloakUser('sub-unauthorized', 'ktos@example.com', 'Ktoś');

        $response = $this->get('http://pay.please-support-me.com/panel/auth/callback');

        $response->assertForbidden();
        $this->assertDatabaseMissing('users', ['keycloak_sub' => 'sub-unauthorized']);
    }

    public function test_gateway_accepts_identity_already_matched_by_keycloak_sub(): void
    {
        $existing = User::factory()->create(['keycloak_sub' => 'sub-gateway-admin', 'email' => 'admin@example.com']);

        $this->fakeKeycloakUser('sub-gateway-admin', 'admin@example.com', 'Admin Bramki');

        $response = $this->get('http://pay.please-support-me.com/panel/auth/callback');

        $response->assertRedirect();
        $this->assertAuthenticatedAs($existing);
    }
}
