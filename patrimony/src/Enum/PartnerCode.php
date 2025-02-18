<?php

namespace App\Enum;

enum PartnerCode: string
{
    case CNC = 'CNC';
    case ARTE = 'ARTE';
    case CANAL_VOD = "CANAL_VOD";
    case CANAL_REPLAY = "CANAL_REPLAY";
    case LACINETEK_TVOD = "LACINETEK_TVOD";
    case LACINETEK_SVOD = "LACINETEK_SVOD";
    case ORANGE_VOD = "ORANGE_VOD";
    case FRANCE_TV = "FRANCE_TV";
}
