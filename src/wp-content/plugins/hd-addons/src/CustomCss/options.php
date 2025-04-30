<?php

\defined( 'ABSPATH' ) || exit;

$css = \Addons\Helper::getCustomPostContent( 'addon_css', false );

?>
<div class="container flex flex-x flex-gap sm-up-1">
	<div class="cell section section-textarea">
		<label class="heading" for="html_custom_css"><?php _e( 'Custom CSS', ADDONS_TEXTDOMAIN ) ?></label>
		<div class="option">
			<div class="controls">
				<textarea class="textarea codemirror_css" name="html_custom_css" id="html_custom_css" rows="8"><?php echo $css ?></textarea>
			</div>
		</div>
	</div>
</div>
