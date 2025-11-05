<?php 


public function retrievePartnerOS($partner = null)
{
    $data = $this->validRequestData();

    $partner_service = service('partner', $partner);

    if($partner_service instanceof PartnerAbstract) {
        return $partner_service->handlePartnerRetrieveData($data);
    }

    return $this->respond(['status' => false, 'message' => 'Erro de processamento'], 401);
}

public function sendServicesOrders(string $partner = null)
{

    PartnerResponse::initLog('CRON PARTNER REQUEST', 'Envio de SO para os parceiros');
    
    try {

        $partnerService = service('partner', $partner);

        $servicesOrders = (new PartnersRepository())->handlePartnersSendSO($partner);
        
        $partnerService->triggerRequest($servicesOrders);

    } catch (\Exception $th) {

        return $this->respond(['status' => false, 'message' => $th->getMessage()], $th->getCode());

    }

    return $this->respond(['status' => true, 'message' => 'OS Enviadas']);
}



function check_partner(string $class)
{
    $namespace = "\App\Libraries\PartnerService\Partners\\";
    $directory = ROOTPATH . "app/Libraries/PartnerService/Partners";

    // Convert the requested class name to lowercase for comparison
    $requestedClass = strtolower($class);

    // Scan the directory for files
    $files = scandir($directory);

    foreach ($files as $file) {
        // Strip the file extension and convert to lowercase for comparison
        $fileClass = strtolower(pathinfo($file, PATHINFO_FILENAME));

        if ($fileClass === $requestedClass) {
            // If matched, get the original class name (case-sensitive)
            $init_class = $namespace . pathinfo($file, PATHINFO_FILENAME);

            if (class_exists($init_class) && is_subclass_of($init_class, PartnerAbstract::class)) {
                return new $init_class;
            }
        }
    }

    throw new \Exception('Erro ao encontrar parceiro');

}

$partnerName = 'sat';
check_partner($partnerName);

$url = getenv('sat_API_URL');
$token = getenv('sat_TOKEN');

if(! isset($url) || ! isset($token)) {
    throw new \Exception('Token ou URL inválida');
}

$app = fn($partnerName) => match($partnerName) {
    'sat' => service('curlrequest', [
        'http_errors' => false,
        'headers'     => [ 'APP-Authorization' => $token ],
        'baseURI'     => $url
    ])
};

$app = $app($partnerName);