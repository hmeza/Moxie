<?php

class Moxie_View_Helper_Web extends Zend_View_Helper_Abstract {
	public function printToTop() {
		global $st_lang;
		echo '<div class="back_to_top"><br><a href="#top">'.$st_lang['back_to_top'].'</a></div>';
	}

	public function printHeaderNextLastInterval($last_url, $next_url, $current_month_and_year) {
?>
		<div class="moxie_time_frame_selector">
			<a href="<?php echo $last_url; ?>">
				<div class="moxieprevious"> < </div>
			</a>
			<div class="moxie_header_date"><span style="display: inline-block; line-height: normal;"><?php echo $current_month_and_year; ?></span></div>
		<a href="<?php echo $next_url; ?>">
			<div class="moxienext"> > </div>
		</a>
		</div>
<?php
	}
}