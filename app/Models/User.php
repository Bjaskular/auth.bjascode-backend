<?php

namespace App\Models;

use App\Traits\HasUUID;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use HasApiTokens;
    use HasUUID;

    protected $guarded = ['id'];
    protected $hidden = ['password'];

    protected function password(): Attribute
    {
        return Attribute::make(
            set: fn (string $password) => Hash::make($password),
        );
    }

    public function getHashEmail(): ?string
    {
        return $this->email ? sha1($this->email) : null;
    }

    public function applications(): HasManyThrough
    {
        return $this->hasManyThrough(Application::class, UserApplication::class);
    }

    public function application(): HasOneThrough
    {
        return $this->hasOneThrough(
            Application::class,
            UserApplication::class,
            'user_id',
            'id',
            'id',
            'application_id'
        );
    }
}
