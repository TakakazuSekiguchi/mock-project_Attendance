<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Admin;

class AdminLogoutTest extends TestCase
{
    public function test_管理者ログインでログアウトができる()
    {
        $adminuser = Admin::factory()->create();

        $this->actingAs($adminuser, 'admin');

        $response = $this->post('/admin/logout');

        $response->assertRedirect('/admin/login');

        $this->assertGuest('admin');
    }
}
