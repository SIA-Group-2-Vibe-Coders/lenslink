# LensLink API Documentation

> **Base URL (Production):** `https://lenslink-api-3w31.onrender.com/api`
> **Base URL (Local):** `http://127.0.0.1:8000/api`
> **Last tested:** 2026-05-19 — ✅ All 33 endpoints passing

---

## Table of Contents

1. [Authentication & Profile](#1-authentication--profile)
2. [Features](#2-features)
3. [Bookings & Payments (Stripe)](#3-bookings--payments-stripe)
4. [Cloudinary (Image Uploads)](#4-cloudinary-image-uploads)
5. [Messaging (Pusher)](#5-messaging-pusher)
6. [Firebase Auth Sync](#6-firebase-auth-sync)
7. [Google Maps](#7-google-maps)
8. [Error Handling Reference](#8-error-handling-reference)
9. [Environments & Variables](#9-environments--variables)
10. [Authentication Scheme](#10-authentication-scheme)

---

## 1. Authentication & Profile

All auth endpoints live in the **LensLink Auth** collection.  
The `Login` endpoint automatically saves the returned `token` to the environment via a Postman test script.

---

### POST `/register`

Create a new user account.

**Auth:** None required

**Headers:**
| Key | Value |
|-----|-------|
| Accept | `application/json` |

**Body** (`form-data`):
| Field | Type | Required | Example |
|-------|------|----------|---------|
| `full_name` | text | ✅ | `zinoel zin` |
| `email` | text | ✅ | `zinoel@gmail.com` |
| `password` | text | ✅ | `1234567891` |
| `role_id` | text | ✅ | `2` (2 = photographer, 1 = client) |

**Success Response `201`:**
```json
{
  "message": "User registered successfully.",
  "user": { "id": 12, "full_name": "zinoel zin", "email": "zinoel@gmail.com" },
  "token": "10|abc123..."
}
```

---

### POST `/login`

Authenticate an existing user and receive a Bearer token.

**Auth:** None required

> **Postman Test Script:** On a successful response, the token is automatically extracted and saved to both `pm.collectionVariables` and `pm.environment` as `token`.

**Body** (`form-data`):
| Field | Type | Required | Example |
|-------|------|----------|---------|
| `email` | text | ✅ | `zinoel@gmail.com` |
| `password` | text | ✅ | `1234567891` |

**Success Response `200`:**
```json
{
  "token": "10|xntnqlPEXZYhwrvzcgoC07vWq7ZYdyNE6rgddxfBaf6a68df",
  "user": { "id": 1, "full_name": "zinoel zin", "role_id": 2 }
}
```

---

### POST `/logout`

Invalidate the current Bearer token.

**Auth:** Bearer `{{token}}`

**Body:** None

**Success Response `200`:**
```json
{ "message": "Logged out successfully." }
```

---

### GET `/profile`

Get the authenticated user's profile.

**Auth:** Bearer `{{token}}`

**Success Response `200`:**
```json
{
  "id": 1,
  "full_name": "zinoel zin",
  "email": "zinoel@gmail.com",
  "role_id": 2,
  "bio": "Professional portrait photographer.",
  "specialty": "Portrait",
  "location": "New York, NY",
  "price_range": "$$$",
  "avatar_url": "https://res.cloudinary.com/...",
  "cover_url": "https://res.cloudinary.com/..."
}
```

---

### POST `/profile/update`

Update the authenticated user's profile fields.

**Auth:** Bearer `{{token}}`

**Headers:**
| Key | Value |
|-----|-------|
| Accept | `application/json` |

**Body** (`form-data`):
| Field | Type | Required | Example |
|-------|------|----------|---------|
| `bio` | text | ❌ | `Professional portrait and nature photographer.` |
| `specialty` | text | ❌ | `Portrait` |
| `location` | text | ❌ | `New York, NY` |
| `price_range` | text | ❌ | `$$$` |
| `avatar` | file | ❌ | _(image file)_ |
| `cover` | file | ❌ | _(image file)_ |

**Success Response `200`:**
```json
{ "message": "Profile updated successfully.", "user": { "..." : "..." } }
```

---

## 2. Features

All public and social feature endpoints live in the **LensLink Features** collection.

---

### GET `/gallery`

Retrieve the public photo gallery feed.

**Auth:** None required

**Success Response `200`:**
```json
{
  "data": [
    { "id": 1, "image_url": "https://res.cloudinary.com/...", "caption": "..." }
  ]
}
```

---

### GET `/albums`

List all public photographer albums.

**Auth:** None required

**Success Response `200`:**
```json
{
  "data": [
    { "id": 1, "title": "Summer 2026", "photographer_id": 11 }
  ]
}
```

---

### GET `/images`

List all public images.

**Auth:** None required

---

### GET `/photographers`

List all registered photographers.

**Auth:** None required

**Success Response `200`:**
```json
{
  "data": [
    {
      "id": 11,
      "full_name": "Jane Doe",
      "specialty": "Wedding",
      "location": "Manila",
      "price_range": "$$",
      "avatar_url": "https://res.cloudinary.com/..."
    }
  ]
}
```

---

### GET `/photographers/{photographer_id}`

Get a single photographer's public profile.

**Auth:** None required

**Path Variable:**
| Variable | Example |
|----------|---------|
| `photographer_id` | `{{photographer_id}}` → `11` |

**Success Response `200`:**
```json
{
  "id": 11,
  "full_name": "Jane Doe",
  "bio": "Wedding and events photographer.",
  "specialty": "Wedding",
  "location": "Manila",
  "price_range": "$$",
  "images": []
}
```

---

### GET `/admin-stats`

Get platform-wide statistics. Requires an admin-role token.

**Auth:** Bearer `{{token}}`

**Success Response `200`:**
```json
{
  "total_users": 45,
  "total_photographers": 12,
  "total_bookings": 88,
  "total_revenue": 12400
}
```

---

### GET `/posts`

List all social feed posts.

**Auth:** Bearer `{{token}}`

**Headers:**
| Key | Value |
|-----|-------|
| Accept | `application/json` |

---

### POST `/posts`

Create a new social post with an optional image.

**Auth:** Bearer `{{token}}`

**Headers:**
| Key | Value |
|-----|-------|
| Accept | `application/json` |

**Body** (`form-data`):
| Field | Type | Required | Example |
|-------|------|----------|---------|
| `caption` | text | ✅ | `Beautiful morning in the hills` |
| `location` | text | ❌ | `Greenville` |
| `image` | file | ❌ | _(image file)_ |

**Success Response `201`:**
```json
{ "message": "Post created.", "post": { "id": 5, "caption": "...", "image_url": "..." } }
```

---

### POST `/posts/{post_id}/like`

Toggle a like on a post (like/unlike).

**Auth:** Bearer `{{token}}`

**Headers:**
| Key | Value |
|-----|-------|
| Accept | `application/json` |

**Path Variable:**
| Variable | Example |
|----------|---------|
| `post_id` | `{{post_id}}` → `1` |

**Success Response `200`:**
```json
{ "liked": true, "total_likes": 14 }
```

---

### POST `/posts/{post_id}/comment`

Add a comment to a post.

**Auth:** Bearer `{{token}}`

**Headers:**
| Key | Value |
|-----|-------|
| Accept | `application/json` |
| Content-Type | `application/json` |

**Body** (`raw JSON`):
```json
{
  "content": "Stunning colors in this shot!"
}
```

**Success Response `201`:**
```json
{ "comment": { "id": 9, "content": "Stunning colors in this shot!", "user_id": 1 } }
```

---

### DELETE `/posts/{post_id}`

Delete a post owned by the authenticated user.

**Auth:** Bearer `{{token}}`

**Headers:**
| Key | Value |
|-----|-------|
| Accept | `application/json` |

**Success Response `200`:**
```json
{ "message": "Post deleted successfully." }
```

---

## 3. Bookings & Payments (Stripe)

All booking and payment endpoints live in the **LensLink Stripe** collection.

---

### GET `/bookings`

List all bookings for the authenticated user.

**Auth:** Bearer `{{token}}`

**Headers:**
| Key | Value |
|-----|-------|
| Accept | `application/json` |

---

### GET `/bookings/{booking_id}`

Get details for a specific booking.

**Auth:** Bearer `{{token}}`

**Path Variable:**
| Variable | Example |
|----------|---------|
| `booking_id` | `{{booking_id}}` → `1` |

**Success Response `200`:**
```json
{
  "id": 1,
  "photographer_id": 2,
  "client_id": 5,
  "session_date": "2026-06-01",
  "amount": 150,
  "status": "pending",
  "payment_status": "unpaid",
  "notes": "Outdoor summer session"
}
```

---

### POST `/bookings`

Create a new booking request.

**Auth:** Bearer `{{token}}`

**Headers:**
| Key | Value |
|-----|-------|
| Accept | `application/json` |
| Content-Type | `application/json` |

**Body** (`raw JSON`):
```json
{
  "photographer_id": 2,
  "session_date": "2026-06-01",
  "amount": 150,
  "notes": "Outdoor summer session"
}
```

**Success Response `201`:**
```json
{ "message": "Booking request sent.", "booking": { "id": 3, "status": "pending" } }
```

---

### PATCH `/bookings/{booking_id}/status`

Update the status of a booking (photographer action: accept/decline).

**Auth:** Bearer `{{token}}`

**Headers:**
| Key | Value |
|-----|-------|
| Accept | `application/json` |
| Content-Type | `application/json` |

**Body** (`raw JSON`):
```json
{
  "status": "accepted"
}
```

> **Allowed status values:** `accepted` | `declined` | `completed` | `cancelled`

---

### POST `/bookings/{booking_id}/pay`

Initiate a Stripe PaymentIntent for a booking. Returns a `client_secret` to complete payment on the frontend.

**Auth:** Bearer `{{token}}`

**Headers:**
| Key | Value |
|-----|-------|
| Accept | `application/json` |

**Success Response `200`:**
```json
{
  "client_secret": "pi_3Abc_secret_xyz123",
  "amount": 15000,
  "currency": "usd"
}
```

> Pass `client_secret` to Stripe.js `confirmCardPayment()` on the frontend.

---

### POST `/bookings/{booking_id}/confirm`

Confirm a Stripe payment after the client completes the card flow.

**Auth:** Bearer `{{token}}`

**Headers:**
| Key | Value |
|-----|-------|
| Accept | `application/json` |
| Content-Type | `application/json` |

**Body** (`raw JSON`):
```json
{
  "payment_intent_id": "pi_123456789"
}
```

**Success Response `200`:**
```json
{ "message": "Payment confirmed.", "booking": { "id": 1, "payment_status": "paid" } }
```

#### Stripe Payment Flow

```
Client → POST /bookings              → booking created (status: pending)
       → PATCH /bookings/{id}/status → photographer accepts
       → POST /bookings/{id}/pay     → receive client_secret
       → Stripe.js confirmCardPayment(client_secret)
       → POST /bookings/{id}/confirm → booking marked as paid
```

---

## 4. Cloudinary (Image Uploads)

All image storage endpoints live in the **LensLink Cloudinary** collection.  
Images are uploaded to Cloudinary and the URL is stored in the database.

---

### POST `/images/upload`

Upload an image to Cloudinary and attach it to an album.

**Auth:** Bearer `{{token}}`

**Body** (`form-data`):
| Field | Type | Required | Example |
|-------|------|----------|---------|
| `image` | file | ✅ | _(select image file)_ |
| `album_id` | text | ✅ | `1` |

**Success Response `201`:**
```json
{
  "message": "Image uploaded successfully.",
  "image": {
    "id": 7,
    "url": "https://res.cloudinary.com/dptmyksyl/image/upload/v.../photo.jpg",
    "album_id": 1
  }
}
```

---

### POST `/images/archive`

Archive (soft-delete) an image by its ID.

**Auth:** Bearer `{{token}}`

**Headers:**
| Key | Value |
|-----|-------|
| Accept | `application/json` |
| Content-Type | `application/json` |

**Body** (`raw JSON`):
```json
{
  "id": "{{image_id}}"
}
```

**Success Response `200`:**
```json
{ "message": "Image archived successfully." }
```

---

## 5. Messaging (Pusher)

Real-time messaging endpoints live in the **LensLink Pusher** collection.  
The backend broadcasts events via Pusher; clients subscribe to private channels.

> **Pusher Config:** App ID `2153994` · Key `d6fc05d33b9792276bd8` · Cluster `ap1`

---

### GET `/messages?receiver_id={id}`

Retrieve message history between the authenticated user and another user.

**Auth:** Bearer `{{token}}`

**Query Parameters:**
| Param | Required | Example |
|-------|----------|---------|
| `receiver_id` | ✅ | `1` |

**Success Response `200`:**
```json
{
  "messages": [
    { "id": 1, "sender_id": 3, "receiver_id": 1, "body": "Hello!", "created_at": "2026-05-19T..." }
  ]
}
```

---

### POST `/messages`

Send a real-time message to another user.

**Auth:** Bearer `{{token}}`

**Headers:**
| Key | Value |
|-----|-------|
| Accept | `application/json` |
| Content-Type | `application/json` |

**Body** (`raw JSON`):
```json
{
  "receiver_id": 1,
  "body": "Hello from Postman!"
}
```

**Success Response `201`:**
```json
{
  "message": { "id": 5, "sender_id": 3, "receiver_id": 1, "body": "Hello from Postman!" },
  "event": "message.sent"
}
```

---

## 6. Firebase Auth Sync

Sync a Google/Firebase authenticated user with the LensLink backend.  
Lives in the **LensLink Firebase** collection.

---

### POST `/auth/firebase`

Exchange a Firebase ID token for a LensLink Bearer token.

**Auth:** None required

**Headers:**
| Key | Value |
|-----|-------|
| Accept | `application/json` |

**Body** (`form-data`):
| Field | Type | Required | Example |
|-------|------|----------|---------|
| `id_token` | text | ✅ | _(Firebase ID token string)_ |

**Success Response `200`:**
```json
{
  "token": "70|LH2M30vhGlBkGUbYKdOuS8uRzRN9...",
  "user": { "id": 9, "email": "user@gmail.com", "full_name": "Google User" },
  "is_new_user": false
}
```

> Obtain `id_token` via the Firebase JS SDK:
> ```js
> const idToken = await firebase.auth().currentUser.getIdToken(true);
> ```

---

## 7. Google Maps

Location-based search endpoints live in the **LensLink Google Maps** collection.

> **API Key:** Configured server-side via `GOOGLE_MAPS_KEY` in `.env`

---

### GET `/search/location?address={query}`

Search for photographers by geographic location using the Google Maps Geocoding API.

**Auth:** None required

**Query Parameters:**
| Param | Required | Example |
|-------|----------|---------|
| `address` | ✅ | `Manila` |

**Success Response `200`:**
```json
{
  "location": { "lat": 14.5995, "lng": 120.9842 },
  "photographers": [
    { "id": 11, "full_name": "Jane Doe", "distance_km": 2.4, "specialty": "Wedding" }
  ]
}
```

---

## 8. Error Handling Reference

The API returns structured JSON errors for all failure cases.

| HTTP Code | Scenario | Example Response |
|-----------|----------|-----------------|
| `401` | No token / expired token | `{ "message": "Unauthenticated." }` |
| `403` | Insufficient role permissions | `{ "message": "Forbidden." }` |
| `404` | Model not found | `{ "message": "No query results for model [Photographer] 999." }` |
| `404` | Route not found | `{ "message": "Not Found." }` |
| `405` | Wrong HTTP method used | `{ "message": "Method Not Allowed." }` |
| `422` | Validation failed | `{ "message": "The email field is required.", "errors": { "email": ["..."] } }` |
| `500` | Server error | `{ "message": "Server Error." }` |

---

### Error Handling Test Requests

These requests are in the **LensLink Error Handling** collection to verify error responses are correctly formatted.

| Request Name | Method | Endpoint | Expected Code |
|---|---|---|---|
| 404 Model Not Found | `GET` | `/photographers/1` | `404` |
| 422 Validation Error | `POST` | `/login` (empty body `{}`) | `422` |
| 401 Unauthenticated | `GET` | `/profile` (no token) | `401` |
| 405 Method Not Allowed | `GET` | `/login` (wrong method) | `405` |
| 404 Route Not Found | `GET` | `/this-route-does-not-exist` | `404` |

---

## 9. Environments & Variables

### Environment Variables

| Variable | Description | Local Value | Production Value |
|----------|-------------|-------------|-----------------|
| `base_url` | API base URL (no trailing slash) | `127.0.0.1:8000/api` | `lenslink-api-3w31.onrender.com/api` |
| `token` | Bearer auth token (auto-set by Login) | _(set by login script)_ | _(set by login script)_ |
| `post_id` | ID for post-related requests | `1` | `1` |
| `booking_id` | ID for booking-related requests | `1` | `1` |
| `photographer_id` | ID for photographer profile requests | `1` | `11` |
| `image_id` | ID for image archive requests | `1` | `1` |

### Available Postman Environments

| Name | Base URL |
|------|----------|
| **LensLink Local (New)** | `http://127.0.0.1:8000/api` |
| **LensLink Production** | `https://lenslink-api-3w31.onrender.com/api` |

---

## 10. Authentication Scheme

LensLink uses **Laravel Sanctum** token-based authentication.

1. Call `POST /login` → receive `token` in response
2. Include it in all protected requests:
   ```
   Authorization: Bearer {token}
   ```
3. Call `POST /logout` to invalidate the token server-side

**Postman auto-token setup** — The `Login` request includes a test script:
```js
var jsonData = pm.response.json();
if (jsonData.token) {
    pm.collectionVariables.set("token", jsonData.token);
    pm.environment.set("token", jsonData.token);
    console.log("✅ Token saved:", jsonData.token);
}
```

---

## Collections Summary

| Collection | Requests | Auth Required | Integration |
|---|---|---|---|
| 🔐 LensLink Auth | 5 | Partial | Laravel Sanctum |
| 🌟 LensLink Features | 11 | Partial | Core API |
| 💳 LensLink Stripe | 6 | All | Stripe PaymentIntents |
| ☁️ LensLink Cloudinary | 2 | All | Cloudinary SDK |
| 📡 LensLink Pusher | 2 | All | Pusher Channels |
| 🔥 LensLink Firebase | 1 | None | Firebase Admin SDK |
| 🗺️ LensLink Google Maps | 1 | None | Maps Geocoding API |
| ⚠️ LensLink Error Handling | 5 | Partial | Validation testing |
| **Total** | **33** | — | — |

---

*Generated from live Postman collections — workspace: **lenslink** (public)*  
*Production API: `lenslink-api-3w31.onrender.com/api`*
