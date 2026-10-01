# NOVA, a minimal Framework for PHP

A small PHP framework and website template without external libraries. It is meant as a starting point for small business websites: copy it, fill in the client's data, change the colors, go live.

Needs PHP 7.4 or newer (`mbstring`, `session`, `json`; `openssl` for encrypted mail, `pdo_mysql` only if the site uses a database) and Apache with `mod_rewrite`.

## New website for a client

1. Copy the project. Copy `config/config.example.ini` to `config/config.ini`.
2. `config/site.php`: name, tagline, menu, address, opening hours, language (`de` or `en`).
3. `assets/theme.css`: colors, fonts, shapes. Put the client's picture into `/pictures` (and point `--hero-image` at it), replace `favicon.ico`.
4. `config/config.ini`: mail settings (see below). Add `[app]` `debug = 1` on your own computer only.
5. Texts of the start page and the contact page are in `src/lang/de.php` and `en.php`. Change single texts for one client in `config/site.php` under `texts`, or edit the views in `src/view/`.
6. Before going live run `php nova check`.

| What | Where |
|---|---|
| Content of the site (git) | `config/site.php` |
| Passwords and server settings (not in git) | `config/config.ini` |
| Look | `assets/theme.css`, `assets/style.css` |
| Pages | `src/controller/*.class.php` + `src/view/<page>/show.phtml` |
| Menu, header, footer | `config/site.php` (`nav`), `src/view/navbar.phtml`, `src/view/footer.phtml` |
| Texts | `src/lang/` |
| Scroll story on the start page (pictures that change while scrolling, text cards in front) | pictures and focus point in `assets/theme.css` (`--story-image-N`, `--story-position-N`), texts `story.N_title` / `story.N_text` in `src/lang/`, steps and card side in `src/view/home/show.phtml` |

Save pictures for the web before using them: about 1920 px wide, as WebP or JPG, under 300 KB. A photo straight from a phone or camera is several MB and makes the start page slow.

Standard pages: **Home** `/`, **Services** `/leistungen` (cards with price, from `services` in `config/site.php`), **Gallery** `/galerie` (shows every picture in `pictures/gallery/`, sorted by file name; descriptions from the file names or `gallery_alts` in `config/site.php`), **Contact** `/kontakt` (form that sends an e-mail), **Impressum** `/impressum`, **Privacy** `/datenschutz`. The pretty urls are set in `config/site.php` under `routes`, the menu under `nav`. Without a route the controller name is the url (`/contact`). Pages a client doesn't need: remove them from `nav` and `routes` (and delete the controller and view).

## Search engines (local SEO)

Set these in `config/site.php`:
- `url`: the live address, e.g. `https://www.client.de` (no slash at the end). Without it there is no sitemap and no canonical link.
- `noindex`: `true` while the site is not ready (test address), so search engines stay away (`robots.txt` and a meta tag). **Set it to `false` when the site goes live.**
- `image`: picture shown when a page is shared, also sent to Google.
- `business` → `type` (e.g. `Restaurant`, `Hairdresser`, `Plumber`, `Store`), address, phone, `hours`: sent to Google as structured business data (schema.org) in every page. Keep the format of `hours` (`Mo – Fr`, `09:00 – 18:00`), rows it can't read are shown but not sent.
- `social`: links for the footer and for Google.

`/robots.txt` and `/sitemap.xml` are made automatically (menu pages plus the legal pages). After going live: register the site at Google Search Console and submit the sitemap, and have the client set up the Google Business Profile with the same data. The "get directions" link opens Google Maps only when clicked, so no map is loaded from a third party and no consent is needed. On phones a "Call now" bar appears at the bottom when `phone` is set.

## Adding a page

```
php nova make About --no-model        # page without a database: controller About + view about/show.phtml, opens at /about
php nova make Product                 # with a database table "product": controller, model ProductModel, view
```
The pages made with a model list the whole table for everybody. Remove or protect them before going live if the data is private. Put the page into the `nav` list in `config/site.php`.

## Mail

The contact form sends to `to` under `[mail]`. Use the SMTP data of the client's mailbox, PHP's `mail()` is a fallback that many hosters deliver badly.

```
[mail]
to = "info@client.de"
from_address = "info@client.de"
host = "smtp.client-hoster.de"
port = 587
encryption = tls          ; tls (587) or ssl (465)
username = "info@client.de"
password = "..."
```
Test it: `php nova mail:test you@example.com`. If the mail server refuses a contact message, the visitor sees an error with the direct e-mail address, and the message is saved in `storage/outbox/` so it isn't lost.

## What is protected

- **Debug is off unless `config/config.ini` says `debug = 1`.** A live server without that file never shows error details. Errors are logged to `storage/logs/php-error.log`. Fatal errors and failing views give a clean error page in the site's layout (JSON for javascript requests).
- **CSRF:** every POST (or PUT, DELETE, ...) needs the token of the form. Put `<?= csrf_field() ?>` into each form. The Router checks it, no controller code is needed. For javascript requests send the token in the header `X-CSRF-Token` (`Csrf::token()`). A controller can turn it off with `protected $csrf = false;` (only for webhooks).
- **Sessions:** start only when a page needs them (the contact form), so other pages set no cookie. Cookie is `HttpOnly`, `SameSite=Lax`, `Secure` + `__Host-` prefix on https. Own session folder in `storage/`, idle timeout 2 hours, strict mode. After a login call `Session::regenerate()`, on logout `Session::destroy()`.
- **Methods:** controllers only answer GET, HEAD and POST. Change per controller with `protected $allowedMethods`.
- **Headers:** Content-Security-Policy (only files from this site, no inline scripts), `X-Frame-Options`, `nosniff`, `Referrer-Policy`, `Permissions-Policy`, HSTS on https. If a client needs something external (a map), allow it in `config/site.php` under `csp` and update the privacy page.
- **Contact form:** honeypot field, minimum fill-in time, 5 messages per hour and visitor (`RateLimit`), validation, header injection impossible. Behind a proxy or Cloudflare set `proxy_header` under `[security]`, or all visitors share one address and one limit.
- **Folders:** `config/`, `storage/`, `src/` (except `src/scripts`), `nova`, `*.md`, `*.ini`, `*.log` and dotfiles can't be requested from the web (`.htaccess`, plus a second lock in `config/` and `storage/`).

After the site runs on https, remove the `#` in front of the redirect in `.htaccess`.

## Legal pages

`Impressum` and `Privacy` are **templates, not legal advice**. They are filled from the `business` block in `config/site.php`, empty fields are left out. Which details are mandatory depends on the business (legal form, trade register, chambers, regulated professions). The privacy text matches the site as delivered: one technically necessary session cookie, no tracking, no external fonts, maps or videos. As soon as something is added (analytics, Google Maps, YouTube, newsletter, shop, login) the text must be extended and usually consent is needed. Have both pages checked with the client before going live.

## Terminal

```
php nova help
php nova make <Name> [--table=<table>] [--no-model]
php nova check                       # go-live checklist
php nova mail:test <address>
```
nova is part of the framework, run it from the project folder.

## Database

Only needed when a page uses a model. Fill the `[database]` section in `config/config.ini`. Right now only MySQL is supported, more database options are on the roadmap.

---

If you have got ideas or want to help make the NOVA-Framework better, contributions are totally welcome!
Just a quick note: this framework is meant to give web developers a simple, no-fuss starting point for building new websites, without relying on big, heavy frameworks or libraries.

This project is licensed under the **GNU LGPL v2.1**.  
You may use, modify, and distribute it under the terms of this license.  
Modifications must remain open-source under the same license.  

Copyright (C) 2026 Hamzah Mansor
