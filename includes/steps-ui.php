<?php
/**
 * Custom Achievement Steps UI.
 *
 * @package BadgeOS LearnDash pro
 * @subpackage Achievements
 * @author Credly, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Update badgeos_get_step_requirements to include our custom requirements.
 *
 * @param $requirements
 * @param $step_id
 * @return mixed
 */
function badgeos_learndash_step_requirements( $requirements, $step_id ) {

    /**
     * Add our new requirements to the list
     */
    $requirements[ 'learndash_trigger' ] = get_post_meta( $step_id, '_badgeos_learndash_trigger', true );
    $requirements[ 'learndash_object_id' ] = (int) get_post_meta( $step_id, '_badgeos_learndash_object_id', true );
    $requirements[ 'learndash_object_arg1' ] = (int) get_post_meta( $step_id, '_badgeos_learndash_object_arg1', true );

    return $requirements;
}
add_filter( 'badgeos_get_deduct_step_requirements', 'badgeos_learndash_step_requirements', 10, 2 );
add_filter( 'badgeos_get_rank_req_step_requirements', 'badgeos_learndash_step_requirements', 10, 2 );
add_filter( 'badgeos_get_award_step_requirements', 'badgeos_learndash_step_requirements', 10, 2 );
add_filter( 'badgeos_get_step_requirements', 'badgeos_learndash_step_requirements', 10, 2 );

/**
 * Filter the BadgeOS Triggers selector with our own options.
 *
 * @param $triggers
 * @return mixed
 */
function badgeos_learndash_activity_triggers( $triggers ) {

    $triggers[ 'learndash_trigger' ] = __( 'LearnDash Activity', 'badgeos-learndash' );
    return $triggers;
}
add_filter( 'badgeos_activity_triggers', 'badgeos_learndash_activity_triggers' );
add_filter( 'badgeos_award_points_activity_triggers', 'badgeos_learndash_activity_triggers' );
add_filter( 'badgeos_deduct_points_activity_triggers', 'badgeos_learndash_activity_triggers' );
add_filter( 'badgeos_ranks_req_activity_triggers', 'badgeos_learndash_activity_triggers' );

/**
 * Add LearnDash Triggers selector to the Steps UI.
 *
 * @param $step_id
 * @param $post_id
 */
function badgeos_learndash_step_learndash_trigger_select( $step_id, $post_id ) {

    /**
     * Setup our select input
     */
    echo '<select name="learndash_trigger" class="select-learndash-trigger">';
    echo '<option value="">' . __( 'Select a LearnDash Trigger', 'badgeos-learndash' ) . '</option>';

    /**
     * Loop through all of our LearnDash trigger groups
     */
    $current_trigger = get_post_meta( $step_id, '_badgeos_learndash_trigger', true );

    $learndash_triggers = $GLOBALS[ 'badgeos_learndash' ]->triggers;

    if ( !empty( $learndash_triggers ) ) {
        foreach ( $learndash_triggers as $trigger => $trigger_label ) {
            if ( is_array( $trigger_label ) ) {
                $optgroup_name = $trigger;
                $triggers = $trigger_label;

                echo '<optgroup label="' . esc_attr( $optgroup_name ) . '">';

                /**
                 * Loop through each trigger in the group
                 */
                foreach ( $triggers as $trigger_hook => $trigger_name ) {
                    echo '<option' . selected( $current_trigger, $trigger_hook, false ) . ' value="' . esc_attr( $trigger_hook ) . '">' . esc_html( $trigger_name ) . '</option>';
                }
                echo '</optgroup>';
            } else {
                echo '<option' . selected( $current_trigger, $trigger, false ) . ' value="' . esc_attr( $trigger ) . '">' . esc_html( $trigger_label ) . '</option>';
            }
        }
    }

    echo '</select>';

}
add_action( 'badgeos_steps_ui_html_after_trigger_type', 'badgeos_learndash_step_learndash_trigger_select', 10, 2 );
add_action( 'badgeos_award_steps_ui_html_after_achievement_type', 'badgeos_learndash_step_learndash_trigger_select', 10, 2 );
add_action( 'badgeos_deduct_steps_ui_html_after_trigger_type', 'badgeos_learndash_step_learndash_trigger_select', 10, 2 );
add_action( 'badgeos_rank_req_steps_ui_html_after_trigger_type', 'badgeos_learndash_step_learndash_trigger_select', 10, 2 );

/**
 * Add a BuddyPress group selector to the Steps UI.
 *
 * @param $step_id
 * @param $post_id
 */
function badgeos_learndash_step_etc_select( $step_id, $post_id ) {

    $current_trigger = get_post_meta( $step_id, '_badgeos_learndash_trigger', true );
    $current_object_id = (int) get_post_meta( $step_id, '_badgeos_learndash_object_id', true );
    $current_object_arg1 = (int) get_post_meta( $step_id, '_badgeos_learndash_object_arg1', true );

    /**
     * Quizes
     */
    echo '<select name="badgeos_learndash_quiz_id" class="select-quiz-id">';
    echo '<option value="">' . __( 'Any Quiz', 'badgeos-learndash' ) . '</option>';

    /**
     * Loop through all objects
     */
    $objects = get_posts( array(
        'post_type' => 'sfwd-quiz',
        'post_status' => 'publish',
        'posts_per_page' => -1
    ) );

    if ( !empty( $objects ) ) {
        foreach ( $objects as $object ) {
            $selected = '';

            if ( in_array( $current_trigger, array( 'learndash_quiz_completed', 'badgeos_learndash_quiz_completed_specific','badgeos_learndash_quiz_completed_fail' ) ) )
                $selected = selected( $current_object_id, $object->ID, false );

            echo '<option' . $selected . ' value="' . $object->ID . '">' . esc_html( get_the_title( $object->ID ) ) . '</option>';
        }
    }

    echo '</select>';

    /**
     * Grade input
     */
    $grade = 100;

    if ( in_array( $current_trigger, array( 'badgeos_learndash_quiz_completed_specific' ) ) )
        $grade = (int) $current_object_arg1;

    if ( empty( $grade ) )
        $grade = 100;

    echo '<span><input name="badgeos_learndash_quiz_grade" class="input-quiz-grade" type="text" value="' . $grade . '" size="3" maxlength="3" placeholder="100" />%</span>';

    /**
     * Topics
     */
    echo '<select name="badgeos_learndash_topic_id" class="select-topic-id">';
    echo '<option value="">' . __( 'Any Topic', 'badgeos-learndash' ) . '</option>';

    /**
     * Loop through all objects
     */
    $topics = get_posts( array(
        'post_type' => 'sfwd-topic',
        'post_status' => 'publish',
        'posts_per_page' => -1
    ) );

    if ( !empty( $topics ) ) {
        foreach ( $topics as $topic ) {
            $selected = '';

            if ( in_array( $current_trigger, array( 'learndash_topic_completed' ) ) )
                $selected = selected( $current_object_id, $topic->ID, false );

            echo '<option' . $selected . ' value="' . $topic->ID . '">' . esc_html( get_the_title( $topic->ID ) ) . '</option>';
        }
    }

    echo '</select>';

    /**
     * Lessons
     */
    echo '<select name="badgeos_learndash_lesson_id" class="select-lesson-id">';
    echo '<option value="">' . __( 'Any Lesson', 'badgeos-learndash' ) . '</option>';

    /**
     * Loop through all objects
     */
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

    /**
     * Courses
     */
    echo '<select name="badgeos_learndash_course_id" class="select-course-id">';
    echo '<option value="">' . __( 'Any Course', 'badgeos-learndash' ) . '</option>';

    /**
     * Loop through all objects
     */
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

    /**
     * Course Category
     */
    $ld_courses_settings = get_option( 'learndash_settings_courses_taxonomies' );

    echo '<select name="badgeos_learndash_course_category_id" class="select-course-category-id">';
    echo '<option value="">' . __( 'Any Course Tag', 'badgeos-learndash' ) . '</option>';

    /**
     * Loop through all objects
     */
    $objects = '';

    $objects_post = '';

    if( $ld_courses_settings['wp_post_tag'] == 'yes' ) {
        $objects_post  = get_terms( 'post_tag', array(
            'hide_empty' => false
        ) );
    }

    if( taxonomy_exists( 'ld_course_tag' ) ) {
        $objects = get_terms( 'ld_course_tag', array(
            'hide_empty' => false
        ) );
    }

    if ( !empty( $objects_post ) ) {
        foreach ( $objects_post as $object ) {
            $selected = '';

            if ( in_array( $current_trigger, array( 'badgeos_learndash_course_completed_tag' ) ) )
                $selected = selected( $current_object_id, $object->term_id, false );

            echo '<option' . $selected . ' value="' . $object->term_id . '">' . esc_html( $object->name ) . '</option>';
        }
    }

    if ( !empty( $objects ) ) {
        foreach ( $objects as $object ) {
            $selected = '';

            if ( in_array( $current_trigger, array( 'badgeos_learndash_course_completed_tag' ) ) )
                $selected = selected( $current_object_id, $object->term_id, false );

            echo '<option' . $selected . ' value="' . $object->term_id . '">' . esc_html( $object->name ) . '</option>';
        }
    }

    echo '</select>';

    /**
     * Topic Category
     */
    $ld_topics_settings = get_option( 'learndash_settings_topics_taxonomies' );

    echo '<select name="badgeos_learndash_topic_tag_id" class="select-topic-tag-id">';
    echo '<option value="">' . __( 'Any topic Tag', 'badgeos-learndash' ) . '</option>';

    /**
     * Loop through all objects
     */
    $objects = '';

    $objects_post = '';

    if( $ld_topics_settings['wp_post_tag'] == 'yes' ) {
        $objects_post  = get_terms( 'post_tag', array(
            'hide_empty' => false
        ) );
    }

    if( taxonomy_exists( 'ld_topic_tag' ) ) {
        $objects = get_terms( 'ld_topic_tag', array(
            'hide_empty' => false
        ) );
    }

    if ( !empty( $objects_post ) ) {
        foreach ( $objects_post as $object ) {
            $selected = '';

            if ( in_array( $current_trigger, array( 'badgeos_learndash_topic_completed_tag' ) ) )
                $selected = selected( $current_object_id, $object->term_id, false );

            echo '<option' . $selected . ' value="' . $object->term_id . '">' . esc_html( $object->name ) . '</option>';
        }
    }

    if ( !empty( $objects ) ) {
        foreach ( $objects as $object ) {
            $selected = '';

            if ( in_array( $current_trigger, array( 'badgeos_learndash_topic_completed_tag' ) ) )
                $selected = selected( $current_object_id, $object->term_id, false );

            echo '<option' . $selected . ' value="' . $object->term_id . '">' . esc_html( $object->name ) . '</option>';
        }
    }

    echo '</select>';

    /**
     * get settings for lesson taxanomies
     */
    $ld_lessons_settings = get_option( 'learndash_settings_lessons_taxonomies' );

    echo '<select name="badgeos_learndash_lesson_tag_id" class="select-lesson-tag-id">';
    echo '<option value="">' . __( 'Any Lesson Tag', 'badgeos-learndash' ) . '</option>';

    /**
     * Loop through all objects
     */
    $objects = '';

    $objects_post = '';

    if( $ld_lessons_settings['wp_post_tag'] == 'yes' ) {
        $objects_post  = get_terms( 'post_tag', array(
            'hide_empty' => false
        ) );
    }

    if( taxonomy_exists( 'ld_lesson_tag' ) ){
        $objects = get_terms( 'ld_lesson_tag', array(
            'hide_empty' => false
        ) );
    }


    if ( !empty( $objects_post  ) ) {
        foreach ( $objects_post  as $object ) {
            $selected = '';

            if ( in_array( $current_trigger, array( 'badgeos_learndash_lesson_completed_tag' ) ) )
                $selected = selected( $current_object_id, $object->term_id, false );

            echo '<option' . $selected . ' value="' . $object->term_id . '">' . esc_html( $object->name ) . '</option>';
        }
    }

    if ( !empty( $objects ) ) {
        foreach ( $objects as $object ) {
            $selected = '';

            if ( in_array( $current_trigger, array( 'badgeos_learndash_lesson_completed_tag' ) ) )
                $selected = selected( $current_object_id, $object->term_id, false );

            echo '<option' . $selected . ' value="' . $object->term_id . '">' . esc_html( $object->name ) . '</option>';
        }
    }


    echo '</select>';

    /**
     * get taxanomies settings for quizez
     */
    $ld_settings = get_option( 'learndash_settings_quizzes_taxonomies');

    echo '<select name="badgeos_learndash_quiz_tag_id" class="select-quiz-tag-id">';
    echo '<option value="">' . __( 'Any Quiz Tag', 'badgeos-learndash' ) . '</option>';

    /**
     * Loop through all objects
     */
    $objects = '';

    $objects_post_tag  = '';

    if( $ld_settings['wp_post_tag'] == 'yes' ) {
        $objects_post_tag  = get_terms( 'post_tag', array(
            'hide_empty' => false
        ) );
    }

    if( taxonomy_exists( 'ld_quiz_tag' ) ) {
        $objects = get_terms( 'ld_quiz_tag', array(
            'hide_empty' => false
        ) );
    }


    if ( !empty( $objects_post_tag ) ) {
        foreach ( $objects_post_tag  as $object ) {
            $selected = '';

            if ( in_array( $current_trigger, array( 'badgeos_learndash_quiz_completed_tag' ) ) )
                $selected = selected( $current_object_id, $object->term_id, false );

            echo '<option' . $selected . ' value="' . $object->term_id . '">' . esc_html( $object->name ) . '</option>';
        }
    }

    if ( !empty( $objects ) ) {
        foreach ( $objects as $object ) {
            $selected = '';

            if ( in_array( $current_trigger, array( 'badgeos_learndash_quiz_completed_tag' ) ) )
                $selected = selected( $current_object_id, $object->term_id, false );

            echo '<option' . $selected . ' value="' . $object->term_id . '">' . esc_html( $object->name ) . '</option>';
        }
    }

    echo '</select>';

    /**
     * Quiz Fail Tags
     */
    echo '<select name="badgeos_learndash_quiz_fail_tag_id" class="select-quiz-fail-tag-id">';
    echo '<option value="">' . __( 'Any Quiz Tag', 'badgeos-learndash' ) . '</option>';

    /**
     * Loop through all objects
     */
    $objects = '';

    $objects_post_tag  = '';

    if ( $ld_settings['wp_post_tag'] == 'yes' ) {
        $objects_post_tag  = get_terms( 'post_tag', array(
            'hide_empty' => false
        ) );
    }

    if ( taxonomy_exists( 'ld_quiz_tag' )){
        $objects = get_terms( 'ld_quiz_tag', array(
            'hide_empty' => false
        ) );
    }

    if ( !empty( $objects_post_tag  ) ) {
        foreach ( $objects_post_tag  as $object ) {
            $selected = '';

            if ( in_array( $current_trigger, array( 'badgeos_learndash_quiz_completed_fail_tag' ) ) )
                $selected = selected( $current_object_id, $object->term_id, false );

            echo '<option' . $selected . ' value="' . $object->term_id . '">' . esc_html( $object->name ) . '</option>';
        }
    }

    if ( !empty( $objects ) ) {
        foreach ( $objects as $object ) {
            $selected = '';

            if ( in_array( $current_trigger, array( 'badgeos_learndash_quiz_completed_fail_tag') ) )
                $selected = selected( $current_object_id, $object->term_id, false );

            echo '<option' . $selected . ' value="' . $object->term_id . '">' . esc_html( $object->name ) . '</option>';
        }
    }

    echo '</select>';


    /**
     * Groups
     */
    echo '<select name="badgeos_learndash_group_id" class="select-lrndsh-group-id">';
    echo '<option value="">' . __( 'Any Group', 'badgeos-learndash' ) . '</option>';

    /**
     * Loop through all objects
     */
    $objects = get_posts( array(
        'post_type' => 'groups',
        'post_status' => 'publish',
        'posts_per_page' => -1
    ) );

    if ( !empty( $objects ) ) {
        foreach ( $objects as $object ) {
            $selected = '';

            if ( in_array( $current_trigger, array( 'ld_added_group_access' ) ) )
                $selected = selected( $current_object_id, $object->ID, false );

            echo '<option' . $selected . ' value="' . $object->ID . '">' . esc_html( get_the_title( $object->ID ) ) . '</option>';
        }
    }

    echo '</select>';

    /**
     * Purchased Courses
     */
    echo '<select name="badgeos_learndash_purchased_course_id" class="select-purchased-course-id">';
    echo '<option value="">' . __( 'Any Course', 'badgeos-learndash' ) . '</option>';

    /**
     * Loop through all objects
     */
    $objects = get_posts( array(
        'post_type' => 'sfwd-courses',
        'post_status' => 'publish',
        'posts_per_page' => -1
    ) );

    if ( !empty( $objects ) ) {
        foreach ( $objects as $object ) {
            $selected = '';

            if ( in_array( $current_trigger, array( 'badgeos_learndash_purchase_course' ) ) )
                $selected = selected( $current_object_id, $object->ID, false );

            echo '<option' . $selected . ' value="' . $object->ID . '">' . esc_html( get_the_title( $object->ID ) ) . '</option>';
        }
    }

    echo '</select>';
}
add_action( 'badgeos_steps_ui_html_after_trigger_type', 'badgeos_learndash_step_etc_select', 10, 2 );
add_action( 'badgeos_award_steps_ui_html_after_achievement_type', 'badgeos_learndash_step_etc_select', 10, 2 );
add_action( 'badgeos_deduct_steps_ui_html_after_trigger_type', 'badgeos_learndash_step_etc_select', 10, 2 );
add_action( 'badgeos_rank_req_steps_ui_html_after_trigger_type', 'badgeos_learndash_step_etc_select', 10, 2 );

/**
 * AJAX Handler for saving all steps.
 *
 * @param $title
 * @param $step_id
 * @param $step_data
 * @return string|void
 */
function badgeos_learndash_save_step( $title, $step_id, $step_data ) {

    /**
     * If we're working on a LearnDash trigger
     */
    if ( 'learndash_trigger' == $step_data[ 'trigger_type' ] ) {

        /**
         * Update our LearnDash trigger post meta
         */
        update_post_meta( $step_id, '_badgeos_learndash_trigger', $step_data[ 'learndash_trigger' ] );

        /**
         * Rewrite the step title
         */
        $title = $step_data[ 'learndash_trigger_label' ];

        $object_id = 0;
        $object_arg1 = 0;

        /**
         * Quiz specific (pass)
         */
        if ( 'learndash_quiz_completed' == $step_data[ 'learndash_trigger' ] ) {

            /**
             * Get Object ID
             */
            $object_id = (int) $step_data[ 'learndash_quiz_id' ];

            /**
             * Set new step title
             */
            if ( empty( $object_id ) ) {
                $title = __( 'Completed any quiz', 'badgeos-learndash' );
            } else {
                $title = sprintf( __( 'Completed quiz "%s"', 'badgeos-learndash' ), get_the_title( $object_id ) );
            }
        } elseif ( 'badgeos_learndash_quiz_completed_specific' == $step_data[ 'learndash_trigger' ] ) {

            /**
             * Get Object ID
             */
            $object_id = (int) $step_data[ 'learndash_quiz_id' ];
            $object_arg1 = (int) $step_data[ 'learndash_quiz_grade' ];

            /**
             * Set new step title
             */
            if ( empty( $object_id ) ) {
                $title = sprintf( __( 'Completed any quiz with a score of %d or higher', 'badgeos-learndash' ), $object_arg1 );
            } else {
                $title = sprintf( __( 'Completed quiz "%s" with a score of %d or higher', 'badgeos-learndash' ), get_the_title( $object_id ), $object_arg1 );
            }
        } elseif ( 'badgeos_learndash_quiz_completed_fail' == $step_data[ 'learndash_trigger' ] ) {

            /**
             * Get Object ID
             */
            $object_id = (int) $step_data[ 'learndash_quiz_id' ];

            /**
             * Set new step title
             */
            if ( empty( $object_id ) ) {
                $title = sprintf( __( 'Failed any quiz', 'badgeos-learndash' ), $object_arg1 );
            }  else {
                $title = sprintf( __( 'Failed quiz "%s"', 'badgeos-learndash' ), get_the_title( $object_id ), $object_arg1 );
            }
        } elseif ( 'learndash_topic_completed' == $step_data[ 'learndash_trigger' ] ) {

            /**
             * Get Object ID
             */
            $object_id = (int) $step_data[ 'learndash_topic_id' ];

            /**
             * Set new step title
             */
            if ( empty( $object_id ) ) {
                $title = __( 'Completed any topic', 'badgeos-learndash' );
            } else {
                $title = sprintf( __( 'Completed topic "%s"', 'badgeos-learndash' ), get_the_title( $object_id ) );
            }
        } elseif ( 'learndash_lesson_completed' == $step_data[ 'learndash_trigger' ] ) {

            /**
             * Get Object ID
             */
            $object_id = (int) $step_data[ 'learndash_lesson_id' ];

            /**
             * Set new step title
             */
            if ( empty( $object_id ) ) {
                $title = __( 'Completed any lesson', 'badgeos-learndash' );
            } else {
                $title = sprintf( __( 'Completed lesson "%s"', 'badgeos-learndash' ), get_the_title( $object_id ) );
            }
        } elseif ( 'learndash_course_completed' == $step_data[ 'learndash_trigger' ] ) {

            /**
             * Get Object ID
             */
            $object_id = (int) $step_data[ 'learndash_course_id' ];

            /**
             * Set new step title
             */
            if ( empty( $object_id ) ) {
                $title = __( 'Completed any course', 'badgeos-learndash' );
            }  else {
                $title = sprintf( __( 'Completed course "%s"', 'badgeos-learndash' ), get_the_title( $object_id ) );
            }
        } elseif ( 'badgeos_learndash_course_completed_tag' == $step_data[ 'learndash_trigger' ] ) {

            /**
             * Get Object ID
             */
            $object_id = (int) $step_data[ 'learndash_course_category_id' ];

            /**
             * get taxanomies settings for courses
             */
            $ld_settings = get_option( 'learndash_settings_courses_taxonomies');

            /**
             * Set new step title
             */
            if ( empty( $object_id ) ) {
                $title = __( 'Completed course in any tag', 'badgeos-learndash' );
            } else {
                if ( get_term( $object_id, 'post_tag' ) && $ld_settings['wp_post_tag'] == 'yes' ) {
                    $title = sprintf( __( 'Completed course in tag "%s"', 'badgeos-learndash' ), get_term( $object_id, 'post_tag' )->name );
                } elseif ( get_term( $object_id, 'ld_course_tag' ) && taxonomy_exists( 'ld_course_tag' ) ) {
                    $title = sprintf( __( 'Completed course in tag "%s"', 'badgeos-learndash' ), get_term( $object_id, 'ld_course_tag' )->name );
                }
            }
        } elseif ( 'badgeos_learndash_topic_completed_tag' == $step_data[ 'learndash_trigger' ] ) {

            /**
             * Get Object ID
             */
            $object_id = (int) $step_data[ 'learndash_topic_tag_id' ];

            /**
             * get taxanomies settings for topics
             */
            $ld_settings = get_option( 'learndash_settings_topics_taxonomies');

            /**
             * Set new step title
             */
            if ( empty( $object_id ) ) {
                $title = __( 'Completed topic in any tag', 'badgeos-learndash' );
            } else {
                if ( get_term( $object_id, 'post_tag' ) && $ld_settings['wp_post_tag'] == 'yes' ){
                    $title = sprintf( __( 'Completed topic in tag "%s"', 'badgeos-learndash' ), get_term( $object_id, 'post_tag' )->name );
                } elseif ( get_term( $object_id, 'ld_topic_tag' ) && taxonomy_exists( 'ld_topic_tag' )){
                    $title = sprintf( __( 'Completed topic in tag "%s"', 'badgeos-learndash' ), get_term( $object_id, 'ld_topic_tag' )->name );
                }
            }
        } elseif ( 'badgeos_learndash_lesson_completed_tag' == $step_data[ 'learndash_trigger' ] ) {

            /**
             * Get Object ID
             */
            $object_id = (int) $step_data[ 'learndash_lesson_tag_id' ];

            /**
             * get taxanomies settings for lessons
             */
            $ld_settings = get_option( 'learndash_settings_lessons_taxonomies');

            /**
             * Set new step title
             */
            if ( empty( $object_id ) ) {
                $title = __( 'Completed Lesson in any tag', 'badgeos-learndash' );
            } else {
                if ( get_term( $object_id, 'post_tag' ) && $ld_settings['wp_post_tag'] == 'yes' ){
                    $title = sprintf( __( 'Completed Lesson in tag "%s"', 'badgeos-learndash' ), get_term( $object_id, 'post_tag' )->name );
                } elseif ( get_term( $object_id, 'ld_lesson_tag' ) && taxonomy_exists( 'ld_lesson_tag' ) ) {
                    $title = sprintf( __( 'Completed Lesson in tag "%s"', 'badgeos-learndash' ), get_term( $object_id, 'ld_lesson_tag' )->name );
                }
            }
        } elseif ( 'badgeos_learndash_quiz_completed_tag' == $step_data['learndash_trigger'] ) {

            /**
             * Get Object ID
             */
            $object_id = (int) $step_data[ 'learndash_quiz_tag_id' ];

            /**
             * get taxanomies settings for quizez
             */
            $ld_settings = get_option( 'learndash_settings_quizzes_taxonomies' );

            if ( empty( $object_id ) ) {
                $title = __( 'Completed quiz in any tag', 'badgeos-learndash' );
            } else {
                if( get_term( $object_id, 'post_tag' ) && $ld_settings['wp_post_tag'] == 'yes' ){
                    $title = sprintf( __( 'Completed quiz in tag "%s"', 'badgeos-learndash' ), get_term( $object_id, 'post_tag' )->name );
                } elseif( get_term( $object_id, 'ld_quiz_tag' ) && taxonomy_exists( 'ld_quiz_tag' ) ) {
                    $title = sprintf( __( 'Completed quiz in tag "%s"', 'badgeos-learndash' ), get_term( $object_id, 'ld_quiz_tag' )->name );
                }
            }
        } elseif ( 'badgeos_learndash_quiz_completed_fail_tag' == $step_data['learndash_trigger'] ) {

            /**
             * Get Object ID
             */
            $object_id = (int) $step_data[ 'learndash_quiz_fail_tag_id' ];

            /**
             * get taxanomies settings for quizez
             */
            $ld_settings = get_option( 'learndash_settings_quizzes_taxonomies' );

            if ( empty( $object_id ) ) {
                $title = __( 'Failed quiz in any tag', 'badgeos-learndash' );
            } else {
                if ( get_term( $object_id, 'post_tag' ) && $ld_settings['wp_post_tag'] == 'yes' ) {
                    $title = sprintf( __( 'Failed quiz in tag "%s"', 'badgeos-learndash' ), get_term( $object_id, 'post_tag' )->name );
                } elseif(get_term( $object_id, 'ld_quiz_tag' ) && taxonomy_exists( 'ld_quiz_tag' ) ) {
                    $title = sprintf( __( 'Failed quiz in tag "%s"', 'badgeos-learndash' ), get_term( $object_id, 'ld_quiz_tag' )->name );
                }
            }
        } elseif ( 'ld_added_group_access' == $step_data[ 'learndash_trigger' ] ) {

            /**
             * Get Object ID
             */
            $object_id = (int) $step_data[ 'learndash_group_id' ];

            /**
             * Set new step title
             */
            if ( empty( $object_id ) ) {
                $title = __( 'Completed group registration in', 'badgeos-learndash' );
            } else {
                $title = sprintf( __( 'Completed group registration in "%s"', 'badgeos-learndash' ), get_the_title( $object_id ) );
            }
        } elseif ( 'badgeos_learndash_purchase_course' == $step_data[ 'learndash_trigger' ] ) {

            /**
             * Get Object ID
             */
            $object_id = (int) $step_data[ 'learndash_purchased_course_id' ];

            /**
             * Set new step title
             */
            if ( empty( $object_id ) ) {
                $title = __( 'Purchased any course', 'badgeos-learndash' );
            } else {
                $title = sprintf( __( 'Purchased course "%s"', 'badgeos-learndash' ), get_the_title( $object_id ) );
            }
        }

        /**
         * Store our Object ID in meta
         */
        update_post_meta( $step_id, '_badgeos_learndash_object_id', $object_id );
        update_post_meta( $step_id, '_badgeos_learndash_object_arg1', $object_arg1 );
    }

    return $title;
}
add_filter( 'badgeos_save_step', 'badgeos_learndash_save_step', 10, 3 );

/**
 * Include custom JS for the BadgeOS Steps UI.
 */
function badgeos_learndash_step_js() {
    ?>
    <script type="text/javascript">
        jQuery( document ).ready( function ( $ ) {

            var times = $( '.required-count' ).val();

            /**
             * Listen for our change to our trigger type selector
             */
            $( document ).on( 'change', '.select-trigger-type', function () {

                var trigger_type = $( this );
                var trigger_parent = trigger_type.parent();
                /**
                 * Show our group selector if we're awarding based on a specific group
                 */
                if ( 'learndash_trigger' == trigger_type.val() ) {
                    trigger_type.siblings( '.select-learndash-trigger' ).show().change();
                    var trigger = trigger_parent.find('.select-learndash-trigger').val();
                    if ( 'badgeos_learndash_quiz_completed_specific'  == trigger ) {
                        trigger_parent.find('.input-quiz-grade').parent().show();
                    }
                    if( parseInt( times ) < 1 )
                        trigger_parent.find('.required-count').val('1');//.prop('disabled', true);
                }  else {
                    trigger_type.siblings( '.select-learndash-trigger' ).val('').hide().change();
                    trigger_parent.find( '.input-quiz-grade' ).parent().hide();
                    var fields = ['quiz','topic','lesson','course','course-category','topic-tag','lesson-tag','purchased-course','lrndsh-group','quiz-tag','quiz-fail-tag'];
                    $( fields ).each( function( i,field ) {
                        trigger_parent.find('.select-' + field + '-id').hide();
                    });
                    trigger_parent.find( '.required-count' ).val( times );//.prop( 'disabled', false );
                }
            } );

            /**
             * Listen for our change to our trigger type selector
             */
            $( document ).on( 'change', '.select-learndash-trigger,' +
                '.select-quiz-id,' +
                '.select-topic-id,' +
                '.select-lesson-id,' +
                '.select-course-id,' +
                '.select-topic-tag-id,' +
                '.select-lesson-tag-id,'+
                '.select-quiz-tag-id,'+
                '.select-quiz-fail-tag-id,'+
                '.select-course-category-id' +
                '.select-purchased-course-id' +
                '.select-lrndsh-group-id', function () {
                badgeos_learndash_step_change( $( this ) , times);
            } );

            /**
             * Trigger a change so we properly show/hide our LearnDash menues
             */
            $( '.select-trigger-type' ).change();

            /**
             * Inject our custom step details into the update step action
             */
            $( document ).on( 'update_step_data', function ( event, step_details, step ) {
                step_details.learndash_trigger = $( '.select-learndash-trigger', step ).val();
                step_details.learndash_trigger_label = $( '.select-learndash-trigger option', step ).filter( ':selected' ).text();

                step_details.learndash_quiz_id = $( '.select-quiz-id', step ).val();
                step_details.learndash_quiz_grade = $( '.input-quiz-grade', step ).val();
                step_details.learndash_topic_id = $( '.select-topic-id', step ).val();
                step_details.learndash_lesson_id = $( '.select-lesson-id', step ).val();
                step_details.learndash_course_id = $( '.select-course-id', step ).val();
                step_details.learndash_course_category_id = $( '.select-course-category-id', step ).val();
                step_details.learndash_topic_tag_id = $( '.select-topic-tag-id', step ).val();
                step_details.learndash_quiz_tag_id = $( '.select-quiz-tag-id', step ).val();
                step_details.learndash_quiz_fail_tag_id = $( '.select-quiz-fail-tag-id', step ).val();
                step_details.learndash_lesson_tag_id = $( '.select-lesson-tag-id', step ).val();
                step_details.learndash_purchased_course_id = $( '.select-purchased-course-id', step ).val();
                step_details.learndash_group_id = $( '.select-lrndsh-group-id', step ).val();
            } );

        } );

        function badgeos_learndash_step_change( $this , times) {

            var trigger_parent = $this.parent(),
                trigger_value = trigger_parent.find( '.select-learndash-trigger' ).val();
            var	trigger_parent_value = trigger_parent.find( '.select-trigger-type' ).val();

            /**
             * Quiz specific
             */
            trigger_parent.find( '.select-quiz-id' )
                .toggle(
                    ( 'learndash_quiz_completed' == trigger_value
                        || 'badgeos_learndash_quiz_completed_specific' == trigger_value
                        || 'badgeos_learndash_quiz_completed_fail' == trigger_value )
                );

            /**
             * Topic specific
             */
            trigger_parent.find( '.select-topic-id' )
                .toggle( 'learndash_topic_completed' == trigger_value );

            /**
             * Lesson specific
             */
            trigger_parent.find( '.select-lesson-id' )
                .toggle( 'learndash_lesson_completed' == trigger_value );

            /**
             * Course specific
             */
            trigger_parent.find( '.select-course-id' )
                .toggle( 'learndash_course_completed' == trigger_value );

            /**
             * Course Category specific
             */
            trigger_parent.find( '.select-course-category-id' )
                .toggle( 'badgeos_learndash_course_completed_tag' == trigger_value );

            /**
             * Topic Tag specific
             */
            trigger_parent.find( '.select-topic-tag-id' )
                .toggle( 'badgeos_learndash_topic_completed_tag' == trigger_value );

            /**
             * Lesson Tag specific
             */
            trigger_parent.find( '.select-lesson-tag-id' )
                .toggle( 'badgeos_learndash_lesson_completed_tag' == trigger_value );

            /**
             * Quiz Tag specific
             */
            trigger_parent.find( '.select-quiz-tag-id' )
                .toggle( 'badgeos_learndash_quiz_completed_tag' == trigger_value );

            /**
             * Fail Quiz Tag specific
             */
            trigger_parent.find( '.select-quiz-fail-tag-id' )
                .toggle( 'badgeos_learndash_quiz_completed_fail_tag' == trigger_value );

            /**
             * Quiz Grade specific
             */
            trigger_parent.find( '.input-quiz-grade' ).parent() // target parent span
                .toggle( 'badgeos_learndash_quiz_completed_specific' == trigger_value );

            /**
             * Group
             */
            trigger_parent.find( '.select-lrndsh-group-id' )
                .toggle( 'ld_added_group_access' == trigger_value );

            /**
             * Purchased Course
             */
            trigger_parent.find( '.select-purchased-course-id' )
                .toggle( 'badgeos_learndash_purchase_course' == trigger_value );
            if ( ( 'learndash_quiz_completed' == trigger_value && '' != trigger_parent.find( '.select-quiz-id' ).val() )
                || ( 'badgeos_learndash_quiz_completed_specific' == trigger_value && '' != trigger_parent.find( '.select-quiz-id' ).val() )
                || ( 'badgeos_learndash_quiz_completed_fail' == trigger_value && '' != trigger_parent.find( '.select-quiz-id' ).val() )
                || ( 'learndash_topic_completed' == trigger_value && '' != trigger_parent.find( '.select-topic-id' ).val() )
                || ( 'learndash_lesson_completed' == trigger_value && '' != trigger_parent.find( '.select-lesson-id' ).val() )
                || ( 'learndash_course_completed' == trigger_value && '' != trigger_parent.find( '.select-course-id' ).val() )
                || ( 'badgeos_learndash_topic_completed_tag' == trigger_value && '' != trigger_parent.find( '.select-topic-tag-id' ).val())
                || ( 'badgeos_learndash_quiz_completed_tag' == trigger_value && '' != trigger_parent.find( '.select-quiz-tag-id' ).val())
                || ( 'badgeos_learndash_quiz_completed_fail_tag' == trigger_value && '' != trigger_parent.find( '.select-quiz-fail-tag-id' ).val())
                || ( 'badgeos_learndash_lesson_completed_tag' == trigger_value && '' != trigger_parent.find( '.select-lesson-tag-id' ).val())
                || ( 'badgeos_learndash_course_completed_tag' == trigger_value && '' != trigger_parent.find( '.select-course-category-id' ).val()
                    || ( 'ld_added_group_access' == trigger_value && '' != trigger_parent.find( '.select-lrndsh-group-id' ).val()
                        || ( 'badgeos_learndash_purchase_course' == trigger_value && '' != trigger_parent.find( '.select-purchased-course-id' ).val()
                        ) ) ) ) {
                trigger_parent.find( '.required-count' )
                    .val( '1' );//.prop( 'disabled', true );
            } else {

                if(trigger_parent_value != 'learndash_trigger') {

                    trigger_parent.find('.required-count')
                        .val(times);//.prop('disabled', false);
                }
            }
        }
    </script>
    <?php
}
add_action( 'admin_footer', 'badgeos_learndash_step_js' );