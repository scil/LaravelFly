
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

