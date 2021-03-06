<?php

class IncomesControllerTest extends Zend_Test_PHPUnit_ControllerTestCase {
	public function setUp() {
		$_SERVER['SERVER_NAME'] = "testing";
		$_SERVER['REQUEST_URI'] = "test";
		$this->bootstrap = new Zend_Application(
			'testing',
			APPLICATION_PATH . '/configs/application.ini'
		);
		parent::setUp();
		$this->fakeLogin();
	}

	public function testIndexIncomes() {
		$this->markTestSkipped();
		$_SERVER['REQUEST_URI'] = 'http://moxie.dev/foo/bar';

		$this->fakeLogin();
		$_SESSION['user_lang'] = 'es';
		$this->dispatch('/incomes/');
		$this->assertController('incomes');
		$this->assertAction('index');

		$this->assertQueryContentContains('dt', 'Importe');
		$this->assertQueryContentContains('dt', 'Categoría');
		$this->assertQueryContentContains('dt', 'Nota');
	}

	public function testEditIncomes() {
		$this->markTestSkipped();
		$_SERVER['REQUEST_URI'] = 'http://moxie.dev/foo/bar';

		$this->fakeLogin();
		$this->dispatch('/incomes/');
		$this->assertController('incomes');
		$this->assertAction('index');

		$this->assertQueryContentContains('dt', 'Importe');
		$this->assertQueryContentContains('dt', 'Categoría');
		$this->assertQueryContentContains('dt', 'Nota');
	}

	public function testEditIncomesThrowsErrorIfNoIncomeIsFound() {
		$_SERVER['REQUEST_URI'] = 'http://moxie.dev/foo/bar';

		$this->fakeLogin();
		$this->request->setMethod('GET');
		$this->dispatch('/incomes/edit/id/0');
		$this->assertController('error');
		$this->assertAction('error');
		$this->assertQueryContentContains('p', 'An error occurred:');
		$this->assertQuery('pre');
		$this->assertQueryContentContains('pre', 'Access error');
	}

	/**
	 * @dataProvider addIncomeDataProvider
	 */
	public function testAddIncomes($amount, $date, $note = '', $category) {
		$this->markTestSkipped();
		$this->fakeLogin();
		$this->request->setMethod('POST')
			->setPost(array(
				'amount' => $amount,
				'date' => $date,
				'note' => $note,
				'category' => $category
			));
		$this->dispatch('/incomes/add');
		$this->assertController('incomes');
		$this->assertAction('add');
		$this->assertRedirectTo('/incomes');
	}

	public function addIncomeDataProvider() {
		return array(
			// normal expense
			array(10.23, '21/01/2016', 'test note', 1),
			// amount with comma
			array("10,25", '21/01/2016', 'test note 2', 1),
			// empty note
			array(10.65, '23/01/2016', '', 3)
		);
	}

	public function testAddIncomeWithoutCategoryThrowsError() {
		$_SERVER['REQUEST_URI'] = 'http://moxie.dev/foo/bar';
		$this->fakeLogin();
		$this->request->setMethod('POST')
				->setPost(array(
						'amount' => 12.01,
						'date' => '12/01/2016',
						'note' => 'test note without category'
				));
		$this->dispatch('/incomes/add');
		$this->assertController('error');
		$this->assertAction('error');
		$this->assertQueryContentContains('p', 'An error occurred:');
		$this->assertQuery('pre');
		$this->assertQueryContentContains('pre', 'Empty category not allowed for incomes');

	}

	public function testAddIncomeWithoutUserThrowsError() {
		// arrange
		$_SERVER['REQUEST_URI'] = 'http://moxie.dev/foo/bar';
		$this->fakeLogin();
		$this->resetRequest()
				->resetResponse();
		$this->request->setPost(array());

		$this->request->setMethod('POST')->setPost(array());

		// act
		$this->dispatch('/incomes/delete');

		// assert
		$this->assertRedirectTo('/incomes');
	}

	public function testDeleteWithoutIncomeIdDoesNotDeleteIncomeAndDoesNotThrowError() {
		// arrange
		$this->resetRequest()
				->resetResponse();
		$this->request->setPost(array());

		$this->request->setMethod('POST')->setPost(array('id' => 1234));

		// act
		$this->dispatch('/incomes/delete');

		// assert
		$this->assertRedirectTo('/incomes');
	}

	public function testDeleteWithoutUserDoesNotDeleteIncomeAndDoesNotThrowError() {
		// arrange
		$this->dispatch('/login/logout');
		$this->resetRequest()
				->resetResponse();
		$this->request->setPost(array());

		$this->request->setMethod('POST')
				->setPost(array(
						'id' => 1234
				));

		// act
		$this->dispatch('/incomes/delete');

		// assert
		$this->assertRedirectTo('/incomes');
	}

	public function testDelete() {
		// arrange
		$incomes = new Incomes();
		$row = $incomes->fetchRow('SELECT id FROM transactions where user_owner = 14 AND amount > 0 ORDER BY id DESC LIMIT 1');
		$incomePK = $row['id'];

		$_SERVER['REQUEST_URI'] = 'http://moxie.dev/foo/bar';
		$this->fakeLogin();
		$this->resetRequest()
				->resetResponse();
		$this->request->setPost(array());

		$this->request->setMethod('POST')
				->setPost(array(
						'id' => $incomePK
				));

		// act
		$this->dispatch('/incomes/delete');

		// assert
		$this->assertRedirectTo('/incomes');
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
