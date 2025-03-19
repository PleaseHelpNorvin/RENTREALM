# RENTREALM

## RENTREALM API

### Running on a Physical Debug Device

To start the Laravel development server, run:

```sh
php artisan serve --host=0.0.0.0 --port=8000
```

### Handling CORS Issues

If CORS is missing in the configuration, publish it using:

```sh
php artisan config:publish cors
```

## User-to-Tenant Flow

1. **Select Property** – Browse available properties.
2. **Select Room** – Choose a room from the selected property.
3. **Create an Account** – Register a new user profile.
4. **Create User Profile & Credentials** – Complete user details.
5. **Upload Reservation** – Submit a reservation request.
6. **Admin Approval** – Wait for an admin to approve the reservation.
7. **Continue Rental Agreement (Contract)** – This will generate a billing as the base for payment.
8. **Continue Payment** – Make the required payment.
9. **User Becomes a Tenant** – The account is now officially listed as a tenant.

## ⚠️ Important: Enable GD Extension in `php.ini`

After cloning this project, you **must enable the GD extension** in PHP to avoid errors when generating PDFs or handling images.

### 🛠 Steps to Enable GD Extension:
1. Open a terminal and run the following command to locate your active `php.ini` file:
   ```sh
   php --ini