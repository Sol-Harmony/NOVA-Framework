# Setting up a website with this template

A short guide for turning the template into a finished website for one business. Everything the visitors see is changed in a few files, nothing else needs to be touched.

## 1. Start

1. Copy the project into a new folder, one copy per client.
2. Copy `config/config.example.ini` to `config/config.ini` (this file holds passwords, it is not in git).
3. Work through the steps below. At the end run `php nova check`, it lists what is still missing.

## 2. The business data: `config/site.php`

| Setting | What to enter |
|---|---|
| `name`, `tagline`, `description` | Business name (header, footer, page title), a short sentence and a search engine description (about 150 characters). Tagline and description have a German and an English text. |
| `lang` | Language visitors see first, `de` or `en`. They can switch in the menu. |
| `url` | The live address, for example `https://www.client.de` (without a slash at the end). |
| `noindex` | `true` on a test address, **`false` when the site is live**. |
| `nav`, `routes` | Menu and page addresses. Remove pages the client doesn't need. |
| `business` | Name, address, phone, e-mail, opening hours. Also fills the footer, the Impressum, the map links and the data sent to Google. Leave a field empty to hide it. |
| `services` | The cards on the services page. |
| `privacy` | `log_days` = how long the host keeps server logs, `mail_provider` = who runs the mailbox. Must be true, ask the host. |

## 3. The look: `assets/theme.css`

Only this file needs to change for a new look. Everything is a variable:

- Colors and fonts at the top (`--color-primary`, `--color-on-primary`, ...).
- `--hero-image` and `--story-image-1` to `3`: the pictures of the start page.
- `--block-bg`, `--block-feather`: how dark and how soft the shade behind the text blocks is.
- `--bar-opacity`: how much of the picture shows through header, footer and the "any questions" band.
- `--story-overlay`, `--hero-overlay`: darkening over the pictures so text stays readable.

Pictures: save them for the web first, about 1920 px wide, as WebP or JPG, **under 300 KB** (a phone photo is several MB and makes the site slow).

| Where | What |
|---|---|
| `pictures/home/` | Start page pictures (see `theme.css`) |
| `pictures/gallery/` | Every picture in this folder appears in the gallery, sorted by file name. The file name becomes the picture description for screen readers and Google, so name them well (`shop-front.webp`). Delete the samples. |
| `favicon.ico` | Replace with the client's icon |
| `image` in `site.php` | Picture shown when a page is shared (WhatsApp, Facebook) |

## 4. Texts

- Texts of the pages are in `src/lang/de.php` and `src/lang/en.php`. Edit them there.
- The three story blocks on the start page: `story.1_title` to `story.3_text`. How many there are and on which side the text sits: `src/view/home/show.phtml`.
- Impressum and privacy policy are in `src/view/impressum/` and `src/view/privacy/` (see section 6).

## 5. Contact form and mail

The form sends an e-mail through the SMTP server of the client's mailbox. Fill in `[mail]` in `config/config.ini`:

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

Test it: `php nova mail:test you@example.com`, then check the inbox and the spam folder.

If the mail server refuses a message the visitor is told to write directly, and the message is kept in `storage/outbox/` for 30 days so it isn't lost.

**Spam protection** is built in: a hidden field, a minimum time, 5 messages per hour and visitor, 100 per hour overall. If the host runs the site behind a proxy or Cloudflare, set `proxy_header` in `config.ini`, otherwise all visitors share one limit.

## 6. Privacy and legal pages

The Impressum and the privacy policy are **templates, not legal advice**. Have them checked before going live.

- They fill themselves from `business` and `privacy` in `site.php`. Which details the Impressum must contain depends on the business (legal form, trade register, chamber, regulated profession).
- Sign a data processing agreement (Auftragsverarbeitung) with the host and the mail provider.
- The template is correct for the site as delivered: **no cookie banner is needed**. The only cookie is a session cookie for the contact form, which is technically necessary. There is no tracking, no external fonts, maps or videos, and the language choice is kept in the address (`?lang=en`), not in a cookie.
- **Anything added later changes this**: analytics, Google Maps embed, YouTube, newsletter, shop, login. Then the privacy text must be extended and usually a consent banner is needed.
- Visitor IP addresses: the website keeps only a keyed hash for 24 hours, for the message limit. To keep no IP at all set `limit_by_ip = 0` under `[security]` in `config.ini`. The web server's own logs are the host's business, ask the host for a short retention (7 days) or anonymisation and enter the number in `privacy.log_days`.

## 7. Going live

**Hosting needs:** Apache with `mod_rewrite` and `.htaccess`, PHP 7.4 or newer with `mbstring` and `openssl`, SMTP access, HTTPS.

1. Upload the project. Create `config/config.ini` on the server (not from your computer), keep `debug = 0`.
2. In `site.php`: set `url`, set `noindex` to `false`.
3. Turn on HTTPS and remove the `#` in front of the three redirect lines in `.htaccess`.
4. Make sure the folder `storage/` is writable for the web server.
5. Run `php nova check` (if the host gives no terminal, skip it and test by hand).
6. Test on the live address:
   - Send the contact form and check that the mail arrives.
   - Open `/config/config.ini`, `/storage/` and `/src/core/` in the browser: all of them must show an error, never content. If they open, the `.htaccess` is not working on this server.
   - Click through every page in German and English, on a phone and a desktop.
   - `/robots.txt` and `/sitemap.xml` exist.
7. Register the site in Google Search Console, submit the sitemap, and create a Google Business Profile with the same data.

## Not to forget

- Backups of the project and of `config/config.ini` (it is not in git).
- Check the Impressum and the privacy text again when the business changes (address, new tools, new services).
