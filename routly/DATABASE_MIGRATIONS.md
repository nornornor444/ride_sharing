# Database Migrations - Women Driver Preference Feature

To implement the women driver preference feature, run the following SQL commands in phpMyAdmin:

## Required Changes

### 1. Add women_driver_pref column to passenger_info table
```sql
ALTER TABLE passenger_info ADD COLUMN women_driver_pref TINYINT(1) DEFAULT 0 AFTER gender;
```

### 2. Add gender column to driver_info table (if not already present)
```sql
ALTER TABLE driver_info ADD COLUMN gender VARCHAR(50) DEFAULT 'Not Specified';
```

### 3. Verify driver_preference table structure
Make sure your `driver_preference` table has these columns:
- driver_id (INT, PRIMARY KEY, FOREIGN KEY)
- chatty_flag (TINYINT)
- silent_flag (TINYINT)
- music_flag (TINYINT)

If the table doesn't exist, create it:
```sql
CREATE TABLE driver_preference (
    driver_id INT PRIMARY KEY,
    chatty_flag TINYINT(1) DEFAULT 0,
    silent_flag TINYINT(1) DEFAULT 0,
    music_flag TINYINT(1) DEFAULT 0,
    FOREIGN KEY (driver_id) REFERENCES driver_info(driver_id)
);
```

### 4. Verify passenger_preference table structure
Make sure your `passenger_preference` table has these columns:
- passenger_id (INT, PRIMARY KEY, FOREIGN KEY)
- chatty_flag (TINYINT)
- silent_flag (TINYINT)
- music_flag (TINYINT)

If the table doesn't exist, create it:
```sql
CREATE TABLE passenger_preference (
    passenger_id INT PRIMARY KEY,
    chatty_flag TINYINT(1) DEFAULT 0,
    silent_flag TINYINT(1) DEFAULT 0,
    music_flag TINYINT(1) DEFAULT 0,
    FOREIGN KEY (passenger_id) REFERENCES passenger_info(passenger_id)
);
```

### 5. Create route_subscription table for route subscriptions
```sql
CREATE TABLE route_subscription (
    subscription_id INT AUTO_INCREMENT PRIMARY KEY,
    passenger_id INT NOT NULL,
    route_id INT NOT NULL,
    subscription_type VARCHAR(20) NOT NULL COMMENT '1week, 1month, 3months, 6months, 1year',
    start_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    end_date DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
    FOREIGN KEY (passenger_id) REFERENCES passenger_info(passenger_id) ON DELETE CASCADE,
    FOREIGN KEY (route_id) REFERENCES route(route_id) ON DELETE CASCADE,
    INDEX idx_passenger (passenger_id),
    INDEX idx_route (route_id),
    INDEX idx_status (status)
);
```

## Features Implemented

✅ **edit_profile.php**: Women passengers can now toggle a "Women Driver Only" preference in their profile  
✅ **ride_booking.php**: When booking, if the preference is enabled, only female drivers will be shown  
✅ **route_subscription**: Passengers can subscribe to routes for 1 week, 1 month, 3 months, 6 months, or 1 year  
✅ Database-ready: Using existing passenger_info table with new women_driver_pref column

## How It Works

1. Passenger goes to "Edit Profile"
2. Checks the "👩 Women Driver Only" checkbox
3. Changes are saved to the database
4. When booking a ride, the system automatically filters to show only female drivers
5. If unchecked, all drivers are shown as normal

## Route Subscription Feature

1. Passenger selects a route and duration (1 week to 1 year)
2. Subscription is created with automatic end date calculation
3. Passengers can view their active subscriptions in their profile
4. Expired or cancelled subscriptions can be managed
