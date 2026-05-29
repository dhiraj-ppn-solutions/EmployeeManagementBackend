<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Http\Resources\CountryResource;
use App\Http\Resources\StateResource;
use App\Http\Resources\CityResource;

class LocationController extends Controller
{
    /**
     * Get all countries.
     */
    public function getCountries()
    {
        return CountryResource::collection(Country::orderBy('name')->get());
    }

    /**
     * Get states of a country.
     */
    public function getStates($countryId)
    {
        $states = State::where('country_id', $countryId)->orderBy('name')->get();
        return StateResource::collection($states);
    }

    /**
     * Get cities of a state.
     */
    public function getCities($stateId)
    {
        $cities = City::where('state_id', $stateId)->orderBy('name')->get();
        return CityResource::collection($cities);
    }
}
