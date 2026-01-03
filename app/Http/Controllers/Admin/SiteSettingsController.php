<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\SystemConfig;
use App\Services\WebCronService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SiteSettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/settings/Index', [
            'settings' => $this->getAllSettings(),
            'groups' => $this->getSettingGroups(),
        ]);
    }

    public function theme(): Response
    {
        return Inertia::render('admin/settings/Theme', [
            'settings' => Setting::getByGroup('theme'),
            'defaults' => $this->getThemeDefaults(),
        ]);
    }

    public function updateTheme(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'primary_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'background_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'dark_mode' => ['required', 'string', 'in:light,dark,system'],
            'custom_css' => ['nullable', 'string', 'max:50000'],
        ]);

        foreach ($validated as $key => $value) {
            Setting::set("theme_{$key}", $value, 'theme');
        }

        return back()->with('success', 'Theme settings updated.');
    }

    public function typography(): Response
    {
        return Inertia::render('admin/settings/Typography', [
            'settings' => Setting::getByGroup('typography'),
            'defaults' => $this->getTypographyDefaults(),
            'googleFonts' => $this->getGoogleFonts(),
        ]);
    }

    public function updateTypography(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'heading_font' => ['required', 'string', 'max:100'],
            'body_font' => ['required', 'string', 'max:100'],
            'code_font' => ['required', 'string', 'max:100'],
            'base_font_size' => ['required', 'integer', 'min:12', 'max:24'],
            'heading_scale' => ['required', 'numeric', 'min:1.1', 'max:1.5'],
            'line_height' => ['required', 'numeric', 'min:1.2', 'max:2.0'],
            'paragraph_spacing' => ['required', 'numeric', 'min:0.5', 'max:3.0'],
        ]);

        foreach ($validated as $key => $value) {
            Setting::set("typography_{$key}", $value, 'typography');
        }

        return back()->with('success', 'Typography settings updated.');
    }

    public function layout(): Response
    {
        return Inertia::render('admin/settings/Layout', [
            'settings' => Setting::getByGroup('layout'),
            'defaults' => $this->getLayoutDefaults(),
        ]);
    }

    public function updateLayout(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sidebar_width' => ['required', 'integer', 'min:200', 'max:400'],
            'content_width' => ['required', 'integer', 'min:600', 'max:1400'],
            'navigation_style' => ['required', 'string', 'in:sidebar,topnav,both'],
            'show_toc' => ['required', 'boolean'],
            'toc_position' => ['required', 'string', 'in:left,right'],
            'show_breadcrumbs' => ['required', 'boolean'],
            'show_footer' => ['required', 'boolean'],
            'footer_text' => ['nullable', 'string', 'max:500'],
        ]);

        foreach ($validated as $key => $value) {
            Setting::set("layout_{$key}", $value, 'layout');
        }

        return back()->with('success', 'Layout settings updated.');
    }

    public function branding(): Response
    {
        return Inertia::render('admin/settings/Branding', [
            'settings' => Setting::getByGroup('branding'),
            'defaults' => $this->getBrandingDefaults(),
        ]);
    }

    public function updateBranding(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:100'],
            'site_tagline' => ['nullable', 'string', 'max:200'],
            'logo_light' => ['nullable', 'image', 'max:2048'],
            'logo_dark' => ['nullable', 'image', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:ico,png,svg', 'max:512'],
            'social_twitter' => ['nullable', 'url', 'max:255'],
            'social_github' => ['nullable', 'url', 'max:255'],
            'social_discord' => ['nullable', 'url', 'max:255'],
            'social_linkedin' => ['nullable', 'url', 'max:255'],
        ]);

        if ($request->hasFile('logo_light')) {
            $path = $request->file('logo_light')->store('branding', 'public');
            Setting::set('branding_logo_light', $path, 'branding');
        }

        if ($request->hasFile('logo_dark')) {
            $path = $request->file('logo_dark')->store('branding', 'public');
            Setting::set('branding_logo_dark', $path, 'branding');
        }

        if ($request->hasFile('favicon')) {
            $path = $request->file('favicon')->store('branding', 'public');
            Setting::set('branding_favicon', $path, 'branding');
        }

        $textSettings = ['site_name', 'site_tagline', 'social_twitter', 'social_github', 'social_discord', 'social_linkedin'];
        foreach ($textSettings as $key) {
            if (isset($validated[$key])) {
                Setting::set("branding_{$key}", $validated[$key], 'branding');
            }
        }

        return back()->with('success', 'Branding settings updated.');
    }

    public function advanced(WebCronService $webCronService): Response
    {
        $config = SystemConfig::instance();

        return Inertia::render('admin/settings/Advanced', [
            'settings' => Setting::getByGroup('advanced'),
            'defaults' => $this->getAdvancedDefaults(),
            'webCron' => [
                'web_cron_enabled' => $config->web_cron_enabled,
                'last_web_cron_at' => $config->last_web_cron_at?->toIso8601String(),
            ],
            'serverCheck' => $webCronService->getServerCompatibility(),
        ]);
    }

    public function updateAdvanced(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'analytics_ga4_id' => ['nullable', 'string', 'max:50'],
            'analytics_plausible_domain' => ['nullable', 'string', 'max:255'],
            'analytics_clarity_id' => ['nullable', 'string', 'max:50'],
            'search_enabled' => ['required', 'boolean'],
            'search_provider' => ['required', 'string', 'in:local,meilisearch,algolia'],
            'llm_txt_enabled' => ['required', 'boolean'],
            'llm_txt_include_drafts' => ['required', 'boolean'],
            'llm_txt_max_tokens' => ['required', 'integer', 'min:1000', 'max:1000000'],
            'meta_robots' => ['required', 'string', 'in:index,noindex'],
            'code_copy_button' => ['required', 'boolean'],
            'code_line_numbers' => ['required', 'boolean'],
            'web_cron_enabled' => ['sometimes', 'boolean'],
        ]);

        // Handle web_cron_enabled separately (stored in SystemConfig)
        if (array_key_exists('web_cron_enabled', $validated)) {
            $config = SystemConfig::instance();
            $config->update(['web_cron_enabled' => $validated['web_cron_enabled']]);
            Cache::forget('system_config');
            unset($validated['web_cron_enabled']);
        }

        foreach ($validated as $key => $value) {
            Setting::set("advanced_{$key}", $value, 'advanced');
        }

        return back()->with('success', 'Advanced settings updated.');
    }

    public function deleteLogo(Request $request): RedirectResponse
    {
        $type = $request->input('type');
        $key = "branding_logo_{$type}";

        $currentPath = Setting::get($key);
        if ($currentPath) {
            Storage::disk('public')->delete($currentPath);
            Setting::set($key, null, 'branding');
        }

        return back()->with('success', 'Logo deleted.');
    }

    private function getAllSettings(): array
    {
        return Setting::getCached();
    }

    private function getSettingGroups(): array
    {
        return [
            ['key' => 'theme', 'label' => 'Theme', 'description' => 'Colors, dark mode, custom CSS'],
            ['key' => 'typography', 'label' => 'Typography', 'description' => 'Fonts, sizes, spacing'],
            ['key' => 'layout', 'label' => 'Layout', 'description' => 'Widths, navigation, footer'],
            ['key' => 'branding', 'label' => 'Branding', 'description' => 'Logo, favicon, site name'],
            ['key' => 'advanced', 'label' => 'Advanced', 'description' => 'Analytics, search, LLM'],
        ];
    }

    private function getThemeDefaults(): array
    {
        return [
            'primary_color' => '#3B82F6',
            'secondary_color' => '#6366F1',
            'accent_color' => '#F59E0B',
            'background_color' => '#FFFFFF',
            'text_color' => '#1F2937',
            'dark_mode' => 'system',
            'custom_css' => '',
        ];
    }

    private function getTypographyDefaults(): array
    {
        return [
            'heading_font' => 'Inter',
            'body_font' => 'Inter',
            'code_font' => 'JetBrains Mono',
            'base_font_size' => 16,
            'heading_scale' => 1.25,
            'line_height' => 1.6,
            'paragraph_spacing' => 1.5,
        ];
    }

    private function getLayoutDefaults(): array
    {
        return [
            'sidebar_width' => 280,
            'content_width' => 900,
            'navigation_style' => 'sidebar',
            'show_toc' => true,
            'toc_position' => 'right',
            'show_breadcrumbs' => true,
            'show_footer' => true,
            'footer_text' => '',
        ];
    }

    private function getBrandingDefaults(): array
    {
        return [
            'site_name' => config('app.name', 'Documentation'),
            'site_tagline' => '',
            'logo_light' => null,
            'logo_dark' => null,
            'favicon' => null,
            'social_twitter' => '',
            'social_github' => '',
            'social_discord' => '',
            'social_linkedin' => '',
        ];
    }

    private function getAdvancedDefaults(): array
    {
        return [
            'analytics_ga4_id' => '',
            'analytics_plausible_domain' => '',
            'analytics_clarity_id' => '',
            'search_enabled' => true,
            'search_provider' => 'local',
            'llm_txt_enabled' => true,
            'llm_txt_include_drafts' => false,
            'llm_txt_max_tokens' => 100000,
            'meta_robots' => 'index',
            'code_copy_button' => true,
            'code_line_numbers' => true,
        ];
    }

    private function getGoogleFonts(): array
    {
        return [
            ['value' => 'Inter', 'label' => 'Inter'],
            ['value' => 'Roboto', 'label' => 'Roboto'],
            ['value' => 'Open Sans', 'label' => 'Open Sans'],
            ['value' => 'Lato', 'label' => 'Lato'],
            ['value' => 'Poppins', 'label' => 'Poppins'],
            ['value' => 'Nunito', 'label' => 'Nunito'],
            ['value' => 'Source Sans Pro', 'label' => 'Source Sans Pro'],
            ['value' => 'Montserrat', 'label' => 'Montserrat'],
            ['value' => 'Raleway', 'label' => 'Raleway'],
            ['value' => 'Merriweather', 'label' => 'Merriweather'],
            ['value' => 'Playfair Display', 'label' => 'Playfair Display'],
            ['value' => 'JetBrains Mono', 'label' => 'JetBrains Mono'],
            ['value' => 'Fira Code', 'label' => 'Fira Code'],
            ['value' => 'Source Code Pro', 'label' => 'Source Code Pro'],
        ];
    }
}
