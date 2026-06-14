<?php

declare(strict_types=1);

namespace Pyrameter\Config;

enum ViolationMode
{
    case Warn;
    case Fail;
}
