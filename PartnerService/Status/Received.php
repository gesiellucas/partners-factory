<?php

namespace App\Libraries\PartnerService\Status;

use App\Libraries\PartnerService\Exceptions\PartnerResponse;
use App\Libraries\PartnerService\Repository\ReceivedRepository;

class Received extends BaseStatus
{

    public function __construct(array $data)
    {        
        parent::__construct();
        $this->execute($data);
    }
    
    private function execute(array $data)
    {
        
        foreach($data as $value) {

            try {
                // Validate
                $this->validate($value, 'partner_received');
                
                $serviceOrderID = $value['proposal'];

                // Get Repository
                $repository = new ReceivedRepository($value);

                // Update Service Order Status
                $this->serviceOrderRepository->update($serviceOrderID, $repository->prepareUpdateStatus());

                // Crate Interaction
                $this->interactionRepository->create($repository->prepareCreateInteractions());

                PartnerResponse::pushSuccess("OS: " . $value['proposal']. " recebida.");

            } catch (\Exception $th) {
                
                PartnerResponse::pushError("OS: " . $value['proposal']. ", erro: " .$th->getMessage());

            } catch (\CodeIgniter\Database\Exceptions\DatabaseException $db) {
                
                PartnerResponse::pushError("OS: " . $value['proposal']. ", erro: " . $db->getMessage());

            }
            
        }
    }
}