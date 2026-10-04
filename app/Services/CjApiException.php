<?php

namespace App\Services;

use RuntimeException;

/**
 * Thrown for both transport failures and CJ's own business-logic failures
 * (CJ can return HTTP 200 with `result: false` / `success: false` in the
 * response envelope — those are treated as failures too, not just non-2xx).
 */
class CjApiException extends RuntimeException {}
