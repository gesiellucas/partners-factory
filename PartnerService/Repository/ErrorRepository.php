<?php

namespace App\Libraries\PartnerService\Repository;

class ErrorRepository
{
    protected $dataset;

    public function __construct(
        protected array $requestData,
        protected $repository = new \App\Repositories\ServicesOrdersRepository()
    )  {
        $this->dataset = $this->setDataset($requestData);
    }

    private function setDataset($data)
    {
        return $this->repository->getServiceOrderByID($data['proposal']) 
            ?? throw new \Exception('Ordem de Serviço não localizada.');
    }

    public function getDataset()
    {
        return [
            'service_order_id' => $serviceOrderID,
            'partner_id' => $partnerID
        ] = $this->dataset;
    }    

    public function prepareUpdateStatus($status, $subStatus): array
    {
        return [
            'status' => $status,
            'status_interaction' => $subStatus
        ];
    }

    public function prepareCreateInteractions($message): array
    {
        return [ 
            'status'           => 'Erro ao realizar serviço',
            'type'             => 'Erro parceiro',
            'message'          => $message,
            'user_id'          => $this->dataset['user_id'],
            'contract_id'      => $this->dataset['contract_id'],
            'service_order_id' => $this->dataset['service_order_id'],
            'contract_status'  => $this->dataset['contract_status']
        ];
    }

    public function belongsToPartner($partnerID): bool
    {
        ['partner_id' => $partner] = $this->getDataset();

        return $partner == $partnerID;
    }
    
}