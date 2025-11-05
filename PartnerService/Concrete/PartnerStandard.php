<?php 

namespace App\Libraries\PartnerService\Concrete;

use App\Libraries\PartnerService\Exceptions\PartnerResponse;
use App\Libraries\PartnerService\Interfaces\PartnerInterface;
use App\Libraries\PartnerService\Repository\CronRepository;
use App\Libraries\PartnerService\Status\NoEquipment;
use App\Libraries\PartnerService\Status\Canceled;
use App\Libraries\PartnerService\Status\Complete;
use App\Libraries\PartnerService\Status\Error;
use App\Libraries\PartnerService\Status\InProgress;
use App\Libraries\PartnerService\Status\Received;
use App\Repositories\InteractionsRepository;
use App\Repositories\LogsRepository;
use App\Repositories\ServicesOrdersRepository;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\ResponseTrait;
use CodeIgniter\Model;

class PartnerStandard implements PartnerInterface
{
    use ResponseTrait;

    const STATUS_RECEIVED     = 5;
    const STATUS_IN_PROGRESS  = 6;
    const STATUS_CANCELED = 7;
    const STATUS_COMPLETE     = 10;
    const STATUS_NO_EQUIPMENT    = 12;
    const STATUS_ERROR    = 13;

    public function __construct(
        protected Model $serviceorder = new \App\Models\ServicesOrdersModel(),
        protected Model $interaction = new \App\Models\InteractionsModel(),
    ) {
        
    }
    
    public static function handle($data)
    {
        $status = (int) $data['status'];
        $dataset = $data['data'];

        return self::handleRetrieve($status, $dataset);
    }

    private static function handleRetrieve(int $status, $data)
    {
        return match ($status) {
            self::STATUS_RECEIVED     => new Received($data),
            self::STATUS_IN_PROGRESS  => new InProgress($data),
            self::STATUS_COMPLETE     => new Complete($data),
            self::STATUS_CANCELED     => new Canceled($data),
            self::STATUS_NO_EQUIPMENT => New NoEquipment($data),
            self::STATUS_ERROR        => New Error($data),
            default                   => throw new \Exception('Status inválido')
        };
    }

    /**
     * Obtém todas as Service Order que pode ser enviada ao Parceiro em evidência
     *
     * @param $services_orders
     * @param $request
     * @return void
     */
    public static function send($servicesOrders): void
    {
        // Create Request
        $request = service('partnerrequest', 'sat');

        // Save Log
        (new LogsRepository())->saveSendOSPartners($servicesOrders);

        // Mount Body Header
        foreach($servicesOrders as $value) {
            
            try {
                // Return data prepared to be sent
                $body = CronRepository::prepareSendOS($value);
                
                // Get request response
                $response = $request->setJSON($body)->request('POST', 'requests-import/import-external/39');
                
                // Deal with the request response
                self::handleResponse($response, $value['service_order_id']);

            } catch (\Exception $th) {
                PartnerResponse::saveLogError(serialize($th), $th->getTraceAsString());

            } catch (\CodeIgniter\Database\Exceptions\DatabaseException $db) {
                
                PartnerResponse::saveLogError($db->getMessage(), $db->getTraceAsString());

            }  catch (HTTPException $ex) {

                PartnerResponse::saveLogError(serialize($ex), $ex->getMessage());

            }   
        }

    }

    private static function handleResponse($response, $serviceOrderID): void
    {
        switch($response->getStatusCode()) {

            case 200:
                self::responseSuccess($response, $serviceOrderID);
                break;

            default:
                self::responseError($response, $serviceOrderID);
        }
    }

    private static function responseSuccess($response, $serviceOrderID): void
    {   
        // Update service order sent
        (new ServicesOrdersRepository)->update((int) $serviceOrderID, ['sent_api' => 1]);

         // Create interaction of successfull
        (new InteractionsRepository())->serviceOrderSentApi($serviceOrderID);

        PartnerResponse::pushSuccess("OS: " . $serviceOrderID. ", response: " . json_encode($response->getJSON(true)));
    }

    private static function responseError($response, $serviceOrderID): void
    {
        // Create interaction of error log 
        (new InteractionsRepository())->serviceOrderSentApi($serviceOrderID, $response->getJSON(true));

        PartnerResponse::saveLogError($response, $response->getReason());
    }
}