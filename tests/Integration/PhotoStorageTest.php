<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Domain\ValidationException;
use App\Service\PhotoStorage;
use PHPUnit\Framework\TestCase;

final class PhotoStorageTest extends TestCase
{
    private string $dir;
    private string $sources;
    private PhotoStorage $storage;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/vc-photos-' . bin2hex(random_bytes(4));
        $this->sources = $this->dir . '-sources';
        mkdir($this->sources, 0775, true);
        $this->storage = new PhotoStorage($this->dir, 200_000);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/*') ?: []);
        array_map('unlink', glob($this->sources . '/*') ?: []);
        @rmdir($this->dir);
        @rmdir($this->sources);
    }

    /**
     * @return array<string, mixed>
     */
    private function upload(string $name, string $content): array
    {
        $path = $this->sources . '/' . $name;
        file_put_contents($path, $content);

        return ['name' => $name, 'tmp_name' => $path, 'error' => UPLOAD_ERR_OK, 'size' => strlen($content)];
    }

    private function jpegWithComment(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, (int) imagecolorallocate($image, 30, 90, 166));
        ob_start();
        imagejpeg($image);
        $jpeg = (string) ob_get_clean();

        // Commentaire JPEG (marqueur COM) ajouté après l'en-tête : il doit disparaître au réenregistrement.
        $comment = 'GPS 48.8566,2.3522 <?php echo 1; ?>';

        return substr($jpeg, 0, 2) . "\xFF\xFE" . pack('n', strlen($comment) + 2) . $comment . substr($jpeg, 2);
    }

    public function testImagesAreReencodedWithARandomName(): void
    {
        $name = $this->storage->store($this->upload('vacances.jpg', $this->jpegWithComment(2400, 1200)));

        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}\.webp$/', $name);
        $stored = (string) file_get_contents($this->dir . '/' . $name);
        $this->assertStringNotContainsString('GPS', $stored);
        $this->assertStringNotContainsString('<?php', $stored);
        [$width, $height, $type] = (array) getimagesize($this->dir . '/' . $name);
        $this->assertSame([1600, 800, IMAGETYPE_WEBP], [$width, $height, $type]);
    }

    public function testRefusesScriptsAndOtherFormats(): void
    {
        foreach (
            [
            'photo.jpg' => '<?php system($_GET["cmd"]); ?>',
            'image.gif' => (string) base64_decode('R0lGODlhAQABAAAAACw=', true),
            'vide.png' => '',
            ] as $name => $content
        ) {
            try {
                $this->storage->store($this->upload($name, $content));
                $this->fail("$name aurait dû être refusé.");
            } catch (ValidationException $error) {
                $this->assertArrayHasKey('photo', $error->errors);
            }
        }
        $this->assertSame([], glob($this->dir . '/*') ?: []);
    }

    public function testRefusesFilesThatAreTooLarge(): void
    {
        $this->expectException(ValidationException::class);
        $this->storage->store(['name' => 'grande.jpg', 'tmp_name' => __FILE__, 'error' => UPLOAD_ERR_OK, 'size' => 300_000]);
    }

    public function testDeleteOnlyAcceptsGeneratedNames(): void
    {
        $name = $this->storage->store($this->upload('a.jpg', $this->jpegWithComment(100, 80)));
        file_put_contents($this->sources . '/garder.txt', 'à garder');

        $this->storage->delete('../' . basename($this->sources) . '/garder.txt');
        $this->assertFileExists($this->sources . '/garder.txt');

        $this->storage->delete($name);
        $this->assertFileDoesNotExist($this->dir . '/' . $name);
    }
}
