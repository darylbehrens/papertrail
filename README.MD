# 🦉 Papertrail: Owl Sightings Tracker

Papertrail is a custom WordPress-based platform designed for responsible tracking and archiving of owl sightings across Oregon. Unlike public platforms like eBird or Merlin—which can inadvertently expose sensitive nesting or roosting locations—Papertrail prioritizes owl safety by handling data visibility more carefully.

## 🌲 Why This Project Matters

Owls, especially protected species like the Northern Spotted Owl or Great Gray Owl, are vulnerable to disturbance when their locations are shared publicly. This platform allows users to:

- Record sightings with **generalized location data** (e.g., county)
- Automatically **hide the location field** from the public when a sighting involves a protected species
- Submit details like date, species, notes, and a photo of the bird
- View an archive of all submitted sightings with filters by species or date range

This ensures valuable data can still be gathered without compromising the safety of the birds.

---

## 🔧 Technologies Used

This app was built in direct response to a PHP-heavy job stack, with hands-on work in:

- **PHP 8+** – Writing procedural and OOP-style logic
- **WordPress** – Custom plugin and theme development
- **MySQL** – Managing and querying relational data
- **Timber + Twig** – Templating system for WP views
- **VVV + Vagrant + VirtualBox** – Local WordPress development environment
- **Custom Post Types** – `owl_sighting` as the core data structure
- **Custom Meta Boxes** – Species, date spotted, notes, location, photo, and "protected" toggle
- **REST API (planned)** – Placeholder for future integration with external tools
- **Frontend styling** – Pure CSS with mobile-first responsiveness
- **Wikipedia API** – Real-time species information during sighting submission

Optional integrations and experience explored:
- **Cloudflare** (for future WAF or DNS use)
- **Bitbucket Pipelines** (researched for CI/CD)
- **SSH + Ubuntu terminal usage**
- **LinkedIn Learning Courses**:
  - PHP Essential Training
  - PHP OOP
  - WordPress Plugin and CPT development
  - PHP for WordPress

---

## ✨ Wikipedia API Integration

The submission form includes a **"More Info" button** next to the owl species dropdown.

When clicked, it:

1. Makes a live request to the **Wikipedia REST API**
2. Fetches a short summary and image for the selected owl species
3. Displays this data inline in the form to help users verify their selection

Fallbacks are gracefully handled (e.g., missing image or article), and requests are cached per session to reduce load.

This demonstrates:
- Using JavaScript to make `fetch()` calls to external APIs
- Dynamically updating DOM content based on user selection
- Responsibly integrating third-party data into WordPress workflows

---

## 🎯 Features

| Feature                          | Description                                                                 |
|----------------------------------|-----------------------------------------------------------------------------|
| 🦉 Owl Submission Form           | Users can submit sightings with species, photo, notes, and date            |
| 🔍 Wikipedia Lookup              | Fetches real-time owl summaries and images                                 |
| 🔒 Protected Species Handling     | Automatically hides the location of protected species in public listings   |
| 🔍 Species/Date Filtering        | Visitors can filter sightings by species or time range                     |
| 🖼️ Image Upload + Display         | Users can include and download owl images                                  |
| 📚 Twig Templates via Timber     | Cleanly separated layout and logic layers for maintainability              |
| 🧠 Smart Data Display            | Human-readable defaults, data sanitization, and safe fallbacks             |

---

## ⚠️ Privacy & Ethics Note

Many owl species are under threat from habitat loss and overexposure. Posting precise locations online (especially in real time) can lead to habitat disruption, stress, or even poaching.

This app:
- **Never shows exact coordinates**
- **Hides county-level data entirely for protected owls**
- **Encourages responsible birding** over clout-seeking posts

---

## 🚀 Local Setup (with VVV)

This project uses [VVV (Varying Vagrant Vagrants)](https://varyingvagrantvagrants.org/) for WordPress development.

```bash
git clone https://github.com/darylbehrens/papertrail.git
cd papertrail/vvv-local
vagrant up
