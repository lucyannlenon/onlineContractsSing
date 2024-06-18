<?php

namespace App\Services;

use Symfony\Component\Serializer\SerializerInterface;

class LocalToken
{
    const string DIR = "/tmp";

    public function __construct(
        private readonly SerializerInterface $serializer
    )
    {

    }

    public function save(mixed $entity, string $key): void
    {
        $serializer = $this->serializer->serialize($entity, 'json');

        file_put_contents($this->getFilename($key), $serializer);
    }

    public function get(string $key, string $class): ?object
    {
        $filename = $this->getFilename($key);
        if (file_exists($filename)) {
            return null;
        }

        $data = file_get_contents($key);

        return $this->serializer->deserialize($data, "json", $class);
    }

    public function remove(string $key): void
    {
        unlink( $this->getFilename($key));
    }

    /**
     * @param string $key
     * @return string
     */
    public function getFilename(string $key): string
    {
        return self::DIR . "/$key";
    }

}