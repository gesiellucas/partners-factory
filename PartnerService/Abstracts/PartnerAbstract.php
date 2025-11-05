<?php 

namespace App\Libraries\PartnerService\Abstracts;

use App\Libraries\PartnerService\Exceptions\PartnerResponse;
use App\Libraries\PartnerService\Interfaces\PartnerInterface;

/**
 * Classe base para criação de parceiros
 */
abstract class PartnerAbstract
{
    
    abstract protected function build(): PartnerInterface;

    public function triggerRequest($servicesOrders)
    {
        $app = $this->build();

        try {
            //code...
            $app::send($servicesOrders);
        } catch (\Throwable $th) {
            //throw $th;
            throw new \Exception($th->getMessage(), 400);
        }

        return service('response')
            ->setStatusCode(PartnerResponse::getCode())
            ->setJSON(PartnerResponse::prepareResponse());
    }

    /**
     * Lida com a recuperação de dados dos parceiros pelo webhook estabelecido.
     * Baseado no status enviado pela requisição é possível executar funções pré estabelecidas para o fim.
     *
     * @return mixed
     */
    public function handlePartnerRetrieveData($data)
    {
        $app = $this->build();
        
        $app::handle($data);

        return service('response')
            ->setStatusCode(PartnerResponse::getCode())
            ->setJSON(PartnerResponse::prepareResponse());
    }
    
}