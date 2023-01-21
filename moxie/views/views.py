import datetime

from django.views.generic import CreateView, UpdateView, DeleteView, ListView
from moxie.forms import CategoryForm, CategoryUpdateForm, ExpensesForm
from django.urls import reverse_lazy
from django_filters.views import FilterView
from django.db.models import Sum
from django.db.models.functions import Abs
from moxie.filters import ExpensesFilter
from moxie.models import Transaction, Tag, Budget


class CreateCategory(CreateView):
	form_class = CategoryForm

    # def addAction():
    # 	try:
	#     	Category::create(
    #             'user_owner' => $_SESSION['user_id'],
    #             'parent' => $data['parent'],
    #             'name' => $data['name'],
    #             'description' => $data['description'],
    #             'type'	=>	$data['type']
	#     	)
	# 	except:
	# 	    logger.error('Exception caught on '.__CLASS__.', '.__FUNCTION__.'('.$e->getLine().'), message: '.$e->getMessage())
    # 	$this->_helper->redirector('index','categories');


class UpdateCategory(UpdateView):
	form_class = CategoryUpdateForm
	success_url = reverse_lazy('')

	# private function getEditForm($i_categoryPK) {
	# 	global $st_lang;
	# 	$form  = new Zend_Form();
	#
	# 	// retrieve data to fill the form
	# 	$st_category = $this->categories->find($i_categoryPK);
	#
	# 	$form->setAction('/categories/update')->setMethod('post');
	#
	# 	$form->addElement('hidden', 'id', array('value' => $i_categoryPK));
	# 	// Add select
	# 	$form->addElement('select', 'parent', array(
	# 		'label' => $st_lang['category_parent'],
	# 		'multioptions' => $this->categories->getCategoriesForSelect(3),
	# 		'value'	=>	$st_category[0]['parent'],
    #             'class' => 'form-control'
	# 		)
	# 	);
	# 	$form->addElement('text', 'name', array('label' => $st_lang['category_name'], 'value' => $st_category[0]['name'], 'class' => 'form-control'));
	# 	$form->addElement('text', 'description', array('label' => $st_lang['category_description'], 'value' => $st_category[0]['description'], 'class' => 'form-control'));
	#
	# 	$categoryTypes = array(Categories::EXPENSES => $st_lang['category_expense'], Categories::INCOMES => $st_lang['category_income'], Categories::BOTH => $st_lang['category_both']);
	# 	$types = new Zend_Form_Element_Radio('type');
	# 	$types->setRequired(true)  // field required
	# 	->setValue($st_category[0]['type']) // first radio button selected
	# 	->setMultiOptions($categoryTypes)  // add array of values / labels for radio group
    #     	->setAttrib('class', 'form-control');
	# 	$form->addElement($types);
	#
	# 	$form->addElement('submit','submit', array('label' => $st_lang['category_send'], 'class' => 'form-control btn-primary'));
	# 	$form->addElement('button', 'delete',
	# 			array(
	# 				'label' => $st_lang['categories_delete'],
	# 				'onclick' => 'window.location.replace("/categories/delete/id/'.$i_categoryPK.'");',
    #                 'class' => 'form-control btn-danger'
	# 			)
	# 	);
	# 	return $form;
	# }
	#
    # public function editAction() {
    # 	$this->view->assign('categories_form', $this->getEditForm($this->_request->getParam('id')));
    # 	$this->view->assign('categories_list', $this->categories->mountCategoryTree($this->categories->getCategoriesByUser(3), $_SESSION['user_id']));
	# 	$this->view->assign('categories_collapse', false);
    # 	$this->_forward('index', 'users');
    # }

    # public function updateAction() {
    #     try {
	#     	$data = $this->_request->getParams();
	#     	$st_update = array(
	#     		'name'	=>	$data['name'],
	#     		'description'	=>	$data['description'],
	#     		'parent'		=>	$data['parent'],
	#     		'type'			=>	$data['type']
	#     	);
	#     	$this->categories->update($st_update,'id = '.$data['id'].' AND user_owner = '.$_SESSION['user_id']);
	# 	} catch (Exception $e) {
    # 		error_log('Exception caught on '.__CLASS__.', '.__FUNCTION__.'('.$e->getLine().'), message: '.$e->getMessage());
    # 	}
    # 	$this->_helper->redirector('index','categories');
    # }


class DeleteCategory(DeleteView):
	pass


# class CategoriesController extends Zend_Controller_Action
# {
#     /**
#      * @return Zend_Form
#      */
# 	public static function getForm() {
# 		global $st_lang;
#     	$form  = new Zend_Form();
#
#     	$categories = new Categories();
#
#     	$form->setAction('/categories/add')
#             ->setMethod('post')
#     	    ->addElement('select', 'parent', array(
#                     'label' => $st_lang['category_parent'],
#                     'multioptions' => $categories->getCategoriesForSelect(3),
#                     'class' => 'form-control'
#                 )
#             )
# 		    ->addElement('text', 'name', array('label' => $st_lang['category_name'], 'class' => 'form-control'))
# 		    ->addElement('text', 'description', array('label' => $st_lang['category_description'], 'class' => 'form-control'));
#
# 		$categoryTypes = array(Categories::EXPENSES => $st_lang['category_expense'], Categories::INCOMES => $st_lang['category_income'], Categories::BOTH => $st_lang['category_both']);
# 		$types = new Zend_Form_Element_Radio('type');
# 		$types->setRequired(true)  // field required
#             ->setLabel($st_lang['category_type'])
#             ->setValue(Categories::BOTH) // first radio button selected
#             ->setAttrib('class', 'form-control')
#             ->setMultiOptions($categoryTypes);  // add array of values / labels for radio group
# 		$form->addElement($types);
#
# 		$form->addElement('submit','submit', array('label' => $st_lang['category_send'], 'class' => 'form-control btn-primary'));
#
# 		return $form;
# 	}
#
#     public function indexAction() {
# 	    $this->view->assign('categories_form', self::getForm());
# 	    $this->view->assign('categories_list', $this->categories->mountCategoryTree($this->categories->getCategoriesByUser(3), $_SESSION['user_id']));
# 		$this->view->assign('categories_collapse', true);
# 		$this->_forward('index', 'users');
#     }
#
#     public function deleteAction() {
#     	// TODO: check if category has expenses or incomes
#     	// if so, assign it before deleting
#     	// delete category
# 		$i_id = $this->getRequest()->getParam('id');
# 		try {
# 			// delete children categories
# 			$this->categories->delete('parent = '.$i_id);
# 			$this->categories->delete('id = '.$i_id);
# 		}
# 		catch (Exception $e) {
# 			error_log('Exception caught on '.__CLASS__.', '.__FUNCTION__.'('.$e->getLine().'), message: '.$e->getMessage());
# 		}
# 		$this->_helper->redirector('index','categories');
#     }
#
#     public function orderAction() {
#         try {
#             $data = $this->_request->getParams();
#             $order = 0;
#             foreach($data as $key => $category_id) {
#                 if(!is_int($key)) {
#                     continue;
#                 }
#                 $st_update = array('order'	=>	++$order);
#                 $this->categories->update($st_update,'id = '.$category_id.' AND user_owner = '.$_SESSION['user_id']);
#             }
#         } catch (Exception $e) {
#             error_log('Exception caught on '.__CLASS__.', '.__FUNCTION__.'('.$e->getLine().'), message: '.$e->getMessage());
#         }
#         $this->_helper->redirector('index','categories');
#     }
# }


class ExpensesView(FilterView):
	model = Transaction
	template_name = 'expenses/index.html'
	filterset_class = ExpensesFilter

	def get_queryset(self):
		queryset = super().get_queryset()
		import datetime
		queryset = queryset.filter(user_owner=1)\
			.filter(amount__lt=0)\
			.filter(date__lt=datetime.datetime.now(tz=datetime.timezone.utc).strptime("2011-03-01", "%Y-%m-%d"))\
			.filter(date__gte=datetime.datetime.now(tz=datetime.timezone.utc).strptime("2011-02-01", "%Y-%m-%d"))
		return queryset

	def get_context_data(self, **kwargs):
		context = super().get_context_data(**kwargs)
		queryset = self.get_queryset()
		context['total_amount'] = queryset.aggregate(total_amount=Sum('amount')).get('total_amount')
		context['current_amount'] = queryset.exclude(in_sum=False).aggregate(total_amount=Sum('amount')).get('total_amount')
		context['edit_slug'] = '/expenses/'
		context['date_get'] = ''
		context['urls'] = ['incomes', 'expenses', 'stats', 'sheets', 'users']
		context['tags'] = Tag.get_tags_by_user(self.request.user)
		context['filter'] = self.filterset_class(self.request.GET, queryset=queryset)
		context['form'] = ExpensesForm()
		context['pie_data'] = [list(a.values()) for a in self.__get_category_amounts(queryset)]
		context['budget'] = Budget.get_budget(1)
		return context

	def __get_category_amounts(self, expenses):
		return expenses.values('category__name').order_by('category__name')\
			.annotate(total=Abs(Sum('amount')))

	def __get_navigation_links(self, date):
		last_month = date - datetime.timedelta(days=31)
	# <!--if($this->source == "expenses") {-->
	# <!--    $last_month = ($this->month == 1) ? 12 : $this->month - 1;-->
	# <!--    $last_year = ($this->month == 1) ? $this->year - 1 : $this->year;-->
	# <!--    $next_month = ($this->month == 12) ? 1 : $this->month + 1;-->
	# <!--    $next_year = ($this->month == 12) ? $this->year + 1 : $this->year;-->
	# <!--    $last_url = "/expenses/index/month/" . $last_month . "/year/" . $last_year;-->
	# <!--    $next_url = "/expenses/index/month/" . $next_month . "/year/" . $next_year;-->
	# <!--    $current_month_and_year = date("M Y", strtotime("01-" . $this->month . "-" . $this->year));-->
	# <!--    // modify current month and year if we are in a search-->
	# <!--    try {-->
	# <!--        $first = $this->list->getRow(0);-->
	# <!--        if (strtotime($first['date']) < strtotime(date('Y-m-01'))) {-->
	# <!--            $current_month_and_year = date('M Y', strtotime($first['date']));-->
	# <!--        }-->
	# <!--    }-->
	# <!--    catch(Exception $e) {-->
	# <!--        // avoid error, previous and next will appear with current date-->
	# <!--    }-->
	# <!--}-->
	# <!--else {-->
	# # slugs for incomes
	# <!--    $editSlug = '/incomes/edit/id/';-->
	# <!--    $current_month_and_year = date("Y", strtotime("01-01-".$this->year));-->
	# <!--    $last_url = "/incomes/index/year/".($this->year-1);-->
	# <!--    $next_url = "/incomes/index/year/".($this->year+1);-->

	# todo export to excel
	# todo check if order and order by works properly
	# todo check if results are correct
	# todo check this st_expense needed in the frontend
	# /**
	#  * Shows the expenses view.
	#  * Receives call from export to excel too.
	#  */
	# public function indexAction() {
	# 	$st_params = $this->getParameters();
	#
	# 	$st_list = $this->expenses->get($_SESSION['user_id'],Categories::EXPENSES, $st_params);
	#
	# 	// order + switch order by
	# 	if (isset($st_params['o'])) {
	# 		$st_params['o'] = ($st_params['o'][0] == '-')
	# 				? substr($st_params['o'], 1)
	# 				: "-".$st_params['o'];
	# 	}
	#
	# 	if($this->getRequest()->getParam('to_excel') == true) {
	# 		$this->exportToExcel($st_list);
	# 	}
	#
	# 	$this->assignViewData($st_list, $st_params);
	# }


class ExpenseView(UpdateView):
	model = Transaction
	form_class = ExpensesForm
	template_name = 'expenses/index.html'

	def get_success_url(self):
		# todo get month and year for expense, get order, redirect
		return reverse_lazy('expenses_edit')


class AddExpense(CreateView):
	model = Transaction

	def get_success_url(self):
		return reverse_lazy('')


# 	/**
# 	 * Adds an expense and shows expenses index again
# 	 * @author	hmeza
# 	 */
# 	public function addAction() {
# 		$st_form = $this->getRequest()->getPost();
# 		if(empty($st_form['amount'])) {
#             $this->_helper->redirector('index','expenses');
#         }
# 		$st_form['amount'] = str_replace(",",".",$st_form['amount']);
# 		$st_form['date'] = str_replace('/', '-', $st_form['date']);
# 		if(empty($st_form['category'])) {
# 			throw new Exception("Empty category not allowed for expenses");
# 		}
# 		if(empty($_SESSION['user_id'])) {
# 			$this->redirect('/index');
# 		}
# 		try {
# 			$expenseId = $this->expenses->addExpense($_SESSION['user_id'], $st_form);
# 			$tags = $this->getRequest()->getParam('tags', array());
# 			if (!empty($tags)) {
# 				$this->updateTags(explode(",", $tags), $expenseId);
#             }
# 		}
# 		catch(Zend_Db_Statement_Exception $e) {
#             error_log(__METHOD__.": ".$e->getMessage());
# 			throw new Exception("Error adding expense");
# 		}
# 		$this->_helper->redirector('index','expenses');
# 	}
#
# 	/**
# 	 * Edits a given expense
# 	 * @author	hmeza
# 	 */
# 	public function editAction() {
# 		$i_expensePK = $this->getRequest()->getParam('id');
# 		// retrieve data to fill the form
# 		$st_expense = $this->expenses->getExpenseByPK($i_expensePK);
# 		if($st_expense['user_owner'] != $_SESSION['user_id']) {
# 			throw new Exception("Access error");
# 		}
#
# 		$st_params = $this->getParameters();
#
# 		$st_list = $this->expenses->get($_SESSION['user_id'],Categories::EXPENSES, $st_params);
#
# 		if (isset($st_params['o'])) {
# 			$st_params['o'] = "-".$st_params['o'];
# 		}
#
# 		$this->assignViewData($st_list, $st_params, $st_expense);
# 		$this->view->assign('tags', $this->transactionTags->getTagsForTransaction($i_expensePK));
# 		$this->render('index');
# 	}
#
# 	/**
# 	 * Updates an expense
# 	 */
# 	public function updateAction() {
# 		$st_params = $this->getRequest()->getPost();
# 		$i_expensePK = $st_params['id'];
#
#         // retrieve data to perform user check
#         $st_expense = $this->expenses->getExpenseByPK($i_expensePK);
#         if($st_expense['user_owner'] != $_SESSION['user_id']) {
#             throw new Exception("Access error");
#         }
#
# 		$originalExpenseDate = explode("-", $st_expense['date']);
# 		$this->transactionTags->removeTagsFromTransaction($i_expensePK);
# 		if(!empty($st_params['tags'])) {
# 			$this->updateTags(
# 					explode(",", $st_params['tags']),
# 					$i_expensePK
# 			);
# 		}
#
# 		$this->expenses->updateExpense($st_params);
# 		$this->getResponse()->setRedirect('/expenses/index/month/'.$originalExpenseDate[1].'/year/'.$originalExpenseDate[0]);
# 	}
#
# 	/**
# 	 * Deletes an expense.
# 	 */
# 	public function deleteAction() {
# 		$i_expensePK = $this->getRequest()->getParam('id');
# 		try {
# 			$this->transactionTags->getAdapter()->beginTransaction();
# 			$this->favourites->deleteByTransactionId($i_expensePK);
# 			$this->transactionTags->removeTagsFromTransaction($i_expensePK);
# 			$this->expenses->delete($i_expensePK, $_SESSION['user_id']);
# 			$this->transactionTags->getAdapter()->commit();
# 		} catch (Exception $e) {
# 			error_log(__METHOD__.": ".$e->getMessage());
# 			$this->transactionTags->getAdapter()->rollBack();
# 		}
# 		$this->_helper->redirector('index','expenses');
# 	}
#
# 	/**
# 	 * This function generates the form to add expenses.
# 	 * @param array $st_expense
# 	 * @return Zend_Form
# 	 */
# 	private function getForm($st_expense) {
# 		global $st_lang;
# 		$form  = new Zend_Form();
#
# 		if(empty($st_expense['id'])) {
# 			$in_sum_value = 1;
# 			$slug = '/expenses/add';
# 			$tag_value = '';
# 			$save_text = $st_lang['expenses_header'];
# 		}
# 		else {
# 			$in_sum_value = $st_expense['in_sum'];
# 			$slug = '/expenses/update';
# 			$tag_list = $this->transactionTags->getTagsForTransaction($st_expense['id']);
# 			$tag_list = str_replace("\\'", "'", $tag_list);
# 			$tag_value = implode(", ", $tag_list);
#             $save_text = $st_lang['expenses_edit'];
# 		}
#
# 		$form->setAction(Zend_Registry::get('config')->moxie->settings->url.$slug)
# 				->setMethod('post');
#
# 		$form->setAttrib('id', 'add_expense');
#
#         $form->setDecorators(array(
#             'FormElements',
#             array('HtmlTag',array('tag' => 'table')),
#             'Form'
#         ));
#
# 		$st_categories = $this->categories->getCategoriesForView(Categories::EXPENSES);
# 		if(empty($st_expense['category'])) {
# 			reset($st_categories);
# 			$st_expense['category'] = key($st_categories);
# 		}
#
# 		$f_expense = !empty($st_expense['amount']) && $st_expense['amount'] != '0.00' ? $st_expense['amount'] : '';
#
#         $form_elements = array();
#
#         $favs = $this->expenses->getFavourites($_SESSION['user_id']);
#         if(!empty($favs)) {
#             $st_favs = array(0 => '');
#             foreach ($favs as $fav) {
#                 $st_favs[$fav['id']] = $fav['note'];
#             }
#             $favouritesOptions = new Zend_Form_Element_Select('category');
#             $favouritesOptions->setName('favourites');
#             $favouritesOptions->setLabel($st_lang['use_favourite']);
#             $favouritesOptions->addMultiOptions($st_favs);
#             $favouritesOptions->setValue(array($st_expense['category']));
#             $favouritesOptions->setAttrib('class', 'form-control font-weight-bold');
#             $form_elements[] = $favouritesOptions;
#         }
#
#         $form_elements[] = new Zend_Form_Element_Text('amount' , array('label' => $st_lang['expenses_amount'], 'value' => $f_expense, 'placeholder' => '0,00', 'class' => 'form-control'));
#         $form_elements[] = new Zend_Form_Element_Text('note' , array('label' => $st_lang['expenses_note'], 'value' => $st_expense['note'], 'class' => 'form-control'));
#         $form_elements[] = new Zend_Form_Element_Date('date' , array('label' => $st_lang['expenses_date'], 'value' => $st_expense['date'], 'class' => 'form-control'));
#
#         $multiOptions = new Zend_Form_Element_Select('category');
#         $multiOptions->setName('category');
#         $multiOptions->setLabel($st_lang['expenses_category']);
#         $multiOptions->addMultiOptions($st_categories);
#         $multiOptions->setValue(array($st_expense['category']));
#         $multiOptions->setAttrib('class', 'form-control');
#         $form_elements[] = $multiOptions;
#
#         $form_elements[] = new Zend_Form_Element_Text('tags', array('id' => 'tags', 'label' => 'Tags', 'value' => $tag_value, 'placeholder' => $st_lang['tags_placeholder'], 'class' => 'form-control typeahead'));
#         $form_elements[] = new Zend_Form_Element_Checkbox('in_sum', array('label' => $st_lang['in_sum_message'], 'value' => $in_sum_value, 'style' => 'width: 20px;', 'class' => 'checkbox-inline'));
#         $form_elements[] = new Zend_Form_Element_Checkbox('favourite', array('label' => $st_lang['favourite_message'], 'value' => $st_expense['favourite'], 'style' => 'width: 20px;', 'class' => 'checkbox-inline'));
#
# 		if (isset($st_expense['id'])) {
# 			$remove = new Zend_Form_Element_Button('delete', array(
#                 'label' => $st_lang['expenses_delete'],
#                 'class' => 'btn btn-danger pull-right',
#                 'onclick' => 'confirmDelete("'.$st_expense['id'].'")'
#             ));
#             $form_elements[] = $remove;
# 		}
#
#         $submit = new Zend_Form_Element_Submit('submit', array('label' => $save_text, 'class' => 'btn btn-primary pull-right'));
#         $form_elements[] = $submit;
#         if (isset($st_expense['id'])) {
#             $form_elements[] = new Zend_Form_Element_Hidden('id', array('label' => null, 'value' => $st_expense['id']));
#         }
#
# 		$this->prepareFormDecorators($form, $form_elements);
#         // removing label works after this point
#         if(isset($remove)) {
#             $remove->removeDecorator("Label");
#         }
#         $submit->removeDecorator("Label");
#
# 		return $form;
# 	}
#
# 	/**
# 	 * Returns the monthly expense for a year.
# 	 * @return array
# 	 */
# 	private function getMonthExpensesData() {
# 		$st_data = array();
# 		$i_dateLimit = date("Y-m-01 00:00:00", strtotime("-12 months"));
#
# 		$o_rows = $this->expenses->getMonthExpensesData($_SESSION['user_id'], $i_dateLimit);
# 		$o_rowsNotInSum = $this->expenses->getMonthExpensesData($_SESSION['user_id'], $i_dateLimit, null, 0);
#
# 		// base expense
# 		foreach ($o_rows as $key => $value) {
# 			try {
# 				$timestamp = mktime(0, 0, 0, $value['month'], 1, $value['year']);
# 				$st_data[$timestamp] = array(
# 						date("M", $timestamp),
# 						(float)$value['amount']
# 				);
# 			}
# 			catch(Exception $e) {
# 				error_log(__METHOD__.": ".$e->getMessage());
# 				error_log(__METHOD__." data: ".$key." ".print_r($value,true));
# 			}
# 		}
# 		// expense not in sum
# 		foreach($o_rowsNotInSum as $key => $value) {
# 		    try {
#                 $timestamp = mktime(0, 0, 0, $value['month'], 1, $value['year']);
#                 $st_data[$timestamp][] = (float)$value['amount'];
#             }
#             catch(Exception $e) {
#                 error_log(__METHOD__.": ".$e->getMessage());
#                 error_log(__METHOD__." data: ".$key." ".print_r($value,true));
#             }
#         }
#         // fill with zeros months without expense
#         foreach($st_data as $key => $value) {
# 		    if(count($value) == 2) {
# 		        $st_data[$key][] = 0;
#             }
#         }
#         global $st_lang;
# 		$st_data = array_merge(array(array('Month', $st_lang['expenses_in_total'], $st_lang['expenses_not_in_total'])), $st_data);
# 		return $st_data;
# 	}
#
# 	/**
# 	 * Exports to excel the data currently shown in the view.
# 	 * @param array|array[] $st_data containing rows with columns date, amount, name and note.
# 	 */
# 	private function exportToExcel($st_data) {
# 		$this->getResponse()
# 				->setHttpResponseCode(200)
# 				->setHeader('Content-Type', 'text/csv; charset=utf-8')
# 				->setHeader('Content-Disposition', 'attachment; filename='.date('Y-m-d').'.csv')
# 				->setHeader('Pragma', 'no-cache')
# 				->sendHeaders();
#
# 		$output = fopen('php://output', 'w');
#
# 		fputcsv($output, array('Fecha', 'Euros', 'Categoria', 'Nota'));
#
# 		foreach($st_data as $row) {
# 			$outputRow = array(
# 					'Fecha' => $row['date'],
# 					'Euros' => $row['amount'],
# 					'Categoria' => $row['name'],
# 					'Nota' => $row['note']
# 			);
# 			fputcsv($output, $outputRow);
# 		}
# 		exit(0);
# 	}
#
# 	/**
# 	 * @param array $tags
# 	 * @param int $expenseId
# 	 * @throws Exception
# 	 */
# 	private function updateTags($tags, $expenseId) {
# 		foreach($tags as $tag) {
#             $tagId = $this->tags->addTag($_SESSION['user_id'], $tag);
# 			$this->transactionTags->addTagToTransaction($expenseId, $tagId);
# 		}
# 	}
#
# 	/**
# 	 * Retrieves parameters from request.
# 	 */
# 	private function getParameters() {
# 		$st_params = $this->getRequest()->getParams();
# 		$st_params['month'] = $this->getRequest()->getParam('month', date('n'));
# 		$st_params['year'] = $this->getRequest()->getParam('year', date('Y'));
# 		$category = $this->getRequest()->getParam('category', null);
# 		if(empty($st_params['category_search'])) {
# 			$st_params['category_search'] = $category;
# 		}
#
# 		// convert month+year to filters for search
# 		if(empty($st_params['date_min']) && empty($st_params['date_max'])) {
# 			$current_date = $st_params['year'].'-'.$st_params['month'].'-01';
# 			$st_params['date_min'] = $current_date;
# 			$st_params['date_max'] = date("Y-m-t", strtotime($current_date));
# 		}
#
# 		return $st_params;
# 	}
#
# 	/**
# 	 * @param array $st_list
# 	 * @param array $st_params
# 	 * @param array $st_expense
# 	 * @throws Exception
# 	 */
# 	private function assignViewData($st_list, $st_params, $st_expense) {
# 		$this->view->assign('expenses', $this->expenses->getExpenses($_SESSION['user_id'], $st_params));
# 		$this->view->assign('month_expenses', json_encode($this->getMonthExpensesData()));
# 		$this->view->assign('budget', $this->budgets->getBudget($_SESSION['user_id']));
# 		$this->view->assign('list', $st_list);
# 		$this->view->assign('year', $st_params['year']);
# 		$this->view->assign('month', $st_params['month']);
# 		$this->view->assign('form', $this->getForm($st_expense));
# 		$this->view->assign('tag_list', $this->tags->getTagsByUser($_SESSION['user_id']));
# 		$this->view->assign('used_tag_list', $this->tags->getUsedTagsByUser($_SESSION['user_id']));
# 		$this->view->assign('search_form', $this->getSearchForm($this->getRequest()));
# 		$this->view->assign('view', 'expenses');
# 		if (isset($st_params['o'])) {
# 			$this->view->assign('o', $st_params['o']);
# 		}
# 		if($this->getRequest()->getParam('is_search', false) == 1) {
# 		    $this->view->assign('is_search', true);
#         }
# 		$this->view->assign('favourites_json', json_encode($this->expenses->getFavourites($_SESSION['user_id'])));
# 	}
# }
