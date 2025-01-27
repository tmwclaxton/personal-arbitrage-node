<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class resetTemplateCooldowns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reset-template-cooldowns';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the posted_offer_templates last_accepted to now - cooldown';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $templates = \App\Models\PostedOfferTemplate::all();
        foreach ($templates as $template) {
            $cooldown = $template->cooldown ?? 0;
            $template->last_accepted = \Illuminate\Support\Carbon::now()->subSeconds($cooldown);
            $template->save();
        }
    }
}
