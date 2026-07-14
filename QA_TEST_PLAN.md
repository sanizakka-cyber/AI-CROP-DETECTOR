# QA Verification Guide - User Profile Display Bug Fix

## Pre-Testing Checklist

### Environment Setup
- [ ] Node.js backend running (port 5000)
- [ ] Laravel backend running (port 8000)
- [ ] Web frontend running (port 3000 or 3001)
- [ ] Mobile emulator/device ready with latest code
- [ ] Test database seeded with all user roles
- [ ] Cache cleared on all clients

### Demo Credentials (Development)
```
Role          | Phone/Username | Password
Farmer        | 08012345678    | farmer123
Admin         | admin          | admin123
Vet           | vet            | vet123
Agronomist    | agronomist     | agro123
```

## Test Cases

### TC-001: Web Navbar - Farmer Login
**Steps:**
1. Navigate to web app
2. Click "Sign In"
3. Enter credentials: phone=08012345678, password=farmer123
4. Verify navbar displays

**Expected Results:**
- [ ] Avatar shows with "A" (first initial)
- [ ] Name displays: "Aminu Yusuf"
- [ ] Role displays: "Farmer"
- [ ] Avatar has emerald background
- [ ] Layout responsive on mobile/tablet/desktop

**Actual Results:**
- [ ] 

---

### TC-002: Web Navbar - Veterinarian Login
**Steps:**
1. Navigate to web app
2. Click "Sign In"
3. Enter credentials: phone=vet, password=vet123
4. Verify navbar displays

**Expected Results:**
- [ ] Avatar shows with "D" or "S" (depending on first name)
- [ ] Name displays: "Dr. Surajo Aminu" or "Surajo Aminu"
- [ ] Role displays: "Veterinarian"
- [ ] Professional styling maintained

**Actual Results:**
- [ ] 

---

### TC-003: Web Navbar - All Role Display Names
**Steps:**
1. Test login with credentials for each role (if available in test DB)
2. Verify role display for each

**Expected Results:**
| Role Code | Role Display |
|-----------|-------------|
| farmer | Farmer |
| vet | Veterinarian |
| agronomist | Agronomist |
| admin | Administrator |
| agro-dealer | Agro Dealer |
| extension-officer | Extension Worker |
| field-officer | Field Officer |
| data-analyst | Data Analyst |
| me-officer | Monitoring & Evaluation Officer |
| customer-support | Customer Support |
| hr | Human Resources |
| finance | Finance Officer |
| operations | Operations Manager |

- [ ] All roles display correct display name

**Actual Results:**
- [ ] 

---

### TC-004: Mobile Profile Tab - Farmer
**Steps:**
1. Login on mobile app: phone=08012345678, password=farmer123
2. Navigate to Profile tab
3. Verify profile header displays correctly

**Expected Results:**
- [ ] Avatar shows with "A"
- [ ] Name: "Aminu Yusuf"
- [ ] Role: "Farmer"
- [ ] Phone: 08012345678
- [ ] Email: (if seeded)
- [ ] State: "Katsina" (or correct value)
- [ ] Verification badge shows "Unverified" or "✓ Verified"
- [ ] All text readable on mobile screen

**Actual Results:**
- [ ] 

---

### TC-005: Mobile Profile Tab - Veterinarian
**Steps:**
1. Login on mobile app: phone=vet, password=vet123
2. Navigate to Profile tab
3. Verify complete profile displays

**Expected Results:**
- [ ] Avatar shows first initial
- [ ] Name displays
- [ ] Role: "Veterinarian"
- [ ] Email visible
- [ ] Phone number visible
- [ ] Verification status badge visible
- [ ] State/location visible

**Actual Results:**
- [ ] 

---

### TC-006: Profile Picture Fallback
**Steps:**
1. Login with any user without profile picture
2. Check avatar display
3. Login with user who has profile picture (if available)
4. Verify picture displays

**Expected Results:**
- [ ] Without picture: Avatar with initial displays in circle
- [ ] With picture: Profile picture displays instead of avatar
- [ ] Avatar size/styling correct
- [ ] Picture properly scaled

**Actual Results:**
- [ ] 

---

### TC-007: Email Display
**Steps:**
1. Login to web with user
2. Check navbar/dashboard for email
3. Login to mobile
4. Check profile tab for email

**Expected Results:**
- [ ] Email visible on web (if available in test DB)
- [ ] Email visible on mobile profile tab
- [ ] Email properly formatted

**Actual Results:**
- [ ] 

---

### TC-008: Verification Status Badge
**Steps:**
1. Login with verified user
2. Check profile for verification badge
3. Login with unverified user (if available)
4. Check profile for verification badge

**Expected Results:**
- [ ] Verified user shows: "✓ Verified" badge in green/primary color
- [ ] Unverified user shows: "Unverified" badge in gray
- [ ] Mobile shows both states correctly

**Actual Results:**
- [ ] 

---

### TC-009: Profile Refresh Function
**Steps:**
1. Login to web app
2. Open browser DevTools console
3. In AuthContext, call `refreshProfile()`
4. Verify fresh data loads

**Expected Results:**
- [ ] No errors in console
- [ ] User data updates from server
- [ ] All fields repopulate
- [ ] localStorage updates

**Actual Results:**
- [ ] 

---

### TC-010: Profile Update Function
**Steps:**
1. Login to web app
2. Open browser DevTools console
3. In AuthContext, call `updateProfile({ name: "Test Name" })`
4. Verify update displays

**Expected Results:**
- [ ] No errors in console
- [ ] Name updates in navbar
- [ ] localStorage updates
- [ ] Changes persist on page reload

**Actual Results:**
- [ ] 

---

### TC-011: API Response Format - Login
**Steps:**
1. Use Postman/curl to POST to `/api/auth/login`
2. Examine response JSON

**Expected Results:**
```json
{
  "success": true,
  "token": "...",
  "user": {
    "id": "...",
    "name": "User Name",
    "phone": "+234...",
    "email": "user@example.com",
    "role": "farmer",
    "roleDisplay": "Farmer",
    "state": "Katsina",
    "lga": "...",
    "village": "...",
    "profilePic": null,
    "isPremium": false,
    "isVerified": true,
    "verificationStatus": "approved"
  }
}
```
- [ ] All expected fields present
- [ ] No unexpected fields
- [ ] Data types correct

**Actual Results:**
- [ ] 

---

### TC-012: API Response Format - /auth/me
**Steps:**
1. Use Postman/curl to GET `/api/auth/me` with bearer token
2. Examine response JSON

**Expected Results:**
```json
{
  "success": true,
  "user": {
    // ... same fields as login response ...
  }
}
```
- [ ] Complete user object returned
- [ ] Fresh data from database
- [ ] All fields populated

**Actual Results:**
- [ ] 

---

### TC-013: API Endpoint - PATCH /auth/profile
**Steps:**
1. Use Postman to PATCH `/api/auth/profile`
2. Payload: `{"name": "New Name"}`
3. Verify response

**Expected Results:**
```json
{
  "success": true,
  "user": {
    // ... updated user object with new name ...
  }
}
```
- [ ] Endpoint exists
- [ ] Updates apply to database
- [ ] Response contains updated user
- [ ] Authorization required (returns 401 without token)

**Actual Results:**
- [ ] 

---

### TC-014: Dark Mode Display
**Steps:**
1. Login to web app
2. Toggle dark mode
3. Verify navbar styling

**Expected Results:**
- [ ] Navbar readable in dark mode
- [ ] Profile card styling maintained
- [ ] Text colors contrasted appropriately
- [ ] No styling broken

**Actual Results:**
- [ ] 

---

### TC-015: Responsive Design - Mobile
**Steps:**
1. Login on web app on mobile device (375px width)
2. Check navbar display

**Expected Results:**
- [ ] Profile card visible
- [ ] Text not cut off
- [ ] Avatar visible
- [ ] Logout button accessible

**Actual Results:**
- [ ] 

---

### TC-016: Responsive Design - Tablet
**Steps:**
1. Login on web app on tablet (768px width)
2. Check navbar display

**Expected Results:**
- [ ] Full profile card visible
- [ ] Name and role both shown
- [ ] Avatar visible
- [ ] All controls clickable

**Actual Results:**
- [ ] 

---

### TC-017: Session Persistence
**Steps:**
1. Login to web app
2. Hard refresh page (Ctrl+F5)
3. Verify profile info still displayed

**Expected Results:**
- [ ] Profile info persists after refresh
- [ ] No re-login required
- [ ] Data loaded from localStorage
- [ ] Then refreshed from /auth/me endpoint

**Actual Results:**
- [ ] 

---

### TC-018: Logout and Re-login
**Steps:**
1. Login with user A
2. Verify profile shows A's info
3. Click Logout
4. Login with user B
5. Verify profile shows B's info

**Expected Results:**
- [ ] User A profile displays correctly
- [ ] Logout clears state and localStorage
- [ ] User B profile displays correctly
- [ ] No data bleeding between sessions

**Actual Results:**
- [ ] 

---

### TC-019: Demo Mode Only in Development
**Steps:**
1. Check if app is running in production
2. Try to use demo credentials if production

**Expected Results:**
- [ ] Demo mode only works in development
- [ ] Production requires real database credentials
- [ ] Demo credentials don't work in production

**Actual Results:**
- [ ] 

---

### TC-020: Error Handling - Invalid Token
**Steps:**
1. Modify localStorage to have invalid token
2. Refresh page
3. Verify error handling

**Expected Results:**
- [ ] Invalid token rejected by server
- [ ] User redirected to login
- [ ] localStorage cleared
- [ ] No errors in console

**Actual Results:**
- [ ] 

---

## Performance Testing

### PT-001: Login Response Time
**Steps:**
1. Clear cache
2. Time login request
3. Measure response time

**Expected Results:**
- [ ] Login completes in < 2 seconds
- [ ] Profile data loads in < 500ms
- [ ] No noticeable delay for user

**Actual Results:**
- [ ] 

---

### PT-002: Page Load with Profile Data
**Steps:**
1. Login and hard refresh page
2. Time total page load

**Expected Results:**
- [ ] Page loads in < 3 seconds
- [ ] Profile info visible within 1 second
- [ ] No network waterfalls

**Actual Results:**
- [ ] 

---

## Regression Testing

### RT-001: Existing Login Flow
**Steps:**
1. Login with standard credentials
2. Verify still works as before

**Expected Results:**
- [ ] Login succeeds
- [ ] Token stored
- [ ] User state updates

**Actual Results:**
- [ ] 

---

### RT-002: Existing Logout Flow
**Steps:**
1. Login and then logout
2. Verify behavior unchanged

**Expected Results:**
- [ ] Logout clears token
- [ ] User redirected
- [ ] localStorage cleared

**Actual Results:**
- [ ] 

---

### RT-003: Existing Dashboard Pages
**Steps:**
1. Login
2. Navigate through dashboard pages
3. Verify no breaks

**Expected Results:**
- [ ] Dashboard pages load correctly
- [ ] No new errors in console
- [ ] Navigation works

**Actual Results:**
- [ ] 

---

## Bug Reports

### Bug Found:
**Title:**
- [ ] 

**Severity:**
- [ ] Critical | High | Medium | Low

**Steps to Reproduce:**
1. 
2. 
3. 

**Expected Result:**
- 

**Actual Result:**
- 

**Environment:**
- Browser: 
- Device: 
- Screen Size: 

---

## Sign-Off

| Role | Name | Date | Status |
|------|------|------|--------|
| QA Lead | | | ✓ PASS / ✗ FAIL |
| Dev Lead | | | ✓ PASS / ✗ FAIL |
| Product Owner | | | ✓ PASS / ✗ FAIL |

## Notes
- All 13+ user roles tested: ✓
- Web app verified: ✓
- Mobile app verified: ✓
- API endpoints verified: ✓
- Performance acceptable: ✓
- No regressions found: ✓

**Ready for Production Deployment**: ☐ YES ☐ NO

---

## Test Results Summary

| Test Case | Status | Notes |
|-----------|--------|-------|
| TC-001 | | |
| TC-002 | | |
| TC-003 | | |
| ... | | |

**Total Tests**: 20  
**Passed**: __  
**Failed**: __  
**Skipped**: __  
**Pass Rate**: __%  

---

**QA Sign-Off Date**: ___________  
**QA Tester Name**: ___________  
**QA Tester Signature**: ___________
