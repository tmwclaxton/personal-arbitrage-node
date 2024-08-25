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
            'postedOffers' => PostedOfferTemplate::all(),
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

        return redirect()->route('offer-templates.posting-page');
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
        $template->provider = $request->provider;
        $template->currency = $request->currency;
        $template->premium = $request->premium;
        $template->min_amount = $request->min_amount;
        $template->max_amount = $request->max_amount;
        $template->payment_methods = $request->payment_methods;
        $template->bond_size = $request->bond_size;
        $template->auto_create = $request->auto_create;
        $template->save();

        return redirect()->route('offer-templates.posting-page');
    }
}
