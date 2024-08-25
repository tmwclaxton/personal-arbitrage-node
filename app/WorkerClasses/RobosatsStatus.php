<?php

namespace App\WorkerClasses;

class RobosatsStatus

{
    public static $status = [
        'WFB' => 0,
        'PUB' => 1,
        'PAU' => 2,
        'TAK' => 3,
        'UCA' => 4,
        'EXP' => 5,
        'WF2' => 6,
        'WFE' => 7,
        'WFI' => 8,
        'CHA' => 9,
        'FSE' => 10,
        'DIS' => 11,
        'CCA' => 12,
        'PAY' => 13,
        'SUC' => 14,
        'FAI' => 15,
        'WFR' => 16,
        'MLD' => 17,
        'TLD' => 18,
    ];

    public static $statusText = [
        0 => 'Waiting for maker bond',
        1 => 'Public',
        2 => 'Paused',
        3 => 'Waiting for taker bond',
        4 => 'Cancelled',
        5 => 'Expired',
        6 => 'Waiting for trade collateral and buyer invoice',
        7 => 'Waiting only for seller trade collateral',
        8 => 'Waiting only for buyer invoice',
        9 => 'Sending fiat - In chatroom',
        10 => 'Fiat sent - In chatroom',
        11 => 'In dispute',
        12 => 'Collaboratively cancelled',
        13 => 'Sending satoshis to buyer',
        14 => 'Successful trade',
        15 => 'Failed lightning network routing',
        16 => 'Wait for dispute resolution',
        17 => 'Maker lost dispute',
        18 => 'Taker lost dispute',
        99 => 'Bad Request Error',
    ];

    public static function getStatusText($status)
    {
        return self::$statusText[$status];
    }

    public static function getStatus($statusText)
    {
        return self::$status[$statusText];
    }

    public static function getStatuses()
    {
        return self::$status;
    }

    public static function getStatusTexts()
    {
        return self::$statusText;
    }
}

