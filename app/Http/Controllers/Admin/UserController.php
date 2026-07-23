<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminUserRequest;
use App\Http\Requests\Admin\UpdateAdminUserRequest;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AdminActivationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('q')->toString());
        $status = $request->string('status')->toString();
        $users = User::query()->where('role', UserRole::Admin)
            ->with(['activationTokens' => fn ($query) => $query->latest()->limit(1), 'userNotificationDeliveries' => fn ($query) => $query->latest()->limit(1)])
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')))
            ->when(in_array($status, ['active', 'inactive'], true), fn (Builder $query) => $query->where('is_active', $status === 'active'))
            ->orderBy('name')->paginate(15)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('admin.users.create', ['user' => new User]);
    }

    public function edit(User $user): View
    {
        abort_unless($user->isAdmin(), 404);

        return view('admin.users.create', compact('user'));
    }

    public function update(UpdateAdminUserRequest $request, User $user, AdminActivationService $activations): RedirectResponse
    {
        abort_unless($user->isAdmin(), 404);
        $data = $request->validated();
        $whatsappChanged = $user->whatsapp_hash !== $data['whatsapp_hash'];
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'whatsapp' => $data['whatsapp'],
            'whatsapp_hash' => $data['whatsapp_hash'],
            'is_active' => $user->is_active,
        ]);
        if ($whatsappChanged && $user->activated_at === null) {
            $activations->issue($user);
        }
        $this->audit($request, 'admin.updated', $user, ['whatsapp_changed' => $whatsappChanged, 'is_active' => $user->is_active]);

        return redirect()->route('admin.users.index')->with('success', $whatsappChanged && $user->activated_at === null
            ? 'Data admin diperbarui dan undangan dikirim ke nomor baru.'
            : 'Data admin berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isAdmin(), 404);
        if ($request->user()?->is($user)) {
            return back()->withErrors(['delete' => 'Anda tidak dapat menghapus akun sendiri.']);
        }
        if ($user->activated_at !== null) {
            return back()->withErrors(['delete' => 'Admin yang sudah aktivasi tidak dapat dihapus. Nonaktifkan akun untuk menjaga histori audit.']);
        }
        $this->audit($request, 'admin.deleted_unactivated', $user);
        $user->delete();

        return back()->with('success', 'Admin yang belum aktivasi berhasil dihapus.');
    }

    public function store(StoreAdminUserRequest $request, AdminActivationService $activations): RedirectResponse
    {
        $data = $request->validated();
        $user = User::query()->create([
            'name' => $data['name'], 'email' => $data['email'], 'whatsapp' => $data['whatsapp'], 'whatsapp_hash' => $data['whatsapp_hash'],
            'password' => Hash::make(Str::random(64)), 'role' => UserRole::Admin, 'is_active' => $data['is_active'], 'activated_at' => null,
        ]);
        $activations->issue($user);
        $this->audit($request, 'admin.created', $user, ['is_active' => $user->is_active]);

        return redirect()->route('admin.users.index')->with('success', 'Admin ditambahkan dan undangan WhatsApp dijadwalkan.');
    }

    public function status(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isAdmin(), 404);
        $active = $request->boolean('is_active');
        if ($request->user()?->is($user) && ! $active) {
            return back()->withErrors(['is_active' => 'Anda tidak dapat menonaktifkan akun sendiri.']);
        }
        $changed = DB::transaction(function () use ($user, $active): bool {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            if (! $active && User::query()->where('role', UserRole::Admin)->where('is_active', true)->lockForUpdate()->count() <= 1) {
                return false;
            }
            $locked->update(['is_active' => $active]);

            return true;
        });
        if (! $changed) {
            return back()->withErrors(['is_active' => 'Admin aktif terakhir tidak dapat dinonaktifkan.']);
        }
        $this->audit($request, 'admin.status_changed', $user, ['is_active' => $active]);

        return back()->with('success', 'Status admin diperbarui.');
    }

    public function resend(Request $request, User $user, AdminActivationService $activations): RedirectResponse
    {
        abort_unless($user->isAdmin() && $user->activated_at === null, 404);
        $activations->issue($user);
        $this->audit($request, 'admin.activation_resent', $user);

        return back()->with('success', 'Undangan aktivasi dijadwalkan ulang.');
    }

    /** @param array<string, mixed> $metadata */
    private function audit(Request $request, string $action, User $target, array $metadata = []): void
    {
        AuditLog::query()->create(['actor_type' => 'admin', 'action' => $action, 'auditable_type' => User::class, 'auditable_id' => $target->id, 'metadata' => $metadata, 'request_id' => (string) str()->uuid()]);
    }
}
