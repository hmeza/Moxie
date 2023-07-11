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
		# presupuesto
		context['budget_expenses'] = []
		context['budget_incomes'] = []
		# anual
		context['yearly'] = Transaction.get_year_incomes_with_category(user, expenses=True, incomes=True)
		today = datetime.date.today()
		context['yearly_header'] = [''] + [y for y in range(today.year - 5, today.year)]
		context['trends'] = self._get_trends(Category.get_categories_tree(user))
		context['stats'] = self.__get_stats(user)
		return context

	def __get_stats(self, user):
		today = datetime.date.today()
		categories = Category.get_categories_tree(user, type_filter=Category.BOTH)
		totals = Transaction.totals(user)
		totals_this_year = Transaction.totals(user, year=today.year)
		stats = {}
		for category in categories:
			pk = category.pk
			stats[pk] = {
				'category': str(category),
				'total': totals.get(pk, {}).get('sum', 0),
				'total_this_year': totals_this_year.get(pk, {}).get('sum', 0),
				'avg_this_year': totals_this_year.get(pk, {}).get('avg', 0),
				'avg': totals.get(pk, {}).get('avg', 0)
			}
		return stats

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