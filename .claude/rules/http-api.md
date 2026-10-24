---
paths:
  - "app/Modules/*/Http/**"
  - "app/Modules/*/routes/**"
  - "app/Modules/*/Policies/**"
  - "routes/**"
---

# HTTP, API and authorization

- Controllers stay thin: authorize, validate with a Form Request, call one Action, return a view, redirect or JSON Resource.
- Every sensitive action has a Policy check. Admin panel access requires the `admin-panel.access` permission.
- Module API routes live in `app/Modules/<Modulo>/routes/api.php`; the module provider adds the `api/v1` prefix. Use JSON Resources, never raw models.
- API auth: Sanctum (session cookie for our own frontend, tokens only for external clients). Web auth: session cookie, single `web` guard with roles.
- Public IDs in URLs and QR codes are ULIDs, never auto-increment IDs.
- Rate limit login, password reset and public certificate verification.
- Uploaded files: validate the real MIME type, store outside `public/`, serve through a controller that checks the Policy.
- Never echo raw user input; never return stack traces (`APP_DEBUG=false` in production).
