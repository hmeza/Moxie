<?php
/** Zend_Controller_Action */
class IndexController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $this->view->assign('message', $this->getRequest()->getParam('message'));
        $this->view->assign('error', $this->getRequest()->getParam('error'));
    }

    public function aboutAction() {
        global $st_lang;
        $this->view->assign('message', $st_lang['text_about_text']);
        $this->view->assign('error', "");
        //return $this->_forward("about", "index");
    }
}