<?php

namespace App\Enum;

/**
 * Defines import sources types.
 */
enum ImportSourceType: string {
  case CNC_CSV = 'CNC_CSV';
  case ARTE_TV_API = 'ARTE_TV_API';
  case MY_CANAL_API = 'MY_CANAL_API';
  case LACINETEK_API = 'LACINETEK_API';
  case ORANGE_VOD_CSV = 'ORANGE_VOD_CSV';
  case FRANCE_TV_CSV = 'FRANCE_TV_CSV';
}
