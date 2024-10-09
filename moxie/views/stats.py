import datetime
import re
from django.views.generic import TemplateView
from django.utils.translation import gettext_lazy as _
from django.urls import reverse_lazy
from moxie.models import Transaction, Category
from moxie.repositories import ExpenseRepository, IncomeRepository


class StatsView(TemplateView):
	template_name = 'stats/index.html'

	def get_context_data(self, **kwargs):
		context = super().get_context_data(**kwargs)
		user = self.request.user

		today = datetime.date.today()
		year = self.__get_year(today)

		incomes = self.__get_incomes(user, year)
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
		context['yearly'], context['yearly_positive_flow'], context['yearly_negative_flow'], context['yearly_total'] = IncomeRepository.get_year_incomes_with_category(user, expenses=True, incomes=True)
		context['year'] = int(year)
		context['yearly_header'] = [''] + [str(y) for y in range(today.year - Transaction.YEARS_FOR_YEARLY_STATS, today.year + 1)]
		context['trends'] = self._get_trends(Category.get_categories_tree(user))
		context['stats'] = Transaction.get_category_stats(user)
		return context

	def __get_incomes(self, user, year):
		income_categories = Category.get_categories_tree(user, Category.INCOMES)
		incomes = None
		for category in income_categories:
			new_incomes = Transaction.get_yearly_stats_by_category(
				user, category_type=Category.INCOMES, year=year, category=category
			)
			if incomes:
				incomes += list(new_incomes)
			else:
				incomes = list(new_incomes)
		incomes = self.__prepare_transactions_to_display(
			incomes,
			is_expense=False
		)
		return incomes

	def __get_year(self, today):
		if 'year' in self.request.path:
			year = int(re.findall(r'/year/(\d{4})', self.request.path)[0])
		else:
			year = today.year
		return year

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
		rows.append([{'title': totals_translation, 'link': ''}] + [{'title': i, 'link': self.get_link(is_expense, year, k)} for k, i in totals.items()])

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
			trend = ExpenseRepository.get_month_expenses_data(self.request.user, 1980, category)
			trend_list = [[str(a.get('month'))+"/"+str(a.get('year')), a.get('amount')] for a in trend]
			trends[category.pk] = {
				'id': category.pk,
				'name': str(category),
				'data': [["Mes/año", str(category)]] + trend_list
			}
		return trends
