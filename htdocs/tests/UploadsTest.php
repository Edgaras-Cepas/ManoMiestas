<?php
// Vienetiniai testai handle_uploads() funkcijai (tests/UploadsTest.php)
// GDI: Sis kodas buvo sugeneruotas naudojant Cursor (Composer / Agent, 2026-04-18).
// Uzklausa: Reikia testu handle_uploads — per didelis failas, php failas, max 5 nuotraukos.
// Rezultatas dalinai koreguotas.
use PHPUnit\Framework\TestCase;

class UploadsTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/manomiestas_test';
        if (!is_dir($this->tmpDir)) {
            mkdir($this->tmpDir, 0755, true);
        }
        $GLOBALS['app_config']['uploads']['base_dir'] = $this->tmpDir;
    }

    private function makeFakeUpload(
        string $content,
        string $name,
        string $mime,
        int $error = UPLOAD_ERR_OK
    ): array {
        $tmp = tempnam(sys_get_temp_dir(), 'upl_');
        file_put_contents($tmp, $content);
        return [
            'name'     => [$name],
            'tmp_name' => [$tmp],
            'size'     => [strlen($content)],
            'error'    => [$error],
        ];
    }

    // Tinkamas JPEG failas priimamas
    public function testValidJpegIsAccepted(): void
    {
        // Minimalus tinkamas JPEG baitų antraštė
        $jpegHeader = "\xFF\xD8\xFF\xE0" . str_repeat('A', 100);
        $files = $this->makeFakeUpload($jpegHeader, 'test.jpg', 'image/jpeg');
        $paths = handle_uploads($files);
        $this->assertNotEmpty($paths, 'Tinkamas JPEG turėjo būti priimtas');
    }

    // PHP failas atmetamas
    public function testPhpFileIsRejected(): void
    {
        $files = $this->makeFakeUpload(
            '<?php echo "hack"; ?>',
            'evil.php',
            'application/x-php'
        );
        $paths = handle_uploads($files);
        $this->assertEmpty($paths, 'PHP failas turėjo būti atmestas');
    }

    // Per didelis failas atmetamas
    public function testOversizedFileIsRejected(): void
    {
        $tooBig = str_repeat('X', 3 * 1024 * 1024); // 3 MB
        $files = $this->makeFakeUpload($tooBig, 'big.jpg', 'image/jpeg');
        $paths = handle_uploads($files);
        $this->assertEmpty($paths, 'Per didelis failas turėjo būti atmestas');
    }

    // Įkėlimo klaida atmetama
    public function testUploadErrorIsRejected(): void
    {
        $files = $this->makeFakeUpload('data', 'err.jpg', 'image/jpeg', UPLOAD_ERR_PARTIAL);
        $paths = handle_uploads($files);
        $this->assertEmpty($paths, 'Failas su klaida turėjo būti atmestas');
    }

    // Daugiau nei 5 failai — priimami tik pirmi 5
    public function testMaxFilesLimitEnforced(): void
    {
        $jpegHeader = "\xFF\xD8\xFF\xE0" . str_repeat('A', 100);
        $files = [
            'name'     => array_fill(0, 7, 'img.jpg'),
            'tmp_name' => [],
            'size'     => array_fill(0, 7, strlen($jpegHeader)),
            'error'    => array_fill(0, 7, UPLOAD_ERR_OK),
        ];
        for ($i = 0; $i < 7; $i++) {
            $tmp = tempnam(sys_get_temp_dir(), 'upl_');
            file_put_contents($tmp, $jpegHeader);
            $files['tmp_name'][] = $tmp;
        }
        $paths = handle_uploads($files);
        $this->assertLessThanOrEqual(5, count($paths),
            'Neturėjo būti priimta daugiau nei 5 failai');
    }
}
