from django.views.generic import CreateView
from django.contrib.auth import authenticate, login, logout
from django.http import HttpResponseRedirect
from django.shortcuts import redirect
from django.urls import reverse_lazy
from django.utils.translation import gettext_lazy as _
from django.contrib.auth.models import User
from moxie.forms import RegisterForm
from django.contrib import messages


def login_view(request):
	username = request.POST.get("username")
	password = request.POST.get("password")
	user = authenticate(request, username=username, password=password)
	if user is not None and password is not None:
		login(request, user)
		return redirect(reverse_lazy('expenses'))
	else:
		messages.error(request, _('Login incorrect'))
		return redirect(reverse_lazy('index'))


def logout_view(request):
	logout(request)
	return HttpResponseRedirect(reverse_lazy('index'))


class RegisterView(CreateView):
	model = User
	form_class = RegisterForm
	template_name = 'login/register.html'

	def get_success_url(self):
		messages.info(self.request, _('Registered successfully.'))
		return reverse_lazy('index')


class ForgotPasswordView():
	pass


# <?php
#
# include("application/3rdparty/simple-php-captcha/simple-php-captcha.php");
#
# class LoginController extends Zend_Controller_Action {
# 	/**
# 	 * @desc	Shows the new user form
# 	 * @author	hmeza
# 	 * @since	2011-04-25
# 	 */
# 	public function newuserAction() {
# 		$this->view->assign('form', $this->getForm());
# 		$this->view->assign('message', '&nbsp;');
# 		// generate captcha
# 		$_SESSION['captcha'] = captcha();
# 	}
#
# 	/**
# 	 * @desc	Registers an user and populates categories with demo data.
# 	 * @author	hmeza
# 	 * @since	2011-04-25
# 	 */
# 	public function registeruserAction() {
# 		$st_form = $this->getRequest()->getPost();
# 		try {
# 			if(empty($st_form['login'])) {
# 				throw new Exception("Empty username");
# 			}
# 			if(empty($st_form['email'])) {
# 				throw new Exception("Empty email");
# 			}
# 			if(!isset($_SESSION['captcha']['code'])
# 					|| strtoupper($_SESSION['captcha']['code']) != strtoupper($st_form['captcha'])) {
# 				throw new Exception("Verify the captcha.");
# 			}
# 			$data = array(
# 					'login'		=> $st_form['login'],
# 					'password'	=> md5($st_form['password']),
# 					'email'		=> $st_form['email'],
# 					'confirmed' => 0
# 			);
# 			$i_lastInsertId = $this->users->insert($data);
# 			$this->categories->insertCategoriesForRegisteredUser($i_lastInsertId);
#
# 			$this->sendRegisterEmail($i_lastInsertId, $st_form);
# 		}
# 		catch (Zend_Db_Statement_Exception $e) {
# 			$_SESSION['captcha'] = captcha();
# 			$this->view->assign('message', 'Duplicated username or email');
# 			$this->view->assign('form', $this->getForm());
# 			$this->render('newuser');
# 		}
# 		catch (Exception $e) {
# 			error_log("Exception caught in ".__METHOD__." on line ".$e->getLine().": ".$e->getMessage());
# 			error_log('MOXIE: Cannot populate user with demo categories');
# 			$_SESSION['captcha'] = captcha();
# 			$this->view->assign('message', $e->getMessage());
# 			$this->view->assign('form', $this->getForm());
# 			$this->render('newuser');
# 		}
# 	}
#
# 	public function confirmAction() {
# 		global $st_lang;
# 		$user = $this->users->fetchRow('login  = "'.$this->getRequest()->getParam('login').'"');
# 		$key = $this->getRequest()->getParam('hash');
# 		if($this->users->validateKey($key, $user->toArray())) {
# 			// redirect to home with message OK!!! Congrats!!!
# 			$this->users->confirm($user['id']);
# 			return $this->_forward("index", "Index", "module", array('message' => $st_lang['registration_confirmed']));
# 		}
# 		else {
# 			$this->view->assign('message', 'Unrecognized user or incorrect hash');
# 			$this->view->assign('form', $this->getForm());
# 			$this->render('newuser');
# 		}
# 	}
#
# 	/**
# 	 * @desc	Generates forgot password form (just login request).
# 	 * @author	hmeza
# 	 * @since	2011-06-21
# 	 */
# 	private function getForgotPasswordForm() {
# 		include_once('Zend/Form.php');
# 		$form  = new Zend_Form();
#
# 		$form->setAction('/login/forgotpassword')
# 			->setMethod('get');
#
# 		$form->addElement('text', 'login', array('label' => 'Login', 'value' => ''));
# 		$form->addElement('submit','submit', array('label' => 'Enviar'));
# 		return $form;
# 	}
#
# 	/**
# 	 * Sends the forgot password email.
# 	 */
# 	public function forgotpasswordAction() {
# 		$s_login = $this->getRequest()->getParam('login');
# 		if (!empty($s_login)) {
# 			// retrieve user email from login if exists. If not, sleep 10 and return error
# 			try {
# 				$email = $this->users->fetchRow($this->users->select()
# 					->where('login = ?', $s_login));
# 				if (empty($email)) {
# 					sleep(10);
# 					$s_infoText = 'Error en el login proporcionado. Por favor, intentalo de nuevo.';
# 				}
# 				else {
# 					$s_server = Zend_Registry::get('config')->moxie->settings->url;
# 					$s_site = Zend_Registry::get('config')->moxie->app->name;
# 					$email = $email['email'];
# 					$key = $this->users->generateKey($s_login);
# 					$subject = $s_site.' - Restore password';
# 					$body = 'Si recibes este email es o bien porque estás intentando restaurar tu contraseña. En tal caso,
# por favor pulsa el siguiente link:
#
# You are receiving this email because you want to restore your password. If so, please click
# the following link:
#
# '.$s_server.'/login/recoverpassword/k/'.$key.'
#
# '.$s_site.'
# '.$s_server.'
# ';
# 					$headers = 'From: Moxie <'.Zend_Registry::get('config')->moxie->email.'>' . "\r\n" .
# 							'Reply-To: '.Zend_Registry::get('config')->moxie->email. "\r\n" .
# 							'X-Mailer: PHP/' . phpversion() . "\r\n";
# 					$result = mail($email, $subject, $body, $headers);
# 					$this->view->assign('text', $email." ".(($result)?"true":"false"));
#
# 					$s_infoText = 'Se ha enviado un email a la cuenta de correo que nos proporcionaste. Por favor, sigue las instrucciones ahí descritas.';
# 				}
# 			}
# 			catch (Exception $e) {
# 				error_log("Exception caught in ".__CLASS__."::".__FUNCTION__." on line ".$e->getLine().": ".$e->getMessage());
# 				$s_infoText = 'Error al conectar con el servidor de email. Por favor, intentalo mas tarde.';
# 			}
# 		}
# 		else {
# 			$s_infoText = 'Por favor, introduce el login para enviar un mail a tu cuenta y recuperar tu password.';
# 		}
# 		$this->view->assign('text', $s_infoText);
# 		$this->view->assign('form', $this->getForgotPasswordForm());
# 	}
#
# 	/**
# 	 * Tries to recover a password.
# 	 */
# 	public function recoverpasswordAction() {
# 		$s_key = $this->getRequest()->getParam('k');
# 		if (!empty($s_key)) {
# 			$st_result = $this->users->checkKey($s_key);
# 			$_SESSION['user_id'] = $st_result['id'];
# 			$_SESSION['user_name'] = $st_result['login'];
# 			$_SESSION['user_lang'] = $st_result['language'];
# 			$this->view->assign('loginMessage', 'login OK');
# 			$this->_helper->redirector('index','users');
# 		}
#  		$this->view->assign('text', print_r($st_result,true));
# 		$this->_helper->viewRenderer('login/forgotpassword', null, true);
# 		$this->_helper->redirector('forgotpassword', 'login');
# 	}
#
# 	/**
# 	 * Email user with register data.
# 	 * @param $i_lastInsertId
# 	 * @param $st_form
# 	 * @throws Zend_Db_Table_Exception
# 	 * @throws Zend_Exception
# 	 */
# 	protected function sendRegisterEmail($i_lastInsertId, $st_form) {
# 		$user = $this->users->find($i_lastInsertId)->toArray();
# 		$hash = $this->users->getValidationKey($user[0]);
#
# 		$s_server = Zend_Registry::get('config')->moxie->settings->url;
# 		$s_site = Zend_Registry::get('config')->moxie->app->name;
# 		$email = $st_form['email'];
# 		$subject = $s_site . ' - ¡Bienvenido/a!';
# 		$body = 'Bienvenido/a a Moxie. Te has registrado con los siguientes datos:
# Welcome to Moxie. You have registered with the following data:
#
# Login: ' . $st_form['login'] . '
# Password: ' . $st_form['password'] . '
#
# Please click here to confirm your account: ' . Zend_Registry::get(
# 						'config'
# 				)->moxie->settings->url . '/login/confirm/login/' . $st_form['login'] . '/hash/' . $hash . '
#
# ' . $s_site . '
# ' . $s_server . '
# ';
# 		$headers = 'From: Moxie <moxie@dootic.com>' . "\r\n" .
# 				'Reply-To: moxie@dootic.com' . "\r\n" .
# 				'X-Mailer: PHP/' . phpversion() . "\r\n";
# 		$result = mail($email, $subject, $body, $headers);
# 	}
# }
# ?>
