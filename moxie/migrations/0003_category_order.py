# Generated by Django 3.1 on 2023-04-19 14:59

from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('moxie', '0002_auto_20230419_0019'),
    ]

    operations = [
        migrations.AddField(
            model_name='category',
            name='order',
            field=models.SmallIntegerField(default=1),
        ),
    ]