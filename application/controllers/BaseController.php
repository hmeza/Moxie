<?php
/** Zend_Controller_Action */
class BaseController extends Zend_Controller_Action {
	public function init() {
		Zend_Loader::loadClass('Zend_View');
		$header = new Zend_View(array('scriptPath' => 'application/views/scripts/layout'));
		$this->view->header = $header->render('header.phtml');
		$this->view->footer = $header->render("footer.phtml");
	}
}
