<?php

namespace App\Libraries\PartnerService\Services;

use App\Libraries\PartnerService\Exceptions\PartnerResponse;
use App\Libraries\PartnerService\Repository\GetrakRepository;
use App\Libraries\PartnerService\Repository\I4proRepository;
use App\Libraries\PartnerService\Repository\LogicaRepository;
use App\Libraries\PartnerService\Repository\MaintenanceRepository;
use App\Libraries\PartnerService\Status\BaseStatus;
use App\Repositories\LogsRepository;

class Maintenance extends BaseStatus
{

    public function __construct(array $data)
    {
        parent::__construct();
        $this->execute($data);
    }

    private function execute($data)
    {
        foreach ($data as $value) {

            try {

                $this->validate($value, 'partner_service_maintenance');

                $serviceOrderID = $value['proposal'];

                $repository = new MaintenanceRepository($value);

                $this->handleCurrentTracker($value, $repository);

                $this->handleNewTracker($repository);

                $this->externalService('i4pro', $repository);

                switch($repository->monitoringType()) {

                    case 1:
                        $this->externalService('getrak', $repository);
                        break;
                    case 2:
                        $this->externalService('logica', $repository);
                        break;
                    default:
                        break;
                }

                // Update Service Order Status
                $this->serviceOrderRepository->update($serviceOrderID, $repository->prepareUpdateStatus());

                // Update Service Order Status
                $this->serviceOrderRepository->update($serviceOrderID, $repository->prepareUpdateStatus(12));

                // Concluded schedule
                $this->scheduleRepository->update($repository->getScheduleID(), $repository->prepareScheduleMaintened());

                PartnerResponse::pushSuccess("OS: " . $value['proposal'] . " recebida.");
            } catch (\Exception $th) {

                PartnerResponse::pushError("OS: " . $value['proposal'] . ", erro: " . $th->getTraceAsString());
            } catch (\CodeIgniter\Database\Exceptions\DatabaseException $db) {

                PartnerResponse::pushError("OS: " . $value['proposal'] . ", erro: " . $db->getTraceAsString());
            } catch (\Throwable $error) {

                PartnerResponse::pushError("OS: " . $value['proposal'] . ", erro: " . $error->getMessage());
            }
        }
    }

    private function handleCurrentTracker($value, $repository)
    {
        // Verify if imei exists
        $tracker = $this->trackerRepository->getTracker($value['current_tracker']);

        if (empty($tracker)) {
            $this->trackerRepository->hasNoTracker($value['current_tracker'], $repository->getDataset());
        }
        
        $current_trackers = $repository->prepareUnrelateScheduleTracker();

        if (! empty($current_trackers)) {

            // Save log from current tracker
            (new LogsRepository())->create(['action' => 'delete-tracker', 'description' => 'Exclui equipamento' . json_encode($repository->prepareUnrelateScheduleTracker())]);

            // Delete schedule tracker
            $this->scheduleTrackerRepository->delete($current_trackers);

            // Update current Tracker
            $this->trackerRepository->update($repository->prepareDisableTracker(), ['policy_id' => null, 'contract_id' => null]);
        }

    }

    private function handleNewTracker($repository)
    {
         // Valid IMEI exists
         $hasTracker = $this->trackerRepository->getIMEI($repository->prepareGetIMEI());

         if (! empty($hasTracker)) {
             $trackerID = $hasTracker['tracker_id'];
             $this->trackerRepository->update($hasTracker['tracker_id'], $repository->prepareUpdateTracker());
         } else {
             $trackerLotID = $this->trackerLotRepository->create($repository->prepareCreateTrackerLot());
             $trackerID = $this->trackerRepository->create($repository->prepareCreateTracker($trackerLotID));
         }

         // Relate Schedules to Trackers
         $this->scheduleTrackerRepository->create($repository->prepareRelateScheduleTracker($trackerID));

         // Crate Interaction
         $this->interactionRepository->create($repository->prepareCreateInteractions());

         return $trackerID;
    }

    private function externalService($service, $repository)
    {
        switch($service) {
            case 'i4pro':
                // Insert i4pro
                I4proRepository::serviceMaintenance($repository->prepareServiceMaintenance());
        
                // Update Schedule in I4PRO
                I4proRepository::updateSchedule($repository->prepareUpdatedI4Pro());
                break;
            case 'getrak':
                // Getrak Steps
                (new GetrakRepository())->handleMaintenanceService($repository->prepareGetrakMaintenance());
                break;

            case 'logica':
                // Logica Steps
                (new LogicaRepository())->handleMaintenanceService($repository->prepareLogicaMaintenance());
                break;
                
            default:
                break;
        }
    }
}
