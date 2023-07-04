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
from django.urls import path
from moxie.views.views import ExpensesView, ExpenseView, ExpenseAddView, UserConfigurationView, login_view,\
    logout_view
from django.views.generic import TemplateView

urlpatterns = [
    path('admin/', admin.site.urls),
    path('expenses/<int:pk>/', ExpenseView.as_view(), name='expenses_edit'),
    path('expenses/add/', ExpenseAddView.as_view(), name='expenses_add'),
    path('expenses/year/2023/month/4/', ExpensesView.as_view(), name='expenses_with_parameters'),
    path('expenses/', ExpensesView.as_view(), name='expenses'),

    # TODO
    path('incomes/', ExpensesView.as_view(), name='incomes'),
    path('stats/', ExpensesView.as_view(), name='stats'),

    path('about', TemplateView.as_view(template_name='index/about.html'), name='about'),
    path('users', UserConfigurationView.as_view(), name='users'),
    path('login', login_view, name='login'),
    path('logout', logout_view, name='logout'),
    path('', TemplateView.as_view(template_name='index/index.html'), name='index'),
]
