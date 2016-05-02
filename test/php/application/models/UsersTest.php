<?php

//include '/tmp/Moxie/test/php/bootstrap.php';

class UsersTest extends Zend_Test_PHPUnit_ControllerTestCase {
	/** @var Users */
	private $usersModel;

	public function setUp() {
		$this->bootstrap = new Zend_Application(
			'development',
			APPLICATION_PATH . '/configs/application.ini'
		);
		$this->usersModel = new Users();
	}

	public function testGetValidationKeyReturnsProperValidationKey() {
		// arrange
		$data = array(
		    'login' => 'hmeza6',
		    'password' => '81dc9bdb52d04dc20036dbd8313ed055',
		    'email' => 'hugoboss666+6@gmail.com',
		    'created_at' => '2016-05-02 22:09:53'
		);

		// act
		$hash = $this->usersModel->getValidationKey($data);

		// assert
		$this->assertEquals('e1e95a60718462e0c06986f014bf620f486d10fc', $hash);
	}

	public function testValidateKeyReturnsTrueForValidKey() {
		// arrange
		$data = array(
				'login' => 'hmeza6',
				'password' => '81dc9bdb52d04dc20036dbd8313ed055',
				'email' => 'hugoboss666+6@gmail.com',
				'created_at' => '2016-05-02 22:09:53'
		);

		// act
		$this->assertTrue($hash = $this->usersModel->validateKey('e1e95a60718462e0c06986f014bf620f486d10fc', $data));
	}

	public function testValidateKeyReturnsFalseForInvalidKey() {
		// arrange
		$data = array(
				'login' => 'hmeza6',
				'password' => '81dc9bdb52d04dc20036dbd8313ed055',
				'email' => 'hugoboss666+6@gmail.com',
				'created_at' => '2016-05-02 22:09:53'
		);

		// act
		$this->assertFalse($hash = $this->usersModel->validateKey('a1e95a64456462e0c06986f014bf620f486d10fc', $data));
	}

	public function testConfirmUpdatesUser() {
		$this->usersModel = $this->getMockBuilder('Users')
				->setMethods(array('update'))
				->getMock();
		$this->usersModel->expects($this->once())
			->method('update')
			->with(array('confirmed' => 1), 'id = 54');

		$this->usersModel->confirm(54);
	}
}
