import datetime
import calendar
import re
import csv
from dateutil.relativedelta import relativedelta
from django.views.generic import CreateView, UpdateView, DeleteView, ListView
from django.shortcuts import redirect
from django.http import QueryDict
from django.http.response import HttpResponseRedirect, HttpResponse
from django.contrib.auth import logout
from django.contrib.auth.mixins import LoginRequiredMixin
from django.utils.translation import gettext_lazy as _
from moxie.forms import ExpensesForm
from django.urls import reverse_lazy
from django_filters.views import FilterView
from django.db.models import Sum, FloatField, Case, When
from django.db.models.functions import Abs, Cast
from moxie.filters import ExpensesFilter
from moxie.models import Transaction, Tag, Budget, TransactionTag, Favourite, Category


# TODO: order
class TransactionListView(FilterView, ListView):
    model = Transaction
    filterset_class = ExpensesFilter

    def get_queryset(self):
        order_field = self.__get_order_field()
        queryset = super().get_queryset()
        queryset = queryset.filter(user=self.request.user, amount__lt=0).order_by(order_field)

        return queryset

    def __get_order_field(self):
        return self.request.GET.get('order', '-date')

    def __get_start_and_end_date_using_date_object(self, date):
        start_date = date.replace(day=1)
        end_date = date
        end_date = end_date.replace(month=end_date.month + 1, day=1) - datetime.timedelta(days=1)
        return start_date, end_date

    def _get_start_and_end_date(self, q):
        start_date, end_date = q.get('date_min'), q.get('date_max')

        if hasattr(self, "instance"):
            return self.__get_start_and_end_date_using_date_object(self.instance.date)
        elif start_date and end_date:
            return start_date, end_date

        (year, month) = self._get_active_year_and_month()
        if year and month:
            start_date = datetime.datetime.strptime(f"{year}-{month}-01", '%Y-%m-%d').date()
            end_date = (start_date + datetime.timedelta(days=32)).replace(day=1)
            return start_date, end_date
        else:
            return self.__get_start_and_end_date_using_date_object(datetime.date.today())

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


class NextAndLastYearAndMonthCalculatorView:
    def _get_last_year_and_month(self, year, month):
        date = datetime.datetime.strptime(f"{year}-{month}-01", '%Y-%m-%d').date()
        date = date - relativedelta(months=1)
        return date.year, date.month

    def _get_next_year_and_month(self, year, month):
        date = datetime.datetime.strptime(f"{year}-{month}-01", '%Y-%m-%d').date()
        date = date + relativedelta(months=1)
        return date.year, date.month


class ExportView:
    def download_csv(self):
        queryset = self.filterset.queryset
        if not self.object_list:
            self.object_list = queryset
        model = queryset.model
        model_fields = model._meta.fields + model._meta.many_to_many
        field_names = [field.name for field in model_fields]

        response = HttpResponse(content_type='text/csv')
        response['Content-Disposition'] = 'attachment; filename="export.csv"'

        writer = csv.writer(response, delimiter=";")
        writer.writerow([_(field.name) for field in model_fields])
        for row in self.object_list:
            values = []
            for field in field_names:
                try:
                    value = getattr(row, str(field))
                    if callable(value):
                        try:
                            value = value() or ''
                        except:
                            value = 'Error retrieving value'
                    if value is None:
                        value = ''
                    values.append(value)
                except (Transaction.DoesNotExist, Category.DoesNotExist) as e:
                    # todo use logger
                    # print(e)
                    ...
            writer.writerow(values)
        return response


class CommonExpensesView:
    def _get_monthly_amounts(self, expenses):
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


class ExpensesView(LoginRequiredMixin, TransactionListView, ListView, NextAndLastYearAndMonthCalculatorView, CommonExpensesView, ExportView):
    model = Transaction
    template_name = 'expenses/index.html'

    def get(self, request, *args, **kwargs):
        response = super().get(request, *args, **kwargs)
        # todo this belongs to ExportView, refactor
        if request.GET.get('to_excel'):
            return self.download_csv()
        return response

    def get_filterset_kwargs(self, filterset_class):
        kwargs = super().get_filterset_kwargs(filterset_class)
        from django.http import QueryDict
        q = QueryDict('', mutable=True)
        if kwargs['data']:
            q.update(kwargs['data'])
        date_min, date_max = self._get_start_and_end_date(q)
        q['date_min'] = date_min
        q['date_max'] = date_max
        if kwargs['data'] and kwargs['data'].get('amount__gte'):
            q['amount__gte'] = -int(kwargs['data']['amount__gte'])
        if kwargs['data'] and kwargs['data'].get('amount__lte'):
            q['amount__lte'] = -int(kwargs['data']['amount__lte'])
        kwargs['data'] = q
        return kwargs

    def get_context_data(self, **kwargs):
        context = super().get_context_data(**kwargs)
        user = self.request.user
        queryset = self.object_list
        context['total_amount'] = queryset.aggregate(total_amount=Sum('amount')).get('total_amount')
        context['current_amount'] = queryset.exclude(in_sum=False).aggregate(total_amount=Sum('amount')).get('total_amount')
        context['tags'] = Tag.get_tags(user)
        context['used_tag_list'] = Tag.get_used_tags(user)
        context['form'] = ExpensesForm(user)
        year, month = self._get_active_year_and_month()
        category_amounts = Transaction.get_category_amounts(user, datetime.date.today(), self.request.GET, year, month)
        context['category_amounts'] = category_amounts
        context['pie_data'] = [list(a.values()) for a in category_amounts]
        month_expenses = [list(a.values()) for a in self._get_monthly_amounts(queryset)]
        month_expenses_list = [["Month", "En la suma total", "Fuera del total"]]
        for expense in month_expenses:
            month_name = datetime.datetime.strptime("2023-{}-01".format(expense[0]), "%Y-%m-%d").strftime("%m")
            month_expenses_list.append([month_name, expense[1], expense[2]])
        context['month_expenses'] = month_expenses_list
        budget = Budget.get_budget_for_month(user, year, month)
        context['budget'] = budget
        context['budget_total'] = budget.aggregate(sum=Sum('user__budgets__amount')).get('sum')
        context['budget_total_spent'] = budget.aggregate(sum=Sum('transaction_total')).get('sum')
        context['year'] = year
        context['month'] = month
        context['current_month_and_year'] = "{} {}".format(calendar.month_name[int(month)][:3], year)
        last_year, last_month = self._get_last_year_and_month(year, month)
        next_year, next_month = self._get_next_year_and_month(year, month)
        context['last_url'] = f"/expenses/year/{last_year}/month/{last_month}"
        context['next_url'] = f"/expenses/year/{next_year}/month/{next_month}"
        context['edit_url'] = reverse_lazy('expenses_add')
        context['filter_url_name'] = 'expenses'
        context['favourite_data'] = Favourite.get_favourites(user)
        return context

    # todo check if order and order by works properly
    # todo check if results are correct
    # todo check this st_expense needed in the frontend
    # /**
    #  * Shows the expenses view.
    #  * Receives call from export to excel too.
    #  */
    # public function indexAction() {
    #     $st_params = $this->getParameters();
    #
    #     $st_list = $this->expenses->get($_SESSION['user_id'],Categories::EXPENSES, $st_params);
    #
    #     // order + switch order by
    #     if (isset($st_params['o'])) {
    #         $st_params['o'] = ($st_params['o'][0] == '-')
    #                 ? substr($st_params['o'], 1)
    #                 : "-".$st_params['o'];
    #     }
    #
    #     if($this->getRequest()->getParam('to_excel') == true) {
    #         $this->exportToExcel($st_list);
    #     }


class UpdateTagsView:
    def update_tags(self, form, transaction, user):
        tags = form.data.get('tag').split(",")
        for tag_name in tags:
            (tag, created) = Tag.objects.get_or_create(user=user, name=tag_name)
            TransactionTag.objects.get_or_create(transaction=transaction, tag=tag)


class ExpenseAddView(LoginRequiredMixin, CreateView, UpdateTagsView, TransactionListView):
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


class ExpenseDeleteView(LoginRequiredMixin, DeleteView):
    model = Transaction
    success_url = reverse_lazy('expenses')

    def get_object(self, queryset=None):
        obj = super().get_object(queryset)
        if obj.user != self.request.user:
            logout(self.request)
            return None
        return obj

    def get(self, request, *args, **kwargs):
        return self.delete(request, *args, **kwargs)


class ExpenseView(LoginRequiredMixin, UpdateView, UpdateTagsView, TransactionListView,
                  NextAndLastYearAndMonthCalculatorView, CommonExpensesView, ExportView):
    model = Transaction
    form_class = ExpensesForm
    template_name = 'expenses/index.html'

    def get(self, request, *args, **kwargs):
        response = super().get(request, *args, **kwargs)
        # todo this belongs to ExportView, refactor
        if request.GET.get('to_excel'):
            self.set_object_list({})
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

    def get_filterset_kwargs(self, filterset_class):
        kwargs = super().get_filterset_kwargs(filterset_class)
        q = QueryDict('', mutable=True)
        if kwargs['data']:
            q.update(kwargs['data'])
        date_min, date_max = self._get_start_and_end_date(q)
        q['date_min'] = date_min
        q['date_max'] = date_max
        kwargs['data'] = q
        return kwargs

    def form_valid(self, form):
        instance = form.save()
        if form.data.get('favourite'):
            Favourite.objects.get_or_create(transaction=form.instance)
        self.update_tags(form, instance, self.request.user)
        return redirect(reverse_lazy('expenses'))

    def form_invalid(self, form):
        # TODO FIX PROBLEM WHEN FORM IS INVALID
        self.object_list = self.get_queryset()
        filterset_class = self.get_filterset_class()
        self.filterset = self.get_filterset(filterset_class)
        response = super().form_invalid(form)
        return HttpResponseRedirect(reverse_lazy('expenses_edit', kwargs={'pk': self.object.pk}))

    def get_success_url(self):
        return reverse_lazy('expenses')

    def get_context_data(self, **kwargs):
        context = super().get_context_data(**kwargs)
        context['edit_slug'] = '/expenses/'
        context['filter_url_name'] = 'expenses'
        year = self.object.date.year
        month = self.object.date.month
        context['year'] = year
        context['month'] = month
        context['current_month_and_year'] = "{} {}".format(calendar.month_name[int(month)][:3], year)
        last_year, last_month = self._get_last_year_and_month(year, month)
        next_year, next_month = self._get_next_year_and_month(year, month)
        context['last_url'] = f"/expenses/year/{last_year}/month/{last_month}"
        context['next_url'] = f"/expenses/year/{next_year}/month/{next_month}"

        self.set_object_list(context)

        # new
        user = self.request.user
        queryset = self.object_list
        context['total_amount'] = queryset.aggregate(total_amount=Sum('amount')).get('total_amount')
        context['current_amount'] = queryset.exclude(in_sum=False).aggregate(total_amount=Sum('amount')).get('total_amount')
        context['tags'] = Tag.get_tags(user)
        context['used_tag_list'] = Tag.get_used_tags(user)

        category_amounts = Transaction.get_category_amounts(
            user, datetime.date.today(), self.request.GET, year, month
        )
        context['category_amounts'] = category_amounts
        context['pie_data'] = [list(a.values()) for a in category_amounts]
        month_expenses = [list(a.values()) for a in self._get_monthly_amounts(queryset)]
        month_expenses_list = [["Month", "En la suma total", "Fuera del total"]]
        for expense in month_expenses:
            month_name = datetime.datetime.strptime("2023-{}-01".format(expense[0]), "%Y-%m-%d").strftime("%m")
            month_expenses_list.append([month_name, expense[1], expense[2]])
        context['month_expenses'] = month_expenses_list
        budget = Budget.get_budget_for_month(user, year, month)
        context['budget'] = budget
        context['budget_total'] = budget.aggregate(sum=Sum('user__budgets__amount')).get('sum')
        context['budget_total_spent'] = budget.aggregate(sum=Sum('transaction_total')).get('sum')
        context['filter_url_name'] = 'expenses'
        return context

    def set_object_list(self, context):
        filterset_class = self.get_filterset_class()
        self.filterset = self.get_filterset(filterset_class)
        if not self.filterset.is_bound or self.filterset.is_valid() or not self.get_strict():
            self.object_list = self.filterset.qs
        else:
            self.object_list = self.filterset.queryset.none()
        context['object_list'] = self.filterset.qs

    def get_filterset_kwargs(self, filterset_class):
        kwargs = super().get_filterset_kwargs(filterset_class)
        from django.http import QueryDict
        q = QueryDict('', mutable=True)
        if kwargs['data']:
            q.update(kwargs['data'])
        date_min, date_max = self._get_start_and_end_date(q)
        q['date_min'] = date_min
        q['date_max'] = date_max
        if kwargs['data'] and kwargs['data'].get('amount__gte'):
            q['amount__gte'] = -int(kwargs['data']['amount__gte'])
        if kwargs['data'] and kwargs['data'].get('amount__lte'):
            q['amount__lte'] = -int(kwargs['data']['amount__lte'])
        kwargs['data'] = q
        return kwargs