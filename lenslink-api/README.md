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

## API Documentation

- `POST /api/register` - User registration
- `POST /api/login` - User authentication
- `GET /api/gallery` - List public galleries
- `GET /api/photographers` - Discover photographers
- `POST /api/upload` - Image upload (Requires Authentication)

---
© 2026 LensLink Project
