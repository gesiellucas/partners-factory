<?php

namespace App\Libraries\PartnerService\Partners;

use App\Libraries\PartnerService\Abstracts\PartnerAbstract;
use App\Libraries\PartnerService\Concrete\PartnerStandard;
use App\Libraries\PartnerService\Interfaces\PartnerInterface;

class Sat extends PartnerAbstract
{
    protected function build(): PartnerInterface
    {
        return new PartnerStandard();
    }
}