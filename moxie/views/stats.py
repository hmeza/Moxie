import datetime
import calendar
import re
from dateutil.relativedelta import relativedelta
from django.views.generic import TemplateView

from django.shortcuts import redirect
from django.utils.translation import gettext_lazy as _

from moxie.forms import IncomesForm
from django.urls import reverse_lazy
from django_filters.views import FilterView
from django.db.models import Sum, FloatField, Case, When
from django.db.models.functions import Abs, Cast, ExtractMonth, ExtractYear
from moxie.filters import IncomesFilter
from moxie.models import Transaction, Tag, Budget, TransactionTag, Favourite, Category


class StatsView(TemplateView):
	template_name = 'stats/index.html'

	def get_context_data(self, **kwargs):
		context = super().get_context_data(**kwargs)
		user = self.request.user

		if 'year' in self.request.path:
			year = int(re.findall(r'/year/(\d{4})', self.request.path)[0])
		else:
			year = datetime.date.today().year

		income_categories = Category.get_categories_tree(user, Category.INCOMES)
		incomes = None
		for category in income_categories:
			new_incomes = Transaction.get_yearly_stats_by_category(user, category_type=Category.INCOMES, year=year, category=category)
			if incomes:
				incomes += list(new_incomes)
			else:
				incomes = list(new_incomes)

		incomes = self.__prepare_transactions_to_display(
			incomes,
			is_expense=False
		)
		context['incomes'] = incomes

		expense_categories = Category.get_categories_tree(user, Category.EXPENSES)
		expenses = None
		for category in expense_categories:
			new_expenses = Transaction.get_yearly_stats_by_category(user, category_type=Category.EXPENSES, year=year, category=category)
			if expenses:
				expenses += list(new_expenses)
			else:
				expenses = list(new_expenses)

		expenses = self.__prepare_transactions_to_display(expenses)
		context['expenses'] = expenses
		context['budget_header'] = self.__initialize_rows(year)

		income_totals = incomes[-1] if incomes else {i: {'title': 0, 'link': ''} for i in range(0, 13)}
		expense_totals = expenses[-1] if expenses else {i: {'title': 0, 'link': ''} for i in range(0, 13)}
		totals = [{'title': _('BALANCE (Income - expense)'), 'link': ''}]
		for i in range(1, 13):
			totals.append({'title': float(income_totals[i]['title']) + float(expense_totals[i]['title']), 'link': ''})
		context['totals'] = totals

		context['yearly'], context['yearly_positive_flow'], context['yearly_negative_flow'], context['yearly_total'] = Transaction.get_year_incomes_with_category(user, expenses=True, incomes=True)

		context['year'] = int(year)

		today = datetime.date.today()
		context['yearly_header'] = [''] + [str(y) for y in range(today.year - Transaction.YEARS_FOR_YEARLY_STATS, today.year + 1)]
		context['trends'] = self._get_trends(Category.get_categories_tree(user))
		context['stats'] = Transaction.get_category_stats(user)
		return context

	def get_link(self, is_expense, year, month):
		url_name = 'expenses_with_parameters' if is_expense else 'incomes_with_parameters'
		kwargs = {'year': year}
		if is_expense:
			kwargs['month'] = month
		return reverse_lazy(url_name, kwargs=kwargs)

	def __prepare_transactions_to_display(self, transaction_list, is_expense=True):
		category = None
		current_month = 1
		current_row = None
		try:
			first_row = transaction_list[0]
		except:
			return
		year = first_row.get('year')
		rows = []
		totals = {i: 0 for i in range(1, 13)}
		for row in transaction_list:
			if row.get('category__id') != category or current_month == 13:
				if category is not None and current_month < 13:
					for i in range(current_month, 13):
						current_row.append({'title': 0, 'link': self.get_link(is_expense, year, i)})
				if category is not None:
					rows.append(current_row)
				category = row.get('category__id')
				current_row = [{'title': row.get('category__name'), 'link': self.get_link(is_expense, year, row.get('month'))}]
				current_month = 1
			if row.get('month') != current_month:
				for i in range(current_month, row.get('month')):
					current_row.append({'title': 0, 'link': self.get_link(is_expense, year, i)})
			total = row.get('total')
			month = row.get('month')
			current_row.append({'title': total, 'link': self.get_link(is_expense, year, row.get('month'))})
			totals[month] += total
			current_month = row.get('month') + 1
		if current_month < 13:
			for i in range(current_month, 13):
				current_row.append({'title': 0, 'link': self.get_link(is_expense, year, i)})

		rows.append(current_row)

		totals_translation = _('TOTAL EXPENSES') if is_expense else _('TOTAL INCOMES')
		rows.append([{'title': totals_translation, 'link': ''}] + [{'title': i, 'link': ''} for k, i in totals.items()])

		return rows

	def __initialize_rows(self, year):
		months = []
		for i in range(1, 13):
			month_obj = datetime.datetime.strptime(str(i), "%m")
			month_str = month_obj.strftime("%b")
			month = month_obj.month
			months.append({'title': month_str, 'link': self.get_link(True, year, month)})
		rows = [{'title': '', 'link': ''}] + months
		return rows

	def _get_trends(self, expenses_categories):
		trends = {}
		for category in expenses_categories:
			trend = self.__get_month_expenses_data(self.request.user, 1980, category)
			trend_list = [[str(a.get('month'))+"/"+str(a.get('year')), a.get('amount')] for a in trend]
			trends[category.pk] = {
				'id': category.pk,
				'name': str(category),
				'data': [["Mes/año", str(category)]] + trend_list
			}
		return trends

	def __get_month_expenses_data(self, user, year, category):
		queryset = Transaction.objects\
			.filter(amount__lt=0, in_sum=True, user=user, date__year__gte=year, category=category)\
			.annotate(month=ExtractMonth('date'), year=ExtractYear('date'))\
			.values('month', 'year')\
			.annotate(amount=Cast(Abs(Sum('amount')), FloatField())).order_by('year', 'month')

		if not queryset:
			today = datetime.date.today()
			queryset = [{
				'month': 	today.month,
				'year': today.year,
				'amount': 0
			}]

		return queryset


# 	/**
# 	 * Print detailed stats from user expenses and incomes.
# 	 * @todo	Use a group by to retrieve data and match with array
# 	 */
# 	public function indexAction() {
# 	    global $st_lang;
#
# 		$data = $this->getIncomeStatsByCategory();
# 		// Get all categories, expenses and incomes from current year
# 		$expenses = array();
# 		$incomes = array();
# 		$st_params = $this->getRequest()->getParams();
# 		for($month = 1; $month <= 12; $month++) {
# 			$current_date = $year.'-'.str_pad($month, 2, '0', STR_PAD_LEFT).'-01';
# 			$st_params['date_min'] = $current_date;
# 			$st_params['date_max'] = $st_params['date_max'] = date("Y-m-t", strtotime($current_date));
# 			$expenses[$month] = $this->expenses->get($_SESSION['user_id'], Categories::EXPENSES, $st_params)->toArray();
# 			$incomes[$month] = $this->incomes->get($_SESSION['user_id'],Categories::INCOMES, $st_params)->toArray();
# 		}
#
# 		// get categories and order strings
# 		$st_expenses = $this->categories->getCategoriesForView(Categories::EXPENSES);
# 		$st_incomes = $this->categories->getCategoriesForView(Categories::INCOMES);
# 		asort($st_expenses);
# 		asort($st_incomes);
#
# 		// Check if there is a category on expenses that does not exist on categories, add "No category"
#         list($st_expenses, $st_incomes, $expenses, $incomes) = $this->checkNoCategory($st_yearly, $st_expenses, $st_incomes, $st_lang, $expenses, $incomes);
#
# 		$this->view->assign('budget_expenses', $st_expenses);
# 		$this->view->assign('budget_incomes', $st_incomes);
# 		$this->view->assign('expenses', $expenses);
# 		$this->view->assign('incomes', $incomes);
# 		$this->view->assign('data', $data);
# 	}
#
# 	private function getIncomeStatsByCategory() {
# 		$incomeStatsByCategory = $this->categories->getCategoriesForView(Categories::BOTH);
# 		$data = array();
# 		foreach ($incomeStatsByCategory as $key => $value) {
# 			$data[$key]['index'] = $key;
# 			$data[$key]['name'] = $value;
#
# 			$st_data = $this->expenses->getSum($_SESSION['user_id'], $key);
#
# 			$data[$key]['sumtotal'] = $st_data['sum'];
#
# 			$st_data = $this->expenses->getStats($_SESSION['user_id'], $key);
#
# 			$data[$key]['sumyear'] = $st_data['sum'];
# 			$data[$key]['avgyear'] = $st_data['avg'];
# 		}
# 		return $data;
# 	}
#
#
#     /**
#      * @param $st_yearly
#      * @param $st_expenses
#      * @param $st_incomes
#      * @param $st_lang
#      * @param $expenses
#      * @param $incomes
#      * @return array
#      */
#     private function checkNoCategory($st_yearly, $st_expenses, $st_incomes, $st_lang, $expenses, $incomes)
#     {
#         $empty_categories = array();
#         foreach ($st_yearly as $st_year) {
#             foreach ($st_year as $value) {
#                 $cat = $value['category'];
#                 if (!array_key_exists($cat, $st_expenses + $st_incomes)) {
#                     $st_expenses[$cat] = $st_lang['empty_category'];
#                     $st_incomes[$cat] = $st_lang['empty_category'];
#                     $empty_categories[] = $cat;
#                 }
#             }
#         }
#         if (!empty($empty_categories)) {
#             foreach ($expenses as $month => $data) {
#                 foreach ($data as $key => $expense) {
#                     if (empty($expense['category'])) {
#                         $expenses[$month][$key]['category'] = $empty_categories[0];
#                         $expenses[$month][$key]['name'] = $st_lang['empty_category'];
#                         $expenses[$month][$key]['description'] = $st_lang['empty_category'];
#                     }
#                 }
#             }
#             foreach ($incomes as $month => $data) {
#                 foreach ($data as $key => $income) {
#                     if (empty($income['category'])) {
#                         $incomes[$month][$key]['category'] = $empty_categories[0];
#                         $incomes[$month][$key]['name'] = $st_lang['empty_category'];
#                         $incomes[$month][$key]['description'] = $st_lang['empty_category'];
#                     }
#                 }
#             }
#         }
#         return array($st_expenses, $st_incomes, $expenses, $incomes);
#     }
# }