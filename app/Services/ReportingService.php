<?php

namespace App\Services;

use App\Models\AdminDashboard;

class ReportingService
{
    public const REDIS_PREFIX = "reporting";

    public static function sendReportingEmail(string $amount, string $sender){

        $mail_service = new GmailService(self::REDIS_PREFIX);

        $adminDashboard = AdminDashboard::get([
            'email_reporting_recipient',
            'reporting_message'
        ])->first();

        $body = str_replace("{amount}", $amount, $adminDashboard->reporting_message);
        $body = str_replace("{sender}", $sender, $body);

        $subject = "P2P Crypto Trade Notice";

        $mail_service->sendEmail(
            $adminDashboard->email_reporting_recipient,
            $subject,
            $body
        );
    }

}
