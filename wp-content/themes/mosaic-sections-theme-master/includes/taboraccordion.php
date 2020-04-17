<?php

define( 'TABACCORDION_TABLE', $table_prefix . 'taboraccordion' );
add_shortcode( "tab", "tabaccordion_tab" );
add_shortcode( "accordion", "tabaccordion_accordion" );
add_action( 'admin_menu', 'tabaccordion_admin_menu' );
add_action( 'admin_init', 'tabaccordion_admin_init' );
add_action( 'wp_enqueue_scripts', 'tabaccordion_scripts' );

function tabaccordion_admin_menu() {
	// Set admin as the only one who can use Inventory for security
	$allowed_group = 'manage_options';
	// Add the admin panel page. Use permissions pulled from above
	if ( function_exists( 'add_menu_page' ) ) {
		add_menu_page( "Tabs/Accordions", "Tabs/Accordions", $allowed_group, 'tabaccordion_admin', 'tabaccordion_admin' );
	}
}

function tabaccordion_admin_init() {
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'jquery-ui-core' );
	wp_enqueue_script( 'jquery-ui-sortable' );
	wp_enqueue_script( 'jquery-ui-draggable' );
	wp_enqueue_script( 'jquery-ui-droppable' );
	wp_register_script( 'tabaccordion', TEMPLATE_URL . '/js/tabaccordion-admin.js' );
	wp_enqueue_script( 'tabaccordion' );
	wp_register_style( 'tabaccordion', TEMPLATE_URL . '/css/style-admin-tabaccordion.css' );
	wp_enqueue_style( 'tabaccordion' );
}

function tabaccordion_scripts() {
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'acg-tabaccordion', TEMPLATE_URL . '/js/jquery.tabaccordion.js' );
	wp_enqueue_script( 'acg-easing', TEMPLATE_URL . '/js/jquery.easing.1.2.js' );
}

function tabaccordion_tab( $atts ) {
	$atts["type"] = "tab";

	return tabaccordion( $atts );
}

function tabaccordion_accordion( $atts ) {
	$atts["type"] = "accordion";

	return tabaccordion( $atts );
}

// *** Function to list contents from shortcode
function tabaccordion( $atts, $content = NULL ) {
	$catid     = 0;
	$grouplist = $catlist = $tabset = $where = "";
	$atts      = wp_parse_args( $atts, [ "category" => NULL, "type" => "tab", "group" => NULL ] );
	extract( $atts );
	$group    = $atts['group'];
	$category = $atts['category'];
	$type     = $atts['type'];

	global $wpdb;

	if ( $group ) {
		$group = $wpdb->get_var( $wpdb->prepare( "SELECT groupname FROM " . TABACCORDION_TABLE . " groupname WHERE groupname = %s LIMIT 1", $group ) );
		$where = ( $group ) ? $wpdb->prepare( ' WHERE groupname = %s', $group ) : '';
	}

	if ( $category ) {
		$category = $wpdb->get_var( $wpdb->prepare( "SELECT category FROM " . TABACCORDION_TABLE . " category WHERE category = %s LIMIT 1", $category ) );
		if ( $category ) {
			$where .= ( $where ) ? ' AND' : ' WHERE';
			$where .= $wpdb->prepare( ' category = %s', $category );
		}
	}

	if ( ! $group ) {
		$query   = "SELECT groupname FROM " . TABACCORDION_TABLE . " GROUP BY groupname ORDER BY groupname";
		$results = $wpdb->get_results( $query );
		foreach ( $results as $cat ) {
			if ( $cat->groupname ) {
				$grouplist .= '<option value="' . tabaccordion_prep_category( $cat->groupname ) . '">' . stripslashes( $cat->groupname ) . '</option>';
			}
		}
		if ( $grouplist ) {
			$grouplist = '<select name="group" class="tabaccordiongroup"><option value="">All Groups</option>' . $grouplist . '</select>';
		}
	}
	if ( ! $category ) {
		$query   = "SELECT category FROM " . TABACCORDION_TABLE . " GROUP BY category ORDER BY category";
		$results = $wpdb->get_results( $query );
		foreach ( $results as $cat ) {
			if ( $cat->category ) {
				$catlist .= '<option value="' . tabaccordion_prep_category( $cat->category ) . '">' . stripslashes( $cat->category ) . '</option>';
			}
		}
		if ( $catlist ) {
			$catlist = '<select name="category" class="tabaccordioncategory"><option value="">All Categories</option>' . $catlist . '</select>';
		}
	}

	if ( $group ) {
		$grouplist = '<h2 class="tabaccordiongrouptitle">' . $group . "</h2>";
	}
	if ( $category ) {
		$catlist = '<h2 class="tabaccordioncategorytitle">' . $category . " </h2>";
	}
	$query = "SELECT id, title, content, groupname, category FROM " . TABACCORDION_TABLE . $where . " ORDER BY sort_order";

	$results     = $wpdb->get_results( $query );
	$lastq_group = "";
	$active      = ' active';
	MosaicSocialMedia::ignore();
	foreach ( $results as $row ) {
		$tabset     .= '<a href="javascript:void(0);" class="tab tab_' . $row->id . $active . '" data-tab="' . $row->id . '">' . stripslashes( $row->title ) . '</a>';
		$content    .= ( $type != 'tab' ) ? '<div class="accordion ' . tabaccordion_prep_category( $row->category ) . '">
		<a class="accordion tab_' . $row->id . $active . '" data-tab="' . $row->id . '" href="javascript:void(0);">' . stripslashes( $row->title ) . '</a>' : '';
		$tabcontent = stripslashes( $row->content );
		$tabcontent = apply_filters( "the_content", $tabcontent );
		$content    .= '<div class="content content_' . $row->id . $active . '">' . $tabcontent . '</div>';
		$content    .= ( $type != 'tab' ) ? '</div>' : '';
		$active     = '';
	}
	MosaicSocialMedia::ignore( FALSE );
	if ( $type == 'tab' ) {
		$content = '<div class="tabaccordion_tabs">' . $tabset . '</div><div class="tabaccordion_tabcontent">' . $content . '</div>';
	} else {
		$content = $catlist . $content;
	}
	$content = '<div class="tabaccordion type_' . $type . '">' . $content . '</div>';

	return apply_filters( "tabaccordion", $content );

}


function tabaccordion_prep_category( $cat ) {
	return strtolower( stripslashes( str_replace( " ", "_", $cat ) ) );
}

function tabaccordion_admin() {
	tabaccordion_check_tables();
	global $wpdb;
	define( "PSS_SELF", "admin.php?page=tabaccordion_admin" );
	$id           = ( isset( $_GET["id"] ) ) ? $_GET["id"] : "";
	$id           = ( isset( $_POST["id"] ) ) ? $_POST["id"] : $id;
	$groupname    = ( isset( $_GET["groupname"] ) ) ? $_GET["groupname"] : "";
	$groupname    = ( isset( $_POST["groupname"] ) ) ? $_POST["groupname"] : $groupname;
	$master_group = $groupname;
	$action       = ( isset( $_GET["action"] ) ) ? $_GET["action"] : '';
	$action       = ( isset( $_POST["action"] ) ) ? $_POST["action"] : $action;
	$action       = strtolower( $action );
	if ( $action == "save sort" ) {
		$action = "sort";
	}

	if ( $action == "sort" ) {
		$sort      = $_POST["tabaccordionsort"];
		$sort      = explode( ",", $sort );
		$sortorder = 1;
		foreach ( $sort as $id ) {
			if ( $id ) {
				$wpdb->query( "UPDATE " . TABACCORDION_TABLE . " SET sort_order=" . $sortorder++ . " WHERE id=" . (int) $id );
			}
		}
		$action = "";
	}

	echo '<div class="wrap tabaccordion">';
	echo '<h2>Manage Tab/Accordion Interfaces</h2>';
	if ( $id || $action ) {
		$title = $content = $sort_order = $group = $category = $newcategory = "";
		if ( $id == "new" ) {
			$id = 0;
		}
		$strmessage = $imagequery = "";
		// *** Saving
		if ( $action == "save" ) {
			extract( $_POST );
			if ( ! $groupname ) {
				$groupname = $master_group;
			}
			$strmessage .= ( ! $title ) ? "Title required.<br>" : '';
			$strmessage .= ( ! $content ) ? "Content required.<br>" : '';
			$category   = ( $category == "||NEW||" ) ? $newcategory : $category;

			if ( ! $strmessage ) {
				$query = TABACCORDION_TABLE . ' SET title=%s,
					content= %s,
					groupname= %s,
					category= %d, sort_order = %d';

				$query = $wpdb->prepare( $query, $title, $content, $groupname, $category, $sort_order );


				$query = ( $id ) ? "UPDATE " . $query . " WHERE id=" . (int) $id : 'INSERT INTO ' . $query;
				$wpdb->query( $query );
				$id        = $action = "";
				$groupname = $group;
			} else {
				echo '<div class="error"><p>' . $strmessage . '</p></div>';
				$action = '';
			}
		}

		if ( $action == "delete" ) {
			if ( $groupname || ! $id ) {
				$wpdb->query( $wpdb->prepare( "DELETE FROM " . TABACCORDION_TABLE . " WHERE groupname= %s", $groupname ) );
				$groupname = "";
			} else if ( $id ) {
				$wpdb->query( $wpdb->prepare( "DELETE FROM " . TABACCORDION_TABLE . " WHERE id= %d", $id ) );
				$id = "";
			}
			$action = "";
		}

		if ( $id !== "" && ( ! $action || $strmessage ) ) {
			$query   = "SELECT * FROM " . TABACCORDION_TABLE . " WHERE id=" . ( $id * 1 );
			$results = $wpdb->get_results( $query );
			$id      = $dr_num = $ps_num = $type = $image = $description = "";
			$type    = "ps";
			foreach ( $results as $row ) {
				extract( (array) $row );
			}
			$categorylist = tabaccordion_buildlist( "category", $category );
			if ( ! $id && ! $groupname ) {
				$h3title = 'Add New Tab/Accordion Set';
			} else if ( ! $id && $groupname ) {
				$h3title = 'Add Item to Set <em>' . $groupname . '</em>';
			} else {
				$h3title = 'Edit Item in Set <em>' . $groupname . '</em>';
			}
			echo '<h3>' . $h3title . '</h3>';
			echo '<form name="dcc" action="' . PSS_SELF . '" method="post" enctype="multipart/form-data">';
			echo '<input type="hidden" name="id" value="' . $id . '" />';
			if ( ! $id && ! $groupname ) {
				echo '<div><label for="group">Set Name</label><input type="text" name="groupname" value="' . stripslashes( $group ) . '" /></div>';
			} else {
				echo '<input type="hidden" name="groupname" value="' . stripslashes( $groupname ) . '" />';
			}
			echo '<div><label for="title">Title</label><textarea name="title" class="title">' . stripslashes( $title ) . '</textarea></div>';
			echo '<div class="editor"><p class="title">Content</p>';
			wp_editor( stripslashes( $content ), "content", [ "textarea_rows" => 6 ] );
			echo '</div>';
			echo '<div><label for="category">Category</label>' . $categorylist . '</div>';
			echo '<div class="newcategory"><label for="category">New Category</label><input type="text" name="newcategory" class="category" value="' . stripslashes( $newcategory ) . '" /></div>';
			echo '<div><label for="sort_order">Sort Order</label><input type="text" name="sort_order" size="2" value="' . (int) $sort_order . '"></div>';
			$delete = ( $id ) ? '<a class="button delete" onclick="return confirm(\'Are you sure you want to delete this item?\');" href="' . PSS_SELF . '&id=' . $id . '&action=delete">Delete</a>' : '';
			echo '<div class="submit"><input type="submit" class="button-primary" name="action" value="Save" /><a class="button cancel" href="' . PSS_SELF . '&groupname=' . $groupname . '">Cancel</a>' . $delete . '</div>';
			echo '</form>';
			$action = "form";
		}
	}

	if ( ! $id && ! $action ) {
		if ( ! $groupname ) {
			$query   = "SELECT count(distinct id) AS items, groupname FROM " . TABACCORDION_TABLE . " GROUP BY groupname ORDER BY groupname";
			$results = $wpdb->get_results( $query );
			echo '<form method="post" action="' . PSS_SELF . '">';
			echo '<div class="grid"><a class="button-primary" href="' . PSS_SELF . '&id=new">Create New Set</a></div>';
			echo '<ul id="tabaccordion">';
			foreach ( $results as $row ) {
				$groupname = $row->groupname;
				if ( ! $groupname ) {
					$groupname = '- no name - ';
				}
				echo '<li>
					<a class="title" href="' . PSS_SELF . '&groupname=' . $groupname . '">' . $groupname . '</a>
					<span class="count">(' . $row->items . ' items)</span>
					<a class="delete" href="' . PSS_SELF . '&groupname=' . $groupname . '&action=delete">delete</a>
				</li>';
			}
			echo '</ul>';

		} else {
			echo '<div class="instructions">
				<h4>To place this tab or accordion on a page, use this shortcode:</h4>
				<p>For a tab interface:<br><span class="shortcode"> [tab group="' . $groupname . '"] </span></p>
				<p>For an accordion interface:<br><span class="shortcode"> [accordion group="' . $groupname . '"] </span></p>
				<p>For a specific category, use the category parameter:<br><span class="shortcode"> [tab group="' . $groupname . '" category="My Category"] </span></p>
				</div>';
			echo '<h3>Manage Items in Set <em>' . $groupname . '</em></h3>';
			$query   = $wpdb->prepare( "SELECT id, title FROM " . TABACCORDION_TABLE . " WHERE groupname = %s ORDER BY sort_order", $groupname );
			$results = $wpdb->get_results( $query );
			echo '<form method="post" action="' . PSS_SELF . '&groupname=' . $groupname . '">';
			echo '<ul id="set">';
			foreach ( $results as $row ) {
				echo '<li title="' . $row->id . '"><a class="title" href="' . PSS_SELF . '&id=' . $row->id . '">' . $row->title . '</a></li>';
			}
			echo '</ul>';
			echo '<input type="hidden" name="tabaccordionsort" class="tabaccordionsort" value="">';
			echo '<div class="savesort"><a class="button-primary" href="' . PSS_SELF . '&id=new&groupname=' . stripslashes( $groupname ) . '">Add New Item to Set</a>
				<a class="button cancel" href="' . PSS_SELF . '">Back</a>';
			echo '<input type="submit" name="action" value="Save Sort" id="sortsave" class="button-primary">';
			echo '</div>';
		}
	}
}

function tabaccordion_buildlist( $type, $selected ) {
	global $wpdb;
	// Build category list
	$type    = ( strtolower( $type ) == 'category' ) ? 'category' : 'groupname';
	$query   = "SELECT " . $type . " FROM " . TABACCORDION_TABLE . " GROUP BY " . $type . " ORDER BY " . $type;
	$results = $wpdb->get_results( $query );
	$list    = '<select name="' . $type . '" class="' . $type . 'list">';
	$list    .= '<option value=""> - Select - </option>';
	foreach ( $results as $row ) {
		if ( $row->category ) {
			$list .= '<option value="' . stripslashes( $row->category ) . '"';
			$list .= ( $selected == stripslashes( $row->category ) ) ? ' selected="selected"' : '';
			$list .= '>' . stripslashes( $row->category ) . '</option>';
		}
	}
	$list .= '<option value="||NEW||"> ** Add New ** </option>';
	$list .= '</select>';

	return $list;
}


function tabaccordion_check_tables() {
	global $wpdb;
	if ( $wpdb->get_var( "SHOW TABLES LIKE '" . TABACCORDION_TABLE . "'" ) != TABACCORDION_TABLE ) {
		$sql = "CREATE TABLE " . TABACCORDION_TABLE . " (
            id INT(11) NOT NULL AUTO_INCREMENT,
			title TEXT NULL,
			content TEXT NULL,
			cat INT(11) NULL,
			groupname VARCHAR(100) NULL,
			sort_order INT(11) NULL,
			category VARCHAR(100) NULL,
            PRIMARY KEY (id))";
		$wpdb->query( $sql );
	}
	// Misspelled accordion, so lets clean up the table names too
	$old_table = str_replace( "accordion", "accordian", TABACCORDION_TABLE );
	if ( $wpdb->get_var( "SHOW TABLES LIKE '" . $old_table . "'" ) == $old_table ) {
		// Found the old table.  Copy into new table, then remove
		$wpdb->query( "INSERT INTO " . TABACCORDION_TABLE . " (SELECT * FROM " . $old_table . ")" );
		$wpdb->query( "DROP TABLE " . $old_table );
	}
}

?>