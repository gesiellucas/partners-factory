<?php

namespace App\Libraries\PartnerService\Repository;

use App\Repositories\TrackersModelsRepository;
use App\Repositories\TrackersRepository;

class InstallationRepository
{
    protected $dataset;
    const STATUS_INSTALLATION = 4;
    const STATUS_INTERACTION_INSTALLATION = 10;
    const USER_ID_INTERACTION = 1;

    public function __construct(
        protected array $requestData,
        protected $repository = new \App\Repositories\SchedulesRepository()
    )  {
        $this->dataset = $this->setDataset($requestData);
    }

    private function setDataset($data)
    {
        return $this->repository->getScheduleByServiceOrder($data['proposal']) 
            ?? throw new \Exception('Agendamento não localizado.');
    }

    public function prepareUpdateStatus(): array
    {
        return [
            'status' => self::STATUS_INSTALLATION,
            'status_interaction' => self::STATUS_INTERACTION_INSTALLATION
        ];
    }

    public function prepareCreateInteractions(): array
    {
        return [ 
            'status' => 'Instalado',
            'type' => 'Instalação',
            'message' => 'Serviço de instalação finalizado',
            'user_id' => self::USER_ID_INTERACTION,
            'contract_id' => $this->dataset['contract_id'],
            'service_order_id' => $this->dataset['service_order_id'],
            'contract_status' => $this->dataset['contract_status']
        ];
    }

    public function prepareServiceInstall($trackerID): array
    {
        $schedule = $this->dataset;

        $tracker = (new TrackersRepository())->getTrackerByID($trackerID);

        $trackerModel = TrackersModelsRepository::trackerModel($this->requestData['tracker']['model_id']);

        return [
            "cd_empresa"=> 490,
            "dt_retorno_ceabs"=> date("Y-m-d H:i:s"),
            "id_apolice"=> $schedule['policy_id'],
            "nm_parceiro"=> $this->dataset['partner_name'],
            "nr_ddd_inst"=> $tracker['ddd'],
            "nr_iccid_inst"=> $tracker['imei_chip'],
            "dt_sincronismo"=> date("Y-m-d H:i:s"),
            "nr_telefone_inst"=> $tracker['phone'],
            "id_tp_agendamento"=> $this->dataset['service_control_id'],
            "nm_operadora_inst"=> $tracker['operator_name'],
            "nm_rastreador_inst"=> $tracker['supplier_name'],
            "nm_local_instalacao"=> $tracker['installation_type'].' - '.$tracker['installation_name'],
            "nr_equipamento_inst"=> $tracker['control_id'],
            "nm_modelo_equipamento_inst"=> $trackerModel['model'],
            "nm_tp_instalacao_ceabs"=> $this->dataset['service_name'],
            "dt_agendamento_ceabs" => $this->requestData['service_date'],
            "nm_status_ceabs" => "Concluído",
            "ds_motivo_fechamento_ceabs" => "Instalação"
        ];
        
    }

    public function prepareCreateTracker($trackerLotID): array
    {
        $requestData = $this->requestData;

        $trackersModel = TrackersModelsRepository::trackerModel($requestData['tracker']['model_id']);

        $module = $trackersModel['version'] . $requestData['tracker']['imei_tracker'];

        $uuid = service('uuid');
        $uuid4 = $uuid->uuid4();
        $uuid = $uuid4->toString();

        $monitoringID = $requestData['tracker']['monitoring_id'] ?? 2;

        return [
            "uuid" => $uuid,
            "tracker_lot_id" => $trackerLotID,
            "imei_chip" => $requestData['tracker']['imei_chip'],
            "imei_tracker" => $requestData['tracker']['imei_tracker'],
            "control_id" => $requestData['tracker']['imei_tracker'],
            "module" => $module,
            "ddd" => $requestData['tracker']['ddd'],
            "phone" => $requestData['tracker']['phone'],
            "status" => 1,
            "sub_status" => 11,
            "policy_id" => $requestData['policy_id'],
            "supplier_id" => $trackersModel['supplier_id'],
            "contract_id" => $this->dataset['contract_id'],
            "provider_id" => $this->dataset['provider_id'],
            "tracker_model_id" => $trackersModel['tracker_model_id'],
            "operator_id" => $requestData['tracker']['operator_id'],
            "monitoring_id" => $monitoringID
        ];

    }

    public function prepareCreateTrackerLot(): array
    {
        $uuid = service('uuid');
        $uuid4 = $uuid->uuid4();
        $uuid = $uuid4->toString();

        $trackerModel = TrackersModelsRepository::trackerModel($this->requestData['tracker']['model_id']);

        return [
            "uuid" => $uuid,
            "supplier_id" => $trackerModel['supplier_id'],
            "tracker_model_id" => $trackerModel['tracker_model_id'],
            "operator_id" => $this->requestData['tracker']['operator_id'],
            "lot" => TrackersModelsRepository::getLastLot()
        ];
    }

    public function prepareGetIMEI(): array
    {
        return [
            'imei_chip' => $this->requestData['tracker']['imei_chip'],
            'imei_tracker' => $this->requestData['tracker']['imei_tracker']
        ];
    }

    public function prepareRelateScheduleTracker($trackerID): array
    {
        return [
            'schedule_id' => $this->dataset['schedule_id'],
            'tracker_id' => $trackerID,
            'installation_location_id' => $this->requestData['tracker']['installation_location_id'] ?? null,
            'installation_type_id' => $this->requestData['tracker']['installation_type_id'] ?? null
        ];
    }

    public function prepareInstalledI4Pro(): array
    {
        $phone = preg_replace('/\D/', '', $this->dataset['provider_phone']);
        preg_match('/^(\d{2})(.*)$/', $phone, $matches);

        $ddd = $matches[1];
        $phone = $matches[2];
        
        return [
            'id_agendamento'=>$this->dataset['control_id'],
            'id_apolice'=>$this->dataset['policy_id'],
            'nm_chassi'=>$this->dataset['chassi'],
            'id_tp_agendamento'=>$this->dataset['service_control_id'],
            'cd_evento'=>8,
            'nm_parceiro'=>$this->dataset['partner_name'],
            'nr_cpf_cnpj_loja_instalador'=>$this->dataset['provider_doc'],
            'nm_loja_instalador'=>$this->dataset['provider_name'],
            'nr_ddd_loja_instalador'=>$ddd,
            'nr_tel_loja_instalador'=>$phone,
            'dt_agendamento'=>date("Y-m-d",strtotime($this->dataset['scheduled_date'])),
            'hr_agendamento'=>date("H:i:s",strtotime($this->dataset['scheduled_date'])),
            'nm_endereco'=>$this->dataset['schedule_address'],
            'nr_rua_endereco'=>$this->dataset['schedule_number'],
            'nm_complemento'=>$this->dataset['schedule_complement'],
            'nm_bairro'=>$this->dataset['schedule_neighborhood'],
            'nm_cidade'=>$this->dataset['schedule_city'],
            'cd_uf'=>$this->dataset['schedule_state'],
            'nm_cep'=>$this->dataset['schedule_zipcode'],
        ];

    }

    public function getScheduleID()
    {
        return $this->dataset['schedule_id'];
    }

    public function prepareScheduleInstalled($i4pro): array
    {
        return [
            'concluded' => 1, 
            'date_concluded' => date("Y-m-d H:i:s"),
            'control_id' => $i4pro[0]['id_agendamento'] ?? 0
        ];
    }

    public function prepareGetrakInstallation()
    {
        return [
            'uuid' => $this->dataset['schedule_uuid'],
            'imei_tracker' => $this->requestData['tracker']['imei_tracker'],
            'doc' => $this->dataset['member_doc'],
            'email' => $this->dataset['member_email'],
            'name' => $this->dataset['member_name'],
            'schedule_id' => $this->dataset['schedule_id'],
            'member_id' => $this->dataset['member_id'],
            'service_order_id' => $this->dataset['service_order_id'],
            'getrak_control_id' => $this->dataset['getrak_control_id'],
            'getrak_users_id' => $this->dataset['getrak_users_id'],
            'plate' => $this->dataset['plate'],
            'contract_id' => $this->dataset['contract_id'],
            'user_id' => $this->dataset['member_id'],
            'vehicle_id' => $this->dataset['vehicle_id']
        ];
    }

    public function prepareLogicaInstallation()
    {
        return [
            'imei_tracker' => $this->requestData['tracker']['imei_tracker'],
            'chassi' => $this->dataset['chassi'],
            'schedule_id' => $this->dataset['schedule_id']
        ];
    }

    public function prepareUpdateTracker()
    {
        return [
            'policy_id' => $this->dataset['policy_id'],
            'contract_id' => $this->dataset['contract_id'],
            'provider_id' => $this->dataset['provider_id'],
            'vehicle_id' => $this->dataset['vehicle_id'],
            'installation_date' => $this->requestData['service_date']
        ];
    }

    public function prepareCreateTrackerInteraction(): array
    {
        $monitoring = $this->requestData['tracker']['monitoring_id'] ?? 2;
        $message = "Criação de equipamento \n" ;
        $message .= " - IMEI TRACKER: " . $this->requestData['tracker']['imei_tracker'] . "\n";
        $message .= " - IMEI CHIP: " . $this->requestData['tracker']['imei_chip'] . "\n";
        $message .= " - MODULO: " . $this->requestData['tracker']['model_id'] . "\n";
        $message .= " - NUMERO DO TELEFONE: " . $this->requestData['tracker']['phone'] . "\n";
        $message .= " - OPERADORA: " . $this->requestData['tracker']['operator_id'] . "\n";
        $message .= " - DATA CONSULTA: " . date("d/m/Y H:i:s") . "\n";
        $message .= " - MONITORAMENTO: " . ($monitoring == 1 ? 'GETRAK' : 'LOGICA') . "\n";

        return [
            'status' => 'Criação de equipamento',
            'type' => 'Equipamento',
            'message' => $message,
            'user_id' => self::USER_ID_INTERACTION,
            'contract_id' => $this->dataset['contract_id'],
            'service_order_id' => $this->dataset['service_order_id'],
            'contract_status' => $this->dataset['contract_status']
        ];
    }
}