<?php
/**
 * Adds meta box to achievement types for turning on/off LearnDash activity posts when a user earns an achievement
 *
 * @since 1.1.0
 */
function badgeos_learndash_custom_metaboxes( array $meta_boxes ) {

	// Start with an underscore to hide fields from custom fields list
	$prefix = '_badgeos_';

	// Setup our $post_id, if available
	$post_id = isset( $_GET[ 'post' ] ) ? $_GET[ 'post' ] : 0;

	// New Achievement Types
	$meta_boxes[] = array(
		'id' => 'learndash_achievement_type_data',
		'title' => __( 'LearnDash Member Activity', 'badgeos-learndash' ),
		'pages' => array( 'achievement-type' ), // Post type
		'context' => 'normal',
		'priority' => 'high',
		'show_names' => true, // Show field names on the left
		'fields' => array(
			array(
				'name' => __( 'Profile Achievements', 'badgeos-learndash' ),
				'desc' => ' ' . __( 'Display earned achievements of this type in the LearnDash Student Profile "Achievements" section.', 'badgeos-learndash' ),
				'id' => $prefix . 'show_in_learndash_profile',
				'type' => 'checkbox',
			),
		)
	);

	return $meta_boxes;

}
// No profile integration yet
//add_filter( 'cmb_meta_boxes', 'badgeos_learndash_custom_metaboxes' );

/**
 * Output LearnDash Achievements
 *
 * @param WP_User $user
 *
 * @since 1.1.0
 */
function badgeos_learndash_learner_profile( $user ) {

	$type = null;

	$achievement_types = badgeos_get_network_achievement_types_for_user( $user->ID );

	// Eliminate step cpt from array
	if ( ( $key = array_search( 'step', $achievement_types ) ) !== false ) {
		unset( $achievement_types[ $key ] );

		$achievement_types = array_values( $achievement_types );
	}

	$profile_types = get_posts( array(
		 'post_type' => 'achievement-type',
		 'posts_per_page' => -1,
		 'meta_key' => '_badgeos_show_in_learndash_profile',
		 'meta_value' => '1'
	) );

	$profile_achievement_types = array();

	foreach ( $profile_types as $achievement_type ) {
		$achievement_name_singular = get_post_meta( $achievement_type->ID, '_badgeos_singular_name', true );

		$profile_achievement_types[] = sanitize_title( substr( strtolower( $achievement_name_singular ), 0, 20 ) );
	}

	$achievement_types = array_diff( $achievement_types, $profile_achievement_types );

	$atts = array(
		'type' => implode( ',', $achievement_types ),
		'limit' => '10',
		'show_filter' => 'false',
		'show_search' => 'false',
		'group_id' => '0',
		'user_id' => $user->ID,
		'wpms' => badgeos_ms_show_all_achievements()
	);

	echo '<h2>' . __( 'Achievements Earned', 'badgeos-learndash' ) . '</h2>';

	echo badgeos_achievements_list_shortcode( $atts );

}
// No profile integration yet
//add_action( 'learndash_learner_profile', 'badgeos_learndash_learner_profile', 10, 1 );

/**
 * Filter step titles to link to LearnDash objects
 *
 * @since  1.0.0
 *
 * @param  string $title Our step title
 * @param  object $step  Our step's post object
 *
 * @return string        Our potentially updated title
 */
function badgeos_learndash_step_link_title_to_object( $title = '', $step = null ) {

	// Can't link a link, bro
	if ( false !== strpos( $title, '<a ' ) ) {
		return $title;
	}

	// Grab our step requirements
	$requirements = badgeos_get_step_requirements( $step->ID );

	// If the step is triggered by LearnDash actions...
	if ( 'learndash_trigger' == $requirements[ 'trigger_type' ] ) {
		$url = '';

		// Set our main vars
		$trigger = $requirements[ 'learndash_trigger' ];
		$object_id = $requirements[ 'learndash_object_id' ];

		// Object-specific triggers
		$object_triggers = array(
			'learndash_quiz_completed' => 'sfwd-quiz',
			'badgeos_learndash_quiz_completed_specific' => 'sfwd-quiz',
			'badgeos_learndash_quiz_completed_fail' => 'sfwd-quiz',
			'learndash_lesson_completed' => 'sfwd-lessons',
			'learndash_topic_completed' => 'sfwd-topic',
			'learndash_course_completed' => 'sfwd-courses'
		);

		// Category-specific triggers
		$category_triggers = array(
			'badgeos_learndash_course_completed_tag' => 'post_tag'
		);

		// Object specific
		if ( isset( $object_triggers[ $trigger ] ) ) {
			if ( !empty( $object_id ) ) {
				$url = get_permalink( $object_id );
			}
			else {
				$url = get_post_type_archive_link( $object_triggers[ $trigger ] );
			}
		}
		// Category specific
		elseif ( isset( $category_triggers[ $trigger ] ) ) {
			if ( !empty( $object_id ) ) {
				$url = get_term_link( $object_id );
			}
			/* LOL JK there is no taxonomy archive
			else {
				$url = get_taxonomy_archive_link( $object_triggers[ $trigger ] );
			}*/
		}

		// If we have a URL, update the title to link to it
		if ( !empty( $url ) ) {
			$title = '<a href="' . esc_url( $url ) . '">' . $title . '</a>';
		}
	}

	return $title;

}
add_filter( 'badgeos_step_title_display', 'badgeos_learndash_step_link_title_to_object', 11, 2 );