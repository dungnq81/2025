<?php

defined( 'ABSPATH' ) || exit;

$base_slug_options   = \Addons\Helper::getOption( 'base_slug__options' );
$base_slug_post_type = $base_slug_options['base_slug_post_type'] ?? [];
$base_slug_taxonomy  = $base_slug_options['base_slug_taxonomy'] ?? [];

?>
<div class="container flex flex-x flex-gap sm-up-1 lg-up-2">
    <input type="hidden" name="base-slug-hidden" value="1">
    <div class="cell section section-checkbox">
        <span class="heading !fw-700"><?php _e( 'Taxonomies', ADDONS_TEXTDOMAIN ); ?></span>
        <div class="desc">Remove category base from category URLs. E.g. <b>/category/my-category/</b> becomes <b>/my-category/</b></div>
		<?php
		$taxonomies = get_taxonomies(
			[
				'show_ui' => true,
				'public'  => true,
			],
			'objects'
		);

		foreach ( $taxonomies as $taxonomy ) :
			$label = $taxonomy->label;
			$label .= ' <span class="!fw-400">(' . $taxonomy->name . ')</span>';
        ?>
        <div class="option mb-20">
            <label class="controls">
                <input type="checkbox" class="checkbox" name="base_slug_taxonomy[]" value="<?php echo esc_attr( $taxonomy->name ); ?>" <?php \Addons\Helper::inArrayChecked( $base_slug_taxonomy, $taxonomy->name ); ?>>
            </label>
            <div class="explain"><?php echo $label; ?></div>
        </div>
		<?php endforeach; ?>

        <div class="desc !mt-30" style="color:#d63638">If encountering a 404 error, navigate to <b>Settings</b> -> <b>Permalinks</b>, and click "<b>Save Changes</b>".</div>
    </div>

    <div class="cell section section-checkbox">
        <span class="heading !fw-700"><?php _e( 'Post Types', ADDONS_TEXTDOMAIN ); ?></span>
        <div class="desc">Remove post-type base like <b>/product/*</b>, <b>/project/*</b> from post-type URLs. Example: default: <b>/product/sample-product/</b> - becomes: <b>/sample-product/</b></div>
		<?php
		$post_types = get_post_types(
			[
				'show_ui'  => true,
				'public'   => true,
				'_builtin' => false,
			],
			'objects'
		);

		foreach ( $post_types as $post_type ) :
			if ( $post_type->name === 'attachment' ) {
				continue;
			}

			$label = $post_type->label;
			$label .= ' <span class="!fw-400">(' . $post_type->name . ')</span>';
        ?>
        <div class="option mb-20">
            <label class="controls">
                <input type="checkbox" class="checkbox" name="base_slug_post_type[]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php \Addons\Helper::inArrayChecked( $base_slug_post_type, $post_type->name ); ?>>
            </label>
            <div class="explain"><?php echo $label; ?></div>
        </div>
		<?php endforeach; ?>
    </div>

	<div class="cell section section-checkbox !sm-12">
		<span class="heading !fw-700"><?php _e( 'Reset', ADDONS_TEXTDOMAIN ); ?></span>
		<div class="option mb-20">
			<label class="controls">
				<input type="checkbox" class="checkbox" name="base_slug_reset" id="base_slug_reset" value="1">
			</label>
			<div class="explain"><?php _e( 'Reset all', ADDONS_TEXTDOMAIN ); ?></div>
		</div>
	</div>
</div>
