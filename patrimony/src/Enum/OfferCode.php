<?php

namespace App\Enum;

enum OfferCode: string
{
    case FREE_ACCESS = 'FREE_ACCESS';
    case TVOD = 'TVOD';
    case SVOD = 'SVOD';
}
