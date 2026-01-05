<?php

namespace App\Providers;

use App\Http\Requests\LoginRequest;
use App\Models\SystemConfig;
use App\Models\User;
use App\Turnstile;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \Laravel\Fortify\Http\Requests\LoginRequest::class,
            LoginRequest::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureViews();
        $this->configureRateLimiting();
        $this->configureTurnstileValidator();
        $this->configureSingleSessionPerUser();
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(function (Request $request) {
            $config = SystemConfig::instance();

            return Inertia::render('auth/Login', [
                'canResetPassword' => Features::enabled(Features::resetPasswords()),
                'status' => $request->hasSession() ? $request->session()->get('status') : null,
                'turnstileSiteKey' => $config->turnstile_site_key,
            ]);
        });

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/TwoFactorChallenge'));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/ConfirmPassword'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            $loginId = $request->hasSession() ? $request->session()->get('login.id') : null;

            return Limit::perMinute(5)->by($loginId);
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }

    /**
     * Configure Turnstile validator.
     */
    private function configureTurnstileValidator(): void
    {
        $config = SystemConfig::instance();

        if (! empty($config->turnstile_secret_key)) {
            Validator::extend('turnstile', function ($attribute, $value, $parameters, $validator) {
                $turnstile = new Turnstile;
                $validationResult = $turnstile->validate($value);

                return $validationResult['status'] === 1;
            }, 'El token de captcha no es válido.');
        }
    }

    /**
     * Configure single session per user.
     * Destroys all other sessions when a user logs in.
     */
    private function configureSingleSessionPerUser(): void
    {
        Event::listen(Authenticated::class, function (Authenticated $event) {
            if ($event->guard === 'web' && $event->user instanceof User) {
                $request = request();

                if (! $request->hasSession()) {
                    return;
                }

                $currentSessionId = $request->session()->getId();

                // Mark all other sessions as terminated before deleting
                DB::table(config('session.table', 'sessions'))
                    ->where('user_id', $event->user->id)
                    ->where('id', '!=', $currentSessionId)
                    ->update(['terminated' => true]);
            }
        });
    }
}
