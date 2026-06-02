<?php
// Vienetiniai testai password_hash ir password_verify (tests/AuthTest.php)
// GDI: Sis kodas buvo sugeneruotas naudojant Cursor (Composer / Agent, 2026-04-18).
// Uzklausa: Kaip PHPUnit patikrinti password_hash ir password_verify elgsena?
// Rezultatas dalinai koreguotas.
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{
    // BCrypt maiša generuojama sėkmingai
    public function testPasswordHashIsGenerated(): void
    {
        $hash = password_hash('Slaptazodis123', PASSWORD_DEFAULT);
        $this->assertNotEmpty($hash);
        $this->assertStringStartsWith('$2y$', $hash,
            'Turi būti BCrypt maiša');
    }

    // Teisingas slaptažodis patvirtinamas
    public function testCorrectPasswordVerifies(): void
    {
        $hash = password_hash('Slaptazodis123', PASSWORD_DEFAULT);
        $this->assertTrue(password_verify('Slaptazodis123', $hash));
    }

    // Neteisingas slaptažodis atmetamas
    public function testWrongPasswordFails(): void
    {
        $hash = password_hash('Slaptazodis123', PASSWORD_DEFAULT);
        $this->assertFalse(password_verify('NeteisingasSlaptazodis', $hash));
    }

    // Kiekviena maiša unikali (skirtingos druskelės)
    public function testTwoHashesOfSamePasswordAreDifferent(): void
    {
        $hash1 = password_hash('tas_pats', PASSWORD_DEFAULT);
        $hash2 = password_hash('tas_pats', PASSWORD_DEFAULT);
        $this->assertNotSame($hash1, $hash2,
            'BCrypt druskelė turi būti atsitiktinė — maišos turi skirtis');
    }

    // Tuščias slaptažodis nesutampa su maiša
    public function testEmptyPasswordDoesNotMatch(): void
    {
        $hash = password_hash('Slaptazodis123', PASSWORD_DEFAULT);
        $this->assertFalse(password_verify('', $hash));
    }
}
