<?php

class WPMediaCategory {

        /**
        *Variables
        */
        const nspace = 'wpmediacat';
        const pname = 'Media Category';
        const term = 'mediacategory'
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

		if ( is_admin() ) {

                        // add menus

			add_action( 'admin_menu', array( &$this, 'add_admin_menus' ), 30 );
                        
                        // enqueue css/js

                        add_action( 'admin_enqueue_scripts', array( &$this, 'add_admin_scripts' ), 10, 1 );

			// settings fields

			$this->settings_fields = array();
		}

                // add custom rewrites

                add_filter( 'generate_rewrite_rules', array( &$this, 'media_category_rewrites' ) );
                add_filter( 'query_vars', array( &$this, 'media_category_query_vars_actions' ) );
                add_action( 'parse_request', array( &$this, 'media_category_parse_request_actions' ) );
        }

        /**
        *Rewrites function
        *
        *@return void
        *@since 0.1
        */
        function media_category_rewrites( $wpr ) {
                $rules = array(
                        'document\-pages/(\d+)/?' => 'index.php?mediacat_pages=1&attachment_id=' .
                                $wpr->preg_index(1),
                        'document\-library/(\d+)/(.*)/(.*)/?' => 'index.php?mediacat_library=1&mediacat_page=' .
                                $wpr->preg_index(1) . '&mediacats=' . $wpr->preg_index(2) . '&mediacat_keyword=' . $wpr->preg_index(3),
                        'document\-library/(\d+)/(.*)/?' => 'index.php?mediacat_library=1&mediacat_page=' .
                                $wpr->preg_index(1) . '&mediacats=' . $wpr->preg_index(2),
                        'document\-library/?$' => 'index.php?mediacat_library=1',
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
                return $query_vars;
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
        *Parse request function
        *
        *@return void
        *@since 0.1
        */
        function media_category_parse_request_actions( &$wp ) {
                if ( array_key_exists( 'mediacat_library', $wp->query_vars ) ) {
                        if ( array_key_exists( 'mediacat_page', $wp->query_vars ) ) {
                                $_REQUEST['pnum'] = $wp->query_vars['mediacat_page'];
                        }
                        else {
                                if ( ! $_REQUEST['media_category_submit'] ) {
                                        foreach ( get_media_categories() as $slug => $name ) $_REQUEST['media-categories'][] = $slug;
                                }
                        }
                        if ( array_key_exists( 'mediacats', $wp->query_vars ) ) {
                                $_REQUEST['media-categories'] = explode( ',', $wp->query_vars['mediacats'] );
                        }
                        if ( array_key_exists( 'mediacat_keyword', $wp->query_vars ) ) {
                                $_REQUEST['keyword'] = $wp->query_vars['mediacat_keyword'];
                        }
                        add_filter( 'body_class', array( &$this, 'body_class' ) );
                        include $this->get_plugin_path() . '/views/search.php';
                        exit;
                }
                elseif ( array_key_exists( 'mediacat_pages', $wp->query_vars ) ) {
                        global $wpdb;

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
                        if ( $row['found_rows'] > 0 ) {
                ?>
                        <ul style="list-style:disc; margin: 50px 0 0 100px;">
                <?php foreach ( $results as $result ): ?>
                                <li><a href="<?php echo admin_url(); ?>post.php?post=<?php echo $result['ID']; ?>&action=edit"><?php echo get_the_title( $result['ID'] ); ?></a></li>
                <?php endforeach; ?>
                        </ul>

                <?php
                        }
                        echo '<p style="margin-top: 100px;text-align:center"><a href="#" onclick="parent.tb_remove();return false;">';
                        _e( 'Close', 'convoy-theme' );
                        echo '</a></p>';
                        exit;
                }
        }

        /**
        *Media category library
        *
        *@return void
        *@since 0.1
        */
        function mediacat_library ( $frontend = false ) {

                global $wpdb;

                if ( $_REQUEST['document_id'] ) {
                        $date = $_REQUEST['year'] . '-' . $_REQUEST['month'] . '-' . $_REQUEST['day'];
                        $sql = "UPDATE wp_posts SET post_date='$date 00:00:00',post_modified='$date 00:00:00',post_date_gmt='$date 00:00:00'," .
                                "post_modified_gmt='$date 00:00:00' WHERE ID = " . $_REQUEST['document_id'];
                        $wpdb->query( $sql );
?>
                        <div id="setting-error-settings_updated" class="updated settings-error"><p><strong>Document date updated successfully.</strong></p></div>
<?php
                }

                // set terms

                $selected_terms = array();
                if ( $_REQUEST['cat'] ) $selected_terms[] = "'" . $wpdb->escape( $_REQUEST['cat'] ) . "'";
                elseif ( $_REQUEST['media-categories'] ) {
                        foreach ( $_REQUEST['media-categories'] as $cat ) $selected_terms[] = "'" . $wpdb->escape( $cat ) . "'";
                }
                else {
                        foreach ( get_media_categories() as $slug => $name )
                                $selected_terms[] = "'" . $wpdb->escape( $slug ) . "'";
                }

                // pagination settings

                $posts_per_page = 20;
                $page = $_REQUEST['pnum'];
                if ( ! $page ) $page = 0;
                else $page -= 1;
                $start = $page * $posts_per_page;
                $start_record = $start + 1;

                // subquery for media categories

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
                        foreach ( $k_fields as $field ) {
                                $where[] = "$field LIKE $keyword";
                        }
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

?>
<br clear="all">
<div class="wrap">
        <div class="icon32" id="icon-upload"><br></div>
           <?php if ( ! $frontend ): ?>
        <h2><?php _e( self::pname . ' Library', 'convoy-theme' ); ?> <a href="<?php echo admin_url(); ?>media-new.php" class="add-new-h2"><?php _e( 'Add New', 'convoy-theme' ); ?></a></h2>
           <?php endif; ?>
<?php if ( ! $frontend ): ?>
        <div class="tablenav top">
                <form id="doc-library-search-form">
                <div class="alignleft actions">
                        <?php _e( 'View By Category', 'convoy-theme' ); ?>: <select name="doc-library-cat" id="doc-library-cat">
                                <option value="">-- <?php _e( 'All', 'convoy-theme' ); ?> --</option>
<?php foreach ( get_media_categories() as $slug => $name ): ?>
                                <option value="<?php echo $slug; ?>"<?php if ( $slug == $_REQUEST['cat'] ): ?> selected<?php endif; ?>><?php echo $name; ?></option>
<?php endforeach; ?>
                        </select>
                </div>
                <div class="alignleft actions">
                        <input type="text" id="doc-library-keyword" name="doc-library-keyword" value="<?php echo $_REQUEST['keyword']; ?>"> <input type="button" id="doc-library-search" class="button button-secondary action" value="<?php _e( 'Search', 'convoy-theme' ); ?>">
        </div>
           </form>
<?php endif; ?>

<?php
        $pagination = $this->get_mediacat_library_pagination( $total_pages, $page, $frontend );
        if ( $row['found_rows'] ) $this->mediacat_library_list( $results, $row['found_rows'], $frontend, $start_record, $posts_per_page, $total_pages, $pagination );
        else echo '<p>' . __( 'No results found.', 'convoy-theme' ) . '</p>';
        }

        /**
        *Media categories
        *
        *@return array
        *@since 0.1
        */
        function get_media_categories () {
                global $wpdb;
                $sql = "SELECT DISTINCT t.name, t.slug FROM wp_term_relationships AS r " .
                        "LEFT JOIN wp_term_taxonomy AS x ON x.term_taxonomy_id = r.term_taxonomy_id " .
                        "LEFT JOIN wp_terms AS t ON t.term_id = x.term_id where r.term_taxonomy_id " .
                        "IN( SELECT term_taxonomy_id FROM wp_term_taxonomy WHERE taxonomy='imagetype') ORDER BY t.name";
                $results = $wpdb->get_results( $sql, ARRAY_A );
                $mediacats = array();
                foreach ( $results as $result ) $mediacats[$result['slug']] = $result['name'];
                return $mediacats;
        }

        /**
        *Media categories library list
        *
        *@return void
        *@since 0.1
        */
        function mediacat_library_list( $results, $total_records, $frontend = false, $start_record, $posts_per_page, $total_pages, $pagination ) {
?>
<?php if ( $frontend ): ?>
<?php if ( $pagination ): ?>
        <div class="pagination docs-pagination">
                <div class="pagination-details">
                <?php echo get_mediacat_library_pagination_details( $start_record, $posts_per_page, $total_records, $total_pages, $pagination ); ?>
                </div>
        </div>
<?php endif; ?>
<?php else: ?>
        <div class="tablenav-pages pagination-records">
                <span class="displaying-num"><?php echo $total_records; ?> <?php _e( 'document(s) found', 'convoy-theme' ); ?></span>
<?php if ( $pagination ): ?>
                <span class="pagination-links">
                        <?php echo $pagination; ?>
                </span>
<?php endif; ?>
        </div>
<?php endif; ?>
        <table class="widefat responsive">
                <thead>
                        <tr>
                                            <th><?php _e( 'Title', 'convoy-theme' ); ?></th>
                                            <th><?php _e( 'File', 'convoy-theme' ); ?></th>
                                            <th><?php _e( 'Type', 'convoy-theme' ); ?></th>
                                            <th><?php _e( 'Category', 'convoy-theme' ); ?></th>
                                            <th><?php _e( 'Caption', 'convoy-theme' ); ?></th>
                                            <th><?php _e( 'Date', 'convoy-theme' ); ?></th>
                                            <?php if ( ! $frontend ): ?><th><?php _e( 'Options', 'convoy-theme' ); ?></th><?php endif; ?>
                                    </tr>
                </thead>
<?php foreach ( $results as $result ): ?>
                <tbody>
                        <tr>
<?php $link = wp_get_attachment_url( $result['ID'] ); ?>
                                <td><a target="_BLANK" href="<?php echo $link; ?>"><?php echo $result['post_title']; ?></a></td>
                                <td><?php echo basename( $link ); ?></td>
                                <td><?php echo $result['post_mime_type']; ?></td>
<?php
        $mediacats = array();
        $terms = wp_get_object_terms( $result['ID'], 'imagetype' );
        foreach ( $terms as $term ) $mediacats[] = $term->name;
        $time_format = get_option( 'date_format' ) . ' ' .  get_option( 'time_format' );
?>
                                <td><?php echo implode( ', ', $mediacats ); ?></td>
                                <td style="width: 40%"><?php echo $result['post_excerpt']; ?></td>
                                <td>
<?php $date = get_the_time( 'm/d/Y', $result['ID'] ); ?>
<?php if ( $frontend ): ?>
                                        <abbr title="<?php echo get_the_time( 'M j, Y', $result['ID'] ); ?>"><?php echo $date; ?></abbr>
<?php else: ?>
<?php list ( $month, $day, $year ) = explode( "/", $date ); ?>
                                        <form action="<?php echo get_mediacat_library_admin_url(); ?>" method="post">
                                                <select name="month">
                                        <?php for( $i = 1; $i <= 12; $i++ ): ?>
                                        <?php $months = array( '01'=>'Jan','02'=>'Feb','03'=>'Mar','04'=>'Apr','05'=>'Mar','06'=>'Jun','07'=>'Jul','08'=>'Aug','09'=>'Sept','10'=>'Oct','11'=>'Nov','12'=>'Dec' ); ?>
                                        <?php if ( $i < 10 ) $i = '0' . $i; ?>
                                                        <option value="<?php echo $i; ?>"<?php if ( $month == $i ): ?> selected<?php endif; ?>><?php echo $months[$i]; ?></option>
                                        <?php endfor; ?>
                                                </select>

                                                <select name="day">
                                        <?php for( $i = 1; $i <= 31; $i++ ): ?>
                                        <?php if ( $i < 10 ) $i = '0' . $i; ?>
                                                        <option value="<?php echo $i; ?>"<?php if ( $day == $i ): ?> selected<?php endif; ?>><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                                </select>

                                                <select name="year">
                                        <?php for( $i = ( date( 'Y' ) - 20 ); $i <= ( date( 'Y' ) + 1 ); $i++ ): ?>
                                        <?php if ( $i < 10 ) $i = '0' . $i; ?>
                                                        <option value="<?php echo $i; ?>"<?php if ( $year == $i ): ?> selected<?php endif; ?>><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                                </select>
                                                <input type="hidden" name="document_id" value="<?php echo $result['ID']; ?>">
                                                <input type="submit" value="Change Date">
                                        </form>
<?php endif; ?>
                                </td>
<?php if ( ! $frontend ): ?>
                                <td>
                                        <ul>
                                                <li><a href="<?php echo admin_url(); ?>media.php?attachment_id=<?php echo $result['ID']; ?>&action=edit"><?php _e( 'Edit', 'convoy-theme' ); ?></a></li>
                                                <li><a class="thickbox" href="/document-pages/<?php echo $result['ID']; ?>/"><?php _e( 'Pages', 'convoy-theme' ); ?></a></li>
                                        </ul>
                                </td>
<?php endif; ?>
                        </tr>
                </tbody>
<?php endforeach; ?>
        </table>
<?php if ( $frontend ): ?>
        <div class="pagination-details">
                <?php echo get_mediacat_library_pagination_details( $start_record, $posts_per_page, $total_records, $total_pages, $pagination ); ?>
        </div>
<?php else: ?>
        <div class="tablenav-pages pagination-records">
                <span class="displaying-num"><?php echo $total_records; ?> <?php _e( 'document(s) found', 'convoy-theme' ); ?></span>
<?php if ( $pagination ): ?>
                <span class="pagination-links">
                        <?php echo $pagination; ?>
                </span>
<?php endif; ?>
        </div>
<?php endif; ?>
<?php
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

                $prev_link = '/document-library/' . ( $_REQUEST['pnum'] - 1 ) . '/' . implode( ',', $_REQUEST['media-categories'] ) . '/';
                if ( $_REQUEST['keyword'] ) $prev_link .= rawurlencode( $_REQUEST['keyword'] ) . '/';
                $next_link = '/document-library/' . ( $_REQUEST['pnum'] + 1 ) . '/' . implode( ',', $_REQUEST['media-categories'] ) . '/';
                if ( $_REQUEST['keyword'] ) $next_link .= rawurlencode( $_REQUEST['keyword'] ) . '/';

                if ( ! $frontend ) {
                        $tmp = array();
                        if ( ! $_REQUEST['pnum'] ) $_REQUEST['pnum'] = 1;
                        foreach ( array( 'cat','keyword' ) as $item ) {
                                if ( $_REQUEST[$item] ) $tmp[] = $item . '=' . rawurlencode( $_REQUEST[$item] );
                        }
                        $prev_link = get_mediacat_library_admin_url() . '&pnum=' . ( $_REQUEST['pnum'] - 1 );
                        $next_link = get_mediacat_library_admin_url() . '&pnum=' . ( $_REQUEST['pnum'] + 1 );
                }
                $previous = '<a title="' . __('Previous', 'convoy-theme' ) . '" class="prev-page" href="' . $prev_link . '">&lsaquo;</a>';
                $next = '<a title="' . __('Next', 'convoy-theme' ) . '" class="next-page" href="' . $next_link . '">&rsaquo;</a>';
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
                $url = admin_url() . 'upload.php?page=document-libary';
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
                return '<div class="pagination-records">' . __( 'Displaying', 'convoy-theme' ) . ' ' .
                        $start_record . ' &mdash; ' . $end_record . ' ' . __( 'of', 'convoy-theme' ) . ' ' .
                        $total_records . ' ' . __( 'total records', 'convoy-theme' ) . '</div>' .
                        '<div class="pagination-pages">' . __( 'Page', 'convoy-theme' ) . ' ' . $page . ' ' . __( 'of', 'convoy-theme' ) .
                        ' ' . $total_pages . '</div>';
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
                        add_media_page( self::pname . ' Library', self::pname . ' Library', '', 'media-category-libary', array( &$this, 'media_category_library' ) );
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
                        wp_enqueue_script( 'wp-media-category', $this->get_plugin_path() . '/js/media-category.js', array( 'jquery' ), self::version, true );
                        $options = array();
                        $terms = get_terms( self::term, 'hide_empty=0' );
                        foreach ( $terms as $term ) $options[] = $term->name;
                        wp_localize_script( 'wp-media-category', 'media_category', array( 'options' => $options ) );
                }
                elseif ( $hook == 'media_page_document-libary' ) {
                        wp_enqueue_script( 'wp-media-category-library', $this->get_plugin_path() . '/js/media-category-library.js', array( 'jquery' ), self::version, true );
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
                if($_POST['wpcjp_update_settings']) $this->update_settings();
                $this->show_settings_form();
        }

        /**
        *Show settings form
        *
        *@return void
        *@since 0.1
        */
        function show_settings_form () {
                echo '
        <div class="wrap">
                <div id="icon-options-general" class="icon32">
                        <br/>
                </div>
                <h2>' . self::pname . ' Settings</h2>
		<p>Updating settings will remove all files cached on the filesystem.</p>
';
                if ( $_POST['wpcjp_update_settings'] ) {
                        echo '
                <div class="updated settings-error" id="setting-error-settings_updated"><p><strong>Settings saved.</strong></p></div>
';
                }
                echo '
                <form method="post">
                        <table class="form-table">
';

                foreach ( $this->settings_fields as $key => $val ) {
                        echo '
                                <tr valign="top">';
                        if ( $val['type'] == 'legend' ) {
                                echo '
                                        <th colspan="2" class="legend" scope="row"><strong>' . $val['label'] . '</strong></th>
';
                        }
                        else {
                                echo '
                                        <th scope="row"><label for="' . $key . '">' . $val['label'] . '</label></th>
                                        <td>
';
                                if( $val['type'] == 'money') echo '<span class="dollar-sign">$</span> ';
                                if( $val['type'] == 'money' || $val['type'] == 'text' || $val['type'] == 'password' ) {
                                        if ( $val['type'] == 'money' ) $val['type'] = 'text';
					$value = $this->get_settings_value( $key );
					if ( ! @strlen( $value ) ) $value = $val['default'];
                                        echo '
                                        <input name="' . $key . '" type="' . $val['type'] . '" id="' . $key . '" value="' . stripslashes( htmlspecialchars( $value ) ) . '" class="regular-text" />
';
                                }
                                elseif ( $val['type'] == 'select' ) {
					$value = $this->get_settings_value( $key );
					if ( ! @strlen( $value ) ) $value = $val['default'];
                                        echo $this->select_field( $key, $val['values'], $value );
                                }
                                elseif($val['type'] == 'textarea') {
                                        echo '<textarea name="' . $key . '" id="' . $key . '">' . stripslashes( htmlspecialchars( $this->get_settings_value( $key ) ) ) . '</textarea>';
                                }
                                if($val['description']) {
                                        echo '
                                                <span class="description">' . $val['description'] . '</span>
';
                                }
                                echo '
                                        </td>';
                        }
                        echo '
                                </tr>';
                }

                echo '
                        </table>
                        <input type="hidden" name="wpcjp_update_settings" value="1" />
                        <p class="submit"><input type="submit" value="Save Changes" class="button-primary" id="submit" name="submit"></p>
                </form>
        </div>
';
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
                        if( $val['type'] != 'legend' ) {
                                $data[$key] = $_POST[$key];
                        }
                }
                $this->set_settings( $data );
		$this->delete_cache();
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
