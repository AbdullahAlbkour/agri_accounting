<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseBackupTest extends TestCase
{
    use RefreshDatabase;

    protected function user(): User
    {
        return User::create([
            'name' => 'مدير',
            'email' => 'admin@agri.local',
            'password' => Hash::make('secret123'),
        ]);
    }

    public function test_settings_page_renders(): void
    {
        $this->actingAs($this->user())
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('نسخة احتياطية', false);
    }

    public function test_backup_downloads_sql_dump_containing_data(): void
    {
        Crop::create(['name' => 'قمح']);

        $response = $this->actingAs($this->user())->get(route('backup.download'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/sql; charset=UTF-8');

        $content = $response->streamedContent();

        $this->assertStringContainsString('CREATE TABLE', $content);
        $this->assertStringContainsString('crops', $content);
        $this->assertStringContainsString('قمح', $content);
        $this->assertStringContainsString('INSERT INTO', $content);
    }

    public function test_backup_requires_authentication(): void
    {
        $this->get(route('backup.download'))->assertRedirect(route('login'));
    }
}
