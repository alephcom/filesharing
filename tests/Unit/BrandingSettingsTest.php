<?php

namespace Tests\Unit;

use App\Services\BrandingSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandingSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_persist_and_clear_cache(): void
    {
        $branding = app(BrandingSettings::class);

        $branding->set(BrandingSettings::KEY_APP_NAME, 'Acme Files');
        $branding->set(BrandingSettings::KEY_PRIMARY_COLOR, '#ff0000');

        $this->assertSame('Acme Files', $branding->appName());
        $this->assertSame('255 0 0', $branding->cssVariables()['--color-primary']);

        $branding->set(BrandingSettings::KEY_APP_NAME, null);

        $this->assertSame(config('app.name'), $branding->appName());
    }

    public function test_logo_url_uses_public_storage_path(): void
    {
        $branding = app(BrandingSettings::class);

        $branding->set(BrandingSettings::KEY_LOGO_PATH, 'branding/logo.png');

        $this->assertStringContainsString('storage/branding/logo.png', $branding->logoUrl());
    }
}
