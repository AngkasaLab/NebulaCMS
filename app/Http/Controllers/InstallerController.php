<?php

namespace App\Http\Controllers;

use App\Installer\EnvWriter;
use App\Installer\InstallerWizardStore;
use Database\Seeders\ContentSampleSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InstallerController extends Controller
{
    // -------------------------------------------------------------------------
    // Step 1: Welcome & Requirements
    // -------------------------------------------------------------------------

    public function setLocale(Request $request, string $locale): RedirectResponse
    {
        $request->session()->put('installer_locale', $locale);

        return redirect()->back();
    }

    public function welcome(): View
    {
        $requirements = $this->checkRequirements();

        return view('installer.welcome', compact('requirements'));
    }

    // -------------------------------------------------------------------------
    // Step 2: Database
    // -------------------------------------------------------------------------

    public function database(Request $request): View|RedirectResponse
    {
        if (! $this->requirementsPass()) {
            return redirect()->route('installer.welcome');
        }

        $data = $request->session()->get('installer.database', [
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'nebula_cms',
            'username' => 'root',
            'password' => '',
        ]);

        return view('installer.database', compact('data'));
    }

    public function saveDatabase(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'host' => ['required', 'string'],
            'port' => ['required', 'numeric', 'between:1,65535'],
            'database' => ['required', 'string'],
            'username' => ['required', 'string'],
            'password' => ['nullable', 'string'],
        ]);

        $error = $this->testDbConnection($validated);
        if ($error) {
            return back()->withInput()->withErrors(['connection' => $error]);
        }

        $request->session()->put('installer.database', $validated);

        return redirect()->route('installer.site');
    }

    public function testDatabase(Request $request): JsonResponse
    {
        $data = $request->only(['host', 'port', 'database', 'username', 'password']);
        $error = $this->testDbConnection($data);

        if ($error) {
            return response()->json(['success' => false, 'message' => $error]);
        }

        return response()->json(['success' => true, 'message' => __('installer.db_connection_success')]);
    }

    // -------------------------------------------------------------------------
    // Step 3: Site Settings
    // -------------------------------------------------------------------------

    public function site(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('installer.database')) {
            return redirect()->route('installer.database');
        }

        $data = $request->session()->get('installer.site', [
            'app_name' => 'NebulaCMS',
            'app_url' => request()->root(),
            'app_env' => 'production',
            'locale' => 'en',
        ]);

        return view('installer.site', compact('data'));
    }

    public function saveSite(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:100'],
            'app_url' => ['required', 'url'],
            'app_env' => ['required', 'in:production,local'],
            'locale' => ['required', 'in:en,id'],
        ]);

        $request->session()->put('installer.site', $validated);

        return redirect()->route('installer.account');
    }

    // -------------------------------------------------------------------------
    // Step 4: Admin Account
    // -------------------------------------------------------------------------

    public function account(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('installer.site')) {
            return redirect()->route('installer.site');
        }

        $data = $request->session()->get('installer.account', [
            'name' => 'Administrator',
            'email' => '',
        ]);

        return view('installer.account', compact('data'));
    }

    public function saveAccount(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ]);

        $account = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ];

        $token = Str::random(64);
        InstallerWizardStore::put($token, [
            'database' => $request->session()->get('installer.database'),
            'site' => $request->session()->get('installer.site'),
            'account' => $account,
            'cursor' => 0,
        ]);

        $request->session()->put([
            'installer.account' => $account,
            'installer.wizard_token' => $token,
            'installer.install_cursor' => 0,
        ]);

        return redirect()->route('installer.installing');
    }

    // -------------------------------------------------------------------------
    // Step 5: Installing
    // -------------------------------------------------------------------------

    public function installing(Request $request): View|RedirectResponse
    {
        $token = $request->session()->get('installer.wizard_token');
        $payload = is_string($token) && $token !== '' ? InstallerWizardStore::get($token) : null;

        if (! $payload || ! isset($payload['account'], $payload['database'], $payload['site'])) {
            return redirect()->route('installer.account');
        }

        return view('installer.installing', [
            'installToken' => $token,
            'installCursor' => (int) ($payload['cursor'] ?? 0),
        ]);
    }

    /**
     * AJAX: Run one install sub-step. The client calls repeatedly until `finished` is true.
     */
    public function run(Request $request): JsonResponse
    {
        $token = $request->input('install_token');
        if (! is_string($token) || $token === '') {
            return response()->json(['error' => __('installer.invalid_install_request')], 422);
        }

        $payload = InstallerWizardStore::get($token);
        if (! $payload || ! isset($payload['account'], $payload['database'], $payload['site'])) {
            return response()->json(['error' => __('installer.session_expired')], 422);
        }

        $cursor = (int) ($payload['cursor'] ?? 0);

        if ($cursor < 0 || $cursor > 5) {
            return response()->json(['error' => __('installer.invalid_install')], 422);
        }

        $db = $payload['database'];
        $site = $payload['site'];
        $account = $payload['account'];

        try {
            $label = $this->installStepLabel($cursor);
            $this->runStep($label, function () use ($cursor, $db, $site, $account) {
                $this->executeInstallStep($cursor, $db, $site, $account);
            });

            $payload['cursor'] = $cursor + 1;
            InstallerWizardStore::put($token, $payload);

            $finished = $cursor === 5;

            if ($finished) {
                InstallerWizardStore::forget($token);
                $request->session()->forget([
                    'installer.database',
                    'installer.site',
                    'installer.account',
                    'installer.install_cursor',
                    'installer.wizard_token',
                ]);
                $request->session()->flash('installer_just_finished', true);
            }

            return response()->json([
                'success' => true,
                'stepIndex' => $cursor,
                'step' => ['status' => 'success', 'message' => $label],
                'finished' => $finished,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'stepIndex' => $cursor,
                'step' => ['status' => 'error', 'message' => $e->getMessage()],
            ], 500);
        }
    }

    private function installStepLabel(int $index): string
    {
        return match ($index) {
            0 => __('installer.install_step_1_label'),
            1 => __('installer.install_step_2_label'),
            2 => __('installer.install_step_3_label'),
            3 => __('installer.install_step_4_label'),
            4 => __('installer.install_step_5_label'),
            5 => __('installer.install_step_6_label'),
            default => throw new \InvalidArgumentException('Invalid install step'),
        };
    }

    private function executeInstallStep(int $index, array $db, array $site, array $account): void
    {
        switch ($index) {
            case 0:
                EnvWriter::write([
                    'APP_NAME' => $site['app_name'],
                    'APP_URL' => $site['app_url'],
                    'APP_ENV' => $site['app_env'],
                    'APP_DEBUG' => $site['app_env'] === 'production' ? 'false' : 'true',
                    'APP_LOCALE' => $site['locale'],
                    'APP_FALLBACK_LOCALE' => $site['locale'],
                    'SESSION_DRIVER' => 'file',
                    'SESSION_ENCRYPT' => 'false',
                    'SESSION_COOKIE' => config('session.cookie'),
                    'DB_CONNECTION' => 'mysql',
                    'DB_HOST' => $db['host'],
                    'DB_PORT' => $db['port'],
                    'DB_DATABASE' => $db['database'],
                    'DB_USERNAME' => $db['username'],
                    'DB_PASSWORD' => $db['password'] ?? '',
                ]);

                return;
            case 1:
                $this->bootstrapApplicationKey();

                return;
            case 2:
                $this->reconfigureDb($db);
                Artisan::call('migrate', ['--force' => true]);
                Artisan::call('db:seed', [
                    '--class' => RoleAndPermissionSeeder::class,
                    '--force' => true,
                ]);

                return;
            case 3:
                $this->createAdminUser($account);

                return;
            case 4:
                Artisan::call('db:seed', [
                    '--class' => ContentSampleSeeder::class,
                    '--force' => true,
                ]);

                return;
            case 5:
                file_put_contents(
                    storage_path('installed.lock'),
                    'Installed on '.now()->toDateTimeString()
                );

                return;
            default:
                throw new \InvalidArgumentException('Invalid install step');
        }
    }

    // -------------------------------------------------------------------------
    // Step 6: Done
    // -------------------------------------------------------------------------

    public function done(Request $request): View|RedirectResponse
    {
        if (! file_exists(storage_path('installed.lock'))) {
            return redirect()->route('installer.installing');
        }

        if (! $request->session()->get('installer_just_finished')) {
            return redirect('/');
        }

        return view('installer.done');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function runStep(string $label, callable $callback): array
    {
        try {
            $callback();

            return ['status' => 'success', 'message' => $label];
        } catch (\Throwable $e) {
            throw new \RuntimeException("{$label}: ".$e->getMessage(), 0, $e);
        }
    }

    /**
     * Ensure .env has a usable APP_KEY. Laravel's key:generate exits without error when
     * preg_replace cannot find an APP_KEY= line, leaving an empty key.
     */
    private function bootstrapApplicationKey(): void
    {
        $path = base_path('.env');
        $examplePath = base_path('.env.example');

        if (! file_exists($path) && file_exists($examplePath)) {
            copy($examplePath, $path);
        }

        $raw = file_exists($path) ? (string) file_get_contents($path) : '';

        if (! preg_match('/^APP_KEY=/m', $raw)) {
            $newContents = $raw === ''
                ? "APP_KEY=\n"
                : rtrim($raw)."\nAPP_KEY=\n";
            file_put_contents($path, $newContents);
        }

        Artisan::call('key:generate', ['--force' => true]);

        $key = $this->readAppKeyFromEnvFile();
        if ($key === null || $key === '') {
            $key = 'base64:'.base64_encode(Encrypter::generateKey(config('app.cipher')));
            EnvWriter::write(['APP_KEY' => $key]);
        }

        config(['app.key' => $key]);
    }

    private function readAppKeyFromEnvFile(): ?string
    {
        $path = base_path('.env');
        if (! file_exists($path)) {
            return null;
        }

        $raw = file_get_contents($path);
        if (! preg_match('/^APP_KEY=(.*)$/m', $raw, $m)) {
            return null;
        }

        $v = trim($m[1]);
        if ($v !== '' && ($v[0] === '"' || $v[0] === "'")) {
            $v = trim($v, "\"'");
        }

        return $v === '' ? null : $v;
    }

    private function testDbConnection(array $config): ?string
    {
        try {
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
            new \PDO($dsn, $config['username'], $config['password'] ?? '', [
                \PDO::ATTR_TIMEOUT => 5,
            ]);

            return null;
        } catch (\PDOException $e) {
            return $e->getMessage();
        }
    }

    private function reconfigureDb(array $db): void
    {
        config([
            'database.connections.mysql.host' => $db['host'],
            'database.connections.mysql.port' => $db['port'],
            'database.connections.mysql.database' => $db['database'],
            'database.connections.mysql.username' => $db['username'],
            'database.connections.mysql.password' => $db['password'] ?? '',
        ]);

        DB::purge('mysql');
        DB::reconnect('mysql');
    }

    private function createAdminUser(array $account): void
    {
        // Match RoleAndPermissionSeeder: role name is "Super Admin", guard web
        $user = \App\Models\User::updateOrCreate(
            ['email' => $account['email']],
            [
                'name' => $account['name'],
                'password' => Hash::make($account['password']),
            ]
        );

        if (! $user->hasRole('Super Admin')) {
            $user->assignRole('Super Admin');
        }
    }

    private function checkRequirements(): array
    {
        $phpVersion = phpversion();
        $phpOk = version_compare($phpVersion, '8.2.0', '>=');

        $extensions = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath', 'fileinfo', 'curl', 'gd'];
        $extResults = [];
        foreach ($extensions as $ext) {
            $extResults[$ext] = extension_loaded($ext);
        }

        $paths = [
            'storage/' => storage_path(),
            'bootstrap/cache/' => base_path('bootstrap/cache'),
        ];
        $pathResults = [];
        foreach ($paths as $label => $path) {
            $pathResults[$label] = is_writable($path);
        }

        return [
            'php' => ['version' => $phpVersion, 'ok' => $phpOk, 'required' => '8.2.0'],
            'extensions' => $extResults,
            'paths' => $pathResults,
        ];
    }

    private function requirementsPass(): bool
    {
        $req = $this->checkRequirements();

        if (! $req['php']['ok']) {
            return false;
        }

        foreach ($req['extensions'] as $loaded) {
            if (! $loaded) {
                return false;
            }
        }

        return true;
    }
}
