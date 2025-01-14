<?php

namespace App\Console\Commands;

use Aloha\Twilio\Twilio;
use App\Models\Offer;
use App\Services\SlackService;
use App\WorkerClasses\Robosats;
use App\WorkerClasses\RobosatsStatus;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;

class WarningSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:warning-system';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for offers that have been in a status for over 20 minutes and trigger a warning';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $offers = Offer::where('status', '>', 2)
            ->where('status', '<', 14)
            ->whereNotIn('status', [4,5,12])
            ->get();

        foreach ($offers as $offer) {
            $key = 'offer_status_' . $offer->id;
            $warnKey = 'offer_warning_' . $offer->id;

            if (Redis::exists($key)) {
                $data = json_decode(Redis::get($key), true);
                $status = $data['status'];
                $timestamp = $data['timestamp'];

                if ($offer->status == $status) {
                    if (Carbon::parse($timestamp)->diffInMinutes(now()) >= 10) {
                        if (Redis::exists($warnKey)) {
                            $warnData = json_decode(Redis::get($warnKey), true);
                            $lastWarning = $warnData['last_warning'];
                            $warnCount = $warnData['warn_count'];

                            if (Carbon::parse($lastWarning)->diffInMinutes(now()) >= 20 && $warnCount < 5) {
                                $this->triggerWarning($offer, $data, $warnData);
                                $warnData['last_warning'] = now();
                                $warnData['warn_count'] += 1;
                                Redis::set($warnKey, json_encode($warnData));
                            }
                        } else {
                            $this->triggerWarning($offer, $data, ['last_warning' => now(), 'warn_count' => 0]);
                            Redis::set($warnKey, json_encode(['last_warning' => now(), 'warn_count' => 1]));
                        }
                    }
                } else {
                    Redis::set($key, json_encode(['status' => $offer->status, 'timestamp' => now()]));
                    Redis::del($warnKey);
                }
            } else {
                Redis::set($key, json_encode(['status' => $offer->status, 'timestamp' => now()]));
            }
        }
    }

    /**
     * Trigger a warning for the offer.
     *
     * @param \App\Models\Offer $offer
     * @return void
     * @throws GuzzleException
     */
    protected function triggerWarning(Offer $offer, array $data, array $warnData): void
    {
        // $twilio = new Twilio(getenv('TWILIO_ACCOUNT_SID'), getenv('TWILIO_AUTH_TOKEN'), getenv('TWILIO_PHONE_NUMBER'));
        // $message = 'Offer ' . $offer->id . ' has been in no. ' . $offer->status .
        //     ' status (' . RobosatsStatus::getStatusText($offer->status) . ') for ' . Carbon::parse($data['timestamp'])->diffInMinutes() .
        //     '. Please check the offer' .
        //     ' using the following token: ' . $offer->robots()->first()->token . ' and take necessary action.';
        //
        // $twilio->message("07837370669", $message);
        // $twilio->message("07711800899", $message);

        $slack = new SlackService();
        $slack->sendMessage('*Warning*: Offer ' . $offer->robosatsId . ' has been in no. ' . $offer->status .
            ' status (' . RobosatsStatus::getStatusText($offer->status) . ') for ' . round(Carbon::parse($data['timestamp'])->diffInMinutes()) .
            ' minutes. Please check the offer' .
            ' using the following token: ' . $offer->robots()->first()->token . ' and take necessary action.');



    }
}
