<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use HasApiTokens;

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
        return $this->email ? sha1($this->email): null;
    }
}
