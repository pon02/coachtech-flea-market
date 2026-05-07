<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Payment;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $payments = [
            'コンビニ払い',
            'カード支払い'
        ];

        foreach ($payments as $payment) {
            Payment::firstOrCreate([
                'name' => $payment,
            ]);
        }
    }
}
