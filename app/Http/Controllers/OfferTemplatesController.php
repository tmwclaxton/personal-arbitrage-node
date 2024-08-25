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
        return $this->offerUpdate($request, $template);
    }

    public function deleteTemplate($id): \Illuminate\Http\RedirectResponse
    {
        $template = PostedOfferTemplate::find($id);
        $template->delete();

        return redirect()->route('offers.posting.index');
    }

    public function editTemplate(Request $request): \Illuminate\Http\RedirectResponse
    {
        $template = PostedOfferTemplate::find($request->id);
        return $this->offerUpdate($request, $template);
    }

    public function autoCreateTemplates() {

    }

    /**
     * @param Request $request
     * @param $template
     * @return \Illuminate\Http\RedirectResponse
     */
    public function offerUpdate(Request $request, $template): \Illuminate\Http\RedirectResponse
    {
        // validate the request (i.e. bond size must be 3 or greater)
        $request->validate([
            'provider' => 'required|array|max:1',
            'currency' => 'required|size:3|alpha',
            'premium' => 'required|numeric|min:0',
            'min_amount' => 'required|numeric|min:0|gt:0',
            'max_amount' => 'nullable|numeric|min:0|gte:0',
            'payment_methods' => 'required|array|min:1',
            'bond_size' => 'required|numeric|min:1|gte:3',
            'auto_create' => 'required|boolean',
        ]);


        $template->provider = $request->provider[0];
        $template->currency = $request->currency;
        $template->premium = $request->premium;
        $template->min_amount = $request->min_amount;
        $template->max_amount = $request->max_amount;
        $template->payment_methods = json_encode($request->payment_methods);
        $template->bond_size = $request->bond_size;
        $template->auto_create = $request->auto_create;
        $template->save();

        return redirect()->route('offers.posting.index');
    }
}
