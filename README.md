# My Life Stuff 🗂️

A small, self-hosted personal website for cataloguing your things — phones,
computers, and whatever else — organised into **categories** and **items**,
with images, spec tables, and descriptions. You edit everything **in the
browser** through a password-protected admin panel. No database required.

Built with plain **PHP + HTML + CSS + JavaScript**. Data is stored as JSON
files; images live in an `uploads/` folder. It runs on virtually any
PHP-capable host — including a standard **Plesk** server.

---

## Features

- 🏠 Public site: home → categories → items, with an image gallery and a
  specifications table per item.
- 🔐 Login-protected admin panel (`/admin`) to add / edit / delete categories
  and items and upload images — all from the website itself.
- 🗃️ Flat-file JSON storage (no MySQL to configure).
- 📱 Responsive, works on phones; automatic light/dark theme.
- 🛡️ CSRF protection, hashed password, protected data folder, safe image
  uploads.

---

## Requirements

- PHP **7.4+** (8.x recommended) with the `fileinfo` extension (on by default).
- Apache with `.htaccess` support (the default on Plesk). See the note below
  for nginx.

---

## Local quick start (optional, for testing)

From inside the project folder:

```bash
php -S localhost:8000
```

Then open <http://localhost:8000>. The first time you visit `/admin`, you'll
be sent to a **setup page** to create your admin username and password.

---

## Deploying on your Plesk server

### 1. Point your domain / subdomain at Plesk (DNS)

Decide which address the site lives at, e.g. `stuff.yourdomain.com` or just
`yourdomain.com`.

- **If your domain's DNS is managed by Plesk:** in Plesk go to
  **Websites & Domains → add a Domain or Subdomain**, enter the name, and
  Plesk creates the DNS records for you.
- **If DNS is managed elsewhere** (registrar, Cloudflare, etc.): create a
  record pointing at your Plesk server's IP:
  - An **A record** → your server's IPv4 (e.g. `stuff` → `203.0.113.10`), or
  - A **CNAME** → your server's hostname, for a subdomain.

  DNS changes can take a little while (minutes to a few hours) to propagate.

### 2. Create the site in Plesk

1. **Websites & Domains → Add Domain / Add Subdomain**, using the name you
   set up above.
2. Set the **document root**. Either:
   - point it at the folder where you'll put these files, or
   - use the default `httpdocs` and place the files directly inside it.
3. Set the **PHP version** to 7.4+ (**Websites & Domains → PHP Settings**).

### 3. Upload the files

Use whichever you prefer:

- **Git (recommended):** in Plesk, **Git → add repository**, point it at this
  repo/branch, and set the deployment path to your document root. Pull to
  deploy updates later.
- **File Manager / FTP:** upload the whole project into the document root.

> **Your content survives deploys.** Your live data (`data/*.json`), your
> login (`data/auth.json`), and your uploaded images (`uploads/`) are all
> **git-ignored**, so pulling updates never overwrites them. The starter
> content ships separately in `data/defaults/` and is copied into your live
> data **once**, the first time the site runs. See
> [How your data is stored](#how-your-data-is-stored) below.

### 4. Make `data/` and `uploads/` writable

The app needs to write JSON and save images. In Plesk **File Manager**, set
permissions so the web server (usually the domain's system user) can write to:

- `data/`
- `uploads/`

`755` for the folders is typically enough on Plesk (files owned by the domain
user). If saving fails, `775` also works. Avoid `777`.

### 5. Enable HTTPS

In Plesk, **SSL/TLS Certificates → install a free Let's Encrypt certificate**
and turn on **redirect from HTTP to HTTPS**. (Logging in over plain HTTP would
send your password unencrypted.)

### 6. First run — create your admin account

Visit `https://your-site/admin`. You'll be taken to the one-time **setup**
page. Pick a username and a password (min 8 characters). That writes
`data/auth.json`, logs you in, and from then on `/admin` shows the login page.

You're done — start adding categories and items!

---

## Using it

- **Categories** group your stuff (Phones, Computers, …). Two example
  categories ship in `data/categories.json`; edit or delete them from the
  dashboard.
- **Items** belong to a category and have a title, optional date, description,
  a list of **spec** key/value rows, and any number of **images**. The first
  image is used as the cover.
- Everything is edited from **/admin**. Deleting an item also deletes its
  uploaded images; deleting a category leaves its items in place (they just
  become uncategorised).

---

## How your data is stored

Your content is kept in plain JSON files, split into two groups so that
deploying new code never wipes what you've entered:

| Location | Tracked in git? | What it is |
|---|---|---|
| `data/defaults/*.json` | ✅ yes | **Starter content** shipped with the code. Read-only in practice. |
| `data/*.json` | ❌ no (git-ignored) | **Your live content** — written by the app, owned by you. |
| `data/auth.json` | ❌ no | Your login (bcrypt hash). |
| `uploads/` | ❌ no | Your uploaded images. |

The first time the site runs, each live file (e.g. `data/categories.json`) is
created by copying its default (`data/defaults/categories.json`). From then on
the app only ever reads and writes the **live** file. Because live files are
git-ignored, a `git pull` / redeploy updates the code and the defaults but
**leaves your live content untouched**.

> **Editing the starter content:** changing `data/defaults/*.json` only affects
> a *brand-new* install (one with no live files yet). To change what's already
> on your live site, edit through `/admin` — that's your real data.

> **Starting over:** to wipe your live content back to the starter set, delete
> the live files on the server (`data/categories.json`, `data/entries.json`,
> `data/pages.json`) and reload — they'll be re-seeded from the defaults.

### Advanced: surviving a "clean checkout" deploy

The setup above protects you against normal `git pull` / `git reset` deploys
(the Plesk default), where git-ignored files are left alone. If your deploy is
configured to do a **fresh clone** or runs `git clean` (which removes *all*
untracked files, including your data and uploads), point the app at a data
folder **outside** the deployment directory: set the environment variable
`WTLA_DATA_DIR` (Plesk: **Websites & Domains → PHP Settings**, or the domain's
environment variables) to something like
`/var/www/vhosts/yourdomain/private/wtla-data`. The app will store all live
JSON there instead of in `./data`. (Uploaded images always stay in `uploads/`
because the browser needs to fetch them, so a `git clean` deploy still isn't
recommended.)

---

## Resetting your password

If you get locked out, delete `data/auth.json` on the server (File Manager or
FTP), then visit `/admin` again to run setup afresh.

---

## Using nginx instead of Apache

The security rules live in `.htaccess` files, which nginx ignores. If your
Plesk setup serves this site with **nginx only**, add equivalent rules under
**Websites & Domains → Apache & nginx Settings → Additional nginx directives**:

```nginx
# Block the data folder
location ^~ /data/ { deny all; return 403; }

# Don't execute anything in uploads; only serve images
location ^~ /uploads/ {
    location ~* \.(jpe?g|png|gif|webp)$ { }
    location ~ \.php$ { deny all; return 403; }
}
```

(Most Plesk installs use Apache in front of, or instead of, nginx, in which
case the bundled `.htaccess` files already handle this.)

---

## Project structure

```
.
├── index.php            # Home — lists categories
├── category.php         # One category — lists its items
├── entry.php            # One item — gallery + specs
├── page.php             # A free-form page (About Me, Resources, …)
├── includes/            # config, shared functions, header/footer
├── admin/               # login, setup, dashboard, editors, delete handler
├── assets/css/style.css # all styling (near-black + blue, light/dark aware)
├── assets/js/           # gallery, scroll-reveal + nav, admin editor scripts
├── data/
│   ├── defaults/        # starter content (tracked in git)
│   └── *.json           # your live content (git-ignored, created on first run)
└── uploads/             # uploaded images (git-ignored)
```

---

## Security notes

- Password is stored as a bcrypt hash in `data/auth.json` (never in git).
- All admin actions require login and a CSRF token.
- The `data/` folder is blocked from web access; `uploads/` cannot execute
  PHP and only serves image types.
- Always run behind HTTPS.
