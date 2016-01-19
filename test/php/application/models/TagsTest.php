<?php

//include '/tmp/Moxie/test/php/bootstrap.php';

class TagsTest extends Zend_Test_PHPUnit_ControllerTestCase {
	private $tagsModel;

	public function setUp() {
		$this->bootstrap = new Zend_Application(
			'development',
			APPLICATION_PATH . '/configs/application.ini'
		);
		$this->tagsModel = new Tags();
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage Empty user id
	 */
	public function testAddTagThrowsExceptionIfNoUserIdIsProvided() {
		$this->tagsModel->addTag(null, null);
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage Empty tag name
	 */
	public function testAddTagThrowsExceptionIfNoNameIsProvided() {
		$this->tagsModel->addTag(1, null);
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage Empty user id
	 */
	public function testGetTagsByUserThrowsExceptionIfNoUserIdIsProvided() {
		$this->tagsModel->getTagsByUser(null);
	}
}
