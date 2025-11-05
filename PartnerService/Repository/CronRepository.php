<?php 

namespace App\Libraries\PartnerService\Repository;

class CronRepository
{
    const PACKAGE_CARRO = 123;
    const PACKAGE_MOTO = 120;

    public static function prepareSendOS($servicesOrders): array
    {
        helper('dates_operations');
        
        $data = [
            "yearManufacture"     => (int) $servicesOrders['manufacture_year'],
            "modelYear"           => (int) $servicesOrders['model_year'],
            "packageId"           => $servicesOrders['type_vehicle_id'] == 1 ? self::PACKAGE_CARRO : self::PACKAGE_MOTO,
            "requestType"         => $servicesOrders['service_name'],
            "packageName"         => "Rastreamento Básico",
            "trackableObjectType" => "veículo",
            "model"               => $servicesOrders['version'],
            "plate"               => $servicesOrders['plate'],
            "chassis"             => $servicesOrders['chassi'],
            "fipecode"            => $servicesOrders['fipe'],
            "manufacturer"        => $servicesOrders['maker'],
            "color"               => $servicesOrders['color'],
            "clientFullName"      => $servicesOrders['member_name'],
            "clientName"          => $servicesOrders['member_name'],
            "clientDocument"      => $servicesOrders['member_doc'],
            "address"             => $servicesOrders['member_address'],
            "publicPlace"         => $servicesOrders['member_address'],
            "state"               => $servicesOrders['member_state'],
            "district"            => $servicesOrders['member_neighborhood'],
            "city"                => $servicesOrders['member_city'],
            "postalCode"          => $servicesOrders['member_zipcode'],
            "ownerPhone1"         => $servicesOrders['member_phone'],
            "ownerEmail"          => $servicesOrders['member_email'],
            "proposal"            => $servicesOrders['service_order_id'],
            "proposalDate"        => $servicesOrders['issuance_date'],
            "slaDate"             => aditionDaysToDate($servicesOrders['issuance_date'], 15),
            "policy"              => $servicesOrders['policy_id'],
            "codeBroker"          => $servicesOrders['partner_id'],
            "broker"              => $servicesOrders['partner_name'],
            "brokerEmail"         => $servicesOrders['partner_email'],
            "brokerPhone1"        => $servicesOrders['partner_phone'],
            "number"              => $servicesOrders['partner_number']
        ]; 

        return [
            "requestImportsList" => [$data]
        ];
    }
}
