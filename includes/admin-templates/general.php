<?php
/**
* General Options
*/

if ( ! defined( 'ABSPATH' ) ) exit;


$wn_bos_ld_options = get_option( 'wn_bos_ld_options', array() );
$bos_ld_quiz_point_type = isset($wn_bos_ld_options['bos_ld_quiz_point_type']) ? $wn_bos_ld_options['bos_ld_quiz_point_type'] : 0;
$quiz_points_as_badgeos_points = !empty( $wn_bos_ld_options['quiz_points_as_badgeos_points']) ? $wn_bos_ld_options['quiz_points_as_badgeos_points'] : 'no';
$badgeos_learndash_quiz_score_multiplier = !empty( $wn_bos_ld_options['badgeos_learndash_quiz_score_multiplier']) ? (int) $wn_bos_ld_options['badgeos_learndash_quiz_score_multiplier'] : '1';

?>
<div id="wn-bos-ld-general-options">
	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST">
		<input type="hidden" name="action" value="wn_bos_ld_admin_settings">
		<?php wp_nonce_field( 'wn_bos_ld_admin_settings_action', 'wn_bos_ld_admin_settings_field' ); ?>
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row">
						<label for="quiz_points_as_badgeos_points">
							<?php _e( 'Award LearnDash Quiz Score as BadgeOS Points:', 'badgeos-learndash' ); ?>
						</label>
					</th>
					<td>
                        <select name="quiz_points_as_badgeos_points" id="quiz_points_as_badgeos_points">
                            <option value="no" <?php echo ( $quiz_points_as_badgeos_points == 'no' ) ? 'selected="selected"' : ''; ?> ><?php echo __( 'No' ); ?></option>
                            <option value="quiz_score_if_passed" <?php echo ( $quiz_points_as_badgeos_points == 'quiz_score_if_passed' ) ? 'selected="selected"' : ''; ?>><?php echo __( 'If quiz passed' ); ?></option>
                            <option value="quiz_score_anyway" <?php echo ( $quiz_points_as_badgeos_points == 'quiz_score_anyway' ) ? 'selected="selected"' : ''; ?>><?php echo __( 'Award points in any case' ); ?></option>
                        </select>
						<p class="description"><?php _e( 'Award quiz score as BadgeOS points on quiz completion', 'badgeos-learndash' ); ?></p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="bos_ld_quiz_point_type">
							<?php _e( 'Point type', 'badgeos-learndash' ); ?>
						</label>
					</th>
					<td>
                        <select name="bos_ld_quiz_point_type" id="bos_ld_quiz_point_type">
                            <option value="0"></option>
                            <?php foreach(badgeos_get_point_types() as $point_type): ?>
                                <option value="<?php echo $point_type->ID; ?>" <?php selected($point_type->ID, $bos_ld_quiz_point_type)?>><?php echo $point_type->post_title; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php _e( 'Select the point type to be awarded.', 'badgeos-learndash' ); ?></p>
                    </td>
				</tr>

				<tr valign="top">
					<th scope="row">
						<label for="badgeos_learndash_quiz_score_multiplier">
							<?php _e( 'Quiz Score Multiplier:', 'badgeos-learndash' ); ?>
						</label>
					</th>
					<td>
						<input type="number" class="checkbox" name="badgeos_learndash_quiz_score_multiplier" id="badgeos_learndash_quiz_score_multiplier" value="<?php echo (int) $badgeos_learndash_quiz_score_multiplier; ?>" min="0">
						<p class="description"><?php _e( 'Set a multiplier when awarding points for quiz score (i.e If quiz score is 12/15 so a multiplier value of 10 will award 120 points to the user)', 'badgeos-learndash' ); ?></p>
					</td>
				</tr>
				<?php do_action( 'badgeos_learndash_settings', $wn_bos_ld_options ); ?>
			</tbody>
		</table>
		<p>
			<?php
			submit_button( __( 'Save Settings', 'badgeos-learndash' ), 'primary', 'wn_bos_ld_settings_submit' );
			?>
		</p>
	</form>
</div>