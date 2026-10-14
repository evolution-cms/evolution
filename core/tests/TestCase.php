<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Tests\Support\HonoursErrorSuppression;

abstract class TestCase extends BaseTestCase
{
    use HonoursErrorSuppression;
}
