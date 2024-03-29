"""moxie URL Configuration

The `urlpatterns` list routes URLs to views. For more information please see:
    https://docs.djangoproject.com/en/3.0/topics/http/urls/
Examples:
Function views
    1. Add an import:  from my_app import views
    2. Add a URL to urlpatterns:  path('', views.home, name='home')
Class-based views
    1. Add an import:  from other_app.views import Home
    2. Add a URL to urlpatterns:  path('', Home.as_view(), name='home')
Including another URLconf
    1. Import the include() function: from django.urls import include, path
    2. Add a URL to urlpatterns:  path('blog/', include('blog.urls'))
"""
from django.contrib import admin
from django.urls import path, register_converter, include
from moxie.views.login import login_view, logout_view, RegisterView, password_change, password_reset_confirm
from moxie.views.expenses import ExpensesView, ExpenseView, ExpenseAddView, ExpenseDeleteView
from moxie.views.incomes import IncomesView, IncomeView, IncomeAddView, IncomeDeleteView
from moxie.views.budgets import BudgetDeleteView, BudgetSnapshotView
from moxie.views.users import UserConfigurationView, user_password_change, UserUpdateView
from moxie.views.sheets import SheetsView, SheetView, SheetCloseView, SheetExpenseDeleteView, SheetCreateView, \
    SharedExpenseView, SheetCopyView
from moxie.views.tags import TagView
from moxie.views.categories import CategoryView, categories_bulk_update, CategoryBudgetView, CategoryAddView, \
    CategoryDeleteView
from moxie.views.stats import StatsView
from django.views.generic import TemplateView
from . import converters
from django.urls import re_path
from django.views.static import serve
from django.conf import settings

register_converter(converters.FourDigitYearConverter, 'yyyy')


urlpatterns = [
    path('admin/', admin.site.urls),
    path('expenses/<int:pk>/delete/', ExpenseDeleteView.as_view(), name='expenses_delete'),
    path('expenses/<int:pk>/', ExpenseView.as_view(), name='expenses_edit'),
    path('expenses/add/', ExpenseAddView.as_view(), name='expenses_add'),
    path(r'expenses/year/<yyyy:year>/month/<int:month>/', ExpensesView.as_view(), name='expenses_with_parameters'),
    path('expenses/', ExpensesView.as_view(), name='expenses'),

    path('incomes/<int:pk>/delete/', IncomeDeleteView.as_view(), name='incomes_delete'),
    path('incomes/<int:pk>/', IncomeView.as_view(), name='incomes_edit'),
    path('incomes/add/', IncomeAddView.as_view(), name='incomes_add'),
    path(r'incomes/year/<yyyy:year>/', IncomesView.as_view(), name='incomes_with_parameters'),
    path('incomes/', IncomesView.as_view(), name='incomes'),

    path('budget/<int:pk>/delete/', BudgetDeleteView.as_view(), name='budget_delete'),

    path('stats/year/<yyyy:year>/', StatsView.as_view(), name='stats_year'),
    path('stats/', StatsView.as_view(), name='stats'),

    path('about', TemplateView.as_view(template_name='index/about.html'), name='about'),
    path('finance', TemplateView.as_view(template_name='finance/index.html'), name='finance'),

    path('users', UserConfigurationView.as_view(), name='users'),
    path('users/update/', UserUpdateView.as_view(), name='users_update'),
    path('users/password-change/', user_password_change, name='password-change'),
    path('tag', TagView.as_view(), name='tags'),
    path('category/order/', categories_bulk_update, name='category_order'),
    path('category/<int:pk>/budget/', CategoryBudgetView.as_view(), name='category_budget_edit'),
    path('category/<int:pk>/delete/', CategoryDeleteView.as_view(), name='category_delete'),
    path('category/<int:pk>/', CategoryView.as_view(), name='category_edit'),
    path('category/add/', CategoryAddView.as_view(), name='category_add'),
    path('category/', CategoryView.as_view(), name='category_view'),
    path('budgets/snapshot', BudgetSnapshotView.as_view(), name='budget_snapshot'),

    path('sheets/add/', SheetCreateView.as_view(), name='sheet_add'),
    path('sheets/<slug:unique_id>/users/', SheetView.as_view(), name='sheet_add_user'),
    path('sheets/<slug:unique_id>/expenses/', SharedExpenseView.as_view(), name='sheet_expenses'),
    path('sheets/<slug:unique_id>/close/', SheetCloseView.as_view(), name='sheet_close'),
    path('sheets/<slug:unique_id>/copy/', SheetCopyView.as_view(), name='sheet_copy'),
    path('sheets/<slug:unique_id>/<int:pk>/delete/', SheetExpenseDeleteView.as_view(), name='sheet_expense_delete'),
    path('sheets/<slug:unique_id>/', SheetView.as_view(), name='sheet_view'),
    path('sheets/', SheetsView.as_view(), name='sheet_list'),
    path('login', login_view, name='login'),
    path('logout', logout_view, name='logout'),
    path('register', RegisterView.as_view(), name='register'),
    path('forgot-password', password_change, name='forgot-password'),
    path('password-reset-confirm/<uidb64>/<token>', password_reset_confirm, name='password_reset_confirm'),
    path('captcha/', include('captcha.urls')),

    path('about', TemplateView.as_view(template_name='index/about.html'), name='about'),
    path('finance', TemplateView.as_view(template_name='finance/index.html'), name='finance'),

    re_path(r'^static/(?P<path>.*)$', serve,{'document_root': settings.STATIC_ROOT}),

    path('', TemplateView.as_view(template_name='index/index.html'), name='index'),
]
