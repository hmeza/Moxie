import datetime
import calendar
import re
from dateutil.relativedelta import relativedelta
from django.views.generic import CreateView, UpdateView, DeleteView, ListView
from django.shortcuts import redirect
from django.utils.translation import gettext_lazy as _

from moxie.forms import CategoryForm, CategoryUpdateForm, ExpensesForm
from django.urls import reverse_lazy
from django_filters.views import FilterView
from django.db.models import Sum, FloatField, Case, When
from django.db.models.functions import Abs, Cast
from moxie.filters import ExpensesFilter
from moxie.models import Transaction, Tag, Budget, TransactionTag, Favourite


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


class TransactionListView(FilterView):
	filterset_class = ExpensesFilter

	def get_queryset(self):
		start_date, end_date = self.__get_start_and_end_date()
		order_field = self.__get_order_field()

		queryset = super().get_queryset()
		queryset = queryset.filter(user=self.request.user)\
			.filter(amount__lt=0, date__lt=end_date, date__gte=start_date)\
			.order_by(order_field)

		return queryset

	def __get_order_field(self):
		return self.request.GET.get('order', '-date')

	def __get_start_and_end_date(self):
		(year, month) = self._get_active_year_and_month()
		if year and month:
			start_date = datetime.datetime.strptime(f"{year}-{month}-01", '%Y-%m-%d').date()
			end_date = (start_date + datetime.timedelta(days=32)).replace(day=1)
		else:
			start_date = datetime.date.today().replace(day=1)
			end_date = datetime.date.today()
			end_date = end_date.replace(month=end_date.month + 1, day=1) - datetime.timedelta(days=1)
		return start_date, end_date

	def _get_active_year_and_month(self):
		url = self.request.path
		if 'year' in url and 'month' in url:
			groups = re.search(r'year/(\d+)/month/(\d+)/$', url)
			if groups:
				year = groups.group(1)
				month = groups.group(2)
			else:
				raise Exception(f"Dates not found in url {url}")
		else:
			current_date = datetime.date.today()
			year = current_date.year
			month = current_date.month
		return year, month

	def get_filterset_kwargs(self, filterset_class):
		kwargs = super().get_filterset_kwargs(filterset_class)
		kwargs['user'] = self.request.user
		return kwargs


class ExpensesView(TransactionListView, ListView):
	model = Transaction
	template_name = 'expenses/index.html'

	def __get_last_year_and_month(self, year, month):
		date = datetime.datetime.strptime(f"{year}-{month}-01", '%Y-%m-%d').date()
		date = date - relativedelta(months=1)
		return date.year, date.month

	def __get_next_year_and_month(self, year, month):
		date = datetime.datetime.strptime(f"{year}-{month}-01", '%Y-%m-%d').date()
		date = date + relativedelta(months=1)
		return date.year, date.month

	def get_context_data(self, **kwargs):
		context = super().get_context_data(**kwargs)
		user = self.request.user
		queryset = self.get_queryset()
		context['total_amount'] = queryset.aggregate(total_amount=Sum('amount')).get('total_amount')
		context['current_amount'] = queryset.exclude(in_sum=False).aggregate(total_amount=Sum('amount')).get('total_amount')
		context['edit_slug'] = '/expenses/'
		context['date_get'] = ''
		_('incomes')
		_('expenses')
		_('stats')
		_('sheets')
		_('users')
		context['tags'] = Tag.get_tags(user)
		context['used_tag_list'] = Tag.get_used_tags(user)
		context['form'] = ExpensesForm(user)
		category_amounts = Transaction.get_category_amounts(user, datetime.date.today(), self.request.GET)
		context['category_amounts'] = category_amounts
		context['pie_data'] = [list(a.values()) for a in category_amounts]
		month_expenses = [list(a.values()) for a in self.__get_monthly_amounts(queryset)]
		month_expenses_list = [["Month", "En la suma total", "Fuera del total"]]
		for expense in month_expenses:
			month_name = datetime.datetime.strptime("2023-{}-01".format(expense[0]), "%Y-%m-%d").strftime("%m")
			month_expenses_list.append([month_name, expense[1], expense[2]])
		context['month_expenses'] = month_expenses_list
		year, month = self._get_active_year_and_month()
		budget = Budget.get_budget_for_month(user, year, month)
		context['budget'] = budget
		context['budget_total'] = budget.aggregate(sum=Sum('user__budgets__amount')).get('sum')
		context['budget_total_spent'] = budget.aggregate(sum=Sum('transaction_total')).get('sum')
		context['year'] = year
		context['month'] = month
		context['current_month_and_year'] = "{} {}".format(calendar.month_name[int(month)][:3], year)
		last_year, last_month = self.__get_last_year_and_month(year, month)
		next_year, next_month = self.__get_next_year_and_month(year, month)
		context['last_url'] = f"/expenses/year/{last_year}/month/{last_month}"
		context['next_url'] = f"/expenses/year/{next_year}/month/{next_month}"
		context['edit_url'] = reverse_lazy('expenses_add')
		context['filter_url_name'] = 'expenses'
		context['favourite_data'] = Favourite.get_favourites(user)
		return context

	def __get_monthly_amounts(self, expenses):
		a_year_ago = datetime.date.today() - datetime.timedelta(days=365)
		queryset = Transaction.objects.filter(date__gte=a_year_ago, amount__lt=0)\
			.values_list('date__month')\
			.annotate(
				total_in_month=Cast(Abs(Sum(Case(
					When(in_sum=True, then='amount'),
					default=0
				))), FloatField()),
				total_out_month=Cast(Abs(Sum(Case(
					When(in_sum=False, then='amount'),
					default=0
				))), FloatField())
			)\
			.values('date__month', 'total_in_month', 'total_out_month')\
			.order_by('date__month')
		return queryset

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


class UpdateTagsView:
	def update_tags(self, form, transaction, user):
		tags = form.data.get('tag').split(",")
		for tag_name in tags:
			(tag, created) = Tag.objects.get_or_create(user=user, name=tag_name)
			TransactionTag.objects.get_or_create(transaction=transaction, tag=tag)


class ExpenseAddView(CreateView, UpdateTagsView, TransactionListView):
	model = Transaction
	form_class = ExpensesForm
	success_url = reverse_lazy('expenses')
	template_name = 'expenses/index.html'

	def get_form_kwargs(self):
		kwargs = super().get_form_kwargs()
		kwargs['user'] = self.request.user
		return kwargs

	def form_valid(self, form):
		instance = form.save(commit=False)
		instance.user_id = self.request.user.pk
		instance.save()
		if form.data.get('favourite'):
			Favourite.objects.get_or_create(transaction=instance)
		self.update_tags(form, instance, self.request.user)
		return redirect(reverse_lazy('expenses'))


class ExpenseDeleteView(DeleteView):
	model = Transaction
	success_url = reverse_lazy('expenses')

	def get(self, request, *args, **kwargs):
		return self.delete(request, *args, **kwargs)


class ExpenseView(UpdateView, UpdateTagsView, TransactionListView):
	model = Transaction
	form_class = ExpensesForm
	template_name = 'expenses/index.html'

	def get_form_kwargs(self):
		kwargs = super().get_form_kwargs()
		kwargs['user'] = self.request.user
		return kwargs

	def form_valid(self, form):
		# TODO VALIDATE THAT EXPENSE BELONGS TO USER
		instance = form.save()
		if form.data.get('favourite'):
			Favourite.objects.get_or_create(transaction=form.instance)
		self.update_tags(form, instance, self.request.user)
		return redirect(reverse_lazy('expenses'))

	def form_invalid(self, form):
		# TODO FIX PROBLEM WHEN ADDING DECIMALS
		print(form.errors)
		print(form.data['amount'])
		response = super().form_invalid(form)
		return response

	def get_success_url(self):
		# todo get month and year for expense, get order, redirect
		return reverse_lazy('expenses')

	def get_context_data(self, **kwargs):
		context = super().get_context_data(**kwargs)
		context['edit_slug'] = '/expenses/'
		context['filter_url_name'] = 'expenses'
		return context

	def __get_transaction_id(self):
		url = self.request.path
		groups = re.search(r'year/(\d+)/month/(\d+)/$', url)
		return groups.group(1)

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
