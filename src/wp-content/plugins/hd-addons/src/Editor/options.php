<?php

\defined( 'ABSPATH' ) || exit;

$editor_options                         = \Addons\Helper::getOption( 'editor__options' );
$use_widgets_block_editor_off           = $editor_options['use_widgets_block_editor_off'] ?? '';
$gutenberg_use_widgets_block_editor_off = $editor_options['gutenberg_use_widgets_block_editor_off'] ?? '';
$use_block_editor_for_post_type_off     = $editor_options['use_block_editor_for_post_type_off'] ?? '';
$block_style_off                        = $editor_options['block_style_off'] ?? '';

?>
<div class="container flex flex-x flex-gap sm-up-1 md-up-2">
    <input type="hidden" name="editor-hidden" value="1">
    <div class="cell section section-checkbox">
        <label class="heading" for="use_widgets_block_editor_off"><?php _e( 'Disable widgets block editor', ADDONS_TEXTDOMAIN ); ?></label>
        <div class="option">
            <div class="controls">
                <input type="checkbox" class="checkbox" name="use_widgets_block_editor_off" id="use_widgets_block_editor_off" <?php checked( $use_widgets_block_editor_off, 1 ); ?> value="1">
            </div>
            <div class="explain"><?php _e( 'Disables the block editor from managing widgets.', ADDONS_TEXTDOMAIN ); ?></div>
        </div>
    </div>
    <div class="cell section section-checkbox">
        <label class="heading" for="gutenberg_use_widgets_block_editor_off"><?php _e( 'Disable Gutenberg widgets', ADDONS_TEXTDOMAIN ); ?></label>
        <div class="option">
            <div class="controls">
                <input type="checkbox" class="checkbox" name="gutenberg_use_widgets_block_editor_off" id="gutenberg_use_widgets_block_editor_off" <?php checked( $gutenberg_use_widgets_block_editor_off, 1 ); ?>value="1">
            </div>
            <div class="explain"><?php _e( 'Disables the block editor from managing widgets in the Gutenberg.', ADDONS_TEXTDOMAIN ); ?></div>
        </div>
    </div>
    <div class="cell section section-checkbox">
        <label class="heading" for="use_block_editor_for_post_type_off"><?php _e( 'Disable Block Editor', ADDONS_TEXTDOMAIN ); ?></label>
        <div class="option">
            <div class="controls">
                <input type="checkbox" class="checkbox" name="use_block_editor_for_post_type_off" id="use_block_editor_for_post_type_off" <?php checked( $use_block_editor_for_post_type_off, 1 ); ?> value="1">
            </div>
            <div class="explain"><?php _e( 'Use Classic Editor - Disable Block Editor.', ADDONS_TEXTDOMAIN ); ?></div>
        </div>
    </div>
    <div class="cell section section-checkbox">
        <label class="heading" for="block_style_off"><?php _e( 'Remove block CSS', ADDONS_TEXTDOMAIN ); ?></label>
        <div class="option">
            <div class="controls">
                <input type="checkbox" class="checkbox" name="block_style_off" id="block_style_off" <?php checked( $block_style_off, 1 ); ?> value="1">
            </div>
            <div class="explain"><?php _e( 'Remove block library styles.', ADDONS_TEXTDOMAIN ); ?></div>
        </div>
    </div>
</div>
