import datetime
import re
from dateutil.relativedelta import relativedelta
from django.views.generic import CreateView, UpdateView, DeleteView, ListView, FormView
from django.urls import reverse_lazy
from django.shortcuts import redirect
from django.utils.translation import gettext_lazy as _
from django.core.mail import send_mail
from django.db.models import Sum, Case, When, Value, BooleanField, Count

from django.contrib.auth.mixins import LoginRequiredMixin
from moxie.models import SharedExpense, SharedExpensesSheet, Category
from moxie.forms import SheetSelector, SharedExpensesSheetsForm#, SharedExpensesSheetEditForm


class SheetsView(LoginRequiredMixin, ListView, FormView):
	template_name = 'sheets/index.html'
	form_class = SharedExpensesSheetsForm

	def get_queryset(self):
		return SharedExpense.objects.filter(user=self.request.user)

	def get_context_data(self, *args, **kwargs):
		context = super().get_context_data(*args, **kwargs)
		context['sheet_list'] = SharedExpensesSheet.objects.exists()
		context['select_sheet_form'] = SheetSelector(user=self.request.user)
		return context


class SheetView(UpdateView):
	model = SharedExpensesSheet
	slug_url_kwarg = 'unique_id'
	query_pk_and_slug = True
	template_name = 'sheets/view.html'
	fields = ['name']

	def get_slug_field(self):
		return 'unique_id'

	def send_user_added(self):
		...

	def get_queryset(self):
		queryset = super().get_queryset()
		queryset = queryset.prefetch_related('users', 'users__user', 'expenses')
		return queryset

	def get_context_data(self, **kwargs):
		context = super().get_context_data(**kwargs)
		context['sheet_list'] = SharedExpensesSheet.objects.exists()
		context['select_sheet_form'] = SheetSelector(user=self.request.user)

		context['shared_expenses_form'] = SharedExpensesSheetsForm()
		context['sheet_not_closed'] = not bool(self.object.closed_at)

		conditional = Case(When(user=self.request.user, then=Value(True)), default_value=Value(False), output_field=BooleanField())

		sheet = SharedExpense.objects\
			.select_related('sheet').prefetch_related('sheet__users')\
			.annotate(my_expense=conditional).filter(sheet__unique_id=self.kwargs.get('unique_id')).order_by('date')
		context['sheet'] = sheet
		context['total'] = self.get_object().expenses.aggregate(sum=Sum('amount'))
		# todo calculate average, keep in mind currency change
		context['user_categories'] = Category.get_categories_by_user(self.request.user, Category.EXPENSES)
		context['sheet_users'] = SharedExpensesSheet.objects.get(unique_id=self.kwargs.get('unique_id')).users
		# < ?php
		# $js = array();
		# if (is_array($this->sheet['users'])) {
		# foreach($this->sheet['users'] as $user) {
		# $name = empty($user['login']) ? $user['email']: $
		# 	user['login'];
		# $js[] = '["'.$name.
		# '", '.$user['total'].
		# ']';
		# }
		# }
		# $pieData = '['.implode(', ', $js).']';
		# ? >
		context['pie_data'] = None
		return context

	def _send_user_added(self, sheet, user_email, registered=False):
		...
		subject = "Moxie - " + _('New shared expenses sheet')
		url = reverse_lazy('sheet_view', kwargs={'unique_id': sheet.unique_id})
		text_1 = _('Someone shared an expenses sheet with you:')
		text_2 = """
		If you have a Moxie account, you will see this sheet in your shared expenses sheets.
		If you do not have an account, you can register and this sheet will be linked to your account
		automatically."""
		footer = _('Best regards\n\nMoxie team')
		body = f"""
		{text_1}
		
		{sheet.name}
		
		{url}
		
		{text_2}
		
		{footer}
		"""
		from_address = 'moxie@dootic.com'
		# TODO https://docs.djangoproject.com/en/4.2/topics/email/
		return send_mail(subject, body, from_address, [user_email])
# 	private function sendUserAdded($sheetId, $userEmail, $sheetName, $registered=false) {
# 		$s_server = Zend_Registry::get('config')->moxie->settings->url;
# 		$s_site = Zend_Registry::get('config')->moxie->app->name;
#
# 		$headers = 'From: Moxie <moxie@dootic.com>' . "\r\n" .
# 				'Reply-To: moxie@dootic.com' . "\r\n" .
# 				'X-Mailer: PHP/' . phpversion() . "\r\n";


# <?php
# /** Zend_Controller_Action */
# class SheetsController extends Zend_Controller_Action
# {
#     /**
#      * @var SharedExpensesSheet
#      */
# 	private $sheetModel;
#
# 	public function init() {
# 		$this->sheetModel = new SharedExpensesSheet();
# 	}
#
# 	/**
# 	 * Show sheets page and all my sheets.
# 	 */
# 	public function indexAction() {
# 		$this->set_sheet_list_to_view();
# 	}
#
# 	/**
# 	 * List a single sheet.
# 	 */
# 	public function viewAction() {
# 		$categories = new Categories();
# 		$id = $this->getRequest()->getParam('id', null);
# 		$sheet = $this->sheetModel->get_by_unique_id($id);
# 		$this->view->assign('sheet', $sheet);
# 		if ($this->getRequest()->getParam('errors', null)) {
# 			$this->view->assign('errors', $this->getRequest()->getParam('errors'));
# 		}
# 		try {
# 			$this->view->assign('categories', $categories->getCategoriesForView(Categories::EXPENSES));
# 		}
# 		catch(Exception $e) {
# 			$this->view->assign('categories', array());
# 		}
# 		$this->set_sheet_list_to_view();
# 		//$this->view->assign('sheet_form', $this->getForm($sheet['users'], $id));
# 	}
#
# 	public function createAction() {
# 	    $request = $this->getRequest();
# 		if ($request->isPost()) {
# 			try {
# 				if (empty($request->getParam('name', null))) {
# 					throw new Exception("Please set name for sheet");
# 				}
# 				$change = floatval(str_replace(",", ".", $request->getParam('change', 1)));
# 				if($change === 0.0) {
# 				    $change = 1;
#                 }
# 				$data = array(
#                     'user_owner' => $_SESSION['user_id'],
#                     'name' => $request->getParam('name', ''),
#                     'unique_id' => uniqid(),
#                     'currency' => $request->getParam('currency', SharedExpenses::DEFAULT_CURRENCY),
#                     'change' => $change
# 				);
# 				$id = $this->sheetModel->insert($data);
# 				$sheet = $this->sheetModel->find($id)->current();
# 				// add creator as first user
# 				$sheetUser = new SharedExpensesSheetUsers();
# 				$sheetUser->insert(array(
# 						'id_sheet' => $id,
# 						'id_user' => $_SESSION['user_id']
# 				));
# 				$this->view->assign('sheet', $sheet);
# 			}
# 			catch(Exception $e) {
# 				$errors = array($e->getMessage());
# 				$this->view->assign('errors', $errors);
# 			}
# 			$this->redirect('/sheets/view/id/'.$sheet['unique_id']);
# 		}
# 		// else render GET page
# 	}
#
# 	public function addAction() {
# 		global $st_lang;
# 		$id_sheet = $this->getRequest()->getParam('id');
# 		// validations: logged user
# 		if(!isset($_SESSION) || (isset($_SESSION) && empty($_SESSION['user_id']))) {
# 			// return 403
# 			$this->_request->setPost(array(
# 					'id' => $id_sheet,
# 					'errors' => array($st_lang['error_nouser'])
# 			));
# 			return $this->_forward("view", "sheets");
# 		}
# 		try  {
# 			$sheet = $this->getSheet();
# 		}
# 		catch(Exception $e) {
# 			// return 404
# 			$this->view->assign('errors', array('Sheet not found'));
# 			//$this->render('index', 'expenses');
# 			$this->redirect('/sheets/view/id/'.$id_sheet);
# 		}
# 		try {
# 			$sharedExpenseModel = new SharedExpenses();
#             $amount = str_replace(",",".",$this->getRequest()->getParam('amount'));
#             $currency = $this->getRequest()->getParam('currency');
#             $currencyValue = ($currency === "on") ? $sheet['currency'] : SharedExpenses::DEFAULT_CURRENCY;
# 			$data = array(
# 					'id_sheet' => $sheet['id'],
# 					'id_sheet_user' => $this->getRequest()->getParam('id_sheet_user'),
# 					'amount' => $amount,
# 					'note' => $this->getRequest()->getParam('note', ''),
# 					'date' => $this->getRequest()->getParam('date'),
#                     'currency' => $currencyValue
# 			);
# 			$sharedExpenseModel->insert($data);
# 		}
# 		catch(Exception $e) {
# 			// return 500 / error message
# 			error_log($e->getMessage());
# 			$this->view->assign('errors', array('Unable to store shared expense'));
# 			$this->render('view', 'sheets');
# 		}
# 		$this->redirect('/sheets/view/id/'.$id_sheet);
# 	}
#
# 	public function deleteAction() {
# 	    global $st_lang;
# 		// validations: logged user
# 		if(!isset($_SESSION) || (isset($_SESSION) && empty($_SESSION['user_id']))) {
# 			// return 403
# 			$this->_request->setPost(array(
# 					'id' => $this->getRequest()->getParam('id'),
# 					'errors' => array($st_lang['error_nouser'])
# 			));
# 			return $this->_forward("view", "sheets");
# 		}
# 		try {
# 			$seid = $this->getRequest()->getParam('id');
# 			// validate that current user appears in the sheet of this shared expense
# 			$seModel = new SharedExpenses();
# 			$seModel->find($seid);
# 			$row = $seModel->getSheetByExpenseIdAndUserId($seid, $_SESSION['user_id']);
# 			if(empty($row)) {
# 				throw new Exception("Shared expense does not appear in a sheet from current user");
# 			}
# 			$id_sheet = $row['unique_id'];
# 			$seModel->delete('id = '.$seid);
# 		}
# 		catch(Exception $e) {
# 			error_log($e->getMessage());
# 			$this->redirect('/sheets');
# 		}
# 		$this->redirect('/sheets/view/id/'.$id_sheet);
# 	}
#
# 	public function closeAction() {
# 		global $st_lang;
# 		$id_sheet = $this->getRequest()->getParam('id_sheet', null);
# 		// validations: logged user
# 		if(!isset($_SESSION) || (isset($_SESSION) && empty($_SESSION['user_id']))) {
# 			// return 403
# 			$this->_request->setPost(array(
# 					'id' => $id_sheet,
# 					'errors' => array($st_lang['error_nouser'])
# 			));
# 			return $this->_forward("view", "sheets");
# 		}
# 		try {
# 			$sheet = $this->getSheet();
# 			$this->sheetModel->update(array('closed_at' => date('Y-m-d H:i:s')), 'unique_id = "'.$sheet['unique_id'].'"');
# 			// @todo: set message for view "Sheet closed"
# 			// @todo: send email to all users in the sheet - sheet closed w/user that closed it.
# 			$this->view->assign('messages', array('Closed successfully'));
# 		}
# 		catch(Exception $e) {
# 			// return error 404
# 			// @todo: set error message
# 			$this->view->assign('errors', array($e->getMessage()));
# 		}
# 		$this->redirect('/sheets/view/id/'.$sheet['unique_id']);
# 	}
#
class SheetCopyView(SheetView):
	...
# 	public function copyAction() {
# 		$sheet_id = $this->getRequest()->getParam('id_sheet');
# 		// @todo validate user
# 		if(empty($_SESSION['user_id'])) {
# 			// @todo set error message
# 			error_log("error in session + category");
# 			$this->redirect('/sheets/view/id/'.$sheet_id);
# 		}
# 		// validate that category belongs to user
# 		$catModel = new Categories();
#
# 		$sheet = $this->getSheet();
#
# 		// Default change rate is 1, so if change is not set from the form, 1 will be used
# 		$changeRate = $this->getParam('change', $sheet['change']);
#
# 		// find sheet_user_id for this sheet and for this user
# 		$id_sheet_user = null;
# 		foreach($sheet['users'] as $u) {
# 			if($u['id_user'] == $_SESSION['user_id']) {
# 				$id_sheet_user = $u['id_sheet_user'];
# 				break;
# 			}
# 		}
# 		error_log("user is ".$id_sheet_user);
# 		if(is_null($id_sheet_user)) {
# 			throw new Exception("Id sheet user not found");
# 		}
# 		$sharedExpenses = new SharedExpenses();
# 		$expenses = new Expenses();
# 		foreach($_POST['row'] as $row) {
# 		    if(empty($row['category_id'])) {
# 		        continue;
#             }
# 		    // sanity check
#             $found = false;
#             $e = null;
#             foreach ($sheet['expenses'] as $e) {
#                 if ($e['id'] == $row['id']) {
#                     $found = true;
#                     break;
#                 }
#             }
#             if(!$found) {
#                 continue;
#             }
#             try {
#                 $cat = $catModel->fetchRow("id = ".$row['category_id'])->toArray();
#                 if(empty($cat)) {
#                     throw new Exception("Category does not exists");
#                 }
#             }
#             catch(Exception $e) {
#                 error_log($e->getMessage());
#                 throw new Exception("Category does not exists");
#             }
#             if ($cat['user_owner'] != $_SESSION['user_id']) {
#                 error_log("category does not belong to user ".$_SESSION['user_id']);
#                 throw new Exception("Category does not belong to user");
#             }
#             // add expense with category received
#             $expenses->insert(array(
#                 'user_owner' => $_SESSION['user_id'],
#                 'amount' => -$e['amount'] / $changeRate,
#                 'category' => $row['category_id'],
#                 'note' => $e['note'],
#                 'date' => $e['date'],
#             ));
#             // update closed
#             $sharedExpenses->update(array('copied' => 1), 'id = ' . $e['id']);
#         }
# 		$this->redirect('/expenses');
#
# 	}
#

class SheetAddUserview(SheetView):
	...

# public function adduserAction() {
# 		try {
# 			$id_sheet= $this->getRequest()->getParam('id_sheet');
# 			$sheetUser = new SharedExpensesSheetUsers();
# 			$userModel = new Users();
# 			$sheet = $this->sheetModel->get_by_unique_id($id_sheet);
# 			if(empty($sheet)) {
# 				throw new Exception("Sheet with id ".$id_sheet." not found");
# 			}
# 			$user = $this->getRequest()->getParam('user');
# 			$user_id = null;
# 			$email = null;
# 			$registered = true;
# 			try {
# 				$u = $userModel->findUserByLogin($user);
# 				error_log(print_r($u,true));
# 				if(empty($u)) {
# 					error_log("user not found by login");
# 					$u = $userModel->findUserByEmail($user);
# 					error_log(print_r($u,true));
# 					if(empty($u)) {
# 						error_log("user not found by email");
# 						$validator = new Zend_Validate_EmailAddress();
# 						if (!$validator->isValid($email)) {
# 							throw new Exception("Invalid email address");
# 						}
# 						error_log("settings user as email ".$user);
# 						$email = $user;
# 						$registered = false;
# 					}
# 				}
# 				if (!empty($u)) {
# 					error_log("\$u is set, ".print_r($u,true));
# 					$user_id = $u['id'];
# 					$email = $u['email'];
# 				}
# 				$data = array(
# 					'id_sheet' => $sheet['id'],
# 					'id_user' => $user_id,
# 					'email' => $email
# 				);
# 				// @todo control duplicates
# 				$sheetUser->insert($data);
# 				$this->sendUserAdded($id_sheet, $email, $sheet['name'], $registered);
# 			}
# 			catch(Exception $e) {
# 				error_log("exception caught when adding user to sheet: ".$e->getMessage());
# 				$this->_request->setPost(array(
# 						'id' => $id_sheet,
# 						'errors' => array($e->getMessage())
# 				));
# 				return $this->_forward("view", "sheets");
# 			}
# 		}
# 		catch(Exception $e) {
# 			error_log($e->getMessage());
# 			$this->view->assign("errors", array($e->getMessage()));
# 		}
# 		$this->redirect('/sheets/view/id/'.$id_sheet);
# 	}


class SheetCloseView(SheetView):
	def get_success_url(self):
		return reverse_lazy('sheet_view', kwargs={'slug': self.object.unique_id})
