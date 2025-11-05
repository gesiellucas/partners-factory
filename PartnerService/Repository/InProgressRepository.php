<?php

namespace App\Libraries\PartnerService\Repository;

use App\Repositories\SchedulesRepository;

class InProgressRepository
{
    protected $dataset;

    const PARTNER_ID = 27;
    const TECHNICIAN_ID = 358;
    const OPTIONAL_FIELDS = [
        'address', 
        'number', 
        'complement', 
        'neighborhood', 
        'city', 
        'state', 
        'zipcode', 
        'scheduled_date'
    ];

    public function __construct(
        protected array $requestData,
        protected $repository = new \App\Repositories\ServicesOrdersRepository()
    ) 
    {
        $this->dataset = $this->setDataset($requestData);
    }

    private function setDataset($data)
    {
        $where = [ 
            'so.partner_id' => self::PARTNER_ID,  
            'so.policy_id' => $data['policy_id']
        ];
        
        return $this->repository->getServiceOrder($data['proposal'], $where) ?? throw new \Exception('Service Order não localizada.');
    }    

    public function prepareCreateSchedule(): array
    {
        $requestData = $this->requestData;
        $serviceOrder = $this->dataset;
        
        // Gerar UUID
        $uuid = service('uuid');
        $uuid4 = $uuid->uuid4();
        $uuid = $uuid4->toString();

        $scheduleData = [
            'uuid' => $uuid,
            'contract_id' => $serviceOrder['contract_id'],
            'service_order_id' => (int) $serviceOrder['service_order_id'],
            'provider_id' => $serviceOrder['partner_id'],
            'service_id' => $serviceOrder['service_id'],
            'technician_id' => self::TECHNICIAN_ID,
            'partner_id' => $serviceOrder['partner_id'],
            'scheduled_date' => $this->requestData['schedule']['date_schedule']
        ];

        // Check if has the non-required fields
        foreach(self::OPTIONAL_FIELDS as $field) {
            if( isset($requestData['schedule'][$field])) {
                $scheduleData[$field] = $requestData['schedule'][$field];
            }
        }

       return $scheduleData;
    }

    public function prepareUpdateStatus(): array
    {
        return [
            'status' => 2,
            'status_interaction' => 6
        ];
    }

    public function prepareCreateInteractions(): array
    {
        $message = '<h6 class="font-bold text-primary"> COM ' . strtoupper($this->dataset['partner_name']) . ' </h6>';
        $message .= '<div class="row">';
        $message .= '<div class="col-md2 col-lg-1">';
        $message .= '<img src="' . base_url('assets/images/calendar_check_mark_icon.svg') . '" width="30px">';
        $message .= '</div>';
        $message .= '<div class="col-lg-8">';
        $message .= '<h6> Dia' . utf8_encode(strftime('<b style="color:#006DC4"> %d de %B %Y </b>, %A as <b style="color:#006DC4"> %H:%M </b> ', strtotime($this->requestData['schedule']['date_schedule']))) . '</h6>';
        $message .= '<h6>' . $this->dataset['partner_address'] . ' , Número: ' . $this->dataset['partner_number'] . ', ' . $this->dataset['partner_city'] . ' ,' . $this->dataset['partner_state'] . '</h6>';
        $message .= '</div>';
        $message .= '</div>';

        return [ 
            'status' => 'Agendado',
            'type' => 'Parceiro',
            'message' => $message,
            'user_id' => $this->dataset['user_id'],
            'contract_id' => $this->dataset['contract_id'],
            'service_order_id' => $this->dataset['service_order_id'],
            'contract_status' => $this->dataset['contract_status']
        ];
    }

    public function prepareSyncI4pro(): array
    {
        $requestData = $this->requestData;
        $serviceOrder = $this->dataset;
        
        $schedule = (new SchedulesRepository())->getScheduleByServiceOrder($requestData['proposal']);

        $phone = preg_replace('/\D/', '', $serviceOrder['partner_phone']);
        preg_match('/^(\d{2})(.*)$/', $phone, $matches);

        $ddd = $matches[1];
        $phone = $matches[2];

        return [
            'id_agendamento' => $schedule['schedule_id'],
            'id_apolice' => $serviceOrder['policy_id'],
            'nm_chassi' => $schedule['chassi'],
            'id_tp_agendamento' => $schedule['service_control_id'],
            'cd_evento' => 19,
            'nm_parceiro' => $serviceOrder['partner_name'],
            'nr_cpf_cnpj_loja_instalador' => $schedule['provider_doc'],
            'nm_loja_instalador' => $schedule['provider_name'],
            'nr_ddd_loja_instalador' => $ddd,
            'nr_tel_loja_instalador' => $phone,
            'dt_agendamento' => date("Y-m-d"),
            'hr_agendamento' => date("H:i:s"),
            'nm_endereco' => $schedule['provider_address'],
            'nr_rua_endereco' => $schedule['provider_number'],
            'nm_complemento' => $schedule['provider_complement'],
            'nm_bairro' => $schedule['provider_neighborhood'],
            'nm_cidade' => $schedule['provider_city'],
            'cd_uf' => $schedule['provider_state'],
            'nm_cep' => $schedule['provider_zipcode']
        ];
        
    }

    public function prepareUpdateSchedule($i4pro): array
    {
        return [
            'control_id' => $i4pro['id_agendamento'] ?? null
        ];
    }
}