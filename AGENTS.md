# AGENT INSTRUCTIONS

This is a PUNK-STYLE karaoke PROGRESSIVE WEB APP.

## Guiding Principles

- **Embrace the Aesthetic:** All code, comments, and assets should reflect a GRUNGE, DIY, ANARCHIC aesthetic.
- **Offline First:** Prioritize offline functionality. Ensure the app is usable without a network connection.
- **Performance is Punk:** Keep the app lean, mean, and fast. No bloated frameworks.
- **Raw and Gritty:** Use raw PHP, vanilla JavaScript, and custom solutions over off-the-shelf libraries where possible.

## Development

- To run the application for local development, use PHP's built-in server: `php -S localhost:8000 -t public`
- Ensure all new assets are added to the `URLS_TO_CACHE` array in `public/service-worker.js`.
- All styling should be done in `public/css/style.css`.

## Verification

- After making changes to the frontend, run a lighthouse audit to check for PWA compliance.
- Test offline functionality by disconnecting from the network and refreshing the page.
- Check PHP syntax with `php -l <filename>`.
