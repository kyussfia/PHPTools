<?php
/**
 * Created by PhpStorm.
 * User: mikus
 * Date: 2017.04.13.
 * Time: 14:39
 */

namespace AppBundle\Doctrine\Common\Cache;

use Doctrine\Common\Cache\FileCache;

class PhpFilesystemCache extends FileCache
{
    const EXTENSION = '.cache.php';

    /**
     * {@inheritdoc}
     */
    public function __construct($directory, $extension = self::EXTENSION, $umask = 0002)
    {
        if (null === $umask) {
            $umask = 0002;
        }

        parent::__construct($directory, $extension, (int)$umask);
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch($id)
    {
        $value = $this->includeFileForId($id);

        if (! $value) {
            return false;
        }

        if ($value['lifetime'] !== 0 && $value['lifetime'] < time()) {
            return false;
        }

        return unserialize($value['data']);
    }

    /**
     * {@inheritdoc}
     */
    protected function doContains($id)
    {
        $value = $this->includeFileForId($id);

        if (! $value) {
            return false;
        }

        return $value['lifetime'] === 0 || $value['lifetime'] > time();
    }

    /**
     * @param string $id
     *
     * @return array|false
     */
    private function includeFileForId($id)
    {
        $fileName = $this->getFilename($id);

        /*if ( ! is_file($fileName)) {
            return false;
        }*/

        // note: error suppression is still faster than `file_exists`, `is_file` and `is_readable`
        $value = @include $fileName;

        if (! isset($value['lifetime'])) {
            return false;
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        if ($lifeTime > 0) {
            $lifeTime = time() + $lifeTime;
        }

        $serialized = serialize($data);
        $filename  = $this->getFilename($id);

        $value = array(
            'lifetime'  => $lifeTime,
            'data'      => $serialized
        );

        $value  = var_export($value, true);
        $code   = sprintf('<?php return %s;', $value);

        return $this->writeFile($filename, $code);
    }
}