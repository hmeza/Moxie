<?php

class Moxie_View_Helper_Web extends Zend_View_Helper_Abstract {
	public function printToTop() {
		global $st_lang;
		echo '<div class="back_to_top"><br><a href="#top">'.$st_lang['back_to_top'].'</a></div>';
	}

	public function printHeaderNextLastInterval($last_url, $next_url, $current_month_and_year) {
?>
<ul class="pager">
  <li><a href="<?php echo $last_url; ?>"> < </a></li>
  <li><span class="moxie_header_date"><?php echo $current_month_and_year; ?></span></li>
  <li><a href="<?php echo $next_url; ?>"> > </a></li>
</ul>

<!-- 	<div class="moxie_time_frame_selector">
			<a href="<?php echo $last_url; ?>">
				<div class="moxieprevious"> < </div>
			</a>
			<div class="moxie_header_date"><span style="display: inline-block; line-height: normal;"><?php echo $current_month_and_year; ?></span></div>
		<a href="<?php echo $next_url; ?>">
			<div class="moxienext"> > </div>
		</a>
		</div>-->
<?php
	}

	public function printExpandCollapseButton($title) {
?>
<h2>
	<span id="show_budget">
		<button class="mdl-button mdl-js-button mdl-button--fab mdl-js-ripple-effect mdl-button--colored">
			<i class="material-icons" style="background-color: ##0b77b7">+</i>
		</button>
		<span><?php echo $title; ?></span>
</span>
</h2>
<?php
	}
}