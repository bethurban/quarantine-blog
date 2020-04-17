<?php


class MosaicSourceTaxonomy {

	private $editing_source = FALSE;

	public function __construct() {
		add_action( 'init', [ $this, 'init' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );

		add_action( 'source_add_form_fields', [ $this, 'image_fields' ] );
		add_action( 'source_edit_form_fields', [ $this, 'image_fields' ] );

		add_action( 'created_source', [ $this, 'save_image' ], 10, 2 );
		add_action( 'edited_source', [ $this, 'save_image' ], 10, 2 );

		add_filter( 'manage_edit-source_columns', [ $this, 'add_column' ] );
		add_filter( 'manage_source_custom_column', [ $this, 'column_content' ], 10, 3 );

		add_filter( 'get_source_logo', [ $this, 'get_source_logo' ], 10, 2 );

		add_action( 'admin_footer', [ $this, 'admin_footer' ], PHP_INT_MAX );

		add_action( 'load-themes.php', [ $this, 'flush_rewrite_rules' ] );
	}

	public function init() {
		$this->register_taxonomy();
	}

	public function admin_enqueue_scripts() {
		$screen = get_current_screen();

		if ( empty( $screen->id ) || 'edit-source' != $screen->id ) {
			return;
		}

		wp_enqueue_media();
	}

	public function image_fields( $taxonomy ) {
		$is_edit = ( ! empty( $_GET['tag_ID'] ) );
		$image   = $this->get_source_image( $taxonomy );
		$img_tag = ( $image ) ? '<img src="' . $image . '">' : '';

		if ( $is_edit ) {
			echo '<tr><th>Logo</th><td>';
		} ?>
        <div class="form-field term-group image-container">
			<?php if ( ! $is_edit ) { ?>
                <label for="source-image"><?php _e( 'Logo', 'mosaic' ); ?></label>
			<?php } ?>
            <input type="hidden" id="source-image" name="source-image" value="<?php echo esc_attr( $image ); ?>">
            <div class="image-wrapper"><?php
				echo $img_tag;
				if ( $img_tag ) { ?>
                    <a class="delete"><span class="dashicons dashicons-no"></span></a>
				<?php } ?></div>
        </div>
		<?php
		if ( $is_edit ) {
			echo '</td></tr>';
		}

		$this->editing_source = TRUE;
	}

	public function add_column( $columns ) {
		$sorted_columns = [];
		$added          = FALSE;

		foreach ( $columns AS $key => $value ) {
			if ( ! $added && ( 'count' == $key || 'posts' == $key ) ) {
				$sorted_columns['logo'] = 'Logo';
				$added                  = TRUE;
			}

			$sorted_columns[ $key ] = $value;
		}

		return $sorted_columns;
	}

	public function column_content( $content, $column_name, $term_id ) {
		if ( 'logo' != $column_name ) {
			return $content;
		}

		$image = $this->get_source_image( $term_id );
		if ( $image ) {
			return '<img src="' . $image . '" style="max-width: 75px; height: auto;">';
		}

		return '';
	}

	public function admin_footer() {
		if ( ! $this->editing_source ) {
			return;
		} ?>
        <script>
          MosaicImageUpload.init();
        </script>
		<?php
	}

	public function save_image( $term_id, $tt_id ) {
		if ( ! empty ( $_POST['source-image'] ) ) {
			$image = $_POST['source-image'];

			update_term_meta( $term_id, 'source-image', $image );
		}
	}

	public function get_source_logo( $content, $post_id ) {
		$terms = wp_get_post_terms( $post_id, 'source' );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return '';
		}

		$term    = reset( $terms );
		$term_id = $term->term_id;
		$logo    = $this->get_source_image( $term_id );

		return $logo;
	}

	public function get_source_image( $taxonomy ) {
		if ( empty( $taxonomy ) || 'source' == $taxonomy ) {
			return '';
		}

		$term_id = NULL;
		if ( is_int( $taxonomy ) ) {
			$term_id = $taxonomy;
		} else if ( ! empty( $taxonomy->term_id ) ) {
			$term_id = $taxonomy->term_id;
		}

		if ( ! $term_id ) {
			return '';
		}

		return get_term_meta( $term_id, 'source-image', TRUE );
	}

	private function register_taxonomy() {
		$labels = [
			'name'              => _x( 'Sources', 'taxonomy general name', 'acg' ),
			'singular_name'     => _x( 'Source', 'taxonomy singular name', 'acg' ),
			'search_items'      => __( 'Search Sources', 'acg' ),
			'all_items'         => __( 'All Sources', 'acg' ),
			'parent_item'       => __( 'Parent Source', 'acg' ),
			'parent_item_colon' => __( 'Parent Source:', 'acg' ),
			'edit_item'         => __( 'Edit Source', 'acg' ),
			'update_item'       => __( 'Update Source', 'acg' ),
			'add_new_item'      => __( 'Add New Source', 'acg' ),
			'new_item_name'     => __( 'New  SourceName', 'acg' ),
			'menu_name'         => __( ' Sources', 'acg' )
		];

		register_taxonomy( 'source', 'post', [
			'hierarchical' => FALSE,
			'labels'       => $labels,
			'query_var'    => TRUE,
			'rewrite'      => [ 'slug' => 'source' ]
		] );
	}

	public function flush_rewrite_rules() {
		global $pagenow, $wp_rewrite;

		if ( 'themes.php' == $pagenow && isset( $_GET['activated'] ) ) {
			$wp_rewrite->flush_rules();
		}
	}
}

new MosaicSourceTaxonomy();
