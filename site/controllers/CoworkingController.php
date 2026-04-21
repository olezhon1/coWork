<?php
// site/controllers/CoworkingController.php

class CoworkingController extends Controller
{
    public function show(): void
    {
        $id = Request::int('id');
        if ($id <= 0) { Response::notFound('Коворкінг не знайдено'); return; }

        $cw = (new CoworkingModel())->findById($id);
        if (!$cw) { Response::notFound('Коворкінг не знайдено'); return; }

        $gallery    = (new GalleryModel())->forCoworking($id);
        $hours      = (new OperatingHoursModel())->forCoworking($id);
        $features   = (new FeatureModel())->forCoworking($id);
        $workspaces = (new WorkspaceModel())->findByCoworking($id);
        $reviews    = (new ReviewModel())->findByCoworking($id, 50);

        $alreadyReviewed = false;
        if (Auth::check()) {
            $alreadyReviewed = (new ReviewModel())->userHasReviewedCoworking(Auth::id(), $id);
        }
        $canReview = Auth::check() && !$alreadyReviewed;

        $this->render('coworking/show', [
            'title'            => $cw['name'],
            'cw'               => $cw,
            'gallery'          => $gallery,
            'hours'            => $hours,
            'features'         => $features,
            'workspaces'       => $workspaces,
            'reviews'          => $reviews,
            'canReview'        => $canReview,
            'alreadyReviewed'  => $alreadyReviewed,
        ]);
    }
}
