<?php

namespace Drupal\audiodescription\Enum;

/**
 * Defines taxonomy types.
 */
enum Taxonomy: string {
  case ACTOR = 'actor';
  case DIRECTOR = 'director';
  case PUBLIC = 'public';
  case GENRE = 'genre';
  case NATIONALITY = 'nationality';
  case PARTNER = 'partner';
  case OFFER = 'offer';
}
