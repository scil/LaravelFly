
public function getCompiledPath($path)
{
return $this->cachePath.'/'.sha1($path).'.php';
}

===A===

public function isExpired($path)
{
$compiled = $this->getCompiledPath($path);

// If the compiled file doesn't exist we will indicate that the view is expired
// so that it can be re-compiled. Else, we will verify the last modification
// of the views is less than the modification times of the compiled views.
if (! $this->files->exists($compiled)) {
return true;
}

return $this->files->lastModified($path) >=
$this->files->lastModified($compiled);
}

===A===

{
    /**
     * The Filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Get the cache path for the compiled views.
     *
     * @var string
     */
    protected $cachePath;

    /**
     * Create a new compiler instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  string  $cachePath
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(Filesystem $files, $cachePath)

