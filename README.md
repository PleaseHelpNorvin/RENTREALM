# RENTREALM
RENT REALM API

# WHEN RUNNING ON PHYSICAL DEBUG DEVICE
-php artisan serve --host=0.0.0.0 --port=8000
# WHEN CORS IS MISSING ON CONFIG/
-php artisan config:publish cors

# USER TO TENANT FLOW
-select property
-select room
-create account
-create userprofile/credentials
-upload reservation
-wait for admin to accept the reservation
-continue rental agreement (contract) //this will generate a billing as base of the payment
-continue payment 
-user account is listed as tenant now
