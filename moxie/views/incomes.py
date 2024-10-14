import datetime
import re
from django.views.generic import CreateView, UpdateView, DeleteView, ListView
from django.shortcuts import redirect
from django.contrib.auth import logout
from django.contrib.auth.mixins import LoginRequiredMixin
from moxie.forms import IncomesForm
from django.urls import reverse_lazy
from django_filters.views import FilterView
from moxie.filters import IncomesFilter
from moxie.models import Transaction, Tag, Favourite
from moxie.repositories import IncomeRepository, TransactionRepository
from moxie.views.common_classes import UpdateTagsView, ExportView, TransactionView
from django.http import QueryDict


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

	def _get_active_year(self):
		if hasattr(self, 'object'):
			return self.object.date.year
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

	def get_filterset_kwargs(self, filterset_class):
		kwargs = super().get_filterset_kwargs(filterset_class)
		kwargs['user'] = self.request.user
		q = QueryDict('', mutable=True)
		if kwargs['data']:
			q.update(kwargs['data'])
		date_min, date_max = self._get_start_and_end_date(self.request.GET)
		q['date_min'] = date_min
		q['date_max'] = date_max
		if kwargs['data'] and kwargs['data'].get('amount__gte'):
			q['amount__gte'] = float(kwargs['data']['amount__gte'])
		if kwargs['data'] and kwargs['data'].get('amount__lte'):
			q['amount__lte'] = float(kwargs['data']['amount__lte'])
		kwargs['data'] = q
		return kwargs

	def _get_common_context_data(self, context):
		year_incomes = [["Fecha", "Importe"]] + [[a[0], a[1]] for a in IncomeRepository.get_year_incomes(self.request.user, expenses=False, incomes=True)]
		year = self._get_active_year()
		last_year = year - 1
		next_year = year + 1
		context.update({
			'edit_slug': '/incomes/',
			'urls': ['incomes', 'expenses', 'stats', 'sheets', 'users'],
			'tags': Tag.get_tags(self.request.user),
			'year_incomes': year_incomes,
			'year': year,
			'current_month_and_year': f"{year}",
			'last_url': f"/incomes/year/{last_year}",
			'next_url': f"/incomes/year/{next_year}",
			'edit_url': reverse_lazy('incomes_add'),
			'filter_url_name': 'incomes',
		})

		if not self.filterset.is_bound or self.filterset.is_valid() or not self.get_strict():
			self.object_list = self.filterset.qs
		else:
			self.object_list = self.filterset.queryset.none()

		queryset = self.filterset.qs
		context['object_list'] = queryset
		context['total_amount'] = TransactionRepository.get_total_amount(queryset)
		context['current_amount'] = TransactionRepository.get_current_amount(queryset)
		context['grouped_object_list'] = self._get_grouped_object_list(context['object_list'])


class IncomesView(LoginRequiredMixin, IncomesListView, ListView, ExportView, TransactionView):
	model = Transaction
	template_name = 'incomes/index.html'

	def get(self, request, *args, **kwargs):
		response = super().get(request, *args, **kwargs)
		if request.GET.get('to_excel'):
			return self.download_csv()
		return response

	def get_context_data(self, **kwargs):
		context = super().get_context_data(**kwargs)
		self._get_common_context_data(context)
		context['form'] = IncomesForm(self.request.user)
		return context


class IncomeAddView(IncomesListView, LoginRequiredMixin, CreateView, UpdateTagsView):
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


class IncomeView(LoginRequiredMixin, UpdateView, UpdateTagsView, IncomesListView, ExportView, TransactionView):
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
		setattr(self, 'instance', Transaction.objects.get(pk=self.kwargs.get('pk')))
		if self.instance.user != self.request.user:
			logout(self.request)
		return self.instance

	def get_form_kwargs(self):
		kwargs = super().get_form_kwargs()
		kwargs['user'] = self.request.user
		return kwargs

	def form_valid(self, form):
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
		self._get_common_context_data(context)
		return context

	def __get_transaction_id(self):
		url = self.request.path
		groups = re.search(r'year/(\d+)/month/(\d+)/$', url)
		return groups.group(1)
