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
from moxie.views.login import login_view, logout_view, RegisterView#, ForgotPasswordView
from moxie.views.views import ExpensesView, ExpenseView, ExpenseAddView, ExpenseDeleteView
from moxie.views.incomes import IncomesView, IncomeView, IncomeAddView, IncomeDeleteView
from moxie.views.budgets import BudgetView, BudgetDeleteView
from moxie.views.users import UserConfigurationView
from moxie.views.tags import TagView
from moxie.views.categories import CategoryView, categories_bulk_update
from moxie.views.stats import StatsView
from django.views.generic import TemplateView
from . import converters


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
    path('budget/', BudgetView.as_view(), name='budget'),

    path('stats/year/<yyyy:year>/', StatsView.as_view(), name='stats'),
    path('stats/', StatsView.as_view(), name='stats'),

    path('about', TemplateView.as_view(template_name='index/about.html'), name='about'),
    path('finance', TemplateView.as_view(template_name='finance/index.html'), name='finance'),
    # TODO
    path('users', UserConfigurationView.as_view(), name='users'),
    path('tag', TagView.as_view(), name='tags'),
    path('category/order/', categories_bulk_update, name='category_order'),
    path('category/<int:pk>/', CategoryView.as_view(), name='category_edit'),
    path('category/', CategoryView.as_view(), name='category_view'),
     # TODO
    path('sheets', UserConfigurationView.as_view(), name='users'),

    path('login', login_view, name='login'),
    path('logout', logout_view, name='logout'),
    path('register', RegisterView.as_view(), name='register'),
    # path('forgot-password', ForgotPasswordView.as_view(), name='forgot-password'),
    path('captcha/', include('captcha.urls')),

    path('about', TemplateView.as_view(template_name='index/about.html'), name='about'),
    path('', TemplateView.as_view(template_name='index/index.html'), name='index'),
]
