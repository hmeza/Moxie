<?php

class ExpensesControllerTest extends Zend_Test_PHPUnit_ControllerTestCase {
	public function setUp() {
		$_SERVER['SERVER_NAME'] = "moxie.dev";
		$_SERVER['REQUEST_URI'] = "test";
		$this->bootstrap = new Zend_Application(
			'development',
			APPLICATION_PATH . '/configs/application.ini'
		);
		parent::setUp();
	}

	/**
	 * @dataProvider addExpenseDataProvider
	 */
	public function testAddExpenseWithTags($amount, $date, $note, $category, $tags = array()) {
		$this->fakeLogin();
		$this->request->setMethod('POST')
			->setPost(array(
				'amount' => $amount,
				'date' => $date,
				'note' => $note,
				'category' => $category,
				'taggles' => $tags
			));
		$this->dispatch('/expenses/add');
		$this->assertController('expenses');
		$this->assertAction('add');
		$this->assertRedirectTo('/expenses');
	}

	public function addExpenseDataProvider() {
		return array(
			// normal expense
			array(10.23, '21/01/2016', 'test note', 1),
			// amount with comma
			array("10,25", '21/01/2016', 'test note 2', 1),
			// note empty
			array(10.62, '23/01/2016', '', 3),
			// amount with tags
			array(10.26, '22/01/2016', 'test note 3', 3, array('tag 1', 'tag 2'))
		);
	}

	public function testAddExpenseSendingAmountWithCommaAddsExpense() {

	}

	private function fakeLogin() {
		$this->request->setMethod('POST')
			->setPost(array(
				'login' => 'test',
				'password' => '123456'
			));
		$this->dispatch('/login/login');
	}
}
