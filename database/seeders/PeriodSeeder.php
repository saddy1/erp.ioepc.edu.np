<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Period;
use Carbon\Carbon;

class PeriodSeeder extends Seeder
{
    public function run(): void
    {
        $this->createShift('morning', '07:00', 7); // 7 * 45min = 5h 15m (to 12:15)
        $this->createShift('day', '10:00', 8);     // 8 * 45min = 6h (to 16:00)
    }

    private function createShift(string $shift, string $start, int $count): void
    {
        $startTime = Carbon::createFromFormat('H:i', $start);

        for ($i = 1; $i <= $count; $i++) {
            $endTime = (clone $startTime)->addMinutes(45);

            Period::updateOrCreate(
                ['shift' => $shift, 'order' => $i],
                [
                    'label'      => strtoupper(substr($shift,0,1))."P{$i}",
                    'start_time' => $startTime->format('H:i'),
                    'end_time'   => $endTime->format('H:i'),
                ]
            );

            $startTime = $endTime;
        }
    }
}
