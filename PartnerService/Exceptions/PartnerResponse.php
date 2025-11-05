<?php 

namespace App\Libraries\PartnerService\Exceptions;

use App\Repositories\LogsRepository;

class PartnerResponse
{
    const SUCCESS_KEY = 'success';
    const ERROR_KEY = 'error';
    
    public static function init(): void
    {
        if(session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function pushError($error): void
    {
        self::init();
        $_SESSION[self::ERROR_KEY][] = $error;
    }

    public static function pushSuccess($success): void
    {
        self::init();
        $_SESSION[self::SUCCESS_KEY][] = $success;
    }

    public static function clear(): void
    {
        self::logResponse();

        unset($_SESSION[self::SUCCESS_KEY]);
        unset($_SESSION[self::ERROR_KEY]);
    }

    public static function getResponse(): array
    {
        self::init();

        return [
            self::SUCCESS_KEY => $_SESSION[self::SUCCESS_KEY] ?? [],
            self::ERROR_KEY => $_SESSION[self::ERROR_KEY] ?? []
        ];
    }

    public static function getMessage(): string
    {
        return !empty($_SESSION[self::SUCCESS_KEY])
            ? 'Ordem de serviço recebidas'
            : 'Erro ao receber ordens';
    }

    public static function prepareResponse(): array
    {
        $response = self::getResponse();
        $message = self::getMessage();

        return [
            'status' => empty($response[self::ERROR_KEY]),
            'message' => $message,
            'report' => $response
        ];
    }

    public static function getCode(): int
    {
        return !empty($response[self::ERROR_KEY]) ? 400 : 200;
    }

    public static function logResponse(): void
    {
        self::logRepository()->update(
            $_SESSION['partner_auth']['log_id'],
            [ 'new_values' => json_encode(self::getResponse()) ]
        );

        unset($_SESSION['partner_auth']['log_id']);
    }

    public static function initLog(string $action, string $description): void
    {
        self::init();
        $request = (service('request'))->getJSON(true);
        
        $logRequest = self::logRepository()->create([
            'user_id' => 5,
            'action' => $action,
            'description' => $description,
            'old_values' => json_encode($request)
        ]);

        unset($_SESSION['partner_auth']['log_id']);
        $_SESSION['partner_auth']['log_id'] = $logRequest; 
    }

    public static function saveLogError($message, $trace = null)
    {
        return self::logRepository()->create([
            'user_id' => 5,
            'action' => 'LOG DE ERRO',
            'description' => 'Dados de requisição dos parceiros',
            'old_values' => json_encode($message),
            'new_values' => json_encode(['trace' => $trace])
        ]);
    }

    private static function logRepository()
    {
        return (new LogsRepository());
    }

}