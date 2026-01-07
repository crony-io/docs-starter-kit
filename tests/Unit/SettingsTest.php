<?php

namespace Tests\Unit;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_returns_setting_value()
    {
        Setting::create([
            'key' => 'site_name',
            'value' => 'My Documentation',
            'group' => 'general',
        ]);

        $value = Setting::get('site_name');

        $this->assertEquals('My Documentation', $value);
    }

    public function test_get_returns_default_when_setting_not_found()
    {
        $value = Setting::get('non_existent', 'default_value');

        $this->assertEquals('default_value', $value);
    }

    public function test_get_returns_null_when_no_default_provided()
    {
        $value = Setting::get('non_existent');

        $this->assertNull($value);
    }

    public function test_set_creates_new_setting()
    {
        Setting::set('new_key', 'new_value', 'custom');

        $this->assertDatabaseHas('settings', [
            'key' => 'new_key',
            'group' => 'custom',
        ]);

        $this->assertEquals('new_value', Setting::get('new_key'));
    }

    public function test_set_updates_existing_setting()
    {
        Setting::set('my_key', 'original');
        Setting::set('my_key', 'updated');

        $this->assertEquals('updated', Setting::get('my_key'));
        $this->assertDatabaseCount('settings', 1);
    }

    public function test_get_by_group_returns_settings_in_group()
    {
        Setting::set('theme_color', '#000000', 'theme');
        Setting::set('theme_font', 'Arial', 'theme');
        Setting::set('site_name', 'Docs', 'general');

        $themeSettings = Setting::getByGroup('theme');

        $this->assertCount(2, $themeSettings);
        $this->assertArrayHasKey('theme_color', $themeSettings);
        $this->assertArrayHasKey('theme_font', $themeSettings);
        $this->assertArrayNotHasKey('site_name', $themeSettings);
    }

    public function test_get_cached_returns_all_settings()
    {
        Setting::set('key1', 'value1');
        Setting::set('key2', 'value2');

        $allSettings = Setting::getCached();

        $this->assertCount(2, $allSettings);
        $this->assertEquals('value1', $allSettings['key1']);
        $this->assertEquals('value2', $allSettings['key2']);
    }

    public function test_theme_returns_theme_group_settings()
    {
        Setting::set('primary_color', '#3B82F6', 'theme');
        Setting::set('secondary_color', '#10B981', 'theme');

        $themeSettings = Setting::theme();

        $this->assertEquals('#3B82F6', $themeSettings['primary_color']);
        $this->assertEquals('#10B981', $themeSettings['secondary_color']);
    }

    public function test_setting_clears_cache_on_save()
    {
        Setting::set('cached_key', 'original');

        // Cache the value first
        Cache::put('setting.cached_key', 'original', 3600);
        Cache::put('settings.group.general', ['cached_key' => 'original'], 3600);
        Cache::put('settings.all', ['cached_key' => 'original'], 3600);

        $this->assertTrue(Cache::has('setting.cached_key'));

        $setting = Setting::where('key', 'cached_key')->first();
        $setting->value = 'updated';
        $setting->save();

        // Verify cache was cleared
        $this->assertFalse(Cache::has('setting.cached_key'));
        $this->assertFalse(Cache::has('settings.group.general'));
        $this->assertFalse(Cache::has('settings.all'));
    }

    public function test_setting_value_is_cast_to_json()
    {
        Setting::set('complex_value', ['nested' => ['key' => 'value']]);

        $value = Setting::get('complex_value');

        $this->assertIsArray($value);
        $this->assertEquals('value', $value['nested']['key']);
    }

    public function test_setting_stores_boolean_values()
    {
        Setting::set('feature_enabled', true);
        Setting::set('feature_disabled', false);

        $this->assertTrue(Setting::get('feature_enabled'));
        $this->assertFalse(Setting::get('feature_disabled'));
    }

    public function test_setting_stores_numeric_values()
    {
        Setting::set('max_items', 100);
        Setting::set('rate', 0.75);

        $this->assertEquals(100, Setting::get('max_items'));
        $this->assertEquals(0.75, Setting::get('rate'));
    }
}
