<?php

namespace Tests\Feature;

use App\Http\Controllers\HealthController;
use App\Mail\ErrorAlertMail;
use App\Support\ErrorAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\Support\UsesDemoCompany;
use Tests\TestCase;

/** Bewaking: /health met planner-hartslag en de gedoseerde alarmmail bij fouten. */
class HealthAndAlertsTest extends TestCase
{
    use RefreshDatabase, UsesDemoCompany;

    public function test_health_is_degraded_without_a_scheduler_heartbeat_and_ok_with_one(): void
    {
        Cache::forget(HealthController::HEARTBEAT_KEY);
        $this->getJson('/health')->assertStatus(503)->assertJsonPath('checks.scheduler.ok', false)->assertJsonPath('checks.database.ok', true);

        Cache::forever(HealthController::HEARTBEAT_KEY, now()->subMinutes(3)->timestamp);
        $this->getJson('/health')->assertOk()->assertJsonPath('status', 'ok')->assertJsonPath('checks.scheduler.age_minutes', 3);

        Cache::forever(HealthController::HEARTBEAT_KEY, now()->subMinutes(40)->timestamp);
        $this->getJson('/health')->assertStatus(503)->assertJsonPath('status', 'degraded');
    }

    public function test_unexpected_errors_are_mailed_to_the_owner_once_per_hour(): void
    {
        Mail::fake();
        config(['app.error_alerts_in_tests' => true, 'services.marketing_stats.emails' => 'eigenaar@easyinvoice.nl']);
        Cache::flush();

        $boom = new \RuntimeException('Kolom bestaat niet');
        ErrorAlert::report($boom);
        ErrorAlert::report($boom);                       // zelfde fout binnen het uur → geen tweede mail
        ErrorAlert::report(new \RuntimeException('Iets anders'));

        Mail::assertSent(ErrorAlertMail::class, 2);
        Mail::assertSent(ErrorAlertMail::class, fn ($mail) => $mail->hasTo('eigenaar@easyinvoice.nl') && str_contains($mail->exception->getMessage(), 'Kolom'));
    }

    public function test_expected_errors_are_not_mailed(): void
    {
        Mail::fake();
        config(['app.error_alerts_in_tests' => true, 'services.marketing_stats.emails' => 'eigenaar@easyinvoice.nl']);

        ErrorAlert::report(new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('Pagina weg'));
        ErrorAlert::report(new \Illuminate\Auth\AuthenticationException());
        ErrorAlert::report(\Illuminate\Validation\ValidationException::withMessages(['email' => 'Ongeldig']));

        Mail::assertNothingSent();
    }
}
