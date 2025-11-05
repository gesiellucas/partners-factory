<?php

namespace App\Libraries\PartnerService\Status;

use App\Libraries\PartnerService\Exceptions\PartnerResponse;
use App\Libraries\PartnerService\Repository\I4proRepository;
use App\Libraries\PartnerService\Repository\InProgressRepository;

class InProgress extends BaseStatus
{
    public function __construct($data)
    {        
        parent::__construct();
        $this->execute($data);
    }
    
    /**
     * TODO Redundancy in schedule by service order
     *
     * @param array $data
     * @return void
     */
    private function execute(array $data)
    {

        foreach($data as $value) {

            try {
                
                $this->validate($value, 'partner_in_progress');

                $serviceOrderID = (int) $value['proposal'];
                
                $repository = new InProgressRepository($value);

                // Create Schedule
                $scheduleID = $this->scheduleRepository->create($repository->prepareCreateSchedule());

                // Update Service Order Status
                $this->serviceOrderRepository->update($serviceOrderID, $repository->prepareUpdateStatus());

                // Crate Interaction
                $this->interactionRepository->create($repository->prepareCreateInteractions());
                
                // Create schedule on I4PRO
                $i4pro = I4proRepository::saveSchedule($repository->prepareSyncI4pro());                

                if(! empty($i4pro)) {
                    // Update Schedule
                    $this->scheduleRepository->update($scheduleID, $repository->prepareUpdateSchedule($i4pro));
                }

                PartnerResponse::pushSuccess("OS: " . $value['proposal']. " recebida.");

            } catch (\Exception $th) {
                
                PartnerResponse::pushError("OS: " . $value['proposal']. ", erro: " .$th->getMessage());

            } catch (\CodeIgniter\Database\Exceptions\DatabaseException $db) {
                
                PartnerResponse::pushError("OS: " . $value['proposal']. ", erro: " . $db->getMessage());

            }  
        }
    }
}