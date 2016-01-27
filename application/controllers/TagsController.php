<?php
class TagsController extends Zend_Controller_Action
{
	/** @var Tags */
	private $tags;
	
	public function init() {
		parent::init();
		$this->tags = new Tags();
	}
    
    public function multiupdateAction() {
        try {
            $a_userTags = $this->tags->getTagsByUser($_SESSION['user_id']);
	    	$a_data = $this->_request->getParam('taggles');
            $a_data = array_unique($a_data);

            foreach($a_data as $tag) {
                // remove each tag from $a_userTags
                $tagId = array_search($tag, $a_userTags);
                if($tagId !== FALSE) {
                    unset($a_userTags[$tagId]);
                }
                else {
                    $this->tags->addTag($_SESSION['user_id'], $tag);
                }
            }
            if(!empty($a_userTags)) {
                foreach ($a_userTags as $tag) {
                    $this->tags->deleteTag($_SESSION['user_id'], $tag);
                }
            }

		} catch (Exception $e) {
    		error_log('Exception caught on '.__CLASS__.', '.__FUNCTION__.'('.$e->getLine().'), message: '.$e->getMessage());
    	}
    	$this->_helper->redirector('index','users');
    }
}