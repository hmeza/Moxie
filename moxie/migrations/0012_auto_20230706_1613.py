# Generated by Django 3.1 on 2023-07-06 16:13

from django.db import migrations, models
import django.db.models.deletion


class Migration(migrations.Migration):

    dependencies = [
        ('moxie', '0011_auto_20230704_1016'),
    ]

    operations = [
        migrations.RemoveField(
            model_name='favourite',
            name='id_transaction',
        ),
        migrations.AddField(
            model_name='favourite',
            name='transaction',
            field=models.ForeignKey(default=None, on_delete=django.db.models.deletion.PROTECT, to='moxie.transaction'),
        ),
    ]