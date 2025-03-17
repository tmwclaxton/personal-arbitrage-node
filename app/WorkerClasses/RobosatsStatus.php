<?php

namespace App\WorkerClasses;

class RobosatsStatus

{
    const STATUS_WAITING_FOR_MAKER_BOND = 0; // WFB
    const STATUS_PUBLIC = 1; // PUB
    const STATUS_PAUSED = 2; // PAU
    const STATUS_WAITING_FOR_TAKER_BOND = 3; // TAK
    const STATUS_CANCELLED_USER_CANCEL = 4; // UCA - User Cancelled
    const STATUS_EXPIRED = 5; // EXP
    const STATUS_WAITING_FOR_TRADE_COLLATERAL_BUYER_INVOICE = 6; // WF2 - Waiting for 2
    const STATUS_WAITING_ONLY_FOR_SELLER_TRADE_COLLATERAL = 7; // WFE - Waiting for Escrow
    const STATUS_WAITING_ONLY_FOR_BUYER_INVOICE = 8; // WFI - Waiting for Invoice
    const STATUS_SENDING_FIAT_IN_CHATROOM = 9; // CHA - Chat
    const STATUS_FIAT_SENT_IN_CHATROOM = 10; // FSE - Fiat Sent Escrow
    const STATUS_IN_DISPUTE = 11; // DIS
    const STATUS_COLLABORATIVELY_CANCELLED = 12; // CCA - Collaborative Cancel
    const STATUS_SENDING_SATOSHIS_TO_BUYER = 13; // PAY
    const STATUS_SUCCESSFUL_TRADE = 14; // SUC
    const STATUS_FAILED_LIGHTNING_NETWORK_ROUTING = 15; // FAI
    const STATUS_WAIT_FOR_DISPUTE_RESOLUTION = 16; // WFR - Wait for Resolution
    const STATUS_MAKER_LOST_DISPUTE = 17; // MLD - Maker Lost Dispute
    const STATUS_TAKER_LOST_DISPUTE = 18; // TLD - Taker Lost Dispute
    const STATUS_BAD_REQUEST_ERROR = 99; // BAD

    public static array $status = [
        'WFB' => self::STATUS_WAITING_FOR_MAKER_BOND,
        'PUB' => self::STATUS_PUBLIC,
        'PAU' => self::STATUS_PAUSED,
        'TAK' => self::STATUS_WAITING_FOR_TAKER_BOND,
        'UCA' => self::STATUS_CANCELLED_USER_CANCEL,
        'EXP' => self::STATUS_EXPIRED,
        'WF2' => self::STATUS_WAITING_FOR_TRADE_COLLATERAL_BUYER_INVOICE,
        'WFE' => self::STATUS_WAITING_ONLY_FOR_SELLER_TRADE_COLLATERAL,
        'WFI' => self::STATUS_WAITING_ONLY_FOR_BUYER_INVOICE,
        'CHA' => self::STATUS_SENDING_FIAT_IN_CHATROOM,
        'FSE' => self::STATUS_FIAT_SENT_IN_CHATROOM,
        'DIS' => self::STATUS_IN_DISPUTE,
        'CCA' => self::STATUS_COLLABORATIVELY_CANCELLED,
        'PAY' => self::STATUS_SENDING_SATOSHIS_TO_BUYER,
        'SUC' => self::STATUS_SUCCESSFUL_TRADE,
        'FAI' => self::STATUS_FAILED_LIGHTNING_NETWORK_ROUTING,
        'WFR' => self::STATUS_WAIT_FOR_DISPUTE_RESOLUTION,
        'MLD' => self::STATUS_MAKER_LOST_DISPUTE,
        'TLD' => self::STATUS_TAKER_LOST_DISPUTE,
        'BAD' => self::STATUS_BAD_REQUEST_ERROR,
    ];

    public static array $statusText = [
        self::STATUS_WAITING_FOR_MAKER_BOND => 'Waiting for maker bond',
        self::STATUS_PUBLIC => 'Public',
        self::STATUS_PAUSED => 'Paused',
        self::STATUS_WAITING_FOR_TAKER_BOND => 'Waiting for taker bond',
        self::STATUS_CANCELLED_USER_CANCEL => 'Cancelled',
        self::STATUS_EXPIRED => 'Expired',
        self::STATUS_WAITING_FOR_TRADE_COLLATERAL_BUYER_INVOICE => 'Waiting for trade collateral and buyer invoice',
        self::STATUS_WAITING_ONLY_FOR_SELLER_TRADE_COLLATERAL => 'Waiting only for seller trade collateral',
        self::STATUS_WAITING_ONLY_FOR_BUYER_INVOICE => 'Waiting only for buyer invoice',
        self::STATUS_SENDING_FIAT_IN_CHATROOM => 'Sending fiat - In chatroom',
        self::STATUS_FIAT_SENT_IN_CHATROOM => 'Fiat sent - In chatroom',
        self::STATUS_IN_DISPUTE => 'In dispute',
        self::STATUS_COLLABORATIVELY_CANCELLED => 'Collaboratively cancelled',
        self::STATUS_SENDING_SATOSHIS_TO_BUYER => 'Sending satoshis to buyer',
        self::STATUS_SUCCESSFUL_TRADE => 'Successful trade',
        self::STATUS_FAILED_LIGHTNING_NETWORK_ROUTING => 'Failed lightning network routing',
        self::STATUS_WAIT_FOR_DISPUTE_RESOLUTION => 'Wait for dispute resolution',
        self::STATUS_MAKER_LOST_DISPUTE => 'Maker lost dispute',
        self::STATUS_TAKER_LOST_DISPUTE => 'Taker lost dispute',
        self::STATUS_BAD_REQUEST_ERROR => 'Bad Request Error',
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
