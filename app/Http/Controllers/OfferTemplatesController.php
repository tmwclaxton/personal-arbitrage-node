<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\PostedOfferTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OfferTemplatesController extends Controller
{
    public function postingPage()
    {
        return Inertia::render('PostingOffers', [
            'templates' => PostedOfferTemplate::all(),
        ]);
    }

    public function createTemplate(Request $request): \Illuminate\Http\RedirectResponse
    {
        $template = new PostedOfferTemplate();
        $this->offerUpdate($request, $template);

        return redirect()->route('offers.posting.index');
    }

    public function deleteTemplate($id): \Illuminate\Http\RedirectResponse
    {
        $template = PostedOfferTemplate::find($id);
        $template->delete();

        return redirect()->route('offers.posting.index');
    }

    public function editTemplate(Request $request): array
    {
        $template = PostedOfferTemplate::find($request->id);
        return $this->offerUpdate($request, $template);
    }

    public function autoCreateTemplates() {

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
            'provider' => 'required|array|max:1',
            'currency' => 'required|size:3|alpha',
            'premium' => 'required',
            'min_amount' => 'required|numeric|min:0|gt:0',
            'max_amount' => 'nullable|numeric|min:0|gte:0',
            'payment_methods' => 'required|array|min:1',
            'bond_size' => 'required|numeric|min:1|gte:3',
            'auto_create' => 'required|boolean',
            'quantity' => 'required|numeric|min:1',
            'cooldown' => 'required|numeric|min:0',
            'ttl' => 'required|numeric|min:0',
        ]);

        $template->type = $request->type;
        $template->provider = $request->provider[0];
        $template->currency = $request->currency;
        $template->premium = (float) $request->premium;
        $template->min_amount = $request->min_amount;
        $template->max_amount = $request->max_amount;
        $template->payment_methods = json_encode($request->payment_methods);
        $template->bond_size = $request->bond_size;
        $template->auto_create = $request->auto_create;
        $template->quantity = $request->quantity;
        $template->cooldown = $request->cooldown;
        $template->ttl = $request->ttl;
        $template->save();

        return [
            'template' => $template,
        ];
    }
}
