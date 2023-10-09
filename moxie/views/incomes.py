import datetime
import re
from dateutil.relativedelta import relativedelta
from django.views.generic import CreateView, UpdateView, DeleteView, ListView

from django.shortcuts import redirect
from django.utils.translation import gettext_lazy as _

from django.contrib.auth.mixins import LoginRequiredMixin

from moxie.forms import IncomesForm
from django.urls import reverse_lazy
from django_filters.views import FilterView
from django.db.models import Sum, FloatField, Case, When
from django.db.models.functions import Abs, Cast
from moxie.filters import IncomesFilter
from moxie.models import Transaction, Tag, TransactionTag, Favourite
from moxie.views.expenses import UserOwnerMixin


class IncomesListView(FilterView):
	filterset_class = IncomesFilter

	def get_queryset(self):
		start_date, end_date = self.__get_start_and_end_date()
		order_field = self.__get_order_field()

		queryset = super().get_queryset()
		queryset = queryset.filter(user=self.request.user)\
			.filter(amount__gte=0, date__lt=end_date, date__gte=start_date)\
			.order_by(order_field)

		return queryset

	def __get_order_field(self):
		return self.request.GET.get('order', '-date')

	def __get_start_and_end_date(self):
		year = self._get_active_year()
		if year:
			start_date = datetime.datetime.strptime(f"{year}-01-01", '%Y-%m-%d').date()
			end_date = datetime.datetime.strptime(f"{year}-12-31", '%Y-%m-%d').date()
		else:
			start_date = datetime.date.today().replace(day=1)
			end_date = datetime.date.today()
			end_date = end_date.replace(month=end_date.month + 1, day=1) - datetime.timedelta(days=1)
		return start_date, end_date

	def _get_active_year(self):
		url = self.request.path
		if 'year' in url:
			groups = re.search(r'year/(\d+)/$', url)
			if groups:
				year = groups.group(1)
			else:
				raise Exception(f"Year not found in url {url}")
		else:
			year = datetime.date.today().year
		return int(year)

	def get_filterset_kwargs(self, filterset_class):
		kwargs = super().get_filterset_kwargs(filterset_class)
		kwargs['user'] = self.request.user
		return kwargs


class CommonIncomesView:
	def _get_category_amounts(self, expenses):
		# todo filter by current parameters month
		today = datetime.date.today()
		month = self.request.GET.get('month', today.month)
		year = self.request.GET.get('year', today.year)
		return Transaction.objects.filter(amount__lt=0, date__month=month, date__year=year)\
			.values('category__name').order_by('category__name')\
			.annotate(total=Cast(Abs(Sum('amount')), FloatField()))


class IncomesView(LoginRequiredMixin, IncomesListView, ListView, CommonIncomesView):
	model = Transaction
	template_name = 'incomes/index.html'

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
		queryset = self.get_queryset()
		context['total_amount'] = queryset.aggregate(total_amount=Sum('amount')).get('total_amount')
		context['current_amount'] = queryset.exclude(in_sum=False).aggregate(total_amount=Sum('amount')).get('total_amount')
		context['edit_slug'] = '/incomes/'
		context['date_get'] = ''
		context['urls'] = ['incomes', 'expenses', 'stats', 'sheets', 'users']
		context['tags'] = Tag.get_tags(self.request.user)
		context['form'] = IncomesForm(self.request.user)
		context['category_amounts'] = self._get_category_amounts(queryset)
		year_incomes = [["Fecha", "Importe"]] + [[a[0], a[1]] for a in Transaction.get_year_incomes(self.request.user, expenses=False, incomes=True)]

		context['year_incomes'] = year_incomes
		year = self._get_active_year()
		context['year'] = year
		context['current_month_and_year'] = f"{year}"
		last_year = year - 1
		next_year = year + 1
		context['last_url'] = f"/incomes/year/{last_year}"
		context['next_url'] = f"/incomes/year/{next_year}"
		context['edit_url'] = reverse_lazy('incomes_add')
		context['filter_url_name'] = 'incomes'
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
	def update_tags(self, tags, expense, user):
		for tag_name in tags:
			(tag, created) = Tag.objects.get_or_create(user=user, name=tag_name)
			TransactionTag.objects.get_or_create(transaction=expense, tag=tag)


class IncomeAddView(LoginRequiredMixin, CreateView, UpdateTagsView, IncomesListView):
	model = Transaction
	form_class = IncomesForm
	success_url = reverse_lazy('incomes')
	template_name = 'incomes/index.html'

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
		return redirect(reverse_lazy('incomes'))


class IncomeDeleteView(LoginRequiredMixin, DeleteView, UserOwnerMixin):
	model = Transaction
	success_url = reverse_lazy('incomes')

	def get(self, request, *args, **kwargs):
		return self.delete(request, *args, **kwargs)


class IncomeView(LoginRequiredMixin, UpdateView, UpdateTagsView, IncomesListView, UserOwnerMixin, CommonIncomesView):
	model = Transaction
	form_class = IncomesForm
	template_name = 'incomes/index.html'

	def get_object(self, queryset=None):
		return Transaction.objects.get(pk=self.kwargs.get('pk'))

	def get_form_kwargs(self):
		kwargs = super().get_form_kwargs()
		kwargs['user'] = self.request.user
		return kwargs

	def form_valid(self, form):
		# TODO VALIDATE THAT EXPENSE BELONGS TO USER
		response = super().form_valid(form)
		if form.data.get('favourite'):
			Favourite.objects.get_or_create(transaction=form.instance)
		return response

	def form_invalid(self, form):
		# TODO FIX PROBLEM WHEN ADDING DECIMALS
		print(form.errors)
		print(form.data['amount'])
		self.object_list = self.get_queryset()
		filterset_class = self.get_filterset_class()
		self.filterset = self.get_filterset(filterset_class)
		response = super().form_invalid(form)
		return response

	def get_success_url(self):
		# todo get month and year for expense, get order, redirect
		return reverse_lazy('incomes')

	def get_context_data(self, **kwargs):
		context = super().get_context_data(**kwargs)
		context['edit_slug'] = '/incomes/'
		context['filter_url_name'] = 'incomes'
		year = self.object.date.year
		context['year'] = year

		# new
		queryset = self.get_queryset()
		context['total_amount'] = queryset.aggregate(total_amount=Sum('amount')).get('total_amount')
		context['current_amount'] = queryset.exclude(in_sum=False).aggregate(total_amount=Sum('amount')).get('total_amount')
		context['date_get'] = ''
		context['urls'] = ['incomes', 'expenses', 'stats', 'sheets', 'users']
		context['tags'] = Tag.get_tags(self.request.user)
		context['form'] = IncomesForm(self.request.user)
		context['category_amounts'] = self._get_category_amounts(queryset)
		year_incomes = [["Fecha", "Importe"]] + [[a[0], a[1]] for a in Transaction.get_year_incomes(self.request.user, expenses=False, incomes=True)]

		context['year_incomes'] = year_incomes
		context['current_month_and_year'] = f"{year}"
		last_year = year - 1
		next_year = year + 1
		context['last_url'] = f"/incomes/year/{last_year}"
		context['next_url'] = f"/incomes/year/{next_year}"
		context['edit_url'] = reverse_lazy('incomes_add')
		context['filter_url_name'] = 'incomes'
		return context

	def __get_transaction_id(self):
		url = self.request.path
		groups = re.search(r'year/(\d+)/month/(\d+)/$', url)
		return groups.group(1)
