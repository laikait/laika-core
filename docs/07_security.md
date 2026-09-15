# Security

## Vault

**Relay:** `Laika\Service\Vault` (`vault`). **Class:** `Laika\Core\Helper\Vault`.

Encryption, keyed hashing, password hashing, signing and random tokens. Requires `ext-openssl`.

```php
use Laika\Service\Vault;

$secret = Vault::encrypt('card-ending-4242');
Vault::decrypt($secret);                       // 'card-ending-4242'

$hash = Vault::hashPassword($password);
Vault::verifyPassword($password, $hash);       // true

$link = Vault::sign('user=42');                // tamper-proof, but readable
Vault::verify($link);                          // 'user=42', or throws
```

| Method | Does |
|---|---|
| `setCipher(string $cipher): static` | Returns a **copy** using `aes-256-gcm` (default), `aes-128-gcm` or `chacha20-poly1305` |
| `encrypt(string $text): string` | Authenticated encryption, base64-encoded |
| `decrypt(string $encryptedBase64): string` | Decrypts. Throws `RuntimeException` on invalid or tampered data. |
| `encryptArray(array $data): string` / `decryptArray(string $encrypted): array` | The same, via JSON |
| `hash(string $text, string $algo = 'sha256'): string` | HMAC keyed with the app key. Allowed: `sha256`, `sha384`, `sha512`, `sha3-256`, `sha3-384`, `sha3-512`. |
| `hashVerify(string $text, string $hash, string $algo = 'sha256'): bool` | Constant-time comparison |
| `hashPassword(string $password): string` | Argon2id |
| `verifyPassword(string $password, string $hash): bool` | `password_verify()` |
| `needsRehash(string $hash): bool` | Whether the hash predates the current Argon2id settings |
| `sign(string $text): string` | `base64("text.signature")`, HMAC-SHA256 |
| `verify(string $signed): string` | The original text, or throws `RuntimeException` |
| `token(int $length = 32): string` | A URL-safe random token from `$length` random bytes |
| `numericOtp(int $digits = 6): string` | A zero-padded numeric code, 4–12 digits |

The ciphertext records which cipher made it, so `decrypt()` handles any supported cipher, whatever the instance is set to.

> **Note:** `setCipher()` returns a configured copy and leaves the shared instance alone. Use the return value, as in `Vault::setCipher('chacha20-poly1305')->encrypt($data)`; calling it on its own changes nothing.

> **Note:** the encryption and HMAC key is derived from the [app key](05_config-and-app.md#app-key). Replacing the key, for example with `php laika secret:generate`, makes every existing ciphertext, `hash()` value, signature, [Token](#token-jwt) and CSRF token invalid. Password hashes aren't affected.

## Token (JWT)

**Relay:** `Laika\Service\Token` (`token`). **Class:** `Laika\Core\Generator\Token`.

Stateless tokens that carry a user payload.

```php
use Laika\Service\Token;

$token = Token::generate(['id' => 7, 'role' => 'staff']);

if (Token::validateToken($token)) {
    $user = Token::user();   // ['id' => 7, 'role' => 'staff']
}
```

| Method | Does |
|---|---|
| `generate(?array $user = null): string` | Issues a token carrying `$user` |
| `validateToken(?string $token): bool` | Checks the token and loads its payload; `false` for anything invalid or expired |
| `check(): bool` | Whether a validated payload is loaded |
| `user(): ?array` | The payload from the last successful `validateToken()` |
| `flush(): void` | Forgets the loaded payload |
| `refresh(string $token): ?string` | A new token with the same payload and a new expiry, or `null` |
| `setTtl(int $ttl): void` | Lifetime in seconds. Default 3600. |

The payload is signed as an HS256 JWT with the app key. `iss` and `aud` are set to `Url::host()`, but they aren't checked on validation. The JWT is then **encrypted with [Vault](#vault)**, so the token is opaque: clients and standard JWT tools can't read its claims.

> **Note:** `iat` and `exp` are taken from the moment the `Token` instance was created. Under PHP-FPM that's once per request. A long-running worker should call `Token::clearResolvedInstance()` before issuing, or every token will carry the worker's start time.

For revocable, database-backed API tokens, see the token guard in [laika-auth](https://github.com/laikait/laika-auth).

## Uid

**Relay:** `Laika\Service\Uid` (`uid`). **Class:** `Laika\Core\Generator\Uid` (static).

RFC 4122 version 4 UUIDs, valid on every database driver laika-model supports.

```php
use Laika\Core\Generator\Uid;

Uid::make();                         // "3f2b8c1e-9a4d-4c2f-8e7b-1d6a0f5c9b21"
Uid::isValid($routeParam);           // reject malformed ids before querying
Uid::stamp([['name' => 'A'], ['name' => 'B']]); // adds a 'uid' to each row lacking one
```

| Member | Does |
|---|---|
| `make(): string` | A new lowercase v4 UUID |
| `isValid(mixed $uid): bool` | Whether the value is a canonical v4 UUID |
| `stamp(array $rows): array` | Adds `uid` to one associative row, or to every row in a list |
| `LENGTH` | 36 |

`Uid::isValid()` requires the version-4 and variant bits. The Validator's `uid` rule is looser and accepts any 8-4-4-4-12 hex string.

## Unique

**Relay:** `Laika\Service\Unique` (`unique`). **Class:** `Laika\Core\Generator\Unique`.

Readable reference numbers built from date tokens and random characters.

```php
use Laika\Service\Unique;

Unique::generate('{y}{d}{c}{c}{c}{n}{n}', 'INV-');   // e.g. "INV-2615k3b07"
```

`generate(string $pattern, string $prefix = '', string $suffix = ''): string` strips whitespace from the pattern and throws `InvalidArgumentException` unless it contains at least **3** tokens.

| Token | Replaced with |
|---|---|
| `{Y}` / `{y}` | Year, 4 or 2 digits |
| `{D}` | Day name, `Mon`–`Sun` |
| `{d}` | Day of month, `01`–`31` |
| `{m}` | **Minutes**, `00`–`59` (same as `{i}`) |
| `{i}` | Minutes, `00`–`59` |
| `{G}` / `{H}` / `{h}` | Hour: `0`–`23`, `00`–`23`, `01`–`12` |
| `{I}` | Daylight-saving flag, `1` or `0` |
| `{s}` | Seconds |
| `{c}` | One random character from `a-z0-9` |
| `{n}` | One random digit |

> **Note:** there is no month token; `{m}` produces minutes. Uniqueness comes only from the `{c}` and `{n}` tokens, so add a unique index and retry on collision.

## Sanitizers

Input sanitizers belong to the request cycle. They're covered in [HTTP → Sanitizers](03_http.md#sanitizers).

## Regex

**Relay:** `Laika\Service\Regex` (`regex`). **Class:** `Laika\Core\Regex\Regex`.

Named, reusable regex rules. The constructor registers every rule class in `src/Regex/Rules`. A rule's name is its class name without `Rule`, lowercased.

| Name | Checks |
|---|---|
| `alpha`, `alphanumeric`, `numeric` | Character classes |
| `email`, `url` | Formats |
| `hasupper`, `haslower`, `hasnumeric`, `hasspecial` | Contains at least one of that kind (`hasspecial` takes a character list) |
| `minimum`, `maximum` | Length, default 6 and 100 |
| `password` | Configurable strength: `min`, `upper`, `lower`, `numeric`, `special`, `specialChars` |

```php
use Laika\Service\Regex;

Regex::validate('email', $input);                 // bool
Regex::validate('minimum', $password, 12);        // extra arguments build a configured rule
Regex::validate('password', $pw, 10, true, true, true, false);
Regex::checkRules('Abc123!');                     // ['alpha' => false, 'hasupper' => true, …]
```

| Method | Does |
|---|---|
| `validate(string $ruleName, string $input, mixed ...$params): bool` | Checks one rule, constructed with `$params` |
| `match(string $ruleName, string $input): ?array` | The regex matches, using the rule's defaults |
| `checkRules(string $input): array` | Every registered rule's result for the input |
| `addRule(Rule $rule): void` / `getRule(string $name): ?Rule` / `getRules(): array` | Registry access |

A custom rule extends `Laika\Core\Regex\Abstracts\Rule`, implements `pattern(): string`, and is registered with `addRule()`. The full guide is in [src/Regex/README.MD](../src/Regex/README.MD).

> **Note:** `PasswordRule`'s docblock says the default minimum length is 8, but the constructor default is **6**. Pass the minimum explicitly.
