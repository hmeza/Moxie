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

	public function testCheckLoginReturnsNullIfLoginIncorrect() {
		$dbTableSelectMock = $this->getMockBuilder('\Zend_Db_Table_Select')
				->disableOriginalConstructor()
				->getMock();
		$dbSelectMock = $this->getMockBuilder('\Zend_Db_Select')
				->disableOriginalConstructor()
				->getMock();
		$oRowsMock = null;

		$dbSelectMock->expects($this->exactly(2))
			->method('where')
			->withConsecutive(array('login = "test_user"'), array('password = md5("12345")'))
			->willReturnOnConsecutiveCalls($dbSelectMock, $oRowsMock);
		$dbTableSelectMock->expects($this->once())
			->method('from')
			->with('users', array('id', 'login', 'language'))
			->will($this->returnValue($dbSelectMock));
		$this->usersModel = $this->getMockBuilder('Users')
				->setMethods(array('select', 'fetchRow'))
				->getMock();
		$this->usersModel->expects($this->once())
			->method('select')
			->will($this->returnValue($dbTableSelectMock));
		$this->usersModel->expects($this->once())
				->method('fetchRow')
				->will($this->returnValue($oRowsMock));

		$this->assertNull($this->usersModel->checkLogin("test_user", "12345"));
	}

	public function testCheckLoginReturnsNullOnException() {
		$this->usersModel = $this->getMockBuilder('Users')
				->setMethods(array('select', 'fetchRow'))
				->getMock();
		$this->usersModel->expects($this->never())
				->method('fetchRow');
		$this->usersModel->expects($this->once())
				->method('select')
				->will($this->throwException(new \Exception("test")));

		$this->assertNull($this->usersModel->checkLogin("test_user", "12345"));
	}

	public function testCheckLoginReturnsUserDataInArrayIfLoginSuccessful() {
		$dbTableSelectMock = $this->getMockBuilder('\Zend_Db_Table_Select')
				->disableOriginalConstructor()
				->getMock();
		$dbSelectMock = $this->getMockBuilder('\Zend_Db_Select')
				->disableOriginalConstructor()
				->getMock();
		$oRowsMock = $this->getMock('\Zend_Db_Table_Row_Abstract');

		$oRowsMock->expects($this->once())
				->method('toArray');
		$dbSelectMock->expects($this->exactly(2))
				->method('where')
				->withConsecutive(array('login = "test_user"'), array('password = md5("12345")'))
				->willReturnOnConsecutiveCalls($dbSelectMock, $dbSelectMock);
		$dbTableSelectMock->expects($this->once())
				->method('from')
				->with('users', array('id', 'login', 'language'))
				->will($this->returnValue($dbSelectMock));
		$this->usersModel = $this->getMockBuilder('Users')
				->setMethods(array('select', 'fetchRow'))
				->getMock();
		$this->usersModel->expects($this->once())
				->method('select')
				->will($this->returnValue($dbTableSelectMock));
		$this->usersModel->expects($this->once())
				->method('fetchRow')
				->will($this->returnValue($oRowsMock));

		$this->assertNull($this->usersModel->checkLogin("test_user", "12345"));
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

	public function testDistinctKeysAreGenerated() {
		$this->usersModel = new Users();
		$a_key = $this->usersModel->generateKey("test_login");
		$this->assertNotEquals($a_key, $this->usersModel->generateKey("test_login"));
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage Invalid key
	 */
	public function testCheckKeyThrowsExceptionIfLoginDoesNotExistInLoginKeys() {
		$this->usersModel = new Users();
		$this->assertEquals("foo_bar", $this->usersModel->checkLogin("foo_bar", "asdfpoiu"));
	}

	public function testGeneratedKeyRetunsLogin() {
		$this->usersModel = new Users();
		$generatedKey = $this->usersModel->generateKey("test_login");
		$this->assertEquals("test_login", $this->usersModel->checkLogin("test_login", $generatedKey));
	}
}