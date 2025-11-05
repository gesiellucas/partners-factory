<?php 

namespace App\Libraries\PartnerService\Repository;

use App\Libraries\Tscorp;

class EmailSenderRepository
{
    private static function instance()
    {
        return new Tscorp;
    }

    public static function sendEmail(array $data)
    {
        $to = $data['to'];
        $subject = $data['subject'];
        $content = $data['content'];
    
        return (self::instance())->sendemail($to, $subject, $content);
    }
}
