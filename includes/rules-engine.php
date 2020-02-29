<?php
/**
 * Custom Achievement Rules
 *
 * @package BadgeOS LearnDash
 * @author WooNinjas
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://wooninjas.com
 */

/**
 * Load up our LearnDash triggers so we can add actions to them
 */
function badgeos_learndash_load_triggers() {

    /**
     * Grab our LearnDash triggers
     */
    $learndash_triggers = $GLOBALS[ 'badgeos_learndash' ]->triggers;

    if ( !empty( $learndash_triggers ) ) {
        foreach ( $learndash_triggers as $trigger => $trigger_label ) {

            if ( is_array( $trigger_label ) ) {
                $triggers = $trigger_label;

                foreach ( $triggers as $trigger_hook => $trigger_name ) {
                    add_action( $trigger_hook, 'badgeos_learndash_trigger_event', 0, 20 );
                    add_action( $trigger_hook, 'badgeos_learndash_trigger_award_points_event', 0, 20 );
                    add_action( $trigger_hook, 'badgeos_learndash_trigger_deduct_points_event', 0, 20 );
                    add_action( $trigger_hook, 'badgeos_learndash_trigger_ranks_event', 0, 20 );
                }
            } else {
                add_action( $trigger, 'badgeos_learndash_trigger_event', 0, 20 );
                add_action( $trigger, 'badgeos_learndash_trigger_award_points_event', 0, 20 );
                add_action( $trigger, 'badgeos_learndash_trigger_deduct_points_event', 0, 20 );
                add_action( $trigger, 'badgeos_learndash_trigger_ranks_event', 0, 20 );
            }
        }
    }
}
add_action( 'init', 'badgeos_learndash_load_triggers', 0 );

/**
 * Handle community triggers for award points
 */
function badgeos_learndash_trigger_award_points_event() {

    /**
     * Setup all our globals
     */
    global $user_ID, $blog_id, $wpdb;

    $site_id = $blog_id;

    $args = func_get_args();

    /**
     * Grab our current trigger
     */
    $this_trigger = current_filter();

    /**
     * Grab the user ID
     */
    if($this_trigger == 'ld_added_group_access') {
        list($user_id, $group_id) = $args;
    } else {
        $user_id = badgeos_trigger_get_user_id($this_trigger, $args);
    }
    $user_data = get_user_by( 'id', $user_id );

    /**
     * Sanity check, if we don't have a user object, bail here
     */
    if ( ! is_object( $user_data ) )
        return $args[ 0 ];

    /**
     * If the user doesn't satisfy the trigger requirements, bail here\
     */
    if ( ! apply_filters( 'user_deserves_point_award_trigger', true, $user_id, $this_trigger, $site_id, $args ) ) {
        return $args[ 0 ];
    }

    /**
     * Now determine if any badges are earned based on this trigger event
     */
    $triggered_points = $wpdb->get_results( $wpdb->prepare("
			SELECT p.ID as post_id FROM $wpdb->postmeta AS pm INNER JOIN $wpdb->posts AS p ON 
			( p.ID = pm.post_id AND pm.meta_key = '_point_trigger_type' )INNER JOIN $wpdb->postmeta AS pmtrg 
			ON ( p.ID = pmtrg.post_id AND pmtrg.meta_key = '_badgeos_learndash_trigger' ) 
			where p.post_status = 'publish' AND pmtrg.meta_value =  %s 
			",
        $this_trigger
    ) );

    if( !empty( $triggered_points ) ) {
        foreach ( $triggered_points as $point ) {

            $parent_point_id = badgeos_get_parent_id( $point->post_id );

            /**
             * Update hook count for this user
             */
            $new_count = badgeos_points_update_user_trigger_count( $point->post_id, $parent_point_id, $user_id, $this_trigger, $site_id, 'Award', $args );

            badgeos_maybe_award_points_to_user( $point->post_id, $parent_point_id , $user_id, $this_trigger, $site_id, $args );

        }
    }
}

/**
 * Handle community triggers for deduct points
 */
function badgeos_learndash_trigger_deduct_points_event( $args='' ) {

    /**
     * Setup all our globals
     */
    global $user_ID, $blog_id, $wpdb;

    $site_id = $blog_id;

    $args = func_get_args();

    /**
     * Grab our current trigger
     */
    $this_trigger = current_filter();

    /**
     * Grab the user ID
     */
    if($this_trigger == 'ld_added_group_access') {
        list($user_id, $group_id) = $args;
    } else {
        $user_id = badgeos_trigger_get_user_id($this_trigger, $args);
    }
    $user_data = get_user_by( 'id', $user_id );

    /**
     * Sanity check, if we don't have a user object, bail here
     */
    if ( ! is_object( $user_data ) ) {
        return $args[ 0 ];
    }

    /**
     * If the user doesn't satisfy the trigger requirements, bail here
     */
    if ( ! apply_filters( 'user_deserves_point_deduct_trigger', true, $user_id, $this_trigger, $site_id, $args ) ) {
        return $args[ 0 ];
    }

    /**
     * Now determine if any Achievements are earned based on this trigger event
     */
    $triggered_deducts = $wpdb->get_results( $wpdb->prepare(
        "SELECT p.ID as post_id FROM $wpdb->postmeta AS pm INNER JOIN $wpdb->posts AS p ON 
		( p.ID = pm.post_id AND pm.meta_key = '_deduct_trigger_type' )INNER JOIN $wpdb->postmeta AS pmtrg 
		ON ( p.ID = pmtrg.post_id AND pmtrg.meta_key = '_badgeos_learndash_trigger' ) 
		where p.post_status = 'publish' AND pmtrg.meta_value =  %s",
        $this_trigger
    ) );

    if( !empty( $triggered_deducts ) ) {
        foreach ( $triggered_deducts as $point ) {

            $parent_point_id = badgeos_get_parent_id( $point->post_id );

            /**
             * Update hook count for this user
             */
            $new_count = badgeos_points_update_user_trigger_count( $point->post_id, $parent_point_id, $user_id, $this_trigger, $site_id, 'Deduct', $args );

            badgeos_maybe_deduct_points_to_user( $point->post_id, $parent_point_id , $user_id, $this_trigger, $site_id, $args );

        }
    }
}

/**
 * Handle community triggers for ranks
 */
function badgeos_learndash_trigger_ranks_event( $args='' ) {

    /**
     * Setup all our globals
     */
    global $user_ID, $blog_id, $wpdb;

    $site_id = $blog_id;

    $args = func_get_args();

    /**
     * Grab our current trigger
     */
    $this_trigger = current_filter();


    /**
     * Grab the user ID
     */
    if($this_trigger == 'ld_added_group_access') {
        list($user_id, $group_id) = $args;
    } else {
        $user_id = badgeos_trigger_get_user_id($this_trigger, $args);
    }
    $user_data = get_user_by( 'id', $user_id );

    /**
     * Sanity check, if we don't have a user object, bail here
     */
    if ( ! is_object( $user_data ) )
        return $args[ 0 ];

    /**
     * If the user doesn't satisfy the trigger requirements, bail here
     */
    if ( ! apply_filters( 'badgeos_user_rank_deserves_trigger', true, $user_id, $this_trigger, $site_id, $args ) )
        return $args[ 0 ];

    /**
     * Now determine if any Achievements are earned based on this trigger event
     */
    $triggered_ranks = $wpdb->get_results( $wpdb->prepare(
        "SELECT p.ID as post_id FROM $wpdb->postmeta AS pm INNER JOIN $wpdb->posts AS p ON 
							( p.ID = pm.post_id AND pm.meta_key = '_rank_trigger_type' )INNER JOIN $wpdb->postmeta AS pmtrg 
							ON ( p.ID = pmtrg.post_id AND pmtrg.meta_key = '_badgeos_learndash_trigger' ) 
							where p.post_status = 'publish' AND pmtrg.meta_value =  %s",
        $this_trigger
    ) );

    if( !empty( $triggered_ranks ) ) {
        foreach ( $triggered_ranks as $rank ) {
            $parent_id = badgeos_get_parent_id( $rank->post_id );
            if( absint($parent_id) > 0) {
                $new_count = badgeos_ranks_update_user_trigger_count( $rank->post_id, $parent_id,$user_id, $this_trigger, $site_id, $args );
                badgeos_maybe_award_rank( $rank->post_id,$parent_id,$user_id, $this_trigger, $site_id, $args );
            }
        }
    }
}

/**
 * Handle each of our LearnDash triggers
 */
function badgeos_learndash_trigger_event() {

    /**
     * Setup all our important variables
     */
    global $blog_id, $wpdb;

    /**
     * Setup args
     */
    $args = func_get_args();

    $userID = get_current_user_id();

    if ( is_array( $args ) && isset( $args[ 'user' ] ) ) {
        if ( is_object( $args[ 'user' ] ) ) {
            $userID = (int) $args[ 'user' ]->ID;
        } else {
            $userID = (int) $args[ 'user' ];
        }
    }

    /**
     * Grab the current trigger
     */
    $this_trigger = current_filter();

    if( trim( $this_trigger ) == 'ld_added_group_access' ) {
        $userID = $args[0];
    } else if( trim( $this_trigger ) == 'badgeos_learndash_purchase_course' ) {
        $userID = $args[0];
    }

    if ( empty( $userID ) ) {
        return;
    }

    $user_data = get_user_by( 'id', $userID );

    if ( empty( $user_data ) ) {
        return;
    }

    /**
     * Now determine if any badges are earned based on this trigger event
     */
    $triggered_achievements = $wpdb->get_results( $wpdb->prepare( "SELECT pm.post_id FROM $wpdb->postmeta as pm inner join $wpdb->posts as p on( pm.post_id = p.ID ) WHERE p.post_status = 'publish' and pm.meta_key = '_badgeos_learndash_trigger' AND pm.meta_value = %s", $this_trigger) );

    if( count( $triggered_achievements ) > 0 ) {
        /**
         * Update hook count for this user
         */
        $new_count = badgeos_update_user_trigger_count( $userID, $this_trigger, $blog_id );

        /**
         * Mark the count in the log entry
         */
        badgeos_post_log_entry( null, $userID, null, sprintf( __( '%1$s triggered %2$s (%3$dx)', 'badgeos-learndash' ), $user_data->user_login, $this_trigger, $new_count ) );
    }

    foreach ( $triggered_achievements as $achievement ) {
        $parents = badgeos_get_achievements( array( 'parent_of' => $achievement->post_id ) );
        if( count( $parents ) > 0 ) {
            if( $parents[0]->post_status == 'publish' ) {
                badgeos_maybe_award_achievement_to_user( $achievement->post_id, $userID, $this_trigger, $blog_id, $args );
            }
        }
    }
}

/**
 * Award LearnDash Quiz Points as Badge Points.
 *
 * @param $quizdata
 * @param $user
 */
function badgeos_learndash_award_quiz_points_as_badge_points( $quizdata, $user ) {



    global $wpdb;

    $passed = isset( $quizdata['pass'] ) ? $quizdata['pass'] : 0;
    $wn_bos_ld_options = get_option( 'wn_bos_ld_options' );
    $bos_ld_quiz_point_type = isset($wn_bos_ld_options['bos_ld_quiz_point_type']) ? $wn_bos_ld_options['bos_ld_quiz_point_type'] : 0;

    if( $wn_bos_ld_options['quiz_points_as_badgeos_points'] == 'no' ) {
        return;
    }

    if( $wn_bos_ld_options['quiz_points_as_badgeos_points'] == 'quiz_score_if_passed'  && !$passed ) {
        return;
    }

    if( empty($bos_ld_quiz_point_type) ) {
        return;
    }

    $quiz_score_multiplier = intval( $wn_bos_ld_options['badgeos_learndash_quiz_score_multiplier'] ) ? intval( $wn_bos_ld_options['badgeos_learndash_quiz_score_multiplier'] ) : 1;

    /**
     * Get Quiz Total Points & Current USER ID
     */
    $user_id 		= $user->ID;
    $total_points 	= absint( $quizdata['total_points'] );
    $points_type    = $bos_ld_quiz_point_type;
    $point_value    = 0;
    $quiz_id        = $quizdata['quiz']->ID;

    $count = $wpdb->get_var(
        $wpdb->prepare( "SELECT COUNT(*) AS count FROM {$wpdb->prefix}badgeos_points WHERE achievement_id = %d  AND credit_id =%d AND user_id = %d AND this_trigger = %s;", $quiz_id, $points_type, $user_id, 'bos_ld_quiz_based' )
    );

    /**
     * Points already awarded
     */
    if( $count > 0 ) {
        return;
    }


    if( $quiz_score_multiplier > 0 )
        $point_value = $total_points * $quiz_score_multiplier;

    /**
     * Award LearnDash Quiz Points to User
     */
    if( intval( $total_points ) > 0 ) {
        bos_ld_update_user_points($user_id, $points_type, $point_value, $quiz_id);
    }


}
add_action( 'learndash_quiz_completed', 'badgeos_learndash_award_quiz_points_as_badge_points', 10, 2 );


function bos_ld_update_user_points($user_id, $points_type, $point_value, $quiz_id) {

    if( $points_type != 0 ) {

        $achievement_id = $quiz_id;
        $admin_id       = 0;

        $earned_credits = badgeos_get_points_by_type( $points_type, $user_id );

        badgeos_add_credit( $points_type, $user_id, 'Award', $point_value, 'bos_ld_quiz_based', $admin_id , 0 , $achievement_id );

        $total_points = badgeos_recalc_total_points( $user_id );

        badgeos_log_users_points( $user_id, $point_value, $total_points, $admin_id, $achievement_id,'Award', $points_type );

        // Available action for triggering other processes
        do_action( 'badgeos_update_users_points', $user_id, $point_value, $total_points, $admin_id, $achievement_id );

        /**
         * Available action for triggering other processes
         */
        do_action( 'badgeos_unlock_user_rank', $user_id, 'Award', $point_value, 0, $points_type, 'credit_based', $achievement_id, 0 );

        // Maybe award some points-based badges
        foreach ( badgeos_get_points_based_achievements() as $achievement ) {
            badgeos_maybe_award_achievement_to_user( $achievement->ID, $user_id );
        }
    }
}

/**
 * Get string between two given strings
 *
 * @param $string
 * @param $start
 * @param $end
 * @return bool|string
 */
function badgeos_ld_get_string_between( $string, $start, $end ) {

    $string = ' ' . $string;
    $ini = strpos( $string, $start );
    if ( $ini == 0 ) return '';
    $ini += strlen( $start );
    $len = strpos( $string, $end, $ini ) - $ini;
    return substr( $string, $ini, $len );
}


/**
 * Check if user deserves a LearnDash trigger step
 *
 * @param $return
 * @param $user_id
 * @param $achievement_id
 * @param string $this_trigger
 * @param int $site_id
 * @param array $args
 * @return bool
 */
function badgeos_learndash_user_deserves_learndash_step( $return, $user_id, $achievement_id, $this_trigger = '', $site_id = 1, $args = array() ) {

    /**
     * If we're not dealing with a step, bail here
     */
    if ( 'step' != get_post_type( $achievement_id ) ) {
        return $return;
    }

    /**
     * Grab our step requirements
     */
    $requirements = badgeos_get_step_requirements( $achievement_id );
    /**
     * If the step is triggered by LearnDash actions...
     */
    if ( 'learndash_trigger' == $requirements[ 'trigger_type' ] ) {

        /**
         * Do not pass go until we say you can
         */
        $return = false;

        /**
         * Unsupported trigger
         */
        if ( ! isset( $GLOBALS[ 'badgeos_learndash' ]->triggers[ $this_trigger ] ) ) {
            return $return;
        }

        /**
         * LearnDash requirements not met yet
         */
        $learndash_triggered = false;

        /**
         * Set our main vars
         */
        $learndash_trigger = $requirements['learndash_trigger'];
        $object_id = $requirements['learndash_object_id'];

        /**
         * Extra arg handling for further expansion
         */
        $object_arg1 = null;

        if ( isset( $requirements['learndash_object_arg1'] ) )
            $object_arg1 = $requirements['learndash_object_arg1'];

        /**
         * Object-specific triggers
         */
        $learndash_object_triggers = array(
            'learndash_quiz_completed',
            'badgeos_learndash_quiz_completed_specific',
            'badgeos_learndash_quiz_completed_fail',
            'learndash_topic_completed',
            'learndash_lesson_completed',
            'learndash_course_completed'
        );

        /**
         * Group
         */
        $learndash_group_triggers = array(
            'ld_added_group_access'
        );

        /**
         * purchase
         */
        $learndash_purchase_triggers = array(
            'badgeos_learndash_purchase_course'
        );

        /**
         * Category-specific triggers
         */
        $learndash_category_triggers = array(
            'badgeos_learndash_quiz_completed_tag',
            'badgeos_learndash_course_completed_tag',
            'badgeos_learndash_topic_completed_tag',
            'badgeos_learndash_lesson_completed_tag',
            'badgeos_learndash_quiz_completed_fail_tag'
        );

        /**
         * Quiz-specific triggers
         */
        $learndash_quiz_triggers = array(
            'learndash_quiz_completed',
            'badgeos_learndash_quiz_completed_tag',
            'badgeos_learndash_quiz_completed_fail_tag',
            'badgeos_learndash_quiz_completed_specific',
            'badgeos_learndash_quiz_completed_fail'
        );

        /**
         * Triggered object ID (used in these hooks, generally 2nd arg)
         */
        $triggered_object_id = 0;

        $arg_data = $args[ 0 ];

        if ( is_array( $arg_data ) ) {
            if ( isset( $arg_data[ 'quiz' ] ) ) {
                $triggered_object_id = (int) $arg_data[ 'quiz' ]->ID;
            } elseif ( isset( $arg_data[ 'topic' ] ) ) {
                $triggered_object_id = (int) $arg_data[ 'topic' ]->ID;
            } elseif ( isset( $arg_data[ 'lesson' ] ) ) {
                $triggered_object_id = (int) $arg_data[ 'lesson' ]->ID;
            } elseif ( isset( $arg_data[ 'course' ] ) ) {
                $triggered_object_id = (int) $arg_data[ 'course' ]->ID;
            }
        }

        /**
         * Use basic trigger logic if no object set
         */
        if ( empty( $object_id ) && !in_array( $learndash_trigger, $learndash_category_triggers ) && ! in_array( $learndash_trigger, $learndash_group_triggers ) && ! in_array( $learndash_trigger, $learndash_purchase_triggers )  ) {
            $learndash_triggered = true;

        } elseif ( in_array( $learndash_trigger, $learndash_object_triggers ) && $triggered_object_id == $object_id ) {
            $learndash_triggered = true;
        } elseif ( in_array( $learndash_trigger, $learndash_purchase_triggers ) ) {
            $post_id = $args[1];

            if( intval($object_id) == 0 ) {
                $learndash_triggered = true;

            } else if( intval( $object_id ) > 0 ) {

                $my_post = get_post( $post_id );
                $post_title_array 	= explode( ' ', $my_post->post_title );

                if( $post_title_array[0] == 'Course' ) {
                    $course_title 		= badgeos_ld_get_string_between( $my_post->post_title, 'Course ', ' Purchased' );
                }

                if( empty( $course_title ) ) {
                    return;
                }

                $course_obj 		= get_page_by_title( $course_title, OBJECT, 'sfwd-courses' );
                $course_id 			= $course_obj->ID;

                if( intval( $object_id ) == intval( $course_id ) ) {
                    $learndash_triggered = true;
                }
            }
        } elseif ( in_array( $learndash_trigger, $learndash_group_triggers ) ) {
            $post_id = $args[1];
            if( function_exists( 'learndash_is_user_in_group' ) && learndash_is_user_in_group( $user_id, $object_id ) && intval( $object_id ) > 0 && intval( $object_id ) == intval( $post_id ) ) {

                $learndash_triggered = true;
            } else if( intval( $object_id ) == 0 ) {
                $learndash_triggered = true;
            } else {
                $learndash_triggered = false;
            }
        } elseif ( in_array( $learndash_trigger, $learndash_category_triggers ) && has_term( $object_id, 'ld_course_tag', $triggered_object_id ) ) {
            $learndash_triggered = true;
        } elseif ( in_array( $learndash_trigger, $learndash_category_triggers ) && has_term( $object_id, 'ld_topic_tag', $triggered_object_id ) ) {
            $learndash_triggered = true;
        } elseif ( in_array( $learndash_trigger, $learndash_category_triggers ) && has_term( $object_id, 'ld_lesson_tag', $triggered_object_id ) ) {
            $learndash_triggered = true;
        } elseif ( in_array( $learndash_trigger, $learndash_category_triggers ) && has_term( $object_id, 'ld_quiz_tag', $triggered_object_id ) ) {
            $learndash_triggered = true;
        } elseif ( in_array( $learndash_trigger, $learndash_category_triggers ) && has_term( $object_id, 'post_tag', $triggered_object_id ) ) {
            $learndash_triggered = true;
        }


        /**
         * Quiz triggers
         */
        if ( $learndash_triggered && in_array( $learndash_trigger, $learndash_quiz_triggers ) ) {

            /**
             * Check for fail
             */
            if ( 'badgeos_learndash_quiz_completed_fail' == $learndash_trigger ) {
                if ( $arg_data[ 'pass' ] ) {
                    $learndash_triggered = false;
                }
            } elseif ( 'badgeos_learndash_quiz_completed_specific' == $learndash_trigger ) {
                $percentage = (int) $arg_data[ 'percentage' ];
                $object_arg1 = (int) $object_arg1;

                if ( $percentage < $object_arg1 ) {
                    $learndash_triggered = false;
                }
            } elseif( 'badgeos_learndash_quiz_completed_tag' == $learndash_trigger ){
                if( !$arg_data[ 'pass' ] ) {
                    $learndash_triggered = false;
                }
            } elseif ( 'badgeos_learndash_quiz_completed_fail_tag' == $learndash_trigger ) {
                if ( $arg_data[ 'pass' ] ) {
                    $learndash_triggered = false;
                }
            } elseif ( !$arg_data[ 'pass' ] ) {
                $learndash_triggered = false;
            }
        }

        /**
         * LearnDash requirements met
         */
        if ( $learndash_triggered ) {

            $parent_achievement = badgeos_get_parent_of_achievement( $achievement_id );
            $parent_id = $parent_achievement->ID;

            $user_crossed_max_allowed_earnings = badgeos_achievement_user_exceeded_max_earnings( $user_id, $parent_id );
            if ( ! $user_crossed_max_allowed_earnings ) {
                $minimum_activity_count = absint( get_post_meta( $achievement_id, '_badgeos_count', true ) );
                if( ! isset( $minimum_activity_count ) || empty( $minimum_activity_count ) )
                    $minimum_activity_count = 1;

                $count_step_trigger = $requirements["learndash_trigger"];
                $activities = badgeos_get_user_trigger_count( $user_id, $count_step_trigger );
                $relevant_count = absint( $activities );

                $achievements = badgeos_get_user_achievements(
                    array(
                        'user_id' => absint( $user_id ),
                        'achievement_id' => $achievement_id
                    )
                );

                $total_achievments = count( $achievements );
                $used_points = intval( $minimum_activity_count ) * intval( $total_achievments );
                $remainder = intval( $relevant_count ) - $used_points;

                $return  = 0;
                if ( absint( $remainder ) >= $minimum_activity_count )
                    $return  = $remainder;

                return $return;
            } else {

                return 0;
            }
        }
    }

    return $return;
}
add_filter( 'user_deserves_achievement', 'badgeos_learndash_user_deserves_learndash_step', 15, 6 );

function badgeos_learndash_purchase_any_course( $post_ID ) {

    global $blog_id, $wpdb;
    $site_id = $blog_id;

    $userID = get_current_user_id();
    if( intval( $userID ) > 0 ) {

        $post_type = get_post_type( $post_ID );
        if( $post_type != 'sfwd-transactions' ) {
            return;
        }

        $GLOBALS['badgeos']->achievement_types[] = 'step';
        do_action( 'badgeos_learndash_purchase_course', $userID, $post_ID );
    }
}
add_action( 'wp_insert_post', 'badgeos_learndash_purchase_any_course' );


function badgeos_learndash_user_deserves_credit_deduct( $return, $credit_step_id, $credit_parent_id, $user_id, $this_trigger, $site_id, $args ) {

    // Grab our step requirements
    $requirements      = badgeos_get_deduct_step_requirements( $credit_step_id );

    // If we're not dealing with a step, bail here
    $settings = get_option( 'badgeos_settings' );
    if ( trim( $settings['points_deduct_post_type'] ) != get_post_type( $credit_step_id ) ) {
        return $return;
    }

    // If the step is triggered by LearnDash actions...
    if ( 'learndash_trigger' == $requirements[ 'trigger_type' ] ) {
        // Do not pass go until we say you can
        $return = false;

        // Unsupported trigger
        if ( !isset( $GLOBALS[ 'badgeos_learndash' ]->triggers[ $this_trigger ] ) ) {
            return $return;
        }

        /**
         * LearnDash requirements not met yet
         */
        $learndash_triggered = false;

        /**
         * Set our main vars
         */
        $learndash_trigger = $requirements['learndash_trigger'];
        $object_id = $requirements['learndash_object_id'];

        /**
         * Extra arg handling for further expansion
         */
        $object_arg1 = null;

        if ( isset( $requirements['learndash_object_arg1'] ) )
            $object_arg1 = $requirements['learndash_object_arg1'];

        /**
         * Object-specific triggers
         */
        $learndash_object_triggers = array(
            'learndash_quiz_completed',
            'badgeos_learndash_quiz_completed_specific',
            'badgeos_learndash_quiz_completed_fail',
            'learndash_topic_completed',
            'learndash_lesson_completed',
            'learndash_course_completed'
        );

        /**
         * Group
         */
        $learndash_group_triggers = array(
            'ld_added_group_access'
        );

        /**
         * purchase
         */
        $learndash_purchase_triggers = array(
            'badgeos_learndash_purchase_course'
        );

        /**
         * Category-specific triggers
         */
        $learndash_category_triggers = array(
            'badgeos_learndash_quiz_completed_tag',
            'badgeos_learndash_course_completed_tag',
            'badgeos_learndash_topic_completed_tag',
            'badgeos_learndash_lesson_completed_tag',
            'badgeos_learndash_quiz_completed_fail_tag'
        );

        /**
         * Quiz-specific triggers
         */
        $learndash_quiz_triggers = array(
            'learndash_quiz_completed',
            'badgeos_learndash_quiz_completed_tag',
            'badgeos_learndash_quiz_completed_fail_tag',
            'badgeos_learndash_quiz_completed_specific',
            'badgeos_learndash_quiz_completed_fail'
        );

        /**
         * Triggered object ID (used in these hooks, generally 2nd arg)
         */
        $triggered_object_id = 0;

        $arg_data = $args[ 0 ];

        if ( is_array( $arg_data ) ) {
            if ( isset( $arg_data[ 'quiz' ] ) ) {
                $triggered_object_id = (int) $arg_data[ 'quiz' ]->ID;
            } elseif ( isset( $arg_data[ 'topic' ] ) ) {
                $triggered_object_id = (int) $arg_data[ 'topic' ]->ID;
            } elseif ( isset( $arg_data[ 'lesson' ] ) ) {
                $triggered_object_id = (int) $arg_data[ 'lesson' ]->ID;
            } elseif ( isset( $arg_data[ 'course' ] ) ) {
                $triggered_object_id = (int) $arg_data[ 'course' ]->ID;
            }
        }

        /**
         * Use basic trigger logic if no object set
         */
        if ( empty( $object_id ) && !in_array( $learndash_trigger, $learndash_category_triggers ) && ! in_array( $learndash_trigger, $learndash_group_triggers ) && ! in_array( $learndash_trigger, $learndash_purchase_triggers )  ) {
            $learndash_triggered = true;

        } elseif ( in_array( $learndash_trigger, $learndash_object_triggers ) && $triggered_object_id == $object_id ) {
            $learndash_triggered = true;
        } elseif ( in_array( $learndash_trigger, $learndash_purchase_triggers ) ) {
            $post_id = $args[1];

            if( intval($object_id) == 0 ) {
                $learndash_triggered = true;

            } else if( intval( $object_id ) > 0 ) {

                $my_post = get_post( $post_id );
                $post_title_array 	= explode( ' ', $my_post->post_title );

                if( $post_title_array[0] == 'Course' ) {
                    $course_title 		= badgeos_ld_get_string_between( $my_post->post_title, 'Course ', ' Purchased' );
                }

                if( empty( $course_title ) ) {
                    return;
                }

                $course_obj 		= get_page_by_title( $course_title, OBJECT, 'sfwd-courses' );
                $course_id 			= $course_obj->ID;

                if( intval( $object_id ) == intval( $course_id ) ) {
                    $learndash_triggered = true;
                }
            }
        } elseif ( in_array( $learndash_trigger, $learndash_group_triggers ) ) {
            $post_id = $args[1];
            if( function_exists( 'learndash_is_user_in_group' ) && learndash_is_user_in_group( $user_id, $object_id ) && intval( $object_id ) > 0 && intval( $object_id ) == intval( $post_id ) ) {

                $learndash_triggered = true;
            } else if( intval( $object_id ) == 0 ) {
                $learndash_triggered = true;
            } else {
                $learndash_triggered = false;
            }
        } elseif ( in_array( $learndash_trigger, $learndash_category_triggers ) && has_term( $object_id, 'ld_course_tag', $triggered_object_id ) ) {
            $learndash_triggered = true;
        } elseif ( in_array( $learndash_trigger, $learndash_category_triggers ) && has_term( $object_id, 'ld_topic_tag', $triggered_object_id ) ) {
            $learndash_triggered = true;
        } elseif ( in_array( $learndash_trigger, $learndash_category_triggers ) && has_term( $object_id, 'ld_lesson_tag', $triggered_object_id ) ) {
            $learndash_triggered = true;
        } elseif ( in_array( $learndash_trigger, $learndash_category_triggers ) && has_term( $object_id, 'ld_quiz_tag', $triggered_object_id ) ) {
            $learndash_triggered = true;
        } elseif ( in_array( $learndash_trigger, $learndash_category_triggers ) && has_term( $object_id, 'post_tag', $triggered_object_id ) ) {
            $learndash_triggered = true;
        }


        /**
         * Quiz triggers
         */
        if ( $learndash_triggered && in_array( $learndash_trigger, $learndash_quiz_triggers ) ) {

            /**
             * Check for fail
             */
            if ( 'badgeos_learndash_quiz_completed_fail' == $learndash_trigger ) {
                if ( $arg_data[ 'pass' ] ) {
                    $learndash_triggered = false;
                }
            } elseif ( 'badgeos_learndash_quiz_completed_specific' == $learndash_trigger ) {
                $percentage = (int) $arg_data[ 'percentage' ];
                $object_arg1 = (int) $object_arg1;

                if ( $percentage < $object_arg1 ) {
                    $learndash_triggered = false;
                }
            } elseif( 'badgeos_learndash_quiz_completed_tag' == $learndash_trigger ){
                if( !$arg_data[ 'pass' ] ) {
                    $learndash_triggered = false;
                }
            } elseif ( 'badgeos_learndash_quiz_completed_fail_tag' == $learndash_trigger ) {
                if ( $arg_data[ 'pass' ] ) {
                    $learndash_triggered = false;
                }
            } elseif ( !$arg_data[ 'pass' ] ) {
                $learndash_triggered = false;
            }
        }

        // LearnDash requirements met
        if ( $learndash_triggered ) {
            // Grab the trigger count
            $trigger_count = points_get_user_trigger_count( $credit_step_id, $user_id, $this_trigger, $site_id, 'Deduct', $args );

            // If we meet or exceed the required number of checkins, they deserve the step
            if ( 1 == $requirements[ 'count' ] || $requirements[ 'count' ] <= $trigger_count ) {
                // OK, you can pass go now
                $return = true;
            }
        }
    }
    return $return;
}
add_filter( 'badgeos_user_deserves_credit_deduct', 'badgeos_learndash_user_deserves_credit_deduct', 15, 7 );

function badgeos_learndash_user_deserves_credit_award( $return, $credit_step_id, $credit_parent_id, $user_id, $this_trigger, $site_id, $args ) {

    // Grab our step requirements
    $requirements      = badgeos_get_award_step_requirements( $credit_step_id );

    // If we're not dealing with a step, bail here
    $settings = get_option( 'badgeos_settings' );
    if ( trim( $settings['points_award_post_type'] ) != get_post_type( $credit_step_id ) ) {
        return $return;
    }
    // If the step is triggered by LearnDash actions...
    if ( 'learndash_trigger' == $requirements[ 'trigger_type' ] ) {
        // Do not pass go until we say you can
        $return = false;

        // Unsupported trigger
        if ( !isset( $GLOBALS[ 'badgeos_learndash' ]->triggers[ $this_trigger ] ) ) {
            return $return;
        }

        /**
         * LearnDash requirements not met yet
         */
        $learndash_triggered = false;

        /**
         * Set our main vars
         */
        $learndash_trigger = $requirements['learndash_trigger'];
        $object_id = $requirements['learndash_object_id'];

        /**
         * Extra arg handling for further expansion
         */
        $object_arg1 = null;

        if ( isset( $requirements['learndash_object_arg1'] ) )
            $object_arg1 = $requirements['learndash_object_arg1'];

        /**
         * Object-specific triggers
         */
        $learndash_object_triggers = array(
            'learndash_quiz_completed',
            'badgeos_learndash_quiz_completed_specific',
            'badgeos_learndash_quiz_completed_fail',
            'learndash_topic_completed',
            'learndash_lesson_completed',
            'learndash_course_completed'
        );

        /**
         * Group
         */
        $learndash_group_triggers = array(
            'ld_added_group_access'
        );

        /**
         * purchase
         */
        $learndash_purchase_triggers = array(
            'badgeos_learndash_purchase_course'
        );

        /**
         * Category-specific triggers
         */
        $learndash_category_triggers = array(
            'badgeos_learndash_quiz_completed_tag',
            'badgeos_learndash_course_completed_tag',
            'badgeos_learndash_topic_completed_tag',
            'badgeos_learndash_lesson_completed_tag',
            'badgeos_learndash_quiz_completed_fail_tag'
        );

        /**
         * Quiz-specific triggers
         */
        $learndash_quiz_triggers = array(
            'learndash_quiz_completed',
            'badgeos_learndash_quiz_completed_tag',
            'badgeos_learndash_quiz_completed_fail_tag',
            'badgeos_learndash_quiz_completed_specific',
            'badgeos_learndash_quiz_completed_fail'
        );

        /**
         * Triggered object ID (used in these hooks, generally 2nd arg)
         */
        $triggered_object_id = 0;

        $arg_data = $args[ 0 ];

        if ( is_array( $arg_data ) ) {
            if ( isset( $arg_data[ 'quiz' ] ) ) {
                $triggered_object_id = (int) $arg_data[ 'quiz' ]->ID;
            } elseif ( isset( $arg_data[ 'topic' ] ) ) {
                $triggered_object_id = (int) $arg_data[ 'topic' ]->ID;
            } elseif ( isset( $arg_data[ 'lesson' ] ) ) {
                $triggered_object_id = (int) $arg_data[ 'lesson' ]->ID;
            } elseif ( isset( $arg_data[ 'course' ] ) ) {
                $triggered_object_id = (int) $arg_data[ 'course' ]->ID;
            }
        }

        /**
         * Use basic trigger logic if no object set
         */
        if ( empty( $object_id ) && !in_array( $learndash_trigger, $learndash_category_triggers ) && ! in_array( $learndash_trigger, $learndash_group_triggers ) && ! in_array( $learndash_trigger, $learndash_purchase_triggers )  ) {
            $learndash_triggered = true;

        } elseif ( in_array( $learndash_trigger, $learndash_object_triggers ) && $triggered_object_id == $object_id ) {
            $learndash_triggered = true;
        } elseif ( in_array( $learndash_trigger, $learndash_purchase_triggers ) ) {
            $post_id = $args[1];

            if( intval($object_id) == 0 ) {
                $learndash_triggered = true;

            } else if( intval( $object_id ) > 0 ) {

                $my_post = get_post( $post_id );
                $post_title_array 	= explode( ' ', $my_post->post_title );

                if( $post_title_array[0] == 'Course' ) {
                    $course_title 		= badgeos_ld_get_string_between( $my_post->post_title, 'Course ', ' Purchased' );
                }

                if( empty( $course_title ) ) {
                    return;
                }

                $course_obj 		= get_page_by_title( $course_title, OBJECT, 'sfwd-courses' );
                $course_id 			= $course_obj->ID;

                if( intval( $object_id ) == intval( $course_id ) ) {
                    $learndash_triggered = true;
                }
            }
        } elseif ( in_array( $learndash_trigger, $learndash_group_triggers ) ) {
            $post_id = $args[1];
            if( function_exists( 'learndash_is_user_in_group' ) && learndash_is_user_in_group( $user_id, $object_id ) && intval( $object_id ) > 0 && intval( $object_id ) == intval( $post_id ) ) {

                $learndash_triggered = true;
            } else if( intval( $object_id ) == 0 ) {
                $learndash_triggered = true;
            } else {
                $learndash_triggered = false;
            }
        } elseif ( in_array( $learndash_trigger, $learndash_category_triggers ) && has_term( $object_id, 'ld_course_tag', $triggered_object_id ) ) {
            $learndash_triggered = true;
        } elseif ( in_array( $learndash_trigger, $learndash_category_triggers ) && has_term( $object_id, 'ld_topic_tag', $triggered_object_id ) ) {
            $learndash_triggered = true;
        } elseif ( in_array( $learndash_trigger, $learndash_category_triggers ) && has_term( $object_id, 'ld_lesson_tag', $triggered_object_id ) ) {
            $learndash_triggered = true;
        } elseif ( in_array( $learndash_trigger, $learndash_category_triggers ) && has_term( $object_id, 'ld_quiz_tag', $triggered_object_id ) ) {
            $learndash_triggered = true;
        } elseif ( in_array( $learndash_trigger, $learndash_category_triggers ) && has_term( $object_id, 'post_tag', $triggered_object_id ) ) {
            $learndash_triggered = true;
        }


        /**
         * Quiz triggers
         */
        if ( $learndash_triggered && in_array( $learndash_trigger, $learndash_quiz_triggers ) ) {

            /**
             * Check for fail
             */
            if ( 'badgeos_learndash_quiz_completed_fail' == $learndash_trigger ) {
                if ( $arg_data[ 'pass' ] ) {
                    $learndash_triggered = false;
                }
            } elseif ( 'badgeos_learndash_quiz_completed_specific' == $learndash_trigger ) {
                $percentage = (int) $arg_data[ 'percentage' ];
                $object_arg1 = (int) $object_arg1;

                if ( $percentage < $object_arg1 ) {
                    $learndash_triggered = false;
                }
            } elseif( 'badgeos_learndash_quiz_completed_tag' == $learndash_trigger ){
                if( !$arg_data[ 'pass' ] ) {
                    $learndash_triggered = false;
                }
            } elseif ( 'badgeos_learndash_quiz_completed_fail_tag' == $learndash_trigger ) {
                if ( $arg_data[ 'pass' ] ) {
                    $learndash_triggered = false;
                }
            } elseif ( !$arg_data[ 'pass' ] ) {
                $learndash_triggered = false;
            }
        }

        // LearnDash requirements met
        if ( $learndash_triggered ) {
            // Grab the trigger count
            $trigger_count = points_get_user_trigger_count( $credit_step_id, $user_id, $this_trigger, $site_id, 'Award', $args );

            // If we meet or exceed the required number of checkins, they deserve the step
            if ( 1 == $requirements[ 'count' ] || $requirements[ 'count' ] <= $trigger_count ) {
                // OK, you can pass go now
                $return = true;
            }
        }
    }

    return $return;
}
add_filter( 'badgeos_user_deserves_credit_award', 'badgeos_learndash_user_deserves_credit_award', 15, 7 );

function badgeos_learndash_user_deserves_rank_step( $return, $step_id, $rank_id, $user_id, $this_trigger, $site_id, $args ) {
    // Grab our step requirements
    $requirements      = badgeos_get_rank_req_step_requirements( $step_id );

    // If we're not dealing with a step, bail here
    $settings = get_option( 'badgeos_settings' );
    if ( trim( $settings['ranks_step_post_type'] ) != get_post_type( $step_id ) ) {
        return $return;
    }

    // If the step is triggered by LearnDash actions...
    if ( 'learndash_trigger' == $requirements[ 'trigger_type' ] ) {
        // Do not pass go until we say you can
        $return = false;

        // Unsupported trigger
        if ( !isset( $GLOBALS[ 'badgeos_learndash' ]->triggers[ $this_trigger ] ) ) {
            return $return;
        }

        /**
         * LearnDash requirements not met yet
         */
        $learndash_triggered = false;

        /**
         * Set our main vars
         */
        $learndash_trigger = $requirements['learndash_trigger'];
        $object_id = $requirements['learndash_object_id'];

        /**
         * Extra arg handling for further expansion
         */
        $object_arg1 = null;

        if ( isset( $requirements['learndash_object_arg1'] ) )
            $object_arg1 = $requirements['learndash_object_arg1'];

        /**
         * Object-specific triggers
         */
        $learndash_object_triggers = array(
            'learndash_quiz_completed',
            'badgeos_learndash_quiz_completed_specific',
            'badgeos_learndash_quiz_completed_fail',
            'learndash_topic_completed',
            'learndash_lesson_completed',
            'learndash_course_completed'
        );

        /**
         * Group
         */
        $learndash_group_triggers = array(
            'ld_added_group_access'
        );

        /**
         * purchase
         */
        $learndash_purchase_triggers = array(
            'badgeos_learndash_purchase_course'
        );

        /**
         * Category-specific triggers
         */
        $learndash_category_triggers = array(
            'badgeos_learndash_quiz_completed_tag',
            'badgeos_learndash_course_completed_tag',
            'badgeos_learndash_topic_completed_tag',
            'badgeos_learndash_lesson_completed_tag',
            'badgeos_learndash_quiz_completed_fail_tag'
        );

        /**
         * Quiz-specific triggers
         */
        $learndash_quiz_triggers = array(
            'learndash_quiz_completed',
            'badgeos_learndash_quiz_completed_tag',
            'badgeos_learndash_quiz_completed_fail_tag',
            'badgeos_learndash_quiz_completed_specific',
            'badgeos_learndash_quiz_completed_fail'
        );

        /**
         * Triggered object ID (used in these hooks, generally 2nd arg)
         */
        $triggered_object_id = 0;

        $arg_data = $args[ 0 ];

        if ( is_array( $arg_data ) ) {
            if ( isset( $arg_data[ 'quiz' ] ) ) {
                $triggered_object_id = (int) $arg_data[ 'quiz' ]->ID;
            } elseif ( isset( $arg_data[ 'topic' ] ) ) {
                $triggered_object_id = (int) $arg_data[ 'topic' ]->ID;
            } elseif ( isset( $arg_data[ 'lesson' ] ) ) {
                $triggered_object_id = (int) $arg_data[ 'lesson' ]->ID;
            } elseif ( isset( $arg_data[ 'course' ] ) ) {
                $triggered_object_id = (int) $arg_data[ 'course' ]->ID;
            }
        }

        /**
         * Use basic trigger logic if no object set
         */
        if ( empty( $object_id ) && !in_array( $learndash_trigger, $learndash_category_triggers ) && ! in_array( $learndash_trigger, $learndash_group_triggers ) && ! in_array( $learndash_trigger, $learndash_purchase_triggers )  ) {
            $learndash_triggered = true;

        } elseif ( in_array( $learndash_trigger, $learndash_object_triggers ) && $triggered_object_id == $object_id ) {
            $learndash_triggered = true;
        } elseif ( in_array( $learndash_trigger, $learndash_purchase_triggers ) ) {
            $post_id = $args[1];

            if( intval($object_id) == 0 ) {
                $learndash_triggered = true;

            } else if( intval( $object_id ) > 0 ) {

                $my_post = get_post( $post_id );
                $post_title_array 	= explode( ' ', $my_post->post_title );

                if( $post_title_array[0] == 'Course' ) {
                    $course_title 		= badgeos_ld_get_string_between( $my_post->post_title, 'Course ', ' Purchased' );
                }

                if( empty( $course_title ) ) {
                    return;
                }

                $course_obj 		= get_page_by_title( $course_title, OBJECT, 'sfwd-courses' );
                $course_id 			= $course_obj->ID;

                if( intval( $object_id ) == intval( $course_id ) ) {
                    $learndash_triggered = true;
                }
            }
        } elseif ( in_array( $learndash_trigger, $learndash_group_triggers ) ) {
            $post_id = $args[1];
            if( function_exists( 'learndash_is_user_in_group' ) && learndash_is_user_in_group( $user_id, $object_id ) && intval( $object_id ) > 0 && intval( $object_id ) == intval( $post_id ) ) {

                $learndash_triggered = true;
            } else if( intval( $object_id ) == 0 ) {
                $learndash_triggered = true;
            } else {
                $learndash_triggered = false;
            }
        } elseif ( in_array( $learndash_trigger, $learndash_category_triggers ) && has_term( $object_id, 'ld_course_tag', $triggered_object_id ) ) {
            $learndash_triggered = true;
        } elseif ( in_array( $learndash_trigger, $learndash_category_triggers ) && has_term( $object_id, 'ld_topic_tag', $triggered_object_id ) ) {
            $learndash_triggered = true;
        } elseif ( in_array( $learndash_trigger, $learndash_category_triggers ) && has_term( $object_id, 'ld_lesson_tag', $triggered_object_id ) ) {
            $learndash_triggered = true;
        } elseif ( in_array( $learndash_trigger, $learndash_category_triggers ) && has_term( $object_id, 'ld_quiz_tag', $triggered_object_id ) ) {
            $learndash_triggered = true;
        } elseif ( in_array( $learndash_trigger, $learndash_category_triggers ) && has_term( $object_id, 'post_tag', $triggered_object_id ) ) {
            $learndash_triggered = true;
        }


        /**
         * Quiz triggers
         */
        if ( $learndash_triggered && in_array( $learndash_trigger, $learndash_quiz_triggers ) ) {

            /**
             * Check for fail
             */
            if ( 'badgeos_learndash_quiz_completed_fail' == $learndash_trigger ) {
                if ( $arg_data[ 'pass' ] ) {
                    $learndash_triggered = false;
                }
            } elseif ( 'badgeos_learndash_quiz_completed_specific' == $learndash_trigger ) {
                $percentage = (int) $arg_data[ 'percentage' ];
                $object_arg1 = (int) $object_arg1;

                if ( $percentage < $object_arg1 ) {
                    $learndash_triggered = false;
                }
            } elseif( 'badgeos_learndash_quiz_completed_tag' == $learndash_trigger ){
                if( !$arg_data[ 'pass' ] ) {
                    $learndash_triggered = false;
                }
            } elseif ( 'badgeos_learndash_quiz_completed_fail_tag' == $learndash_trigger ) {
                if ( $arg_data[ 'pass' ] ) {
                    $learndash_triggered = false;
                }
            } elseif ( !$arg_data[ 'pass' ] ) {
                $learndash_triggered = false;
            }
        }

        // LearnDash requirements met
        if ( $learndash_triggered ) {

            // Grab the trigger count
            $trigger_count = ranks_get_user_trigger_count( $step_id, $user_id, $this_trigger, $site_id, 'Award', $args );

            // If we meet or exceed the required number of checkins, they deserve the step
            if ( 1 == $requirements[ 'count' ] || $requirements[ 'count' ] <= $trigger_count ) {
                // OK, you can pass go now
                $return = true;
            }
        }
    }

    return $return;
}
add_filter( 'badgeos_user_deserves_rank_step', 'badgeos_learndash_user_deserves_rank_step', 15, 7 );

/**
 * Check if user meets the rank requirement for a given rank
 *
 * @param  bool    $return         	The current status of whether or not the user deserves this rank
 * @param  integer $step_id 		The given rank's post ID
 * @param  integer $rank_id 		The given rank's post ID
 * @param  integer $user_id        	The given user's ID
 * @param  string  $this_trigger
 * @param  string  $site_id
 * @param  array   $args
 * @return bool                    	Our possibly updated earning status
 */
function badgeos_learndash_user_deserves_rank_step_count_callback( $return, $step_id = 0, $rank_id = 0, $user_id = 0, $this_trigger = '', $site_id = 0, $args=array() ) {

    if( ! $return ) {
        return $return;
    }

    /**
     * Only override the $return data if we're working on a step
     */
    $settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
    if ( trim( $settings['ranks_step_post_type'] ) == get_post_type( $step_id ) ) {

        if( ! empty( $this_trigger ) && array_key_exists( $this_trigger, $GLOBALS[ 'badgeos_learndash' ]->triggers ) ) {

            /**
             * Get the required number of checkins for the step.
             */
            $minimum_activity_count = absint( get_post_meta( $step_id, '_badgeos_count', true ) );

            /**
             * Grab the relevent activity for this step
             */
            $current_trigger = get_post_meta( $step_id, '_badgeos_learndash_trigger', true );
            $relevant_count = absint( ranks_get_user_trigger_count( $step_id, $user_id, $current_trigger, $site_id, $args ) );

            /**
             * If we meet or exceed the required number of checkins, they deserve the step
             */
            if ( $relevant_count >= $minimum_activity_count ) {
                $return = true;
            } else {
                $return = false;
            }
        }
    }

    return $return;
}
add_filter( 'badgeos_user_deserves_rank_step_count', 'badgeos_learndash_user_deserves_rank_step_count_callback', 10, 7 );