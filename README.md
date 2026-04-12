<div align="center">

# NebulaCMS

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Laravel](https://img.shields.io/badge/Laravel-12.0-FF2D20?logo=laravel)](https://laravel.com)
[![React](https://img.shields.io/badge/React-19.0-61DAFB?logo=react)](https://reactjs.org)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.7-3178C6?logo=typescript)](https://www.typescriptlang.org)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)](http://makeapullrequest.com)

NebulaCMS is a modern and open-source Content Management System (CMS) built with Laravel 12 and React. It is designed to provide an intuitive, flexible, and powerful content management experience.

[Demo](https://nebula.angkasalabs.com/demo) • [Dokumentasi](https://nebula.angkasalabs.com/docs) • [Roadmap](https://github.com/AngkasaLabs/NebulaCMS/projects) • [Contribute](#-Contribute) 

</div>

## ✨ Highlights

- 🚀 **Modern Stack** - Laravel 12 + React + TypeScript
- 🎨 **Elegant UI/UX** - With Radix UI, Shadcn UI, and TailwindCSS
- 📱 **Fully Responsive** - Perfect display on all devices
- 🔒 **Secure by Default** - Best security practices
- 🌐 **SEO Friendly** - Built-in search engine optimization
- 🔌 **Extensible** - Modular plugin and theme system

## 🎯 Main Features

- 📝 **Content Management**
  - Post and Page Management with draft/publish system
  - WYSIWYG Editor with TinyMCE and TipTap
  - Categories and Tags for content organization
  - Media Manager for file and image management

- 🎨 **Theme System**
  - Flexible theme system
  - Support for multiple themes
  - Ability to upload and activate themes
  - Customizable layouts

- 👥 **User Management**
  - Role-Based Access Control (RBAC)
  - User authentication and authorization
  - Optional two-factor authentication (TOTP authenticator apps) under **Settings → Security**
  - Activity / audit log for sign-ins and content changes (**Audit log** in the admin sidebar; requires `view audit log`)
  - Email verification
  - Permission management

[See all features in the documentation](https://nebula.angkasalabs.com/docs/features)

## 🛠️ Tech Stack

- **Backend:**
  - Laravel 12
  - PHP 8.3+
  - MySQL/PostgreSQL

- **Frontend:**
  - React with TypeScript
  - Inertia.js
  - TailwindCSS
  - Shadcn UI Components
  - Radix UI Components

[Complete tech stack details](https://nebula.angkasalabs.com/docs/tech-stack)

## 🚀 Quick Start

**Installing without Git or a terminal?** Download the latest **`nebulacms-v*.zip`** from the **[Download page](https://nebula.angkasalabs.com/download)** (same package as [GitHub Releases](https://github.com/AngkasaLabs/NebulaCMS/releases)) — it includes dependencies and a production build. Upload and extract on your host, point the document root to the `public` folder, create a MySQL database, then open the site and complete the **web installer** at `/install`. See the [installation guide](https://nebula.angkasalabs.com/docs/installation) (section *Install from a release ZIP*).

**Developing from source:**

```bash
# Clone repository
git clone https://github.com/AngkasaLabs/NebulaCMS.git

# Install dependency
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Migrate database
php artisan migrate --seed

# Build & running
npm run build
php artisan serve
```

[Complete installation guide](https://nebula.angkasalabs.com/docs/installation)


## Production: Task Scheduler (Scheduled Posts)

NebulaCMS registers the `posts:publish-scheduled` command in [`routes/console.php`](routes/console.php) to ensure posts with a **scheduled** status are automatically published at their `published_at` time. Laravel will only execute this scheduled task if the server’s **cron** is configured to run the scheduler.

**Required in production:** Add the following cron entry (make sure to adjust the project path and user as needed):

```bash
* * * * * cd /path/to/nebulacms && php artisan schedule:run >> /dev/null 2>&1
```

Without this line, scheduled posts will never be published automatically; you would have to run `php artisan posts:publish-scheduled` manually. See also [Laravel — task scheduling](https://laravel.com/docs/scheduling) for more information.

**For local development:** While developing, run `php artisan schedule:work` in a separate terminal (or set up cron in WSL) to test scheduled tasks without deploying.

## 📷 Media: image variants & S3 / CDN

- **Varian gambar:** untuk gambar raster (bukan SVG/GIF), setelah unggah NebulaCMS membuat preset `thumb`, `medium`, dan `large` (lebar maks. 300 / 768 / 1920 px) memakai [Intervention Image](https://image.intervention.io/). Metadata disimpan di kolom `variants`; URL lengkap tersedia di `variant_urls` pada API media admin. Matikan dengan `MEDIA_IMAGE_VARIANTS=false` atau sesuaikan preset di `config/media.php`.
- **Disk penyimpanan:** set `MEDIA_DISK=public` (default, `storage/app/public` + symlink `public/storage`) atau `MEDIA_DISK=s3` setelah mengisi `AWS_*` di `.env` dan memastikan bucket serta IAM sudah benar.
- **CDN / URL publik:** untuk disk `public`, set `MEDIA_URL` ke basis URL aset Anda (mis. `https://cdn.example.com/storage`) agar `Storage::url()` mengarah ke CDN. Untuk S3, gunakan `AWS_URL` (mis. CloudFront) sesuai [dokumentasi Laravel filesystem](https://laravel.com/docs/filesystem).

**Full guide:** [Media storage, variants & CDN](https://nebula.angkasalabs.com/docs/media) on the documentation site.

## 🤝 Contribute

We greatly appreciate contributions from the community! NebulaCMS is an open-source project, and we welcome contributions in various forms:

- 🐛 Reporting bugs
- 💡 Suggesting new features
- 📝 Improving documentation
- 💻 Submitting pull requests

Before contributing, please read our [Contribution Guidelines](CONTRIBUTING.md).

### Contributors

<a href="https://github.com/AngkasaLabs/NebulaCMS/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=AngkasaLabs/NebulaCMS" />
</a>

## 📊 Roadmap

See our [Project Board](https://github.com/AngkasaLabs/NebulaCMS/projects) for upcoming development plans.

## 📜 License

NebulaCMS is licensed under the [MIT License](LICENSE).

## 💬 Community

- [GitHub Discussions](https://github.com/AngkasaLabs/NebulaCMS/discussions)

## 🌟 Sponsors

If you like NebulaCMS and want to support its development:

[![Sponsor on GitHub](https://img.shields.io/badge/Sponsor-GitHub-ea4aaa?logo=github-sponsors)](https://github.com/sponsors/AngkasaLabs)
[![Sponsor on OpenCollective](https://img.shields.io/badge/Sponsor-OpenCollective-7FADF2?logo=open-collective)](https://opencollective.com/nebulacms)

## 🙏 Special Thanks

- [Laravel](https://laravel.com)
- [React](https://reactjs.org)
- [TailwindCSS](https://tailwindcss.com)
- [Shadcn UI](https://ui.shadcn.com/)
- [Radix UI](https://www.radix-ui.com)
- [And all contributors](https://github.com/AngkasaLabs/NebulaCMS/graphs/contributors)
