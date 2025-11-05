<?php 

namespace App\Libraries\PartnerService\Repository;

class GetrakRepository
{
    public function __construct(
        protected $getrak = new \App\Libraries\Getrak(),
        protected $logGetrakRepository = new \App\Repositories\LogsGetrakRepository(),
        protected $memberRepository = new \App\Repositories\MembersRepository(),
        protected $emailRepository = new \App\Repositories\EmailsRepository(),
        protected $interactionRepository = new \App\Repositories\InteractionsRepository,
        protected $userGetrakRepository = new \App\Libraries\PartnerService\Repository\UserGetrakRepository()
    )  {

    }

    /**
     * Método principal que dispara as ações da instalação dos serviços na Getrak
     * - Registra Log da requisição
     * - Sincroniza os dados do agendamento com o equipamento, isso é, verifica se equipamento existe, 
     * cria um novo caso precise, salva log de resposta.
     * - Sincroniza dados de cliente, criando um novo caso precise.
     * - Realiza o disparo de e-mail para o cliente com os dados de acesso ao painel Getrak, salva os logs,
     * cria as interações do processo.
     *
     * @param $data
     * @return void
     */
    public function handleInstallationService(array $data): void
    {
        // Create Log Getrak
        $this->logGetrakRepository->saveRequestLogGetrak($data, '', 'request-log-getrak');

        // Sync Service Order
        $this->getrak->syncGetrakServiceOrder($data['uuid'], $data['imei_tracker']);

        // Sync User
        $userData = $this->userGetrakRepository->syncUser($data);

        if(!empty($userData)) {
            // Handle Email Sender
            $this->triggerSendEmail($data, $userData);
        }


    }

    public function handleMaintenanceService(array $data): void
    {
        // Create Log Getrak
        $this->logGetrakRepository->saveRequestLogGetrak($data, '', 'request-log-getrak');

        // Sync install
        $this->getrak->syncGetrakServiceOrder($data['uuid'], $data['imei_tracker']);

        // Sync User
        $userData = $this->userGetrakRepository->syncUser($data);

        // Handle Email Sender
        $this->triggerSendEmail($data, $userData);
    }

    public function handleRemovalService(array $data): void
    {
        // Sync Service Order
        $remove = $this->getrak->removeGetrakServiceOrder($data);

        // Create Log Getrak
        $this->logGetrakRepository->saveRequestLogGetrak($data, $remove, 'request-log-getrak');
    }

    private function triggerSendEmail($data, $userData)
    {
        try {

            // Get Email Template
            $template = $this->emailRepository->prepareHTMLEmail($data, $userData['password']);

            // Trigger Email
            $emailSendID = EmailSenderRepository::sendEmail(static::prepareGetrakEmail($data['email'], $template));

            // Save Data Email
            $emailSaveID = $this->emailRepository->create(static::prepareSaveDataEmail($emailSendID['id'], $data, $template));
            
            // Create interaction of send email
            $emailInteraction = $this->emailRepository->prepareEmailInteraction($emailSaveID, $data);

            $this->interactionRepository->create($emailInteraction);

        } catch (\Exception $th) {

            $this->logGetrakRepository->saveLogEmailGetrak($th->getMessage(), 'erro-email');

        } catch (\CodeIgniter\Database\Exceptions\DataException $th) {

            $this->logGetrakRepository->saveLogEmailGetrak($th->getMessage(), 'erro-email');

        }

    }

    private static function prepareGetrakEmail($email, $template): array
    {
        return [
            'to' => $email,
            'subject' => 'Novidade! Agora você pode rastrear seu veículo!',
            'content' => $template
        ];
    }

    private function prepareSaveDataEmail($emailID, $data, $template): array
    {
        return [
            'control_id' => $emailID,
            'template_id' => 9,
            'member_id' => $data['member_id'],
            'service_order_id' => $data['service_order_id'],
            'vehicle_id' => $data['vehicle_id'],
            'name_from' => null,
            'email_from' => null,
            'email_to' => $data['email'],
            'name_to' => $data['name'],
            'subject' => 'Novidade! Agora você pode rastrear seu veículo!',
            'content' => $template,
            'status' => 1,
            'read' => 0,
            'sent' => 1
        ];
    }

}
