<?php

namespace App\Services;

use App\Exceptions\AffiliateCreateException;
use App\Mail\AffiliateCreated;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;

class AffiliateService
{
    public function __construct(
        protected ApiService $apiService
    )
    {
    }

    /**
     * Create a new affiliate for the merchant with the given commission rate.
     *
     * @param Merchant $merchant
     * @param string $email
     * @param string $name
     * @param float $commissionRate
     * @return Affiliate
     */
    public function register(Merchant $merchant, string $email, string $name, float $commissionRate): Affiliate
    {
        if ($merchant->user->email === $email) {
            throw new AffiliateCreateException('The email is already registered as a merchant');
        }

        if (Affiliate::whereHas('user', function (Builder $query) use ($email) {
            $query->where('email', $email);
        })->exists()) {
            throw new AffiliateCreateException('The email is already registered as an affiliate');
        }

        $discountCodeResponse = $this->apiService->createDiscountCode($merchant);
        $discountCode = $discountCodeResponse['code'];

        $user = User::firstOrCreate(['email' => $email], [
            'name' => $name
        ]);

        $affiliate = Affiliate::create([
            'merchant_id' => $merchant->id,
            'user_id' => $user->id,
            'commission_rate' => $commissionRate,
            'discount_code' => $discountCode
        ]);

        Mail::to($user->email)->send(new AffiliateCreated($affiliate));

        return $affiliate;
    }
}
