<?php


//  Featured Projects With Featured Image
class mosaic_featured_projects extends WP_Widget {

	function __construct() {
		parent::__construct( 'mosaic_featured_projects', '- Featured Projects', [ 'description' => 'Featured projects with hover effect.' ] );
	}

	function widget( $args, $instance ) {

		$type       = $instance['type'];
		$learn_more = ( isset( $instance["learn_more"] ) ) ? ( $instance['learn_more'] ) : '';
		$post_id    = (int) $instance["post_id"];

		echo $args['before_widget'];

		$query_args = [
			'post_type'      => $type,
			'posts_per_page' => 1,
			'p'              => $post_id
		];

		$wp = new WP_Query( $query_args );

		if ( $wp->have_posts() ) {
			$wp->the_post();

			echo '<div class="thumbnail"><a href="' . get_permalink() . '">' . PHP_EOL;
			the_post_thumbnail( 'large' );
			echo '</a></div>' . PHP_EOL;
			echo '<div class="widget_entry"><a href="' . get_permalink() . '">';
			echo '<span class="va-helper"></span>';
			echo '<span class="va-wrapper">';
			echo '<span class="project_title">' . get_the_title() . '</span>' . PHP_EOL;
			echo '<span class="view_project">' . $learn_more . '</span>' . PHP_EOL;
			echo '</span>';
			echo '</a></div>';
		}

		echo $args['after_widget'];
	}

	function update( $new_instance, $old_instance ) {
		$instance                = $old_instance;
		$instance['widgettitle'] = strip_tags( stripslashes( $new_instance['widgettitle'] ) );
		$instance['type']        = strip_tags( stripslashes( $new_instance['type'] ) );
		$instance['post_id']     = strip_tags( stripslashes( $new_instance["post_id"] ) );
		$instance['learn_more']  = $new_instance["learn_more"];

		return $instance;
	}

	function form( $instance ) {
		$default = [
			'type'        => 'web-design',
			'post_id'     => '',
			'learn_more'  => '<span class="fa fa-eye"></span>View Project',
			'widgettitle' => ''
		];

		$instance   = wp_parse_args( (array) $instance, $default );
		$type       = esc_attr( $instance['type'] );
		$post_id    = esc_attr( $instance['post_id'] );
		$learn_more = esc_attr( $instance['learn_more'] );

		$args           = [ 'public' => TRUE, 'publicly_queryable' => TRUE ];
		$types          = get_post_types( $args, 'objects' );
		$typelist       = '<select name="' . $this->get_field_name( "type" ) . '">';
		$post_type_name = '';

		foreach ( $types as $post_type ) {
			if ( $post_type->name != 'attachment' ) {
				$typelist       .= '<option value="' . $post_type->name . '"';
				$typelist       .= ( $type == $post_type->name ) ? ' selected="selected"' : '';
				$post_type_name = ( $type == $post_type->name ) ? $post_type->labels->name : $post_type_name;
				$typelist       .= '>' . $post_type->labels->name . '</option>';
			}
		}
		$typelist .= '</select>';

		$posts     = get_posts( [
			'post_type'        => $type,
			'post_status'      => 'publish',
			'suppress_filters' => FALSE,
			'posts_per_page'   => -1
		] );
		$post_list = '<select name="' . $this->get_field_name( 'post_id' ) . '">';
		foreach ( $posts as $post ) {
			$post_list .= '<option value="' . $post->ID . '"';
			$post_list .= ( $post_id == $post->ID ) ? ' selected' : '';
			$post_list .= '>' . $post->post_title . '</option>';
		}
		$post_list .= '</select>';

		$type_identifier = 'select[name="' . str_replace( '[', '\\[', str_replace( ']', '\\]', $this->get_field_name( 'type' ) ) ) . '"]';
		$post_identifier = 'select[name="' . str_replace( '[', '\\[', str_replace( ']', '\\]', $this->get_field_name( 'post_id' ) ) ) . '"]';

		?>
      <p><label for="<?php echo $this->get_field_id( 'type' ); ?>">Type</label><?php echo $typelist; ?></p>
      <p class="post_id"><label for="">Selected <?php echo $post_type_name; ?></label><?php echo $post_list; ?></p>
      <p><label for="<?php echo $this->get_field_id( 'learn_more' ); ?>">View Details Link Text: </label><input
                type="text" name="<?php echo $this->get_field_name( 'learn_more' ); ?>"
                value="<?php echo $learn_more; ?>"/></p>
      <input class="post_title" type="hidden" name="<?php echo $this->get_field_name( 'widgettitle' ); ?>"
             value="<?php echo $title; ?>"/>

      <script>
        jQuery( '<?php echo $type_identifier; ?>' ).change( function () {
          toggleACGSWPostID();
        } );
        jQuery( '<?php echo $post_identifier; ?>' ).change( function () {
          toggleACGSWPostName( jQuery( this ) );
        } ).trigger( 'change' );

        function toggleACGSWPostID() {
          var type  = jQuery( '<?php echo $type_identifier; ?>' );
          var sel   = type.val();
          var tax   = type.closest( '.widget-content' ).find( 'p.post_id' );
          var exist = tax.attr( 'data-post-type' );
          type.closest( '.widget-content' ).find( '.update-type' ).remove();
          if ( sel === exist ) {
            tax.show();
          } else {
            tax.hide().last().after( '<p class="update-type"><strong> - Click Save to select the Project - </strong></p>' );
          }
        }

        function toggleACGSWPostName( el ) {
          var title = jQuery( el ).find( 'option:selected' ).text();
          console.log( title );
          jQuery( el ).closest( '.widget-content' ).find( 'input.post_title' ).val( title );
        }
      </script>
	<?php }
}

register_widget( 'mosaic_featured_projects' );

