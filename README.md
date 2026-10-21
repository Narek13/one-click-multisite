# One Click Multisite

Convert your single WordPress site into a multisite network with **one click**.

![WordPress version](https://img.shields.io/badge/WordPress-6.0%2B-blue)  
![PHP version](https://img.shields.io/badge/PHP-8.0%2B-green)

---

## 🚀 Features

- One-click conversion from single-site to multisite.
- Automatically configures `wp-config.php` and `.htaccess`.
- Integrates with the native **Network Setup** screen.
- Removes the manual setup guide and replaces it with an action button.

---

## 🛠 Installation

1. Upload the plugin to your `/wp-content/plugins/` directory.
2. Activate it via the WordPress admin panel.
3. Navigate to **Tools → Network Setup**.
4. Choose between **Subdomains** or **Subdirectories**.
5. Click the “**Finish Multisite Setup Automatically**” button.

---

## 🧰 Development

### Requirements

- PHP 8.0+
- WordPress 6.0+
- Composer (for linting and dev tools)

### Coding Standards

```bash
composer install
composer run lint     # Check coding standards
composer run lint:fix # Auto-fix issues
