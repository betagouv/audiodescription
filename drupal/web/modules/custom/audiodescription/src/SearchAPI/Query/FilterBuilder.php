<?php

namespace Drupal\audiodescription\SearchAPI\Query;

use Drupal\search_api\Query\Condition;
use Drupal\search_api\Query\ConditionGroupInterface;
use Drupal\search_api\SearchApiException;

/**
 * Extends the elasticsearch_connector FilterBuilder to fix nested field paths.
 *
 * The parent class converts the Search API property path (e.g.
 * "field_offers:entity:field_pg_partners:entity:field_pg_partner") to ES dot
 * notation, but fields are indexed under their identifier ("field_pg_partner"),
 * not their full traversal path. This override skips the path conversion for
 * nested fields so the condition targets the correct ES field name.
 */
class FilterBuilder extends \Drupal\elasticsearch_connector\SearchAPI\Query\FilterBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildFilters(ConditionGroupInterface $condition_group, array $index_fields) {
    $filters = [
      'filters' => [],
      'post_filters' => [],
      'facets_post_filters' => [],
    ];

    $backend_fields = [
      'search_api_id' => TRUE,
      'search_api_language' => TRUE,
    ];

    if (empty($condition_group->getConditions())) {
      return $filters;
    }

    $conjunction = $condition_group->getConjunction();

    foreach ($condition_group->getConditions() as $condition) {
      $filter = NULL;

      if ($condition instanceof Condition) {
        if (!$condition->getField() || !$condition->getValue() || !$condition->getOperator()) {
          $this->logger->warning("Invalid condition %condition", ['%condition' => $condition]);
        }

        $field_id = $condition->getField();
        if (!isset($index_fields[$field_id]) && !isset($backend_fields[$field_id])) {
          throw new SearchApiException(sprintf("Invalid field '%s' in search filter", $field_id));
        }

        if (!$condition->getOperator()) {
          throw new SearchApiException(sprintf('Unspecified filter operator for field "%s"', $field_id));
        }

        if (isset($index_fields[$field_id])) {
          $field = $index_fields[$field_id];
          if ($field->getType() === 'boolean') {
            $condition->setValue((bool) $condition->getValue());
          }

          $index_field_name = $field->getPropertyPath();
          // Only apply property path for simple (non-nested) fields. Nested
          // fields (containing ':') are indexed flat under their identifier.
          if (!empty($index_field_name) && !str_contains($index_field_name, ':')) {
            $field_id = $index_field_name;
            $condition->setField($field_id);
          }

          if ($condition_group->hasTag(sprintf('facet:%s', $field_id)) &&
              $conjunction == "OR" &&
              $field->getType() === 'text') {
            $condition->setField($field_id . '.keyword');
          }
        }

        $filter = $this->buildFilterTerm($condition, $index_fields);

        if (!empty($filter)) {
          if ($condition_group->hasTag(sprintf('facet:%s', $field_id))
            && $conjunction == "OR"
          ) {
            $filters["post_filters"][] = $filter;

            $facet_filter_field = $condition->getField();

            if (isset($filters["facets_post_filters"][$field_id])) {
              $existing_filter = $filters["facets_post_filters"][$field_id];
              $merged_values = array_merge(
                $existing_filter['terms'][$facet_filter_field] ?? [],
                (array) $condition->getValue()
              );

              $filters["facets_post_filters"][$field_id] = [
                'terms' => [
                  $facet_filter_field => array_unique($merged_values),
                ],
              ];
            }
            else {
              $filters["facets_post_filters"][$field_id] = [
                'terms' => [
                  $facet_filter_field => (array) $condition->getValue(),
                ],
              ];
            }
          }
          else {
            $filters["filters"][] = $filter;
          }
        }
      }
      elseif ($condition instanceof ConditionGroupInterface) {
        $nested_filters = $this->buildFilters(
          $condition,
          $index_fields
        );

        foreach ([
          "filters",
          "post_filters",
        ] as $filter_type) {
          if (!empty($nested_filters[$filter_type])) {
            $filters[$filter_type][] = $nested_filters[$filter_type];
          }
        }

        foreach ($nested_filters["facets_post_filters"] as $facetId => $facetsPostFilters) {
          $filters["facets_post_filters"][$facetId] = $facetsPostFilters;
        }
      }
    }

    foreach ([
      "filters",
      "post_filters",
    ] as $filter_type) {
      if (count($filters[$filter_type]) > 1) {
        $filters[$filter_type] = $this->wrapWithConjunction($filters[$filter_type], $conjunction);
      }
      else {
        $filters[$filter_type] = array_pop($filters[$filter_type]);
      }
    }

    return $filters;
  }

}
