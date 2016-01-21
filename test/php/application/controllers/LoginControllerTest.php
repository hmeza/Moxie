<?php

class LoginControllerTest extends Zend_Test_PHPUnit_ControllerTestCase {
	public function setUp() {
		$_SERVER['SERVER_NAME'] = "moxie.dev";
		$_SERVER['REQUEST_URI'] = "test";
		$this->bootstrap = new Zend_Application(
			'development',
			APPLICATION_PATH . '/configs/application.ini'
		);
		parent::setUp();
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
