from django.db import models, transaction
from django.contrib.auth.models import User, AbstractUser
from django.db.models.functions import Cast, Concat, ExtractMonth, ExtractYear, Abs
from django.db.models import Sum, FloatField, Count, F, Q, Value, Avg
import datetime


from django.contrib.auth.base_user import BaseUserManager
from django.utils.translation import gettext_lazy as _
from django.utils import timezone


# class CustomUserManager(BaseUserManager):
#     """
#     Custom user model manager where email is the unique identifiers
#     for authentication instead of usernames.
#     """
#     def create_user(self, email, password, **extra_fields):
#         """
#         Create and save a user with the given email and password.
#         """
#         if not email:
#             raise ValueError(_("The Email must be set"))
#         email = self.normalize_email(email)
#         user = self.model(email=email, **extra_fields)
#         user.set_password(password)
#         user.save()
#         return user
#
#     def create_superuser(self, email, password, **extra_fields):
#         """
#         Create and save a SuperUser with the given email and password.
#         """
#         extra_fields.setdefault("is_staff", True)
#         extra_fields.setdefault("is_superuser", True)
#         extra_fields.setdefault("is_active", True)
#
#         if extra_fields.get("is_staff") is not True:
#             raise ValueError(_("Superuser must have is_staff=True."))
#         if extra_fields.get("is_superuser") is not True:
#             raise ValueError(_("Superuser must have is_superuser=True."))
#         return self.create_user(email, password, **extra_fields)


# class User(AbstractUser):
#     id = models.IntegerField(primary_key=True, auto_created=True)
#     username = models.CharField(max_length=12, null=False, blank=False, default='', unique=True)
#     password = models.CharField(max_length=50, null=False, blank=False, default='')
#     email = models.CharField(max_length=255, null=False, blank=False, default='')
#     language = models.CharField(max_length=2, null=False, blank=False, default='es')
#     created_at = models.DateTimeField(auto_now_add=True)
#     updated_at = models.DateTimeField(auto_now=True)
#     last_login = models.DateTimeField(auto_now_add=True)
#     is_superuser = models.BooleanField(default=False)
#
#     def __str__(self):
#         return self.username
#
#     USERNAME_FIELD = "username"
#     REQUIRED_FIELDS = []
#
#     objects = CustomUserManager()
#
#     class Meta:
#         db_table = 'auth_user'


class Category(models.Model):
    EXPENSES = 1
    INCOMES = 2
    BOTH = 3

    CHOICE_TYPE = (
        (EXPENSES, _('Expenses'), ),
        (INCOMES, _('Incomes')),
        (BOTH, _('Both'))
    )

    user = models.ForeignKey(User, blank=False, null=True, on_delete=models.CASCADE, default=None)
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
    user = models.ForeignKey(User, blank=False, null=False, on_delete=models.PROTECT, related_name='budgets')
    category = models.ForeignKey(Category, on_delete=models.PROTECT, blank=False, null=False, db_column='category')
    amount = models.FloatField(default=0)
    date_created = models.DateTimeField(auto_now_add=True)
    date_modified = models.DateTimeField(auto_now=True)
    date_ended = models.DateTimeField(blank=True, null=True)

    @staticmethod
    def get_budget(user):
        return Budget.objects.select_related('category')\
            .filter(user=user, date_ended__isnull=True)

    def getBudget(user):
        data = Budget.get_budget(user)
        return [{element['category']: element['amount']} for element in data]

    @staticmethod
    def get_budget_for_month(user, year, month):
        queryset = Transaction.objects\
            .prefetch_related('user', 'user__budgets', 'category') \
            .filter(date__year=year, date__month=month, user=user)\
            .filter(user__budgets__user=user, user__budgets__date_ended__isnull=True, user__budgets__category=F('category')) \
            .values('category')\
            .annotate(category_group=Count('category'))\
            .annotate(transaction_total=Cast(Sum('amount'), FloatField()))\
            .values('transaction_total', 'category__name', 'user__budgets__amount', 'category_group', 'category__id')\
            .order_by('category')
        return queryset

    @staticmethod
    def getLastBudgetByCategoryId(category_id):
        return Budget.objects.filter(category=category_id, date_ended_isnull=True)

    @staticmethod
    def snapshot(user_id):
        current_budget = Budget.get_budget(user_id)
        current_budget.date_ended = datetime.datetime.now()
    #
    #
    #     // mark current budget with end date
    #     $st_data = array('date_ended'    => date('Y-m-d h:i:s'));
    #     $this->_db->beginTransaction();
    #     try {
    #         $this->_db->update($this->_name, $st_data,
    #             'user_owner = '.$user_id.' AND date_ended IS NULL');
    #         // duplicate latest budget
    #         foreach ($st_currentBudget as $key => $value) {
    #             $st_data = array(
    #                 'user_owner'    =>    $user_id,
    #                 'category'        =>    $key,
    #                 'amount'        =>    $value,
    #                 'date_created'    =>    date('Y-m-d h:i:s')
    #             );
    #             $this->_db->insert($this->_name, $st_data);
    #         }
    #         $this->_db->commit();
    #     }
    #     catch (Exception $e) {
    #         $this->_db->rollBack();
    #         throw $e;
    #     }
    #     // finally get the current budget for this user
    #     return $this->getBudget($user_id);
    # }

    def getYearBudgets(user_id, i_year=None):
        if not i_year:
            i_year = datetime.date.today().year
        year_budget = []
        for r in range(1, 13):
            pass
    #         $s_nextMonthDate = ($i == 12)
    #                 ? strtotime('-1 day', mktime(23, 59, 59, 1, 1, $i_year+1))
    #                 : strtotime('-1 day', mktime(23, 59, 59, $i+1, 1, $i_year));
    #         $st_data = $this->_db->fetchAll(
    #             $this->_db->select()
    #                     ->from('budgets')
    #                     ->where('user_owner = '.$user_id)
    #                     ->where('YEAR(date_ended) = '.$i_year.' OR date_ended IS NULL')
    #                     ->where('unix_timestamp(date_created) <= '.$s_nextMonthDate)
    #                     ->where('unix_timestamp(date_ended) >= '.$s_nextMonthDate.' OR date_ended IS NULL')
    #                     ->order('date_created ASC')
    #             );
    #         if (empty($st_data)) {
    #             $st_data = $this->_db->fetchAll(
    #                 $this->_db->select()
    #                         ->from('budgets')
    #                         ->where('user_owner = '.$user_id)
    #                         ->where('YEAR(date_ended) = '.$i_year.' OR date_ended IS NULL')
    #                         ->where('date_ended IS NULL')
    #                         ->order('date_created ASC')
    #                 );
    #         }
    #         $st_budget = array();
    #         $s_currentDate = null;
    #         foreach($st_data as $key => $value) {
    #             $st_budget[$value['category']] = $value['amount'];
    #         }
    #         $st_yearBudget[$i] = $st_budget;
    #     }
    #     return $st_yearBudget;
    # }

    def getBudgetsDatesList(self):
        return Budget.objects.all().filter()
        #     $st_budgetsList = array();
        #     $st_budgetsListObjects = $this->fetchAll(
        #             $this->select()
        #                     ->from("budgets", array('DISTINCT(date_created) as date_created'))
        #                     ->where('user_owner = ?', $_SESSION['user_id'])
        #                     ->where('date_ended IS NOT NULL')
        #     );
        #     foreach($st_budgetsListObjects as $target => $budget) {
        #         $st_budgetsList[] = $budget->toArray();
        #     }
        #     return $st_budgetsList;
        # }


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

    user = models.ForeignKey(User, on_delete=models.CASCADE, related_name='shared_expenses_sheets')
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

    @staticmethod
    def get_by_user_match(user=None, user_id=None):
        assert user or user_id
        user_id = user_id if user_id else user.pk

    def __str__(self):
        return self.name

#     public function get_by_user_match($user_id) {
#         $select = $this->select()
#             ->from(array('s' => $this->_name), array('*'))
#             ->setIntegrityCheck(false)
#             ->joinLeft(array('su' => 'shared_expenses_sheet_users'), 'su.id_sheet = s.id', array())
#             ->where('s.user_owner = ?', $user_id)
#             ->orWhere('su.id_user = ?', $user_id)
#             ->order('s.id desc');
#         return $this->fetchAll($select);
#     }
#
#     public function get_by_unique_id($id) {
#         if (is_null($id)) {
#             error_log("null id received when getting sheet");
#             return null;
#         }
#         $row = $this->fetchRow('unique_id = "' . $id . '"')->toArray();
#         // now fetch expenses, order by date
#         $sharedExpense = new SharedExpenses();
#         $list = $sharedExpense->fetchAll('id_sheet = ' . $row['id'], array('date ASC', 'id ASC'));
#         $row['expenses'] = array();
#         $distinct_users = 0;
#         $distinct_users_list = array();
#         foreach($list as $l) {
#             if (!in_array($l['id_sheet_user'], $distinct_users_list)) {
#                 $distinct_users++;
#                 $distinct_users_list[] = $l['id_sheet_user'];
#             }
#             $row['expenses'][] = $l->toArray();
#         }
#         $row['distinct_users'] = $distinct_users;
#         $row['distinct_users_list'] = $distinct_users_list;
#         $row['users'] = $this->getUsersForSheet($row['unique_id']);
#         // add users that do not have any expense but exist in the sheet
#         foreach($row['users'] as $key => $u) {
#             if(!in_array($u['id_sheet_user'], $row['distinct_users_list'])) {
#                 $row['distinct_users_list'][] = $u['id_sheet_user'];
#                 $row['distinct_users']++;
#             }
#         }
#         return $row;
#     }
#
#     private function getUsersForSheet($sheet_id) {
#         try {
#             $nameCoalesce = new Zend_Db_Expr('COALESCE(u.login, sesu.email) as login');
#             $emailCoalesce = new Zend_Db_Expr('COALESCE(u.email, sesu.email) as email');
#             $select = $this->select()
#                 ->setIntegrityCheck(false)
#                 ->from(array('ses' => 'shared_expenses_sheets'), array(new Zend_Db_Expr ('0 as total')))
#                 ->joinInner(array('sesu' => 'shared_expenses_sheet_users'), 'ses.id = sesu.id_sheet', array('id as id_sheet_user'))
#                 ->joinLeft(array('u' => 'users'), 'u.id = sesu.id_user', array("u.id as id_user", $nameCoalesce, $emailCoalesce))
#                 ->where('ses.unique_id = ?', $sheet_id);
#             return $this->fetchAll($select)->toArray();
#         }
#         catch(Exception $e) {
#             return array();
#         }
#     }
# }


class SharedExpensesSheetUsers(models.Model):
    sheet = models.ForeignKey(SharedExpensesSheet, on_delete=models.CASCADE, related_name='users')
    user = models.ForeignKey(User, on_delete=models.CASCADE, related_name='related_shared_expenses_sheets')
    email = models.EmailField()
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    def __str__(self):
        return self.user.username if self.user else self.email

    @property
    def sheet_expense(self):
        result = SharedExpense.objects.filter(user=self).aggregate(total=Sum('amount'))
        return result['total'] if result['total'] else 0

    @property
    def difference(self):
        average_queryset = SharedExpense.objects.filter(sheet=self.sheet).aggregate(total=Sum('amount'))
        average = average_queryset['total'] if average_queryset['total'] else 0
        users = self.sheet.users.count()
        return self.sheet_expense - (average / users)


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
    user = models.ForeignKey(User, blank=False, null=False, on_delete=models.PROTECT, related_name='transactions')
    amount = models.DecimalField(max_digits=10, decimal_places=2, blank=False, null=False)
    category = models.ForeignKey(Category, on_delete=models.PROTECT, blank=False, null=False, db_column='category')
    note = models.CharField(max_length=255)
    date = models.DateTimeField(default=None)
    in_sum = models.BooleanField(blank=False, null=False)
    income_update = models.DateTimeField(auto_now=True)

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
        first_year = int(datetime.date.today().year) - 5
        queryset = queryset\
            .values(year=ExtractYear('date'))\
            .filter(year__gt=first_year)\
            .annotate(year_group=Count(F('year')), category_group=Count(F('category')))\
            .annotate(sum_amount=Cast(Sum('amount'), FloatField()))\
            .order_by('category', 'year')\
            .values_list('year', 'category', 'category__name', 'sum_amount')

        incomes_by_year_and_category = {}
        current_category = None
        for value in queryset:
            if value[1] != current_category:
                current_category = value[1]
                incomes_by_year_and_category[current_category] = []
            incomes_by_year_and_category[current_category].append({
                'year': value[0],
                'category': value[1],
                'name': value[2],
                'amount': value[3]
            })
        return incomes_by_year_and_category

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
    def get_category_amounts(user, filter_date, get_params):
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


class Tag(models.Model):
    # TODO check if when adding tags, backslashes must be replaced
    transaction_tags = None
    existing_tags = None

    user = models.ForeignKey(User, blank=False, null=False, on_delete=models.CASCADE)
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
    def get_used_tags(user):
        queryset = Tag.objects\
            .prefetch_related('transactions')\
            .annotate(count=Count('id'))\
            .filter(user=user).order_by('-name')
        return Tag.__get_tags_from_query(queryset)

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

    # public function getTagsForTransaction($transactionId) {
    #     $select = $this->select()
    #             ->setIntegrityCheck(false)
    #             ->from(array('tt' => $this->_name), array())
    #             ->joinInner(array('t' => 'tags'), 't.id = tt.id_tag', array('name'))
    #             ->joinInner(array('tr' => 'transactions'), 'tr.id = tt.id_transaction', array())
    #             ->where('tr.id = ?', $transactionId);
    #     $rows = $this->fetchAll($select)->toArray();
    #     $tags = array();
    #     foreach($rows as $row) {
    #         $tags[] = str_replace("'", "\'", $row['name']);
    #     }
    #     return $tags;
    # }
    #
    # /**
    #  * Removes tags from transactions.
    #  * @var int $transactionId
    #  * @var int|array $tags
    #  */
    # public function removeTagsFromTransaction($transactionId) {
    #     $this->delete("id_transaction = ".$transactionId);
    # }


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
