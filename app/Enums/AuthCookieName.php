<?php

namespace App\Enums;

enum AuthCookieName: string
{
    case API_ACCESS = 'auth-bjascode-access-token';
    case REFRESH = 'auth-bjascode-refresh-token';
}
