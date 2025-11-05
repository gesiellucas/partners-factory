<?php

namespace App\Libraries\PartnerService\Repository;

class CompleteRepository
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
            'partner_id' => self::PARTNER_ID,  
            'policy_id' => $data['policy_id']
        ];

        return $this->repository->findWhere($data['proposal'], $where);
    }
}