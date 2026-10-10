<?php

namespace Functional\Portal\Tests\Feature\Auth;

use Functional\Portal\Livewire\Auth\VerifyEmailNotice;
use Functional\Portal\Notifications\VerifyCustomerEmail;
use Functional\Portal\Tests\Concerns\BuildsPortalFixtures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use BuildsPortalFixtures, RefreshDatabase;

    /**
     * @return iterable<string, array{string}>
     */
    public static function protectedRoutes(): iterable
    {
        yield 'recherche' => ['portal.search'];
        yield 'demandes' => ['portal.requests'];
        yield 'réservations' => ['portal.reservations'];
        yield 'compte' => ['portal.account'];
    }

    #[DataProvider('protectedRoutes')]
    public function test_an_unconfirmed_account_only_reaches_the_confirmation_page(string $routeName): void
    {
        $this->actingAs($this->unverifiedCustomerAccount(), 'customer')
            ->get(route($routeName))
            ->assertRedirect(route('portal.verification.notice'));
    }

    public function test_the_confirmation_page_offers_to_resend_the_link(): void
    {
        Notification::fake();
        $account = $this->unverifiedCustomerAccount();
        $this->actingAs($account, 'customer');

        $this->get(route('portal.verification.notice'))->assertOk()->assertSee(__('portal::auth.verify.resend'));
        Livewire::test(VerifyEmailNotice::class)->call('resend')->assertHasNoErrors();

        Notification::assertSentTo($account, VerifyCustomerEmail::class);
    }

    public function test_the_signed_link_confirms_the_address(): void
    {
        $account = $this->unverifiedCustomerAccount();

        $this->actingAs($account, 'customer')
            ->get($this->verificationUrl($account->id, sha1($account->email)))
            ->assertRedirect(route('portal.search'));

        $this->assertNotNull($account->fresh()?->email_verified_at);
    }

    public function test_a_tampered_or_expired_link_is_refused(): void
    {
        $account = $this->unverifiedCustomerAccount();
        $this->actingAs($account, 'customer');

        $this->get($this->verificationUrl($account->id, sha1('autre@exemple.fr')))->assertForbidden();
        $this->get($this->verificationUrl($account->id, sha1($account->email), minutes: -1))->assertForbidden();

        $this->assertNull($account->fresh()?->email_verified_at);
    }

    public function test_a_confirmed_account_reaches_the_search(): void
    {
        $this->actingAs($this->customerAccount(), 'customer')->get(route('portal.search'))->assertOk();
    }

    private function verificationUrl(int $accountId, string $hash, int $minutes = 60): string
    {
        return URL::temporarySignedRoute('portal.verification.verify', now()->addMinutes($minutes), ['id' => $accountId, 'hash' => $hash]);
    }
}
