<?php

namespace App\Libraries\PartnerService\Status;

use App\Libraries\PartnerService\Exceptions\PartnerResponse;
use App\Libraries\PartnerService\Services\Installation;
use App\Libraries\PartnerService\Services\Maintenance;
use App\Libraries\PartnerService\Services\Removal;

class Complete extends BaseStatus
{
    const SERVICE_INSTALLATION = 2;
    const SERVICE_MAINTENANCE = 3;
    const SERVICE_REMOVAL = 4;
   
    public function __construct($data)
    {        
        parent::__construct();
        $this->execute($data);
    }
    
    /**
     * TODO Remover redundancia de codigo
     *
     * @param array $data
     * @return void
     */
    private function execute(array $data)
    {
        $SO = [];

        foreach($data as $value) {

            try {

                $this->validate($value, 'partner_complete');

                // Get Service Type
                $serviceType = (int) $this->serviceOrderRepository->getServiceType($value['proposal'])['service_id'];

                // Attribute to SO array
                $SO[$serviceType][] = $value;

            } catch (\Exception $th) {
                
                PartnerResponse::pushError("OS: " . $value['proposal']. ", erro: " .$th->getMessage());

            } catch (\CodeIgniter\Database\Exceptions\DatabaseException $db) {
                
                PartnerResponse::pushError("OS: " . $value['proposal']. ", erro: " . $db->getMessage());

            }
        }

        if(! empty($SO[self::SERVICE_INSTALLATION])) {
            new Installation($SO[self::SERVICE_INSTALLATION]);
        }

        if(! empty($SO[self::SERVICE_MAINTENANCE])) {
            new Maintenance($SO[self::SERVICE_MAINTENANCE]);
        }

        if(! empty($SO[self::SERVICE_REMOVAL])) {
            new Removal($SO[self::SERVICE_REMOVAL]);
        }

    }
}