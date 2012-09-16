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
                <span class="displaying-num"><?php echo $total_records; ?> <?php _e( 'document(s) found', self::nspace ); ?></span>
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
                                <th><?php _e( 'Title', self::nspace ); ?></th>
                                <th><?php _e( 'File', self::nspace ); ?></th>
                                <th><?php _e( 'Type', self::nspace ); ?></th>
                                <th><?php _e( 'Category', self::nspace ); ?></th>
                                <th><?php _e( 'Caption', self::nspace ); ?></th>
                                <th><?php _e( 'Date', self::nspace ); ?></th>
                                <?php if ( ! $frontend ): ?><th><?php _e( 'Options', self::nspace ); ?></th><?php endif; ?>
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
        $terms = wp_get_object_terms( $result['ID'], $this->settings_data['taxonomy_name'] );
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
                                        <form action="<?php echo $this->get_mediacat_library_admin_url(); ?>" method="post">
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
                                                <input type="hidden" name="mediacat_document_id" value="<?php echo $result['ID']; ?>">
                                                <input type="submit" value="Change Date">
                                        </form>
<?php endif; ?>
                                </td>
<?php if ( ! $frontend ): ?>
                                <td>
                                        <ul>
                                                <li><a href="<?php echo admin_url(); ?>media.php?attachment_id=<?php echo $result['ID']; ?>&action=edit"><?php _e( 'Edit', self::nspace ); ?></a></li>
                                                <li><a class="thickbox" href="/mediacat-pages/<?php echo $result['ID']; ?>/"><?php _e( 'Pages', self::nspace ); ?></a></li>
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
                <span class="displaying-num"><?php echo $total_records; ?> <?php _e( 'document(s) found', self::nspace ); ?></span>
<?php if ( $pagination ): ?>
                <span class="pagination-links">
                        <?php echo $pagination; ?>
                </span>
<?php endif; ?>
        </div>
<?php endif; ?>