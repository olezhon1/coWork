<?php
// site/controllers/SubscriptionsController.php

class SubscriptionsController extends Controller
{
    public function index(): void
    {
        $plans = (new SubscriptionPlanModel())->allActive();
        $this->render('subscriptions/index', [
            'title' => 'Абонементи',
            'plans' => $plans,
        ]);
    }

    public function buy(): void
    {
        $this->requireLogin();
        csrfCheck();

        $planId = Request::int('plan_id');
        $plan = (new SubscriptionPlanModel())->findById($planId);
        if (!$plan) {
            flash('err', 'План не знайдено');
            Response::redirect(siteUrl('subscriptions'));
            return;
        }

        try {
            (new SubscriptionModel())->purchase(Auth::id(), $plan);
            flash('ok', 'Абонемент «' . $plan['name'] . '» придбано. Приємного користування!');
        } catch (Throwable $e) {
            flash('err', 'Не вдалось придбати абонемент: ' . $e->getMessage());
        }

        Response::redirect(siteUrl('profile'));
    }
}
