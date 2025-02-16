<?php

namespace App\Enum;

enum ValidationErrorCode: string
{
    case UNKNOWN = 'VALIDATION_UNKNOWN';
    case NOT_UNIQUE = 'VALIDATION_NOT_UNIQUE';
    case NOT_BLANK = 'VALIDATION_NOT_BLANK';
    case WRONG_FORMAT = 'VALIDATION_WRONG_FORMAT';
}
