<?php

namespace App\Enums;

enum WebLoginResult
{
    case Success;
    case InvalidCredentials;
    case AccountInactive;
}
