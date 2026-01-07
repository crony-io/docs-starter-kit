<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Branding
            ['key' => 'branding_site_name', 'value' => 'Docs Starter Kit', 'group' => 'branding'],
            ['key' => 'branding_site_tagline', 'value' => 'Modern documentation made simple', 'group' => 'branding'],
            ['key' => 'branding_social_twitter', 'value' => '', 'group' => 'branding'],
            ['key' => 'branding_social_github', 'value' => 'https://github.com/crony-io/docs-starter-kit', 'group' => 'branding'],
            ['key' => 'branding_social_discord', 'value' => '', 'group' => 'branding'],
            ['key' => 'branding_social_linkedin', 'value' => '', 'group' => 'branding'],

            // Theme
            ['key' => 'theme_primary_color', 'value' => '#3B82F6', 'group' => 'theme'],
            ['key' => 'theme_secondary_color', 'value' => '#6366F1', 'group' => 'theme'],
            ['key' => 'theme_accent_color', 'value' => '#F59E0B', 'group' => 'theme'],
            ['key' => 'theme_background_color', 'value' => '#FFFFFF', 'group' => 'theme'],
            ['key' => 'theme_text_color', 'value' => '#1F2937', 'group' => 'theme'],
            ['key' => 'theme_dark_mode', 'value' => 'system', 'group' => 'theme'],
            ['key' => 'theme_custom_css', 'value' => '', 'group' => 'theme'],

            // Typography
            ['key' => 'typography_heading_font', 'value' => 'Inter', 'group' => 'typography'],
            ['key' => 'typography_body_font', 'value' => 'Inter', 'group' => 'typography'],
            ['key' => 'typography_code_font', 'value' => 'JetBrains Mono', 'group' => 'typography'],
            ['key' => 'typography_base_font_size', 'value' => 16, 'group' => 'typography'],
            ['key' => 'typography_heading_scale', 'value' => 1.25, 'group' => 'typography'],
            ['key' => 'typography_line_height', 'value' => 1.6, 'group' => 'typography'],
            ['key' => 'typography_paragraph_spacing', 'value' => 1.5, 'group' => 'typography'],

            // Layout
            ['key' => 'layout_sidebar_width', 'value' => 280, 'group' => 'layout'],
            ['key' => 'layout_content_width', 'value' => 1400, 'group' => 'layout'],
            ['key' => 'layout_navigation_style', 'value' => 'both', 'group' => 'layout'],
            ['key' => 'layout_show_toc', 'value' => true, 'group' => 'layout'],
            ['key' => 'layout_toc_position', 'value' => 'right', 'group' => 'layout'],
            ['key' => 'layout_show_breadcrumbs', 'value' => true, 'group' => 'layout'],
            ['key' => 'layout_show_footer', 'value' => true, 'group' => 'layout'],
            ['key' => 'layout_footer_text', 'value' => '© '.date('Y').' Docs Starter Kit. All rights reserved.', 'group' => 'layout'],

            // Advanced
            ['key' => 'advanced_analytics_ga4_id', 'value' => '', 'group' => 'advanced'],
            ['key' => 'advanced_analytics_plausible_domain', 'value' => '', 'group' => 'advanced'],
            ['key' => 'advanced_search_enabled', 'value' => true, 'group' => 'advanced'],
            ['key' => 'advanced_search_provider', 'value' => 'local', 'group' => 'advanced'],
            ['key' => 'advanced_llm_txt_enabled', 'value' => true, 'group' => 'advanced'],
            ['key' => 'advanced_llm_txt_include_drafts', 'value' => false, 'group' => 'advanced'],
            ['key' => 'advanced_llm_txt_max_tokens', 'value' => 100000, 'group' => 'advanced'],
            ['key' => 'advanced_meta_robots', 'value' => 'index', 'group' => 'advanced'],
            ['key' => 'advanced_code_copy_button', 'value' => true, 'group' => 'advanced'],
            ['key' => 'advanced_code_line_numbers', 'value' => false, 'group' => 'advanced'],

            // General (legacy support)
            ['key' => 'feedback_enabled', 'value' => true, 'group' => 'general'],
            ['key' => 'show_edit_on_github', 'value' => false, 'group' => 'general'],
        ];

        foreach ($settings as $setting) {
            Setting::create($setting);
        }
    }
}
