<?php
/** Zend_Controller_Action */
class IndexController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $message = $this->getRequest()->getParam('message');
        $this->view->assign('message', $message);
    }
}