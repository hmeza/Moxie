from django.db import models, transaction
from django.contrib.auth.models import User as DjangoUser
from django.db.models.functions import Cast, Concat, ExtractMonth, ExtractYear, Abs
from django.db.models import Sum, FloatField, Count, F, Q, Value, Avg, Case, When
import datetime
from django.utils.translation import gettext_lazy as _
import logging


logger = logging.getLogger('django')


class MoxieUser(DjangoUser):
    user = models.OneToOneField(DjangoUser, on_delete=models.CASCADE, null=True, related_name='%(class)s_resource_file')
    language = models.CharField(max_length=2, null=False, blank=False, default='es')


class Category(models.Model):
    EXPENSES = 1
    INCOMES = 2
    BOTH = 3

    CHOICE_TYPE = (
        (EXPENSES, _('Expenses'), ),
        (INCOMES, _('Incomes')),
        (BOTH, _('Both'))
    )

    user = models.ForeignKey(DjangoUser, blank=False, null=True, on_delete=models.CASCADE, default=None)
    parent = models.ForeignKey(
        "self", db_column='parent', blank=True, null=True, related_name='subcategories', on_delete=models.PROTECT,
        default=None
    )
    name = models.CharField(max_length=50, blank=False, null=False)
    description = models.CharField(max_length=200, blank=False, null=False)
    type = models.SmallIntegerField(choices=CHOICE_TYPE)
    order = models.SmallIntegerField(default=1)

    def __str__(self):
        if self.parent is None or self.parent.parent is None:
            return self.name
        else:
            return self.parent.name + " - " + self.name

    @staticmethod
    def get_categories_by_user(user, category_type=BOTH):
        return Category.objects \
            .filter(user=user.pk, parent__isnull=False, type=category_type)\
            .order_by('order', 'name') \
            .all()

    @property
    def type_name(self):
        if self.type == Category.EXPENSES:
            return _('Expense')
        elif self.type == Category.INCOMES:
            return _('Income')
        else:
            return _('Both')

    @staticmethod
    def get_categories_tree(user, type_filter=EXPENSES):
        queryset = Category.objects.select_related('parent')\
            .filter(user=user)
        if type_filter == Category.BOTH:
            queryset = queryset.filter(Q(type=Category.EXPENSES) | Q(type=Category.INCOMES) | Q(type=Category.BOTH))
        else:
            queryset = queryset.filter(Q(type=type_filter) | Q(type=Category.BOTH))
        queryset = queryset\
            .filter(Q(parent__isnull=False))\
            .annotate(cat_name=Concat(F('name'), Value('-'), F('parent__name')))\
            .order_by('order')
        return queryset

    def register_new_user_categories(self, user):
        root_category = Category.objects.get_or_create(
            user=user,
            name=_('Root category'),
            parent=None,
            description=_('Root category'),
            order=0,
            type=Category.BOTH
        )
        new_categories = [
            Category(
                user=user,
                name=_('Home'),
                parent=root_category,
                description=_('Home'),
                order=1,
                type=Category.EXPENSES
            ),
            Category(
                user=user,
                name=_('Fun'),
                parent=root_category,
                description=_('Going out, dinner outside, parties, fun...'),
                order=2,
                type=Category.EXPENSES
            ),
            Category(
                user=user,
                name=_('Technology'),
                parent=root_category,
                description=_('Technology'),
                order=3,
                type=Category.EXPENSES
            ),
            Category(
                user=user,
                name=_('Gifts'),
                parent=root_category,
                description=_("Christmas, Valentine's day, birthday..."),
                order=4,
                type=Category.BOTH
            ),
            Category(
                user=user,
                name=_('Clothing'),
                parent=root_category,
                description=_('Wear and dress'),
                order=5,
                type=Category.EXPENSES
            ),
            Category(
                user=user,
                name=_('Other'),
                parent=root_category,
                description=_('Non-classifiable transactions'),
                order=6,
                type=Category.BOTH
            ),
        ]
        Category.objects.bulk_create(new_categories)
        food_category = Category(
            user=user,
            name=_('Food'),
            parent=root_category, description=_('Food'),
            order=7,
            type=Category.EXPENSES
        ),
        new_categories = [
            Category(
                user=user,
                name=_('Home'),
                parent=food_category,
                description=_('Home food, grocery'),
                order=8,
                type=Category.EXPENSES
            ),
            Category(
                user=user,
                name=_('Away'),
                parent=food_category,
                description=_('Lunch and dinner out'),
                order=9,
                type=Category.EXPENSES
            ),
            Category(
                user=user,
                name=_('Coffee'),
                parent=food_category,
                description=_('Coffee outside, take away, some pastry maybe'),
                order=10,
                type=Category.EXPENSES
            ),
        ]
        Category.objects.bulk_create(new_categories)


class Budget(models.Model):
    user = models.ForeignKey(DjangoUser, blank=False, null=False, on_delete=models.PROTECT, related_name='budgets')
    category = models.ForeignKey(Category, on_delete=models.PROTECT, blank=False, null=False, db_column='category')
    amount = models.FloatField(default=0)
    date_created = models.DateTimeField(auto_now_add=True)
    date_modified = models.DateTimeField(auto_now=True)
    date_ended = models.DateTimeField(blank=True, null=True)

    @staticmethod
    def get_budget(user):
        return Budget.objects.select_related('category')\
            .filter(user=user, date_ended__isnull=True)\
            .order_by('category__type', 'category__order')

    @staticmethod
    def get_budget_for_month(user, year, month, expenses=False, incomes=False):
        queryset = Transaction.objects\
            .prefetch_related('user', 'user__budgets', 'category') \
            .filter(date__year=year, date__month=month, user=user)\
            .filter(user__budgets__user=user, user__budgets__date_ended__isnull=True, user__budgets__category=F('category')) \
            .values('category')\
            .annotate(category_group=Count('category'))\
            .annotate(transaction_total=Cast(Sum('amount'), FloatField()))\
            .values('transaction_total', 'category__name', 'user__budgets__amount', 'category_group', 'category__id')\
            .order_by('category')
        if expenses:
            queryset = queryset.filter(amount__lt=0)
        return queryset

    @staticmethod
    def getLastBudgetByCategoryId(category_id):
        return Budget.objects.filter(category=category_id, date_ended_isnull=True)

    @staticmethod
    def snapshot(user_id):
        close_date = datetime.datetime.now()
        with transaction.atomic():
            current_budget_data = Budget.get_budget(user_id)
            create_objects = []
            for budget in current_budget_data:  # type: Budget
                create_objects.append(Budget(
                    user_id=budget.user_id, category_id=budget.category_id, amount=budget.amount, date_created=close_date
                ))
                budget.date_ended = close_date
            Budget.objects.bulk_update(current_budget_data, ['date_ended'])
            Budget.objects.bulk_create(create_objects)

    @staticmethod
    def delete_budget_set(pk, user):
        budget = Budget.objects.get(pk=pk)
        start_date = budget.date_ended - datetime.timedelta(seconds=1)
        end_date = budget.date_ended + datetime.timedelta(seconds=1)
        return Budget.objects\
            .filter(user=user, date_ended__gte=start_date, date_ended__lte=end_date)\
            .delete()

    @staticmethod
    def closed_budgets(user):
        return Budget.objects.filter(user=user, date_ended__isnull=False).order_by('-date_created').all()


class SharedExpensesSheet(models.Model):
    DEFAULT_CURRENCY = 'eur'
    POUNDS = 'gbp'
    US_DOLLAR = 'usd'
    OTHER = 'nan'

    CURRENCIES = (
        (DEFAULT_CURRENCY, _('Euro')),
        (POUNDS, _('Pounds')),
        (US_DOLLAR, _('US Dollar')),
        (OTHER, _('Other'))
    )

    user = models.ForeignKey(DjangoUser, on_delete=models.CASCADE, related_name='shared_expenses_sheets')
    name = models.CharField(max_length=255, default='')
    unique_id = models.CharField(max_length=64)  # TODO change to UUIDField
    closed_at = models.DateTimeField(default=None, null=True, blank=True)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)
    currency = models.CharField(max_length=3, choices=CURRENCIES, default=DEFAULT_CURRENCY)
    change = models.DecimalField(max_digits=10, decimal_places=2, default=1)

    @staticmethod
    def get(user=None, user_id=None):
        assert user or user_id
        user_id = user_id if user_id else user.pk
        return SharedExpensesSheet.objects.get(user_id=user_id)

    def __str__(self):
        return self.name

    @property
    def total(self):
        when_change_currency = When(currency=F('sheet__currency'), then=F('amount')/F('sheet__change'))

        return self.expenses\
            .annotate(amount_converted=Case(when_change_currency, default=F('amount')))\
            .aggregate(sum=Sum('amount_converted'))['sum']


class SharedExpensesSheetUsers(models.Model):
    sheet = models.ForeignKey(SharedExpensesSheet, on_delete=models.CASCADE, related_name='users')
    user = models.ForeignKey(DjangoUser, on_delete=models.CASCADE, related_name='related_shared_expenses_sheets')
    email = models.EmailField()
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    def __str__(self):
        return self.user.username if self.user else self.email

    @property
    def sheet_expense(self):
        when_change_currency = When(currency=F('sheet__currency'), then=F('amount') / F('sheet__change'))

        result = SharedExpense.objects\
            .filter(user=self)\
            .annotate(amount_converted=Case(when_change_currency, default=F('amount')))\
            .aggregate(total=Sum('amount_converted'))

        return result['total'] if result['total'] else 0

    @property
    def difference(self):
        users = self.sheet.users.count()
        return self.sheet_expense - (self.sheet.total / users)


class SharedExpense(models.Model):
    sheet = models.ForeignKey(SharedExpensesSheet, on_delete=models.CASCADE, related_name='expenses')
    user = models.ForeignKey(SharedExpensesSheetUsers, on_delete=models.CASCADE, related_name='shared_expenses')
    amount = models.DecimalField(max_digits=10, decimal_places=2)
    note = models.CharField(max_length=255)
    date = models.DateTimeField()
    copied = models.BooleanField(default=False)
    currency = models.CharField(max_length=3, default=SharedExpensesSheet.DEFAULT_CURRENCY)

    @staticmethod
    def get_sheet_by_expense_id_and_user_id(shared_expense_id, user_id):
        sheet = SharedExpense.objects.select_related('sheet', 'user')\
            .filter(user__id=user_id, id=shared_expense_id)
        return sheet

    @property
    def sheet_user(self):
        return self.sheet.user


class Transaction(models.Model):
    YEARS_FOR_YEARLY_STATS = 4

    user = models.ForeignKey(DjangoUser, blank=False, null=False, on_delete=models.PROTECT, related_name='transactions')
    amount = models.DecimalField(max_digits=10, decimal_places=2, blank=False, null=False)
    category = models.ForeignKey(Category, on_delete=models.PROTECT, blank=False, null=False, db_column='category')
    note = models.CharField(max_length=255)
    date = models.DateTimeField(default=None)
    in_sum = models.BooleanField(blank=False, null=False)
    income_update = models.DateTimeField(auto_now=True)

    @staticmethod
    def totals(user, year=None):
        queryset = Transaction.objects.filter(user=user)
        if year:
            queryset = queryset.filter(date__year=year)
        queryset = queryset.values('category')\
            .annotate(category_count=Count('category'))\
            .annotate(
                category_sum=Cast(Sum(Abs('amount')), FloatField()),
                category_avg=Cast(Avg(Abs('amount')), FloatField())
            )
        return {c['category']: {'sum': c['category_sum'], 'avg': c['category_avg']} for c in queryset}

    @staticmethod
    def get_category_amounts(user, filter_date, get_params, year=None, month=None):
        if not year or not month:
            month = get_params.get('month', filter_date.month)
            year = get_params.get('year', filter_date.year)
        return Transaction.objects.filter(user=user, amount__lt=0, date__month=month, date__year=year) \
            .values('category__name').order_by('category__name') \
            .annotate(total=Cast(Abs(Sum('amount')), FloatField()))

    @staticmethod
    def get_yearly_stats_by_category(user, category_type=Category.EXPENSES, year=None, category=None):
        if not year:
            year = datetime.date.today().year
        transactions = Transaction.objects\
            .select_related('category')\
            .filter(user=user, date__year=year)
        if category_type == Category.EXPENSES:
            transactions = transactions.filter(amount__lt=0)
        elif category_type == Category.INCOMES:
            transactions = transactions.filter(amount__gte=0)
        if category:
            transactions = transactions.filter(category=category)
        transactions = transactions\
            .annotate(month=ExtractMonth('date'), year=ExtractYear('date'))\
            .values('month', 'year', 'category__name', 'category__id')\
            .annotate(category_group=Count('category__id'), category__month=Count('month'))\
            .annotate(total=Cast(Sum('amount'), FloatField()))\
            .order_by('category__id', 'month')
        return transactions

    @staticmethod
    def get_category_stats(user):
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

    @staticmethod
    def copy_from_shared_expense(shared_expense_id, category_id):
        with transaction.atomic():
            shared_expense = SharedExpense.objects.get(pk=shared_expense_id)
            # todo apply change to amount if shared_expense in other currency
            Transaction.objects.create(
                user=shared_expense.user.user,
                amount=-shared_expense.amount,
                note=shared_expense.note,
                date=shared_expense.date,
                in_sum=True,
                category_id=category_id
            )
            shared_expense.copied = True
            shared_expense.save()

    def used_tags(self):
        tags = self.tags.all()
        return {tag.tag_id: tag.tag.name for tag in tags}


class Tag(models.Model):
    transaction_tags = None
    existing_tags = None

    user = models.ForeignKey(DjangoUser, blank=False, null=False, on_delete=models.CASCADE)
    name = models.CharField(max_length=255, null=True, blank=True)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    def __str__(self):
        return self.name

    @staticmethod
    def get_tags(user=None):
        if not user:
            return []
        queryset = Tag.objects.filter(user=user)
        return Tag.__get_tags_from_query(queryset)

    def get_tags_for_transaction(self, transaction):
        queryset = Tag.objects.prefetch_related('transaction_tags')\
            .filter(transaction_tags__id_transaction=transaction)
        return self.__get_tags_from_query(queryset)

    @staticmethod
    def __get_tags_from_query(queryset):
        tags = {}
        for tag in queryset.all():
            tags[tag.id] = tag.name.replace("'", "\\'")
        return tags

    def get_tag(self, user_id=None, tag=None):
        return self.objects.filter(user=user_id, name=tag).first()

    @staticmethod
    def clean_tags(tag_list, user):
        tags_to_be_deleted = Tag.objects.filter(user=user).exclude(name__in=tag_list)
        for tag in tags_to_be_deleted:
            Tag.objects.filter(name=tag).delete()

    @staticmethod
    def create_new_tags(tag_list, user):
        tags_in_list = Tag.objects.filter(user=user, name__in=tag_list).values_list('name')
        tag_list_to_create = set(tag_list).difference(tags_in_list)
        new_tags = []
        for tag in tag_list_to_create:
            new_tags.append(Tag(user=user, name=tag))
        Tag.objects.bulk_create(new_tags)


class TransactionTag(models.Model):
    transaction = models.ForeignKey(
        Transaction, related_name='tags', on_delete=models.CASCADE
    )
    tag = models.ForeignKey(Tag, related_name='transactions', on_delete=models.CASCADE)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)


class Favourite(models.Model):
    transaction = models.ForeignKey(Transaction, on_delete=models.PROTECT, null=False, blank=False, default=None, related_name='favourite')
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    @staticmethod
    def get_favourites(user):
        queryset = Transaction.objects\
            .prefetch_related('favourite')\
            .filter(user=user, id=F('favourite__transaction'))\
            .annotate(favourite_amount=Cast('amount', FloatField()))\
            .values('id', 'favourite_amount', 'category', 'note', 'in_sum', 'tags')
        result = dict((obj['id'], obj) for obj in queryset)
        for key in result:
            result[key].update({
                'amount': result[key]['favourite_amount'],
                'in_sum': 1 if result[key]['in_sum'] else 0,
                'tags': result[key]['tags'] if result[key]['tags'] else ''
            })
        return result

    @staticmethod
    def get_for_config(user):
        queryset = Favourite.objects.select_related('transaction')\
            .prefetch_related('transaction__tags__tag')\
            .filter(transaction__user=user).all()
        return queryset
