<?php $body_classes = get_body_class(); ?>
<form role="search" method="get" class="searchform" action="<?php echo home_url( '/' ); ?>">
    <div>
        <span class="screen-reader-text"><?php echo _x( 'Search for:', 'label' ) ?></span>
        <input type="search" class="search-field"
            placeholder="<?php if(in_array("blog", $body_classes) || in_array("post-type-archive-event", $body_classes)) { echo esc_attr_x( 'Search by keyword', 'placeholder' ); } else { echo esc_attr_x( 'Search...', 'placeholder' ); } ?>"
            value="<?php echo get_search_query() ?>" name="s"
            title="<?php echo esc_attr_x( 'Search for:', 'label' ) ?>" />
        <input type="hidden" name="search-type"
        value="<?php if(in_array("blog", $body_classes)) {echo 'post';} elseif(in_array("post-type-archive-event", $body_classes)) {echo 'event';} else {echo 'all';} ?>"/>
        <input type="submit" id="searchsubmit" value="Search">
    </div>
</form>