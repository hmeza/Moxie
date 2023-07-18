from django.db import models
from django.contrib.auth.models import User, AbstractUser
from django.db.models.functions import Cast, Concat, ExtractYear, Abs
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

    user_owner = models.ForeignKey(User, db_column='user_owner', blank=False, null=False, on_delete=models.CASCADE)
    parent = models.ForeignKey(
        "self", db_column='parent', blank=True, null=True, related_name='subcategories', on_delete=models.PROTECT,
        default=None
    )
    name = models.CharField(max_length=50, blank=False, null=False)
    description = models.CharField(max_length=200, blank=False, null=False)
    type = models.SmallIntegerField()
    order = models.SmallIntegerField(default=1)

    def __str__(self):
        return self.name if self.name else ''

    @staticmethod
    def get_categories_by_user(user, category_type=BOTH):
        return Category.objects \
            .filter(user_owner=user.pk, parent__isnull=False, type=category_type)\
            .order_by('order', 'name') \
            .all()

#     /**
#      * Gets categories for a given user.
#      * i_typeFilter stands for the type of category to retrieve.
#      * @param int $i_typeFilter
#      * @throws Exception No user found in session
#      */
#     public function getCategoriesByUser($i_typeFilter) {
#         if(empty($_SESSION) || empty($_SESSION['user_id'])) {
#             throw new Exception("No user id found in session");
#         }
#         if ($i_typeFilter == self::BOTH) $s_typeFilter = '1 = 1';
#         else $s_typeFilter = 'c2.type = '.self::BOTH.' OR c2.type = '.$i_typeFilter;
#
#         $query = $this->database->select()
#             ->from(array('c1'=>'categories'), array(
#                 'id1'    =>    'distinct(c2.id)',
#                 'parent1'    =>    'c1.id',
#                 'grandparent' => 'c1.parent',
#                 'name1'    =>    'c1.name',
#                 'name2'    =>    'c2.name',
#                 'type'    =>    'c2.type',
#                 'parent' => 'c2.parent',
#                 'order' => 'c2.order'
#             ))
#             ->joinLeft(array('c2'=>'categories'),'c2.parent = c1.id',array())
#             ->where('c1.user_owner = ?', $_SESSION['user_id'])
#             ->where('c2.id is not null')
#             ->where($s_typeFilter)
#             ->order('c2.order')
#             ->order('c1.parent')
#             ->order('c2.parent');
#         $stmt = $this->database->query($query);
#         return $stmt->fetchAll();
#     }
#

    def __str__(self):
        if self.parent.parent is None:
            return self.name
        else:
            return self.parent.name + " - " + self.name

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
            .filter(user_owner=user)
        if type_filter == Category.BOTH:
            queryset = queryset.filter(Q(type=Category.EXPENSES) | Q(type=Category.INCOMES) | Q(type=Category.BOTH))
        else:
            queryset = queryset.filter(Q(type=type_filter) | Q(type=Category.BOTH))
        queryset = queryset\
            .filter(Q(parent__isnull=False))\
            .annotate(cat_name=Concat(F('name'), Value('-'), F('parent__name')))\
            .order_by('order')
        return queryset

#     /**
#      * @param $i_lastInsertId
#      * @return boolean
#      */
#     public function insertCategoriesForRegisteredUser($i_lastInsertId) {
#         $orderIndex = 0;
#         // first insert root category
#         $st_categoriesData = array(
#                 'user_owner' => $i_lastInsertId,
#                 'description' => 'root category'
#         );
#         // add default categories for the new user
#         $i_rootCategory = $this->insert($st_categoriesData);
#         $st_categoriesData = array(
#                 'user_owner' => $i_lastInsertId,
#                 'parent' => $i_rootCategory,
#                 'name' => 'Hogar',
#                 'description' => 'Hogar',
#                 'type' => 3,
#                 'order' => $orderIndex++
#         );
#         $this->insert($st_categoriesData);
#         $st_categoriesData = array(
#                 'user_owner' => $i_lastInsertId,
#                 'parent' => $i_rootCategory,
#                 'name' => 'Diversión',
#                 'description' => 'Salidas, cenas fuera, ocio, etc.',
#                 'type' => 3,
#                 'order' => $orderIndex++
#         );
#         $this->insert($st_categoriesData);
#         $st_categoriesData = array(
#                 'user_owner' => $i_lastInsertId,
#                 'parent' => $i_rootCategory,
#                 'name' => 'Tecnología',
#                 'description' => 'Tecnología',
#                 'type' => 3,
#                 'order' => $orderIndex++
#         );
#         $this->insert($st_categoriesData);
#         $st_categoriesData = array(
#                 'user_owner' => $i_lastInsertId,
#                 'parent' => $i_rootCategory,
#                 'name' => 'Regalos',
#                 'description' => 'Navidad, reyes, aniversarios, san Valentín, etc.',
#                 'type' => 3,
#                 'order' => $orderIndex++
#         );
#         $this->insert($st_categoriesData);
#         $st_categoriesData = array(
#                 'user_owner' => $i_lastInsertId,
#                 'parent' => $i_rootCategory,
#                 'name' => 'Ropa',
#                 'description' => 'Ropa',
#                 'type' => 3,
#                 'order' => $orderIndex++
#         );
#         $this->insert($st_categoriesData);
#         $st_categoriesData = array(
#                 'user_owner' => $i_lastInsertId,
#                 'parent' => $i_rootCategory,
#                 'name' => 'Varios',
#                 'description' => 'Otros gastos',
#                 'type' => 3,
#                 'order' => $orderIndex++
#         );
#         $this->insert($st_categoriesData);
#         $st_categoriesData = array(
#             'user_owner' => $i_lastInsertId,
#             'parent' => $i_rootCategory,
#             'name' => 'Comida',
#             'description' => 'Comida',
#             'type' => 3,
#             'order' => $orderIndex++
#         );
#         $this->insert($st_categoriesData);
#
#         $o_foodCategory = $this->fetchRow(
#                 $this->select()->where('name = "Comida" AND user_owner = ' . $i_lastInsertId)
#         );
#         $st_categoriesData = array(
#                 'user_owner' => $i_lastInsertId,
#                 'parent' => $o_foodCategory->id,
#                 'name' => 'Casa',
#                 'description' => 'Comida comprada para casa',
#                 'type' => 3,
#                 'order' => $orderIndex++
#         );
#         $this->insert($st_categoriesData);
#         $st_categoriesData = array(
#                 'user_owner' => $i_lastInsertId,
#                 'parent' => $o_foodCategory->id,
#                 'name' => 'Fuera',
#                 'description' => 'Comidas fuera de casa',
#                 'type' => 3,
#                 'order' => $orderIndex++
#         );
#         $this->insert($st_categoriesData);
#         $st_categoriesData = array(
#                 'user_owner' => $i_lastInsertId,
#                 'parent' => $o_foodCategory->id,
#                 'name' => 'Café',
#                 'description' => 'Cafés, bollería durante el día, desayuno en cafetería, etc.',
#                 'type' => 3,
#                 'order' => $orderIndex
#         );
#         $this->insert($st_categoriesData);
#         return true;
#     }
# }


class Budget(models.Model):
    user = models.ForeignKey(User, blank=False, null=False, on_delete=models.PROTECT, related_name='budgets')
    category = models.ForeignKey(Category, on_delete=models.PROTECT, blank=False, null=False, db_column='category')
    amount = models.FloatField(default=0)
    date_created = models.DateTimeField(auto_now_add=True)
    date_modified = models.DateTimeField(auto_now=True)
    date_ended = models.DateTimeField(blank=True, null=True)

    @staticmethod
    def get_budget(user_id):
        return Budget.objects.filter(user_owner=user_id, date_ended__isnull=True)

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

    def getBudget(user_id):
        data = Budget.get_budget(user_id)
        return [{element['category']: element['amount']} for element in data]

    @staticmethod
    def getLastBudgetByCategoryId(category_id):
        return Budget.objects.filter(category=category_id, date_ended_isnull=True)

    @staticmethod
    def snapshot(user_id):
        currentBudget = Budget.get_budget(user_id)
        currentBudget.date_ended = datetime.datetime.now()
        pass
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

    def delete(self, user=None, date=None, *args, **kwargs):
        if user and date:
            return Budget.objects.all().filter(user_owner=user, date_created=date).delete()
        return super().delete(*args, **kwargs)


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

# <?php
#
# class SharedExpenses extends Zend_Db_Table_Abstract {
#     const DEFAULT_CURRENCY = 'eur';
#
#     protected $_name = 'shared_expenses';
#     protected $_primary = 'id';
#
#     public function __construct() {
#         $this->_db = Zend_Registry::get('db');
#     }
#
#     public function getSheetByExpenseIdAndUserId($sharedExpenseId, $userId) {
#         $select = $this->select()
#             ->from(array('se' => $this->_name), array())
#             ->setIntegrityCheck(false)
#             ->joinInner(array('ses' => 'shared_expenses_sheets'), 'se.id_sheet = ses.id', array('unique_id'))
#             ->joinInner(array('sesu' => 'shared_expenses_sheet_users'), 'sesu.id_sheet = ses.id', array('id'))
#             ->where('sesu.id_user = ?', $userId)
#             ->where('se.id = ?', $sharedExpenseId);
#         error_log($select);
#         try {
#             $row = $this->fetchRow($select);
#             if($row) {
#                 $row = $row->toArray();
#             }
#         }
#         catch(Exception $e) {
#             error_log($e->getMessage());
#             return false;
#         }
#         return $row;
#     }
# }


# <?php
#
# class SharedExpensesSheet extends Zend_Db_Table_Abstract {
#     private $database;
#     protected $_name = 'shared_expenses_sheets';
#     protected $_primary = 'id';
#
#     public function __construct() {
#         global $db;
#         $this->database = $db;
#         $this->_db = Zend_Registry::get('db');
#     }
#
#     public function get_by_user_owner($owner_id) {
#         return $this->fetchAll("user_owner = ?", $owner_id)->toArray();
#     }
#
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


# <?php
#
# class SharedExpensesSheetUsers extends Zend_Db_Table_Abstract {
#
#     protected $_name = 'shared_expenses_sheet_users';
#     protected $_primary = 'id';
#
#     public function __construct() {
#         $this->_db = Zend_Registry::get('db');
#     }
# }


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
        Transaction, db_column='id_transaction', related_name='tags', on_delete=models.CASCADE
    )
    tag = models.ForeignKey(Tag, db_column='id_tag', related_name='transactions', on_delete=models.CASCADE)
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
