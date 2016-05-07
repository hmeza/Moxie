<?php

class Moxie_View_Helper_Web extends Zend_View_Helper_Abstract {
	public function printToTop() {
		global $st_lang;
		echo '<div class="back_to_top"><br><a href="#top">'.$st_lang['back_to_top'].'</a></div>';
	}
}