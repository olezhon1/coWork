<?php
// site/controllers/CoworkingsController.php

class CoworkingsController extends Controller
{
    public function index(): void
    {
        $page = max(1, Request::int('p', 1));
        $perPage = 12;
        $offset = ($page - 1) * $perPage;

        $filters = [
            'city'               => selectedCity() ?: Request::str('city') ?: null,
            'is_24_7'            => Request::str('is_24_7'),
            'search'             => Request::str('q'),
            'workspace_type_key' => Request::str('type') ?: null,
            'price_max'          => Request::str('price_max') !== '' ? (float) Request::str('price_max') : null,
            'feature_ids'        => array_map('intval', (array) ($_GET['features'] ?? [])),
        ];
        $filters['feature_ids'] = array_filter($filters['feature_ids']);

        $sort = Request::str('sort', 'rating');
        if (!in_array($sort, ['rating', 'price', 'new', 'name'], true)) {
            $sort = 'rating';
        }

        $cwm = new CoworkingModel();
        $coworkings = $cwm->search($filters, $sort, $offset, $perPage);
        $total = $cwm->count($filters);
        $totalPages = (int) max(1, ceil($total / $perPage));

        $featureModel = new FeatureModel();
        $hoursModel = new OperatingHoursModel();
        foreach ($coworkings as &$c) {
            $c['top_features'] = $featureModel->forCoworking((int) $c['id'], 3);
            $c['hours'] = $hoursModel->forCoworking((int) $c['id']);
        }
        unset($c);

        $allFeatures = $featureModel->findAll();
        $allCities   = $cwm->distinctCities();

        $this->render('coworkings/index', [
            'title'       => 'Каталог коворкінгів',
            'coworkings'  => $coworkings,
            'total'       => $total,
            'page'        => $page,
            'totalPages'  => $totalPages,
            'filters'     => $filters,
            'sort'        => $sort,
            'allFeatures' => $allFeatures,
            'allCities'   => $allCities,
        ]);
    }
}
