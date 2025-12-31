<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'success' => fn () => $request->session()->get('success'),
                'info' => fn () => $request->session()->get('info'),
                'fail' => fn () => $request->session()->get('fail') ?? $request->session()->get('error'),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'siteSettings' => fn () => $this->getSiteSettings(),
        ];
    }

    private function getSiteSettings(): array
    {
        $settings = Setting::getCached();

        return [
            'siteName' => $settings['branding_site_name'] ?? config('app.name'),
            'siteTagline' => $settings['branding_site_tagline'] ?? '',
            'logoLight' => isset($settings['branding_logo_light']) ? "/storage/{$settings['branding_logo_light']}" : null,
            'logoDark' => isset($settings['branding_logo_dark']) ? "/storage/{$settings['branding_logo_dark']}" : null,
            'favicon' => isset($settings['branding_favicon']) ? "/storage/{$settings['branding_favicon']}" : null,
            'theme' => [
                'primaryColor' => $settings['theme_primary_color'] ?? '#3B82F6',
                'secondaryColor' => $settings['theme_secondary_color'] ?? '#6366F1',
                'accentColor' => $settings['theme_accent_color'] ?? '#F59E0B',
                'darkMode' => $settings['theme_dark_mode'] ?? 'system',
                'customCss' => $settings['theme_custom_css'] ?? '',
            ],
            'typography' => [
                'headingFont' => $settings['typography_heading_font'] ?? 'Inter',
                'bodyFont' => $settings['typography_body_font'] ?? 'Inter',
                'codeFont' => $settings['typography_code_font'] ?? 'JetBrains Mono',
                'baseFontSize' => $settings['typography_base_font_size'] ?? 16,
                'lineHeight' => $settings['typography_line_height'] ?? 1.6,
            ],
            'layout' => [
                'sidebarWidth' => $settings['layout_sidebar_width'] ?? 280,
                'contentWidth' => $settings['layout_content_width'] ?? 900,
                'showToc' => $settings['layout_show_toc'] ?? true,
                'tocPosition' => $settings['layout_toc_position'] ?? 'right',
                'showBreadcrumbs' => $settings['layout_show_breadcrumbs'] ?? true,
                'showFooter' => $settings['layout_show_footer'] ?? true,
                'footerText' => $settings['layout_footer_text'] ?? '',
            ],
            'social' => [
                'twitter' => $settings['branding_social_twitter'] ?? '',
                'github' => $settings['branding_social_github'] ?? '',
                'discord' => $settings['branding_social_discord'] ?? '',
                'linkedin' => $settings['branding_social_linkedin'] ?? '',
            ],
            'advanced' => [
                'searchEnabled' => $settings['advanced_search_enabled'] ?? true,
                'codeCopyButton' => $settings['advanced_code_copy_button'] ?? true,
                'codeLineNumbers' => $settings['advanced_code_line_numbers'] ?? true,
                'llmTxtEnabled' => $settings['advanced_llm_txt_enabled'] ?? false,
            ],
        ];
    }
}
