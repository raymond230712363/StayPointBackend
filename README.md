# StayPoint Backend API

REST API Laravel untuk manajemen hotel, kamar, booking, addon, fasilitas, autentikasi, dan review.

## Instalasi

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Pastikan database MySQL `staypoint` sudah dibuat sebelum menjalankan migration.

Admin seed:

- Email: `admin@staypoint.test`
- Password: `password`

Customer seed:

- Email: `customer@staypoint.test`
- Password: `password`

## Format Response

Sukses:

```json
{
  "success": true,
  "message": "Success",
  "data": {}
}
```

Error validasi:

```json
{
  "success": false,
  "message": "Validation Error",
  "errors": {}
}
```

Gunakan header berikut untuk endpoint yang membutuhkan login:

```http
Authorization: Bearer {token}
Accept: application/json
```

## Endpoint API

### Auth

| Method | Endpoint | Keterangan |
| --- | --- | --- |
| POST | `/api/register` | Register user |
| POST | `/api/login` | Login user |
| POST | `/api/google-login` | Placeholder login Google |
| POST | `/api/forgot-password` | Placeholder forgot password |
| POST | `/api/logout` | Logout token aktif |
| GET | `/api/me` | Current user |
| PUT | `/api/profile` | Update profile |
| POST | `/api/profile/photo` | Upload profile photo |
| PUT | `/api/change-password` | Change password |

### Hotels

| Method | Endpoint | Keterangan |
| --- | --- | --- |
| GET | `/api/hotels` | List hotel, pagination, search |
| GET | `/api/hotels/{hotel}` | Detail hotel |
| POST | `/api/hotels` | Create hotel |
| PUT | `/api/hotels/{hotel}` | Update hotel |
| DELETE | `/api/hotels/{hotel}` | Delete hotel |

Query list: `search`, `per_page`.

### Rooms

| Method | Endpoint | Keterangan |
| --- | --- | --- |
| GET | `/api/rooms` | List room, pagination, filter, search |
| GET | `/api/rooms/{room}` | Detail room |
| POST | `/api/rooms` | Create room |
| PUT | `/api/rooms/{room}` | Update room |
| DELETE | `/api/rooms/{room}` | Delete room |
| POST | `/api/rooms/{room}/images` | Upload multiple room images |
| DELETE | `/api/room-images/{roomImage}` | Delete room image |

Query list: `search`, `hotel_id`, `min_capacity`, `min_price`, `max_price`, `facility_id`, `per_page`.

### Facilities

| Method | Endpoint | Keterangan |
| --- | --- | --- |
| GET | `/api/facilities` | List facility |
| GET | `/api/facilities/{facility}` | Detail facility |
| POST | `/api/facilities` | Create facility |
| PUT | `/api/facilities/{facility}` | Update facility |
| DELETE | `/api/facilities/{facility}` | Delete facility |

### Addons

| Method | Endpoint | Keterangan |
| --- | --- | --- |
| GET | `/api/addons` | List addon |
| GET | `/api/addons/{addon}` | Detail addon |
| POST | `/api/addons` | Create addon |
| PUT | `/api/addons/{addon}` | Update addon |
| DELETE | `/api/addons/{addon}` | Delete addon |

### Bookings

| Method | Endpoint | Keterangan |
| --- | --- | --- |
| POST | `/api/bookings` | Create booking |
| GET | `/api/bookings/history` | History booking user |
| GET | `/api/bookings/admin` | Admin booking list |
| GET | `/api/bookings/{booking}` | Detail booking |
| PUT | `/api/bookings/{booking}/status` | Update status dan payment status |
| POST | `/api/bookings/{booking}/cancel` | Cancel booking |

Saat booking dibuat, API menghitung `total_nights`, `total_price`, membuat `booking_code`, QR SVG, dan receipt PDF di storage public.

### Reviews

| Method | Endpoint | Keterangan |
| --- | --- | --- |
| POST | `/api/reviews` | Create review |
| PUT | `/api/reviews/{review}` | Update review |
| DELETE | `/api/reviews/{review}` | Delete review |
| GET | `/api/rooms/{room}/reviews` | List review dan average rating room |

User hanya bisa membuat review untuk booking miliknya yang status-nya `completed`.

## Contoh Request

### Register

```json
{
  "name": "Customer",
  "email": "customer@example.com",
  "password": "password",
  "phone": "08123456789"
}
```

### Login Response

```json
{
  "success": true,
  "message": "Login berhasil",
  "data": {
    "access_token": "token",
    "token_type": "Bearer",
    "user": {
      "id": 1,
      "name": "Customer",
      "email": "customer@example.com",
      "role": "customer"
    }
  }
}
```

### Create Booking

```json
{
  "room_id": 1,
  "check_in": "2026-07-01",
  "check_out": "2026-07-03",
  "addons": [
    {
      "id": 1,
      "quantity": 2
    }
  ]
}
```

### Update Booking Status

```json
{
  "status": "completed",
  "payment_status": "paid"
}
```

### Create Review

Gunakan `multipart/form-data` jika mengirim `photo`.

```json
{
  "booking_id": 1,
  "rating": 5,
  "comment": "Kamar bersih dan nyaman."
}
```

## Struktur Utama

- Model: `app/Models`
- Controller API: `app/Http/Controllers/Api`
- Form Request: `app/Http/Requests/Api`
- API Resource: `app/Http/Resources`
- Service dokumen booking: `app/Services/BookingDocumentService.php`
- Policy: `app/Policies`
- Route API: `routes/api.php`
