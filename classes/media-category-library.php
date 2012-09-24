<?php

class WPMediaCategoryLibrary {

	/**
	*Variables
	*/
	const nspace = 'wpmediacatlib';
	const pname = 'Media Category';
	const term = 'mediacategory';
	const version = 0.1;
	protected $_plugin_file;
	protected $_plugin_dir;
	protected $_plugin_path;
	protected $_plugin_url;

	var $settings_fields = array();
	var $settings_data = array();
	var $debug = false;

	/**
	*Constructor
	*
	*@return void
	*@since 0.1
	*/
	function __construct() {}

	/**
	*Init function
	*
	*@return void
	*@since 0.1
	*/
	function init() {

		// settings data -- leave at top of constructor

		$this->settings_data = unserialize( get_option( self::nspace . '-settings' ) );

		// set default taxonomy_name

                if ( ! @strlen( $this->settings_data['rewrite_url'] ) ) $this->settings_data['rewrite_url'] = 'mediacat-library';
		if ( ! @strlen( $this->settings_data['taxonomy_name'] ) ) $this->settings_data['taxonomy_name'] = 'media-category';
                if ( ! @strlen( $this->settings_data['title'] ) ) $this->settings_data['title'] = 'Media Category Library';

		if ( is_admin() ) {

			// add menus

			add_action( 'admin_menu', array( &$this, 'add_admin_menus' ), 30 );

			// enqueue css/js

			add_action( 'admin_enqueue_scripts', array( &$this, 'add_admin_scripts' ), 10, 1 );

			// settings fields

			$this->settings_fields = array(
							'legend_1' => array(
								'label' => __( 'General Settings', self::nspace ),
								'type' => 'legend'
							),
							'taxonomy_name' => array(
								'label' => __( 'Taxonomy Name (should be all lowercase)', self::nspace ),
								'type' => 'text',
								'default' => 'media-category'
							),
                                                        'rewrite_url' => array(
                                                                'label' => __( 'Media Category Library Rewrite URL', self::nspace ),
                                                                'type' => 'text',
                                                                'default' => 'mediacat-library'
                                                        ),
                                                        'title' => array(
                                                                'label' => __( 'Media Category Library Title', self::nspace ),
                                                                'type' => 'text',
                                                                'default' => 'Media Category Library'
                                                        ),
                                                        'include_css' => array(
                                                                'label' => __( 'Include CSS', self::nspace ),
                                                                'type' => 'select',
                                                                'values' => array( 'yes' => 'Yes', 'no' => 'No' ),
                                                                'default' => 'yes'
                                                        )
						);
		}

		// add custom rewrites

		add_filter( 'generate_rewrite_rules', array( &$this, 'media_category_rewrites' ) );
		add_filter( 'query_vars', array( &$this, 'media_category_query_vars_actions' ) );
		add_action( 'parse_request', array( &$this, 'media_category_parse_request_actions' ) );

		// flush rewrite rules, if necessary

		add_action( 'wp_loaded', array( &$this, 'flush_rewrite_rules' ) );

		// Media category taxonomy

		add_action( 'init', array( &$this, 'create_taxonomy' ) );

		// shortcodes

		add_shortcode( 'mediacat', array( &$this, 'get_mediacategory_shortcode' ) );
                add_shortcode( 'mediacatform', array( &$this, 'get_mediacategoryform_shortcode' ) );
	}

        /**
        *Flush rewrite rules function
        *
        *@return void
        *@since 0.1
        */
        function flush_rewrite_rules(){
                $rules = get_option( 'rewrite_rules' );
                if ( ! isset( $rules[$rewrite_url . '/?$'] ) ) {
			global $wp_rewrite;
			$wp_rewrite->flush_rules();
                }
        }

        /**
        *Get rewrite url
        *
        *@return void
        *@since 0.1
        */
	function get_rewrite_url() {
		$rewrite_url = 'mediacat\-library';
                if ( $this->settings_data['rewrite_url'] ) {
                        $rewrite_url = $this->settings_data['rewrite_url'];
                        $rewrite_url = str_replace( '-', '\-', $rewrite_url );
                }
		return $rewrite_url;
	}

        /**
        *Rewrites function
        *
        *@return void
        *@since 0.1
        */
        function media_category_rewrites( $wpr ) {
                $rewrite_url = $this->get_rewrite_url();
                $rules = array(
                        'mediacat\-pages/(\d+)/?' => 'index.php?mediacat_pages=1&attachment_id=' .
                                $wpr->preg_index(1),
                        $rewrite_url . '/(\d+)/(.*)/(.*)/?' => 'index.php?mediacat_library=1&mediacat_page=' .
                                $wpr->preg_index(1) . '&mediacats=' . $wpr->preg_index(2) . '&mediacat_keyword=' . $wpr->preg_index(3),
                        $rewrite_url . '/(\d+)/(.*)/?' => 'index.php?mediacat_library=1&mediacat_page=' .
                                $wpr->preg_index(1) . '&mediacats=' . $wpr->preg_index(2),
                        $rewrite_url . '/?$' => 'index.php?mediacat_library=1',
                        'mediacat\-del/(\d+)/(\d+)/?' => 'index.php?mediacat_del=' . 
                                $wpr->preg_index(1) . '&attachment_id=' . $wpr->preg_index(2),
                );
                $wpr->rules = $rules + $wpr->rules;
        }

        /**
        *Query vars function
        *
        *@return void
        *@since 0.1
        */
        function media_category_query_vars_actions( $query_vars ) {
                $query_vars[] = 'mediacat_library';
                $query_vars[] = 'mediacat_page';
                $query_vars[] = 'mediacat_keyword';
                $query_vars[] = 'mediacat_pages';
                $query_vars[] = 'attachment_id';
                $query_vars[] = 'mediacats';
                $query_vars[] = 'mediacat_del';
                return $query_vars;
        }

        /**
        *Title function
        *
        *@return string
        *@since 0.1
        */
        function media_category_title () {
                return $this->settings_data['title'];
        }

        /**
        *Body class function
        *
        *@return void
        *@since 0.1
        */
        function body_class ( $classes ) {
                $classes[] = self::nspace;
                return $classes;
        }

        /**
        *Media categories
        *
        *@return array
        *@since 0.1
        */
        function get_media_categories ( $by_name_and_id = false ) {
                global $wpdb;
                $sub_sql = "SELECT term_taxonomy_id FROM " . $wpdb->term_taxonomy . " WHERE taxonomy='" . $this->settings_data['taxonomy_name'] . "'";
                $sql = "SELECT DISTINCT t.term_id, t.name, t.slug FROM " . $wpdb->term_taxonomy . " AS x " .
                        "LEFT JOIN " . $wpdb->terms . " AS t ON t.term_id = x.term_id WHERE x.term_taxonomy_id IN($sub_sql) ORDER BY t.name";
                $results = $wpdb->get_results( $sql, ARRAY_A );
                $mediacats = array();
                $key = 'slug';
                $val = 'name';
                if ( $by_name_and_id ) {
                        $key = 'name';
                        $val = 'term_id';
                }
                foreach ( $results as $result ) $mediacats[$result[$key]] = $result[$val];
                return $mediacats;
        }

        /**
        *Media category content
        *
        *@return string
        *@since 0.1
        */
        function media_category_content( $content ) {
                ob_start();
                include $this->get_plugin_path() . '/views/search.php';
                $content = ob_get_contents();
                ob_end_clean();
                return $content;
        }

        /**
        *Media category remove filters
        *
        *@return void
        *@since 0.1
        */
        function media_category_remove_filters() {
                remove_filter( 'the_title', array( &$this, 'media_category_title' ) );
                remove_filter( 'the_content', array( &$this, 'media_category_content' ) );
        }

        /**
        *Media category add filters
        *
        *@return void
        *@since 0.1
        */
        function media_category_add_filters() {
                add_filter('the_title', array( &$this, 'media_category_title' ) );
                add_filter('the_content', array( &$this, 'media_category_content' ) );
        }

        /**
        *Parse request function
        *
        *@return void
        *@since 0.1
        */
        function media_category_parse_request_actions( &$wp ) {
                global $wpdb;
                if ( array_key_exists( 'mediacat_library', $wp->query_vars ) ) {
                        if ( array_key_exists( 'mediacat_page', $wp->query_vars ) ) {
                                $_REQUEST['pnum'] = $wp->query_vars['mediacat_page'];
                        }
                        else {
                                if ( ! $_REQUEST['mediacat_library_submit'] ) {
                                        foreach ( $this->get_media_categories() as $slug => $name ) $_REQUEST['media-categories'][] = $slug;
                                }
                        }
                        if ( array_key_exists( 'mediacats', $wp->query_vars ) ) {
                                $_REQUEST['media-categories'] = explode( ',', $wp->query_vars['mediacats'] );
                        }
                        if ( array_key_exists( 'mediacat_keyword', $wp->query_vars ) ) {
                                $_REQUEST['keyword'] = $wp->query_vars['mediacat_keyword'];
                        }

                        global $wp_query;
                        $wp_query->post_count = 1;

                        add_action( 'get_header', array( &$this, 'media_category_remove_filters' ) );
                        add_action( 'get_sidebar', array( &$this, 'media_category_remove_filters' ) );
                        add_action( 'get_footer', array( &$this, 'media_category_remove_filters' ) );
                        add_action( 'loop_start', array( &$this, 'media_category_add_filters' ) );

                        // add title

                        add_filter( 'wp_title', array( &$this, 'media_category_title' ) );

                        // add body class

                        add_filter( 'body_class', array( &$this, 'body_class' ) );

                        // add css

                        if ( $this->settings_data['include_css'] != 'no' )
                                wp_enqueue_style( 'wp-media-category-library', $this->get_plugin_url() . 'css/media-category-library.css' );

                        // include page template

                        include get_page_template();
                        exit;
                }
                elseif ( array_key_exists( 'mediacat_pages', $wp->query_vars ) ) {
                        if ( current_user_can( 'manage_options' ) ) {

                                // get file name

                                $row = $wpdb->get_row( "SELECT guid FROM " . $wpdb->posts . " WHERE ID = '" . $wpdb->escape( $wp->query_vars['attachment_id'] ) . "'", ARRAY_A );
                                $file_name = basename( $row['guid'] );

                                // get pages or posts that reference this file

                                $sql = "SELECT SQL_CALC_FOUND_ROWS ID FROM " . $wpdb->posts .
                                        " WHERE post_type <> 'revision' AND post_content LIKE '" . $wpdb->escape( '%' . $file_name . '%' ) . "'";
                                $results = $wpdb->get_results( $sql, ARRAY_A );
                                $sql = 'SELECT FOUND_ROWS() AS found_rows';
                                $row = $wpdb->get_row( $sql, ARRAY_A );
                                echo '<h3>Pages that include the following document: ' . $file_name . '</h3>';
                                $label = 'pages';
                                if ( $row['found_rows'] == 1 ) $label = 'page';
                                echo '<h4>' . $row['found_rows'] . ' ' . $label . ' found.</h4>';
?>
<?php if ( $row['found_rows'] > 0 ): ?>
                <ul style="list-style:disc; margin: 50px 0 0 100px;">
<?php foreach ( $results as $result ): ?>
                        <li><a href="<?php echo admin_url(); ?>post.php?post=<?php echo $result['ID']; ?>&action=edit"><?php echo get_the_title( $result['ID'] ); ?></a></li>
<?php endforeach; ?>
                </ul>

<?php endif; ?>
                <p style="margin-top: 100px;text-align:center">
                        <a href="#" onclick="parent.tb_remove();return false;"><?php _e( 'Close', self::nspace ); ?></a>
                </p>
<?php
                        }
                        else echo 'Not authorized.';
                        exit;
                }
                elseif ( array_key_exists( 'mediacat_del', $wp->query_vars ) ) {
                        if ( current_user_can( 'manage_options' ) && $wp->query_vars['mediacat_del'] > 0 && $wp->query_vars['attachment_id'] > 0 ) {
                                wp_delete_term( $wp->query_vars['mediacat_del'], $this->settings_data['taxonomy_name'] );
                                header( "Location: /wp-admin/media.php?attachment_id=" . $wp->query_vars['attachment_id'] . "&action=edit" );
                        }
                        else echo 'Not authorized.';
                        exit;
                }
        }

	/**
	*Create taxonomy
	*
	*@return void
	*@since 0.1
	*/
	function create_taxonomy() {
		$labels = array(
				'name' => __( 'Media Category', self::nspace ),
				'singular_name' => __( 'Media Category', self::nspace ),
				'search_items' => __( 'Search Media Categories', self::nspace ),
				'all_items' => __( 'All Media Categories', self::nspace ),
				'parent_item' => __( 'Parent Media Category', self::nspace ),
				'parent_item_colon' => __( 'Parent Media Category', self::nspace ),
				'edit_item' => __( 'Edit Media Category', self::nspace ),
				'update_item' => __( 'Update Media Category', self::nspace ),
				'add_new_item' => __( 'Add New Media Category', self::nspace ),
				'new_item_name' => __( 'New Media Category Name', self::nspace ),
				'menu_name' => __( 'Media Category', self::nspace )
			);
		$args = array(
				'hierarchical' => true,
				'labels' => $labels,
				'show_ui' => true,
				'query_var' => true,
				'rewrite' => true
			);
		register_taxonomy( $this->settings_data['taxonomy_name'], 'attachment', $args );
	}

	/**
	*Media category library
	*
	*@return void
	*@since 0.1
	*/
	function mediacat_library ( $frontend = false ) {
		global $wpdb;
		if ( $_REQUEST['mediacat_document_id'] ) {
			$date = $_REQUEST['year'] . '-' . $_REQUEST['month'] . '-' . $_REQUEST['day'];
			$sql = "UPDATE wp_posts SET post_date='$date 00:00:00',post_modified='$date 00:00:00',post_date_gmt='$date 00:00:00'," .
				"post_modified_gmt='$date 00:00:00' WHERE ID = " . $_REQUEST['mediacat_document_id'];
			$wpdb->query( $sql );
		}

		// set terms

		$selected_terms = array();
		if ( $_REQUEST['cat'] ) $selected_terms[] = "'" . $wpdb->escape( $_REQUEST['cat'] ) . "'";
		elseif ( $_REQUEST['media-categories'] ) {
			foreach ( $_REQUEST['media-categories'] as $cat ) $selected_terms[] = "'" . $wpdb->escape( $cat ) . "'";
		}
		else foreach ( $this->get_media_categories() as $slug => $name ) $selected_terms[] = "'" . $wpdb->escape( $slug ) . "'";

		// pagination settings

		$posts_per_page = 20;
		$page = $_REQUEST['pnum'];
		if ( ! $page ) $page = 0;
		else $page -= 1;
		$start = $page * $posts_per_page;
		$start_record = $start + 1;

		// subquery for media categories

		if ( count( $selected_terms ) > 0 ) {

			// create sub query

			$sub_sql = "SELECT x.term_taxonomy_id FROM " . $wpdb->term_taxonomy . " AS x " .
				"LEFT JOIN " . $wpdb->terms . " AS t ON x.term_id = t.term_id WHERE " .
				"t.slug IN(" . implode( ",", $selected_terms ) . ")";

			// main query that uses subquery

			$sql = "SELECT SQL_CALC_FOUND_ROWS p.ID, p.post_title, p.post_mime_type, p.post_excerpt, p.post_date FROM " . $wpdb->posts . " AS p " .
				"LEFT JOIN " . $wpdb->term_relationships . " AS r ON p.ID = r.object_id " .
				"WHERE r.term_taxonomy_id IN($sub_sql) AND p.post_type='attachment' ";

			// keyword

			if ( $_REQUEST['keyword'] ) {
				$where = array();
				$k_fields = array( 'post_title', 'post_excerpt', 'post_content', 'guid' );
				$keyword = "'%" . $wpdb->escape( $_REQUEST['keyword'] ) . "%'";
				foreach ( $k_fields as $field ) $where[] = "$field LIKE $keyword";
				$sql .= "AND (" . implode( " OR ", $where ) . ") ";
			}

			// order by and limit for pagination

			$sql .= "ORDER BY p.post_title LIMIT $start, " . $posts_per_page;

			//echo "<p>$sql</p>";

			// get results, found rows, and total pages

			$results = $wpdb->get_results( $sql, ARRAY_A );
			$sql = 'SELECT FOUND_ROWS() AS found_rows';
			$row = $wpdb->get_row( $sql, ARRAY_A );
			$total_pages = ceil( $row['found_rows'] / $posts_per_page );
		}
		$pagination = $this->get_mediacat_library_pagination( $total_pages, $page, $frontend );
		if ( $row['found_rows'] ) $this->mediacat_library_list( $results, $row['found_rows'], $frontend, $start_record, $posts_per_page, $total_pages, $pagination );
		else echo '<p style="clear:both">' . __( 'No results found.', self::nspace ) . '</p>';
	}

        /**
        *Media categories library list
        *
        *@return void
        *@since 0.1
        */
        function mediacat_library_list( $results, $total_records, $frontend = false, $start_record, $posts_per_page, $total_pages, $pagination ) {
                include( $this->get_plugin_path() . '/views/list.php' );
        }

        /**
        *Pagination
        *
        *@return void
        *@since 0.1
        */
        function get_mediacat_library_pagination( $total_pages, $page, $frontend = false ) {
                if ( ! $_REQUEST['pnum'] ) $_REQUEST['pnum'] = 1;
                if ( ! $_REQUEST['media-categories'] ) $_REQUEST['media-categories'][] = 'none';
                $prev_link = '/' . $this->settings_data['rewrite_url'] . '/' . ( $_REQUEST['pnum'] - 1 ) . '/' . implode( ',', $_REQUEST['media-categories'] ) . '/';
                if ( $_REQUEST['keyword'] ) $prev_link .= rawurlencode( $_REQUEST['keyword'] ) . '/';
                $next_link = '/' . $this->settings_data['rewrite_url'] . '/' . ( $_REQUEST['pnum'] + 1 ) . '/' . implode( ',', $_REQUEST['media-categories'] ) . '/';
                if ( $_REQUEST['keyword'] ) $next_link .= rawurlencode( $_REQUEST['keyword'] ) . '/';
                if ( ! $frontend ) {
                        $tmp = array();
                        if ( ! $_REQUEST['pnum'] ) $_REQUEST['pnum'] = 1;
                        foreach ( array( 'cat','keyword' ) as $item ) {
                                if ( $_REQUEST[$item] ) $tmp[] = $item . '=' . rawurlencode( $_REQUEST[$item] );
                        }
                        $prev_link = $this->get_mediacat_library_admin_url() . '&pnum=' . ( $_REQUEST['pnum'] - 1 );
                        $next_link = $this->get_mediacat_library_admin_url() . '&pnum=' . ( $_REQUEST['pnum'] + 1 );
                }
                $previous = '<a title="' . __( 'Previous', self::nspace ) . '" class="prev-page" href="' . $prev_link . '">&lsaquo;</a>';
                $next = '<a title="' . __('Next', self::nspace ) . '" class="next-page" href="' . $next_link . '">&rsaquo;</a>';
                if ( $_REQUEST['pnum'] > 1 && $_REQUEST['pnum'] < $total_pages ) return $previous . $next;
                if ( $_REQUEST['pnum'] == $total_pages && $total_pages > 1 ) return $previous;
                elseif ( $total_pages > 1 ) return $next;
        }

        /**
        *Admin url
        *
        *@return string
        *@since 0.1
        */
        function get_mediacat_library_admin_url() {
                $tmp = array();
                if ( ! $_REQUEST['pnum'] ) $_REQUEST['pnum'] = 1;
                foreach ( array( 'cat','keyword' ) as $item ) {
                        if ( $_REQUEST[$item] ) $tmp[] = $item . '=' . rawurlencode( $_REQUEST[$item] );
                }
                $url = admin_url() . 'upload.php?page=wpmediacatlib-library';
                if ( $tmp ) $url .= '&' . implode( '&', $tmp );
                return $url;
        }

        /**
        *Pagination details
        *
        *@return string
        *@since 0.1
        */
        function get_mediacat_library_pagination_details( $start_record, $posts_per_page, $total_records, $total_pages, $pagination = '' ) {
                $page = $_REQUEST['pnum'];
                if ( ! $page ) $page = 1;
                if ( ! $start_record ) $start_record = 1;
                $end_record = ( $start_record + $posts_per_page - 1 );
                if ( $end_record > $total_records ) $end_record = $total_records;
                if ( $pagination != '' ) $total_pages = $total_pages . ' <span class="pagination-links">' . $pagination . '</span>';
                return '<div class="pagination-records">' . __( 'Displaying', self::nspace ) . ' ' .
                        $start_record . ' &mdash; ' . $end_record . ' ' . __( 'of', self::nspace ) . ' ' .
                        $total_records . ' ' . __( 'total records', self::nspace ) . '</div>' .
                        '<div class="pagination-pages">' . __( 'Page', self::nspace ) . ' ' . $page . ' ' . __( 'of', self::nspace ) .
                        ' ' . $total_pages . '</div>';
        }

        /**
        *Shortcode for form
        *
        *@return string
        *@since 0.1
        */
        function get_mediacategoryform_shortcode( $atts = array() ) {

                // add css

                if ( $this->settings_data['include_css'] != 'no' )
                        wp_enqueue_style( 'wp-media-category-library', $this->get_plugin_url() . 'css/media-category-library.css' );
                return $this->media_category_content();
        }

        /**
        *Shortcode for list of files
        *
        *@return string
        *@since 0.1
        */
        function get_mediacategory_shortcode( $atts = array() ) {
                ob_start(); 	
                $terms = get_terms( $this->settings_data['taxonomy_name'], array( 'hide_empty' => false ) );
                $cats = explode( ',', $atts['cats'] );
                $selected_terms = array();
                foreach ( $cats as $cat ) {
                        $cat = trim( $cat );
                        foreach ( $terms as $term ) {
                                if ( $term->name == $cat ) {
                                        $selected_terms[] = $term->slug;
                                        break;
                                }
                        }
                }
                $args = array(
                                'numberposts' => -1,
                                'order_by' => 'menu_order',
                                'order' => 'ASC',
                                'post_type' => 'attachment',
                                'tax_query' => array(
                                                        array(
                                                        'taxonomy' => $this->settings_data['taxonomy_name'],
                                                        'field' => 'slug',
                                                        'terms' => $selected_terms
                                                        )
                                                )
                );
                $posts = get_posts($args);
?>
        <table>
                <thead>
                        <tr><th>Title</th><th>File Name</th><th>Caption</th><th>Date</th></tr>
                </thead>
                <tbody>
<?php foreach ( $posts as $post ): ?>
<?php $link = wp_get_attachment_url( $post->ID ); ?>
<?php $time_format = get_option( 'date_format' ) . ' ' .  get_option( 'time_format' ); ?>
                        <tr>
                                <td><a href="<?php echo $link; ?>"><?php echo get_the_title( $post->ID ); ?></a></td>
                                <td><?php echo basename( $link ); ?></td>
                                <td><?php echo $post->post_excerpt; ?></td>
                                <td><?php echo get_the_time( $time_format, $post->ID ); ?></td>
                        </tr>
<?php endforeach; ?>
                </tbody>
        </table>
<?php
                $content = ob_get_contents();
                ob_end_clean();
                return $content;
        }

        /**
        *Debug function
        *
        *@return void
        *@since 0.1
        */
        function debug ( $msg ) {
                if ( $this->debug ) {
                        error_log( 'DEBUG: ' . $msg );
                }
        }

        /**
        *Add admin menus
        *
        *@return void
        *@since 0.1
        */
        function add_admin_menus () {
                if ( current_user_can( 'manage_options' ) ) {
                        add_options_page( self::pname, self::pname, 'manage_options', self::nspace . '-settings', array( &$this, 'settings_page' ) );
                        add_media_page( $this->settings_data['title'], $this->settings_data['title'], 'manage_options', self::nspace . '-library', array( &$this, 'mediacat_library' ) );
                }
        }

        /**
        *Admin scripts
        *
        *@return void
        *@since 0.1
        */
        function add_admin_scripts ( $hook ) {
                global $post;
                if ( $hook == 'media.php' || $hook == 'media-new.php' || $hook == 'media-upload-popup' ) {
                        wp_enqueue_script( 'wp-media-category', $this->get_plugin_url() . 'js/media-category.js', array( 'jquery' ), self::version, true );
                        $options = array();
                        $terms = get_terms( $this->settings_data['taxonomy_name'], 'hide_empty=0' );
                        foreach ( $terms as $term ) $options[] = $term->name;
                        $cats = $this->get_media_categories ( true );
                        $args = array(
                                        'add_label' => __( 'Add/Edit Category', self::nspace ),
                                        'del_label' => __( 'Delete Category', self::nspace ),
                                        'plugin_url' => $this->get_plugin_url(),
                                        'taxonomy_name' => $this->settings_data['taxonomy_name'],
                                        'options' => $options,
                                        'cats' => $cats
                                );
                        wp_localize_script( 'wp-media-category', 'media_category', $args );
                }
                elseif ( $hook == 'media_page_wpmediacatlib-library' ) {
                        wp_enqueue_script( 'wp-media-category-library', $this->get_plugin_url() . 'js/media-category-library.js', array( 'jquery' ), self::version, true );
                        wp_enqueue_script( 'thickbox' );
                }
        }

        /**
        *Settings page
        *
        *@return void
        *@since 0.1
        */
        function settings_page () {
                if($_POST[self::nspace . '_update_settings']) $this->update_settings();
                $this->show_settings_form();
        }

        /**
        *Show settings form
        *
        *@return void
        *@since 0.1
        */
        function show_settings_form () {
                include( $this->get_plugin_path() . '/views/admin_settings_form.php' );
        }

        /**
        *Get single value from unserialized data
        *
        *@return string
        *@since 0.1
        */
        function get_settings_value( $key = '' ) {
                return $this->settings_data[$key];
        }

        /**
        *Remove option when plugin is deactivated
        *
        *@return void
        *@since 0.1
        */
        function delete_settings () {
                delete_option( $this->option_key );
        }

        /**
        *Is associative array function
        *
        *@return string
        *@since 0.1
        */
        function is_assoc ( $arr ) {
                if ( isset ( $arr[0] ) ) return false;
                return true;
        }

        /**
        *Display a select form element
        *
        *@return string
        *@since 0.1
        */
        function select_field( $name, $values, $value, $use_label = false, $default_value = '', $custom_label = '' ) {
                ob_start();
                $label = '-- please make a selection --';
                if (@strlen($custom_label)) {
                        $label = $custom_label;
                }

                // convert indexed array into associative

                if ( ! $this->is_assoc( $values ) ) {
                        $tmp_values = $values;
                        $values = array();
                        foreach ( $tmp_values as $tmp_value ) {
                                $values[$tmp_value] = $tmp_value;
                        }
                }
        ?>
        <select name="<?php echo $name; ?>" id="<?php echo $name; ?>">
<?php if ( $use_label ): ?>
                <option value=""><?php echo $label; ?></option>

<?php endif; ?>
<?php foreach ( $values as $val => $label ) : ?>
                <option value="<?php echo $val; ?>"<?php if ($value == $val || ( $default_value == $val && @strlen( $default_value ) && ! @strlen( $value ) ) ) : ?> selected="selected"<?php endif; ?>><?php echo $label; ?></option>
<?php endforeach; ?>

        </select>
<?php
                $content = ob_get_contents();
                ob_end_clean();
                return $content;
        }

        /**
        *Update settings form
        *
        *@return void
        *@since 0.1
        */
        function update_settings () {
                $data = array();
                foreach( $this->settings_fields as $key => $val ) {
                        if( $val['type'] != 'legend' ) $data[$key] = $_POST[$key];
                }
                $this->set_settings( $data );
        }

        /**
        *Update serialized array option
        *
        *@return void
        *@since 0.1
        */
        function set_settings ( $data ) {
                update_option( self::nspace . '-settings', serialize( $data ) );
                $this->settings_data = $data;
        }

        /**
        *Set plugin file
        *
        *@return void
        *@since 0.1
        */
        function set_plugin_file( $plugin_file ) {
                $this->_plugin_file = $plugin_file;
        }

        /**
        *Get plugin file
        *
        *@return string
        *@since 0.1
        */
        function get_plugin_file() {
                return $this->_plugin_file;
        }

        /**
        *Set plugin directory
        *
        *@return void
        *@since 0.1
        */
        function set_plugin_dir( $plugin_dir ) {
                $this->_plugin_dir = $plugin_dir;
        }

        /**
        *Get plugin directory
        *
        *@return string
        *@since 0.1
        */
        function get_plugin_dir() {
                return $this->_plugin_dir;
        }

        /**
        *Set plugin file path
        *
        *@return void
        *@since 0.1
        */
        function set_plugin_path( $plugin_path ) {
                $this->_plugin_path = $plugin_path;
        }

        /**
        *Get plugin file path
        *
        *@return string
        *@since 0.1
        */
        function get_plugin_path() {
                return $this->_plugin_path;
        }

        /**
        *Set plugin URL
        *
        *@return void
        *@since 0.1
        */
        function set_plugin_url( $plugin_url ) {
                $this->_plugin_url = $plugin_url;
        }

        /**
        *Get plugin URL
        *
        *@return string
        *@since 0.1
        */
        function get_plugin_url() {
                return $this->_plugin_url;
        }

}

?>
