<?php

namespace App\Libraries\PartnerService\Status;

use App\Libraries\PartnerService\Exceptions\PartnerResponse;
use App\Libraries\PartnerService\Repository\ErrorRepository;

class Error extends BaseStatus
{   
    public function __construct($data)
    {        
        parent::__construct();
        $this->execute($data);
    }
    
    /**
     * TODO Remover redundancia de codigo
     *
     * @param array $data
     * @return void
     */ 
    private function execute(array $data)
    {
        $SO = [];

        foreach($data as $value) {
            try {

                $this->validate($value, 'partner_service_error');
                    
                $serviceOrderID = $value['proposal'];
                
                $repository = new ErrorRepository($value);
               
                if(! $repository->belongsToPartner(27)) {
                    throw new \Exception('Erro ao indentificar Ordem de Serviço');
                }

                // update service order
                $this->serviceOrderRepository->update($serviceOrderID, $repository->prepareUpdateStatus(3, 23));

                // Create interaction
                $this->interactionRepository->create($repository->prepareCreateInteractions($value['message']));
                
                PartnerResponse::pushSuccess("OS: " . $value['proposal']. " erro reportado.");

            } catch (\Exception $th) {
                
                PartnerResponse::pushError("OS: " . $value['proposal']. ", erro: " .$th->getMessage());

            } catch (\CodeIgniter\Database\Exceptions\DatabaseException $db) {
                
                PartnerResponse::pushError("OS: " . $value['proposal']. ", erro: " . $db->getMessage());

            }
        }

    }
}