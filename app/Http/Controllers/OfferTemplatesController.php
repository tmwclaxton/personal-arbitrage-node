<?php

namespace App\Http\Controllers;

use App\Models\AdminDashboard;
use App\Models\BtcFiat;
use App\Models\Offer;
use App\Models\PaymentMethod;
use App\Models\PostedOfferTemplate;
use App\WorkerClasses\HelperFunctions;
use Illuminate\Http\Request;
use Inertia\Inertia;
use SebastianBergmann\Template\Template;

class OfferTemplatesController extends Controller
{
    public function postingPage()
    {
        $helpers = new HelperFunctions();
        $providers = $helpers->getOnlineProviders();
        $templates = PostedOfferTemplate::all();
        $currencies = BtcFiat::all()->pluck('currency');
        $adminDashboard = AdminDashboard::all()->first();
        //PaymentMethod::all()->pluck('name')
        $paymentMethods = json_decode($adminDashboard->payment_methods);

        return Inertia::render('PostingOffers', [
            'templates' => $templates,
            'paymentMethods' => $paymentMethods,
            'providers' => $providers,
            'currencies' => $currencies
        ]);
    }

    public function createTemplate(Request $request): array
    {
        $template = new PostedOfferTemplate();
        $helpers = new HelperFunctions();
        $template->slug = $helpers->generateSlug();
        $this->offerUpdate($request, $template);

        return [
            'template' => $template,
        ];
    }

    public function deleteTemplate($id)
    {
        $template = PostedOfferTemplate::find($id);
        $template->delete();

        return [
            'template' => $template,
        ];
    }

    public function editTemplate(Request $request): array
    {
        $template = PostedOfferTemplate::find($request->id);
        return $this->offerUpdate($request, $template);
    }

    /**
     * @param Request $request
     * @param $template
     * @return array
     */
    public function offerUpdate(Request $request, $template): array
    {
        // validate the request (i.e. bond size must be 3 or greater)
        $request->validate([
            'type' => 'required|in:buy,sell',
            'provider' => 'required|array',
            'currency' => 'required|size:3|alpha',
            'premium' => 'required',
            'min_amount' => 'required|numeric|min:0|gt:0',
            'max_amount' => 'nullable|numeric|min:0|gte:0',
            'latitude' => 'nullable',
            'longitude' => 'nullable',
            'payment_methods' => 'required|array|min:1',
            'bond_size' => 'required|numeric|min:1|gte:3',
            'auto_create' => 'required|boolean',
            'quantity' => 'required|numeric|min:1',
            'cooldown' => 'required|numeric|min:0',
            'ttl' => 'required|numeric|min:0',
            'escrow_time' => 'required|numeric|min:0',
            'custom_message' => 'nullable',
            'disable_all_messages' => 'nullable|boolean',
        ]);

        $template->type = $request->type;
        $template->provider = json_encode($request->provider);
        $template->currency = $request->currency;
        $template->premium = (float) $request->premium;
        $template->min_amount = $request->min_amount;
        $template->max_amount = $request->max_amount;
        $template->payment_methods = json_encode($request->payment_methods);
        $template->bond_size = $request->bond_size;
        $template->auto_create = $request->auto_create;
        $template->quantity = $request->quantity;
        $template->cooldown = $request->cooldown;
        $template->latitude = $request->latitude;
        $template->longitude = $request->longitude;
        $template->ttl = $request->ttl;
        $template->escrow_time = $request->escrow_time;
        $template->custom_message = $request->custom_message;
        $template->disable_all_messages = $request->disable_all_messages;
        $template->save();

        return [
            'template' => $template,
        ];
    }
}
