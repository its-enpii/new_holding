<?php

use Illuminate\Support\Facades\Schedule;

// Periksa lisensi yang mendekati kadaluarsa atau sudah kadaluarsa setiap pagi.
Schedule::command('licenses:check-expiry')->dailyAt('07:00');
