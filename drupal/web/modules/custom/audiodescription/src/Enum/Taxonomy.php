<?php

namespace Drupal\audiodescription\Enum;

/**
 * Defines taxonomy types.
 */
enum Taxonomy: string {
  case Actor = 'actor';
  case Director = 'director';
  case Public = 'public';
  case Genre = 'genre';
  case Nationality = 'nationality';
  case Partner = 'partner';
  case Offer = 'offer';
}
