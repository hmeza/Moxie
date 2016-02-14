<?php

class ExpensesControllerTest extends Zend_Test_PHPUnit_ControllerTestCase {
	public function setUp() {
		$_SERVER['SERVER_NAME'] = "testing";
		$_SERVER['REQUEST_URI'] = 'http://moxie.dev/foo/bar';
		$this->bootstrap = new Zend_Application(
			'testing',
			APPLICATION_PATH . '/configs/application.ini'
		);
		parent::setUp();
		$this->fakeLogin();
	}

	public function testExpensesIndexShowsForm() {
		$this->fakeLogin();
		$this->request->setMethod('GET');

		$this->dispatch('/expenses/index');

		$this->assertController('expenses');
		$this->assertAction('index');

		$this->assertQueryContentContains('form', 'Importe');
		$this->assertQueryContentContains('form', 'Nota');
		$this->assertQueryContentContains('form', 'Fecha');
		$this->assertQueryContentContains('form', 'Categoría');
		$this->assertQueryContentContains('form', 'Tags');
//		$this->assertQueryContentContains('input', 'Empty category not allowed for expenses');

		// must see form
		// must not see in_sum checkbox
		// must not find id_expense
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

	public function testAddExpenseWithoutCategoryThrowsError() {
		$this->fakeLogin();
		$this->request->setMethod('POST')
				->setPost(array(
						'amount' => 12.01,
						'date' => '12/01/2016',
						'note' => 'test note without category',
						'taggles' => array()
				));
		$this->dispatch('/expenses/add');
		$this->assertController('error');
		$this->assertAction('error');
		$this->assertQueryContentContains('p', 'An error occurred:');
		$this->assertQuery('pre');
		$this->assertQueryContentContains('pre', 'Empty category not allowed for expenses');

	}

	public function testAddExpenseWithoutUserThrowsError() {
		// arrange
		$this->dispatch('/login/logout');
		$this->resetRequest()
				->resetResponse();
		$this->request->setPost(array());

		$this->request->setMethod('POST')
				->setPost(array(
						'amount' => 12.01,
						'date' => '12/01/2016',
						'note' => 'test note without category',
						'category' => 10,
						'taggles' => array()
				));

		// act
		$this->dispatch('/expenses/add');

		// assert
		$this->assertRedirectTo('/index');

	}

	private function fakeLogin() {
		$this->request->setMethod('POST')
			->setPost(array(
				'login' => 'test',
				'password' => '123456'
			));
		$this->dispatch('/login/login');

		$this->resetRequest()
			->resetResponse();

		$this->request->setPost(array());
	}
}
