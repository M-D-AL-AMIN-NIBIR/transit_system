# Transit System - Backend Implementation

A complete PHP-based backend system for managing public transportation with admin and passenger roles, pass management, live tracking, and notifications.

## 📋 Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Database Setup](#database-setup)
- [API Documentation](#api-documentation)
- [Security Standards](#security-standards)
- [File Structure](#file-structure)

## ✨ Features

### Authentication & Authorization
- User registration and login with role-based access (admin/passenger)
- Secure password hashing using `password_hash()`
- Session management with regeneration for security
- Protected routes with middleware

### Admin Module
- Fleet management (CRUD for buses and trains)
- Route management with fare rules
- User management
- View all passes and payments

### Passenger Module
- Purchase passes (daily, weekly, monthly, trip-based)
- View pass history and status
- Trip tracking
- View live vehicle locations
- Notification system
- Payment simulation

### Live Tracking System
- Real-time vehicle location updates
- GPS coordinate tracking
- Frontend polling support

### Security Features
- PDO prepared statements (SQL injection prevention)
- XSS prevention with `htmlspecialchars()`
- CSRF token support
- Password security with bcrypt hashing
- Input validation and sanitization

## 🛠 Requirements

- PHP 8.0 or higher
- MySQL 8.0 or higher
- Web server (Apache/Nginx)
- PDO MySQL extension enabled

## 📦 Installation

1. Clone or extract the project to your web server directory:
   ```
   f:/surf/transit-system/
   ```

2. Configure database settings in `config/database.php`:
   ```php
   return [
       'host' => 'localhost',
       'dbname' => 'transit_system',
       'username' => 'root',
       'password' => ''
   ];
   ```

3. Import the database schema:
   ```sql
   mysql -u root -p transit_system < sql/database_schema.sql
   ```

4. Ensure proper permissions for the `uploads/` directory (if handling file uploads).

## 🗄 Database Setup

The database schema includes:

- **Users & Authentication**: `users`, `passenger_profiles`
- **Routes & Fares**: `routes`, `fare_rules`
- **Vehicles & Scheduling**: `vehicles`, `bus_details`, `train_details`, `route_assignments`
- **Passes & Payments**: `passes`, `payments`, `pass_purchases`
- **Trip Tracking**: `trips`
- **Live Tracking**: `live_tracking`
- **Notifications**: `notifications`

### Default Admin Credentials
- **Email**: admin@transit.com
- **Password**: admin123

*(Change immediately in production)*

## 🔌 API Documentation

### Authentication Endpoints

| Method | Endpoint | Description | Access |
|--------|----------|-------------|--------|
| POST | `/auth/login.php` | User login | Public |
| POST | `/auth/register.php` | User registration | Public |
| GET | `/auth/logout.php` | Logout | Authenticated |

### Admin API Endpoints

| Method | Endpoint | Description | Access |
|--------|----------|-------------|--------|
| GET | `/api/admin/fleet.php` | Get all vehicles | Admin |
| POST | `/api/admin/fleet.php` | Add new vehicle | Admin |
| PUT | `/api/admin/fleet.php` | Update vehicle | Admin |
| DELETE | `/api/admin/fleet.php` | Delete vehicle | Admin |
| GET | `/api/admin/routes.php` | Get all routes | Admin |
| POST | `/api/admin/routes.php` | Create route | Admin |
| PUT | `/api/admin/routes.php` | Update route | Admin |
| DELETE | `/api/admin/routes.php` | Delete route | Admin |
| GET | `/api/admin/users.php` | Get users list | Admin |
| PUT | `/api/admin/users.php` | Update user | Admin |
| DELETE | `/api/admin/users.php` | Delete user | Admin |

### Passenger API Endpoints

| Method | Endpoint | Description | Access |
|--------|----------|-------------|--------|
| GET | `/api/passenger/passes.php` | Get user's passes | Passenger |
| PUT | `/api/passenger/passes.php` | Cancel pass | Passenger |
| POST | `/api/passenger/payments.php` | Purchase pass | Passenger |
| GET | `/api/passenger/trips.php` | Get trip history | Passenger |
| POST | `/api/passenger/trips.php` | Start trip | Passenger |
| PUT | `/api/passenger/trips.php` | End trip | Passenger |
| GET | `/api/passenger/routes.php` | Get routes & vehicles | Authenticated |
| GET | `/api/passenger/notifications.php` | Get notifications | Passenger |
| PUT | `/api/passenger/notifications.php` | Mark as read | Passenger |
| DELETE | `/api/passenger/notifications.php` | Delete notification | Passenger |

### Tracking API Endpoints

| Method | Endpoint | Description | Access |
|--------|----------|-------------|--------|
| POST | `/api/tracking/update-location.php` | Update vehicle location | Driver/Device |

## 🔒 Security Standards

### Password Security
```php
// Hash passwords
$hash = password_hash($password, PASSWORD_DEFAULT);

// Verify passwords
$valid = password_verify($password, $hash);
```

### SQL Injection Prevention
```php
// Always use prepared statements
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
```

### XSS Prevention
```php
// Escape output
$output = htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');
```

### Session Protection
```php
// Regenerate session ID on authentication
session_regenerate_id(true);
```

### CSRF Tokens
```php
// Generate token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Verify token in forms
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    // Invalid token
}
```

## 📁 File Structure

```
transit-system/
├── admin/                  # Admin dashboard pages
│   ├── dashboard.php
│   └── fleet.php
├── api/                    # API endpoints
│   ├── admin/
│   │   ├── fleet.php       # Fleet CRUD
│   │   ├── routes.php      # Route CRUD
│   │   └── users.php       # User management
│   ├── passenger/
│   │   ├── passes.php      # Pass management
│   │   ├── payments.php    # Payment processing
│   │   ├── routes.php      # Route/vehicle info
│   │   ├── trips.php       # Trip management
│   │   └── notifications.php
│   └── tracking/
│       └── update-location.php
├── assets/                 # CSS, JS, images
│   ├── css/
│   ├── js/
│   └── images/
├── auth/                   # Authentication handlers
│   ├── login.php
│   ├── register.php
│   └── logout.php
├── config/                 # Configuration files
│   └── database.php
├── includes/               # Shared PHP files
│   ├── auth.php           # Authentication functions
│   ├── db.php             # Database connection
│   ├── notifications.php  # Notification system
│   ├── header.php
│   ├── footer.php
│   ├── topbar-admin.php
│   ├── sidebar-admin.php
│   └── sidebar-passenger.php
├── passenger/             # Passenger dashboard pages
│   └── dashboard.php
├── sql/                   # Database files
│   └── database_schema.sql
├── uploads/               # File uploads directory
├── index.php             # Login page
├── unauthorized.php      # 403 error page
└── README.md             # This file
```

## 🔧 AI IDE Tags

Use these tags for rapid code generation:

```
// AUTH_REQUIRED
requireAuth();

// ADMIN_ONLY
requireAuth('admin');

// PASSENGER_ONLY
requireAuth('passenger');

// SECURE_QUERY
$stmt = $pdo->prepare("...");
$stmt->execute([...]);

// API_RESPONSE
header('Content-Type: application/json');
echo json_encode($response);
exit;
```

## 📱 Frontend Integration Example

```javascript
// Fetch user passes
fetch('/api/passenger/passes.php', {
    method: 'GET'
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        renderPasses(data.data);
    }
});

// Live vehicle tracking
setInterval(() => {
    fetch(`/api/passenger/routes.php?vehicle_id=${vehicleId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                updateMarker(data.data.latitude, data.data.longitude);
            }
        });
}, 10000); // Poll every 10 seconds
```

## 🗺 External API Integrations

- **Leaflet.js** - Interactive maps, route visualization, vehicle markers
- **OpenStreetMap Nominatim** - Geocoding for stops
- **OpenRouteService** - Route optimization

## 📞 Support

For issues or feature requests, please refer to the project documentation or contact the development team.

---

**Note**: This is a development environment. Change all default passwords and security settings before deploying to production.
