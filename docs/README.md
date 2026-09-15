# Laika Core Documentation

`laikait/laika-core` is the core of the Laika PHP Framework. It provides:

- **HTTP:** request, response, redirects, CORS, CSRF, validation
- **Application plumbing:** configuration, resource discovery, hooks, localisation, app key
- **Templates:** Twig rendering, assets, meta tags, navigation, icons
- **Security helpers:** encryption, JWT, IDs, sanitizers, regex rules
- **Files and storage:** local/S3/JSON/Redis/Memcached storage, uploads, images, zips
- **Utilities:** dates, arbitrary-precision math, cron, shell commands
- **Data:** the options table and the activity log
- **Error handling** and the **global helper functions** templates and apps rely on

Most of it is reached through relays, static proxy classes in the `Laika\Service` namespace, for example `Url::base()` or `Request::input('email')`. Each relay forwards to a shared instance of a class in `Laika\Core\...`.

| Page | Covers |
|---|---|
| [Getting Started](01_getting-started.md) | Installation, what happens at boot, path constants |
| [Relays & the Container](02_relays.md) | How `Laika\Service\*` relays resolve, the full relay list |
| [HTTP](03_http.md) | Request, Validator, Response, Redirect, CORS, CSRF, ProxyTrust, sanitizers |
| [URL, Client & IP](04_url-client-ip.md) | Url, Page, Visitor (Client), Cookie, IP utilities |
| [Configuration & App](05_config-and-app.md) | Config, Init, app key, Local, Hook, resources, MemoryManager |
| [Templates](06_templates.md) | Template, Asset, Meta, Context, Nav, Icon |
| [Security](07_security.md) | Vault, Token (JWT), Uid, Unique, sanitizers, Regex |
| [Files & Storage](08_files-and-storage.md) | File, Directory, Upload, Image, Zip, MimeType, storage drivers |
| [Utilities](09_utilities.md) | Date, Math, Cron, shell commands, PhpMetadataParser, Queue |
| [Options & Activity Log](10_data.md) | The `options` table, the `activities` table, change logs |
| [Errors & Exceptions](11_errors.md) | The error handler and every exception class |
| [Helper Functions](12_helper-functions.md) | Every global function and the hooks registered for templates |
| [Upgrading](13_upgrading.md) | What changed in 5.1.0 and 5.1.1 |

Three components keep their own detailed READMEs next to the code:

- [IP utilities](../src/IP/README.MD): CIDR maths for IPv4 and IPv6
- [Nav builder](../src/Nav/README.MD): menus built from named routes
- [Regex rules](../src/Regex/README.MD): reusable validation patterns

## Conventions in These Pages

- **Relay** means the `Laika\Service\*` class you call statically. **Class** is the `Laika\Core\*` class that does the work.
- Method tables list signatures exactly as they appear in the source.
- **Note** boxes flag behaviour that is easy to get wrong. They describe the code as it is, not as it might be expected to work.
