<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Initial payment methods (PRD §11, §66). Extendable later via settings.
     */
    private const DEFAULT_PAYMENT_METHODS = [
        'Money Transfer',
        'Handed',
        'Check',
    ];

    public function run(): void
    {
        Setting::put(Setting::PAYMENT_METHODS, self::DEFAULT_PAYMENT_METHODS);
    }
}
