

public function addJsonPath($path)
{
    $this->jsonPaths[] = $path;
}

===A===

protected function loadJsonPaths($locale)
{
    return collect(array_merge($this->jsonPaths, [$this->path]))
        ->reduce(function ($output, $path) use ($locale) {
            if ($this->files->exists($full = "{$path}/{$locale}.json")) {
                $decoded = json_decode($this->files->get($full), true);

                if (is_null($decoded) || json_last_error() !== JSON_ERROR_NONE) {
                    throw new RuntimeException("Translation file [{$full}] contains an invalid JSON structure.");
                }

                $output = array_merge($output, $decoded);
            }

            return $output;
        }, []);
}

===A===


{
/**
* The filesystem instance.
*
* @var \Illuminate\Filesystem\Filesystem
*/
protected $files;

/**
* The default path for the loader.
*
* @var string
*/
protected $path;

/**
* All of the registered paths to JSON translation files.
*
* @var string
*/
protected $jsonPaths = [];

/**
* All of the namespace hints.
*
* @var array
*/
protected $hints = [];

/**
* Create a new file loader instance.
*
* @param  \Illuminate\Filesystem\Filesystem  $files
* @param  string  $path
* @return void
*/
public function __construct(Filesystem $files, $path)
