<?php

namespace App\Libraries\PartnerService\Repository;

class ReceivedRepository
{
    protected $dataset;

    const PARTNER_ID = 27;

    public function __construct(
        protected array $requestData,
        protected $repository = new \App\Repositories\ServicesOrdersRepository()
    )  {
        $this->dataset = $this->setDataset($requestData);
    }

    private function setDataset($data)
    {
        $where = [ 
            'so.partner_id' => self::PARTNER_ID,  
            'so.policy_id' => $data['policy_id']
        ];

        return $this->repository->getServiceOrder($data['proposal'], $where) ?? throw new \Exception('Service Order não localizada.');
    }    

    public function prepareUpdateStatus(): array
    {
        return [
            'status' => 2,
            'status_interaction' => 18
        ];
    }

    public function prepareCreateInteractions(): array
    {
        return [ 
            'status' => 'Em andamento',
            'type' => 'Parceiro',
            'message' => 'Recebido pelo parceiro',
            'user_id' => $this->dataset['user_id'],
            'contract_id' => $this->dataset['contract_id'],
            'service_order_id' => $this->dataset['service_order_id'],
            'contract_status' => $this->dataset['contract_status']
        ];
    }
}