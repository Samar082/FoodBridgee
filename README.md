# FoodBridge PHP + MySQL

An editable FoodBridge starter project. It has a separate HTML interface, CSS stylesheet, JavaScript behaviour, PHP API, MySQL schema, and image upload folder.

## Files

```text
FoodBridge-PHP-MySQL/
|-- index.html                # Website interface
|-- style.css                 # All styling and responsive layout
|-- script.js                 # Modal, form submission, NGO loading
|-- api/
|   |-- config.php            # Database connection settings
|   |-- db.php                # PDO connection and JSON helpers
|   |-- ngos.php              # GET verified NGOs
|   |-- donations.php         # GET donations / POST a new donation
|   `-- health.php            # Database connection check
|-- database/foodbridge.sql   # Database tables and sample NGO data
`-- uploads/                  # Uploaded food images are saved here
```

## Run it locally with XAMPP

1. Install and open XAMPP. Start **Apache** and **MySQL**.
2. Copy the complete `FoodBridge-PHP-MySQL` folder into `C:\xampp\htdocs\`.
3. Open `http://localhost/phpmyadmin` and import `database/foodbridge.sql`.
4. Check `api/config.php`. The default settings work with the usual XAMPP setup: database `foodbridge`, user `root`, blank password.
5. Open `http://localhost/FoodBridge-PHP-MySQL/` in your browser.
6. Visit `http://localhost/FoodBridge-PHP-MySQL/api/health.php`. It should return a successful JSON response once PHP and MySQL are connected.

## Editing guide

- Change text and sections in `index.html`.
- Change colors, layouts, and mobile design in `style.css`.
- Change modal and API behaviour in `script.js`.
- Change validation, matching logic, or upload handling in `api/donations.php`.
- Change database tables and example NGOs in `database/foodbridge.sql`.

## Important notes

- The donation form sends data to `api/donations.php`; it only works through an Apache/PHP server, not by double-clicking `index.html`.
- JPG, PNG, and WEBP food photos up to 5 MB are supported. Keep the `uploads` folder writable by Apache.
- The current NGO match picks the first verified active NGO. Replace that query in `api/donations.php` with distance/GPS matching when you add latitude and longitude fields.
- This is a development starter. Before a public launch, use environment variables for credentials, add authentication/CSRF protection, rate limiting, and a real NGO verification workflow.
