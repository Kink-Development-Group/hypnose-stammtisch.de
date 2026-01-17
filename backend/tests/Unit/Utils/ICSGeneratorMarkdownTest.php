<?php

declare(strict_types=1);

namespace HypnoseStammtisch\Tests\Unit\Utils;

use HypnoseStammtisch\Utils\ICSGenerator;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Tests for the ICSGenerator markdownToPlainText functionality
 */
class ICSGeneratorMarkdownTest extends TestCase
{
  private ReflectionMethod $markdownToPlainText;

  protected function setUp(): void
  {
    parent::setUp();

    // Access private method via reflection
    $reflection = new ReflectionClass(ICSGenerator::class);
    $this->markdownToPlainText = $reflection->getMethod('markdownToPlainText');
    $this->markdownToPlainText->setAccessible(true);
  }

  /**
   * Helper to invoke the private method
   */
  private function convert(string $markdown): string
  {
    return $this->markdownToPlainText->invoke(null, $markdown);
  }

  // =========================================================================
  // Empty / Edge Cases
  // =========================================================================

  public function testEmptyString(): void
  {
    $result = $this->convert('');
    $this->assertEquals('', $result);
  }

  public function testPlainTextUnchanged(): void
  {
    $input = 'This is plain text without any Markdown.';
    $result = $this->convert($input);
    $this->assertEquals($input, $result);
  }

  public function testWhitespaceOnly(): void
  {
    $result = $this->convert('   ');
    $this->assertEquals('', $result);
  }

  // =========================================================================
  // Header Tests
  // =========================================================================

  public function testH1Header(): void
  {
    $result = $this->convert('# Main Title');
    $this->assertStringContainsString('Main Title', $result);
    $this->assertStringNotContainsString('#', $result);
  }

  public function testH2Header(): void
  {
    $result = $this->convert('## Section Title');
    $this->assertStringContainsString('Section Title', $result);
    $this->assertStringNotContainsString('##', $result);
  }

  public function testH3Header(): void
  {
    $result = $this->convert('### Subsection');
    $this->assertStringContainsString('Subsection', $result);
    $this->assertStringNotContainsString('###', $result);
  }

  public function testH6Header(): void
  {
    $result = $this->convert('###### Deep Header');
    $this->assertStringContainsString('Deep Header', $result);
    $this->assertStringNotContainsString('#', $result);
  }

  public function testMultipleHeaders(): void
  {
    $input = "# Title\n\n## Section 1\n\n### Subsection";
    $result = $this->convert($input);
    $this->assertStringContainsString('Title', $result);
    $this->assertStringContainsString('Section 1', $result);
    $this->assertStringContainsString('Subsection', $result);
    $this->assertStringNotContainsString('#', $result);
  }

  // =========================================================================
  // Bold / Italic Tests
  // =========================================================================

  public function testBoldWithAsterisks(): void
  {
    $result = $this->convert('This is **bold** text.');
    $this->assertStringContainsString('*bold*', $result);
    $this->assertStringNotContainsString('**', $result);
  }

  public function testBoldWithUnderscores(): void
  {
    $result = $this->convert('This is __bold__ text.');
    $this->assertStringContainsString('*bold*', $result);
    $this->assertStringNotContainsString('__', $result);
  }

  public function testItalicWithUnderscores(): void
  {
    $result = $this->convert('This is _italic_ text.');
    $this->assertStringContainsString('italic', $result);
    // Underscores should be removed
    $this->assertStringNotContainsString('_italic_', $result);
  }

  public function testMixedBoldAndItalic(): void
  {
    $result = $this->convert('**Bold** and _italic_ text.');
    $this->assertStringContainsString('*Bold*', $result);
    $this->assertStringContainsString('italic', $result);
  }

  // =========================================================================
  // Link Tests
  // =========================================================================

  public function testSimpleLink(): void
  {
    $result = $this->convert('[Click here](https://example.com)');
    $this->assertStringContainsString('Click here', $result);
    $this->assertStringContainsString('https://example.com', $result);
    $this->assertStringNotContainsString('[', $result);
    $this->assertStringNotContainsString('](', $result);
  }

  public function testLinkWithTextBefore(): void
  {
    $result = $this->convert('Visit [our website](https://example.com) for more info.');
    $this->assertStringContainsString('Visit', $result);
    $this->assertStringContainsString('our website', $result);
    $this->assertStringContainsString('https://example.com', $result);
    $this->assertStringContainsString('for more info', $result);
  }

  public function testMultipleLinks(): void
  {
    $input = '[Link 1](https://one.com) and [Link 2](https://two.com)';
    $result = $this->convert($input);
    $this->assertStringContainsString('Link 1', $result);
    $this->assertStringContainsString('https://one.com', $result);
    $this->assertStringContainsString('Link 2', $result);
    $this->assertStringContainsString('https://two.com', $result);
  }

  // =========================================================================
  // Code Tests
  // =========================================================================

  public function testInlineCode(): void
  {
    $result = $this->convert('Use `code` here.');
    $this->assertStringContainsString("'code'", $result);
    $this->assertStringNotContainsString('`', $result);
  }

  public function testMultipleInlineCode(): void
  {
    $result = $this->convert('Use `foo` and `bar` functions.');
    $this->assertStringContainsString("'foo'", $result);
    $this->assertStringContainsString("'bar'", $result);
  }

  public function testCodeBlock(): void
  {
    $input = "```php\necho 'Hello';\n```";
    $result = $this->convert($input);
    $this->assertStringContainsString("echo 'Hello';", $result);
    $this->assertStringNotContainsString('```', $result);
    // Note: Language identifier may remain in output
  }

  public function testCodeBlockWithoutLanguage(): void
  {
    $input = "```\nsome code\n```";
    $result = $this->convert($input);
    $this->assertStringContainsString('some code', $result);
    $this->assertStringNotContainsString('```', $result);
  }

  // =========================================================================
  // List Tests
  // =========================================================================

  public function testUnorderedListWithAsterisks(): void
  {
    $input = "* Item 1\n* Item 2\n* Item 3";
    $result = $this->convert($input);
    $this->assertStringContainsString('• Item 1', $result);
    $this->assertStringContainsString('• Item 2', $result);
    $this->assertStringContainsString('• Item 3', $result);
  }

  public function testUnorderedListWithDashes(): void
  {
    $input = "- First\n- Second\n- Third";
    $result = $this->convert($input);
    $this->assertStringContainsString('• First', $result);
    $this->assertStringContainsString('• Second', $result);
    $this->assertStringContainsString('• Third', $result);
  }

  public function testOrderedList(): void
  {
    $input = "1. First item\n2. Second item\n3. Third item";
    $result = $this->convert($input);
    // Ordered lists should remain readable
    $this->assertStringContainsString('1. First item', $result);
    $this->assertStringContainsString('2. Second item', $result);
  }

  // =========================================================================
  // Blockquote Tests
  // =========================================================================

  public function testBlockquote(): void
  {
    $result = $this->convert('> This is a quote');
    $this->assertStringContainsString('| This is a quote', $result);
    $this->assertStringNotContainsString('>', $result);
  }

  public function testMultilineBlockquote(): void
  {
    $input = "> Line 1\n> Line 2";
    $result = $this->convert($input);
    $this->assertStringContainsString('| Line 1', $result);
    $this->assertStringContainsString('| Line 2', $result);
  }

  // =========================================================================
  // Horizontal Rule Tests
  // =========================================================================

  public function testHorizontalRuleWithDashes(): void
  {
    $input = "Before\n\n---\n\nAfter";
    $result = $this->convert($input);
    $this->assertStringContainsString('Before', $result);
    $this->assertStringContainsString('---', $result);
    $this->assertStringContainsString('After', $result);
  }

  public function testHorizontalRuleWithAsterisks(): void
  {
    $result = $this->convert('***');
    $this->assertStringContainsString('---', $result);
  }

  public function testHorizontalRuleWithUnderscores(): void
  {
    $result = $this->convert('___');
    $this->assertStringContainsString('---', $result);
  }

  // =========================================================================
  // Image Tests
  // =========================================================================

  public function testImageRemoved(): void
  {
    $result = $this->convert('![Alt text](https://example.com/image.png)');
    // Images are converted to keep alt text visible
    $this->assertStringContainsString('Alt text', $result);
    // The ! prefix should be removed
    $this->assertStringNotContainsString('![', $result);
  }

  public function testImageWithEmptyAlt(): void
  {
    $result = $this->convert('![](https://example.com/image.png)');
    $this->assertStringNotContainsString('https://example.com/image.png', $result);
  }

  // =========================================================================
  // Complex / Combined Markdown Tests
  // =========================================================================

  public function testComplexMarkdown(): void
  {
    $input = <<<'MD'
# Welcome

This is a **sample** document with various _formatting_.

## Features

- Item one
- Item two with `code`

> Important note here

Visit [our site](https://example.com) for more.

---

### Code Example

```javascript
console.log('Hello');
```
MD;

    $result = $this->convert($input);

    // Headers converted
    $this->assertStringContainsString('Welcome', $result);
    $this->assertStringContainsString('Features', $result);
    $this->assertStringContainsString('Code Example', $result);
    $this->assertStringNotContainsString('#', $result);

    // Bold converted to emphasis
    $this->assertStringContainsString('*sample*', $result);

    // Italic converted
    $this->assertStringContainsString('formatting', $result);

    // List converted
    $this->assertStringContainsString('• Item one', $result);

    // Inline code converted
    $this->assertStringContainsString("'code'", $result);

    // Blockquote converted
    $this->assertStringContainsString('| Important note here', $result);

    // Link converted
    $this->assertStringContainsString('our site', $result);
    $this->assertStringContainsString('https://example.com', $result);

    // Code block converted
    $this->assertStringContainsString("console.log('Hello');", $result);
    $this->assertStringNotContainsString('```', $result);
  }

  // =========================================================================
  // Malformed Markdown Tests
  // =========================================================================

  public function testUnclosedBold(): void
  {
    $input = 'This is **unclosed bold';
    $result = $this->convert($input);
    // Should not crash, content preserved
    $this->assertStringContainsString('unclosed bold', $result);
  }

  public function testUnclosedItalic(): void
  {
    $input = 'This is _unclosed italic';
    $result = $this->convert($input);
    // Should not crash, content preserved
    $this->assertStringContainsString('unclosed italic', $result);
  }

  public function testUnclosedInlineCode(): void
  {
    $input = 'This is `unclosed code';
    $result = $this->convert($input);
    // Should not crash, content preserved
    $this->assertStringContainsString('unclosed code', $result);
  }

  public function testUnclosedCodeBlock(): void
  {
    $input = "```\nUnclosed block";
    $result = $this->convert($input);
    // Should not crash
    $this->assertNotEmpty($result);
  }

  public function testMalformedLink(): void
  {
    $input = '[Broken link(https://example.com)';
    $result = $this->convert($input);
    // Should not crash, content preserved somehow
    $this->assertNotEmpty($result);
  }

  public function testEmptyLink(): void
  {
    $input = '[](https://example.com)';
    $result = $this->convert($input);
    // Empty text with URL
    $this->assertStringContainsString('https://example.com', $result);
  }

  public function testLinkWithEmptyUrl(): void
  {
    $input = '[Click here]()';
    $result = $this->convert($input);
    $this->assertStringContainsString('Click here', $result);
  }

  public function testNestedBold(): void
  {
    $input = '**Bold **nested** bold**';
    $result = $this->convert($input);
    // Should handle gracefully
    $this->assertNotEmpty($result);
  }

  public function testOnlySpecialCharacters(): void
  {
    $input = '# ** __ `` [] ()';
    $result = $this->convert($input);
    // Should not crash
    $this->assertNotEmpty($result);
  }

  // =========================================================================
  // Newline Handling Tests
  // =========================================================================

  public function testExcessiveNewlinesReduced(): void
  {
    $input = "Line 1\n\n\n\n\nLine 2";
    $result = $this->convert($input);
    // Should not have more than 2 consecutive newlines
    $this->assertDoesNotMatchRegularExpression('/\n{3,}/', $result);
  }

  public function testNewlinesPreserved(): void
  {
    $input = "Line 1\n\nLine 2";
    $result = $this->convert($input);
    $this->assertStringContainsString('Line 1', $result);
    $this->assertStringContainsString('Line 2', $result);
  }

  // =========================================================================
  // Unicode / Special Character Tests
  // =========================================================================

  public function testGermanUmlauts(): void
  {
    $input = '# Überschrift mit Umlauten: äöüß';
    $result = $this->convert($input);
    $this->assertStringContainsString('Überschrift', $result);
    $this->assertStringContainsString('äöüß', $result);
  }

  public function testEmojis(): void
  {
    $input = 'Hello 👋 World 🌍';
    $result = $this->convert($input);
    $this->assertStringContainsString('👋', $result);
    $this->assertStringContainsString('🌍', $result);
  }

  public function testMixedMarkdownWithUnicode(): void
  {
    $input = '**Fröhliche Grüße** und _schöne Wünsche_!';
    $result = $this->convert($input);
    $this->assertStringContainsString('Fröhliche Grüße', $result);
    $this->assertStringContainsString('schöne Wünsche', $result);
  }

  // =========================================================================
  // Real-World Event Description Tests
  // =========================================================================

  public function testTypicalEventDescription(): void
  {
    $input = <<<'MD'
# Hypnose-Stammtisch Treffen

Willkommen zum monatlichen Treffen unserer **Community**!

## Was erwartet euch?

- Austausch und Networking
- Live-Demonstrationen
- Offene Fragen und Diskussionen

## Wichtige Infos

> Bitte meldet euch vorher an!

Bei Fragen: [Kontakt](mailto:info@example.com)

---

Wir freuen uns auf euch! 🎉
MD;

    $result = $this->convert($input);

    // All content should be present and readable
    $this->assertStringContainsString('Hypnose-Stammtisch Treffen', $result);
    $this->assertStringContainsString('*Community*', $result);
    $this->assertStringContainsString('Was erwartet euch?', $result);
    $this->assertStringContainsString('• Austausch und Networking', $result);
    $this->assertStringContainsString('• Live-Demonstrationen', $result);
    $this->assertStringContainsString('| Bitte meldet euch vorher an!', $result);
    $this->assertStringContainsString('Kontakt', $result);
    $this->assertStringContainsString('mailto:info@example.com', $result);
    $this->assertStringContainsString('🎉', $result);

    // No Markdown syntax should remain
    $this->assertStringNotContainsString('#', $result);
    $this->assertStringNotContainsString('**', $result);
    $this->assertStringNotContainsString('[Kontakt]', $result);
  }
}
