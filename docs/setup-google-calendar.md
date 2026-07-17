Buka Google Cloud Console → buat project → aktifkan Google Calendar API → buat OAuth 2.0 Client ID (tipe "Web application").
Set Authorized redirect URI ke https://domain-anda.com/integrations/google-calendar/callback (atau http://localhost:8000/... untuk lokal).
Isi di .env: GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_CALENDAR_REDIRECT_URI (sudah ada templatenya di .env.example).
Pastikan php artisan migrate dijalankan di server dan queue worker aktif (php artisan queue:work — sudah bagian dari composer run dev/Docker profile full) supaya job sinkron benar-benar diproses.