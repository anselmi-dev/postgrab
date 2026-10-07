# Brief — Micro app: descargador de media de X (Twitter)

Oct 5, 2026 · @Anselmi

## Resumen y objetivo

Una web de una sola pantalla donde el usuario pega el link de un post de X, ve el texto y los medios (fotos, videos, GIFs) y elige qué descargar. Se construye en Laravel, con cuenta opcional para guardar el historial de enlaces, y se monetiza con publicidad display.

- **Objetivo de producto:** pegar link → ver preview → descargar en menos de 10 segundos.
- **Objetivo de negocio:** tráfico orgánico por SEO ("descargar video de twitter") convertido en impresiones publicitarias.
- **Público:** usuarios en mobile que quieren guardar un video o imagen para compartir por WhatsApp o guardar en la galería.

## Alcance del MVP

El MVP cubre el flujo link → preview → descarga, una cuenta opcional con historial y archivos temporales que se borran a las 24 h.

1. El usuario pega la URL (x.com, twitter.com, fxtwitter, vxtwitter, con o sin parámetros).
2. El backend extrae el ID numérico del post y consulta los datos.
3. Se muestra una tarjeta con autor, avatar, fecha, texto y cada medio con su miniatura, numerado (1 de 4, 2 de 4…) cuando el post tiene varios.
4. Por cada video se listan las calidades disponibles (ej. 1280x720, 640x360) con su peso aproximado.
5. El usuario baja cada archivo por separado o todos juntos en un ZIP; cada archivo lleva nombre legible (`usuario_idpost_1.mp4`).

| Incluido en MVP | Fuera del MVP |
| --- | --- |
| Fotos, videos y GIFs (como MP4), incluidos posts con varios archivos: uno a uno o todos en ZIP | Cuentas privadas o protegidas |
| Varias calidades por video | Hilos completos (varios posts encadenados) |
| Copiar texto del post | Conversión a MP3 o GIF real |
| Cuenta opcional con historial de enlaces | Favoritos, carpetas y etiquetas |
| Archivos temporales borrados a las 24 h por cron | Almacenamiento permanente de archivos |
| Responsive, mobile first, en español e inglés | Otras redes (Instagram, TikTok) |
| Manejo de errores claro (post borrado, sin media, link inválido) | Extensión de navegador o app nativa |

## Diseño y layout

La web pública (la que usa el visitante) sigue la captura de referencia; el panel de Filament tiene su propio tema, en la pestaña Tema del panel. La web: fondo blanco con mucho aire, tarjetas gris muy claro con esquinas grandes, títulos grandes en dos tonos (negro + gris) y un único color de acento lima para iconos y estados positivos. Todo con Tailwind, sin librerías de UI extra.

&#91;image: Referencia de layout\]

**Cómo se traduce cada bloque de la referencia a la app**

| Bloque de la referencia | En la app |
| --- | --- |
| Pill "Content Section" | Pill "Descargador de X" sobre el título |
| Título en dos tonos | "Descargá videos y fotos" en negro + "de cualquier post de X" en gris |
| Rombos de la esquina (el negro destacado) | Selector de idioma ES/EN y botón de cuenta; el negro marca el activo |
| Tarjeta ancha (2/3) | Input grande redondeado + botón negro "Buscar"; debajo, el preview del post con la grilla de medios |
| Tarjeta angosta (1/3) | Acciones: descargar todos en ZIP, seleccionados, y "Te quedan 3 descargas esta hora" |
| Fila de 3 features con icono en círculo lima | "Máxima calidad", "Varios archivos en un ZIP", "Historial con tu cuenta" |

**Tokens de diseño (valores aproximados tomados de la captura)**

| Token | Valor | Uso |
| --- | --- | --- |
| `ink` | #0A0A0A | Títulos, botones primarios |
| `muted` | #9CA3AF | Segunda parte del título, textos secundarios |
| `surface` | #F5F5F5 | Fondo de tarjetas |
| `accent` | #E4F86B | Círculos de iconos, badges positivos, foco del input |
| `rounded-card` | 1.5rem | Tarjetas y anuncios |
| Tipografías | Inter Tight (títulos), Inter (texto) | Vía Google Fonts o self-hosted |

```css
/* resources/css/app.css (Tailwind 4: los tokens van en CSS, sin tailwind.config.js) */
@import 'tailwindcss';

@source '../views/**/*.blade.php';
@source '../../app/Livewire/**/*.php';

@theme {
    --color-ink: #0a0a0a;
    --color-muted: #9ca3af;
    --color-surface: #f5f5f5;
    --color-accent: #e4f86b;
    --radius-card: 1.5rem;
    --font-display: 'Inter Tight', 'Inter', sans-serif;
    --font-sans: 'Inter', sans-serif;
}
```

Con esto quedan disponibles `bg-surface`, `text-muted`, `bg-accent`, `rounded-card` y `font-display`. La web y el tema del panel se compilan en el mismo Vite, ambos con Tailwind 4.

**Reglas para mantenerlo limpio**

- Un solo acento (lima) y un solo color fuerte (negro); nada de gradientes ni sombras pesadas.
- Los anuncios van dentro de tarjetas `bg-surface rounded-card` con la etiqueta "Publicidad", para que no rompan el layout.
- En mobile las dos tarjetas se apilan (`grid-cols-1 lg:grid-cols-3`, la ancha con `lg:col-span-2`) y la fila de features pasa a una columna.
- Componentes Blade reutilizables en `resources/views/components/`: `<x-card>`, `<x-pill>`, `<x-feature>`, `<x-ad>`.

## Cómo obtener los datos del post

Recomendación: arrancar con una fuente gratuita no oficial detrás de una interfaz `TweetProvider`, y tener la API oficial de X como respaldo pago. Así, si una fuente se rompe, cambiás de driver sin tocar el resto de la app.

| Opción | Costo | Ventajas | Riesgos |
| --- | --- | --- | --- |
| API pública de FxTwitter (`api.fxtwitter.com/status/{id}`) | Gratis | JSON limpio con texto, autor y variantes de video; proyecto open source (FxEmbed) | No oficial, puede cambiar o limitar; dependés de un tercero |
| Endpoint de syndication de X (el que usan los embeds) | Gratis | Directo desde X, sin intermediarios | No documentado, X lo cambia sin aviso |
| API oficial de X v2 (pay-per-use) | USD 0,005 por post leído | Estable, documentada, legítima | Costo por request; requiere cuenta de desarrollador |
| APIs de terceros (TwitterAPI.io, GetXAPI, etc.) | Más baratas que la oficial | Fáciles de integrar | Mismo riesgo de ToS que el scraping |

Desde febrero de 2026 la API oficial no tiene plan gratuito para nuevos desarrolladores: se paga por uso, USD 0,005 por post leído y con tope de 3 millones de lecturas por mes ([X Docs](https://docs.x.com/x-api/getting-started/pricing), [Sorsa](https://api.sorsa.io/blog/twitter-api-pricing-2026)). En la práctica son USD 5 cada 1.000 consultas, por lo que solo conviene como respaldo y siempre con caché.

**Regla clave:** cachear cada post consultado (Redis, 24 h) por ID. Si 10 personas pegan el mismo link viral, se consulta la fuente una sola vez.

## Arquitectura en Laravel

Laravel 11.28+ (backend) + Livewire 4 + Tailwind CSS 4 + Filament 5 (panel admin), versiones que Filament 5 exige + MySQL, con cuentas opcionales: todo pasa por un `TweetService` con caché en Redis y drivers intercambiables. Cada descarga se baja en una cola al disco del servidor, se sirve desde ahí y se borra a las 24 h.

&#91;embedded content: arquitectura · consulta y descarga\]

Si la caché tiene el post, no se toca ninguna fuente externa; si no, el servicio prueba los drivers en orden y guarda el resultado.

| Pieza | Responsabilidad |
| --- | --- |
| `App\Livewire\TweetLookup` | Input, validación de URL, estados de carga y error, render del preview |
| `App\Services\TweetService` | Extraer el ID con regex, consultar caché, llamar drivers, normalizar a un DTO `TweetData` |
| `App\Contracts\TweetProvider` + drivers | `FxTwitterProvider`, `SyndicationProvider`, `XApiProvider`; orden configurable en `config/downloader.php` |
| Auth (Laravel Fortify + vistas Livewire propias con el estilo de la web) | Registro, login, verificación de email y reset de contraseña; Google opcional con Socialite |
| `App\Jobs\DownloadMediaJob` | En cola (Redis + Horizon): baja la variante elegida desde `*.twimg.com` al disco `downloads` y crea el `DownloadedFile` con `expires_at = now()->addDay()` |
| `GET /download/{file}` (`download.show`) | URL firmada (`URL::temporarySignedRoute`, 10 min) que devuelve el archivo con `Storage::disk('downloads')->download()` |
| Rate limiting | `RateLimiter` con límites por IP y por usuario con bloqueo progresivo (ver Seguridad y límites de uso) |
| Componentes de anuncios | `<x-ad slot="top" />` en Blade, con el código de la red en `.env` como valor por defecto; desde Filament se cambia de proveedor sin deploy |

## Cuentas, historial y archivos temporales

La app sigue funcionando sin cuenta; registrarse solo agrega el historial. Los archivos viven en el servidor como máximo 25 h y un cron horario los borra.

**Modelo de datos**

| Tabla | Campos principales | Notas |
| --- | --- | --- |
| `users` | Estándar de Laravel | Borrado de cuenta en cascada sobre el historial |
| `link_requests` | `user_id`, `tweet_id`, `url`, `author_handle`, `text_excerpt`, `thumbnail_url`, `created_at` | Solo para usuarios logueados; índice `(user_id, created_at)` |
| `downloaded_files` | `tweet_id`, `variant` (ej. 720p, o zip + hash de la selección), `disk`, `path`, `size_bytes`, `expires_at` | Único por `tweet_id` + `variant`: si otro usuario pide lo mismo antes de vencer, se reutiliza el archivo |

**Historial (`/historial`, componente `App\Livewire\History`)**

- Lista paginada (`WithPagination`) con miniatura, autor, extracto del texto y fecha.
- Botón "Volver a descargar": reconsulta el post y, si el archivo venció, lo vuelve a bajar.
- Borrar entradas sueltas, vaciar el historial completo y eliminar la cuenta.
- Las miniaturas se muestran desde el CDN de X; no se guardan.

**Flujo de descarga con archivo temporal**

1. El usuario elige una variante; si existe un `DownloadedFile` vigente para ese post y calidad, se usa directo.
2. Si no, se despacha `DownloadMediaJob` y el componente hace `wire:poll` cada 2 s hasta que el archivo esté listo.
3. Se entrega una URL firmada de 10 min hacia `download.show`.
4. El archivo queda en disco hasta que el cron lo elimina.

**Limpieza con cron (Laravel Scheduler)**

```php
// app/Models/DownloadedFile.php
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Support\Facades\Storage;

class DownloadedFile extends Model
{
    use Prunable;

    protected $casts = ['expires_at' => 'datetime'];

    public function prunable()
    {
        return static::where('expires_at', '<=', now());
    }

    protected function pruning(): void
    {
        Storage::disk($this->disk)->delete($this->path);
    }
}

// routes/console.php
use App\Models\DownloadedFile;
use Illuminate\Support\Facades\Schedule;

Schedule::command('model:prune', ['--model' => [DownloadedFile::class]])
    ->hourly()
    ->withoutOverlapping();

// Red de seguridad: borra archivos de más de 25 h sin registro en la BD
Schedule::command('downloads:sweep-orphans')->daily();
```

- En Forge: activar el **Scheduler** del sitio (corre `php artisan schedule:run` cada minuto) y un daemon para Horizon.
- `downloads:sweep-orphans` cubre jobs que fallaron a mitad de camino y dejaron archivos sin fila en `downloaded_files`.
- Pendiente: estimar descargas diarias para dimensionar el disco del VPS; si crece, mover el disco `downloads` a Vultr Object Storage (S3) con una regla de ciclo de vida de 1 día como respaldo del cron.

## Posts con varios archivos y descarga en ZIP

Cuando un post trae más de un archivo (un post de X admite hasta 4), el preview los lista todos y el usuario elige: bajar cada uno por separado, solo los seleccionados o todos juntos en un ZIP. Con un solo archivo, las opciones de ZIP no se muestran.

**Interfaz**

- Grilla de tarjetas numeradas (1 de 4…), cada una con miniatura, tipo (foto, video, GIF), peso aproximado y su botón "Descargar".
- En los videos, selector de calidad por tarjeta; el ZIP usa la calidad elegida o, por defecto, la mejor.
- Checkbox por tarjeta + botones "Descargar seleccionados (ZIP)" y "Descargar todos (ZIP)".
- Progreso visible mientras se arma el ZIP ("Preparando 2 de 4") con `wire:poll`.
- En mobile, aviso breve: el ZIP va a la app Archivos; para guardar en la galería conviene bajar uno a uno.

**Backend**

1. Se arma una clave de selección: `tweet_id` + IDs de medios y calidades ordenados → hash. Si ya existe un ZIP vigente con ese hash, se entrega directo.
2. Si no, `Bus::batch()` despacha un `DownloadMediaJob` por cada archivo que no esté ya en disco (reutiliza los existentes).
3. Al terminar el batch (`->then()`), `BuildZipJob` arma el ZIP sin compresión (`CM_STORE`: fotos y videos ya vienen comprimidos, así se ahorra CPU).
4. El ZIP se guarda como un `DownloadedFile` más, con `expires_at` a 24 h: el mismo cron de limpieza lo borra.

```php
// app/Jobs/BuildZipJob.php
public function handle(): void
{
    $disk = Storage::disk('downloads');
    $zipPath = "zips/{$this->zipFile->uuid}.zip";
    $disk->makeDirectory('zips');

    $zip = new \ZipArchive();
    $zip->open($disk->path($zipPath), \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

    foreach ($this->files as $i => $file) {
        $name = sprintf('%s_%s_%d.%s', $this->authorHandle, $this->tweetId, $i + 1, $file->extension);
        $zip->addFile($disk->path($file->path), $name);
        $zip->setCompressionName($name, \ZipArchive::CM_STORE);
    }

    $zip->close();

    $this->zipFile->update([
        'path' => $zipPath,
        'size_bytes' => $disk->size($zipPath),
        'ready_at' => now(),
    ]);
}
```

- Requiere la tabla `job_batches` (`php artisan make:queue-batches-table`) y las columnas `uuid`, `extension` y `ready_at` en `downloaded_files`.
- Dentro del ZIP los archivos se llaman `usuario_idpost_1.jpg`, `usuario_idpost_2.mp4`…; el ZIP se llama `usuario_idpost.zip`.
- Opcional: incluir un `post.txt` con el texto y el link del post.

## Seguridad y límites de uso

La defensa va en capas: Cloudflare frena el volumen antes de llegar al servidor, Laravel limita por IP y por usuario con un bloqueo que crece en cada abuso (igual que un login), y Horizon pone un techo global a las descargas simultáneas. Los números de abajo son un punto de partida para ajustar con tráfico real.

**Límites por acción**

| Acción | Invitado (por IP) | Usuario logueado | Al superarlo |
| --- | --- | --- | --- |
| Consultar un post | 10/min | 30/min | 429 y desafío Turnstile |
| Descargar archivos | 5/hora y 20/día | 30/hora y 100/día | Bloqueo progresivo con tiempo restante visible |
| Descargas en curso a la vez | 1 | 3 | Esperar a que termine la anterior |
| Login | 5 intentos/min por email + IP (Fortify ya lo trae) | — | Bloqueo temporal y Turnstile |
| Registro | 3/hora por IP | — | Turnstile obligatorio |

**Bloqueo progresivo de descargas:** cada vez que alguien supera su límite suma un "strike" y queda bloqueado 1 min, luego 5 min, 15 min y 1 h. Los strikes se reinician tras 24 h sin abusos. Los archivos que ya están en caché (mismo post y calidad) igual cuentan como descarga, y un ZIP cuenta como tantas descargas como archivos contenga.

```php
// app/Actions/Downloads/EnsureDownloadAllowed.php
namespace App\Actions\Downloads;

use App\Exceptions\TooManyDownloadsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class EnsureDownloadAllowed
{
    private const PENALTIES = [60, 300, 900, 3600]; // segundos

    public function handle(Request $request): void
    {
        $key = 'dl:' . ($request->user()?->id ?? $request->ip());

        if ($until = Cache::get("{$key}:lock")) {
            throw new TooManyDownloadsException($until);
        }

        $max = $request->user() ? 30 : 5;

        if (RateLimiter::tooManyAttempts("{$key}:hour", $max)) {
            $strikes = Cache::get("{$key}:strikes", 0) + 1;
            Cache::put("{$key}:strikes", $strikes, now()->addDay());

            $seconds = self::PENALTIES[min($strikes, count(self::PENALTIES)) - 1];
            $until = now()->addSeconds($seconds);
            Cache::put("{$key}:lock", $until, $seconds);

            throw new TooManyDownloadsException($until);
        }

        RateLimiter::hit("{$key}:hour", 3600);
    }
}
```

El límite diario se suma igual con una segunda clave (`{$key}:day`, 86400 s). Para las descargas simultáneas se usa `Cache::lock("dl-active:{$key}")` mientras corre el job.

**Protección del servidor**

- Horizon con un máximo de procesos fijo para la cola `downloads` (ej. 3): aunque lleguen 500 pedidos, el servidor nunca baja más de 3 archivos a la vez.
- `DownloadMediaJob` con `$timeout = 120`, tamaño máximo por archivo (ej. 512 MB) y por ZIP (ej. 1 GB) verificado con el header `Content-Length` antes de bajar.
- Cola con prioridad: usuarios logueados primero, invitados después.

**Amenazas y defensas**

| Amenaza | Defensa |
| --- | --- |
| Bots y scraping masivo | Cloudflare delante con Bot Fight Mode, reglas de rate limiting sobre `/download` y `/livewire/update`, Turnstile tras varias consultas |
| DDoS y acceso directo al servidor | IP de origen oculta; firewall de Vultr/Forge que solo acepta 80/443 desde rangos de Cloudflare; SSH solo con clave |
| Spam de registros | Turnstile, honeypot (`spatie/laravel-honeypot`), verificación de email obligatoria (`MustVerifyEmail`) antes de guardar historial |
| Fuerza bruta en login | Throttle de Fortify + Turnstile tras fallos + aviso por email de inicio de sesión nuevo |
| SSRF (usar el servidor para pedir URLs internas) | El usuario nunca manda una URL de archivo: solo el ID del post; el job solo baja de `https://*.twimg.com` y revalida el host tras redirects |
| Path traversal o acceso a archivos ajenos | Archivos con nombre UUID, servidos por ID de modelo con URL firmada, nunca por ruta recibida del cliente |
| Manipulación de componentes Livewire | Propiedades sensibles con `#[Locked]`, validación en cada acción, lógica en Actions y no en el componente |
| Archivos servidos como contenido ejecutable | `Content-Disposition: attachment` y `X-Content-Type-Options: nosniff` en cada descarga |
| XSS e inyección | Blade escapa por defecto; el texto del post nunca se imprime con `{!! !!}`; headers HSTS y X-Frame-Options |
| Dependencias vulnerables | `composer audit` y `npm audit` en cada deploy de Forge |

Cada 429 y cada bloqueo se registra en un canal de log propio para detectar IPs reincidentes y, si hace falta, bloquearlas en Cloudflare.

## Panel de administración (Filament)

Filament 5 en `/admin` concentra métricas, moderación y configuración, para operar la app sin tocar código ni `.env`. Solo entran usuarios con `is_admin`, vía `canAccessPanel()` en el modelo `User` (implementa `FilamentUser`).

| Pieza de Filament | Qué permite |
| --- | --- |
| Dashboard (`StatsOverviewWidget` + `ChartWidget`) | Consultas y descargas por día, tasa de éxito por driver, disco usado, posts más pedidos |
| `UserResource` | Ver, suspender y eliminar cuentas; ver el historial de cada usuario |
| `LinkRequestResource` | Solo lectura, con filtros por fecha, usuario y post |
| `DownloadedFileResource` | Archivos vigentes, peso y vencimiento; acción "Eliminar por reclamo DMCA" que borra el archivo y agrega el post a una lista de bloqueo |
| Página de bloqueos | Lista los strikes y bloqueos activos (leídos de Redis); permite levantar un bloqueo o banear una IP |
| Página de ajustes (`filament/spatie-laravel-settings-plugin`) | Límites de uso, orden de drivers, TTL de archivos y códigos de anuncios, editables en caliente |

- Nueva tabla `blocked_tweets` (`tweet_id`, `reason`, `created_at`): `TweetService` la consulta antes de mostrar cualquier post.
- Estilo: tema propio de Filament 5 con acento fijo, detallado en Tema del panel (Filament 5). Menú carbón a toda la altura, contenido gris claro, tarjetas blancas, Plus Jakarta Sans y el lima `#e4f86b` como acento con texto oscuro, forzado fuera de las capas de Filament para que botones e ítem activo se lean bien.

## Monetización

Recomendación: no depender de Google AdSense y arrancar con Adsterra o Monetag en formatos no invasivos, sumando Media.net como alternativa contextual. Los sitios de descargas suelen tener problemas con AdSense: Google tiende a deshabilitar anuncios en sitios que distribuyen contenido con copyright ([ShoutMeLoud](https://www.shoutmeloud.com/10-simple-mistake-which-violate-google-adsense-policies-and-get-banned.html/comment-page-5)), y los revisores ven con malos ojos páginas con medios scrapeados o descargas ([AdSense Audit](https://adsenseaudit.net/guides/google-adsense-alternatives-for-rejected-sites)). Ezoic tampoco sirve como atajo: si AdSense te rechaza por política, Ezoic tampoco te acepta ([Monetag](https://monetag.com/blog/adsense-alternatives/)).

| Red | Encaje con este nicho | Requisitos | Uso recomendado |
| --- | --- | --- | --- |
| [Adsterra](https://adsterra.com/blog/adsense-alternatives/) | Alto: acepta utilidades, descargas y tráfico global | Aprobación casi instantánea, sin mínimo de tráfico | Principal: banners display + native |
| [Monetag](https://monetag.com/blog/adsense-alternatives/) | Alto | Aprobación rápida, sin mínimo | Alternativa o complemento a Adsterra |
| [Media.net](https://www.searchengineworld.com/exploring-adsense-alternatives-a-guide-for-site-owners-looking-to-maximize-revenue) | Medio: prefiere sitios con contenido | Revisión manual | Cuando haya páginas de contenido (guías, FAQ) |
| Google AdSense | Bajo: riesgo de rechazo o baneo | Revisión estricta de políticas | No usar en el sitio principal |

**Ubicaciones de anuncios (sin arruinar la experiencia):**

- Un banner 728x90 / 320x50 arriba del input.
- Un bloque 300x250 o native entre la tarjeta del preview y los botones de descarga: es el momento de mayor atención.
- Un banner en el footer.
- Evitar popunders y redirecciones en el botón de descarga: suben el ingreso a corto plazo pero bajan la retención y pueden marcarte el dominio en navegadores.

**Ingresos extra a mediano plazo:** un botón "Invitame un café" (Ko-fi o Mercado Pago) y una versión sin anuncios por suscripción mínima si el tráfico crece.

## Riesgos legales y de ToS

El mayor riesgo no es técnico sino de cumplimiento: los términos de X prohíben el scraping sin autorización y el contenido descargado pertenece a sus autores. No soy abogado; conviene validar esto con uno antes de escalar.

| Riesgo | Mitigación |
| --- | --- |
| Reclamos de copyright (DMCA) | Página y email de DMCA, términos que responsabilizan al usuario, procesar bajas en menos de 48 h |
| Guardar copias en el servidor aumenta la exposición legal | TTL de 24 h, rutas firmadas y no indexables, borrado inmediato del archivo ante un reclamo |
| Bloqueo de la fuente no oficial | Interfaz `TweetProvider` con varios drivers y fallback a la API oficial |
| Abuso (bots, descargas masivas) | Rate limit por IP y por usuario, Cloudflare Turnstile tras N consultas, caché |
| Disco lleno o costo de ancho de banda | Archivos deduplicados por post y calidad, cron horario de limpieza, alerta de disco al 80% |
| Rechazo de redes publicitarias | Usar redes que aceptan este nicho (ver Monetización) |
| Datos personales (cuentas e historial) | Historial solo con cuenta, borrado de historial y de cuenta por el propio usuario, contraseñas con hash por defecto de Laravel, aviso de cookies |

Páginas obligatorias desde el día uno: Términos de uso, Privacidad, DMCA/Copyright y Contacto.

## SEO, infraestructura, métricas y roadmap

El tráfico va a venir casi todo de Google, así que el SEO se diseña desde el MVP y la infraestructura se mantiene mínima.

**SEO**

- Landing por intención de búsqueda: `/descargar-video-twitter`, `/descargar-gif-twitter`, `/descargar-imagenes-x` y sus versiones en inglés con `hreflang`.
- 300 a 500 palabras de contenido propio por landing (cómo usarlo, FAQ con schema `FAQPage`).
- Core Web Vitals en verde: Blade + Livewire liviano, anuncios con lazy load y espacio reservado para evitar saltos de layout.
- Dominio corto y genérico; evitar "twitter" o "x" en el dominio por riesgo de marca.

**Infraestructura**

- Un VPS chico en Vultr gestionado con Laravel Forge, Redis para caché y colas, Cloudflare delante (CDN, Turnstile, protección DDoS).
- MySQL para usuarios, historial, archivos y métricas agregadas; disco del VPS dimensionado para un día de descargas.

**Métricas a seguir**

| Métrica | Para qué |
| --- | --- |
| Consultas por día y tasa de éxito | Salud de la fuente de datos |
| Clics en descarga / consultas | Conversión real del flujo |
| RPM publicitario (USD por 1.000 visitas) | Rentabilidad |
| Cache hit ratio | Ahorro de llamadas pagas |

**Roadmap**

1. MVP (3 semanas): flujo completo, cuentas con historial, archivos temporales con limpieza por cron, una fuente de datos, caché, páginas legales, panel Filament básico, un set de anuncios.
2. Lanzamiento SEO (semanas 3 a 6): landings ES/EN, Search Console, segundo driver de datos como fallback.
3. Optimización (mes 2 en adelante): pruebas A/B de ubicaciones de anuncios, PWA, descarga de hilos en ZIP.
4. Expansión: sumar otras redes como micro apps hermanas reutilizando la misma base.
