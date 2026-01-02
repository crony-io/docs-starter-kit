<?php

use App\Http\Middleware\DetectSessionTermination;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\LogUserActivity;
use App\Http\Middleware\SetupMiddleware;
use App\Models\GitSync;
use App\Models\SystemConfig;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Spatie\Csp\AddCspHeaders;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        App\Providers\EventServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->validateCsrfTokens(except: [
            'webhook/*',
        ]);

        $middleware->web(append: [
            AddCspHeaders::class,
            HandleAppearance::class,
            SetupMiddleware::class,
            DetectSessionTermination::class,
            LogUserActivity::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

    })
    ->withSchedule(function (Schedule $schedule) {
        // Git sync (only if in Git mode)
        $schedule->command('docs:sync')
            ->everyMinute()
            ->when(function () {
                if (! config('docs.git_enabled', true)) {
                    return false;
                }

                if (! SystemConfig::isGitMode()) {
                    return false;
                }

                $config = SystemConfig::instance();

                if (! $config->git_repository_url) {
                    return false;
                }

                $frequency = (int) ($config->git_sync_frequency ?? 15);

                if ($frequency < 1) {
                    $frequency = 15;
                }

                if (! $config->last_synced_at) {
                    return true;
                }

                return $config->last_synced_at->diffInMinutes(now()) >= $frequency;
            })
            ->withoutOverlapping()
            ->runInBackground()
            ->onFailure(function () {
                Log::error('Scheduled git sync failed');
            })
            ->onSuccess(function () {
                Log::info('Scheduled git sync completed');
            });

        // Generate LLM files daily
        $schedule->command('docs:generate-llm')
            ->daily()
            ->at('02:00')
            ->withoutOverlapping();

        // Clean old git syncs (keep last 100)
        $schedule->call(function () {
            GitSync::orderBy('created_at', 'desc')
                ->skip(100)
                ->delete();
        })->weekly()->sundays()->at('03:00');

        // Anonymize old IP addresses for GDPR compliance (runs daily at 3:30 AM)
        $schedule->command('privacy:anonymize-ips --days=30')
            ->daily()
            ->at('03:30')
            ->withoutOverlapping()
            ->onSuccess(function () {
                Log::info('Scheduled IP anonymization completed');
            })
            ->onFailure(function () {
                Log::error('Scheduled IP anonymization failed');
            });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            $handledErrorCodes = [
                400,
                401,
                403,
                404,
                405,
                408,
                413,
                422,
                429,
                500,
                502,
                503,
                504,
            ];

            // Check if we're not in local/testing environment and if the status code is one we want to handle
            if (! app()->environment(['local', 'testing']) && in_array($response->getStatusCode(), $handledErrorCodes)) {
                $message = null;
                if ($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                    $rawMessage = $exception->getMessage();
                    if (! empty($rawMessage)) {
                        // Sanitizar mensaje
                        $message = htmlspecialchars($rawMessage, ENT_QUOTES, 'UTF-8');
                        // Solo usar mensajes seguros conocidos
                        $safeMessages = ['not found', 'forbidden', 'unauthorized', 'method not allowed'];
                        if (! in_array(strtolower($message), $safeMessages)) {
                            $message = null;
                        }
                    }
                }

                return Inertia::render('Error', [
                    'status' => $response->getStatusCode(),
                    'message' => $message,
                ])
                    ->toResponse($request)
                    ->setStatusCode($response->getStatusCode());
            }
            // Special handling for CSRF token expiration (419)
            elseif ($response->getStatusCode() === 419) {
                return back()->with([
                    'message' => 'The page expired, please try again.',
                ]);
            }

            return $response;
        });
    })->create();
