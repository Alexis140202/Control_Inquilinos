<?php

return [
    'late_fee_per_day' => (float) env('RENTAL_LATE_FEE_PER_DAY', 5),
    'late_fee_max_days' => (int) env('RENTAL_LATE_FEE_MAX_DAYS', 30),
];
