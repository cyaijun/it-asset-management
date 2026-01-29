# IT Asset Management (PHP + MySQL) 鈥?Deploy to IIS

This package is a minimal IT asset management system in PHP + MySQL.
Features:
- Asset create / checkout / checkin / list / user management
- Server-side QR code generation (PNG)
- Mobile scan page using camera (jsQR)
- Responsive UI (Bootstrap)

Deploy steps:
1. Unzip it-asset.zip to IIS site folder (e.g. C:\inetpub\wwwroot\it-asset).
2. Edit db.php with your MySQL credentials.
3. Run scripts/create_db.sql on your MySQL server to create tables.
4. Open site in browser: e.g. http://yourserver/it-asset/
5. For mobile scanning, visit: http://yourserver/it-asset/?p=scan and allow camera.

Security notes:
- This is a demo. Add authentication, input validation, and CSRF protection for production.
- Do NOT commit real DB passwords to a public repo.
