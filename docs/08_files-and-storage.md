# Files & Storage

## File

**Relay:** `Laika\Service\File` (`file`). **Class:** `Laika\Core\Helper\File`.

| Method | Returns / does |
|---|---|
| `exists(string $file): bool` | `is_file()` |
| `readable(string $file): bool` / `writable(string $file): bool` | Permission checks |
| `size(string $file): int\|false` | Size in bytes, or `false` if missing |
| `info(string $file): array` | `pathinfo()` |
| `mime(string $file): string\|false` | MIME type detected from the content (`finfo`) |
| `extension()` / `name()` / `base()` / `path(string $file): string` | Extension, name without extension, basename, directory |
| `read(string $file): string\|false` | The contents |
| `write(string $content, string $file, int $flags = 0): bool` | Writes the file, **creating missing directories** |
| `append(string $str, string $file): bool` | Appends with `LOCK_EX`, creating the file if needed |
| `pop(string $file): bool` | Deletes; `false` if missing |
| `move(string $from, string $to): bool` / `copy(string $from, string $to): bool` | Moves or copies |
| `touch(string $file, ?int $mtime = null, ?int $atime = null): bool` | Sets timestamps |
| `require(string $file, bool $require_once = false): mixed` | Includes the file; throws `RuntimeException` if missing |
| `download(string $file, ?string $as = null): void` | Streams the file as an attachment |

`download()` sends `Content-Type`, `Content-Disposition` (with a sanitized name, also RFC 5987-encoded), `Content-Length` and `nosniff`, then `readfile()`. It doesn't exit, so return from the controller without printing anything else.

## Directory

**Relay:** `Laika\Service\Directory` (`directory`). **Class:** `Laika\Core\Helper\Directory`.

| Method | Returns / does |
|---|---|
| `folders(string $path): array` | Immediate sub-directories, sorted absolute paths |
| `files(string $path, string\|array $ext = '*'): array` | Immediate files, optionally filtered by extension (`'php'`, `['jpg','png']`) |
| `scan(string $path, bool $includeDirs = true, string\|array $ext = '*'): array` | Recursive listing; parents come before their children |
| `exists(string $path): bool` | `is_dir()` |
| `make(string $path, int $permissions = 0755, bool $recursive = true): bool` | Creates the directory. Safe when another process creates it at the same moment. |
| `empty(string $path): bool` | Deletes everything inside, keeping the directory |
| `pop(string $path): bool` | Deletes the directory and its contents |

Extension filters ignore case and a leading dot. Invalid paths throw `RuntimeException`. Symlinks and Windows junctions are removed as links: `pop()` and `empty()` never delete what a link points to.

## Upload

**Relay:** `Laika\Service\Upload` (`upload`, a new instance per use). **Class:** `Laika\Core\Helper\Upload`.

```php
use Laika\Service\Upload;

$path = Upload::init($_FILES['avatar'])->single(APP_PATH . '/uploads/avatars', 'user-42', [
    'maxsize'      => 2 * 1024 * 1024,
    'extensions'   => ['jpg', 'png', 'webp'],
    'mimetypes'    => ['image/jpeg', 'image/png', 'image/webp'],
    'processimage' => true,
]);
// "/…/uploads/avatars/user-42.png", or false
```

| Method | Returns |
|---|---|
| `init(array $fields): static` | Takes a `$_FILES` entry. Required before `single()` or `multiple()`. |
| `single(string $directory, ?string $name = null, array $options = []): string\|false` | The stored path, or `false` for any failure |
| `multiple(string $destinationDir, array $options = []): array` | `['success' => [name => ['slug', 'path']], 'errors' => [name => message]]` |

| Option | Meaning |
|---|---|
| `maxsize` | Maximum bytes |
| `extensions` | An allow-list that **replaces** the default one |
| `mimetypes` | Allowed MIME types, checked against the file content |
| `processimage` | Re-encode images at quality 85, which strips anything smuggled in after the image data |
| `basename` | `multiple()` only: name files `{basename}_{index}` |

Checks that always apply:
- **Blocked extensions:** `php`, `php3`–`php8`, `phtml`, `phps`, `phar`, `htaccess`, `htpasswd`, `cgi`, `pl`, `py`, `sh`, `bash`, `exe`, `so` and `dll` are refused whatever `extensions` says.
- **Default allow-list,** used when `extensions` isn't given: common images, office documents, archives, audio and video. `svg` is left out on purpose, because served inline it runs script.
- **File names:** `single()` names the file with the slugified `$name` (or the original name) plus the original extension, and **overwrites** a file of the same name. `multiple()` prefixes names with a timestamp.

> **Warning (5.1.x):** `multiple()` records a validation failure in `errors` but **still moves the file**. A blocked or disallowed file sent through `multiple()` ends up in the directory. Until this is fixed, call `single()` per file, or check `errors` and delete what was written.

## Image

**Relay:** `Laika\Service\Image` (`image`, a new instance per use). **Class:** `Laika\Core\Helper\Image`. Requires GD.

```php
use Laika\Service\Image;

Image::path($upload)->thumbnail(300, 300, 'cover')->convertTo('webp')->save($thumb, 80);
```

`path()` must come first. Every other method throws `RuntimeException` until an image is loaded.

| Method | Does |
|---|---|
| `path(string $path): static` | Loads JPEG, PNG, GIF or WebP, plus BMP and AVIF where GD supports them |
| `resize(int $width, int $height, bool $keepAspect = true): static` | Fits inside the box when `$keepAspect` is true |
| `crop(int $x, int $y, int $width, int $height): static` | Crops a region |
| `thumbnail(int $width, int $height, string $mode = 'fit'): static` | `fit` shrinks to fit; `cover` fills the box and centre-crops |
| `watermark(string $text, int $gdfont = 5, ?array $rgb = null, int $x = 10, int $y = 20): static` | Text watermark with a built-in GD font (1–5) |
| `watermarkImage(string $logoPath, int $x = 0, int $y = 0, int $opacity = 100): static` | Image watermark |
| `rotate(float\|int $angle): static` | Clockwise |
| `flipHorizontal()` / `flipVertical()` / `grayscale(): static` | Transforms |
| `convertTo(string $format): static` | Output format for later `save()`, `show()` and `toBase64()` calls |
| `save(string $path, ?int $quality = null): bool` | Writes the file. Quality 0–100, default 85; PNG maps it to a zlib level. |
| `show(): void` | Sends it to the browser with its content type, then releases it |
| `toBase64(): string` | A `data:` URI, then releases it |
| `info(): array` | `width`, `height`, `mime` |
| `destroy(): void` | Frees the GD image |
| `unlink(): bool` | Deletes the **source** file |

`convertTo()` only changes the encoder, so give `save()` a path whose extension matches.

## Zip

**Class:** `Laika\Core\Helper\Zip`. Requires `ext-zip`.

```php
use Laika\Core\Helper\Zip;

(new Zip(APP_PATH . '/lf-storage/backup.zip'))->create(APP_PATH . '/uploads');
(new Zip($archive))->extract(APP_PATH . '/lf-storage/import');
```

| Method | Does |
|---|---|
| `__construct(string $path)` | The archive path |
| `create(string\|array $files): bool` | Archives a directory (keeping relative paths) or a list of files (flattened to their basenames; duplicate names throw) |
| `extract(string $to, int $maxBytes = 536870912, int $maxEntries = 10000): bool` | Extracts, refusing absolute or `..` entries, archives over 512 MB uncompressed, and archives with more than 10 000 entries |

Problems throw `PathException`. A missing extension throws `ExtensionException`.

## MimeType

**Relay:** `Laika\Service\MimeType` (`mime`). **Class:** `Laika\Core\Helper\MimeType` (static).

| Method | Returns |
|---|---|
| `fromExtension(string $extension): string` | The type, or `application/octet-stream` |
| `fromFile(string $filename): string` | The same, from the file name's extension (the content isn't read) |
| `fromContent(string $content): string` | Detected from the bytes (`finfo`) |
| `all(): array` | Extension → type map |
| `register(string $extension, string $mimeType): void` | Adds or overrides a mapping |

Registering a type only teaches the front controller its `Content-Type`. Whether files of that type may be served is decided by `lf-config/assets.php`.

## Storage Drivers

The storage classes aren't relays; construct them where you need them.

### LocalStorage

`Laika\Core\Storage\LocalStorage(?string $root = null, ?string $publicBaseUrl = null)`

```php
use Laika\Core\Storage\LocalStorage;

$disk = new LocalStorage(APP_PATH . '/uploads', app_host() . 'uploads');
$url  = $disk->upload($_FILES['doc'], 'documents');  // …/uploads/documents/report-6650f1c2a3b4d-1718000000.pdf
$disk->delete('documents/' . $disk->name());
```

| Method | Returns |
|---|---|
| `upload(array\|string $file, ?string $destination = null): string` | The public URL. Takes a `$_FILES` entry or a local path. The folder defaults to `Y/m/d`. |
| `delete(string $file): bool` | Deletes by path relative to the root |
| `url(string $file): string` | The public URL of a stored file |
| `root()` / `name()` / `path()` / `mime(): string` | The root, plus the last upload's name, absolute path and MIME type |

Stored names get a `-uniqid-timestamp` suffix, so an upload never overwrites an existing file. Paths containing `..` are refused.

> **Note:** the default root is `lf-storage/files`. The front controller never serves anything under `lf-*`, so the URLs `upload()` returns for that root give a 404. Point the root at a public directory such as `uploads/`, or serve the files through a controller.

### S3Storage

`Laika\Core\Storage\S3Storage(array $overrides = [], ?string $publicBaseUrl = null)` has the same methods as `LocalStorage`. It reads `lf-config/s3.php`; a non-empty value in `$overrides` wins:

| Key | Default | Meaning |
|---|---|---|
| `region`, `key`, `secret` | required | AWS credentials and region |
| `bucket` | required | Target bucket |
| `version` | `latest` | SDK API version |
| `endpoint` | — | S3-compatible services (MinIO, R2, Spaces) |
| `path_style` | `true` when an endpoint is set | Path-style addressing |
| `root` | `lf-storage` | Key prefix for every object |
| `acl` | `public-read` | Canned ACL for uploads |
| `url` | — | CDN or custom domain. Otherwise `https://{bucket}.s3.{region}.amazonaws.com/`. |

> **Note:** uploads are **public** by default (`public-read`). Set `'acl' => 'private'` for private files. Buckets with ACLs disabled (Object Ownership "bucket owner enforced") need an ACL they accept.

### JsonStorage

`Laika\Core\Storage\JsonStorage(?string $path = null)` stores documents in `lf-storage/json/{name}.json` by default.

| Method | Returns / does |
|---|---|
| `set(string $name, array $array, bool $merge = true): bool` | Merges into the document, or replaces it when `$merge` is false |
| `get(string $name, ?string $key = null): mixed` | The document or one key; `null` if missing or invalid |
| `pop(string $name, string $key): bool` | Removes a key |
| `mutate(string $name, callable $fn): mixed` | Atomic read-modify-write under one lock |

`set()` only locks the write, so two concurrent `set()` calls can lose an update. Use `mutate()` when the new contents depend on the old ones. The callback receives the current array and returns `['records' => $new, 'return' => $value]`; leave out `records` to skip the write.

```php
$next = $store->mutate('counters', fn (array $r) => [
    'records' => ['orders' => ($r['orders'] ?? 0) + 1] + $r,
    'return'  => ($r['orders'] ?? 0) + 1,
]);
```

### RedisStorage and MemcachedStorage

`new RedisStorage()` and `new MemcachedStorage()` share one interface:

| Method | Does |
|---|---|
| `set(string $key, mixed $value): bool` | Stores a value (Redis serializes it) |
| `get(string $key): mixed` | The value, or `null` when missing |
| `pop(string $key): bool` | Deletes |
| `expire(int $seconds): void` | TTL for later `set()` calls; 0 means no expiry |
| `prefix(string $prefix): void` | Key prefix; a trailing `:` is added |

Both read their `lf-config` file:

| File | Keys |
|---|---|
| `lf-config/redis.php` | `host` (127.0.0.1), `port` (6379), `timeout` / `read_timeout` (2.5), `password` (`auth` is a deprecated alias), `username` (Redis 6 ACL), `database` (0), `prefix` (`laika`), `expire` (0) |
| `lf-config/memcached.php` | `host` (127.0.0.1), `port` (11211), `username` / `password` (SASL, which switches to the binary protocol), `prefix` (`laika`), `expire` (0) |

Memcached treats a TTL over 30 days as a timestamp, and `set()` converts it for you. A dead Memcached server isn't reported at construction: it shows up as `false` from `set()` and `null` from `get()`.

### Connection Factories

`Laika\Core\Storage\Connection\RedisConnection::make(array $overrides = []): Redis`, `MemcachedConnection::make(): Memcached` and `S3Connection::make(): S3Client` build bare clients from the same config files. The storage classes, the session drivers and the queue drivers all use them. A missing extension or package throws `ExtensionException`, and a Redis connect or auth failure does too.
