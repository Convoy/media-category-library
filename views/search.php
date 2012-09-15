<?php if ( !defined('ABSPATH') ) die('-1'); ?>
<?php get_header(); ?>

        <section id="widecolumn" role="main">

                <div id="hdr"><h2 class="pagetitle"><?php _e( 'Document Library', 'convoy-theme' ); ?></h2></div>

                <h1 id="document-library-search-details"><?php _e( 'Search', 'convoy-theme' ); ?></h1>

		        <form id="document-library-search-form" action="/document-library/" method="post">
		                <div class="cols two-cols">
		                        <fieldset class="col first-col">
		                                <legend><?php _e( 'Category', 'convoy-theme' ); ?></legend>
		                        <div class="radio-group">
<?php foreach ( $this->get_media_categories() as $slug => $name ): ?>
		                                <label><input type="checkbox" name="media-categories[]" value="<?php echo $slug; ?>"<?php if ( in_array( $slug, $_REQUEST['media-categories'] ) ): ?> checked<?php endif; ?>>
		                                <?php echo $name; ?></label>
<?php endforeach; ?>
		                                </div>
		                        </fieldset>
		                        <fieldset class="col">
		                                        <p class="form-row" id="document-library-keyword">
		                                                <label for="keyword"><?php _e( 'Keyword', 'convoy-theme' ); ?></label>
		                                                <input type="text" name="keyword" id="keyword" value="<?php echo $_REQUEST['keyword']; ?>">
		                                        </p>
		                        </fieldset>
		                </div>
		                <input type="hidden" name="document_library_submit" value="1">
		                <input type="submit" value="<?php _e( 'Search', 'convoy-theme' ); ?>">
		        </form>

<?php if ( $_REQUEST['keyword'] || $_REQUEST['media-categories'] ) ufcw_document_library( true ); ?>

        </section>

<?php get_footer(); ?>