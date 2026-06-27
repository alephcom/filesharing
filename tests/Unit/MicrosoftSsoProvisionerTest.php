<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\MicrosoftSsoProvisioner;
use SocialiteProviders\Manager\OAuth2\User as AzureSocialiteUser;
use Tests\TestCase;

class MicrosoftSsoProvisionerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'sso.tenant_id' => 'expected-tenant-id',
            'sso.allowed_domains' => ['yourcompany.com'],
        ]);
    }

    public function test_creates_user_on_first_login(): void
    {
        $azureUser = $this->makeAzureUser('new.user@yourcompany.com', 'oid-new-user');

        $user = app(MicrosoftSsoProvisioner::class)->provision($azureUser);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'new.user@yourcompany.com',
            'azure_oid' => 'oid-new-user',
        ]);
        $this->assertTrue($user->hasRole(UserRole::User));
        $this->assertSame('newuser', $user->username);
        $this->assertNull($user->password);
        $this->assertNotNull($user->last_login_at);
    }

    public function test_updates_existing_user_matched_by_azure_oid(): void
    {
        $existing = User::factory()->create([
            'username' => 'existing',
            'email' => 'existing@yourcompany.com',
            'azure_oid' => 'oid-existing',
            'name' => 'Old Name',
        ]);

        $azureUser = $this->makeAzureUser('existing@yourcompany.com', 'oid-existing', 'Updated Name');

        $user = app(MicrosoftSsoProvisioner::class)->provision($azureUser);

        $this->assertSame($existing->id, $user->id);
        $this->assertSame('Updated Name', $user->name);
        $this->assertNotNull($user->last_login_at);
    }

    public function test_links_existing_user_by_email(): void
    {
        $existing = User::factory()->create([
            'username' => 'legacyuser',
            'email' => 'legacy@yourcompany.com',
            'azure_oid' => null,
        ]);

        $azureUser = $this->makeAzureUser('legacy@yourcompany.com', 'oid-linked');

        $user = app(MicrosoftSsoProvisioner::class)->provision($azureUser);

        $this->assertSame($existing->id, $user->id);
        $this->assertSame('oid-linked', $user->azure_oid);
    }

    private function makeAzureUser(string $email, string $oid, string $name = 'Test User'): AzureSocialiteUser
    {
        $user = new AzureSocialiteUser;
        $user->map([
            'id' => $oid,
            'name' => $name,
            'email' => $email,
        ]);
        $user->setRaw([
            'id' => $oid,
            'displayName' => $name,
            'userPrincipalName' => $email,
            'mail' => $email,
        ]);
        $user->setAccessTokenResponseBody([
            'id_token' => $this->fakeIdToken(['tid' => 'expected-tenant-id']),
        ]);

        return $user;
    }

    private function fakeIdToken(array $claims): string
    {
        $header = $this->base64UrlEncode(json_encode(['alg' => 'none', 'typ' => 'JWT']));
        $payload = $this->base64UrlEncode(json_encode($claims));

        return $header.'.'.$payload.'.signature';
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
