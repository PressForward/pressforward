<?php

namespace GenderEngine;

use Eloquent\Enumeration\Enumeration;

final class Gender extends Enumeration
{
    const MALE    = 'MALE';
    const FEMALE  = 'FEMALE';
    const UNKNOWN = 'UNKNOWN';
}

