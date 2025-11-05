<?php 

namespace App\Libraries\PartnerService\Repository;

use App\Libraries\I4pro;
use App\Repositories\LogsRepository;

class I4proRepository
{
    public static function instance()
    {
        return new I4pro;
    }

    public static function saveSchedule($data)
    {
        $response = (self::instance())->scheduleTracker($data);
        
        self::saveLogI4Pro($data, $response);

        return $response[0] ?? null;
    }

    public static function serviceInstall($data)
    {
        $response = (self::instance())->syncInstall($data);
        
        self::saveLogI4Pro($data, $response, 'Instalando Equipamento');

        return $response;
    }

    public static function serviceMaintenance($data)
    {
        $response =  (self::instance())->syncMaintenance($data);

        self::saveLogI4Pro($data, $response, 'Atualizando Equipamento');

        return $response;
    }

    public static function serviceRemoval($data)
    {
        $response =  (self::instance())->syncRemoval($data);

        self::saveLogI4Pro($data, $response, 'Removendo Equipamento');

        return $response;
    }

    public static function updateSchedule($data)
    {
        $response = (self::instance())->scheduleTracker($data);

        self::saveLogI4Pro($data, $response, 'Atualizar agendamento na I4Pro');

        return $response;
    }

    private static function saveLogI4Pro($data, $response, $message = 'Log de requisicao i4pro'): void
    {
        (new LogsRepository())->create([
            'user_id' => 5,
            'action' => 'I4PRO LOGS',
            'description' => $message,
            'old_values' => json_encode($data),
            'new_values' => json_encode($response)
        ]);
    }
}
