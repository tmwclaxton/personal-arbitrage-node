<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateInvoice implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // POST
        // http://192.168.1.39:12596/mainnet/satstralia/api/order/?order_id=18201
        //
        // {
        // "action": "update_invoice",
        // "invoice": "-----BEGIN PGP SIGNED MESSAGE-----\nHash: SHA512\n\nlnbc807880n1pnwhej0pp5qd792yakggtjhv6zhkn4fgvxtsafjlj5efhxzdsnyhc8s2vprmdqdqqcqzzsxqrrsssp5zgcv7r4npgnx4870pvcp04ktj88d4xw42t90rvzmp37z8wr66lss9qxpqysgqmsyl8r4lukaencu98suy5e4dklpylcyq2yshxzzgg656hd60a40q8u082h52y7h7wflxd8errzzuc7v607cnjrdpzsyfh6z8w7laz9gplgu9nh\n-----BEGIN PGP SIGNATURE-----\n\nwsBzBAEBCgAnBYJm6+Z5CZCmG3pQCa7YahYhBKognPjerUaPkfYseaYbelAJ\nrthqAAABlwgAozMdp+Yc3cN7XsWr51AyWnA0NAt9DkLoQ2XDTa//nYVKOsH1\nAdVug0y3fr+30MLRBg44FoPjLalDx6xgMxSehpWZwWvX4rLPCiGA4WDS6g0W\n78LzIBexgOYNGrGzB4JTBJGfT7+3t5uGYDnto23OK7YD6F+Anbp+OiJRrvVy\nx2C54qmFRXINrtFLCqrXWBdpSe11UYOXbsPj93+2iosHr9jVsZ/0IgxK4rBC\ngp43TyW65Ah0bAbe+q/mK6eAJS5Kpu80MJlLXxkckxRWgBxoWNnLGGDsKHRO\n58y71Be0XeifbqS0r+UrQZB7gJPlz12ogevbcZKIbgJ9paLxLnjYUg==\n=QHgd\n-----END PGP SIGNATURE-----\n",
        // "routing_budget_ppm": "1200"
        // }
    }
}
