<?php

namespace App\Services;

use App\Jobs\PayoutOrderJob;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;

class MerchantService
{
    /**
     * Register a new user and associated merchant.
     * Hint: Use the password field to store the API key.
     * Hint: Be sure to set the correct user type according to the constants in the User model.
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return Merchant
     */
    public function register(array $data): Merchant
    {
        $user = new User();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = $data['api_key'];
        $user->type = User::TYPE_MERCHANT;
        $user->save();

        $merchant = new Merchant();
        $merchant->user_id = $user->id;
        $merchant->display_name = $user->name;
        $merchant->domain = $data['domain'];
        $merchant->save();

        return $merchant;
    }

    /**
     * Update the user
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return void
     */
    public function updateMerchant(User $user, array $data)
    {
        $merchant = Merchant::where('user_id', $user->id)->first();

        $merchant->domain = $data['domain'];
        $merchant->display_name = $data['name'];

        $merchant->save();
    }

    /**
     * Find a merchant by their email.
     * Hint: You'll need to look up the user first.
     *
     * @param string $email
     * @return Merchant|null
     */
    public function findMerchantByEmail(string $email): ?Merchant
    {
        $user = User::where('email', $email)->first();

        if (!$user || $user->type !== User::TYPE_MERCHANT) {
            return null;
        }

        return Merchant::where('user_id', $user->id)->first();
    }

    /**
     * Pay out all of an affiliate's orders.
     * Hint: You'll need to dispatch the job for each unpaid order.
     *
     * @param Affiliate $affiliate
     * @return void
     */
    public function payout(Affiliate $affiliate)
    {
        $orders = $affiliate->orders()->where('payout_status', Order::STATUS_UNPAID)->get();

        foreach ($orders as $order) {
            dispatch(new PayoutOrderJob($order));
        }
    }
}
