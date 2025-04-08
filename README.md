
# RENTREALM API

a backbone API for RentRealm System

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

   Example output:

   Configuration File (php.ini) Path:
    Loaded Configuration File:         C:\php-8.3.9\php.ini
    Scan for additional .ini files in: (none)
    Additional .ini files parsed:      (none)

2. Locate the "Loaded Configuration File" path in the output.
Example: C:\php-8.3.9\php.ini

3. Open the php.ini file in a text editor.

4. Find the following line:

    ```sh
    ;extension=gd

Remove the semicolon (;) at the beginning to enable it:

    extension=gd

5. Save the file and restart your server:

    ```sh
    php artisan serve --host=0.0.0.0 --port=8000

6. Verify that GD is enabled:

    ```sh
    php -r "echo extension_loaded('gd') ? 'GD is enabled' : 'GD is NOT enabled';"
##




### NEXT TASK

1. billing logic 
2. logic for ending a tenant contract

# RENTREALM API

a backbone API for RentRealm System

### Running on a Physical Debug Device

To start the Laravel development server, run:

```sh
php artisan serve --host=0.0.0.0 --port=8000
```
### for starting queue
```sh
php artisan queue:work
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

   Example output:

   Configuration File (php.ini) Path:
    Loaded Configuration File:         C:\php-8.3.9\php.ini
    Scan for additional .ini files in: (none)
    Additional .ini files parsed:      (none)

2. Locate the "Loaded Configuration File" path in the output.
Example: C:\php-8.3.9\php.ini

3. Open the php.ini file in a text editor.

4. Find the following line:

    ```sh
    ;extension=gd

Remove the semicolon (;) at the beginning to enable it:

    extension=gd

5. Save the file and restart your server:

    ```sh
    php artisan serve --host=0.0.0.0 --port=8000

6. Verify that GD is enabled:

    ```sh
    php -r "echo extension_loaded('gd') ? 'GD is enabled' : 'GD is NOT enabled';"
##






rental_agreements FOR Monthly Billing Logic
----------------------------
data module or provider needed to formulate billing countdown logic:


reservation
userProfile
room

 
for making a billing and payment :
billings
payment
notifications

### NEXT TASK

1. billing logic 
2. logic for ending a tenant contract
