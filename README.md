# Memories Event Manager

A simple PHP application for managing events and guests. This project is a small demonstration used to track event details and invited guests.

## Setup
1. Copy `config/config-example.php` to `config/config.php` and adjust the database credentials and DigitalOcean Spaces information.
2. Run `composer install` to fetch required PHP dependencies such as the AWS SDK and QR code library.
3. Make sure the required MySQL databases exist and the credentials match your setup.
4. Run the SQL in `sql/alter_add_public_id.sql` to add the `public_id` column used for public event links.

5. Run the SQL in `sql/alter_add_upload_folder.sql` to add the `upload_folder` column storing each event's DigitalOcean folder path.

6. Run the SQL in `sql/create_feed_tables.sql` to create tables used for the public feed feature.

7. The guest selector relies on the Choices.js library loaded from a CDN. Ensure the host running the app can access the CDN or adjust the paths accordingly.
8. The public event page uses JavaScript and AJAX for posting memories, comments and likes. No additional setup is required but JavaScript must be enabled in the browser.
9. When uploading a new memory you can choose to capture media from your phone's camera or select an existing file. This works out of the box and requires no server changes.
10. Create an `uploads` directory in the project root, make it writable and ensure FFmpeg is installed for video processing.
11. Ensure the PHP EXIF extension is enabled so photos taken on phones keep their correct orientation.

## Running
Use PHP's built-in server from the project root:
```bash
php -S localhost:8000 -t public
```
Then browse to `http://localhost:8000`.

## Directory Structure
- `public/` – entry point scripts and frontend pages
- `templates/` – shared page fragments
- `config/` – configuration samples
- `src/` – PHP classes (currently minimal)

## Event Status
Events progress through three states: **Created**, **Started** and **Ended**. The
status can be changed on the Edit Event page and is displayed in the events
list.

## Event Header Images
You can upload an optional header image when creating or editing an event. Images are uploaded to your configured DigitalOcean Spaces bucket.

## Event QR Codes
Each event page shows a QR code linking to the public event URL. You can regenerate the event's public identifier from the Edit Event screen which will update the QR code accordingly.

## Uploads Management
Staff users can browse all uploaded memories on the **Uploads** page in the dashboard. Choose an event from the drop-down to see its files and download or delete them. No extra setup is required beyond the feed tables mentioned above.

## Development Notes
Run `php -l public/*.php` before committing to ensure there are no syntax errors.

## Language Support
The event management pages (events.php and event.php) now support a simple
translation system. Append `?lang=es` to the URL to switch to Spanish. If no
language is provided the interface defaults to English.

The public event page (`/e/{id}`) also understands the `lang` parameter and
uses the same translation system for its interface.

### Progressive Web App
The public event page includes a minimal Web App Manifest and service worker.
`manifest.json` and `sw.js` reside in the `public/` folder. No additional build
steps are required—just ensure these files are served by your web server.

### Camera Uploads
`Webcam.js` is loaded from a CDN to allow capturing a photo directly from the
browser. The feature works out of the box and does not need extra server
configuration.
