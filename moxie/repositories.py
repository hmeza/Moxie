from django.db.models.functions import Cast, ExtractMonth, ExtractYear, Abs
from django.db.models import Sum, FloatField, Count, F, Q, Value, Case, When, DecimalField
from moxie.models import Transaction
import datetime


class TransactionRepository:
    @staticmethod
    def get_monthly_totals_from_a_period(user, start_date):
        queryset = Transaction.objects.filter(date__gte=start_date, amount__lt=0, user=user)\
            .values_list('date__month')\
            .annotate(
                total_in_month=Cast(Abs(Sum(Case(
                    When(in_sum=True, then='amount'),
                    default=(Value(0, output_field=DecimalField()))
                ), output_field=DecimalField()), output_field=FloatField()), output_field=FloatField()),
                total_out_month=Cast(Abs(Sum(Case(
                    When(in_sum=False, then='amount'),
                    default=(Value(0, output_field=DecimalField()))
                ), output_field=DecimalField()), output_field=FloatField()), output_field=FloatField())
            )\
            .values('date__month', 'total_in_month', 'total_out_month')\
            .order_by('date__month')
        return queryset


class IncomeRepository:
    @staticmethod
    def get_year_incomes(user, expenses=True, incomes=False):
        queryset = Transaction.objects.filter(user=user)
        if incomes and not expenses:
            queryset = queryset.filter(amount__gte=0)
        elif expenses and not incomes:
            queryset = queryset.filter(amount__lt=0)
        queryset = queryset\
            .values(year=ExtractYear('date'))\
            .filter(year__gt=1900)\
            .annotate(year_group=Count(F('year')))\
            .annotate(sum_amount=Cast(Sum('amount'), FloatField()))\
            .order_by('year').values_list('year', 'sum_amount')
        return queryset

    @staticmethod
    def get_year_incomes_with_category(user, expenses=True, incomes=False):
        queryset = Transaction.objects.filter(user=user)
        if incomes and not expenses:
            queryset = queryset.filter(amount__gte=0)
        elif expenses and not incomes:
            queryset = queryset.filter(amount__lt=0)
        current_year = datetime.date.today().year
        first_year = int(current_year) - Transaction.YEARS_FOR_YEARLY_STATS
        queryset = queryset\
            .values(year=ExtractYear('date'))\
            .filter(year__gte=first_year)\
            .annotate(year_group=Count(F('year')), category_group=Count(F('category')))\
            .annotate(sum_amount=Cast(Sum('amount'), FloatField()))\
            .order_by('category', 'year')\
            .values_list('year', 'category', 'category__name', 'sum_amount')

        positive_flow = {y: 0 for y in range(first_year, current_year + 1)}
        negative_flow = {y: 0 for y in range(first_year, current_year + 1)}
        grand_total = {y: 0 for y in range(first_year, current_year + 1)}

        incomes_by_year_and_category = {}
        current_category = None
        loop_year = first_year
        for value in queryset:
            if value[1] != current_category:
                if loop_year < current_year and current_category is not None:
                    IncomeRepository.__fill_empty_category_years(
                        current_category, incomes_by_year_and_category, loop_year, value, current_year
                    )
                current_category = value[1]
                incomes_by_year_and_category[current_category] = []
                loop_year = first_year
            if value[0] > loop_year:
                IncomeRepository.__fill_empty_category_years(
                    current_category, incomes_by_year_and_category, loop_year, value, value[0]
                )
                loop_year = value[0]
            incomes_by_year_and_category[current_category].append({
                'year': value[0],
                'category': value[1],
                'name': value[2],
                'amount': value[3]
            })
            if value[3] >= 0:
                positive_flow[value[0]] += value[3]
            else:
                negative_flow[value[0]] -= value[3]
            grand_total[value[0]] += value[3]
            loop_year += 1
        return incomes_by_year_and_category, positive_flow, negative_flow, grand_total

    @staticmethod
    def __fill_empty_category_years(current_category, incomes_by_year_and_category, loop_year, value, target_year):
        for i in range(loop_year, target_year):
            incomes_by_year_and_category[current_category].append({
                'year': loop_year,
                'category': value[1],
                'name': value[2],
                'amount': 0
            })


class ExpenseRepository:
    @staticmethod
    def get_month_expenses_data(user, year, category):
        queryset = Transaction.objects \
            .filter(amount__lt=0, in_sum=True, user=user, date__year__gte=year, category=category) \
            .annotate(month=ExtractMonth('date'), year=ExtractYear('date')) \
            .values('month', 'year') \
            .annotate(amount=Cast(Abs(Sum('amount')), FloatField())).order_by('year', 'month')

        if not queryset:
            today = datetime.date.today()
            queryset = [{
                'month': today.month,
                'year': today.year,
                'amount': 0
            }]

        return queryset
