<?php

namespace App\Libraries\PartnerService\Services;

use App\Libraries\PartnerService\Exceptions\PartnerResponse;
use App\Libraries\PartnerService\Repository\GetrakRepository;
use App\Libraries\PartnerService\Repository\I4proRepository;
use App\Libraries\PartnerService\Repository\LogicaRepository;
use App\Libraries\PartnerService\Repository\RemovalRepository;
use App\Libraries\PartnerService\Status\BaseStatus;

class Removal extends BaseStatus
{

    public function __construct(array $data) 
    {        
        parent::__construct();
        $this->execute($data);
    }

    private function execute($data)
    {
        
        foreach($data as $value) {
            try {
                
                $this->validate($value, 'partner_service_removal');
                
                $serviceOrderID = $value['proposal'];
                
                $repository = new RemovalRepository($value);
                
                // Verify if imei exists
                $tracker = $this->trackerRepository->getTracker($value['tracker']);
                
                if(empty($tracker)) {
                    $this->trackerRepository->hasNoTracker($value['tracker'], $repository->getDataset());

                    $tracker = $this->trackerRepository->getTracker($value['tracker']);
                } 
                
                $monitoring = $tracker['monitoring_id'];

                switch($monitoring) {
                    case 1:
                        // Getrak Steps
                        (new GetrakRepository())->handleRemovalService($repository->prepareGetrakRemoval($tracker));
                        break;
                    case 2:
                        // Logica Steps
                        (new LogicaRepository())->handleRemovalService($repository->prepareLogicaRemoval($tracker));
                        break;
                }

                $this->serviceOrderRepository->update($serviceOrderID, $repository->prepareUpdateStatus(4, 19));

                // Crate Interaction
                $this->interactionRepository->create($repository->prepareCreateInteractions());

                // Insert i4pro
                I4proRepository::serviceRemoval($repository->prepareServiceRemoval());
                
                // Update Schedule in I4PRO
                I4proRepository::updateSchedule($repository->prepareRemovedI4Pro());

                // Concluded schedule
                $this->scheduleRepository->update($repository->getScheduleID(), $repository->prepareScheduleRemoval());

                $this->trackerRepository->disable($tracker);

                // Update Service Order Status
                $this->serviceOrderRepository->update($serviceOrderID, $repository->prepareUpdateStatus(4, 11));

                $this->scheduleTrackerRepository->unrelateScheduleTracker($repository->getScheduleID(), $tracker['tracker_id']);
                
                PartnerResponse::pushSuccess("OS: " . $value['proposal']. " recebida.");

            } catch (\Exception $th) {
                
                PartnerResponse::pushError("OS: " . $value['proposal']. ", erro: " .$th->getMessage());

            } catch (\CodeIgniter\Database\Exceptions\DatabaseException $db) {
                
                PartnerResponse::pushError("OS: " . $value['proposal']. ", erro: " . $db->getMessage());

            }
        }
    }
}