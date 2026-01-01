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
                'backgroundColor' => $settings['theme_background_color'] ?? '#FFFFFF',
                'textColor' => $settings['theme_text_color'] ?? '#1F2937',
                'darkMode' => $settings['theme_dark_mode'] ?? 'system',
                'customCss' => $settings['theme_custom_css'] ?? '',
            ],
            'typography' => [
                'headingFont' => $settings['typography_heading_font'] ?? 'Inter',
                'bodyFont' => $settings['typography_body_font'] ?? 'Inter',
                'codeFont' => $settings['typography_code_font'] ?? 'JetBrains Mono',
                'baseFontSize' => (int) ($settings['typography_base_font_size'] ?? 16),
                'headingScale' => (float) ($settings['typography_heading_scale'] ?? 1.25),
                'lineHeight' => (float) ($settings['typography_line_height'] ?? 1.6),
                'paragraphSpacing' => (float) ($settings['typography_paragraph_spacing'] ?? 1.5),
            ],
            'layout' => [
                'sidebarWidth' => (int) ($settings['layout_sidebar_width'] ?? 280),
                'contentWidth' => (int) ($settings['layout_content_width'] ?? 900),
                'navigationStyle' => $settings['layout_navigation_style'] ?? 'sidebar',
                'showToc' => $this->toBool($settings['layout_show_toc'] ?? false),
                'tocPosition' => $settings['layout_toc_position'] ?? 'right',
                'showBreadcrumbs' => $this->toBool($settings['layout_show_breadcrumbs'] ?? false),
                'showFooter' => $this->toBool($settings['layout_show_footer'] ?? false),
                'footerText' => $settings['layout_footer_text'] ?? '',
            ],
            'social' => [
                'twitter' => $settings['branding_social_twitter'] ?? '',
                'github' => $settings['branding_social_github'] ?? '',
                'discord' => $settings['branding_social_discord'] ?? '',
                'linkedin' => $settings['branding_social_linkedin'] ?? '',
            ],
            'advanced' => [
                'searchEnabled' => $this->toBool($settings['advanced_search_enabled'] ?? false),
                'codeCopyButton' => $this->toBool($settings['advanced_code_copy_button'] ?? false),
                'codeLineNumbers' => $this->toBool($settings['advanced_code_line_numbers'] ?? false),
                'llmTxtEnabled' => $this->toBool($settings['advanced_llm_txt_enabled'] ?? false),
                'metaRobots' => $settings['advanced_meta_robots'] ?? 'index, follow',
            ],
        ];
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['true', '1', 'yes', 'on'], true);
        }

        return (bool) $value;
    }
}
