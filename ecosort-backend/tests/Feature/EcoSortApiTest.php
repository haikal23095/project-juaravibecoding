<?php

use App\Models\User;
use App\Models\WasteBank;
use App\Models\Transaction;
use App\Models\Withdrawal;
use App\Models\UserWallet;
use App\Models\WasteCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('db:seed');
});

test('guest cannot access authenticated routes', function () {
    $this->getJson('/api/me')->assertStatus(401);
    $this->getJson('/api/admin/dashboard')->assertStatus(401);
});

test('user can register and login successfully', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Nasabah Baru',
        'email' => 'newuser@ecosort.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'user'
    ]);

    $response->assertStatus(200)
             ->assertJsonStructure(['status', 'message']);

    $loginResponse = $this->postJson('/api/login', [
        'email' => 'newuser@ecosort.com',
        'password' => 'password123',
    ]);

    $loginResponse->assertStatus(200)
                  ->assertJsonStructure(['status', 'token', 'user']);
});

test('admin can access dashboard statistics', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    
    Sanctum::actingAs($admin);

    $category = WasteCategory::firstOrCreate(
        ['name' => 'plastik'],
        [
            'description' => 'Sampah plastik',
            'is_default' => true,
            'is_active' => true,
        ]
    );

    $manager = User::factory()->create();
    $manager->assignRole('bank_sampah');

    $wasteBank = WasteBank::create([
        'name' => 'Bank Sampah Test',
        'address' => 'Jl. Test No. 1',
        'latitude' => -6.2,
        'longitude' => 106.8,
        'is_active' => true,
        'manager_id' => $manager->id
    ]);

    $nasabah = User::factory()->create();
    
    $transaction = Transaction::create([
        'user_id' => $nasabah->id,
        'waste_bank_id' => $wasteBank->id,
        'total_earnings' => 15000,
        'status' => 'completed'
    ]);

    $transaction->details()->create([
        'waste_category_id' => $category->id,
        'weight_kg' => 2.5,
        'subtotal' => 15000,
        'scan_method' => 'manual',
    ]);

    $response = $this->getJson('/api/admin/dashboard');

    $response->assertStatus(200)
             ->assertJsonPath('status', 'success')
             ->assertJsonStructure([
                 'status',
                 'data' => [
                     'stats' => [
                         'total_users',
                         'admins_count',
                         'managers_count',
                         'regular_users_count',
                         'total_waste_banks',
                         'active_waste_banks',
                         'total_transactions',
                         'completed_transactions',
                         'total_points',
                         'total_weight',
                     ],
                     'recent_users',
                     'recent_transactions'
                 ]
             ]);
});

test('admin can manage users CRUD', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    Sanctum::actingAs($admin);

    // 1. Create User
    $createResponse = $this->postJson('/api/users', [
        'name' => 'Mitra Baru',
        'email' => 'mitrabaru@ecosort.com',
        'password' => 'password123',
        'phone' => '08123456789',
        'role' => 'manager'
    ]);

    $createResponse->assertStatus(200)
                   ->assertJsonPath('status', 'success');

    $createdUser = User::where('email', 'mitrabaru@ecosort.com')->first();
    expect($createdUser)->not->toBeNull();
    expect($createdUser->hasRole('bank_sampah'))->toBeTrue();

    // 2. Read Users
    $listResponse = $this->getJson('/api/users');
    $listResponse->assertStatus(200)
                 ->assertJsonStructure(['status', 'data']);

    // 3. Update User
    $updateResponse = $this->putJson("/api/users/{$createdUser->id}", [
        'name' => 'Mitra Baru Updated',
        'email' => 'mitrabaru@ecosort.com',
        'phone' => '08999999999',
        'role' => 'user'
    ]);

    $updateResponse->assertStatus(200);
    $createdUser->refresh();
    expect($createdUser->name)->toBe('Mitra Baru Updated');
    expect($createdUser->hasRole('user'))->toBeTrue();

    // 4. Delete User
    $deleteResponse = $this->deleteJson("/api/users/{$createdUser->id}");
    $deleteResponse->assertStatus(200);
    expect(User::find($createdUser->id))->toBeNull();
});

test('admin can manage locations CRUD', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    Sanctum::actingAs($admin);

    $manager = User::factory()->create();
    $manager->assignRole('bank_sampah');

    // 1. Create Location
    $createResponse = $this->postJson('/api/waste-banks', [
        'name' => 'Bank Sampah Harmoni',
        'address' => 'Jl. Harmoni No. 5',
        'latitude' => -6.345,
        'longitude' => 106.987,
        'is_active' => true,
        'manager_id' => $manager->id
    ]);

    $createResponse->assertStatus(200)
                   ->assertJsonPath('status', 'success');

    $createdBank = WasteBank::where('name', 'Bank Sampah Harmoni')->first();
    expect($createdBank)->not->toBeNull();

    // 2. Read Locations
    $listResponse = $this->getJson('/api/waste-banks');
    $listResponse->assertStatus(200)
                 ->assertJsonStructure(['status', 'data']);

    // 3. Update Location
    $updateResponse = $this->putJson("/api/waste-banks/{$createdBank->id}", [
        'name' => 'Bank Sampah Harmoni Baru',
        'address' => 'Jl. Harmoni Baru No. 10',
        'latitude' => -6.350,
        'longitude' => 106.990,
        'is_active' => false,
        'manager_id' => $manager->id
    ]);

    $updateResponse->assertStatus(200);
    $createdBank->refresh();
    expect($createdBank->name)->toBe('Bank Sampah Harmoni Baru');
    expect($createdBank->is_active)->toBeFalse();

    // 4. Delete Location
    $deleteResponse = $this->deleteJson("/api/waste-banks/{$createdBank->id}");
    $deleteResponse->assertStatus(200);
    expect(WasteBank::find($createdBank->id))->toBeNull();
});

test('user can manage their wallets', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    // 1. Create Wallet
    $createResponse = $this->postJson('/api/user/wallets', [
        'bank_name' => 'Gopay',
        'account_number' => '08123456789',
        'account_name' => 'Nasabah Gopay'
    ]);

    $createResponse->assertStatus(200)
                   ->assertJsonPath('status', 'success');

    $wallet = $user->wallets()->first();
    expect($wallet)->not->toBeNull();

    // 2. Read Wallets
    $listResponse = $this->getJson('/api/user/wallets');
    $listResponse->assertStatus(200)
                 ->assertJsonStructure(['status', 'data']);

    // 3. Update Wallet
    $updateResponse = $this->putJson("/api/user/wallets/{$wallet->id}", [
        'bank_name' => 'OVO',
        'account_number' => '08999999999',
        'account_name' => 'Nasabah OVO'
    ]);

    $updateResponse->assertStatus(200);
    $wallet->refresh();
    expect($wallet->bank_name)->toBe('OVO');

    // 4. Delete Wallet
    $deleteResponse = $this->deleteJson("/api/user/wallets/{$wallet->id}");
    $deleteResponse->assertStatus(200);
    expect($user->wallets()->count())->toBe(0);
});

test('user can request withdrawals and manager can approve', function () {
    $nasabah = User::factory()->create(['balance' => 50000]);
    
    $manager = User::factory()->create();
    $manager->assignRole('bank_sampah');

    $wasteBank = WasteBank::create([
        'name' => 'Bank Sampah Sejahtera',
        'address' => 'Jl. Sejahtera No. 7',
        'latitude' => -6.1,
        'longitude' => 106.7,
        'is_active' => true,
        'manager_id' => $manager->id
    ]);

    // 1. Make Withdrawal Request
    Sanctum::actingAs($nasabah);
    $requestResponse = $this->postJson('/api/withdrawals', [
        'amount' => 20000,
        'bank_name' => 'BCA',
        'account_number' => '1234567890',
        'account_name' => 'Nasabah BCA',
        'waste_bank_id' => $wasteBank->id
    ]);

    $requestResponse->assertStatus(200)
                    ->assertJsonPath('status', 'success');

    $withdrawalId = $requestResponse->json('data.id');
    $withdrawal = Withdrawal::findOrFail($withdrawalId);
    expect($withdrawal->status)->toBe('pending');

    // 2. Manager Approve Withdrawal
    Sanctum::actingAs($manager);
    $approveResponse = $this->patchJson("/api/withdrawals/{$withdrawal->id}/status", [
        'status' => 'approved'
    ]);

    $approveResponse->assertStatus(200);
    $withdrawal->refresh();
    expect($withdrawal->status)->toBe('approved');
    
    $nasabah->refresh();
    expect($nasabah->balance)->toBe(30000); // 50000 - 20000
});
