<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Initial configurable currency list (PRD §39). No exchange rates — ever.
     *
     * @var array<string, string>
     */
    private const CURRENCIES = [
        'USD' => 'US Dollar',
        'EUR' => 'Euro',
        'EGP' => 'Egyptian Pound',
        'SAR' => 'Saudi Riyal',
        'AED' => 'UAE Dirham',
        'GBP' => 'British Pound',
    ];

    public function run(): void
    {
        foreach (self::CURRENCIES as $code => $name) {
            Currency::updateOrCreate(['code' => $code], ['name' => $name]);
        }
    }
}
