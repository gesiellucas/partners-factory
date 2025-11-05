<?php

namespace App\Libraries\PartnerService\Status;

use App\Repositories\InteractionsRepository;
use App\Repositories\SchedulesRepository;
use App\Repositories\SchedulesTrackersRepository;
use App\Repositories\ServicesOrdersRepository;
use App\Repositories\TrackersLotsRepository;
use App\Repositories\TrackersRepository;

abstract class BaseStatus 
{
    public function __construct(
        protected $serviceOrderRepository = new ServicesOrdersRepository,
        protected $scheduleRepository = new SchedulesRepository,
        protected $interactionRepository = new InteractionsRepository,
        protected $trackerRepository = new TrackersRepository,
        protected $scheduleTrackerRepository = new SchedulesTrackersRepository,
        protected $trackerLotRepository = new TrackersLotsRepository
    ) {

    }
    
    protected function validate($data, $rules)
    {
        $service = service('validation');
        $service->reset();

        return $service->run($data, $rules) == true
            ? true 
            : throw new \Exception('Erro: ' . json_encode($service->getErrors(), JSON_UNESCAPED_UNICODE));
    }
}