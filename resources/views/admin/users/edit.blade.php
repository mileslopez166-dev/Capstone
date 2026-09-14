<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Edit User - AI-PGAALS</title>
    <x-admin-head />
</head>
<body class="staff-theme admin-theme min-h-screen overflow-x-hidden bg-surface text-on-surface">
    <div class="min-h-screen lg:flex">
        <x-admin-sidebar active="users" :admin-user="$adminUser" :admin-initials="$adminInitials" />

        <div class="w-full lg:ml-64">
            <x-admin-topbar title="Edit User" :admin-user="$adminUser" :admin-initials="$adminInitials"  />

            <main class="mx-auto max-w-5xl p-6 lg:p-8">
                <a class="mb-8 inline-flex items-center gap-2 font-medium text-primary transition-colors hover:text-primary-dim" href="{{ route('admin.dashboard') }}#user-management">
                    <span class="material-symbols-outlined text-lg">arrow_back</span>
                    Back to Users
                </a>

                @if (session('status'))
                    <div class="mb-6 rounded-DEFAULT border border-secondary/10 bg-secondary-container/35 px-5 py-4 text-sm font-medium text-on-surface">
                        {{ session('status') }}
                    </div>
                @endif

                <section class="relative overflow-hidden rounded-lg bg-surface-container-lowest p-6 shadow-[0_20px_40px_rgba(0,94,159,0.06)] lg:p-10">
                    <div class="pointer-events-none absolute inset-0 rounded-lg border border-outline-variant/15"></div>

                    <h2 class="mb-8 font-headline text-3xl font-bold tracking-tight text-on-surface">Edit Profile</h2>

                    <div class="mb-10 flex flex-col gap-6 rounded-DEFAULT bg-surface-container-low p-6 sm:flex-row sm:items-center">
                        <div class="flex h-24 w-24 shrink-0 items-center justify-center rounded-full bg-primary-container/30 text-2xl font-bold text-primary shadow-sm">
                            {{ $managedUserInitials ?: 'U' }}
                        </div>
                        <div>
                            <h3 class="font-headline text-2xl font-bold text-on-surface">{{ $managedUser->name }}</h3>
                            <p class="mt-1 text-on-surface-variant">{{ $managedUser->email }}</p>
                            <span class="mt-3 inline-flex rounded-md bg-tertiary-container px-3 py-1 text-xs font-bold uppercase tracking-wider text-on-tertiary-container">
                                {{ $managedUser->role }}
                            </span>
                        </div>
                    </div>

                    <form class="space-y-8" method="POST" action="{{ route('admin.users.update', $managedUser) }}">
                        @csrf
                        @method('PATCH')

                        <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                            <div class="space-y-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="name">Full Name</label>
                                <div class="relative">
                                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline">person</span>
                                    <input class="w-full rounded-sm border-none bg-surface-container-low py-3 pl-12 pr-4 text-on-surface shadow-inner transition-colors focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary" id="name" name="name" type="text" value="{{ old('name', $managedUser->name) }}" required>
                                </div>
                                @error('name')
                                    <p class="text-sm text-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="email">Email Address</label>
                                <div class="relative">
                                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline">mail</span>
                                    <input class="w-full cursor-not-allowed rounded-sm border-none bg-surface-container py-3 pl-12 pr-12 text-on-surface-variant" id="email" type="email" value="{{ $managedUser->email }}" readonly>
                                    <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-sm text-outline-variant">lock</span>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="role">Role</label>
                                <div class="relative">
                                    <span class="material-symbols-outlined pointer-events-none absolute left-4 top-1/2 z-10 -translate-y-1/2 text-outline">badge</span>
                                    <select class="w-full rounded-sm border-none bg-surface-container-low py-3 pl-12 pr-10 text-on-surface shadow-inner transition-colors focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary" id="role" name="role">
                                        <option value="student" @selected(old('role', $managedUser->role) === 'student')>Student</option>
                                        <option value="teacher" @selected(old('role', $managedUser->role) === 'teacher')>Teacher</option>
                                        <option value="admin" @selected(old('role', $managedUser->role) === 'admin')>Admin</option>
                                    </select>
                                </div>
                                @error('role')
                                    <p class="text-sm text-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="section">Section Assignment</label>
                                <div class="relative">
                                    <span class="material-symbols-outlined pointer-events-none absolute left-4 top-1/2 z-10 -translate-y-1/2 text-outline">class</span>
                                    <select class="w-full rounded-sm border-none bg-surface-container-low py-3 pl-12 pr-10 text-on-surface shadow-inner transition-colors focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary" id="section" name="section">
                                        <option value="">Unassigned</option>
                                        @foreach ($sections as $section)
                                            <option value="{{ $section }}" @selected(old('section', $managedUser->section) === $section)>{{ $section }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('section')
                                    <p class="text-sm text-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="space-y-2 md:col-span-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="approval_status">Account Status</label>
                                <div class="relative max-w-md">
                                    <span class="material-symbols-outlined pointer-events-none absolute left-4 top-1/2 z-10 -translate-y-1/2 text-outline">verified_user</span>
                                    <select class="w-full rounded-sm border-none bg-surface-container-low py-3 pl-12 pr-10 text-on-surface shadow-inner transition-colors focus:bg-surface-container-lowest focus:ring-2 focus:ring-primary" id="approval_status" name="approval_status">
                                        <option value="approved" @selected(old('approval_status', $managedUser->approval_status ?? 'approved') === 'approved')>Approved</option>
                                        <option value="pending" @selected(old('approval_status', $managedUser->approval_status ?? 'approved') === 'pending')>Pending</option>
                                        <option value="rejected" @selected(old('approval_status', $managedUser->approval_status ?? 'approved') === 'rejected')>Declined</option>
                                    </select>
                                </div>
                                @error('approval_status')
                                    <p class="text-sm text-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="md:col-span-2">
                                <div class="rounded-DEFAULT bg-surface-container-low p-5">
                                    <div class="mb-5">
                                        <h3 class="font-headline text-xl font-bold text-on-surface">Change Password</h3>
                                        <p class="mt-1 text-sm text-on-surface-variant">Leave these fields blank to keep the current password.</p>
                                    </div>

                                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                        <div class="space-y-2">
                                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="password">New Password</label>
                                            <div class="relative">
                                                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline">lock</span>
                                                <input class="w-full rounded-sm border-none bg-surface-container-lowest py-3 pl-12 pr-4 text-on-surface shadow-inner transition-colors focus:ring-2 focus:ring-primary" id="password" name="password" type="password" autocomplete="new-password" placeholder="Enter new password">
                                            </div>
                                            @error('password')
                                                <p class="text-sm text-error">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div class="space-y-2">
                                            <label class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant" for="password_confirmation">Confirm Password</label>
                                            <div class="relative">
                                                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline">verified_user</span>
                                                <input class="w-full rounded-sm border-none bg-surface-container-lowest py-3 pl-12 pr-4 text-on-surface shadow-inner transition-colors focus:ring-2 focus:ring-primary" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" placeholder="Re-enter password">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex items-center justify-end gap-4 border-t border-surface-container-low pt-8">
                            <a class="rounded-sm border border-outline-variant/30 bg-surface px-6 py-3 text-sm font-bold uppercase tracking-wider text-on-surface-variant transition-colors hover:bg-surface-container-low" href="{{ route('admin.dashboard') }}#user-management">
                                Cancel
                            </a>
                            <button class="rounded-sm bg-primary px-8 py-3 text-sm font-bold uppercase tracking-wider text-on-primary shadow-sm transition-colors hover:bg-primary-dim" type="submit">
                                Save Changes
                            </button>
                        </div>
                    </form>

                    @if($managedUser->role !== 'admin')
                        <div class="mt-6 border-t border-surface-container-low pt-6">
                            <form method="POST" action="{{ route('admin.users.destroy', $managedUser) }}" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                @csrf
                                @method('DELETE')
                                <button class="rounded-sm border border-error px-6 py-3 text-sm font-bold uppercase tracking-wider text-error transition-colors hover:bg-error/10" type="submit">
                                    Delete User
                                </button>
                            </form>
                        </div>
                    @endif
                </section>
            </main>
        </div>
    </div>
</body>
</html>
