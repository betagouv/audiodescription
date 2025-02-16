<?php

namespace App\Enum;

use function Symfony\Component\String\s;

enum ApiResponseCode: string
{
    case ERR_VALIDATION = 'ERR_VALIDATION';
    case ERR_BAD_REQUEST = 'ERR_BAD_REQUEST';
    case ERR_UNAUTHORIZED = 'ERR_UNAUTHORIZED';
    case ERR_FORBIDDEN = 'ERR_FORBIDDEN';
    case ERR_NOT_FOUND = 'ERR_NOT_FOUND';
    case ERR_INTERNAL = 'ERR_INTERNAL';
    case ERR_GENERIC = 'ERR_GENERIC';

    public static function fromStatusCode(int $code): ApiResponseCode
    {
        return match ($code) {
            400 => self::ERR_BAD_REQUEST,
            401 => self::ERR_UNAUTHORIZED,
            403 => self::ERR_FORBIDDEN,
            404 => self::ERR_NOT_FOUND,
            500 => self::ERR_INTERNAL,
            default => self::ERR_GENERIC
        };
    }
}
