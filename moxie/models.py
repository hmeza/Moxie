from django.db import models
from django.contrib.auth.models import User, AbstractUser
from django.db.models.fields.related import ForeignKey
from django.db.models.functions import Abs, Cast, ExtractMonth
from django.db.models import Sum, FloatField, Count, F
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
        "self", db_column='parent', blank=True, null=True, related_name='subcategories', on_delete=models.PROTECT, default=None
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
#.order_by('order')\

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
#     /**
#      * @desc    Get 3 level categories tree, only with leaves
#      * @author    hmeza
#      * @return    array
#      */
    @staticmethod
    def get_categories_tree(user):
        data = Category.objects\
            .filter(user_owner=user, name__isnull=True)\
            .prefetch_related('subcategories', 'subcategories__subcategories')\
            .order_by('subcategories__id')
        print(data.query)
        return data
#     public function getCategoriesTree() {
#         try {
#             $query = $this->database->select()
#                 ->from(array('c1'=>'categories'), array(
#                     'id1'    =>    'c1.id',
#                     'name1'    =>    'c1.name',
#                     'id2'    =>    'c2.id',
#                     'name2'    =>    'c2.name',
#                     'id3'    =>    'c3.id',
#                     'name3'    =>    'c3.name'
#                 ))
#                 ->joinLeft(array('c2'=>'categories'),'c2.parent = c1.id',array())
#                 ->joinLeft(array('c3'=>'categories'),'c3.parent = c2.id',array())
#                 ->where('c1.user_owner = ?', $_SESSION['user_id'])
#                 ->where('c1.name IS NULL')
#                 ->order('c2.id');
#             $stmt = $this->database->query($query);
#             return $stmt->fetchAll();
#         }
#         catch (Exception $e) {
#             error_log('Exception caught on '.__CLASS__.', '.__FUNCTION__.'('.$e->getLine().'), message: '.$e->getMessage());
#         }
#         return array();
#     }
#
#     public function getCategoriesForView($i_typeFilter) {
#         // get categories and prepare them for view
#         $s_categories = $this->getCategoriesByUser($i_typeFilter);
#         $formCategories = array();
#         foreach($s_categories as $key => $value) {
#             $formCategories[$value['id1']] = $value['name2'];
#             if (!empty($value['name1'])) {
#                 $formCategories[$value['id1']] = $value['name1'].' - '.$formCategories[$value['id1']];
#             }
#         }
#         return $formCategories;
#     }
#
#     public function getCategoriesForSelect($i_typeFilter) {
#         global $st_lang;
#         // get categories and prepare them for view
#         $s_categories = $this->getCategoriesByUser($i_typeFilter);
#         // get root category
#         $st_parent = $this->fetchRow($this->select()
#             ->where('user_owner = '.$_SESSION['user_id'])
#             ->where('parent IS NULL'));
#
#         $formCategories = array();
#         $formCategories[$st_parent->id] = $st_lang['category_new'];
#         foreach($s_categories as $key => $value) {
#             $formCategories[$value['id1']] = $value['name2'];
#             if (!empty($value['name1'])) {
#                 $formCategories[$value['id1']] = $value['name1'].' - '.$formCategories[$value['id1']];
#             }
#         }
#         return $formCategories;
#     }
#
#     /**
#      * Returns the list of categories mounted as a tree.
#      * @param array $st_categories
#      * @return array
#      */
#     public function mountCategoryTree($st_categories, $i_userId) {
#         $st_parent = $this->fetchRow($this->select()
#                 ->where('user_owner = '.$i_userId)
#                 ->where('parent IS NULL'));
#         $st_root = array(
#                 'id1'        =>    $st_parent->id,
#                 'parent1'    =>    null,
#                 'name1'        =>    null,
#                 'name2'        =>    'New category'
#         );
#         $st_parentCategories = array();
#         $st_parentCategories[] = $st_root;
#         foreach ($st_categories as $key => $value) {
#             $st_parentCategories[] = $value;
#         }
#         return $st_parentCategories;
#     }
#
#     /**
#      * Mount the category tree for the current budget.
#      * @param array $st_categories
#      * @return array
#      */
#     public function prepareCategoriesTree($st_categories) {
#         $st_preparedTree = array();
#         foreach($st_categories as $key => $value) {
#             if (empty($value['id3'])) {
#                 $i_key = null;
#                 $st_value = null;
#             }
#             if (!empty($value['id3']) && $i_key == null) {
#                 $i_key = $value['id2'];
#                 $st_parentLine = array(
#                         'id1'    =>    $value['id1'],
#                         'name1'    =>    $value['name1'],
#                         'id2'    =>    $value['id2'],
#                         'name2'    =>    $value['name2'],
#                         'id3'    =>    null,
#                         'name3'    =>    null
#                 );
#                 $st_preparedTree[] = $st_parentLine;
#             }
#             $st_preparedTree[] = $value;
#         }
#         return $st_preparedTree;
#     }
#
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
        qq = Transaction.objects\
            .prefetch_related('user', 'user__budgets', 'category') \
            .filter(date__year=year, date__month=month, user=user)\
            .filter(user__budgets__user=user, user__budgets__date_ended__isnull=True, user__budgets__category=F('category')) \
            .values('category')\
            .annotate(category_group=Count('category'))\
            .annotate(transaction_total=Cast(Sum('amount'), FloatField()))\
            .values('transaction_total', 'category__name', 'user__budgets__amount', 'category_group')\
            .order_by('category')
        print(qq.query)
        return qq

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

    def delete(user=None, date=None, *args, **kwargs):
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

#     def query_filter(self, query, params):
#         if params.get('category_search'):
#             if type(params.get('category_search')) == int:
#                 # $query = $query->where('i.category = ?', params['category_search']);
#                 query = query.filter(category=params.get('category_search'))
#             else:
#                 # $query = $query->having('c.id IS NULL');
#                 query = query.having('c.id is NULL')
#
#         if params.get('note_search'):
#             query = query.filter()
#             # $query = $query->where('LOWER(i.note) like ?', '%'.strtolower(params['note_search']).'%');
#
#         if(!empty(params['tag_search'])) {
#             $s_tag = urldecode(params['tag_search']);
#             $query = $query->joinInner(array('tt' => 'transaction_tags'), 'tt.id_transaction = i.id', array())
#                     ->joinInner(array('t' => 'tags'), 't.id = tt.id_tag', array())
#                     ->where('t.name = ?', $s_tag);
#         }
#         if(!empty(params['amount_min'])) {
#             $query = $query->where('ABS(i.amount) >= ?', params['amount_min']);
#         }
#         if(!empty(params['amount_max'])) {
#             $query = $query->where('ABS(i.amount) <= ?', params['amount_max']);
#         }
#         if(!empty(params['date_min'])) {
#             $query = $query->where('i.date >= ?', params['date_min']);
#         }
#         if(!empty(params['date_max'])) {
#             $query = $query->where('i.date <= ?', params['date_max']);
#         }
#     }
#
#     def get(user_id, type=Categories::EXPENSES, params):
#         try {
#             $query = $this->select()
#                     ->setIntegrityCheck(false)
#                     ->from(
#                             array('i' => $this->_name),
#                             array(
#                                     'id' => 'i.id',
#                                     'user_owner' => 'i.user_owner',
#                                     'amount' => new Zend_Db_Expr('ABS(i.amount)'),
#                                     'note' => 'i.note',
#                                     'date' => 'i.date',
#                                     'in_sum' => 'i.in_sum'
#                             )
#                     )
#                     ->joinLeft(
#                             array('c' => 'categories'),
#                             'c.id = i.category',
#                             array(
#                                     'name' => 'c.name',
#                                     'description' => 'c.description',
#                                     'category' => 'c.id'
#                             )
#                     )
#                     ->where('i.user_owner = ' . $user_id);
#
#             $this->query_filter($query, params);
#
#             if ($type == Categories::EXPENSES) {
#                 $query = $query->where('amount < 0');
#             } else {
#                 if ($type == Categories::INCOMES) {
#                     $query = $query->where('amount >= 0');
#                 }
#             }
#
#             if (isset(params['o'])) {
#                 $filter = params['o'];
#                 $order = 'desc';
#                 if($filter[0] == '-') {
#                     $order = 'asc';
#                     $filter = substr($filter, 1, strlen($filter) - 1);
#                 }
#                 if ($filter == 'date') {
#                     $filter = 'i.date';
#                 }
#                 if($filter == 'amount') {
#                     $filter = 'i.amount';
#                 }
#                 $query = $query->order($filter.' '.$order);
#             }
#             else {
#                 $query = $query->order('i.date desc');
#             }
#             $result = $this->fetchAll($query);
#         }
#         catch(Exception $e) {
#             error_log($e->getMessage());
#             $result = array();
#         }
#         return $result;
#     }
#
#     /**
#      * Delete row by id, with user_owner check.
#      * @param int $id
#      * @param int $user
#      * @return int
#      * @throws Zend_Db_Select_Exception
#      * @throws Exception
#      */
#     public function delete($id, $user) {
#         $s_where = $this->select()
#                 ->from($this->_name)
#                 ->where('id = ?', $id)
#                 ->where('user_owner = ?', $user)
#                 ->getPart(Zend_Db_Select::SQL_WHERE);
#         $deleted = parent::delete(implode(" ", $s_where));
#         error_log("deleted rows: ".$deleted);
#         if($deleted == 0) {
#             throw new Exception("Error deleting transaction ".$id." for user ".$user);
#         }
#     }
#
#     public function getYearly($user, $year) {
#         $select = $this->select()
#             ->setIntegrityCheck(false)
#             ->from(array('t' => 'transactions'), array('category', 'sum(amount)'))
#             ->joinLeft(array('c' => 'categories'), 't.category = c.id', array())
#             ->where('t.user_owner = ?', $user)
#             ->where('year(date) = ?', $year)
#             ->group(array('year(date)', 'category'));
#         return $this->fetchAll($select)->toArray();
#     }
#
#     public function getFavourites($user) {
#         $select = $this->select()
#                 ->setIntegrityCheck(false)
#                 ->from(array('f' => 'favourites'), array())
#                 ->joinInner(array('t' => 'transactions'), 'f.id_transaction = t.id', array('t.id', 't.amount', 't.note', 't.category', 't.in_sum'))
#                 ->where('t.user_owner = ?', $user);
#         $results = $this->fetchAll($select)->toArray();
#         $tagsModel = new Tags();
#         foreach ($results as $key => $result) {
#             // use $result['id'] to retrieve tags
#             $tags = array();
#             foreach($tagsModel->getTagsForTransaction($result['id']) as $t) {
#                 $tags[] = str_replace("\\'", "'", $t);
#             }
#             $results[$key]['tags'] = $tags;
#         }
#         return $results;
#     }
# }


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
    transaction_tags = None
    existing_tags = None

    user = models.IntegerField(db_column='user_owner', blank=False, null=False)
    name = models.CharField(max_length=255, null=True, blank=True)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

	# /**
	#  * Adds a new tag for $userId
	#  * @var int $userId
	#  * @var string $name
	#  * @return int
    #  * @throws Exception
	#  */
	# public function addTag($userId, $name) {
	# 	if(empty($userId)) {
	# 		throw new Exception("Empty user id");
	# 	}
	# 	if(empty($name)) {
	# 		throw new Exception("Empty tag name");
	# 	}
    #     if(is_null($this->existingTags)) {
    #         $this->existingTags = $this->getTagsByUser($userId);
    #     }
    #
    #     // check if backslashes have been already replaced
    #     $name = trim($name);
    #     $pos = strpos($name, "\\'");
	# 	if ($pos === false) {
    #     }
    #     else {
    #         $name = str_replace("\\'", "'", $name);
    #     }
    #     $tagId = array_search($name, $this->existingTags);
    #     if($tagId === FALSE) {
    #         $data = array(
    #             'user_owner' => $userId,
    #             'name' => $name
    #         );
    #         try {
    #             return $this->insert($data);
    #         } catch (Exception $e) {
    #             error_log('Exception caught on '.__METHOD__.'('.$e->getLine().'), message: '.$e->getMessage());
    #             return null;
    #         }
    #     }
    #     else {
    #         return $tagId;
    #     }
	# }

    def get_tags_by_user(self, user=None):
        if not user:
            return []
        queryset = Tag.objects.filter(user_owner=user)
        return Tag.__get_tags_from_query(queryset)

    def get_tags_for_transaction(self, transaction):
        queryset = Tag.objects.prefetch_related('transaction_tags')\
            .filter(transaction_tags__id_transaction=transaction)
        return Tag.__get_tags_from_query(queryset)

    def get_used_tags_by_user(self, user):
        queryset = Tag.objects\
            .select_related('transaction_tags')\
            .filter(user_owner=user).group('id').order_by('-name')
        return Tag.__get_tags_from_query(queryset)

    def __get_tags_from_query(self, queryset):
        tags = {}
        for tag in queryset.all():
            tags[tag.id] = tag.name.replace("'", "\\'")
        return tags

    def get_tag(self, user_id=None, tag=None):
        return self.objects.filter(user_owner=user_id, name=tag).first()


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
    transaction = models.ForeignKey(Transaction, on_delete=models.PROTECT, null=False, blank=False, default=None)
    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)
