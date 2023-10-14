import hashlib
import re
from django.contrib.auth.hashers import BasePasswordHasher, mask_hash
from django.utils.crypto import (
    constant_time_compare
)
from django.utils.translation import gettext_noop as _


class MD5PasswordHasher(BasePasswordHasher):
    """
    The Salted MD5 password hashing algorithm (not recommended)
    """
    algorithm = "md5"

    def encode(self, password, salt):
        assert password is not None
        pass_hashed = hashlib.md5(password.encode()).hexdigest()
        return "%s$%s$%s" % (self.algorithm, salt, pass_hashed)

    def verify(self, password, encoded):
        encoded = re.sub('\$(.*)\$', '$$', encoded)
        algorithm, salt, hash = encoded.split('$', 2)
        assert algorithm == self.algorithm
        encoded_2 = self.encode(password, '')
        return constant_time_compare(encoded, encoded_2)

    def safe_summary(self, encoded):
        algorithm, salt, hash = encoded.split('$', 2)
        assert algorithm == self.algorithm
        return {
            _('algorithm'): algorithm,
            _('salt'): mask_hash(salt, show=2),
            _('hash'): mask_hash(hash),
        }

    def harden_runtime(self, password, encoded):
        pass
