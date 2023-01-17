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

		$params = $request->getParams();

		if(!isset($params['is_search']) || $params['is_search'] != 1) {
		    if(isset($_SESSION['search_params'])) {
                $request->setParams($_SESSION['search_params']);
            }
        }
        else {
		    $_SESSION['search_params'] = $params;
        }

		$form  = new Zend_Form();
		$form->setName("search_form");
		$form->setAttrib('class', 'moxie_form');

        $form->setDecorators(array(
            'FormElements',
            array('HtmlTag',array('tag' => 'table')),
            'Form'
        ));

        $form_elements = array();

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
		asort($st_categories);
		$st_categories = array(0 => '---', -1 => $st_lang['empty_category']) + $st_categories;

		$multiOptions = new Zend_Form_Element_Select('category');
		$multiOptions->setName('category_search');
		$multiOptions->setLabel($st_lang['expenses_category']);
		$multiOptions->addMultiOptions($st_categories);
		$multiOptions->setValue($request->getParam('category_search', ''));
		$multiOptions->setAttrib('class', 'form-control');
        $form_elements[] = $multiOptions;

		if($category_type == Categories::EXPENSES) {
			$tag_search = new Zend_Form_Element_Text('tag_search', array('label' => $st_lang['search_tag'], 'value' => $request->getParam('tag_search', ''), 'class' => 'form-control typeahead'));
			$form_elements[] = $tag_search;
		}

        $submit = new Zend_Form_Element_Submit('search_submit', array('label' => $st_lang['search_send'], 'class' => 'btn btn-info pull-right'));
		$form_elements[] = new Zend_Form_Element_Text('note_search', array('label' => $st_lang['search_note'], 'value' => $request->getParam('note_search', ''), 'class' => 'form-control'));
        $form_elements[] = new Zend_Form_Element_Text('amount_min', array('label' => $st_lang['search_amount_min'], 'value' => $request->getParam('amount_min', 0), 'class' => 'form-control'));
        $form_elements[] = new Zend_Form_Element_Text('amount_max', array('label' => $st_lang['search_amount_max'], 'value' => $request->getParam('amount_max', ''), 'class' => 'form-control'));
        $form_elements[] = new Zend_Form_Element_Date('date_min', array('label' => $st_lang['search_date_min'], 'value' => $request->getParam('date_min', $current_min_date), 'class' => 'form-control'));
		$form_elements[] = new Zend_Form_Element_Date('date_max', array('label' => $st_lang['search_date_max'], 'value' => $request->getParam('date_max', $current_max_date), 'class' => 'form-control'));
		$form_elements[] = new Zend_Form_Element_Hidden('to_excel', array('value' => 0));
        $form_elements[] = new Zend_Form_Element_Hidden('is_search', array('value' => 1));
		$form_elements[] = $submit;

        $this->prepareFormDecorators($form, $form_elements);
        $submit->removeDecorator("label");
		return $form;
	}

	protected function prepareFormDecorators($form, $form_elements) {
        $decorators = array(
            'ViewHelper',
            array('Errors'),
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')),
            array('Label', array('tag' => 'td')),
            array(array('row' => 'HtmlTag'), array('tag' => 'tr')),
        );

        foreach($form_elements as $element) {
            /** @var Zend_Form_Decorator_Label $decorator */
            $decorator = $element->getDecorator('Label');
            if ($decorator) $decorator->setOption('placement', Zend_Form_Decorator_Abstract::APPEND);
            $element->removeDecorator('DtDdWrapper');
            if($element instanceof Zend_Form_Element_Submit) {
                 $decorators = array(
                    'ViewHelper',
                    array('Errors'),
                    array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element', 'colspan' => 2)),
                    array(array('row' => 'HtmlTag'), array('tag' => 'tr')),
                );
            }
            $element->setDecorators($decorators);
            $form->addElement($element);
        }
    }
}
