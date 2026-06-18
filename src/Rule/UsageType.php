<?php

declare(strict_types=1);

namespace Boundwize\Pyrameter\Rule;

enum UsageType: string
{
    case ClassLike = 'class';
    case Function  = 'function';
}
