<?php
/** Zend_Controller_Action */
class TransactionsController extends Zend_Controller_Action
{
	/** @var Categories */
	protected $categories;
	/** @var TransactionTags */
	protected $transactionTags;

	public function init() {
		parent::init();
		$this->categories = new Categories();
		$this->transactionTags = new TransactionTags();
	}

	/**
	 * @param Zend_Controller_Request_Abstract $request
	 * @return Zend_Form
	 * @throws Zend_Exception
	 * @throws Zend_Form_Exception
	 */
	protected function getSearchForm($request, $category_type = Categories::EXPENSES) {
		global $st_lang;
		$form  = new Zend_Form();
		$form->setName("search_form");

		// mount default min and max date
		if($category_type == Categories::EXPENSES) {
			$month = $num_padded = sprintf("%02d", $request->getParam('month', date('m')));
			$current_min_date = $request->getParam('year', date('Y')).'-'.$month.'-01';
			$current_max_date = date("Y-m-t", strtotime($current_min_date));
			$slug = '/expenses/index';
		}
		else {
			$current_min_date = $request->getParam('year', date('Y')).'-01-01';
			$current_max_date = date("Y-12-t", strtotime($current_min_date));
			$slug = '/incomes/index';
		}

		$form->setAction(Zend_Registry::get('config')->moxie->settings->url.$slug)->setMethod('post');

		$st_categories = $this->categories->getCategoriesForView($category_type);
		$st_categories[0] = '---';
		asort($st_categories);

		$multiOptions = new Zend_Form_Element_Select('category');
		$multiOptions->setName('category_search');
		$multiOptions->setLabel($st_lang['expenses_category']);
		$multiOptions->addMultiOptions($st_categories);
		$multiOptions->setValue($request->getParam('category_search', ''));
		$form->addElement($multiOptions);

		if($category_type == Categories::EXPENSES) {
			$form->addElement('text', 'tag_search', array('label' => $st_lang['search_tag'], 'value' => $request->getParam('tag', '')));
		}

		$form->addElement('text', 'note_search', array('label' => $st_lang['search_note'], 'value' => $request->getParam('note', '')));

		$form->addElement('text', 'amount_min', array('label' => $st_lang['search_amount_min'], 'value' => $request->getParam('amount_min', 0)));
		$form->addElement('text', 'amount_max', array('label' => $st_lang['search_amount_max'], 'value' => $request->getParam('amount_max', '')));

		$form->addElement('text', 'date_min', array('label' => $st_lang['search_date_min'], 'value' => $request->getParam('date_min', $current_min_date)));
		$form->addElement('text', 'date_max', array('label' => $st_lang['search_date_max'], 'value' => $request->getParam('date_max', $current_max_date)));
		$form->addElement('hidden', 'to_excel', array('value' => 0));
		$form->addElement('submit','search_submit', array('label' => $st_lang['search_send']));
		return $form;
	}
}
