<script lang="ts">
  import DOMPurify from "dompurify";
  import { marked } from "marked";

  // Props
  export let value = "";
  export let placeholder = "Schreiben Sie hier... (Markdown wird unterstützt)";
  export let rows = 6;
  export let id = "";
  export let label = "";
  export let required = false;
  export let error = "";
  export let helpText = "";
  export let maxLength: number | undefined = undefined;
  export let ariaDescribedBy = "";
  export let theme: "dark" | "light" = "dark";

  // Theme-aware classes
  $: toolbarClasses =
    theme === "dark"
      ? "bg-charcoal-700 border-charcoal-600"
      : "bg-gray-100 border-gray-300";

  $: buttonClasses =
    theme === "dark"
      ? "text-smoke-300 hover:text-smoke-50 hover:bg-charcoal-600"
      : "text-gray-600 hover:text-gray-900 hover:bg-gray-200";

  $: activeButtonClasses =
    theme === "dark"
      ? "bg-accent-600 text-charcoal-900"
      : "bg-blue-600 text-white";

  $: dividerClasses = theme === "dark" ? "bg-charcoal-500" : "bg-gray-300";

  $: textareaClasses =
    theme === "dark"
      ? "bg-charcoal-800 border-charcoal-600 text-smoke-50 placeholder-smoke-400 focus:ring-accent-400"
      : "bg-white border-gray-300 text-gray-900 placeholder-gray-400 focus:ring-blue-500 focus:border-blue-500";

  $: previewClasses =
    theme === "dark"
      ? "bg-charcoal-800 border-charcoal-600 prose-invert"
      : "bg-white border-gray-300";

  $: labelClasses = theme === "dark" ? "text-smoke-100" : "text-gray-700";

  $: helpClasses = theme === "dark" ? "text-smoke-400" : "text-gray-500";

  $: errorClasses = theme === "dark" ? "text-boundaries" : "text-red-600";

  $: detailsClasses =
    theme === "dark"
      ? "text-smoke-400 hover:text-smoke-200"
      : "text-gray-500 hover:text-gray-700";

  $: detailsContentClasses =
    theme === "dark"
      ? "bg-charcoal-700/50 text-smoke-300"
      : "bg-gray-50 text-gray-600";

  $: codeHighlightClasses =
    theme === "dark" ? "text-accent-400" : "text-blue-600";

  // State
  let showPreview = false;
  let textarea: HTMLTextAreaElement;

  // Reactive computed preview
  $: processedContent = value
    ? DOMPurify.sanitize(marked.parse(value) as string)
    : "";

  $: characterCount = value.length;

  // Toolbar actions
  function insertText(before: string, after: string = "") {
    if (!textarea) return;

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = value.substring(start, end);

    const newText =
      value.substring(0, start) +
      before +
      selectedText +
      after +
      value.substring(end);

    value = newText;

    // Restore cursor position after the inserted text
    setTimeout(() => {
      textarea.focus();
      const newCursorPos = start + before.length + selectedText.length;
      textarea.setSelectionRange(newCursorPos, newCursorPos);
    }, 0);
  }

  function wrapSelection(before: string, after: string) {
    if (!textarea) return;

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;

    if (start === end) {
      // No selection - insert placeholder
      insertText(before + "Text" + after, "");
      setTimeout(() => {
        textarea.setSelectionRange(
          start + before.length,
          start + before.length + 4,
        );
      }, 0);
    } else {
      insertText(before, after);
    }
  }

  function insertBold() {
    wrapSelection("**", "**");
  }

  function insertItalic() {
    wrapSelection("*", "*");
  }

  function insertLink() {
    if (!textarea) return;

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = value.substring(start, end);

    if (selectedText) {
      insertText("[", "](https://)");
    } else {
      insertText("[Link-Text](https://)", "");
    }
  }

  function insertHeading() {
    if (!textarea) return;

    const start = textarea.selectionStart;
    // Find the start of the current line
    let lineStart = start;
    while (lineStart > 0 && value[lineStart - 1] !== "\n") {
      lineStart--;
    }

    const beforeLine = value.substring(0, lineStart);
    const afterLine = value.substring(lineStart);

    value = beforeLine + "## " + afterLine;

    setTimeout(() => {
      textarea.focus();
      textarea.setSelectionRange(lineStart + 3, lineStart + 3);
    }, 0);
  }

  function insertList() {
    if (!textarea) return;

    const start = textarea.selectionStart;
    // Find the start of the current line
    let lineStart = start;
    while (lineStart > 0 && value[lineStart - 1] !== "\n") {
      lineStart--;
    }

    const beforeLine = value.substring(0, lineStart);
    const afterLine = value.substring(lineStart);

    value = beforeLine + "- " + afterLine;

    setTimeout(() => {
      textarea.focus();
      textarea.setSelectionRange(lineStart + 2, lineStart + 2);
    }, 0);
  }

  function insertNumberedList() {
    if (!textarea) return;

    const start = textarea.selectionStart;
    // Find the start of the current line
    let lineStart = start;
    while (lineStart > 0 && value[lineStart - 1] !== "\n") {
      lineStart--;
    }

    const beforeLine = value.substring(0, lineStart);
    const afterLine = value.substring(lineStart);

    value = beforeLine + "1. " + afterLine;

    setTimeout(() => {
      textarea.focus();
      textarea.setSelectionRange(lineStart + 3, lineStart + 3);
    }, 0);
  }
</script>

<div class="markdown-editor">
  {#if label}
    <label for={id} class="block text-sm font-medium {labelClasses} mb-2">
      {label}
      {#if required}
        <span class={errorClasses} aria-label="Pflichtfeld">*</span>
      {/if}
    </label>
  {/if}

  <!-- Toolbar -->
  <div
    class="flex flex-wrap items-center gap-1 p-2 {toolbarClasses} border border-b-0 rounded-t-lg"
  >
    <button
      type="button"
      class="p-2 {buttonClasses} rounded transition-colors"
      on:click={insertBold}
      title="Fett (Strg+B)"
      aria-label="Fett formatieren"
    >
      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
        <path
          d="M4 3a1 1 0 011-1h5.5a3.5 3.5 0 012.456 6A3.5 3.5 0 0111.5 15H5a1 1 0 01-1-1V3zm2 2v3h3.5a1.5 1.5 0 100-3H6zm0 5v3h4.5a1.5 1.5 0 100-3H6z"
        />
      </svg>
    </button>

    <button
      type="button"
      class="p-2 {buttonClasses} rounded transition-colors"
      on:click={insertItalic}
      title="Kursiv (Strg+I)"
      aria-label="Kursiv formatieren"
    >
      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
        <path
          d="M8 3a1 1 0 011-1h4a1 1 0 110 2h-1.465l-2.07 10H11a1 1 0 110 2H7a1 1 0 110-2h1.465l2.07-10H9a1 1 0 01-1-1z"
        />
      </svg>
    </button>

    <div class="w-px h-5 {dividerClasses} mx-1" aria-hidden="true"></div>

    <button
      type="button"
      class="p-2 {buttonClasses} rounded transition-colors"
      on:click={insertHeading}
      title="Überschrift"
      aria-label="Überschrift einfügen"
    >
      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
        <path
          d="M3 4a1 1 0 011-1h3a1 1 0 011 1v5h4V4a1 1 0 011-1h3a1 1 0 110 2h-2v10h2a1 1 0 110 2h-3a1 1 0 01-1-1v-5H8v5a1 1 0 01-1 1H4a1 1 0 110-2h2V5H4a1 1 0 01-1-1z"
        />
      </svg>
    </button>

    <button
      type="button"
      class="p-2 {buttonClasses} rounded transition-colors"
      on:click={insertLink}
      title="Link"
      aria-label="Link einfügen"
    >
      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
        <path
          fill-rule="evenodd"
          d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z"
          clip-rule="evenodd"
        />
      </svg>
    </button>

    <div class="w-px h-5 {dividerClasses} mx-1" aria-hidden="true"></div>

    <button
      type="button"
      class="p-2 {buttonClasses} rounded transition-colors"
      on:click={insertList}
      title="Aufzählung"
      aria-label="Aufzählung einfügen"
    >
      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
        <path
          fill-rule="evenodd"
          d="M5 4a1 1 0 100 2 1 1 0 000-2zM8 5a1 1 0 011-1h8a1 1 0 110 2H9a1 1 0 01-1-1zm1 4a1 1 0 100 2h8a1 1 0 100-2H9zm0 4a1 1 0 100 2h8a1 1 0 100-2H9zm-4 0a1 1 0 100 2 1 1 0 000-2zm0-4a1 1 0 100 2 1 1 0 000-2z"
          clip-rule="evenodd"
        />
      </svg>
    </button>

    <button
      type="button"
      class="p-2 {buttonClasses} rounded transition-colors"
      on:click={insertNumberedList}
      title="Nummerierte Liste"
      aria-label="Nummerierte Liste einfügen"
    >
      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
        <path
          fill-rule="evenodd"
          d="M3 4a1 1 0 011-1h.01a1 1 0 110 2H4a1 1 0 01-1-1zm4 0a1 1 0 011-1h9a1 1 0 110 2H8a1 1 0 01-1-1zM3 9a1 1 0 011-1h.01a1 1 0 110 2H4a1 1 0 01-1-1zm4 0a1 1 0 011-1h9a1 1 0 110 2H8a1 1 0 01-1-1zm-4 5a1 1 0 011-1h.01a1 1 0 110 2H4a1 1 0 01-1-1zm4 0a1 1 0 011-1h9a1 1 0 110 2H8a1 1 0 01-1-1z"
          clip-rule="evenodd"
        />
      </svg>
    </button>

    <div class="flex-1"></div>

    <!-- Preview Toggle -->
    <button
      type="button"
      class="px-3 py-1.5 text-sm font-medium rounded transition-colors
        {showPreview ? activeButtonClasses : buttonClasses}"
      on:click={() => (showPreview = !showPreview)}
      aria-pressed={showPreview}
    >
      {showPreview ? "Bearbeiten" : "Vorschau"}
    </button>
  </div>

  <!-- Editor / Preview Area -->
  {#if showPreview}
    <div
      class="min-h-[150px] p-4 {previewClasses} border border-t-0 rounded-b-lg prose prose-sm max-w-none"
    >
      {#if processedContent}
        {@html processedContent}
      {:else}
        <p class="{helpClasses} italic">Keine Vorschau verfügbar</p>
      {/if}
    </div>
  {:else}
    <textarea
      bind:this={textarea}
      bind:value
      {id}
      {rows}
      {placeholder}
      {required}
      maxlength={maxLength}
      class="w-full px-4 py-3 {textareaClasses} border border-t-0 rounded-b-lg focus:outline-none focus:ring-2 focus:border-transparent font-mono text-sm"
      aria-describedby={ariaDescribedBy ||
        (error ? `${id}-error` : helpText ? `${id}-help` : undefined)}
      aria-invalid={error ? "true" : "false"}
    ></textarea>
  {/if}

  <!-- Help text and character count -->
  <div class="mt-2 flex justify-between items-start">
    <div class="flex-1">
      {#if helpText && !error}
        <p id="{id}-help" class="text-sm {helpClasses}">
          {helpText}
        </p>
      {/if}
      {#if error}
        <p id="{id}-error" class="text-sm {errorClasses}" role="alert">
          {error}
        </p>
      {/if}
    </div>
    {#if maxLength}
      <p
        class="text-sm ml-4 {characterCount > maxLength * 0.9
          ? errorClasses
          : helpClasses}"
      >
        {characterCount}/{maxLength}
      </p>
    {/if}
  </div>

  <!-- Markdown Hilfe -->
  <details class="mt-3">
    <summary class="text-sm {detailsClasses} cursor-pointer select-none">
      Markdown-Formatierungshilfe
    </summary>
    <div class="mt-2 p-3 {detailsContentClasses} rounded-lg text-sm space-y-2">
      <div class="grid grid-cols-2 gap-2">
        <code class="font-mono {codeHighlightClasses}">**fett**</code>
        <span><strong>fett</strong></span>

        <code class="font-mono {codeHighlightClasses}">*kursiv*</code>
        <span><em>kursiv</em></span>

        <code class="font-mono {codeHighlightClasses}">[Link](URL)</code>
        <span class="{codeHighlightClasses} underline">Link</span>

        <code class="font-mono {codeHighlightClasses}">## Überschrift</code>
        <span class="font-bold">Überschrift</span>

        <code class="font-mono {codeHighlightClasses}">- Aufzählung</code>
        <span>• Aufzählung</span>

        <code class="font-mono {codeHighlightClasses}">1. Nummeriert</code>
        <span>1. Nummeriert</span>
      </div>
    </div>
  </details>
</div>

<style>
  /* Prose styling adjustments for dark theme */
  .markdown-editor :global(.prose) {
    color: theme("colors.smoke.200");
  }

  .markdown-editor :global(.prose h1),
  .markdown-editor :global(.prose h2),
  .markdown-editor :global(.prose h3),
  .markdown-editor :global(.prose h4) {
    color: theme("colors.smoke.50");
  }

  .markdown-editor :global(.prose strong) {
    color: theme("colors.smoke.50");
  }

  .markdown-editor :global(.prose code) {
    color: theme("colors.accent.400");
    background: theme("colors.charcoal.700");
    padding: 0.125rem 0.25rem;
    border-radius: 0.25rem;
  }

  .markdown-editor :global(.prose blockquote) {
    border-left-color: theme("colors.accent.500");
    color: theme("colors.smoke.300");
  }
</style>
