# Fix: Tutors Not Showing on Page

## Problem
The tutors & PALs page is empty - no tutors are displaying.

## Cause
The tutors and PALs haven't been inserted into the database yet.

## Solution

### Step 1: Check if tutors exist
1. Open your browser and go to: `http://localhost/booking/test-tutors.php`
2. This will show you if any tutors/PALs exist in the database

### Step 2: Insert tutors into database

**Option A: Using phpMyAdmin (Recommended)**
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Select the `wsu_booking` database
3. Click on the "SQL" tab
4. Copy and paste the contents of `database/insert_tutors_only.sql`
5. Click "Go" to execute

**Option B: Using MySQL command line**
```bash
mysql -u root -p wsu_booking < database/insert_tutors_only.sql
```

**Option C: Using XAMPP Shell**
1. Open XAMPP Control Panel
2. Click "Shell" button
3. Run:
```bash
cd C:\xampp\htdocs\booking
mysql -u root wsu_booking < database/insert_tutors_only.sql
```

### Step 3: Verify tutors were inserted
1. Refresh the test page: `http://localhost/booking/test-tutors.php`
2. You should see 6 tutors and 6 PALs listed

### Step 4: View tutors page
1. Log in as coordinator (COORD001, password: password123)
2. Go to "Tutors & PALs" page
3. You should now see all 12 tutors/PALs

## What Gets Inserted

### 6 Tutors:
- TUT001 - Sipho Mthembu (MSc Computer Science)
- TUT002 - Nomsa Dlamini (MSc Mathematics)
- TUT003 - Mandla Zulu (MSc Engineering)
- TUT004 - Zanele Ndlovu (MSc Economics)
- TUT005 - Thabo Mokoena (MSc Physics)
- TUT006 - Lerato Molefe (MSc Chemistry)

### 6 PALs:
- PAL001 - Thandi Khumalo (BSc Computer Science Honours)
- PAL002 - Bongani Sithole (BSc Computer Science Honours)
- PAL003 - Lindiwe Moyo (BA Education Honours)
- PAL004 - Thabo Nkosi (BCom Accounting Honours)
- PAL005 - Nokuthula Dube (BSc Mathematics Honours)
- PAL006 - Mpho Radebe (BCom Economics Honours)

All passwords: `password123`

## Troubleshooting

### Still not showing?
1. Check the test page shows tutors exist
2. Clear browser cache (Ctrl+F5)
3. Check browser console for JavaScript errors (F12)
4. Verify you're logged in as coordinator
5. Check the coordinator's assigned campus matches the tutors

### Database connection error?
1. Make sure XAMPP MySQL is running
2. Check `config/database.php` has correct credentials
3. Verify database name is `wsu_booking`

### Need to start fresh?
Run the full sample data script:
```sql
-- In phpMyAdmin SQL tab:
source database/sample_modules_data.sql;
```

This will insert:
- 4 Coordinators
- 12 Tutors/PALs
- 31 Modules across 4 campuses
