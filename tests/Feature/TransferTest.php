<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;


class TransferTest extends TestCase
{
    use RefreshDatabase;

    /** @var \App\Models\User */
    protected $payer;

    /** @var \App\Models\User */
    protected $payee;

    /** @var string */
    protected $token;

    protected function setUp(): void
    {

        parent::setUp();
        Http::fake([
        'https://util.devi.tools/api/v2/authorize' => Http::response([
            'status' => 'success',
            'data'   => ['authorization' => true],
        ], 200),

    ]);


        $this->payer = User::factory()->create([
            'email'    => 'payer@example.com',
            'cpf_cnpj' => '11111111111',
            'password' => bcrypt('password'),
            'balance'  => 500.00,
            'type'     => 'common',
        ]);

        $this->payee = User::factory()->create([
            'email'    => 'payee@example.com',
            'cpf_cnpj' => '22222222222',
            'password' => bcrypt('password'),
            'balance'  => 0,
            'type'     => 'common',
        ]);

        $this->token = $this->payer
                            ->createToken('test-token')
                            ->plainTextToken;
    }

    public function test_successful_transfer()
    {
        $payload = [
            'amount'      => 100,
            'payerEmail'  => 'payer@example.com',
            'payeeEmail'  => 'payee@example.com',
        ];

        $response = $this->withHeaders([
                              'Authorization' => "Bearer {$this->token}",
                              'Accept'        => 'application/json',
                          ])->postJson('/api/transfer', $payload);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Transfer successful',
                     'transaction' => [
                         'amount'      => 100,
                         'payer_email' => 'payer@example.com',
                         'payee_email' => 'payee@example.com',
                     ],
                 ]);

        $this->assertDatabaseHas('users', [
            'email'   => 'payer@example.com',
            'balance' => 400.00, // 500 - 100
        ]);
        $this->assertDatabaseHas('users', [
            'email'   => 'payee@example.com',
            'balance' => 100.00,
        ]);
    }

    public function test_insufficient_balance()
    {
        $payload = [
            'amount'      => 1000,
            'payerEmail'  => 'payer@example.com',
            'payeeEmail'  => 'payee@example.com',
        ];

        $response = $this->withHeaders([
                              'Authorization' => "Bearer {$this->token}",
                              'Accept'        => 'application/json',
                          ])->postJson('/api/transfer', $payload);

        $response->assertStatus(400)
                 ->assertJson(['error' => 'Insufficient balance']);
    }
}
