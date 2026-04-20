# Entity Relationship Diagram (ERD) - Bookcabin

Diagram ini menunjukkan relasi antar entitas di database Bookcabin. Diagram menggunakan skema relasional berdasarkan migrasi Laravel yang dibuat di Fase 3.

```mermaid
erDiagram
    users {
        bigint id PK
        string name
        string email
        string password
        enum role "super_admin, manajer, kasir, resepsionis"
        boolean is_active
    }

    outlets {
        bigint id PK
        string name
        string location
        enum type "cafe, convenience_store, vending_machine"
        boolean is_active
    }

    rooms {
        bigint id PK
        string room_number
        enum type "standard, vip"
        decimal price_per_night
        enum status "available, occupied, maintenance"
    }

    menus {
        bigint id PK
        bigint outlet_id FK
        string name
        decimal price
        enum category "makanan, minuman, snack"
        boolean is_available
    }

    bookings {
        bigint id PK
        string booking_code "UNIQUE"
        bigint room_id FK
        string guest_name
        timestamp check_in
        timestamp check_out
        decimal total_price
        enum status "pending, confirmed, checked_in, checked_out, cancelled"
        string payment_status "unpaid, paid, refunded"
        string ota_booking_id "UNIQUE"
    }

    transactions {
        bigint id PK
        string transaction_ref "UNIQUE"
        bigint outlet_id FK
        bigint kasir_id FK
        decimal total_amount
        string payment_method "cash, qris, midtrans"
        boolean synced_from_offline
    }

    transaction_items {
        bigint id PK
        bigint transaction_id FK
        bigint menu_item_id FK
        integer quantity
        decimal unit_price
    }

    %% Relasi
    outlets ||--o{ menus : "memiliki"
    outlets ||--o{ transactions : "mencatat"
    
    users ||--o{ transactions : "memproses (kasir_id)"

    rooms ||--o{ bookings : "dipesan dalam"

    transactions ||--|{ transaction_items : "terdiri dari"
    menus ||--o{ transaction_items : "sebagai"
```
