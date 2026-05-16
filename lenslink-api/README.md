# LensLink API

LensLink is a specialized platform for photographers to showcase their work, manage portfolios, and connect with clients. This repository contains the backend API built with Laravel 11.

## Features

- **User Authentication**: Secure login and registration using Laravel Sanctum.
- **Role-Based Access**: Specialized roles for Admins, Photographers, and Clients.
- **Gallery Management**: Organize work into galleries and albums.
- **Smart Image Processing**: Integrated with Cloudinary for automatic watermarking and thumbnail generation.
- **Real-time Messaging**: Direct chat between clients and photographers powered by Pusher.
- **Admin Dashboard**: System-wide statistics and management.

## Architecture

The project follows a **Service-Layer Architecture** to keep the codebase clean, testable, and maintainable:
- **Controllers**: Handle HTTP requests and responses.
- **Services**: Contain the core business logic.
- **Models**: Define data structure and relationships.
- **Events**: Handle asynchronous actions like real-time notifications.

## Tech Stack

- **Framework**: [Laravel 11](https://laravel.com)
- **Database**: PostgreSQL (Supabase) / SQLite (Local)
- **Authentication**: Laravel Sanctum
- **Image Storage**: Cloudinary
- **Real-time**: Pusher / Laravel Reverb

## Setup

1. **Clone the repository**:
   ```bash
   git clone <repo-url>
   cd lenslink-api
   ```

2. **Install dependencies**:
   ```bash
   composer install
   ```

3. **Configure Environment**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Run Migrations & Seeders**:
   ```bash
   php artisan migrate --seed
   ```

5. **Start the server**:
   ```bash
   php artisan serve
   ```

## Core Integrations (Mandatory APIs)

LensLink is integrated with 5 essential third-party services to provide a professional photography experience:

1.  **Cloudinary (Storage & CDN)**: Handles image uploads, automatic watermarking, and responsive image transformations.
2.  **Pusher (Real-time Messaging)**: Powering the direct chat system with instant message delivery and status updates.
3.  **Stripe (Payments)**: Secure payment intent creation for booking deposits and professional services.
4.  **Google Maps (Geocoding)**: Enables "Nearby Photographer" search by converting user input addresses into geographic coordinates.
5.  **Firebase (Auth Sync)**: Allows seamless Google/Social sign-in synchronization with the local user database.

## Deployment Guide

### Backend (Laravel) - Deploying to Render/Railway
1.  **Repository**: Connect this repository to your hosting provider.
2.  **Build Command**: `composer install --no-dev && php artisan migrate --force`
3.  **Environment Variables**: Ensure all keys in `.env.example` are set in your hosting provider's dashboard.
4.  **Database**: Recommended to use a managed PostgreSQL instance (e.g., Supabase).

### Frontend (HTML/JS) - Deploying to Vercel/Netlify
1.  **Repository**: Connect the `/frontend` directory.
2.  **API URL**: Update `assets/js/main.js` with your production backend URL.
3.  **Firebase**: Configure your production Firebase config in `login.html`.

## API Endpoints (Quick Reference)

- `GET /api/photographers` - List all photographers.
- `GET /api/search/location?address={city}` - Search photographers by city (Google Maps).
- `POST /api/bookings/intent` - Create a Stripe payment intent.
- `POST /api/auth/firebase` - Sync Firebase social login with local account.
- `GET /api/messages` - Retrieve chat history.

---
© 2026 LensLink Project
