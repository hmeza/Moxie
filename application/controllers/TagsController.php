<?php
class TagsController extends Zend_Controller_Action
{
	/** @var Tags */
	private $tags;
	
	public function init() {
		parent::init();
		$this->tags = new Tags();
	}
    
    public function multiUpdateAction() {
        try {
            $a_userTags = $this->tags->getUsedTagsByUser($_SESSION['user_id']);
	    	$a_data = $this->_request->getParams();

            foreach($a_data['taggle'] as $tag) {
                // remove each tag from $a_userTags
                if(in_array($tag, $a_userTags)) {
                    unset($a_userTags);
                }
                else {
                    $this->tags->addTag($_SESSION['user_id'], $tag);
                }
            }
            foreach($a_userTags as $tag) {
                $this->tags->deleteTag($_SESSION['user_id'], $tag);
            }

		} catch (Exception $e) {
    		error_log('Exception caught on '.__CLASS__.', '.__FUNCTION__.'('.$e->getLine().'), message: '.$e->getMessage());
    	}
    	$this->_helper->redirector('index','users');
    }
}