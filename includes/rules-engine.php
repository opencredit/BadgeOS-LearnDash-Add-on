<?php
/**
 * Custom Achievement Rules
 *
 * @package BadgeOS LearnDash
 * @subpackage Achievements
 * @author Credly, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Load up our LearnDash triggers so we can add actions to them
 *
 * @since 1.0.0
 */
function badgeos_learndash_load_triggers() {

	// Grab our LearnDash triggers
	$learndash_triggers = $GLOBALS[ 'badgeos_learndash' ]->triggers;

	if ( !empty( $learndash_triggers ) ) {
		foreach ( $learndash_triggers as $trigger => $trigger_label ) {
			if ( is_array( $trigger_label ) ) {
				$triggers = $trigger_label;

				foreach ( $triggers as $trigger_hook => $trigger_name ) {
					add_action( $trigger_hook, 'badgeos_learndash_trigger_event', 10, 20 );
				}
			}
			else {
				add_action( $trigger, 'badgeos_learndash_trigger_event', 10, 20 );
			}
		}
	}

}

add_action( 'init', 'badgeos_learndash_load_triggers' );

/**
 * Handle each of our LearnDash triggers
 *
 * @since 1.0.0
 */
function badgeos_learndash_trigger_event() {

	// Setup all our important variables
	global $blog_id, $wpdb;

	// Setup args
	$args = func_get_args();

	$userID = get_current_user_id();

	if ( is_array( $args ) && isset( $args[ 'user' ] ) ) {
		if ( is_object( $args[ 'user' ] ) ) {
			$userID = (int) $args[ 'user' ]->ID;
		}
		else {
			$userID = (int) $args[ 'user' ];
		}
	}

	if ( empty( $userID ) ) {
		return;
	}

	$user_data = get_user_by( 'id', $userID );

	if ( empty( $user_data ) ) {
		return;
	}

	// Grab the current trigger
	$this_trigger = current_filter();

	// Update hook count for this user
	$new_count = badgeos_update_user_trigger_count( $userID, $this_trigger, $blog_id );

	// Mark the count in the log entry
	badgeos_post_log_entry( null, $userID, null, sprintf( __( '%1$s triggered %2$s (%3$dx)', 'badgeos' ), $user_data->user_login, $this_trigger, $new_count ) );

	// Now determine if any badges are earned based on this trigger event
	$triggered_achievements = $wpdb->get_results( $wpdb->prepare( "
		SELECT post_id
		FROM   $wpdb->postmeta
		WHERE  meta_key = '_badgeos_learndash_trigger'
				AND meta_value = %s
		", $this_trigger ) );

	foreach ( $triggered_achievements as $achievement ) {
		badgeos_maybe_award_achievement_to_user( $achievement->post_id, $userID, $this_trigger, $blog_id, $args );
	}
}

/**
 * Check if user deserves a LearnDash trigger step
 *
 * @since  1.0.0
 *
 * @param  bool $return         Whether or not the user deserves the step
 * @param  integer $user_id        The given user's ID
 * @param  integer $achievement_id The given achievement's post ID
 * @param  string $trigger        The trigger
 * @param  integer $site_id        The triggered site id
 * @param  array $args        The triggered args
 *
 * @return bool                    True if the user deserves the step, false otherwise
 */
function badgeos_learndash_user_deserves_learndash_step( $return, $user_id, $achievement_id, $this_trigger = '', $site_id = 1, $args = array() ) {

	// If we're not dealing with a step, bail here
	if ( 'step' != get_post_type( $achievement_id ) ) {
		return $return;
	}

	// Grab our step requirements
	$requirements = badgeos_get_step_requirements( $achievement_id );

	// If the step is triggered by LearnDash actions...
	if ( 'learndash_trigger' == $requirements[ 'trigger_type' ] ) {
		// Do not pass go until we say you can
		$return = false;

		// Unsupported trigger
		if ( !isset( $GLOBALS[ 'badgeos_learndash' ]->triggers[ $this_trigger ] ) ) {
			return $return;
		}

		// LearnDash requirements not met yet
		$learndash_triggered = false;

		// Set our main vars
		$learndash_trigger = $requirements[ 'learndash_trigger' ];
		$object_id = $requirements[ 'learndash_object_id' ];

		// Extra arg handling for further expansion
		$object_arg1 = null;

		if ( isset( $requirements[ 'learndash_object_arg1' ] ) )
			$object_arg1 = $requirements[ 'learndash_object_arg1' ];

		// Object-specific triggers
		$learndash_object_triggers = array(
			'learndash_quiz_completed',
			'badgeos_learndash_quiz_completed_specific',
			'badgeos_learndash_quiz_completed_fail',
			'learndash_lesson_completed',
			'learndash_course_completed'
		);

		// Category-specific triggers
		$learndash_category_triggers = array(
			'badgeos_learndash_course_completed_tag'
		);

		// Quiz-specific triggers
		$learndash_quiz_triggers = array(
			'learndash_quiz_completed',
			'badgeos_learndash_quiz_completed_specific',
			'badgeos_learndash_quiz_completed_fail'
		);

		// Triggered object ID (used in these hooks, generally 2nd arg)
		$triggered_object_id = 0;

		$arg_data = $args[ 0 ];

		if ( is_array( $arg_data ) ) {
			if ( isset( $arg_data[ 'quiz' ] ) ) {
				$triggered_object_id = (int) $arg_data[ 'quiz' ]->ID;
			}
			elseif ( isset( $arg_data[ 'lesson' ] ) ) {
				$triggered_object_id = (int) $arg_data[ 'lesson' ]->ID;
			}
			elseif ( isset( $arg_data[ 'course' ] ) ) {
				$triggered_object_id = (int) $arg_data[ 'course' ]->ID;
			}
		}

		// Use basic trigger logic if no object set
		if ( empty( $object_id ) ) {
			$learndash_triggered = true;
		}
		// Object specific
		elseif ( in_array( $learndash_trigger, $learndash_object_triggers ) && $triggered_object_id == $object_id ) {
			$learndash_triggered = true;

			// Forcing count due to BadgeOS bug tracking triggers properly
			$requirements[ 'count' ] = 1;
		}
		// Category specific
		elseif ( in_array( $learndash_trigger, $learndash_category_triggers ) && has_term( $object_id, 'post_tag', $triggered_object_id ) ) {
			$learndash_triggered = true;

			// Forcing count due to BadgeOS bug tracking triggers properly
			$requirements[ 'count' ] = 1;
		}

		// Quiz triggers
		if ( $learndash_triggered && in_array( $learndash_trigger, $learndash_quiz_triggers ) ) {
			// Check for fail
			if ( 'badgeos_learndash_quiz_completed_fail' == $learndash_trigger ) {
				if ( $arg_data[ 'pass' ] ) {
					$learndash_triggered = false;
				}
			}
			// Check for a specific grade
			elseif ( 'badgeos_learndash_quiz_completed_specific' == $learndash_trigger ) {
				$percentage = (int) $arg_data[ 'percentage' ];
				$object_arg1 = (int) $object_arg1;

				if ( $percentage < $object_arg1 ) {
					$learndash_triggered = false;
				}
			}
			// Check for passing
			elseif ( !$arg_data[ 'pass' ] ) {
				$learndash_triggered = false;
			}
		}

		// LearnDash requirements met
		if ( $learndash_triggered ) {
			// Grab the trigger count
			$trigger_count = badgeos_get_user_trigger_count( $user_id, $this_trigger, $site_id );

			// If we meet or exceed the required number of checkins, they deserve the step
			if ( 1 == $requirements[ 'count' ] || $requirements[ 'count' ] <= $trigger_count ) {
				// OK, you can pass go now
				$return = true;
			}
		}
	}

	return $return;
}

add_filter( 'user_deserves_achievement', 'badgeos_learndash_user_deserves_learndash_step', 15, 6 );