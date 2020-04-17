<?php
/**
 * The template for displaying Comments.
 *
 * @package WordPress
 */
?>

<div id="comments">
	<?php if ( post_password_required() ) : ?>
    <p class="nopassword"><?php _e( 'This post is password protected. Enter the password to view any comments.', '' ); ?></p>
</div><!-- #comments -->
<?php
/* Stop the rest of comments.php from being processed,
 * but don't kill the script entirely -- we still have
 * to fully load the template.
 */

return;
endif;
?>
<?php if ( have_comments() ) : ?>
    <h3 id="comments-title"><?php
		printf( _n( 'One Response to %2$s', '%1$s Responses to %2$s', get_comments_number(), '' ),
			number_format_i18n( get_comments_number() ), '<em>' . get_the_title() . '</em>' );
		?></h3>

	<?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : // Are there comments to navigate through? ?>
        <div class="navigation">
            <div class="nav-previous"><?php previous_comments_link( __( '<span class="meta-nav">&larr;</span> Older Comments', '' ) ); ?></div>
            <div class="nav-next"><?php next_comments_link( __( 'Newer Comments <span class="meta-nav">&rarr;</span>', '' ) ); ?></div>
        </div> <!-- .navigation -->
	<?php endif; // check for comment navigation ?>

    <ol class="commentlist">
		<?php
		wp_list_comments( [ 'callback' => '' ] );
		?>
    </ol>

	<?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : // Are there comments to navigate through? ?>
        <div class="navigation">
            <div class="nav-previous"><?php previous_comments_link( __( '<span class="meta-nav">&larr;</span> Older Comments', '' ) ); ?></div>
            <div class="nav-next"><?php next_comments_link( __( 'Newer Comments <span class="meta-nav">&rarr;</span>', '' ) ); ?></div>
        </div><!-- .navigation -->
	<?php endif; // check for comment navigation ?>

<?php else : // or, if we don't have comments:

	/* If there are no comments and comments are closed,
	 * let's leave a little note, shall we?
	 */
	if ( ! comments_open() ) :
		?>
        <p class="nocomments"><?php _e( 'Comments are closed.', '' ); ?></p>
	<?php endif; // end ! comments_open()
	?>

<?php endif; // end have_comments() ?>
<?php
$fields = [
	'author' => '<p class="comment-form-author">' . '<input id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) . '" size="30"' . $aria_req . ' />' .
	            ' <label for="author">' . __( 'Name' ) . '</label> ' . ( $req ? '<span class="required">(required)</span>' : '' ) . '</p>',
	'email'  => '<p class="comment-form-email">' . '<input id="email" name="email" type="text" value="' . esc_attr( $commenter['comment_author_email'] ) . '" size="30"' . $aria_req . ' />' .
	            ' <label for="email">' . __( 'Email' ) . '</label> <span class="required">(will not be published)' . ( $req ? '(required)' : '' ) . '</span></p>',
	'url'    => '<p class="comment-form-url">' . '<input id="url" name="url" type="text" value="' . esc_attr( $commenter['comment_author_url'] ) . '" size="30" />' .
	            ' <label for="url">' . __( 'Website' ) . ' <span class="required">(optional)</span></label>' . '</p>',
];
$args   = [
	"fields"               => $fields,
	"comment_notes_before" => "",
	"comment_notes_after"  => "",
	'comment_field'        => '<p class="comment-form-comment"><textarea id="comment" name="comment" cols="45" rows="8" aria-required="true"></textarea>' .
	                          ' <label for="comment">' . __( 'Comment', 'noun' ) . '</label></p>'
];
comment_form( $args ); ?>

</div><!-- #comments -->
