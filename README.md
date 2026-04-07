# CLAW - Content & Link Acquisition Workspace

A comprehensive web application for managing content, media, and publishing workflows.

## Features

- **Admin Dashboard**: Full-featured administrative panel
- **Content Management**: Pages and posts editor
- **Media Management**: Upload and manage images
- **User Authentication**: Secure login system
- **Customizable Themes**: Multiple theme options including Ocean Breeze, Forest Zen, Midnight Code, and more
- **Invoice System**: Built-in invoice management
- **Responsive Design**: Mobile-friendly interface

## Project Structure

```
claw/
├── admin/                 # Admin panel interface
│   ├── assets/           # Admin-specific CSS
│   ├── login.php         # Login page
│   ├── pages.php         # Page management
│   ├── posts.php         # Post management
│   ├── media.php         # Media management
│   └── settings-general.php
├── assets/               # Public assets
│   ├── css/             # Stylesheets and themes
│   └── js/              # JavaScript files
├── includes/            # Core includes
│   ├── auth.php         # Authentication
│   ├── functions.php    # Helper functions
│   └── settings.php     # Settings
├── templates/           # HTML templates
├── invoice/             # Invoice management
├── uploads/             # User uploads directory
└── config.php          # Configuration file
```

## Installation

1. Clone the repository:
```bash
git clone https://github.com/nayan35224108-cmd/claw.git
cd claw
```

2. Configure the application:
   - Copy `config.php` and update database credentials
   - Set proper file permissions for `uploads/` directory

3. Set up the database and run initial setup

## Requirements

- PHP 7.4 or higher
- MySQL/MariaDB
- Node.js (for invoice server)
- Web server (Apache/Nginx)

## Configuration

Edit `config.php` to set up your database connection and other application settings.

## Usage

### Admin Panel
Access the admin panel at `/admin/` and log in with your credentials.

### Managing Content
- **Pages**: Create and edit static pages
- **Posts**: Publish and manage blog posts
- **Media**: Upload and organize images

### Themes
Multiple themes are available in `assets/css/`:
- Ocean Breeze
- Forest Zen
- Midnight Code
- Inner Glow (multiple variants)
- Theme Crab Manja

## API & Services

### Invoice Server
The invoice system includes a Node.js server:
```bash
cd invoice/server
npm install
npm start
```

## Security

- Always use HTTPS in production
- Keep credentials secure in `.env` or environment variables
- Regularly update dependencies
- Implement proper access controls

## Author

Nayan (nayan.35224108@mhs.polines.ac.id)

## License

Please refer to the LICENSE file for licensing information.

## Support

For issues and feature requests, please use the GitHub issues page.
