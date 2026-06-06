# ts-website — User & Administrator Documentation

**Version:** 2.0 · **Language:** English  
*For the German version, see [README.de.md](README.de.md)*

---

## Table of Contents

1. [What is ts-website?](#1-what-is-ts-website)
   - 1.1 [Why this fork exists](#11-why-this-fork-exists)
2. [What You Need Before You Start](#2-what-you-need-before-you-start)
3. [Installation](#3-installation)
   - 3.1 [The tsw.phar Command-Line Tool](#31-the-tswphar-command-line-tool)
   - 3.2 [Running the Web Installer](#32-running-the-web-installer)
   - 3.3 [Setting Up Your First Admin Account](#33-setting-up-your-first-admin-account)
4. [The Website — What Visitors See](#4-the-website--what-visitors-see)
   - 4.1 [Server Viewer](#41-server-viewer)
   - 4.2 [Ban List](#42-ban-list)
   - 4.3 [Rules Page](#43-rules-page)
   - 4.4 [FAQ Page](#44-faq-page)
   - 4.5 [Group Assigner](#45-group-assigner)
   - 4.6 [Logging In](#46-logging-in)
   - 4.7 [Changing the Language](#47-changing-the-language)
5. [The Admin Panel](#5-the-admin-panel)
   - 5.1 [Accessing the Admin Panel](#51-accessing-the-admin-panel)
   - 5.2 [News](#52-news)
   - 5.3 [FAQ Management](#53-faq-management)
   - 5.4 [Rules Editor](#54-rules-editor)
   - 5.5 [Group Assigner Settings](#55-group-assigner-settings)
   - 5.6 [Imprint / Legal Notice](#56-imprint--legal-notice)
   - 5.7 [Website Configuration](#57-website-configuration)
6. [Troubleshooting](#6-troubleshooting)
7. [Security Notes](#7-security-notes)

---

## 1. What is ts-website?

**ts-website** is a website designed specifically for communities that run a **TeamSpeak 3** voice-chat server. It gives your community members a place to go on the web, where they can:

- See who is currently online on your TeamSpeak server — without having to open TeamSpeak themselves
- Read your community's rules and frequently asked questions
- Apply for server groups (e.g. "Member", "VIP", "Game: Minecraft") directly through the browser
- Read news posted by your admins
- Check who has been banned from the server and why
- Log in securely using their TeamSpeak identity (no separate password needed)

Think of it as the "home page" for your TeamSpeak community.

The software runs on a web server and connects to your TeamSpeak server in the background. It does not require any changes to TeamSpeak itself.

### 1.1 Why this fork exists

The original ts-website by [Wruczek](https://github.com/Wruczek/ts-website) is a fantastic piece of software — clean, fast, and purpose-built for TeamSpeak communities. However, the project has not received major updates in several years, and one long-awaited feature has never arrived: **a proper admin panel**. Without it, server operators had to edit database rows directly or use workarounds just to post a news item or update their rules.

This fork ([nodedropweb/ts-website](https://github.com/nodedropweb)) changes that. It adds everything that was missing:

- A **full admin panel** — manage News, FAQ, Rules, Imprint, Group Assigner, and website settings entirely through a web interface, with no database knowledge required
- A **command-line setup tool** (`tsw.phar`) — simplifies installation and maintenance without manual file editing
- **PHP 8.4 compatibility** — the original codebase had accumulated PHP deprecations that caused errors on modern servers
- **GDPR-compliant asset hosting** — all JavaScript and CSS libraries are served locally; no external CDN requests are made to third-party servers
- **Bilingual CLI** — the setup tool works in both English and German

If you already know and love the original ts-website, this fork is a drop-in extension of it. Everything that worked before still works.

---

## 2. What You Need Before You Start

Before installing ts-website, make sure the following are in place. If you are not managing the server yourself, ask your hosting provider or server administrator.

| Requirement | Details |
|---|---|
| **Web server** | Apache 2.4 or newer, with `mod_rewrite` enabled |
| **PHP** | Version 8.1 or newer (8.4 recommended) |
| **PHP extensions** | `pdo`, `pdo_mysql`, `mbstring`, `json`, `curl`, `fileinfo` |
| **Database** | MariaDB 10.5+ or MySQL 8.0+ |
| **Composer** | PHP package manager (for installing dependencies) |
| **TeamSpeak 3 server** | Version 3.10.0 or newer, with ServerQuery access |
| **TeamSpeak ServerQuery login** | A query username and password with at least read permissions |
| **SSH or terminal access** | Required to run the `tsw.phar` setup tool |

**What is ServerQuery?**  
TeamSpeak 3 has a built-in remote administration interface called "ServerQuery". It lets external programs (like ts-website) read information from and send commands to the server. You configure the ServerQuery credentials in your TeamSpeak server configuration or via the TS3 server admin tools.

---

## 3. Installation

### 3.1 The tsw.phar Command-Line Tool

`tsw.phar` is a small helper program that runs in your terminal (command line). It handles the technical steps of installation so you do not have to do them manually.

**Basic usage:**

```bash
php tsw.phar <command>
```

To see all available commands:

```bash
php tsw.phar help
```

**Language options:**

By default, `tsw.phar` uses English. To switch to German for a single command:

```bash
php tsw.phar help --lang=de
```

To make German permanent for your terminal session:

```bash
export TSW_LANG=de
```

To make it permanent across all future sessions, add that line to your `~/.bashrc` file.

**Available commands:**

| Command | What it does |
|---|---|
| `help` | Shows the list of all commands with a short description |
| `install` | Runs the full installation: downloads Composer packages and sets up required folders and permissions |
| `update` | Updates the software to the latest version after you download a new release archive |
| `clear-cache` | Clears all cached pages and data — use this whenever something looks outdated or after a config change |
| `check` | Checks whether your server meets all software requirements (PHP version, extensions, etc.) |
| `version` | Shows the currently installed version number |

**Getting help for a specific command:**

Every command accepts `--help` to show detailed usage:

```bash
php tsw.phar install --help
```

**Example — full installation:**

```bash
cd /var/www/ts-website
php tsw.phar install
```

Follow the prompts. The tool will guide you through each step.

**When to use which command:**

- **First-time setup:** run `check` first, then `install`
- **After pulling a software update:** run `update` to apply database migrations and refresh dependencies
- **Something looks wrong on the website:** try `clear-cache` — this fixes most display issues without restarting anything
- **Not sure what version you have:** run `version`

---

### 3.2 Running the Web Installer

After running `php tsw.phar install`, open your browser and go to:

```
http://your-domain.com/installer/
```

(Replace `your-domain.com` with the actual address of your server.)

The installer walks you through **7 steps**:

1. **Welcome** — Introduction and language selection. You can change the installer language using the dropdown in the top-right corner.

2. **Requirements** — The installer checks whether your server has everything it needs (PHP version, extensions, database access). Green checkmarks mean everything is fine. Red errors must be fixed before continuing.

3. **Database** — Enter your database connection details:
   - **Host**: Usually `127.0.0.1` or `localhost`
   - **Database name**: The name of the database you created for ts-website
   - **Username** and **Password**: The database user credentials
   
   The installer will create all the necessary tables automatically.

4. **TeamSpeak** — Enter your TeamSpeak server connection details:
   - **Host**: The IP address or domain of your TeamSpeak server
   - **ServerQuery port**: Usually `10011`
   - **ServerQuery username and password**: The query login credentials
   - **Virtual server port**: Usually `9987` (the port players connect to)
   
5. **Security** — Choose a secure admin password for the web interface (if applicable) and configure any security-related settings.

6. **Configuration** — Set basic site options like the website title and the brand name shown in the navigation bar.

7. **Done** — Installation is complete. The installer locks itself so it cannot be run again. Your website is now live.

> **Important:** Once the installer is done, it creates a file called `private/INSTALLER_LOCK`. This prevents anyone from running the installer again. Do not delete this file. If you ever need to reinstall, you will need to delete it manually — but be aware that reinstalling will overwrite your existing configuration.

---

### 3.3 Setting Up Your First Admin Account

After the installer finishes, you need to tell the website who the administrator is. This is done through a one-time setup page:

```
http://your-domain.com/admin/setup.php
```

**What you need: your Client Database ID**

TeamSpeak identifies every user with an internal number called the "Client Database ID" (sometimes shown as "cldbid"). This is *not* your TeamSpeak nickname — it is a permanent number assigned to your account on that specific server.

**How to find your Client Database ID in TeamSpeak:**

1. Open TeamSpeak 3
2. Connect to your server
3. Click **Extras** in the top menu
4. Click **My TeamSpeak**
5. Look for the field **"Client Database ID"** — write this number down

On the setup page, you will also see a list of users currently connected to the TeamSpeak server. You can click your own name in that list, and your ID will be filled in automatically.

Enter the number and click **Register as Admin**. The page will then lock itself (it creates a file called `private/SETUP_LOCK`). After this, `setup.php` will show a "403 Forbidden" error to everyone — this is correct and expected behavior.

> **If you need to change the admin later:** Connect to your server via SSH, navigate to `src/private/`, and delete the file named `SETUP_LOCK`. Then visit `setup.php` again to enter a new admin ID. Delete the lock file only when you actually need to make a change.

---

## 4. The Website — What Visitors See

### 4.1 Server Viewer

The **Viewer** (accessible via the "Viewer" link in the navigation bar) shows a live snapshot of who is on the TeamSpeak server right now.

- Channels are listed in the same order as in TeamSpeak
- Each channel shows the users currently inside it
- User icons and group badges are displayed (e.g. "Server Admin", "VIP")
- The viewer refreshes automatically — no need to reload the page

This is useful for community members who want to see if their friends are online before launching TeamSpeak.

### 4.2 Ban List

The **Bans** page shows a table of users who have been banned from the server. For each ban, the following information is shown:

- The player's nickname at the time of the ban
- The reason for the ban (if one was given)
- Who issued the ban
- When the ban expires (or "permanent" if it never expires)

This page is public by default.

### 4.3 Rules Page

The **Rules** page displays the community rules written by your admins. The content is managed entirely in the Admin Panel (see [Section 5.4](#54-rules-editor)).

### 4.4 FAQ Page

The **FAQ** page lists frequently asked questions and their answers. All questions and answers are managed in the Admin Panel (see [Section 5.3](#53-faq-management)).

### 4.5 Group Assigner

The **Group Assigner** allows logged-in community members to assign themselves to certain server groups — for example, groups for specific games, roles, or opt-in categories.

> **Important:** Only groups that an admin has explicitly enabled in the Group Assigner settings will appear here. Users cannot give themselves admin-level groups through this feature.

To use the Group Assigner, a visitor must first log in (see below).

### 4.6 Logging In

Visitors log in using their **TeamSpeak identity** — no separate password is required. The login process works as follows:

1. Click **Login** in the navigation bar
2. Enter your **TeamSpeak nickname** exactly as it appears in the client
3. The website sends you a **one-time confirmation code** via a TeamSpeak "poke" (a small notification that pops up in your TeamSpeak client)
4. Enter that code on the website
5. You are now logged in

> **The code never arrives?**
> - Make sure you are currently connected to the server while trying to log in
> - Check that you entered your nickname correctly (capitalization matters)
> - There is a 120-second cooldown between login attempts — wait 2 minutes and try again
> - Ensure TeamSpeak poke notifications are not disabled in your client settings

Once logged in, your username appears in the navigation bar. Click it to access the logout option.

### 4.7 Changing the Language

The website automatically detects your browser's preferred language and uses the closest available translation. To change manually:

1. Click the **language selector** in the navigation bar (shown as a globe icon with the current language name)
2. Select your preferred language from the dropdown

The website supports **24 languages**, including English, German, French, Spanish, Polish, Russian, Turkish, Chinese (Simplified), Arabic, and more.

---

## 5. The Admin Panel

### 5.1 Accessing the Admin Panel

The admin panel is available at:

```
http://your-domain.com/admin/
```

To access it, you must be logged in to the website **with the TeamSpeak account that was registered as admin** during setup. If you see a "403 Forbidden" page, either:

- You are not logged in — click Login in the navigation bar first
- You are logged in with the wrong TeamSpeak account — log out and log in with your admin account
- Your Client Database ID is not listed as an admin in the configuration

The admin panel has a navigation bar at the top with links to all management sections.

---

### 5.2 News

The **News** section lets you post announcements and updates for your community.

**To create a new news post:**

1. In the admin panel, click **News**
2. Click **New Post** (or the equivalent button)
3. Enter a **title** and the **content** of your post
4. Click **Save**

The post will immediately appear on the website's front page.

**To edit or delete a post:**

Click the edit icon (pencil) or delete icon (trash can) next to any existing post in the list.

News posts support basic HTML formatting — you can use `<b>` for bold, `<i>` for italic, and so on.

---

### 5.3 FAQ Management

The **FAQ** section lets you manage a list of frequently asked questions that appear on the public FAQ page.

**To add a new FAQ entry:**

1. Click **FAQ** in the admin navigation
2. Click **Add Question**
3. Enter the **question** and the **answer**
4. Click **Save**

**To reorder questions:** Drag and drop the entries in the list (if supported), or edit their order numbers.

**To edit or delete:** Click the corresponding icons next to each entry.

---

### 5.4 Rules Editor

The **Rules** editor lets you write and update your community's rules. The rules are displayed publicly on the Rules page.

The editor supports basic text formatting. Write your rules in the text area and click **Save**. The change takes effect immediately.

---

### 5.5 Group Assigner Settings

The **Group Assigner** admin section lets you control which TeamSpeak server groups community members can assign themselves to.

**To enable a group for self-assignment:**

1. Click **Assigner** in the admin navigation
2. You will see a list of all server groups from your TeamSpeak server
3. Toggle the switch next to a group to make it available (or unavailable) in the public Group Assigner

Only groups you explicitly enable will be visible to users on the Assigner page. Groups that you do not enable (including admin groups) will never appear there.

**Use case examples:**

- A "Game: Minecraft" group that players can add themselves to
- A "Notifications: Events" opt-in group for event announcements
- A "Language: English" self-sorting group

---

### 5.6 Imprint / Legal Notice

In many countries (especially in the EU), websites are legally required to display an "imprint" or legal notice with contact information. The **Imprint** section lets you manage this.

**To configure the imprint:**

1. Click the appropriate link in the admin panel (may be listed under configuration or as a separate menu item)
2. Navigate to **Imprint Edit**
3. **Enable imprint:** Toggle this on if you need to display a legal notice
4. **Imprint URL:** Enter the web address of your full legal notice page (this can be an external page, e.g. on your personal website)
5. **Imprint content:** Enter the text that should be shown (if the imprint is displayed inline rather than via a link)
6. Click **Save**

When the imprint is enabled, a link labeled **"Imprint"** (or the translated equivalent in the visitor's language) will appear in the website footer.

---

### 5.7 Website Configuration

The **Config** section contains general settings for your website.

Common settings include:

| Setting | Description |
|---|---|
| **Website Title** | The name shown in the browser tab and page title |
| **Navigation Brand** | The name shown in the top-left corner of the navigation bar |
| **TeamSpeak Server Address** | The address players use to connect (shown on the viewer page) |
| **News enabled** | Show or hide the news section |
| **Bans enabled** | Show or hide the public ban list |
| **Assigner enabled** | Show or hide the group assigner feature |
| **Cookie notice** | Enable or disable the GDPR cookie consent banner |

After changing any setting, click **Save**. Most changes take effect immediately; if something does not update, try clearing the cache with `php tsw.phar clear-cache`.

---

## 6. Troubleshooting

### "I see raw text like LOGIN_CONFIRMATION_CODE instead of a real message"

This means a translation key is missing for your chosen language. The website could not find the translated text and showed the internal key name instead.

**Solution:** This is a software issue. Check whether your language's translation file is complete. If you are using a language that was recently added or if you customized the language files, compare with the English (`en/frontend.po`) file and add the missing entries.

### "The login poke never arrives"

- Make sure you are **connected to the TeamSpeak server** while trying to log in
- Check your TeamSpeak nickname is entered exactly correctly (including capital letters)
- Wait at least **2 minutes** between attempts — there is a built-in cooldown to prevent spam
- Check that TeamSpeak **poke notifications** are enabled in your client (right-click your server name → Edit → Notifications)

### "The imprint / rules / news I saved is not showing up"

- Clear the website cache: `php tsw.phar clear-cache`
- Wait a few seconds and reload the page
- If the problem persists, check that your database connection is working correctly

### "The admin panel says 403 Forbidden"

- You are either not logged in, or logged in with the wrong TeamSpeak account
- Log in with the TeamSpeak account whose Client Database ID was entered during setup
- If you need to change the admin ID, delete `src/private/SETUP_LOCK` and visit `/admin/setup.php` again

### "setup.php shows Forbidden even though I am the admin"

- This is correct. After the first admin is registered, `setup.php` locks itself permanently
- To re-enable it, connect via SSH and delete the file `src/private/SETUP_LOCK`
- Only do this if you genuinely need to re-register the admin — the file exists to protect you

### "The website shows outdated data / cached content"

Run the following in your terminal:

```bash
php tsw.phar clear-cache
```

This removes all cached pages and data. The next page load will fetch fresh data from the TeamSpeak server and database.

### "The server viewer shows nothing / all channels are empty"

- Your TeamSpeak server may be offline or unreachable from the web server
- Check that the ServerQuery credentials in your configuration are correct
- Verify that the TeamSpeak server's ServerQuery port (default: `10011`) is reachable from your web server
- Check your web server's error log for connection errors

### "I get a 500 Internal Server Error"

- Enable PHP error display temporarily and reload the page to see the actual error message
- Check the Apache error log: usually found at `/var/log/apache2/error.log`
- Common causes: missing Composer packages (run `php tsw.phar install` again), incorrect file permissions, or a database connection failure

---

## 7. Security Notes

- **Never share your ServerQuery credentials.** Anyone with ServerQuery access can control your TeamSpeak server.
- **The `src/private/` directory must not be web-accessible.** It contains your configuration, database credentials, and lock files. If your web server is configured correctly (using the provided `.htaccess` rules), this directory is already protected.
- **Keep the `INSTALLER_LOCK` and `SETUP_LOCK` files in place.** These prevent the installer and setup pages from being run again. Only delete them when you specifically need to re-run those steps.
- **Keep your software up to date.** Run `php tsw.phar update` periodically to apply security fixes.
- **All assets (JavaScript, CSS) are served locally** — no data is sent to external CDNs. This is in compliance with GDPR.

---

*Documentation for ts-website v3.0. Original software by [Wruczek](https://github.com/Wruczek/ts-website), fork and extensions by [nodedropweb](https://github.com/nodedropweb).*
