<?php
/** Zend_Controller_Action */
class IndexController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $this->view->assign('message', $this->getRequest()->getParam('message'));
        $this->view->assign('error', $this->getRequest()->getParam('error'));
    }
}