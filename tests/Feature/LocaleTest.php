<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.api_key' => 'test_api_key_123']);
    }

    /** @test */
    public function test_registration_with_portuguese_locale()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'first_name' => 'João',
            'last_name' => 'Silva',
            'email' => 'joao@example.com',
            'password' => 'Test123456',
            'password_confirmation' => 'Test123456',
        ], [
            'API_KEY' => 'test_api_key_123',
            'Accept' => 'application/json',
            'X-Locale' => 'pt_BR',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Registro realizado com sucesso! Verifique seu e-mail para verificar sua conta.',
            ]);
    }

    /** @test */
    public function test_registration_with_english_locale()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'Test123456',
            'password_confirmation' => 'Test123456',
        ], [
            'API_KEY' => 'test_api_key_123',
            'Accept' => 'application/json',
            'X-Locale' => 'en',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        // Check that English message is NOT the Portuguese one
        $this->assertStringNotContainsString('Registro realizado com sucesso', $response->json('message'));
    }

    /** @test */
    public function test_web_verification_with_portuguese_browser()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);

        $hash = sha1($user->getEmailForVerification());
        $url = \Illuminate\Support\Facades\URL::signedRoute('verification.verify', [
            'id' => $user->id,
            'hash' => $hash
        ]);

        $response = $this->get($url, [
            'Accept-Language' => 'pt-BR,pt;q=0.9,en;q=0.8'
        ]);

        $response->assertStatus(200)
            ->assertViewIs('email-verification-result')
            ->assertViewHas('success', true);

        // Check that the response contains Portuguese text
        $content = $response->getContent();
        $this->assertStringContainsString('E-mail verificado com sucesso!', $content);
    }

    /** @test */
    public function test_web_verification_with_english_browser()
    {
        $user = User::factory()->create([
            'email' => 'test2@example.com',
            'email_verified_at' => null,
        ]);

        $hash = sha1($user->getEmailForVerification());
        $url = \Illuminate\Support\Facades\URL::signedRoute('verification.verify', [
            'id' => $user->id,
            'hash' => $hash
        ]);

        $response = $this->get($url, [
            'Accept-Language' => 'en-US,en;q=0.9'
        ]);

        $response->assertStatus(200)
            ->assertViewIs('email-verification-result')
            ->assertViewHas('success', true);

        // Check that the response contains English text
        $content = $response->getContent();
        $this->assertStringContainsString('Email verified successfully!', $content);
    }
}
