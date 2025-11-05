<?php 

namespace App\Libraries\PartnerService\Repository;

class UserGetrakRepository
{
    public function __construct(
        protected $getrak = new \App\Libraries\Getrak(),
        protected $logGetrakRepository = new \App\Repositories\LogsGetrakRepository(),
        protected $memberRepository = new \App\Repositories\MembersRepository(),
        protected $emailRepository = new \App\Repositories\EmailsRepository(),
        protected $interactionRepository = new \App\Repositories\InteractionsRepository
    )  {

    }

    public function syncUser($data)
    {
        return !isset($data['getrak_users_id']) 
            ? $this->createNewUser($data)
            : $this->updateUser($data);
    }

    private function createNewUser($data)
    {
        // Generate Credencial Login
        $password = str_pad(rand(0, 999999), 6, "0", STR_PAD_LEFT);
        $member = [
            'email' => $data['email'],
            'doc' => $data['doc'],
            'name' => $data['name'],
            'getrak_control_id' => $data['getrak_control_id']
        ];

        // Create User on Getrak
        $newUser = $this->getrak->newUser($member, $password);

        $this->logGetrakRepository->saveRequestLogGetrak($data, $newUser, 'new-user');
        
        return [
            'password' => $password
        ];
    }

    private function updateUser($data)
    {
        // Update User
        $user = $this->getrak->getUser($data['getrak_users_id']);

        $this->logGetrakRepository->saveRequestLogGetrak($data, $user, 'verify-user' );

        $password = str_pad(rand(0, 999999), 6, "0", STR_PAD_LEFT);
        $user['senha'] = $password;
        $user['senhatemp'] = "Y";
        $user['acesso_ws'] = "Y";
        unset($user['subcliente']);

        $updateUser = $this->getrak->editUser($data['getrak_users_id'], $user);

        $this->logGetrakRepository->saveRequestLogGetrak($data, $updateUser, 'painel-new-password');
     
        return [
            'password' => $password
        ];
    }

}
