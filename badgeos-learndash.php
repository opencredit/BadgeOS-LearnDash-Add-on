<?php
/**
 * Plugin Name: BadgeOS LearnDash Add-On
 * Plugin URI: https://badgeos.org/downloads/learndash-add-on/
 * Description: This BadgeOS add-on integrates BadgeOS features with LearnDash
 * Tags: learndash, badgeos, badgeos-learndash-integration, learndash-gamification
 * Author: LearningTimes, LLC
 * Version: 1.1
 * Author URI: http://www.learningtimes.com/
 * License: GNU AGPLv3
 * License URI: http://www.gnu.org/licenses/agpl-3.0.html
 */

/*
 * Copyright © 2020 Credly, LLC
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU Affero General
 * Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>;.
*/

class BadgeOS_LearnDash {

	/**
	 * Plugin Basename
	 *
	 * @var string
	 */
	public $basename = '';

	/**
	 * Plugin Directory Path
	 *
	 * @var string
	 */
	public $directory_path = '';

	/**
	 * Plugin Directory URL
	 *
	 * @var string
	 */
	public $directory_url = '';

	/**
	 * BadgeOS LearnDash Triggers
	 *
	 * @var array
	 */
	public $triggers = array();

	/**
	 * Actions to forward for splitting an action up
	 *
	 * @var array
	 */
	public $actions = array();

    /**
     * BadgeOS_LearnDash constructor.
     */
	function __construct() {

		// Define plugin constants
		$this->basename = plugin_basename( __FILE__ );
		$this->directory_path = plugin_dir_path( __FILE__ );
		$this->directory_url = plugin_dir_url( __FILE__ );

		// If BadgeOS is unavailable, deactivate our plugin
		add_action( 'admin_notices', array( $this, 'maybe_disable_plugin' ) );

		// LearnDash Action Hooks
        $this->triggers = array(
            'learndash_quiz_completed' => __( 'Passed Quiz', 'badgeos-learndash' ),
            'badgeos_learndash_quiz_completed_tag' => __( 'Passed Quiz from a Tag', 'badgeos-learndash' ),
            'badgeos_learndash_quiz_completed_specific' => __( 'Minimum % Grade on a Quiz', 'badgeos-learndash' ),
            'badgeos_learndash_quiz_completed_fail' => __( 'Fails Quiz', 'badgeos-learndash' ),
            'badgeos_learndash_quiz_completed_fail_tag' => __( 'Fails Quiz from a Tag', 'badgeos-learndash' ),
            'learndash_topic_completed' => __( 'Completed Topic', 'badgeos-learndash' ),
            'badgeos_learndash_topic_completed_tag' => __( 'Completed Topic from a Tag', 'badgeos-learndash' ),
            'learndash_lesson_completed' => __( 'Completed Lesson', 'badgeos-learndash' ),
            'badgeos_learndash_lesson_completed_tag' => __( 'Completed Lesson from a Tag', 'badgeos-learndash' ),
            'learndash_course_completed' => __( 'Completed Course', 'badgeos-learndash' ),
            'badgeos_learndash_course_completed_tag' => __( 'Completed Course from a Tag', 'badgeos-learndash' ),
            'badgeos_learndash_purchase_course' => __( 'On Purchase of Course(s)', 'badgeos-learndash' ),
            'ld_added_group_access' => __( 'On Group Registration', 'badgeos-learndash' )
        );

		// Actions that we need split up
		$this->actions = array(
            'learndash_course_completed' =>  'badgeos_learndash_course_completed_tag',
            'learndash_topic_completed' => 'badgeos_learndash_topic_completed_tag',
            'learndash_lesson_completed' => 'badgeos_learndash_lesson_completed_tag',
            'learndash_quiz_completed' => array(
                'actions' => array(
                    'badgeos_learndash_quiz_completed_specific',
                    'badgeos_learndash_quiz_completed_fail',
                    'badgeos_learndash_quiz_completed_tag',
                    'badgeos_learndash_quiz_completed_fail_tag'
                )
            )

			/*
			 * Default action split will be badgeos_learndash_{$action}, can set multiple actions with 'actions'
			 *
			 * 'original_action' => array(
			 * 	'priority' => 12,
			 * 	'accepted_args' => 5,
			 * 	'actions' => array(
			 * 		'another_action1'
			 * 		'another_action2'
			 * 		'another_action3'
			 * 	)
			 * )
			 *
			 *
			 * shorthand forwarding to a single action
			 *
			 * 'original_action' => 'another_action'
			 */
		);

        add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 11 );

	}

	/**
	 * Check if BadgeOS is available
	 *
	 * @since  1.0.0
	 * @return bool True if BadgeOS is available, false otherwise
	 */
	public static function meets_requirements() {

		if ( !class_exists( 'BadgeOS' ) || !function_exists( 'badgeos_get_user_earned_achievement_types' ) ) {
			return false;
		}
		elseif ( !class_exists( 'SFWD_LMS' ) ) {
			return false;
		}

		return true;

	}

	/**
	 * Generate a custom error message and deactivates the plugin if we don't meet requirements
	 *
	 * @since 1.0.0
	 */
	public function maybe_disable_plugin() {

		if ( !$this->meets_requirements() ) {

			$badgeos_activated = class_exists( 'BadgeOS' ) && function_exists( 'badgeos_get_user_earned_achievement_types' );
			$learndash_activated = class_exists( 'SFWD_LMS' );

			if ( !$badgeos_activated || !$learndash_activated ) {

			    unset($_GET['activate']);
			    $message = __('<div id="message" class="error"><p><strong>BadgeOS LearnDash Add-On</strong> requires both <a href="%s" target="_blank">%s</a> and <a href="%s" target="_blank">%s</a> add-ons to be activated.</p></div>', 'badgeos-learndash');
				echo sprintf($message,
                    'https://badgeos.org/',
                    'BadgeOS',
				    'https://www.learndash.com/',
                    'LearnDash'
                );
			}

			// Deactivate our plugin
			deactivate_plugins( $this->basename );
		}
	}

	/**
	 * Load the plugin textdomain and include files if plugin meets requirements
	 *
	 * @since 1.0.0
	 */
	public function plugins_loaded() {
		// Load translations
		load_plugin_textdomain( 'badgeos-learndash', false, dirname( $this->basename ) . '/languages/' );

		if ( $this->meets_requirements() ) {
			require_once( $this->directory_path . '/includes/rules-engine.php' );
			require_once( $this->directory_path . '/includes/steps-ui.php' );

			$this->action_forwarding();
		}

        if( file_exists( $this->directory_path . '/includes/admin-settings.php' ) ) {
            require_once( $this->directory_path . '/includes/admin-settings.php' );
        }
	}

	/**
	 * Forward WP actions into a new set of actions
	 *
	 * @since 1.0.0
	 */
	public function action_forwarding() {
		foreach ( $this->actions as $action => $args ) {
			$priority = 10;
			$accepted_args = 20;

			if ( is_array( $args ) ) {
				if ( isset( $args[ 'priority' ] ) ) {
					$priority = $args[ 'priority' ];
				}

				if ( isset( $args[ 'accepted_args' ] ) ) {
					$accepted_args = $args[ 'accepted_args' ];
				}
			}

			add_action( $action, array( $this, 'action_forward' ), $priority, $accepted_args );
		}
	}

	/**
	 * Forward a specific WP action into a new set of actions
	 *
	 * @return mixed Action return
	 *
	 * @since 1.0.0
	 */
	public function action_forward() {
		$action = current_filter();
		$args = func_get_args();

		if ( isset( $this->actions[ $action ] ) ) {
			if ( is_array( $this->actions[ $action ] )
				 && isset( $this->actions[ $action ][ 'actions' ] ) && is_array( $this->actions[ $action ][ 'actions' ] )
				 && !empty( $this->actions[ $action ][ 'actions' ] ) ) {
				foreach ( $this->actions[ $action ][ 'actions' ] as $new_action ) {
					if ( 0 !== strpos( $new_action, strtolower( __CLASS__ ) . '_' ) ) {
						$new_action = strtolower( __CLASS__ ) . '_' . $new_action;
					}

					$action_args = $args;

					array_unshift( $action_args, $new_action );

					call_user_func_array( 'do_action', $action_args );
				}

				return null;
			}
			elseif ( is_string( $this->actions[ $action ] ) ) {
				$action =  $this->actions[ $action ];
			}
		}

		if ( 0 !== strpos( $action, strtolower( __CLASS__ ) . '_' ) ) {
			$action = strtolower( __CLASS__ ) . '_' . $action;
		}

		array_unshift( $args, $action );

		return call_user_func_array( 'do_action', $args );
	}

}

$GLOBALS[ 'badgeos_learndash' ] = new BadgeOS_LearnDash();