<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'add_meta_boxes', 'wp_amsawal_meta_box_add' );
function wp_amsawal_meta_box_add(){
    add_meta_box( 'wp_amsawal_meta_box', 'Amsawal: Parámetros', 'wp_amsawal_meta_box_content', 'page', 'side', 'high' );
}

function wp_amsawal_meta_box_content(){
	if (basename($_SERVER['SCRIPT_FILENAME']) == "post-new.php") {
		$course = isset( $_GET['wp_amsawal_mb_course'] ) ? esc_attr( $_GET['wp_amsawal_mb_course']) : '';
		$lesson = isset( $_GET['wp_amsawal_mb_lesson'] ) ? esc_attr( $_GET['wp_amsawal_mb_lesson']) : '';
		$type = isset( $_GET['wp_amsawal_mb_typeh5p'] ) ? esc_attr( $_GET['wp_amsawal_mb_typeh5p']) : '';
		$step = isset( $_GET['wp_amsawal_mb_step'] ) ? esc_attr( $_GET['wp_amsawal_mb_step']) : 0;
		$video = isset( $_GET['wp_amsawal_mb_video'] ) ? esc_attr( $_GET['wp_amsawal_mb_video']) : '';
	}
	else {
		global $post;
		$values = get_post_custom( $post->ID );
		$course = isset( $values['wp_amsawal_mb_course'] ) ? esc_attr( $values['wp_amsawal_mb_course'][0] ) : '';
		$lesson = isset( $values['wp_amsawal_mb_lesson'] ) ? esc_attr( $values['wp_amsawal_mb_lesson'][0] ) : '';
		$type = isset( $values['wp_amsawal_mb_typeh5p'] ) ? esc_attr( $values['wp_amsawal_mb_typeh5p'][0] ) : '';
		$step = isset( $values['wp_amsawal_mb_step'] ) ? esc_attr( $values['wp_amsawal_mb_step'][0] ) : 0;
		$video = isset( $values['wp_amsawal_mb_video'] ) ? esc_attr( $values['wp_amsawal_mb_video'][0] ) : '';
	}
	if (empty($step)) {
		$step = 0;
	}
	echo '

		<p class="post-attributes-label-wrapper"><label class="post-attributes-label" for="wp_amsawal_mb_course">Curso: </label></p>
		<p>
		<input type="text" name="wp_amsawal_mb_course" id="wp_amsawal_mb_course" value="'.$course.'" />
		</p>

		<p class="post-attributes-label-wrapper"><label class="post-attributes-label" for="wp_amsawal_mb_lesson">Lección: </label></p>
		<p>
		<input type="number" name="wp_amsawal_mb_lesson" id="wp_amsawal_mb_lesson" value="'.$lesson.'" />
		</p>

		<p class="post-attributes-label-wrapper"><label class="post-attributes-label" for="wp_amsawal_mb_step">Peldaño: </label></p>
		<p>
		<input type="number" name="wp_amsawal_mb_step" id="wp_amsawal_mb_step" value="'.$step.'" />
		</p>


		<p class="post-attributes-label-wrapper"><label class="post-attributes-label" for="wp_amsawal_mb_typeh5p">Tipo: </label></p>
		<p>
			<select name="wp_amsawal_mb_typeh5p" id="wp_amsawal_mb_typeh5p">
				<option value="" style="min-width: 300px;">(no definido)</option>
				<option value="test" '.($type === "test" ? "selected" : "").'>Test</option>
				<option value="lesson" '.($type === "lesson" ? "selected" : "").'>Lesson</option>
				<option value="memory" '.($type === "memory" ? "selected" : "").'>Memory</option>
				<option value="dialogcards" '.($type === "dialogcards" ? "selected" : "").'>Dialogcards</option>
				<option value="video" '.($type === "video" ? "selected" : "").'>Video</option>
				<option value="presentation" '.($type === "presentation" ? "selected" : "").'>Presentation</option>
				<option value="accordion" '.($type === "accordion" ? "selected" : "").'>Accordion</option>
				<option value="flashcards" '.($type === "flashcards" ? "selected" : "").'>Flashcards</option>
			</select>
		</p>


		<p class="post-attributes-label-wrapper"><label class="post-attributes-label" for="wp_amsawal_mb_video">Vídeo: </label></p>
		<p>
		<input type="text" name="wp_amsawal_mb_video" id="wp_amsawal_mb_video" value="'.$video.'" />
		</p>
	';

    wp_nonce_field( 'wp_amsawal_meta_box', 'wp_amsawal_meta_box_nonce' );
}


add_action( 'save_post', 'wp_amsawal_meta_box_save' );
function wp_amsawal_meta_box_save($post_id) {
    if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if( !isset( $_POST['wp_amsawal_meta_box_nonce'] ) || !wp_verify_nonce( $_POST['wp_amsawal_meta_box_nonce'], 'wp_amsawal_meta_box' ) ) return;
    if ( !current_user_can( 'edit_post', $post_id ) ) return;


    if( isset( $_POST['wp_amsawal_mb_course'] )) {
        update_post_meta( $post_id, 'wp_amsawal_mb_course', wp_kses( $_POST['wp_amsawal_mb_course'], array() ));
	}
 	if( isset( $_POST['wp_amsawal_mb_lesson'] )) {
        update_post_meta( $post_id, 'wp_amsawal_mb_lesson', wp_kses( $_POST['wp_amsawal_mb_lesson'], array() ));
	}
    if( isset( $_POST['wp_amsawal_mb_typeh5p'] )) {
        update_post_meta( $post_id, 'wp_amsawal_mb_typeh5p', esc_attr( $_POST['wp_amsawal_mb_typeh5p'] ));
	}
	if( isset( $_POST['wp_amsawal_mb_step'] )) {
        update_post_meta( $post_id, 'wp_amsawal_mb_step', esc_attr( $_POST['wp_amsawal_mb_step'] ));
	}
	if( isset( $_POST['wp_amsawal_mb_video'] )) {
        update_post_meta( $post_id, 'wp_amsawal_mb_video', wp_kses( $_POST['wp_amsawal_mb_video'], array() ));
	}
}
