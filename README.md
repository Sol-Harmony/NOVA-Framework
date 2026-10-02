# NOVA, a minimal Framework for PHP

A small PHP framework without external libraries. It gives web developers a simple, no-fuss starting point for new websites, without relying on big, heavy frameworks.

- Routing from the address to a controller and a view, with pretty URLs
- Controllers, views (plain PHP templates) and simple models
- Config in two files, translations, helper functions for the views
- Security by default: CSRF protection, safe sessions, security headers, rate limiting, input validation
- SMTP mail without a library
- A terminal tool (`php nova`) to check the setup and test the mail

The repository also contains a ready website template (start page, services, gallery, contact form, Impressum, privacy policy) built on the framework. How to turn it into a client website is explained in [SETUP.md](SETUP.md).

## Requirements

PHP 7.4 or newer (`mbstring`, `session`, `json`, `filter`; `openssl` for encrypted mail, `pdo_mysql` only if a model is used) and Apache with `mod_rewrite`.

## Structure

```
index.php            entry point: security headers, then the Router
nova                 terminal tool
.htaccess            pretty URLs, blocks direct access to config, source and storage
config/              site.php (content, in git) and config.ini (secrets, not in git)
assets/              theme.css (colors, fonts, shapes) and style.css (layout)
src/
  controller/        one class per page
  view/              templates: <page>/show.phtml, plus head, navbar, footer
  model/             database models
  core/              the framework: Router, Controller, Request, Config, Lang, Session, ...
  lang/              translations (de.php, en.php)
  scripts/           javascript (no inline scripts, see CSP)
storage/             logs, sessions, rate limit files (not reachable from the web)
```

## How a request works

`/product/show?id=5` runs the method `showAction()` of the class `Product` in `src/controller/Product.class.php`, then renders `src/view/product/show.phtml` inside the layout (head, navbar, footer). Without a method name `showAction` is used, the address `/` runs `Home`. Classes are loaded automatically from `src/model`, `src/controller` and `src/core`.

Pretty URLs are set in `config/site.php`: `'routes' => ['kontakt' => 'contact']` makes `/kontakt` open the `Contact` controller. Use `route('contact')` in views to get the right address.

```php
class About extends Controller
{
    public function showAction()
    {
        $this->title = 'About us';                 // <title> of the page
        $this->data['team'] = ['Anna', 'Ben'];     // available in the view as $this->data
    }
}
```

A new page is two files: the controller `src/controller/About.class.php` and the view `src/view/about/show.phtml` (folder in lowercase). It opens at `/about`. To use another address or to put it into the menu, add it to `routes` and `nav` in `config/site.php`.

What a controller can do:

| | |
|---|---|
| `$this->data`, `$this->title`, `$this->description`, `$this->noindex` | values for the view and the page head |
| `$this->myrequest->getText('name')`, `getParam()`, `getJson()`, `getPath()`, `getIp()` | read the request |
| `$this->redirect('/path')` | redirect (only to paths of the site) |
| `$this->abort(404)` | stop with an error page |
| `$this->asJson([...])`, `$this->asText($body, $type)` | answer with JSON or plain text / XML |
| `$this->setView('x/y')`, `$this->localizedView('page/show')` | other view, or `show.de.phtml` / `show.en.phtml` for the current language |
| `$this->requirePost()` | only accept form submits |
| `protected $allowedMethods`, `protected $csrf` | allowed HTTP methods (default GET, HEAD, POST), CSRF check on or off |

## Config

- `config/site.php` returns an array with the content of the site. Read it with `Config::get('site.business.phone')`.
- `config/config.ini` holds server settings and secrets, sections are available by name: `Config::get('mail.host')`, `Config::bool('app.debug')`. Copy `config/config.example.ini` to start.
- `Config::text('site.tagline')` returns a text that may be given per language (`['de' => '...', 'en' => '...']`) in the visitor's language.
- Debug is off unless `config.ini` says `debug = 1`. Errors go to `storage/logs/php-error.log`.

## Languages

Texts are in `src/lang/<code>.php` (`de`, `en`). Use `t('key')` or `t('key', ['name' => 'Anna'])` for `:name` placeholders in a view. Missing texts fall back to English, then to the key itself. The language is the `lang` setting in `site.php`, a visitor can choose another one with `?lang=en` in the address. Nothing is stored for this, so no cookie is needed. `Lang::withLang($url)` and `route()` keep the choice in links.

## Helpers for views

`e($text)` escapes for HTML (use it for everything printed), `t()`, `route()`, `asset('/assets/style.css')` (adds the change time so browsers reload changed files), `csrf_field()`, `biz('phone')` (value from the `business` block).

## Security

- **CSRF:** every POST needs the token. Put `<?= csrf_field() ?>` into each form, the Router checks it. For JavaScript send it in the header `X-CSRF-Token` (`Csrf::token()`).
- **Sessions:** start only when a page uses them, so other pages set no cookie. `HttpOnly`, `SameSite=Lax`, `Secure` and `__Host-` prefix on https, own folder in `storage/`. `Session::get/set/flash/getFlash`, `Session::regenerate()` after a login, `Session::destroy()` on logout.
- **Headers:** Content-Security-Policy (only files from the own site, no inline scripts), `X-Frame-Options`, `nosniff`, `Referrer-Policy`, `Permissions-Policy`, HSTS on https. Extend `Security::csp()` if something external is needed.
- **Rate limit:** `RateLimit::hit('key', $max, $seconds)` returns false when the limit is reached. Only a keyed hash of the key is stored, files are deleted after 24 hours.
- **Validation:** `Validate` returns an error text or `null`: `ValidateText($input, $name, $min, $max)`, `ValidateEmail($input)`, `ValidatePattern($input, $name, $regex, $max)`.
- **Output:** everything printed goes through `e()`, error pages never show details unless debug is on.
- **Folders:** `config/`, `storage/`, `src/` (except `src/scripts`), `nova`, `*.md`, `*.ini`, `*.log` and dotfiles can't be requested from the web (`.htaccess`).
- Behind a proxy or CDN set `proxy_header` under `[security]` in `config.ini`, and `trust_proxy = 1` if the proxy terminates https.

## Mail

`Mail::send($to, $subject, $body, $replyTo, $replyToName)` sends a plain text mail through the SMTP server from `[mail]` in `config.ini` (STARTTLS or SSL, certificate checked), or PHP's `mail()` if no host is set. It returns `true` or `false` (reason in `Mail::lastError()`). Header injection is not possible: addresses are checked and the visitor's address only goes into `Reply-To`. `Mail::saveCopy()` keeps a message in `storage/outbox/` when sending failed.

## Terminal

```
php nova help
php nova check                        # checks config, folders, mail and placeholders before going live
php nova mail:test you@example.com    # sends a test mail with the settings from config.ini
```

Run `nova` from the project folder.

## Database

Only needed when a page uses a model. Fill the `[database]` section in `config/config.ini`. A model is a class in `src/model/` that extends `BaseModel` and names its table:

```php
class ProductModel extends BaseModel
{
    protected $table = 'product';
}
```

`BaseModel` offers `find`, `all`, `where` and `create`, rows (`RowModel`) offer `save` and `delete`. Queries use prepared statements. Right now only MySQL is supported. Remember that a page that lists a table shows it to every visitor, so protect it if the data is private.

---

If you have ideas or want to help make the NOVA framework better, contributions are welcome.

This project is licensed under the **GNU LGPL v2.1**.
You may use, modify, and distribute it under the terms of this license.
Modifications must remain open-source under the same license.

Copyright (C) 2026 Hamzah Mansor
