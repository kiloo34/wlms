<?php

use Illuminate\Support\Facades\Schedule;

// Scheduled tasks are registered here.

// Pengingat pekerjaan per tipe. Jam kirim & jadwal diatur di Pengaturan > Pengingat;
// dicek tiap 15 menit dan setiap pengingat hanya terkirim sekali.
Schedule::command('workload:send-reminders')->everyFifteenMinutes()->withoutOverlapping();
