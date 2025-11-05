<?php

namespace App\Libraries\PartnerService\Repository;

use App\Repositories\SchedulesTrackersRepository;
use App\Repositories\TrackersRepository;

class RemovalRepository
{
    protected $dataset;

    const DEFAULT_LOCATION = 99;
    const DEFAULT_TYPE = 1;

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

    public function getDataset()
    {
        return [
            'schedule_id'      => $scheduleID,
            'service_order_id' => $serviceOrderID
        ] = $this->dataset;
    }

    public function prepareGetIMEI(): array
    {
        return [
            'imei_chip' => $this->requestData['tracker']['imei_chip'],
            'imei_tracker' => $this->requestData['tracker']['imei_tracker']
        ];
    }

    public function prepareDisableTracker()
    {
        $tracker = (new TrackersRepository())->getTracker([
            'imei_chip' => $this->requestData['tracker']['imei_chip'],
            'imei_tracker' => $this->requestData['tracker']['imei_tracker']
        ]);

        return $tracker['tracker_id'];
    }

    public function prepareUnrelateScheduleTracker(): array
    {
        $tracker = (new SchedulesTrackersRepository())->getSchedulesTrackersByIMEI($this->prepareGetIMEI()) 
            ?? throw new \Exception('Equipamento não relacionado a agendamento.');

        return [
            'schedule_id' => $tracker['schedule_id'],
            'tracker_id' => $tracker['tracker_id']
        ];
    }

    public function prepareUpdateStatus($status, $subStatus): array
    {
        return [
            'status' => $status,
            'status_interaction' => $subStatus
        ];
    }

    public function prepareCreateInteractions(): array
    {
        return [ 
            'status' => 'Retirada concluída',
            'type' => 'Retirada',
            'message' => 'Serviço feito com sucesso',
            'user_id' => $this->dataset['user_id'],
            'contract_id' => $this->dataset['contract_id'],
            'service_order_id' => $this->dataset['service_order_id'],
            'contract_status' => $this->dataset['contract_status']
        ];
    }
    
    public function prepareServiceRemoval(): array
    {
        $schedule = $this->dataset;

        $tracker = (new TrackersRepository())->getTracker($this->requestData['tracker']);

        return [
            'id_apolice' => $schedule['policy_id'],
            "nm_parceiro" => $schedule['partner_name'],
            "id_agendamento" => $schedule['control_id'],
            "id_tp_agendamento" => 3,
            "nr_equipamento_ret" => $tracker['control_id'],
            "nm_modelo_equipamento_ret" => $tracker['model'],
            "nm_local_ret" => 'Retirada',
            "dt_servico" => date("Y-m-d H:i:s"),
            "dt_sincronismo" => date("Y-m-d H:i:s"),
            "dt_agendamento_ceabs" => $this->requestData['service_date'],
            "dt_retorno_ceabs" => $this->requestData['service_date'],
            "ds_motivo_fechamento_ceabs" => "Concluido",
            "ds_submotivo_fechamento_ceabs" => "Retirada",
            "nm_tp_instalacao_ceabs" => "Retirada"
        ];
    }

    public function prepareRemovedI4Pro(): array
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

    public function prepareScheduleRemoval(): array
    {
        return [
            'concluded' => 1, 
            'date_concluded' => date("Y-m-d H:i:s")
        ];
    }

    public function prepareGetrakRemoval($tracker)
    {
        return (new TrackersRepository())
            ->getTrackerGetrak($tracker);
    }

    public function prepareScheduleTracker($trackerID): array
    {
        return [
            'schedule_id'              => $this->dataset['schedule_id'],
            'tracker_id'               => $trackerID,
            'installation_location_id' => self::DEFAULT_LOCATION,
            'installation_type_id'     => self::DEFAULT_TYPE
        ];
    }

    public function prepareLogicaRemoval($tracker)
    {
        return [
            'imei_tracker' => $tracker['imei_tracker'],
            'chassi' => $this->dataset['chassi'],
            'schedule_id' => $this->dataset['schedule_id']
        ];
    }

    public function monitoringType()
    {
        return $this->requestData['tracker']['monitoring_id'] ?? 2;
    }
}