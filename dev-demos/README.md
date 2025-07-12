# Dev Demos

This folder contains developer-focused demos to showcase various PHP and WordPress techniques that are **not part of the main Owl Sightings plugin**, but demonstrate familiarity with the ecosystem and core concepts.

---

## 🧱 `Article.php` & `Database.php`

These files demonstrate **object-oriented PHP** using a simple article model and database access layer.

### ✅ Highlights

- OOP principles (class encapsulation, static methods, and type declarations)
- PDO-based MySQL access with secure prepared statements
- Use of `mb_substr()` for character-safe previews
- Defensive programming (null checks, fetch modes)

### 📄 Files

- `Article.php`: Represents an individual article record, with methods for loading all articles, fetching by ID, saving new entries, and returning a text summary.
- `Database.php`: A singleton-style connection manager that returns a PDO instance with error handling and performance settings.

These demonstrate your understanding of **clean PHP coding practices** and modern patterns.

---

## 📊 `papertrail-author-stats` Plugin

This small WordPress plugin was created to demonstrate:

- ✅ WordPress dashboard widgets
- ✅ Shortcode generation
- ✅ Theme integration via `wp_head` styling hook
- ✅ `WP_Widget` class extension and registration

### Features

- Adds an "Author Stats" dashboard widget showing post counts per author.
- Includes `[author_stats]` shortcode for embedding author data in posts/pages.
- Registers a `WP_Widget` instance for use in widgetized sidebars or footers.

This plugin is **demo-only** and not tied to the Owl Sightings plugin.

---

## 📝 Notes

These files are left intentionally modular and lightweight to focus on **core coding concepts** — not full application integration.

They are included in this project to demonstrate your:

- Object-Oriented PHP skills
- Familiarity with WordPress plugin architecture
- Comfort with both procedural and class-based code
