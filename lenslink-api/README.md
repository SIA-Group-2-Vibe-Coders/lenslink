# LensLink API

LensLink is a platform for photographers to showcase their work, manage portfolios, and connect with clients. This repository contains the backend API built with **Laravel 12** and secured with **Laravel Sanctum**.

---

## Features

- **Authentication** — Register/login with Sanctum tokens. Firebase/Google social login sync.
- **Role-Based Access** — Admin (1), Photographer (2), Client (3).
- **Gallery Management** — Galleries, albums, and image uploads via Cloudinary.
- **Social Feed** — Posts, likes, and comments.
- **Real-time Messaging** — Direct chat powered by Pusher.
- **Bookings & Payments** — Full booking lifecycle with Stripe PaymentIntent.
- **Location Search** — Find photographers by city via Google Maps Geocoding.
- **Admin Dashboard** — Platform-wide statistics.

---

## Architecture

Service-layer architecture for clean separation of concerns:

```
Controllers  →  Services / Repositories  →  Models
     ↓
Form Requests (validation) + Policies (authorization) + Middleware (auth/roles)
```

| Layer | Path |
|---|---|
| Controllers | `app/Http/Controllers/` |
| Services | `app/Services/` |
| Repositories | `app/Repositories/` (Booking, Post, Message) |
| Models | `app/Models/` |
| Middleware | `app/Http/Middleware/` |
| Form Requests | `app/Http/Requests/` |
| Policies | `app/Policies/` |
| Events | `app/Events/` |

---

## Tech Stack

| Component | Technology |
|---|---|
| Framework | Laravel 12 |
| Auth | Laravel Sanctum |
| Database | SQLite (local) / PostgreSQL (production) |
| Image Storage | Cloudinary |
| Real-time | Pusher |
| Payments | Stripe |
| Location | Google Maps Geocoding API |
| Social Auth | Firebase Admin SDK |

---

## Roles

| role_id | Name | Access |
|---|---|---|
| 1 | Admin | Full access + `/admin-stats` |
| 2 | Photographer | Gallery, albums, images, bookings |
| 3 | Client | Browse, book, message, social feed |

---

## Setup

```bash
# 1. Install dependencies
composer install

# 2. Configure environment
cp .env.example .env
php artisan key:generate

# 3. Run migrations and seed default data
php artisan migrate --seed

# 4. Start the server
php artisan serve
```

Or use the composer shortcut:
```bash
composer run setup
```

---

## Environment Variables

Copy `.env.example` and fill in the required values:

```env
# Database
DB_CONNECTION=sqlite

# Cloudinary
CLOUDINARY_URL=cloudinary://key:secret@cloud_name

# Pusher
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=

# Stripe
STRIPE_SECRET=

# Google Maps
GOOGLE_MAPS_KEY=

# Firebase
FIREBASE_CREDENTIALS=storage/app/firebase-auth.json

# CORS
ALLOWED_ORIGINS=http://localhost:5500
```

---

## API Documentation

Full interactive documentation is available at:
👉 [`api-docs.html`](../api-docs.html) — open in a browser

**Base URL:** `http://127.0.0.1:8000/api`

Quick reference:

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| POST | `/register` | Public | Register a new user |
| POST | `/login` | Public | Login and get token |
| POST | `/logout` | Bearer | Invalidate token |
| GET | `/profile` | Bearer | Get own profile |
| GET | `/photographers` | Public | List photographers |
| GET | `/search/location?address=` | Public | Search by location |
| GET/POST | `/posts` | Bearer | Social feed |
| GET/POST | `/messages` | Bearer | Messaging |
| GET/POST | `/bookings` | Bearer | Bookings |
| POST | `/bookings/intent` | Bearer | Stripe payment intent |
| GET | `/admin-stats` | Admin | Platform stats |

---

## Deployment

### Backend — Render / Railway
1. Connect this repository
2. Build command: `composer install --no-dev && php artisan migrate --force && php artisan db:seed --force`
3. Set all `.env` variables in your hosting dashboard
4. Set `ALLOWED_ORIGINS` to your deployed frontend URL

### Frontend — Vercel / Netlify
1. Connect the `/frontend` directory
2. Update `assets/js/main.js` with your production API URL
3. Restrict Firebase API key by domain in the Firebase Console

---

© 2026 LensLink — SIA Group 2 Vibe Coders
