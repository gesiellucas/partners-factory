<?php 

namespace App\Libraries\PartnerService\Repository;

class LogicaRepository
{
    public function __construct(
        protected $logica = new \App\Libraries\Logica(),
        protected $logGetrakRepository = new \App\Repositories\LogsGetrakRepository(),
    )  {

    }

    public function handleInstallationService(array $data): void
    {
        // Sync Service Order
        try {
            $this->logica->handleInstallationService($data['imei_tracker'], $data['chassi']);

            $this->logica->syncMember($data['schedule_id']);

        } catch (\Exception $th) {
            $this->logGetrakRepository->saveRequestLogGetrak($data, $th->getMessage(), 'request-log-LOGICA');
        }

    }

    public function handleMaintenanceService(array $data): void
    {
        try{
            $this->logica->handleMaintenanceService($data['imei_tracker'], $data['chassi']);

            $this->logica->syncMember($data['schedule_id']);

        } catch (\Exception $th) {
            $this->logGetrakRepository->saveRequestLogGetrak($data, $th->getMessage(), 'request-log-LOGICA');
        }

        
    }

    public function handleRemovalService(array $data): void
    {
        try{
            $this->logica->handleRemovalService($data['imei_tracker'], $data['chassi']);
        } catch (\Exception $th) {
            $this->logGetrakRepository->saveRequestLogGetrak($data, $th->getMessage(), 'request-log-LOGICA');
        }
    }

}
