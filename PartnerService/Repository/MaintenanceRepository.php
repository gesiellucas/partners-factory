<?php

namespace App\Libraries\PartnerService\Repository;

use App\Repositories\SchedulesTrackersRepository;
use App\Repositories\TrackersModelsRepository;
use App\Repositories\TrackersRepository;

class MaintenanceRepository
{
    const MONITORING_LOGICA = 2;

    protected $dataset;

    public function __construct(
        protected array $requestData,
        protected $repository = new \App\Repositories\SchedulesRepository()
    ) 
    {
        $this->dataset = $this->setDataset($requestData);
    }

    private function setDataset($data)
    {
        return $this->repository->getScheduleByServiceOrder($data['proposal']) ?? throw new \Exception('Agendamento não localizado.');
    }    
    
    public function getDataset()
    {
        return [
            'schedule_id'      => $scheduleID,
            'service_order_id' => $serviceOrderID
        ] = $this->dataset;
    }

    public function prepareGetCurrentIMEI(): array
    {
        return [
            'imei_chip'    => $this->requestData['current_tracker']['imei_chip'],
            'imei_tracker' => $this->requestData['current_tracker']['imei_tracker']
        ];
    }

    public function prepareGetNewIMEI(): array
    {
        return [
            'imei_chip'    => $this->requestData['new_tracker']['imei_chip'],
            'imei_tracker' => $this->requestData['new_tracker']['imei_tracker']
        ];
    }

    public function prepareLogicaMaintenance(): array
    {
        return [
            'imei_tracker' => $this->requestData['new_tracker']['imei_tracker'],
            'chassi' => $this->dataset['chassi'],
            'schedule_id' => $this->dataset['schedule_id']
        ];
    }

    public function prepareCreateTracker($trackerLotID): array
    {
        $requestData = $this->requestData;

        $trackersModel = TrackersModelsRepository::trackerModel($requestData['new_tracker']['model_id']);

        $module = $trackersModel['version'] . $requestData['new_tracker']['imei_tracker'];

        $uuid  = service('uuid');
        $uuid4 = $uuid->uuid4();
        $uuid  = $uuid4->toString();

        return [
            "uuid"             => $uuid,
            "tracker_lot_id"   => $trackerLotID,
            "imei_chip"        => $requestData['new_tracker']['imei_chip'],
            "imei_tracker"     => $requestData['new_tracker']['imei_tracker'],
            "control_id"       => $requestData['new_tracker']['imei_tracker'],
            "module"           => $module,
            "ddd"              => $requestData['new_tracker']['ddd'],
            "phone"            => $requestData['new_tracker']['phone'],
            "status"           => 1,
            "sub_status"       => 11,
            "policy_id"        => $requestData['policy_id'],
            "supplier_id"      => $trackersModel['supplier_id'],
            "contract_id"      => $this->dataset['contract_id'],
            "provider_id"      => $this->dataset['provider_id'],
            "tracker_model_id" => $trackersModel['tracker_model_id'],
            "operator_id"      => $requestData['new_tracker']['operator_id'],
            "monitoring_id"    => $requestData['new_tracker']['monitoring_id'] ?? 2
        ];

    }

    public function prepareUpdateTracker()
    {
        return [
            'policy_id'         => $this->dataset['policy_id'],
            'contract_id'       => $this->dataset['contract_id'],
            'provider_id'       => $this->dataset['provider_id'],
            'vehicle_id'        => $this->dataset['vehicle_id'],
            'installation_date' => $this->requestData['service_date']
        ];
    }

    public function prepareCreateTrackerLot(): array
    {
        $uuid = service('uuid');
        $uuid4 = $uuid->uuid4();
        $uuid = $uuid4->toString();

        $trackerModel = TrackersModelsRepository::trackerModel($this->requestData['new_tracker']['model_id']);

        return [
            "uuid" => $uuid,
            "supplier_id" => $trackerModel['supplier_id'],
            "tracker_model_id" => $trackerModel['tracker_model_id'],
            "operator_id" => $this->requestData['new_tracker']['operator_id'],
            "lot" => TrackersModelsRepository::getLastLot()
        ];
    }

    public function prepareDisableTracker()
    {
        $tracker = (new TrackersRepository())->getTracker([
            'imei_chip'    => $this->requestData['current_tracker']['imei_chip'],
            'imei_tracker' => $this->requestData['current_tracker']['imei_tracker']
        ]);

        return $tracker['tracker_id'];
    }

    public function prepareUnrelateScheduleTracker(): ?string
    {
        $tracker = (new SchedulesTrackersRepository())->getSchedulesTrackersByIMEI($this->prepareGetCurrentIMEI());

        return $tracker['schedule_tracker_id'] ?? null;
    }

    public function prepareRelateScheduleTracker($trackerID): array
    {
        return [
            'schedule_id'              => $this->dataset['schedule_id'],
            'tracker_id'               => $trackerID,
            'installation_location_id' => $this->requestData['new_tracker']['installation_location_id'] ?? null,
            'installation_type_id'     => $this->requestData['new_tracker']['installation_type_id'] ?? null
        ];
    }

    public function prepareUpdateStatus($status = 19): array
    {
        return [
            'status'             => 4,
            'status_interaction' => $status
        ];
    }

    public function prepareCreateInteractions(): array
    {
        return [ 
            'status'           => 'Manutenção concluída',
            'type'             => 'Manutenção',
            'message'          => 'Manutencao concluída',
            'user_id'          => $this->dataset['user_id'],
            'contract_id'      => $this->dataset['contract_id'],
            'service_order_id' => $this->dataset['service_order_id'],
            'contract_status'  => $this->dataset['contract_status']
        ];
    }

    public function prepareServiceMaintenance(): array
    {
        $schedule = $this->dataset;

        $tracker = (new TrackersRepository())->getTracker($this->requestData['new_tracker']);

        return [
            "dt_servico"                    => $this->requestData['service_date'],
            'id_apolice'                    => $schedule['policy_id'],
            "nm_parceiro"                   => $schedule['partner_name'],
            "nr_ddd_inst"                   => $tracker['ddd'],
            "nr_iccid_inst"                 => $tracker['imei_chip'],
            "dt_sincronismo"                => date("Y-m-d H:i:s"),
            "id_agendamento"                => $schedule['control_id'],
            "id_tp_agendamento"             => 2,
            "nr_equipamento_inst"           => $tracker['control_id'],
            "nm_rastreador_inst"            => $tracker['supplier_name'],
            "nm_modelo_equipamento_inst"    => $tracker['model'],
            "dt_retorno_ceabs"              => $this->requestData['service_date'],
            "ds_motivo_fechamento_ceabs"    => "Concluido",
            "ds_submotivo_fechamento_ceabs" => "Manutenção",
            "nm_tp_instalacao_ceabs"        => "Manutenção",
            "nr_telefone_inst"              => $tracker['phone'],
            "nm_operadora_inst"             => $tracker['operator_name'],
            "dt_agendamento_ceabs"          => $this->requestData['service_date'],
            "nm_status_ceabs"               => "Concluído"
        ];

    }
    
    public function prepareUpdatedI4Pro(): array
    {
        $phone = preg_replace('/\D/', '', $this->dataset['provider_phone']);
        preg_match('/^(\d{2})(.*)$/', $phone, $matches);

        $ddd   = $matches[1];
        $phone = $matches[2];
        
        return [
            'id_agendamento'              => $this->dataset['control_id'],
            'id_apolice'                  => $this->dataset['policy_id'],
            'nm_chassi'                   => $this->dataset['chassi'],
            'id_tp_agendamento'           => $this->dataset['service_control_id'],
            'cd_evento'                   => 8,
            'nm_parceiro'                 => $this->dataset['partner_name'],
            'nr_cpf_cnpj_loja_instalador' => $this->dataset['provider_doc'],
            'nm_loja_instalador'          => $this->dataset['provider_name'],
            'nr_ddd_loja_instalador'      => $ddd,
            'nr_tel_loja_instalador'      => $phone,
            'dt_agendamento'              => date("Y-m-d",strtotime($this->dataset['scheduled_date'])),
            'hr_agendamento'              => date("H:i:s",strtotime($this->dataset['scheduled_date'])),
            'nm_endereco'                 => $this->dataset['schedule_address'],
            'nr_rua_endereco'             => $this->dataset['schedule_number'],
            'nm_complemento'              => $this->dataset['schedule_complement'],
            'nm_bairro'                   => $this->dataset['schedule_neighborhood'],
            'nm_cidade'                   => $this->dataset['schedule_city'],
            'cd_uf'                       => $this->dataset['schedule_state'],
            'nm_cep'                      => $this->dataset['schedule_zipcode'],
        ];

    }

    public function getScheduleID()
    {
        return $this->dataset['schedule_id'];
    }

    public function prepareScheduleMaintened(): array
    {
        return [
            'concluded'      => 1,
            'date_concluded' => date("Y-m-d H:i:s")
        ];
    }

    public function prepareGetrakMaintenance()
    {
        return [
            'uuid'              => $this->dataset['schedule_uuid'],
            'imei_tracker'      => $this->requestData['new_tracker']['imei_tracker'],
            'doc'               => $this->dataset['member_doc'],
            'email'             => $this->dataset['member_email'],
            'name'              => $this->dataset['member_name'],
            'schedule_id'       => $this->dataset['schedule_id'],
            'member_id'         => $this->dataset['member_id'],
            'service_order_id'  => $this->dataset['service_order_id'],
            'getrak_control_id' => $this->dataset['getrak_control_id'],
            'getrak_users_id'   => $this->dataset['getrak_users_id'],
            'plate'             => $this->dataset['plate'],
            'contract_id'       => $this->dataset['contract_id'],
            'user_id'           => $this->dataset['member_id'],
            'vehicle_id'        => $this->dataset['vehicle_id']
        ];
    }

    public function prepareGetIMEI(): array
    {
        return [
            'imei_chip'    => $this->requestData['new_tracker']['imei_chip'],
            'imei_tracker' => $this->requestData['new_tracker']['imei_tracker']
        ];
    }

    public function monitoringType()
    {
        return $this->dataset['monitoring_id'] ?? self::MONITORING_LOGICA;
    }

}