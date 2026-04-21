<?php
// site/controllers/HomeController.php

class HomeController extends Controller
{
    public function index(): void
    {
        $city = selectedCity();
        $coworkings = (new CoworkingModel())->popular(20, $city);

        // Для кожного — прикріпимо топ-3 фічі та сьогоднішній графік (для статусу)
        $featureModel = new FeatureModel();
        $hoursModel = new OperatingHoursModel();
        foreach ($coworkings as &$c) {
            $c['top_features'] = $featureModel->forCoworking((int) $c['id'], 3);
            $c['hours'] = $hoursModel->forCoworking((int) $c['id']);
        }
        unset($c);

        $workspaceTypes = [
            ['key' => WorkspaceType::Open->value,       'label' => 'Open Space',       'tagline' => 'Недорого, гнучко'],
            ['key' => WorkspaceType::Conference->value, 'label' => 'Meeting Room',     'tagline' => 'Для команд'],
            ['key' => WorkspaceType::Cabinet->value,    'label' => 'Private Office',   'tagline' => 'Максимальна зосередженість'],
            ['key' => WorkspaceType::Silent->value,     'label' => 'Тихий простір',    'tagline' => 'Для концентрації'],
        ];
        $wm = new WorkspaceModel();
        foreach ($workspaceTypes as &$wt) {
            $wt['min_price'] = $wm->minPriceByType($wt['key'], $city);
            $wt['count']     = $wm->coworkingsCountByType($wt['key'], $city);
        }
        unset($wt);

        $plans = (new SubscriptionPlanModel())->allActive();

        $mapPoints = (new CoworkingModel())->withCoordinates($city);

        $topFeatures = (new FeatureModel())->top(8);

        $this->render('home/index', [
            'title'          => 'coWork — твій ідеальний офіс на годину чи день',
            'coworkings'     => $coworkings,
            'workspaceTypes' => $workspaceTypes,
            'plans'          => $plans,
            'mapPoints'      => $mapPoints,
            'topFeatures'    => $topFeatures,
            'city'           => $city,
        ]);
    }
}
