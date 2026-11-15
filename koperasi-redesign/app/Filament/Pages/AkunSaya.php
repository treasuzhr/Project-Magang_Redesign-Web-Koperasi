<?php

namespace App\Filament\Pages;

use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/** @property-read Schema $form */
class AkunSaya extends Page
{
    protected static ?string $title = 'Akun saya';

    protected static ?string $slug = 'akun-saya';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected string $view = 'filament.pages.akun-saya';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('current_password')->label('Password lama')->password()->revealable()->required()->rules(['string'])->maxLength(1024)->autocomplete('current-password'),
            TextInput::make('password')->label('Password baru')->password()->revealable()->required()->rules(['string'])->minLength(8)->maxLength(1024)->confirmed()->autocomplete('new-password')->validationMessages(['min' => 'Password baru minimal 8 karakter.', 'confirmed' => 'Konfirmasi password baru tidak sesuai.']),
            TextInput::make('password_confirmation')->label('Konfirmasi password baru')->password()->revealable()->required()->rules(['string'])->maxLength(1024)->autocomplete('new-password'),
        ])->statePath('data');
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);
        $key = 'admin-password:'.auth()->id();
        if (RateLimiter::tooManyAttempts($key, 6)) {
            throw ValidationException::withMessages(['data.current_password' => 'Terlalu banyak percobaan. Coba lagi dalam satu menit.']);
        }
        RateLimiter::hit($key, 60);
        $data = $this->form->getState();
        $user = DB::transaction(function () use ($data): User {
            $user = User::query()->lockForUpdate()->findOrFail(auth()->id());
            if (! Hash::check($data['current_password'], $user->password)) {
                throw ValidationException::withMessages(['data.current_password' => 'Password lama tidak sesuai.']);
            }
            $user->forceFill(['password' => Hash::make($data['password'])])->save();

            return $user;
        });
        auth()->setUser($user);
        session()->regenerate();
        session()->put('password_hash_'.auth()->getDefaultDriver(), auth()->guard()->hashPasswordForCookie($user->password));
        RateLimiter::clear($key);
        $this->form->fill();
        $this->resetValidation();
        Notification::make()->title('Password berhasil diubah.')->success()->send();
    }
}
