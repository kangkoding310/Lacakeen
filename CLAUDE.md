# CLAUDE.md

Panduan kerja untuk Claude Code di project **Lacakeen** — aplikasi manajemen task/project berbasis Laravel + Inertia + Vue.

## Tech Stack

**Backend**
- Laravel 13 (PHP 8.3+), Inertia Laravel v2 (`inertiajs/inertia-laravel`)
- PostgreSQL 14+, `spatie/laravel-permission` untuk role (admin, dsb), `tightenco/ziggy` untuk expose named routes ke JS
- Laravel Pint untuk code style (PSR-12), PHPUnit sebagai test runner (bukan Pest — lihat README untuk alasannya)

**Frontend**
- Vue 3 (Composition API, `<script setup>`) + Inertia.js v2 (`@inertiajs/vue3`, SPA-like navigation tanpa REST API terpisah)
- TypeScript 5.9 dengan `strict: true` — tapi **adopsinya belum merata** (lihat bagian TypeScript di bawah)
- Pinia untuk state management (baru dipakai sangat minim — 1 store: `stores/date.ts`)
- Tailwind CSS 3, ikon `lucide-vue-next`, drag-and-drop `vuedraggable` (dipakai di Kanban board)
- Build tool: Vite 8 + `laravel-vite-plugin`

**Terpasang di `package.json` tapi BELUM dipakai di codebase manapun** — jangan asumsikan sudah terintegrasi: `reka-ui`, `vee-validate`, `zod`, `@vee-validate/zod`, `class-variance-authority` (cva), `tailwind-merge`, `clsx`, `@tanstack/vue-table`. Kalau mau mulai pakai salah satunya untuk pola baru (mis. `cn()` helper atau validasi form berbasis schema), diskusikan dulu karena belum ada preseden di repo.

## Arsitektur

### Backend: Controller tipis → Request → Action → Model
Pola yang sudah settled (lihat `app/Http/Controllers/TaskController.php`, `app/Actions/Task/*`):
1. **Controller** — hanya orkestrasi: terima Form Request yang sudah tervalidasi, panggil Action, return Inertia response/redirect. Tidak ada business logic di controller.
2. **Form Request** (`app/Http/Requests/<Domain>/`) — validasi input, suffix `Request` (`StoreTaskRequest`, `MoveTaskRequest`).
3. **Action** (`app/Actions/<Domain>/`) — 1 class = 1 use case, method publik `handle()`, suffix `Action`. Business logic, transaksi DB, otorisasi tambahan, side effect (notifikasi, activity log) ada di sini.
4. **Policy** (`app/Policies/`) — otorisasi berbasis relasi (mis. cek keanggotaan project + role pivot). Dipanggil lewat `$this->authorize()` di controller ATAU `Gate::authorize()` di dalam Action — **keduanya dipakai, tidak konsisten**, ikuti pola yang sudah ada di area yang disentuh.
5. **Enum** (`app/Enums/`) — backed enum untuk nilai tetap (mis. `TaskPriority`).

Model pakai query scope custom untuk multi-tenancy/visibility, mis. `Task::visibleTo($user)`, `Project::visibleTo($user)` — selalu pakai scope ini daripada query manual saat menampilkan data ke user.

### Frontend: pola target (baru diterapkan di fitur Tasks)
Refactor terbaru (`refactor backend for clean code`, `refactor frontend feature task`) memperkenalkan struktur berikut untuk fitur **Tasks** sebagai pola target:
- `resources/js/services/<feature>Service.ts` — wrapper `router.get/patch/delete` dari Inertia, object literal berisi method per aksi (bukan class).
- `resources/js/composables/use<Feature>.ts` — reactive logic yang baca shared Inertia props (`usePage()`), prefix wajib `use`.
- `resources/js/types/<feature>.ts` — interface TS yang mirror struktur data dari backend (resource/Model).
- `resources/js/constants/<feature>.ts` — daftar opsi tetap + mapping class Tailwind (mis. badge warna per priority).
- `resources/js/utils/<name>.ts` — pure helper function, tidak reactive, tidak tahu soal Inertia.

**PENTING — migrasi belum selesai.** Fitur lain (Projects, Members, Workspaces, Calendar, Chat, Reporting, Workflow, Integrations, Settings, Notifications, Inbox, HelpCenter) masih pakai pola lama: logic inline di dalam `<script setup>` tanpa TypeScript, tanpa service/types/constants terpisah. Jangan asumsikan pola baru berlaku di seluruh codebase — cek dulu fitur yang sedang disentuh. Kalau task menyentuh fitur lama dan scope-nya wajar untuk dirapikan sekalian, boleh migrasikan ke pola baru; kalau tidak, jangan refactor besar-besaran di luar scope task.

### Struktur folder frontend
```
resources/js/
  Pages/<Feature>/Index.vue, Show.vue, Partials/   # 1 route Inertia = 1 Page
  Components/                                       # komponen shared/spesifik-fitur (flat)
  Components/ui/                                    # primitif UI generik (Modal, AppSelect, AvatarStack, EmptyState)
  Layouts/                                          # AppLayout, AuthenticatedLayout, GuestLayout
  services/ · composables/ · types/ · constants/ · utils/
```

## Konvensi Coding

### Naming
- Vue component file: PascalCase (`TaskCard.vue`)
- Composable: camelCase, prefix `use` (`useTaskComposer.ts`)
- Service: camelCase + suffix `Service`, diekspor sebagai object literal (`export const taskService = { ... }`), bukan class
- Types/interface: PascalCase, nama file camelCase sesuai domain
- PHP Controller: PascalCase + suffix `Controller`; Action: PascalCase + suffix `Action` dengan method `handle()`; Form Request: PascalCase + suffix `Request`

### TypeScript — WAJIB untuk file baru
- Saat ini hanya **6 dari 51** file `.vue` pakai `<script setup lang="ts">`; sisanya legacy plain JS meski `tsconfig.json` strict. **Setiap file `.vue` baru, atau file lama yang disentuh signifikan, harus pakai `lang="ts"`.**
- Pakai generic type syntax untuk props/emits (`defineProps<{ task: Task }>()`, `defineEmits<{ close: [] }>()`), bukan runtime object syntax (`defineProps({ task: Object })`) — lihat `TaskCard.vue` / `TaskDetailDrawer.vue` sebagai contoh.
- `strict`, `noUnusedLocals`, `noUnusedParameters`, `noFallthroughCasesInSwitch`, `noImplicitReturns` semua aktif — jangan pakai `any` tanpa alasan kuat, hapus import/variable yang tidak dipakai (akan gagal `npm run type-check`).
- Path alias `@/*` → `resources/js/*`, dan `ziggy-js` → `vendor/tightenco/ziggy`. Pakai alias, jangan relative path panjang (`../../../`).

### Import order
Ikuti urutan yang sudah konsisten di file-file yang sudah pakai `lang="ts"` (lihat `TaskDetailDrawer.vue`):
1. Komponen lokal (`@/Components/...`, `@/Layouts/...`)
2. Utils/composables/services/constants lokal (`@/utils/...`, `@/composables/...`, `@/services/...`, `@/constants/...`)
3. `import type` untuk types lokal (`@/types/...`)
4. Library eksternal (`@inertiajs/vue3`, `lucide-vue-next`, dll)
5. Vue core (`computed`, `ref`, `watch`, dst dari `'vue'`) paling akhir

### Styling
- Tailwind utility-first langsung di template, tidak ada abstraksi `@apply` yang luas. Palet: `slate` untuk neutral, `blue-600` sebagai primary accent, `red`/`emerald`/`orange`/`violet` untuk warna semantic (priority, status).
- Jangan perkenalkan pola styling baru (cva/tailwind-merge) tanpa memastikan itu memang dibutuhkan — belum ada preseden di repo.

## Command Penting

| Perintah | Fungsi |
|---|---|
| `npm run dev` | Vite dev server |
| `composer run dev` | Jalankan server + queue worker + log (pail) + vite bareng (pakai `concurrently`) |
| `npm run build` | Build produksi |
| `npm run type-check` | `vue-tsc --noEmit` — **wajib clean** sebelum menganggap perubahan `.vue`/`.ts` selesai |
| `npm run format` | Prettier write ke `resources/**/*.{vue,js,ts,json,css,scss,md}` |
| `vendor/bin/pint --test` | Cek PHP code style tanpa mengubah file |
| `vendor/bin/pint` | Format PHP sesuai Pint |
| `php artisan test` atau `composer test` | Jalankan PHPUnit (Feature tests) |

**Tidak ada ESLint** di project ini — hanya Prettier (format) + TypeScript strict/`vue-tsc` (type safety). Jangan sarankan menjalankan `eslint`, tidak ada konfigurasinya.

## Hal yang Harus Dihindari / Diperhatikan Khusus

1. **Dua lockfile hidup bersamaan** — `package-lock.json` dan `yarn.lock` sama-sama ada di git. README pakai `npm install`. Default ke **npm**; jangan generate ulang `yarn.lock` kecuali diminta eksplisit.
2. **Tidak ada test runner frontend** (tidak ada Vitest/Jest/Cypress terpasang). Satu-satunya test suite yang bisa dijalankan adalah PHPUnit Feature test di `tests/Feature/`. Jangan menulis file test `.spec.ts`/`.test.ts` — tidak akan bisa dijalankan.
3. **Duplikasi enum priority** — `App\Enums\TaskPriority` (backend) dan union type `TaskPriority` di `resources/js/types/task.ts` (frontend) didefinisikan terpisah manual. Kalau menambah/mengubah nilai priority, update **kedua tempat**.
4. **Otorisasi tidak seragam** — sebagian controller pakai `$this->authorize()`, Action lain pakai `Gate::authorize()` langsung di dalam `handle()`. Jangan campur pola tanpa alasan; ikuti pola yang sudah dipakai di file yang disentuh.
5. **Deep-clone reaktif di `KanbanBoard.vue`** — props `statuses` di-clone ke local state pakai `JSON.parse(JSON.stringify(...))` supaya drag-and-drop tidak memutasi props langsung. Kalau ubah struktur data task/column, pastikan pola ini tetap konsisten.
6. **Migrasi clean-code baru mencakup fitur Tasks** — jangan asumsikan pola Action/Request/service/composable sudah ada di fitur lain; cek dulu.
7. **Bug diketahui (belum diperbaiki, di luar scope dokumen ini)**: [AppLayout.vue:172-174](resources/js/Layouts/AppLayout.vue#L172-L174) — atribut `class` pada tombol overlay mobile terpecah jadi `cla` / `ss="..."` di dua baris terpisah, sehingga styling overlay tidak jalan dengan benar.
8. **File env** (`.env`, `.env.docker`) ada di root — jangan pernah commit isinya atau expose ke luar konteks yang perlu.
9. Selalu jalankan `npm run type-check` dan `vendor/bin/pint --test` sebelum menganggap perubahan selesai; untuk perubahan backend yang menyentuh alur task/project/workspace, jalankan `php artisan test` juga karena area itu punya coverage Feature test yang cukup baik.

## Prinsip Clean Code di Project Ini
- Controller tetap tipis — logic pindah ke Action, bukan menumpuk di controller.
- Satu Action = satu use case, method `handle()` yang jelas namanya.
- Tidak menambah abstraksi/helper generik sebelum benar-benar dipakai berulang (project ini sengaja menghindari over-engineering — lihat betapa flat-nya `Components/`).
- Tipe eksplisit di boundary (props, emits, return type Action) lebih diutamakan daripada inferensi implisit, terutama di file yang sudah/akan pakai `lang="ts"`.
