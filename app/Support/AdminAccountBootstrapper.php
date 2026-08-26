<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AdminAccountBootstrapper
{
    public function ensureExists(): void
    {
        $config = config('auth.bootstrap_admin', []);

        if (! ($config['enabled'] ?? false)) {
            return;
        }

        if (blank($config['email'] ?? null) || blank($config['password'] ?? null)) {
            return;
        }

        try {
            if (! Schema::hasTable('users')) {
                return;
            }
        } catch (Throwable) {
            return;
        }

        $user = User::withTrashed()->where('email', $config['email'])->first();

        if (! $user) {
            User::create([
                'name' => $config['name'],
                'email' => $config['email'],
                'role' => 'admin',
                'section' => $config['section'],
                'approval_status' => 'approved',
                'approved_at' => now(),
                'password' => Hash::make($config['password']),
                'email_verified_at' => now(),
            ]);

            return;
        }

        $updates = [];

        if ($user->trashed()) {
            $user->restore();
        }

        if ($user->role !== 'admin') {
            $updates['role'] = 'admin';
        }

        if (($user->approval_status ?? null) !== 'approved') {
            $updates['approval_status'] = 'approved';
            $updates['approved_at'] = now();
        }

        if (blank($user->section)) {
            $updates['section'] = $config['section'];
        }

        if (blank($user->name)) {
            $updates['name'] = $config['name'];
        }

        if (! $user->email_verified_at) {
            $updates['email_verified_at'] = now();
        }

        if (($config['sync_password'] ?? false) && ! Hash::check($config['password'], $user->password)) {
            $updates['password'] = Hash::make($config['password']);
        }

        if ($updates !== []) {
            $user->forceFill($updates)->save();
        }
    }
}
