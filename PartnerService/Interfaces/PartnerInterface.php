<?php

namespace App\Libraries\PartnerService\Interfaces;

/**
 * Regra para que os fabricantes de produtos {\App\Libraries\PartnerService\Factory\Concrete\} possa usar como referencia
 */
interface PartnerInterface
{
    public static function send($data);
    public static function handle($data);
}