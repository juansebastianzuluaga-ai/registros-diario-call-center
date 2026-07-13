<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Internal signal used by ZenniaClient to detect a session that died mid-batch
 * (the request got bounced back to the login page). Caught internally to
 * trigger a single re-login-and-retry; never expected to escape ZenniaClient.
 */
class ZenniaSessionExpiredException extends RuntimeException
{
}
