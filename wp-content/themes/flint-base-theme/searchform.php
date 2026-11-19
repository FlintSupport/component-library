<?php $body_classes = get_body_class(); $search_id = wp_unique_id( 'flint-search-' ); ?>
<form role="search" method="get" class="searchform" action="<?php echo esc_url( home_url( '/' ) ); ?>">
    <div>
        <label class="screen-reader-text" for="<?php echo esc_attr( $search_id ); ?>"><?php echo esc_html_x( 'Search for:', 'label' ); ?></label>
        <input id="<?php echo esc_attr( $search_id ); ?>" type="search" class="search-field"
            placeholder="<?php echo esc_attr( ( in_array( 'blog', $body_classes, true ) || in_array( 'post-type-archive-event', $body_classes, true ) ) ? 'Search by keyword' : 'Search…' ); ?>"
            value="<?php echo esc_attr( get_search_query() ); ?>" name="s" />
        <input type="hidden" name="search-type" value="<?php if ( in_array( 'blog', $body_classes, true ) ) { echo 'post'; } elseif ( in_array( 'post-type-archive-event', $body_classes, true ) ) { echo 'event'; } else { echo 'all'; } ?>"/>
        <button type="submit" class="search-submit">Search</button>
    </div>
</form>
