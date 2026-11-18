<?php

namespace Tests\Feature;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FortifyActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_user_action_rejects_invalid_registration_payload(): void
    {
        $this->expectException(ValidationException::class);

        (new CreateNewUser())->create([
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'different',
        ]);
    }

    public function test_reset_user_password_persists_a_hashed_password(): void
    {
        $user = User::factory()->create();
        $newPassword = 'new-secure-password-123';

        (new ResetUserPassword())->reset($user, [
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        $this->assertTrue(Hash::check($newPassword, $user->fresh()->password));
    }

    public function test_reset_user_password_rejects_mismatched_confirmation(): void
    {
        $user = User::factory()->create();

        try {
            (new ResetUserPassword())->reset($user, [
                'password' => 'new-secure-password-123',
                'password_confirmation' => 'different-password-123',
            ]);
            $this->fail('A mismatched confirmation must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('password', $exception->errors());
            $this->assertTrue(Hash::check('123456789', $user->fresh()->password));
        }
    }

    public function test_update_user_password_checks_current_password_and_saves_new_hash(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        (new UpdateUserPassword())->update($user, [
            'current_password' => '123456789',
            'password' => 'updated-secure-password-123',
            'password_confirmation' => 'updated-secure-password-123',
        ]);

        $this->assertTrue(Hash::check('updated-secure-password-123', $user->fresh()->password));
        $this->assertFalse(Hash::check('123456789', $user->fresh()->password));
    }

    public function test_update_user_password_rejects_wrong_current_password(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        try {
            (new UpdateUserPassword())->update($user, [
                'current_password' => 'incorrect-password',
                'password' => 'updated-secure-password-123',
                'password_confirmation' => 'updated-secure-password-123',
            ]);
            $this->fail('A wrong current password must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('current_password', $exception->errors());
            $this->assertTrue(Hash::check('123456789', $user->fresh()->password));
        }
    }

    public function test_update_profile_action_rejects_an_email_owned_by_another_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->expectException(ValidationException::class);

        (new UpdateUserProfileInformation())->update($user, [
            'name' => 'Updated Name',
            'email' => $otherUser->email,
        ]);
    }
}
