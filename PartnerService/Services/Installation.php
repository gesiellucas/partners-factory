<?php

namespace App\Libraries\PartnerService\Services;

use App\Libraries\PartnerService\Exceptions\PartnerResponse;
use App\Libraries\PartnerService\Repository\GetrakRepository;
use App\Libraries\PartnerService\Repository\I4proRepository;
use App\Libraries\PartnerService\Repository\InstallationRepository;
use App\Libraries\PartnerService\Repository\LogicaRepository;
use App\Libraries\PartnerService\Status\BaseStatus;
class Installation extends BaseStatus
{
    protected $repository;

    public function __construct( array $data ) {        
        parent::__construct();

        $this->execute($data);
    }

    private function execute($data)
    {        
        
        foreach($data as $value) {
            
            try {

                $this->validate($value, 'partner_service_installation');

                $serviceOrderID = $value['proposal'];
                $monitoring = $value['tracker']['monitoring_id'] ?? 2;

                $repository = new InstallationRepository($value);

                // Valid IMEI exists
                $hasTracker = $this->trackerRepository->getIMEI($repository->prepareGetIMEI());

                if(! empty($hasTracker)) {
                    $trackerID = $hasTracker['tracker_id'];
                    $this->trackerRepository->update($trackerID, $repository->prepareUpdateTracker());
                } else {
                    $trackerLotID = $this->trackerLotRepository->create($repository->prepareCreateTrackerLot());
                    $trackerID = $this->trackerRepository->create($repository->prepareCreateTracker($trackerLotID));

                    // Create traacker interaction
                    $this->interactionRepository->create($repository->prepareCreateTrackerInteraction());
                }

                // Relate Schedules to Trackers
                $this->scheduleTrackerRepository->create($repository->prepareRelateScheduleTracker($trackerID));

                // Add I4PRO Install
                I4proRepository::serviceInstall($repository->prepareServiceInstall($trackerID));

                // Update Schedule in I4PRO
                $i4pro = I4proRepository::updateSchedule($repository->prepareInstalledI4Pro());

                if(! empty($i4pro)) {
                    // Concluded schedule
                    $this->scheduleRepository->update($repository->getScheduleID(), $repository->prepareScheduleInstalled($i4pro));
                }

                switch($monitoring) {
                    case 1:
                        
                        // Handle Getrak Process to installation (Send email)
                        (new GetrakRepository())->handleInstallationService($repository->prepareGetrakInstallation());
                        break;

                    case 2:

                        // Handle Logica Process to installation (Send email)
                        (new LogicaRepository())->handleInstallationService($repository->prepareLogicaInstallation());
                        break;
                        
                    default:
                        break;
                }
                
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