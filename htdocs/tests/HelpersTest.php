<?php
// Vienetiniai testai e() funkcijai (tests/HelpersTest.php)
// GDI: Sis kodas buvo sugeneruotas naudojant Cursor (Composer / Agent, 2026-04-18).
// Uzklausa: Parasyk PHPUnit testus funkcijai e() — script tagai, kabutes, tuscia eilute.
// Rezultatas dalinai koreguotas.
use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    // Paprastas tekstas turi likti nepakitęs
    public function testEscapeReturnsPlainTextUnchanged(): void
    {
        $this->assertSame('Labas pasaulis', e('Labas pasaulis'));
    }

    // Script tagas turi būti ekranuotas
    public function testEscapeConvertsScriptTag(): void
    {
        $this->assertSame(
            '&lt;script&gt;alert(1)&lt;/script&gt;',
            e('<script>alert(1)</script>')
        );
    }

    // Kabutės turi būti ekranuotos
    public function testEscapeConvertsQuotes(): void
    {
        $this->assertSame('&quot;test&quot;', e('"test"'));
        $this->assertSame('&#039;test&#039;', e("'test'"));
    }

    // Ampersandas turi būti ekranuotas
    public function testEscapeConvertsAmpersand(): void
    {
        $this->assertSame('a &amp; b', e('a & b'));
    }

    // Tuščia eilutė
    public function testEscapeHandlesEmptyString(): void
    {
        $this->assertSame('', e(''));
    }
}
