<?php

namespace App\Libraries\PartnerService\Status;

use App\Libraries\PartnerService\Exceptions\PartnerResponse;
use App\Libraries\PartnerService\Repository\CanceledRepository;
use App\Libraries\PartnerService\Status\BaseStatus;

class Canceled extends BaseStatus
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
                    
                $serviceOrderID = $value['proposal'];
                $repository = new CanceledRepository($value);
               
                if(! $repository->belongsToPartner(27)) {
                    throw new \Exception('Erro ao indentificar Ordem de Serviço');
                }

                // update service order
                $this->serviceOrderRepository->update($serviceOrderID, $repository->prepareUpdateStatus(3, 13));

                // Create interaction
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