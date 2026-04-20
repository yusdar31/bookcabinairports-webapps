# ERD — Bookcabin Free Tier

## Tabel Utama

### `rooms` — Data Kamar
```sql
CREATE TABLE rooms (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(10) NOT NULL UNIQUE,
    room_type   ENUM('standard','deluxe','suite','pod') NOT NULL,
    floor       TINYINT NOT NULL,
    price_per_night INT NOT NULL COMMENT 'dalam Rupiah',
    status      ENUM('available','occupied','maintenance') DEFAULT 'available',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### `bookings` — Data Booking
```sql
CREATE TABLE bookings (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_code    VARCHAR(20) NOT NULL UNIQUE,
    room_id         BIGINT UNSIGNED NOT NULL,
    source          ENUM('direct','agoda','traveloka','booking_com') DEFAULT 'direct',
    ota_booking_id  VARCHAR(100) NULL UNIQUE COMMENT 'ID dari OTA, null jika walk-in/online langsung',
    guest_name      VARCHAR(100) NOT NULL,
    guest_email     VARCHAR(150) NOT NULL,
    guest_id_number VARCHAR(30) NOT NULL COMMENT 'KTP atau paspor',
    check_in        DATETIME NOT NULL,
    check_out       DATETIME NOT NULL,
    total_price     INT NOT NULL,
    status          ENUM('pending','confirmed','checked_in','checked_out','cancelled') DEFAULT 'pending',
    payment_status  ENUM('unpaid','paid','refunded') DEFAULT 'unpaid',
    pin_code        VARCHAR(6) NULL,
    pin_expires_at  DATETIME NULL,
    qr_token        VARCHAR(100) NULL UNIQUE,
    notes           TEXT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id)
);
```

### `users` — Admin & Staff
```sql
CREATE TABLE users (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL COMMENT 'bcrypt hashed',
    role       ENUM('super_admin','manajer','kasir','resepsionis') NOT NULL,
    is_active  BOOLEAN DEFAULT TRUE,
    otp_secret VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### `outlets` — Outlet F&B
```sql
CREATE TABLE outlets (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    location   VARCHAR(200) NULL COMMENT 'Terminal, lantai, gate',
    is_active  BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### `menu_items` — Menu POS
```sql
CREATE TABLE menu_items (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    outlet_id   BIGINT UNSIGNED NOT NULL,
    name        VARCHAR(150) NOT NULL,
    description TEXT NULL,
    price       INT NOT NULL,
    category    VARCHAR(50) NULL,
    stock       INT DEFAULT 0,
    photo_url   VARCHAR(500) NULL COMMENT 'URL dari S3',
    is_available BOOLEAN DEFAULT TRUE,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (outlet_id) REFERENCES outlets(id)
);
```

### `transactions` — Transaksi POS
```sql
CREATE TABLE transactions (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_ref   VARCHAR(50) NOT NULL UNIQUE COMMENT 'UUID dari client untuk duplicate detection',
    outlet_id         BIGINT UNSIGNED NOT NULL,
    kasir_id          BIGINT UNSIGNED NOT NULL,
    total_amount      INT NOT NULL,
    discount_amount   INT DEFAULT 0,
    payment_method    ENUM('cash','qris','midtrans') NOT NULL,
    payment_status    ENUM('pending','paid','failed') DEFAULT 'pending',
    midtrans_order_id VARCHAR(100) NULL,
    synced_from_offline BOOLEAN DEFAULT FALSE,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (outlet_id) REFERENCES outlets(id),
    FOREIGN KEY (kasir_id) REFERENCES users(id)
);
```

### `transaction_items` — Item per Transaksi
```sql
CREATE TABLE transaction_items (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id BIGINT UNSIGNED NOT NULL,
    menu_item_id   BIGINT UNSIGNED NOT NULL,
    quantity       INT NOT NULL,
    unit_price     INT NOT NULL,
    notes          VARCHAR(255) NULL,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id),
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
);
```

## Relasi Antar Tabel

```
users ──────────────────────── transactions (kasir_id)
rooms ──────────────────────── bookings (room_id)
outlets ──── menu_items ──────── transaction_items ──── transactions
```
