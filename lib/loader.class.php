<?

namespace Salesbeat;

class Loader
{
    public function register()
    {
        spl_autoload_register([$this, 'loadClasses']);
    }

    public function loadClasses($className)
    {
        $classPath = str_replace('\\', '/', strtolower($className)) . '.class.php';

        $classes = [dirname(__FILE__)];

        foreach ($classes as $class) {
            $filePath = $class . '/' . $classPath;

            if (is_file($filePath))
                require_once $filePath;
        }
    }
}