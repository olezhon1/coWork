<?php
// site/controllers/CityController.php

class CityController extends Controller
{
    public function set(): void
    {
        $city = trim((string) Request::str('city'));
        setSelectedCity($city !== '' ? $city : null);
        Response::back();
    }
}
