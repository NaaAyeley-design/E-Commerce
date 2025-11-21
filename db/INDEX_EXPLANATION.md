# Database Indexes Explanation

## What Are Indexes?

Indexes are database structures that speed up data retrieval. Think of them like an index in a book - instead of reading every page to find a topic, you can look it up in the index and go directly to the right page.

## Indexes in Your Schema

### 🔴 **CRITICAL INDEXES** (Do NOT Remove)

#### 1. **customer_email** (Line 17, 89)
```sql
UNIQUE KEY customer_email (customer_email)
CREATE INDEX idx_customer_email ON customer(customer_email);
```
**Purpose:** Used for user login authentication
**Why Critical:** Every login checks `WHERE customer_email = ?`. Without this index, MySQL scans the entire customer table.
**Performance Impact:** Without index: O(n) scan. With index: O(log n) lookup.
**Note:** Line 89 is redundant since line 17 already creates a unique index.

#### 2. **idx_product_cat** (Line 133)
```sql
INDEX idx_product_cat (product_cat)
```
**Purpose:** Filter products by category
**Why Critical:** Used in queries like:
- `SELECT * FROM products WHERE product_cat = ?`
- Product browsing by category
- Category filtering in search
**Performance Impact:** Essential for category pages and filtering

#### 3. **idx_product_brand** (Line 134)
```sql
INDEX idx_product_brand (product_brand)
```
**Purpose:** Filter products by brand
**Why Critical:** Used in brand filtering queries
**Performance Impact:** Speeds up brand-based product listings

#### 4. **idx_product_title** (Line 135)
```sql
INDEX idx_product_title (product_title)
```
**Purpose:** Product search functionality
**Why Critical:** Your code uses `WHERE product_title LIKE ?` for searches
**Performance Impact:** Without this, product searches become very slow as data grows

#### 5. **idx_order_status** (Line 177)
```sql
INDEX idx_order_status (order_status)
```
**Purpose:** Filter orders by status (pending, completed, cancelled, etc.)
**Why Critical:** Admin dashboards frequently filter orders by status
**Performance Impact:** Essential for order management pages

### 🟡 **IMPORTANT INDEXES** (Keep for Performance)

#### 6. **idx_product_price** (Line 136)
```sql
INDEX idx_product_price (product_price)
```
**Purpose:** Price-based sorting and filtering
**Why Important:** 
- Price range filtering (`WHERE product_price <= ?`)
- Sorting by price (`ORDER BY product_price`)
**Performance Impact:** Helps with price-based queries, but less critical than category/brand

#### 7. **idx_customer_role** (Line 90)
```sql
CREATE INDEX idx_customer_role ON customer(user_role);
```
**Purpose:** Filter users by role (admin vs customer)
**Why Important:** Admin functions need to find all admins or filter by role
**Performance Impact:** Useful for admin user management

#### 8. **idx_cat_name** (Line 58)
```sql
INDEX idx_cat_name (cat_name)
```
**Purpose:** Category search functionality
**Why Important:** Your code uses `WHERE cat_name LIKE ?` for category searches
**Performance Impact:** Speeds up category search/autocomplete

#### 9. **idx_brand_name** (Line 116)
```sql
INDEX idx_brand_name (brand_name)
```
**Purpose:** Brand search functionality
**Why Important:** Brand name searches and lookups
**Performance Impact:** Helps with brand search features

#### 10. **idx_is_active** (Line 117)
```sql
INDEX idx_is_active (is_active)
```
**Purpose:** Filter active/inactive brands
**Why Important:** Only show active brands to customers
**Performance Impact:** Speeds up queries filtering by active status

### 🟢 **OPTIONAL INDEXES** (Can Remove if Not Used)

#### 11. **idx_token** (Line 30)
```sql
INDEX idx_token (token)
```
**Purpose:** Password reset token lookups
**Why Optional:** Only used during password reset flow
**Performance Impact:** Small benefit, but password resets are infrequent

#### 12. **idx_expires** (Lines 31, 70, 85, 178)
```sql
INDEX idx_expires (expires_at)
```
**Purpose:** Clean up expired tokens/sessions
**Why Optional:** Only used for maintenance cleanup queries
**Performance Impact:** Helps with periodic cleanup, but not critical for daily operations

#### 13. **idx_created_at** (Lines 85, 91, 157, 178)
```sql
INDEX idx_created_at (created_at)
```
**Purpose:** Time-based queries and sorting
**Why Optional:** Only needed if you frequently sort/filter by creation date
**Performance Impact:** Useful for "recent items" queries, but not essential

#### 14. **idx_action** (Line 84)
```sql
INDEX idx_action (action)
```
**Purpose:** Filter audit log by action type
**Why Optional:** Only if you frequently query audit logs by action
**Performance Impact:** Minimal unless you have heavy audit log usage

#### 15. **idx_sort_order** (Line 155)
```sql
INDEX idx_sort_order (sort_order)
```
**Purpose:** Order product images by sort_order
**Why Optional:** Only if you frequently sort images
**Performance Impact:** Small benefit for image ordering

#### 16. **idx_is_primary** (Line 156)
```sql
INDEX idx_is_primary (is_primary)
```
**Purpose:** Find primary product image
**Why Optional:** Only if you frequently query for primary images
**Performance Impact:** Small benefit

### ⚠️ **REDUNDANT INDEXES** (Can Remove - Already Covered)

#### 17. **idx_customer_email** (Line 89)
```sql
CREATE INDEX idx_customer_email ON customer(customer_email);
```
**Status:** REDUNDANT - Line 17 already creates UNIQUE KEY which includes an index
**Action:** Safe to remove

#### 18. **idx_customer_created** (Line 91)
```sql
CREATE INDEX idx_customer_created ON customer(created_at);
```
**Status:** PROBLEMATIC - The `customer` table doesn't have a `created_at` column!
**Action:** This index will fail or be useless. Remove it.

#### 19. **Foreign Key Indexes** (Lines 114, 115, 133, 134, 154, 176, 192, 193)
**Status:** InnoDB automatically creates indexes on foreign key columns
**Action:** These are technically redundant but harmless. MySQL will use the existing index.

## Recommendations

### ✅ **KEEP These Indexes:**
1. All UNIQUE KEY indexes (customer_email, etc.)
2. idx_product_cat
3. idx_product_brand
4. idx_product_title
5. idx_order_status
6. idx_product_price (if you do price filtering)
7. idx_customer_role (if you have admin features)
8. idx_cat_name (if you search categories)
9. idx_brand_name (if you search brands)

### ❌ **SAFE TO REMOVE:**
1. idx_customer_email (line 89) - redundant
2. idx_customer_created (line 91) - references non-existent column
3. idx_expires indexes - only for cleanup operations
4. idx_created_at indexes - only if you don't sort by date
5. idx_token - only for password resets
6. idx_action - only for audit log filtering
7. idx_sort_order, idx_is_primary - only for image ordering

## Performance Impact

**Without Critical Indexes:**
- Login: **Slow** (full table scan)
- Product search: **Very Slow** (full table scan)
- Category filtering: **Slow** (full table scan)
- Order management: **Slow** (full table scan)

**With Critical Indexes:**
- Login: **Fast** (index lookup)
- Product search: **Fast** (index scan)
- Category filtering: **Fast** (index lookup)
- Order management: **Fast** (index lookup)

## When to Remove Indexes

Only remove indexes if:
1. ✅ You **never** query by that column
2. ✅ The table is **very small** (< 100 rows)
3. ✅ You need to **optimize write performance** (indexes slow down INSERT/UPDATE)

## General Rule

**For columns used in:**
- `WHERE` clauses → **Keep the index**
- `ORDER BY` clauses → **Keep the index**
- `JOIN` conditions → **Keep the index** (usually covered by foreign keys)
- `LIKE` searches → **Keep the index** (helps with prefix searches)

**For columns only used in:**
- `SELECT` (display only) → **Index not needed**
- Rare maintenance queries → **Index optional**


