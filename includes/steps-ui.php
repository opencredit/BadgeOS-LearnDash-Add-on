<?php
/**
 * Custom Achievement Steps UI
 *
 * @package BadgeOS LearnDash
 * @subpackage Achievements
 * @author Credly, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Update badgeos_get_step_requirements to include our custom requirements
 *
 * @since  1.0.0
 *
 * @param  array $requirements The current step requirements
 * @param  integer $step_id      The given step's post ID
 *
 * @return array                 The updated step requirements
 */
function badgeos_learndash_step_requirements( $requirements, $step_id ) {

	// Add our new requirements to the list
	$requirements[ 'learndash_trigger' ] = get_post_meta( $step_id, '_badgeos_learndash_trigger', true );
	$requirements[ 'learndash_object_id' ] = (int) get_post_meta( $step_id, '_badgeos_learndash_object_id', true );
	$requirements[ 'learndash_object_arg1' ] = (int) get_post_meta( $step_id, '_badgeos_learndash_object_arg1', true );

	// Return the requirements array
	return $requirements;

}

add_filter( 'badgeos_get_step_requirements', 'badgeos_learndash_step_requirements', 10, 2 );

/**
 * Filter the BadgeOS Triggers selector with our own options
 *
 * @since  1.0.0
 *
 * @param  array $triggers The existing triggers array
 *
 * @return array           The updated triggers array
 */
function badgeos_learndash_activity_triggers( $triggers ) {

	$triggers[ 'learndash_trigger' ] = __( 'LearnDash Activity', 'badgeos-learndash' );

	return $triggers;

}

add_filter( 'badgeos_activity_triggers', 'badgeos_learndash_activity_triggers' );

/**
 * Add LearnDash Triggers selector to the Steps UI
 *
 * @since 1.0.0
 *
 * @param integer $step_id The given step's post ID
 * @param integer $post_id The given parent post's post ID
 */
function badgeos_learndash_step_learndash_trigger_select( $step_id, $post_id ) {

	// Setup our select input
	echo '<select name="learndash_trigger" class="select-learndash-trigger">';
	echo '<option value="">' . __( 'Select a LearnDash Trigger', 'badgeos-learndash' ) . '</option>';

	// Loop through all of our LearnDash trigger groups
	$current_trigger = get_post_meta( $step_id, '_badgeos_learndash_trigger', true );

	$learndash_triggers = $GLOBALS[ 'badgeos_learndash' ]->triggers;

	if ( !empty( $learndash_triggers ) ) {
		foreach ( $learndash_triggers as $trigger => $trigger_label ) {
			if ( is_array( $trigger_label ) ) {
				$optgroup_name = $trigger;
				$triggers = $trigger_label;

				echo '<optgroup label="' . esc_attr( $optgroup_name ) . '">';
				// Loop through each trigger in the group
				foreach ( $triggers as $trigger_hook => $trigger_name ) {
					echo '<option' . selected( $current_trigger, $trigger_hook, false ) . ' value="' . esc_attr( $trigger_hook ) . '">' . esc_html( $trigger_name ) . '</option>';
				}
				echo '</optgroup>';
			}
			else {
				echo '<option' . selected( $current_trigger, $trigger, false ) . ' value="' . esc_attr( $trigger ) . '">' . esc_html( $trigger_label ) . '</option>';
			}
		}
	}

	echo '</select>';

}

add_action( 'badgeos_steps_ui_html_after_trigger_type', 'badgeos_learndash_step_learndash_trigger_select', 10, 2 );

/**
 * Add a BuddyPress group selector to the Steps UI
 *
 * @since 1.0.0
 *
 * @param integer $step_id The given step's post ID
 * @param integer $post_id The given parent post's post ID
 */
function badgeos_learndash_step_etc_select( $step_id, $post_id ) {

	$current_trigger = get_post_meta( $step_id, '_badgeos_learndash_trigger', true );
	$current_object_id = (int) get_post_meta( $step_id, '_badgeos_learndash_object_id', true );
	$current_object_arg1 = (int) get_post_meta( $step_id, '_badgeos_learndash_object_arg1', true );

	// Quizes
	echo '<select name="badgeos_learndash_quiz_id" class="select-quiz-id">';
	echo '<option value="">' . __( 'Any Quiz', 'badgeos-learndash' ) . '</option>';

	// Loop through all objects
	$objects = get_posts( array(
		'post_type' => 'sfwd-quiz',
		'post_status' => 'publish',
		'posts_per_page' => -1
	) );

	if ( !empty( $objects ) ) {
		foreach ( $objects as $object ) {
			$selected = '';

			if ( in_array( $current_trigger, array( 'learndash_quiz_completed', 'badgeos_learndash_quiz_completed_specific' ) ) )
				$selected = selected( $current_object_id, $object->ID, false );

			echo '<option' . $selected . ' value="' . $object->ID . '">' . esc_html( get_the_title( $object->ID ) ) . '</option>';
		}
	}

	echo '</select>';

	// Grade input
	$grade = 100;

	if ( in_array( $current_trigger, array( 'badgeos_learndash_quiz_completed_specific' ) ) )
		$grade = (int) $current_object_arg1;

	if ( empty( $grade ) )
		$grade = 100;

	echo '<span><input name="badgeos_learndash_quiz_grade" class="input-quiz-grade" type="text" value="' . $grade . '" size="3" maxlength="3" placeholder="100" />%</span>';

	// Lessons
	echo '<select name="badgeos_learndash_lesson_id" class="select-lesson-id">';
	echo '<option value="">' . __( 'Any Lesson', 'badgeos-learndash' ) . '</option>';

	// Loop through all objects
	$objects = get_posts( array(
		'post_type' => 'sfwd-lessons',
		'post_status' => 'publish',
		'posts_per_page' => -1
	) );

	if ( !empty( $objects ) ) {
		foreach ( $objects as $object ) {
			$selected = '';

			if ( in_array( $current_trigger, array( 'learndash_lesson_completed' ) ) )
				$selected = selected( $current_object_id, $object->ID, false );

			echo '<option' . $selected . ' value="' . $object->ID . '">' . esc_html( get_the_title( $object->ID ) ) . '</option>';
		}
	}

	echo '</select>';

	// Courses
	echo '<select name="badgeos_learndash_course_id" class="select-course-id">';
	echo '<option value="">' . __( 'Any Course', 'badgeos-learndash' ) . '</option>';

	// Loop through all objects
	$objects = get_posts( array(
		'post_type' => 'sfwd-courses',
		'post_status' => 'publish',
		'posts_per_page' => -1
	) );

	if ( !empty( $objects ) ) {
		foreach ( $objects as $object ) {
			$selected = '';

			if ( in_array( $current_trigger, array( 'learndash_course_completed' ) ) )
				$selected = selected( $current_object_id, $object->ID, false );

			echo '<option' . $selected . ' value="' . $object->ID . '">' . esc_html( get_the_title( $object->ID ) ) . '</option>';
		}
	}

	echo '</select>';

	// Course Category
	echo '<select name="badgeos_learndash_course_category_id" class="select-course-category-id">';
	echo '<option value="">' . __( 'Any Course Tag', 'badgeos-learndash' ) . '</option>';

	// Loop through all objects
	$objects = get_terms( 'post_tag', array(
		'hide_empty' => false
	) );

	if ( !empty( $objects ) ) {
		foreach ( $objects as $object ) {
			$selected = '';

			if ( in_array( $current_trigger, array( 'badgeos_learndash_course_completed_tag' ) ) )
				$selected = selected( $current_object_id, $object->term_id, false );

			echo '<option' . $selected . ' value="' . $object->term_id . '">' . esc_html( $object->name ) . '</option>';
		}
	}

	echo '</select>';

}

add_action( 'badgeos_steps_ui_html_after_trigger_type', 'badgeos_learndash_step_etc_select', 10, 2 );

/**
 * AJAX Handler for saving all steps
 *
 * @since  1.0.0
 *
 * @param  string $title     The original title for our step
 * @param  integer $step_id   The given step's post ID
 * @param  array $step_data Our array of all available step data
 *
 * @return string             Our potentially updated step title
 */
function badgeos_learndash_save_step( $title, $step_id, $step_data ) {

	// If we're working on a LearnDash trigger
	if ( 'learndash_trigger' == $step_data[ 'trigger_type' ] ) {

		// Update our LearnDash trigger post meta
		update_post_meta( $step_id, '_badgeos_learndash_trigger', $step_data[ 'learndash_trigger' ] );

		// Rewrite the step title
		$title = $step_data[ 'learndash_trigger_label' ];

		$object_id = 0;
		$object_arg1 = 0;

		// Quiz specific (pass)
		if ( 'learndash_quiz_completed' == $step_data[ 'learndash_trigger' ] ) {
			// Get Object ID
			$object_id = (int) $step_data[ 'learndash_quiz_id' ];

			// Set new step title
			if ( empty( $object_id ) ) {
				$title = __( 'Completed any quiz', 'badgeos-learndash' );
			}
			else {
				$title = sprintf( __( 'Completed quiz "%s"', 'badgeos-learndash' ), get_the_title( $object_id ) );
			}
		}
		// Quiz specific (grade specific)
		elseif ( 'badgeos_learndash_quiz_completed_specific' == $step_data[ 'learndash_trigger' ] ) {
			// Get Object ID
			$object_id = (int) $step_data[ 'learndash_quiz_id' ];
			$object_arg1 = (int) $step_data[ 'learndash_quiz_grade' ];

			// Set new step title
			if ( empty( $object_id ) ) {
				$title = sprintf( __( 'Completed any quiz with a score of %d or higher', 'badgeos-learndash' ), $object_arg1 );
			}
			else {
				$title = sprintf( __( 'Completed quiz "%s" with a score of %d or higher', 'badgeos-learndash' ), get_the_title( $object_id ), $object_arg1 );
			}
		}
		// Quiz specific (fail)
		elseif ( 'badgeos_learndash_quiz_completed_fail' == $step_data[ 'learndash_trigger' ] ) {
			// Get Object ID
			$object_id = (int) $step_data[ 'learndash_quiz_id' ];

			// Set new step title
			if ( empty( $object_id ) ) {
				$title = sprintf( __( 'Failed any quiz', 'badgeos-learndash' ), $object_arg1 );
			}
			else {
				$title = sprintf( __( 'Failed quiz "%s"', 'badgeos-learndash' ), get_the_title( $object_id ), $object_arg1 );
			}
		}
		// Lesson specific
		elseif ( 'learndash_lesson_completed' == $step_data[ 'learndash_trigger' ] ) {
			// Get Object ID
			$object_id = (int) $step_data[ 'learndash_lesson_id' ];

			// Set new step title
			if ( empty( $object_id ) ) {
				$title = __( 'Completed any lesson', 'badgeos-learndash' );
			}
			else {
				$title = sprintf( __( 'Completed lesson "%s"', 'badgeos-learndash' ), get_the_title( $object_id ) );
			}
		}
		// Course specific
		elseif ( 'learndash_course_completed' == $step_data[ 'learndash_trigger' ] ) {
			// Get Object ID
			$object_id = (int) $step_data[ 'learndash_course_id' ];

			// Set new step title
			if ( empty( $object_id ) ) {
				$title = __( 'Completed any course', 'badgeos-learndash' );
			}
			else {
				$title = sprintf( __( 'Completed course "%s"', 'badgeos-learndash' ), get_the_title( $object_id ) );
			}
		}
		// Course Category specific
		elseif ( 'badgeos_learndash_course_completed_tag' == $step_data[ 'learndash_trigger' ] ) {
			// Get Object ID
			$object_id = (int) $step_data[ 'learndash_course_category_id' ];

			// Set new step title
			if ( empty( $object_id ) ) {
				$title = __( 'Completed course in any tag', 'badgeos-learndash' );
			}
			else {
				$title = sprintf( __( 'Completed course in tag "%s"', 'badgeos-learndash' ), get_term( $object_id, 'post_tag' )->name );
			}
		}

		// Store our Object ID in meta
		update_post_meta( $step_id, '_badgeos_learndash_object_id', $object_id );
		update_post_meta( $step_id, '_badgeos_learndash_object_arg1', $object_arg1 );
	}

	// Send back our custom title
	return $title;

}

add_filter( 'badgeos_save_step', 'badgeos_learndash_save_step', 10, 3 );

/**
 * Include custom JS for the BadgeOS Steps UI
 *
 * @since 1.0.0
 */
function badgeos_learndash_step_js() {

	?>
	<script type="text/javascript">
		jQuery( document ).ready( function ( $ ) {

			// Listen for our change to our trigger type selector
			$( document ).on( 'change', '.select-trigger-type', function () {

				var trigger_type = $( this );

				// Show our group selector if we're awarding based on a specific group
				if ( 'learndash_trigger' == trigger_type.val() ) {
					trigger_type.siblings( '.select-learndash-trigger' ).show().change();
				}
				else {
					trigger_type.siblings( '.select-learndash-trigger' ).hide().change();
				}

			} );

			// Listen for our change to our trigger type selector
			$( document ).on( 'change', '.select-learndash-trigger,' +
										'.select-quiz-id,' +
										'.select-lesson-id,' +
										'.select-course-id,' +
										'.select-course-category-id', function () {

				badgeos_learndash_step_change( $( this ) );

			} );

			// Trigger a change so we properly show/hide our LearnDash menues
			$( '.select-trigger-type' ).change();

			// Inject our custom step details into the update step action
			$( document ).on( 'update_step_data', function ( event, step_details, step ) {
				step_details.learndash_trigger = $( '.select-learndash-trigger', step ).val();
				step_details.learndash_trigger_label = $( '.select-learndash-trigger option', step ).filter( ':selected' ).text();

				step_details.learndash_quiz_id = $( '.select-quiz-id', step ).val();
				step_details.learndash_quiz_grade = $( '.input-quiz-grade', step ).val();
				step_details.learndash_lesson_id = $( '.select-lesson-id', step ).val();
				step_details.learndash_course_id = $( '.select-course-id', step ).val();
				step_details.learndash_course_category_id = $( '.select-course-category-id', step ).val();
			} );

		} );

		function badgeos_learndash_step_change( $this ) {
				var trigger_parent = $this.parent(),
					trigger_value = trigger_parent.find( '.select-learndash-trigger' ).val();

				// Quiz specific
				trigger_parent.find( '.select-quiz-id' )
					.toggle(
						( 'learndash_quiz_completed' == trigger_value
						 || 'badgeos_learndash_quiz_completed_specific' == trigger_value
						 || 'badgeos_learndash_quiz_completed_fail' == trigger_value )
					);

				// Lesson specific
				trigger_parent.find( '.select-lesson-id' )
					.toggle( 'learndash_lesson_completed' == trigger_value );

				// Course specific
				trigger_parent.find( '.select-course-id' )
					.toggle( 'learndash_course_completed' == trigger_value );

				// Course Category specific
				trigger_parent.find( '.select-course-category-id' )
					.toggle( 'badgeos_learndash_course_completed_tag' == trigger_value );

				// Quiz Grade specific
				trigger_parent.find( '.input-quiz-grade' ).parent() // target parent span
					.toggle( 'badgeos_learndash_quiz_completed_specific' == trigger_value );

				if ( ( 'learndash_quiz_completed' == trigger_value
					   && '' != trigger_parent.find( '.select-quiz-id' ).val() )
					 || ( 'badgeos_learndash_quiz_completed_specific' == trigger_value
					   && '' != trigger_parent.find( '.select-quiz-id' ).val() )
					 || ( 'badgeos_learndash_quiz_completed_fail' == trigger_value
					   && '' != trigger_parent.find( '.select-quiz-id' ).val() )
					 || ( 'learndash_lesson_completed' == trigger_value
						  && '' != trigger_parent.find( '.select-lesson-id' ).val() )
					 || ( 'learndash_course_completed' == trigger_value
						  && '' != trigger_parent.find( '.select-course-id' ).val() )
					 || ( 'badgeos_learndash_course_completed_tag' == trigger_value
						  && '' != trigger_parent.find( '.select-course-category-id' ).val() ) ) {
					trigger_parent.find( '.required-count' )
						.val( '1' )
						.prop( 'disabled', true );
				}
		}
	</script>
<?php
}

add_action( 'admin_footer', 'badgeos_learndash_step_js' );