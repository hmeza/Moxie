<?php

class LoginControllerTest extends Zend_Test_PHPUnit_ControllerTestCase {
	public function setUp() {
		$_SERVER['SERVER_NAME'] = "testing";
		$_SERVER['REQUEST_URI'] = "test";
		$this->bootstrap = new Zend_Application(
			'development',
			APPLICATION_PATH . '/configs/application.ini'
		);
		parent::setUp();
	}

	public function testRegisteruserActionReturnsErrorIfNoLoginParameter() {
		$request = new Zend_Controller_Request_Http();
		$request->setPost(array());

		$view = $this->getMockBuilder('Zend_View')
				->setMethods(array())
				->getMock();
		$view = new Zend_View();

		/** @var LoginController $loginControllerMock */
		$loginControllerMock = $this->getMockBuilder('LoginController')
				->disableOriginalConstructor()
				->setMethods(array('render'))
				->getMock();
		$loginControllerMock->expects($this->once())
				->method('render');
		$loginControllerMock->view = $view;
		$loginControllerMock->setRequest($request);
		$loginControllerMock->registeruserAction();

		$this->assertEquals('Empty username', $loginControllerMock->view->message);
	}


	public function testLoginIncorrect() {
		$this->request->setMethod('POST')
			->setPost(array(
				'login' => 'pepe',
				'password' => 'test'
			));
		$this->dispatch('/login');
		$this->assertController('error');
		$this->assertAction('error');
	}

	public function testLoginCorrect() {
		$this->request->setMethod('POST')
			->setPost(array(
				'login' => 'test',
				'password' => '123456'
			));
		$this->dispatch('/login/login');
		$this->assertController('login');
		$this->assertAction('login');
		$this->assertRedirectTo('/expenses');
	}
}
