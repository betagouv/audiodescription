<?php

namespace Drupal\audiodescription\Enum;

/**
 * Defines taxonomy types.
 */
enum Taxonomy: string {
  case DIRECTOR = 'director';
  case PUBLIC = 'public';
  case GENRE = 'genre';
  case NATIONALITY = 'nationality';
}
