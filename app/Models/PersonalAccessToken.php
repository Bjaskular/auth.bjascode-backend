<?php

namespace App\Models;

use App\Traits\HasUUID;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    use HasUUID;
}
