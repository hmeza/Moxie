from django.db.backends.signals import connection_created
from django.dispatch import receiver


# @receiver(connection_created)
# def set_character_set_results(*args, connection, **kwargs):
#     with connection.cursor() as cursor:
#         cursor.execute('SET CHARACTER_SET_RESULTS = \'latin1\'')
