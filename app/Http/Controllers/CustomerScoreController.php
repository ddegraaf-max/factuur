<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\CustomerScoreService;
use Illuminate\Http\RedirectResponse;

/** De klantscore opnieuw laten rekenen, met een verse blik op de openbare bronnen. */
class CustomerScoreController extends Controller
{
    public function __construct(private CustomerScoreService $service) {}

    public function refresh(Customer $customer): RedirectResponse
    {
        abort_unless($this->service->available(), 404);

        $score = $this->service->score($customer, true);

        return back()->with('flash', $score['score'] === null
            ? __('Klantscore bijgewerkt: nog te weinig gegevens voor een score.')
            : __('Klantscore bijgewerkt: :grade, :label.', ['grade' => $score['grade'], 'label' => $score['label']]));
    }
}
