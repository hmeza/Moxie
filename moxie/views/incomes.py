import datetime
import re
from dateutil.relativedelta import relativedelta
from django.views.generic import CreateView, UpdateView, DeleteView, ListView
from django.shortcuts import redirect
from django.contrib.auth import logout
from django.contrib.auth.mixins import LoginRequiredMixin
from moxie.forms import IncomesForm
from django.urls import reverse_lazy
from django_filters.views import FilterView
from django.db.models import Sum, FloatField, Case, When
from django.db.models.functions import Abs, Cast
from moxie.filters import IncomesFilter
from moxie.models import Transaction, Tag, Favourite
from moxie.views.common_classes import UpdateTagsView, ExportView


class IncomesListView(FilterView, ListView):
	model = Transaction
	filterset_class = IncomesFilter

	def get_queryset(self):
		order_field = self.__get_order_field()
		queryset = super().get_queryset()
		queryset = queryset.filter(user=self.request.user)\
			.filter(amount__gte=0)\
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


class IncomesView(LoginRequiredMixin, IncomesListView, ListView, CommonIncomesView, ExportView):
	model = Transaction
	template_name = 'incomes/index.html'

	def get(self, request, *args, **kwargs):
		response = super().get(request, *args, **kwargs)
		# todo this belongs to ExportView, refactor
		if request.GET.get('to_excel'):
			return self.download_csv()
		return response

	def __get_last_year_and_month(self, year, month):
		date = datetime.datetime.strptime(f"{year}-{month}-01", '%Y-%m-%d').date()
		date = date - relativedelta(months=1)
		return date.year, date.month

	def __get_next_year_and_month(self, year, month):
		date = datetime.datetime.strptime(f"{year}-{month}-01", '%Y-%m-%d').date()
		date = date + relativedelta(months=1)
		return date.year, date.month

	def get_filterset_kwargs(self, filterset_class):
		kwargs = super().get_filterset_kwargs(filterset_class)
		from django.http import QueryDict
		q = QueryDict('', mutable=True)
		if kwargs['data']:
			q.update(kwargs['data'])
		date_min, date_max = self._get_start_and_end_date(self.request.GET)
		q['date_min'] = date_min
		q['date_max'] = date_max
		if kwargs['data'] and kwargs['data'].get('amount__gte'):
			q['amount__gte'] = -int(kwargs['data']['amount__gte'])
		if kwargs['data'] and kwargs['data'].get('amount__lte'):
			q['amount__lte'] = -int(kwargs['data']['amount__lte'])
		kwargs['data'] = q
		return kwargs

	def _get_start_and_end_date(self, q):
		start_date, end_date = q.get('date_min'), q.get('date_max')

		if hasattr(self, "instance"):
			return self._get_start_and_end_date_by_date(dateobject=self.instance.date)
		elif start_date and end_date:
			return start_date, end_date
		return self._get_start_and_end_date_by_date(year=self.kwargs.get('year'))

	def _get_start_and_end_date_by_date(self, year=None, dateobject=None):
		if dateobject:
			year = dateobject.year
		if not year and not dateobject:
			year = datetime.datetime.today().year
		date_format = "%Y-%M-%d"
		start_date = datetime.datetime.strptime(f"{year}-01-01", date_format)
		end_date = datetime.datetime.strptime(f"{year}-12-31", date_format)
		return start_date.strftime(date_format), end_date.strftime(date_format)

	def get_context_data(self, **kwargs):
		context = super().get_context_data(**kwargs)
		queryset = self.object_list
		context['total_amount'] = queryset.aggregate(total_amount=Sum('amount')).get('total_amount')
		context['current_amount'] = queryset.exclude(in_sum=False).aggregate(total_amount=Sum('amount')).get('total_amount')
		context['edit_slug'] = '/incomes/'
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


class IncomeDeleteView(LoginRequiredMixin, DeleteView):
	model = Transaction
	success_url = reverse_lazy('incomes')

	def get_object(self, queryset=None):
		obj = super().get_object(queryset)
		if obj.user != self.request.user:
			logout(self.request)
			return None
		return obj

	def get(self, request, *args, **kwargs):
		return self.delete(request, *args, **kwargs)


class IncomeView(LoginRequiredMixin, UpdateView, UpdateTagsView, IncomesListView, CommonIncomesView, ExportView):
	model = Transaction
	form_class = IncomesForm
	template_name = 'incomes/index.html'

	def get(self, request, *args, **kwargs):
		response = super().get(request, *args, **kwargs)
		# todo this belongs to ExportView, refactor
		if request.GET.get('to_excel'):
			return self.download_csv()
		return response

	def get_object(self, queryset=None):
		if hasattr(self, 'instance'):
			return self.instance
		self.instance = Transaction.objects.get(pk=self.kwargs.get('pk'))
		if self.instance.user != self.request.user:
			logout(self.request)
		return self.instance

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
		context['urls'] = ['incomes', 'expenses', 'stats', 'sheets', 'users']
		context['tags'] = Tag.get_tags(self.request.user)
		context['category_amounts'] = self._get_category_amounts(queryset)
		year_incomes = [["Fecha", "Importe"]] + [[a[0], a[1]] for a in Transaction.get_year_incomes(self.request.user, expenses=False, incomes=True)]

		filterset_class = self.get_filterset_class()
		self.filterset = self.get_filterset(filterset_class)

		if not self.filterset.is_bound or self.filterset.is_valid() or not self.get_strict():
			self.object_list = self.filterset.qs
		else:
			self.object_list = self.filterset.queryset.none()

		context['object_list'] = self.filterset.qs

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

	def get_filterset_kwargs(self, filterset_class):
		kwargs = super().get_filterset_kwargs(filterset_class)
		from django.http import QueryDict
		q = QueryDict('', mutable=True)
		if kwargs['data']:
			q.update(kwargs['data'])
		date_min, date_max = self._get_start_and_end_date(self.request.GET)
		q['date_min'] = date_min
		q['date_max'] = date_max
		if kwargs['data'] and kwargs['data'].get('amount__gte'):
			q['amount__gte'] = -int(kwargs['data']['amount__gte'])
		if kwargs['data'] and kwargs['data'].get('amount__lte'):
			q['amount__lte'] = -int(kwargs['data']['amount__lte'])
		kwargs['data'] = q
		return kwargs

	def _get_start_and_end_date(self, q):
		start_date, end_date = q.get('date_min'), q.get('date_max')

		if hasattr(self, "instance"):
			return self._get_start_and_end_date_by_date(dateobject=self.instance.date)
		elif start_date and end_date:
			return start_date, end_date
		return self._get_start_and_end_date_by_date(year=self.kwargs.get('year'))

	def _get_start_and_end_date_by_date(self, year=None, dateobject=None):
		if dateobject:
			year = dateobject.year
		if not year and not dateobject:
			year = datetime.datetime.today().year
		date_format = "%Y-%M-%d"
		start_date = datetime.datetime.strptime(f"{year}-01-01", date_format)
		end_date = datetime.datetime.strptime(f"{year}-12-31", date_format)
		return start_date.strftime(date_format), end_date.strftime(date_format)