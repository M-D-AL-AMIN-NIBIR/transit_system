# MetroLink Transit System - Frontend Analysis for Backend Development

## System Overview

**Application Name:** MetroLink Transit System  
**Tech Stack:** PHP (backend), MySQL (database), Vanilla JS + Leaflet (frontend), CSS3  
**User Roles:** Admin, Passenger  

---

## Project Structure

```
transit-system/
├── index.php                    # Landing/Login page (empty)
├── admin/
│   └── fleet.php               # Admin fleet & route management
├── passenger/
│   └── dashboard.php           # Passenger dashboard
├── includes/
│   ├── header.php              # (empty)
│   ├── footer.php              # (empty)
│   ├── sidebar-admin.php       # Admin navigation sidebar
│   ├── sidebar-passenger.php   # Passenger navigation sidebar
│   ├── topbar-admin.php        # Admin top bar with notifications
│   ├── auth.php                # (empty - needs implementation)
│   └── db.php                  # (empty - needs implementation)
├── config/
│   └── database.php            # (empty - needs implementation)
├── api/
│   └── .gitkeep                # API endpoints folder
├── assets/
│   ├── css/
│   │   ├── style.css           # Shared base styles
│   │   ├── admin.css           # Admin-specific styles
│   │   └── passenger.css       # Passenger-specific styles
│   └── js/
│       ├── main.js             # Shared UI interactions
│       └── map.js              # Leaflet map initialization
└── uploads/                    # File uploads folder
```

---

## 1. ADMIN INTERFACE

### 1.1 Admin Sidebar (includes/sidebar-admin.php)

**Variables Required from Backend:**
- `$admin_current_page` - string, active page ID (default: 'fleet')

**Navigation Items (6 pages):**
| ID | Label | Icon | URL |
|----|-------|------|-----|
| dashboard | Dashboard | grid | index.php |
| fleet | Fleet & Routes | truck | fleet.php |
| users | Users | users | users.php |
| schedules | Schedules | calendar | schedules.php |
| reports | Reports | bar-chart | reports.php |
| settings | Settings | settings | settings.php |

**Backend Requirements:**
- Session-based authentication check
- Admin role verification

---

### 1.2 Admin Topbar (includes/topbar-admin.php)

**Variables Required from Backend:**
- `$topbar_title` - string, page title (default: 'Dashboard')
- `$topbar_title_prefix` - string, prefix like "Admin" (default: 'Admin')
- `$notification_count` - int, unread notifications (default: 3)
- `$admin_name` - string, current admin name (default: 'Admin')
- `$admin_initials` - string, first 2 letters uppercase

**Backend Requirements:**
- Notification system with unread count
- User session data retrieval
- Dropdown menu for account actions

---

### 1.3 Admin Fleet & Route Registry Page (admin/fleet.php)

**Full Page Template with PHP Integration Points:**

```php
<?php
/**
 * REQUIRED PHP INTEGRATION
 * ========================
 * 1. Session/auth check - redirect if not logged in as admin
 * 2. Fleet vehicles data from database
 * 3. System alerts from database
 */

// AUTHENTICATION CHECK (REQUIRED)
session_start();
require_once '../includes/auth.php';
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header('Location: /index.php');
    exit;
}

// PAGE CONFIG
$admin_current_page  = 'fleet';
$topbar_title        = 'Fleet & Route Registry';
$topbar_title_prefix = 'Admin';
$notification_count  = 3;  // FROM: SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0
$admin_name          = $_SESSION['user_name'] ?? 'Admin';

// FLEET DATA QUERY (REQUIRED)
// SELECT v.*, r.route_name FROM vehicles v 
// LEFT JOIN routes r ON v.route_id = r.route_id 
// ORDER BY v.vehicle_id
$fleet_vehicles = [
  ['id' => 'BUS-1042', 'type' => 'Bus',   'status' => 'Active',      'route' => 'Route 42: Downtown Loop',  'last_maint' => '2026-5-15'],
  ['id' => 'TRN-501A', 'type' => 'Train', 'status' => 'Active',      'route' => 'Route B: Airport Express', 'last_maint' => '2026-5-01'],
  ['id' => 'BUS-2105', 'type' => 'Bus',   'status' => 'Maintenance', 'route' => 'Unassigned',               'last_maint' => '2026-5-10'],
];

// ALERTS QUERY (REQUIRED)
// SELECT * FROM alerts ORDER BY created_at DESC LIMIT 5
$system_alerts = [
  ['type' => 'warning',     'icon' => 'triangle', 'color_cls' => 'alert-icon-red',    'message' => 'Route 42 delay due to traffic', 'time' => '10:45 AM'],
  ['type' => 'maintenance', 'icon' => 'bus',      'color_cls' => 'alert-icon-orange', 'message' => 'Vehicle TRN-501A maintenance due soon', 'time' => 'Yesterday'],
  ['type' => 'info',        'icon' => 'user',     'color_cls' => 'alert-icon-green',  'message' => 'New driver assigned to Bus-1042', 'time' => '2 days ago'],
];

$assets = '../assets';
?>
```

**Fleet Table Columns:**
| Column | Data Type | Source |
|--------|-----------|--------|
| Vehicle ID | string | vehicles.vehicle_id |
| Type | enum('Bus','Train') | vehicles.type |
| Status | enum('Active','Maintenance','Inactive') | vehicles.status |
| Assigned Route | string | routes.route_name OR 'Unassigned' |
| Last Maintenance | date | vehicles.last_maintenance_date |
| Actions | links | Edit, View |

**Action URLs:**
- Edit: `edit-vehicle.php?id={vehicle_id}&row={index}`
- View: `view-vehicle.php?id={vehicle_id}&row={index}`

**Search Functionality:**
- Input ID: `fleet-search`
- Real-time client-side filtering on all columns

**Button Actions:**
- "Assign New Route" → Opens modal or navigates to assignment page

---

## 2. PASSENGER INTERFACE

### 2.1 Passenger Sidebar (includes/sidebar-passenger.php)

**Variables Required from Backend:**
- `$current_page` - string, active page ID (default: 'dashboard')
- `$passenger_name` - string, full name from session (default: 'Sarah J.')
- `$passenger_initials` - string, auto-generated from name

**Navigation Items (6 pages):**
| ID | Label | Icon | URL |
|----|-------|------|-----|
| dashboard | Dashboard | grid | dashboard.php |
| passes | My Passes | credit-card | passes.php |
| trip-history | Trip History | clock | trips.php |
| routes-schedules | Routes & Schedules | map | routes.php |
| profile | Profile | user | profile.php |
| settings | Settings | settings | settings.php |

**Logout Form:**
```html
<form method="post" action="/auth/logout.php">
  <button type="submit">Log Out</button>
</form>
```

---

### 2.2 Passenger Dashboard (passenger/dashboard.php)

**Full Page Template with PHP Integration Points:**

```php
<?php
/**
 * REQUIRED PHP INTEGRATION
 * ========================
 * 1. Session/auth check - passenger role
 * 2. Active pass data from passes table
 * 3. Recent trip history from trips table (paginated)
 */

// AUTHENTICATION CHECK (REQUIRED)
session_start();
require_once '../includes/auth.php';
if (!isLoggedIn() || $_SESSION['role'] !== 'passenger') {
    header('Location: /index.php');
    exit;
}

// PAGE CONFIG
$current_page   = 'dashboard';
$passenger_name = $_SESSION['user_name'] ?? 'Passenger';

// ACTIVE PASS QUERY (REQUIRED)
// SELECT * FROM passes 
// WHERE user_id = ? AND status = 'active' 
// AND valid_until >= CURDATE()
// LIMIT 1
$active_pass = [
  'label'       => 'Monthly Unlimited Pass - Zone 1-3',
  'valid_until' => 'Oct 31, 2024',
  'balance'     => '0.00 Tk (Unlimited)',
  'pass_token'  => 'PASS_TOKEN_FOR_QR',  // For QR generation
];

// TRIP HISTORY QUERY (REQUIRED - PAGINATED)
// SELECT t.*, r.route_name FROM trips t
// JOIN routes r ON t.route_id = r.route_id
// WHERE t.user_id = ?
// ORDER BY t.created_at DESC
// LIMIT 20 OFFSET ?
$recent_trips = [
  ['date' => 'Oct 26, 8:30 AM', 'route' => 'Bus Route 42',  'from' => 'Central Station', 'to' => 'Tech Park', 'fare' => '2.50 Tk', 'status' => 'Completed'],
  ['date' => 'Oct 25, 6:15 PM', 'route' => 'Subway Line A', 'from' => 'Downtown',        'to' => 'Uptown',    'fare' => '3.00 Tk', 'status' => 'Completed'],
];

$assets = '../assets';
?>
```

**Active Pass Status Card:**
- Pass label pill (colored badge)
- QR Code (SVG placeholder - needs dynamic generation)
- Valid Until date
- Balance amount
- "Renew Pass" button → links to `renew.php`

**QR Code Generation Note:**
```php
// RECOMMENDED: Use endroid/qr-code library
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;

$qr = QrCode::create($pass_token)->setSize(110)->setMargin(4);
$qrSvg = (new SvgWriter())->write($qr)->getString();
```

**Find Route Card:**
- From: input with location icon
- To: input with destination icon
- "Search Route" button (ID: `btn-search-route`)
- Leaflet Map container (ID: `map`)

**Trip History Table:**
| Column | Data Type | Source |
|--------|-----------|--------|
| Date & Time | string | trips.trip_date formatted |
| Route | string | routes.route_name |
| From | string | trips.origin_stop |
| To | string | trips.destination_stop |
| Fare | decimal | trips.fare_amount |
| Status | enum | trips.status |

**Pagination:**
- Client-side pagination (3 rows per page)
- Controls: Previous, Page Number, Next
- Backend should implement server-side pagination for performance

---

## 3. CSS STYLESHEETS

### 3.1 Base Styles (assets/css/style.css)

**Design Tokens (CSS Variables):**
```css
:root {
  --sidebar-bg:        #1a2332;
  --sidebar-width:     220px;
  --primary-blue:      #2563eb;
  --primary-blue-dark: #1d4ed8;
  --primary-blue-hover:#1e40af;
  --surface-white:     #ffffff;
  --surface-light:     #f8fafc;
  --table-header-bg:   #f1f5f9;
  --border-color:      #e2e8f0;
  --text-primary:      #0f172a;
  --text-muted:        #64748b;
  --text-sidebar-inactive: #94a3b8;
  --badge-active-bg:   #dcfce7;
  --badge-active-text: #16a34a;
  --badge-maint-bg:    #fee2e2;
  --badge-maint-text:  #dc2626;
  --card-radius:       12px;
  --btn-radius:        8px;
  --badge-radius:      20px;
  --transition:        0.18s ease;
}
```

**Key Components:**
- `.layout` - Flex container for sidebar + main content
- `.sidebar` - Fixed 220px dark navigation
- `.main-content` - Flexible content area with 24px padding
- `.card` - White rounded container with border
- `.btn` - Primary (filled) and Outlined variants
- `.badge` - Status indicators (active, maintenance, completed)
- `.table-wrapper` - Responsive table container
- `.input-field` - Form inputs with focus states
- `.pagination` - Page navigation controls
- `.hamburger` - Mobile menu toggle (hidden on desktop)

**Responsive Breakpoints:**
- Desktop: > 1024px (2-column grids)
- Tablet: 768px - 1024px (stacked layouts)
- Mobile: < 768px (hidden sidebar, hamburger menu)

---

### 3.2 Admin Styles (assets/css/admin.css)

**Components:**
- `.admin-topbar` - Header with title, notifications, avatar
- `.admin-content-grid` - 2-column layout (1fr 280px)
- `.fleet-card-header` - Toolbar with search and actions
- `.alerts-card` - System notifications panel
- `.alert-icon-circle` - Color-coded alert icons (red/orange/green)

---

### 3.3 Passenger Styles (assets/css/passenger.css)

**Components:**
- `.sidebar-user` - Avatar + welcome message in sidebar
- `.passenger-dashboard` - Main container
- `.dashboard-grid` - 2-column grid for pass + route cards
- `.pass-pill` - Blue pass label badge
- `.qr-code-box` - 120x120 QR container
- `.pass-info-box` - Valid until + balance info
- `.route-inputs` - From/To input grid
- `#map` - Leaflet map container (200px height)
- `.trip-history-card` - Full-width trip table

---

## 4. JAVASCRIPT FUNCTIONALITY

### 4.1 Main UI (assets/js/main.js)

**Features:**

1. **Mobile Sidebar Toggle**
   - Opens/closes sidebar on mobile (< 768px)
   - Prevents body scroll when open
   - Click overlay to close

2. **Trip History Pagination (Client-side)**
   - 3 rows per page
   - Updates on Previous/Next click
   - Shows/hides rows based on current page

3. **Fleet Table Search**
   - Real-time filtering on input
   - Searches all row text content

**Backend Note:** Replace client-side pagination with AJAX calls for large datasets.

---

### 4.2 Map Integration (assets/js/map.js)

**External Dependencies:**
- Leaflet CSS: `https://unpkg.com/leaflet@1.9.4/dist/leaflet.css`
- Leaflet JS: `https://unpkg.com/leaflet@1.9.4/dist/leaflet.js`

**Features:**
- Map centered on Bangladesh (40.7128, -74.0060)
- OpenStreetMap tile layer
- Custom blue dot markers for stops
- Sample route polyline

**Backend Integration Points:**
```javascript
// TODO: Fetch real route data from backend
fetch('/api/routes.php?from=lat,lng&to=lat,lng')
  .then(r => r.json())
  .then(data => {
    // Draw route on map
    // Add markers for stops
  });
```

---

## 5. REQUIRED DATABASE SCHEMA

### 5.1 Users Table
```sql
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'passenger', 'driver') DEFAULT 'passenger',
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 5.2 Vehicles Table
```sql
CREATE TABLE vehicles (
    vehicle_id VARCHAR(20) PRIMARY KEY,
    type ENUM('Bus', 'Train', 'Tram') NOT NULL,
    status ENUM('Active', 'Maintenance', 'Inactive') DEFAULT 'Active',
    route_id INT,
    last_maintenance_date DATE,
    next_maintenance_date DATE,
    capacity INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (route_id) REFERENCES routes(route_id)
);
```

### 5.3 Routes Table
```sql
CREATE TABLE routes (
    route_id INT PRIMARY KEY AUTO_INCREMENT,
    route_name VARCHAR(100) NOT NULL,
    route_number VARCHAR(20),
    start_location VARCHAR(100),
    end_location VARCHAR(100),
    description TEXT,
    status ENUM('Active', 'Suspended', 'Planned') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 5.4 Passes Table
```sql
CREATE TABLE passes (
    pass_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    pass_type ENUM('Single', 'Daily', 'Weekly', 'Monthly', 'Yearly') NOT NULL,
    zone_range VARCHAR(20),
    valid_from DATE NOT NULL,
    valid_until DATE NOT NULL,
    status ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
    pass_token VARCHAR(100) UNIQUE,  -- For QR generation
    balance DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);
```

### 5.5 Trips Table
```sql
CREATE TABLE trips (
    trip_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    route_id INT NOT NULL,
    vehicle_id VARCHAR(20),
    origin_stop VARCHAR(100),
    destination_stop VARCHAR(100),
    fare_amount DECIMAL(6,2),
    trip_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Completed', 'Cancelled', 'In Progress') DEFAULT 'Completed',
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (route_id) REFERENCES routes(route_id),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id)
);
```

### 5.6 Alerts Table
```sql
CREATE TABLE alerts (
    alert_id INT PRIMARY KEY AUTO_INCREMENT,
    alert_type ENUM('warning', 'maintenance', 'info', 'critical') NOT NULL,
    message TEXT NOT NULL,
    related_vehicle_id VARCHAR(20),
    related_route_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (related_vehicle_id) REFERENCES vehicles(vehicle_id),
    FOREIGN KEY (related_route_id) REFERENCES routes(route_id)
);
```

### 5.7 Notifications Table
```sql
CREATE TABLE notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(100),
    message TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);
```

---

## 6. REQUIRED API ENDPOINTS

### 6.1 Authentication
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/login.php` | Login handler |
| POST | `/auth/logout.php` | Logout handler |
| POST | `/auth/register.php` | Registration |
| GET | `/auth/check.php` | Session check |

### 6.2 Admin APIs
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/fleet.php` | Get all vehicles |
| POST | `/api/admin/fleet.php` | Add new vehicle |
| PUT | `/api/admin/fleet.php?id={id}` | Update vehicle |
| DELETE | `/api/admin/fleet.php?id={id}` | Delete vehicle |
| GET | `/api/admin/alerts.php` | Get system alerts |
| POST | `/api/admin/alerts.php` | Create alert |
| GET | `/api/admin/users.php` | Get all users |

### 6.3 Passenger APIs
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/passenger/pass.php` | Get active pass |
| POST | `/api/passenger/renew.php` | Renew pass |
| GET | `/api/passenger/trips.php?page={n}` | Get trip history |
| GET | `/api/passenger/routes.php?from={lat,lng}&to={lat,lng}` | Find routes |
| GET | `/api/passenger/notifications.php` | Get notifications |
| PUT | `/api/passenger/notifications.php?id={id}` | Mark as read |

---

## 7. AUTHENTICATION REQUIREMENTS

### 7.1 Session Management
```php
// Required functions in includes/auth.php:

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function getUserRole(): string {
    return $_SESSION['role'] ?? 'guest';
}

function requireAuth(string $role = null): void {
    if (!isLoggedIn()) {
        header('Location: /index.php');
        exit;
    }
    if ($role && $_SESSION['role'] !== $role) {
        header('Location: /unauthorized.php');
        exit;
    }
}

function logout(): void {
    session_destroy();
    header('Location: /index.php');
    exit;
}
```

### 7.2 Protected Routes
- All `/admin/*` pages → require `admin` role
- All `/passenger/*` pages → require `passenger` role
- `/api/*` endpoints → check Authorization header or session

---

## 8. SECURITY CONSIDERATIONS

1. **SQL Injection Prevention:** Use prepared statements for all queries
2. **XSS Prevention:** Use `htmlspecialchars()` when outputting user data
3. **CSRF Protection:** Add CSRF tokens to all forms
4. **Password Hashing:** Use `password_hash()` with bcrypt
5. **Session Security:** Regenerate ID on login, set secure flags
6. **File Uploads:** Validate types, restrict to images only in uploads/
7. **Rate Limiting:** Implement on login and API endpoints

---

## 9. FRONTEND FEATURES SUMMARY

### Admin Features
- [ ] Fleet management table with search
- [ ] Vehicle status badges (Active/Maintenance)
- [ ] Edit/View actions per vehicle
- [ ] Assign new route functionality
- [ ] System alerts panel
- [ ] Notification bell with badge count
- [ ] User avatar dropdown
- [ ] Mobile-responsive sidebar

### Passenger Features
- [ ] Active pass display with QR code
- [ ] Pass renewal functionality
- [ ] Route finder with map
- [ ] Trip history with pagination
- [ ] Real-time location search
- [ ] Mobile-responsive layout

---

## 10. EXTERNAL DEPENDENCIES

| Dependency | CDN URL | Purpose |
|------------|---------|---------|
| Inter Font | Google Fonts | Typography |
| Leaflet CSS | unpkg.com/leaflet@1.9.4/dist/leaflet.css | Map styling |
| Leaflet JS | unpkg.com/leaflet@1.9.4/dist/leaflet.js | Interactive maps |

---

## 11. IMPLEMENTATION CHECKLIST

### Phase 1: Core Infrastructure
- [ ] Database setup with all tables
- [ ] Database connection (includes/db.php)
- [ ] Authentication system (includes/auth.php)
- [ ] Login page (index.php)

### Phase 2: Admin Backend
- [ ] Fleet CRUD API endpoints
- [ ] Alerts management API
- [ ] Admin auth middleware

### Phase 3: Passenger Backend
- [ ] Pass management API
- [ ] Trip history API with pagination
- [ ] Route search API with geocoding
- [ ] QR code generation

### Phase 4: Integration
- [ ] Replace mock data with real queries
- [ ] Test all API endpoints
- [ ] Security audit
- [ ] Performance optimization
