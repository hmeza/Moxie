<?php

//include '/tmp/Moxie/test/php/bootstrap.php';

class CategoriesTest extends Zend_Test_PHPUnit_ControllerTestCase {
	/** @var Categories */
	private $categoriesModel;

	public function setUp() {
		$this->bootstrap = new Zend_Application(
			'development',
			APPLICATION_PATH . '/configs/application.ini'
		);
		$this->categoriesModel = new Categories();
	}

	public function testInsertCategoriesForRegisteredUser() {
		$queryMock = $this->getMockBuilder('\Zend_Db_Table_Select')
				->disableOriginalConstructor()
				->getMock();
		$objectMock = new stdClass();
		$objectMock->id = 1;
		$this->categoriesModel = $this->getMockBuilder('Categories')
				->setMethods(array('insert', 'select', 'fetchRow'))
				->getMock();
		$this->categoriesModel->expects($this->once())
				->method('select')
				->will($this->returnValue($queryMock));
		$this->categoriesModel->expects($this->once())
				->method('fetchRow')
				->will($this->returnValue($objectMock));
		$this->categoriesModel->expects($this->exactly(11))
				->method('insert')
				->with($this->isType('array'));

		$this->categoriesModel->insertCategoriesForRegisteredUser(54);
	}
}
