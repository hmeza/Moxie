<?php

class FinancesControllerTest extends Zend_Test_PHPUnit_ControllerTestCase {
	public function setUp() {
		$_SERVER['SERVER_NAME'] = "testing";
		$_SERVER['REQUEST_URI'] = "test";
		$this->bootstrap = new Zend_Application(
			'testing',
			APPLICATION_PATH . '/configs/application.ini'
		);
		parent::setUp();
	}

	public function testIndexIncomes() {
		$_SERVER['REQUEST_URI'] = 'http://moxie.dev/foo/bar';

		$_SESSION['user_lang'] = 'es';
		$this->dispatch('/finances/');
		$this->assertController('finances');
		$this->assertAction('index');

		$this->assertQueryContentContains('dt', 'Dinero a depositar');
		$this->assertQueryContentContains('dt', 'Interés');
		$this->assertQueryContentContains('dt', 'Meses');
		$this->assertQueryContentContains('dt', 'Intereses');
		$this->assertQueryContentContains('dt', 'Total');
	}
}
