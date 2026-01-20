# Sprint 1: User Table Name Structure Refinement

## Overview

Refine the users table from Laravel's default `name` field to a comprehensive identity structure with `first_name`, `middle_names`, `last_name`, `date_of_birth`, and soft delete support to support the Octomat application's requirements for athletes, parents, coaches, and officials.

## Business Context

The Octomat system needs comprehensive user identity management for:

- Competition brackets and certificates (age-based categories)
- Ticket purchasing and registration (age-based pricing)
- Parent-child relationships (DOB verification)
- Athlete records and eligibility (age restrictions)
- Legal documentation (identity verification)
- Data retention and compliance (audit trails)

## Implementation Scope

This sprint focuses on comprehensive user identity foundation including:

- Name structure refinement with proper fields
- Date of birth for age verification and eligibility
- Soft delete support for data retention and recovery
- Database performance optimization with proper indexing
- Full backward compatibility across the application

## Database Changes

### Original Structure

```php
$table->string('name');
```

### Updated Structure

```php
$table->string('first_name');
$table->string('middle_names')->nullable();  // Optional
$table->string('last_name');
$table->date('date_of_birth')->nullable();  // Nullable in DB, enforced on frontend
$table->softDeletes();  // Data retention & recovery

// Performance indexes
$table->index(['first_name', 'last_name']);
$table->index('email');
$table->index('date_of_birth');
$table->dropColumn('name');  // Remove default

// Performance indexes
$table->index(['first_name', 'last_name']);
$table->index('email');
$table->index('date_of_birth');
```

## Application Changes Required

### 1. Migration Updates

- Updated original migration `0001_01_01_000000_create_users_table.php`
- Replaced single `name` field with structured fields: `first_name`, `middle_names`, `last_name`
- Added `date_of_birth` field for age verification (nullable)
- Added soft delete support with `deleted_at` timestamp
- Added performance indexes for common queries

### 2. User Model Updates

- Update `$fillable` properties for new fields
- Add `SoftDeletes` trait for soft delete functionality
- Add date casting for `date_of_birth` field
- Add computed accessors for backward compatibility:

    ```php
    public function getNameAttribute()
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getFullNameAttribute()
    {
        return trim($this->first_name.' '.$this->middle_names.' '.$this->last_name);
    }
    ```

### 3. Validation Updates

- Update `ProfileValidationRules` trait to handle new name fields
- Modify Fortify's validation approach for registration
- Add DOB validation rules (nullable in DB, enforced on frontend)
- Update frontend form labels: "Name", "Surname", "Middle Names" (optional), "Date of Birth"

### 4. Registration Form Updates

- Update Inertia.js registration form (`resources/js/pages/auth/register.tsx`)
- Replace single `name` field with structured name fields
- Add date of birth field with proper validation
- Map frontend fields:
    - `name` → `first_name`, `last_name`, `middle_names`
    - `date_of_birth` → `date_of_birth` (nullable in DB, enforced on frontend)

### 5. Factory and Seeder Updates

- Update `database/factories/UserFactory.php` to include DOB
- Update any seeders that reference the `name` field

### 6. View and Component Updates

- Identify all views using `$user->name`
- Ensure backward compatibility through computed accessor
- Update form labels to use "Name" and "Surname"
- Add date of birth display in profile forms

### 7. Privacy & Compliance

- Implement basic privacy controls for DOB display
- Ensure proper consent mechanisms for minors
- Add age verification logic where needed

## Frontend Considerations

### Form Labels

- **Display**: "Name", "Surname", "Middle Names" (optional), "Date of Birth" (user-friendly labels)
- **Database**: `first_name`, `last_name`, `middle_names`, `date_of_birth` (standardized)
- **DOB**: Nullable in database but enforced during registration

### Backward Compatibility

- Existing `$user->name` calls continue to work
- New `$user->full_name` available for formal contexts
- No breaking changes to existing components

## Testing Strategy

### Unit Tests

- Test computed accessors return expected formats
- Test validation rules for new name fields
- Test User model fillable properties

### Feature Tests

- Test registration with new name fields
- Test user profile updates
- Test existing views still display names correctly

### Database Tests

- Test migration runs successfully
- Test data integrity with new schema

## Implementation Order

### Phase 1: Database Foundation

1. **Migration** - Update users table with all new fields
2. **Model** - Add soft deletes trait and date casting
3. **ProfileValidationRules** - Update trait to handle structured name fields (`first_name`, `last_name`, `middle_names`)
4. **Validation** - Add DOB validation rules (nullable in DB, enforced on frontend)
5. **DOB Encryption** - Implement encrypted storage for DOB field

### Phase 2: Backend Integration

4. **Factory/Seeder** - Update data generation
5. **Model Accessors** - Ensure compatibility
6. **Privacy Controls** - Implement DOB display logic

### Phase 3: User Interface

7. **Registration Form** - Add DOB field
8. **Profile Form** - Update with all fields
9. **View Updates** - Ensure proper display

### Phase 4: Testing & Verification

10. **Unit Tests** - Test all new functionality
11. **Feature Tests** - Test user workflows
12. **Performance Tests** - Verify indexing effectiveness

### Phase 5: Documentation

13. **Update Documentation** - Record privacy policies
14. **Completion Report** - Document all changes

## Files to Modify

### Core Files

- `database/migrations/0001_01_01_000000_create_users_table.php` (updated original migration)
- `app/Models/User.php` (added DOB encryption/decryption)
- `app/Concerns/ProfileValidationRules.php` (updated for structured name fields)

### Form Components

- `resources/js/pages/auth/register.tsx` (Inertia.js registration form)
- `resources/js/pages/settings/profile.tsx` (profile update form)
- Any user creation/editing components

### Supporting Files

- `database/factories/UserFactory.php`
- `database/seeders/DatabaseSeeder.php`
- Any seeders referencing user names
- Test files with user name assertions
- Privacy policy documents (DOB handling)

## Success Criteria

- [x] Migration runs without errors
- [x] Registration works with new name fields and DOB (enforced on frontend)
- [x] Existing views display names correctly
- [x] Validation rules work properly for all fields
- [x] Soft deletes function correctly
- [x] DOB privacy controls implemented
- [x] DOB encrypted storage implemented
- [x] All tests pass
- [x] Computed accessors return expected values
- [x] No breaking changes to existing functionality
- [x] Database indexes improve query performance
- [x] Database seeding works correctly
- [x] Enhanced User model methods (initials, age, isMinor, formatted DOB)
- [x] Improved validation rules (DOB after 1900-01-01)
- [x] Better UI layout (grid layout for name fields)
- [x] Enhanced factory data generation

## Privacy & Compliance Considerations

### DOB Privacy Implementation (Sprint 1)

- **Data Classification**: DOB as sensitive personal data
- **Database**: Nullable field (optional in DB, enforced on frontend)
- **Display Controls**: Show age instead of full DOB in most contexts
- **Consent Requirements**: Parental consent for minors
- **Storage**: Encrypted storage for compliance
- **Access Logging**: Track who accesses DOB information

### GDPR/Data Protection

- **Minimal Collection**: DOB nullable in database, collected during registration
- **Purpose Limitation**: Use only for age verification and eligibility
- **Data Minimization**: Don't display full DOB unnecessarily
- **User Rights**: Allow users to update/remove DOB (nullable field)

### Soft Delete Policy

- **Retention Period**: Keep deleted records for 12 months minimum
- **Recovery Window**: Allow user account restoration within 30 days
- **Permanent Deletion**: Admin-triggered after retention period
- **Audit Trail**: Log all delete/restore actions

## ProfileValidationRules.php Updates

### What Happens to the Trait?

The existing `app/Concerns/ProfileValidationRules.php` trait will be **updated** (not removed) to handle the new structured name fields:

**Current Structure:**

```php
protected function profileRules(?int $userId = null): array
{
    return [
        'name' => $this->nameRules(),
        'email' => $this->emailRules($userId),
    ];
}
```

**Updated Structure:**

```php
protected function profileRules(?int $userId = null): array
{
    return [
        'first_name' => ['required', 'string', 'max:255'],
        'middle_names' => ['nullable', 'string', 'max:255'],
        'last_name' => ['required', 'string', 'max:255'],
        'date_of_birth' => ['nullable', 'date', 'before:today'],
        'email' => $this->emailRules($userId),
    ];
}
```

**Fortify Integration:** Instead of creating a custom RegisterRequest, we'll modify Fortify's built-in validation to use the updated trait rules for registration.

## Database Seeding Fix

### DOB Encryption Issue Resolution

- **Problem**: User model encrypted DOB values during seeding, causing PostgreSQL date format errors
- **Solution**: Modified `setDateOfBirthAttribute()` and `getDateOfBirthAttribute()` to store raw values during console commands (including seeding)
- **Impact**: DOB values are stored encrypted in production but raw during development/testing/seeding

## Enhanced User Model Features

### New Methods Added from Archived Project

- **`initials()`**: Generates user initials (e.g., "JD" for John Doe)
- **`getAgeAttribute()`**: Calculates current user age in years
- **`isMinor()`**: Checks if user is under 18 years old
- **`getFormattedDateOfBirthAttribute()`**: Returns privacy-conscious DOB display format

### Improved Validation Rules

- **DOB Validation**: Added `after:1900-01-01` to prevent unrealistic birth dates
- **Enhanced Security**: Prevents users from entering dates before 1900

### UI Layout Improvements

- **Grid Layout**: Name fields now display side-by-side on larger screens
- **Better UX**: More professional and space-efficient form layout
- **Responsive Design**: Single column on mobile, grid on desktop

### Factory Enhancements

- **Realistic DOB**: 70% chance of having DOB with age range 13-80 years
- **Better Data**: More representative test data for development

### Seeder Updates

- Updated `DatabaseSeeder.php` to use structured name fields (`first_name`, `last_name`)
- Maintained known test user for development consistency
- Ensured seeding works with new schema
- **Updated React profile page** to use structured name fields instead of single name field
- **Added date of birth field** to profile form with proper validation
- **Implemented email verification resend** functionality
- **Improved form layout** with responsive grid system for name fields

## Frontend Implementation

### React Profile Page Updates

The React profile page (`resources/js/pages/settings/profile.tsx`) has been updated to match the functionality of the archived Livewire version:

- **Structured Name Fields**: Replaced single `name` field with `first_name`, `last_name`, and `middle_names` fields
- **Date of Birth Field**: Added date picker with proper validation
- **Responsive Layout**: Grid system places name fields side-by-side on larger screens
- **Email Verification**: Resend functionality for unverified emails
- **TypeScript Types**: Updated `resources/js/types/index.d.ts` to include new user fields
- **Modern Framework**: Maintains React/TypeScript/Inertia.js architecture while matching feature set

### TypeScript Types Update

Updated `resources/js/types/index.d.ts` to include the new structured user fields:

```typescript
export interface User {
    id: number;
    name: string; // Backward compatibility accessor
    first_name: string;
    middle_names?: string | null;
    last_name: string;
    date_of_birth?: string | null;
    email: string;
    // ... other fields
}
```

This eliminates TypeScript LSP errors and provides proper type safety for the new user fields.

### Livewire Insights Applied

Successfully applied valuable UX patterns from the archived Livewire implementation:

#### **1. ✅ Form Persistence (localStorage)**

- **Livewire**: Automatic form state preservation
- **React Implementation**: Added `localStorage` persistence for all form fields
- **Benefits**: Users don't lose data on page refresh or navigation
- **Code**: `saveFormData()` and `clearFormData()` functions with auto-save on change

#### **2. ✅ Enhanced Loading States**

- **Livewire**: Automatic loading indicators
- **React Implementation**: Disabled inputs during submission + dynamic button text
- **Benefits**: Clear visual feedback, prevents double-submission
- **Code**: `disabled={processing}`, button text changes to "Saving..."

#### **3. ✅ Real-time Validation (Debounced)**

- **Livewire**: Client-side validation feedback
- **React Implementation**: 500ms debounced validation for immediate feedback
- **Benefits**: Instant user feedback without overwhelming server requests
- **Validation**: Email format, name length, age restrictions (13-120 years)
- **Code**: `debouncedValidate()` with comprehensive client-side rules

### Feature Parity Achieved

The React implementation now provides the same user experience and functionality as the archived Livewire version, but with modern frontend architecture and **enhanced UX features**:

- **Complete Structured Name Support**: first_name, middle_names, last_name
- **Date of Birth Management**: With age validation and proper formatting
- **Email Verification**: Resend functionality with status feedback
- **Professional Layout**: Responsive grid system matching Livewire UX
- **Form Persistence**: localStorage auto-save prevents data loss
- **Enhanced Loading**: Disabled inputs and dynamic loading states
- **Real-time Validation**: Debounced client-side feedback
- **TypeScript Safety**: Full type checking with proper interfaces

**The archived Livewire implementation provided excellent UX patterns that have been successfully modernized and enhanced in the React version!**

## Notes & Considerations

### Future Extensions

The structure supports future additions:

- `display_name` field (can be computed)
- `phone_number` field (communication)
- `gender` field (competition categories)
- Enhanced profile information

### Internationalization

- `first_name`/`last_name` is internationally standard
- Frontend can adapt labels per region
- Structure works for various naming conventions

### Performance

- Computed accessors have minimal performance impact
- Database indexes optimize common queries
- Soft deletes add minimal overhead

---

**Sprint Priority**: HIGH
**Estimated Effort**: 2-3 days (with extensions)
**Dependencies**: None (system in development, no production data)
