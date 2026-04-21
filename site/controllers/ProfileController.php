<?php
// site/controllers/ProfileController.php

class ProfileController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();

        $bookings = (new BookingModel())->findByUser(Auth::id());
        $subscriptions = (new SubscriptionModel())->findByUser(Auth::id());

        $this->render('profile/index', [
            'title'         => 'Мій профіль',
            'user'          => Auth::user(),
            'bookings'      => $bookings,
            'subscriptions' => $subscriptions,
        ]);
    }

    public function cancelBooking(): void
    {
        $this->requireLogin();
        csrfCheck();
        $id = Request::int('booking_id');
        if ((new BookingModel())->cancel($id, Auth::id())) {
            flash('ok', 'Бронювання скасовано');
        } else {
            flash('err', 'Не вдалось скасувати бронювання');
        }
        Response::redirect(siteUrl('profile'));
    }
}
