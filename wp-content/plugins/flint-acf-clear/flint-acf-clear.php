<?php
/*
Plugin Name: (ƒ) FLINT - ACF Clear Fields
Plugin URI: https://flint-group.com
Description: Declares a plugin that will clear field content if the field is hidden with conditional logic.
Version: 1.0
Author URI: https://flint-group.com
*/

/**
 * Clear ANY ACF field value on save if its conditional logic evaluates to false.
 *
 * Notes:
 * - Runs for all fields via acf/update_value.
 * - Only clears fields that have conditional_logic configured.
 * - Evaluates ACF conditional logic groups (OR between groups, AND within a group).
 * - Uses submitted values in $_POST['acf'] when available, otherwise falls back to existing DB value.
 */

add_filter('acf/update_value', 'my_acf_clear_if_conditional_logic_false', 20, 3);
function my_acf_clear_if_conditional_logic_false( $value, $post_id, $field ) {

	// Only act on fields that have conditional logic configured.
	if ( empty($field['conditional_logic']) || !is_array($field['conditional_logic']) ) {
		return $value;
	}

	// If the logic does NOT pass, clear the value.
	if ( ! my_acf_conditional_logic_passes( $field['conditional_logic'], $post_id ) ) {
		return null; // deletes value (for most field types)
	}

	return $value;
}

/**
 * Evaluate ACF conditional logic array for a field.
 * - Groups are OR'ed.
 * - Rules within a group are AND'ed.
 */
function my_acf_conditional_logic_passes( array $conditional_logic, $post_id ) : bool {

	foreach ( $conditional_logic as $group ) {
		if ( !is_array($group) ) {
			continue;
		}

		$group_passes = true;

		foreach ( $group as $rule ) {
			if ( ! my_acf_rule_passes( $rule, $post_id ) ) {
				$group_passes = false;
				break; // AND failed
			}
		}

		if ( $group_passes ) {
			return true; // OR success
		}
	}

	return false;
}

/**
 * Evaluate a single conditional logic rule.
 * ACF rule shape typically:
 * [
 *   'field'    => 'field_abc123', // controller field key
 *   'operator' => '==',           // e.g. ==, !=, >, <, contains, empty, etc
 *   'value'    => '1'             // comparison value (string)
 * ]
 */
function my_acf_rule_passes( array $rule, $post_id ) : bool {

	$controller_key = $rule['field'] ?? '';
	$operator       = $rule['operator'] ?? '==';
	$expected       = $rule['value'] ?? '';

	if ( !$controller_key ) {
		// If the rule is malformed, fail safe: treat as not passing
		return false;
	}

	$actual = my_acf_get_controller_value_for_logic( $controller_key, $post_id );

	return my_acf_compare_values( $actual, $operator, $expected );
}

/**
 * Get the controller field's value for conditional logic.
 * Prefer the value currently being submitted in $_POST['acf'].
 * Fall back to stored DB value if not present in POST.
 */
function my_acf_get_controller_value_for_logic( string $controller_key, $post_id ) {

	// Prefer current submitted form values, if present.
	if ( isset($_POST['acf']) && is_array($_POST['acf']) && array_key_exists($controller_key, $_POST['acf']) ) {
		return $_POST['acf'][$controller_key];
	}

	// Fall back to the existing value in the DB.
	// Using ACF's internal getter avoids triggering update_value recursion.
	return function_exists('acf_get_value') ? acf_get_value($post_id, ['key' => $controller_key]) : null;
}

/**
 * Compare actual vs expected with an operator (covers common ACF operators).
 */
function my_acf_compare_values( $actual, string $operator, $expected ) : bool {

	// Normalize expected (ACF stores rule values as strings)
	$expected_str = is_array($expected) ? '' : (string) $expected;

	// Normalize actual
	// - Some fields submit arrays (checkbox, select multiple)
	// - Some submit "0"/"1", etc.
	$actual_is_array = is_array($actual);
	$actual_str      = $actual_is_array ? '' : (string) $actual;

	switch ( $operator ) {

		case '==':
			if ( $actual_is_array ) {
				return in_array($expected_str, array_map('strval', $actual), true);
			}
			return $actual_str === $expected_str;

		case '!=':
			if ( $actual_is_array ) {
				return !in_array($expected_str, array_map('strval', $actual), true);
			}
			return $actual_str !== $expected_str;

		case '>':
		case '<':
		case '>=':
		case '<=':
			$a = is_numeric($actual_str) ? (float) $actual_str : 0.0;
			$e = is_numeric($expected_str) ? (float) $expected_str : 0.0;

			if ( $operator === '>' )  return $a >  $e;
			if ( $operator === '<' )  return $a <  $e;
			if ( $operator === '>=' ) return $a >= $e;
			return $a <= $e;

		case 'contains':
			if ( $actual_is_array ) {
				return in_array($expected_str, array_map('strval', $actual), true);
			}
			return $expected_str !== '' && strpos($actual_str, $expected_str) !== false;

		case 'not_contains':
			if ( $actual_is_array ) {
				return !in_array($expected_str, array_map('strval', $actual), true);
			}
			return $expected_str === '' || strpos($actual_str, $expected_str) === false;

		case 'empty':
			if ( $actual_is_array ) {
				return count($actual) === 0;
			}
			return $actual_str === '' || $actual === null;

		case 'not_empty':
			if ( $actual_is_array ) {
				return count($actual) > 0;
			}
			return !($actual_str === '' || $actual === null);

		// Fallback: behave like ==
		default:
			if ( $actual_is_array ) {
				return in_array($expected_str, array_map('strval', $actual), true);
			}
			return $actual_str === $expected_str;
	}
}