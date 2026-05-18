# Blueprint Database & RBAC EcoSort

Aplikasi EcoSort menggunakan MySQL (database: `ecosort_db`) dan mengimplementasikan arsitektur Multi-Role menggunakan library `spatie/laravel-permission`.

## 1. Roles & Permissions (Spatie)
Terdapat 3 peran utama dalam sistem:
- `super_admin`: Mengelola administrasi aplikasi (CRUD users, roles, master data kategori sampah).
- `bank_sampah`: Menerima setoran sampah dari user (manual maupun via scan AI), dan menyetujui (ACC) permintaan penarikan saldo dari user.
- `user`: Menggunakan teknologi AI untuk deteksi jenis sampah, melakukan setoran sampah (store), dan melakukan *request* penarikan saldo.

## 2. Skema Tabel (Migrations)
Buatkan migration dan Eloquent Model untuk tabel-tabel berikut, lengkap dengan relasinya:

1. **waste_categories**
   - id, name (string), description (text), icon_url (string), timestamps.
2. **waste_banks**
   - id, manager_id (foreign key ke users dengan role 'bank_sampah'), name (string), address (text), latitude (decimal 10,8), longitude (decimal 11,8), is_active (boolean), timestamps.
3. **price_catalogs**
   - id, waste_bank_id (foreign key), waste_category_id (foreign key), price_per_kg (decimal 10,2), timestamps.
4. **transactions** (Untuk setoran sampah)
   - id, user_id (foreign key ke users), waste_bank_id (foreign key), waste_category_id (foreign key), weight_kg (decimal 8,2), total_earnings (decimal 12,2), scan_method (enum: 'manual', 'ai_scan'), status (enum: 'pending', 'completed', 'rejected'), timestamps.
5. **withdrawals** (Untuk penarikan saldo)
   - id, user_id (foreign key), waste_bank_id (foreign key - bank sampah yang memproses), amount (decimal 12,2), status (enum: 'pending', 'approved', 'rejected'), timestamps.

## 3. Relasi Eloquent
- User `hasMany` Transactions, `hasMany` Withdrawals.
- WasteBank `belongsTo` User (manager_id), `hasMany` Transactions, `hasMany` Withdrawals.
- Transaction `belongsTo` User, WasteBank, WasteCategory.
- Withdrawal `belongsTo` User, WasteBank.