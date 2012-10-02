                <h1 id="mediacat-library-search-details"><?php _e( 'Search', 'convoy-theme' ); ?></h1>
	        <form id="mediacat-library-search-form" action="<?php if ( get_option( 'permalink_structure' ) ): ?><?php echo $this->settings_data['rewrite_url']; ?>/<?php else: ?>?mediacat_library=1<?php endif; ?>" method="post">
	                <div class="cols two-cols">
	                        <fieldset class="col first-col">
	                                <legend><?php _e( 'Category', 'convoy-theme' ); ?></legend>
	                        	<div class="radio-group">

<?php foreach ( $this->get_media_categories() as $slug => $name ): ?>
	                                	<label>
	                                		<input type="checkbox" name="media-categories[]" value="<?php echo $slug; ?>"<?php if ( in_array( $slug, $_REQUEST['media-categories'] ) ): ?> checked<?php endif; ?>>
	                                		<?php echo $name; ?>
	                        		</label>
<?php endforeach; ?>

					</div>
				</fieldset>
	                        <fieldset class="col">
	                                        <p class="form-row" id="mediacat-library-keyword">
	                                                <label for="keyword"><?php _e( 'Keyword', 'convoy-theme' ); ?></label>
	                                                <input type="text" name="keyword" id="keyword" value="<?php echo $_REQUEST['keyword']; ?>">
	                                        </p>
	                        </fieldset>
	                </div>
	                <input type="hidden" name="mediacat_library_submit" value="1">
	                <input type="submit" value="<?php _e( 'Search', 'convoy-theme' ); ?>">
	        </form>
<?php if ( $_REQUEST['keyword'] || $_REQUEST['media-categories'] ) $this->mediacat_library( true ); ?>
