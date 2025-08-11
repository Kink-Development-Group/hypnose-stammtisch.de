<script lang="ts">
  import dayjs from "dayjs";
  import { RRule } from "rrule";
  import { createEventDispatcher, onMount } from "svelte";
  import { summarizeRRule } from "../../../utils/rruleSummary";

  export let value: string = ""; // vorhandener RRULE String
  export let startDate: string | null = null; // YYYY-MM-DD
  // Zeitzone aktuell nicht genutzt, für spätere Erweiterung optional
  export const tzid = "Europe/Berlin";
  export let disabled: boolean = false;

  const dispatch = createEventDispatcher();

  type Freq = "DAILY" | "WEEKLY" | "MONTHLY" | "YEARLY";
  let freq: Freq = "WEEKLY";
  let interval = 1;
  let weekdays: string[] = []; // MO, TU, ...
  let monthlyMode: "bymonthday" | "bysetpos" = "bymonthday";
  let monthday: number | null = null;
  let setpos = 1; // 1..4 oder -1
  let setposWeekday: string = "MO";
  let count: number | null = null;
  let until: string | null = null; // YYYY-MM-DD
  let advanced = false;
  let manualEdit = false;
  let manualString = "";
  let parseError: string | null = null;
  let occurrences: Date[] = [];
  let summary = "";
  // Berechnete Anzahl (wenn Enddatum/UNTIL gesetzt)
  let derivedCount: number | null = null;
  let derivedCountOverflow = false;

  const weekOptions = ["MO", "TU", "WE", "TH", "FR", "SA", "SU"];
  const posOptions = [1, 2, 3, 4, -1];

  function parseExisting(rr: string) {
    if (!rr) return;
    try {
      const rule = RRule.fromString(rr);
      freq = ["DAILY", "WEEKLY", "MONTHLY", "YEARLY"][
        rule.options.freq
      ] as Freq;
      interval = rule.options.interval || 1;
      if (rule.options.byweekday && rule.options.byweekday.length) {
        weekdays = rule.options.byweekday.map((w: any) =>
          w.toString().substring(0, 2),
        );
      }
      if (rule.options.bymonthday && rule.options.bymonthday.length) {
        monthlyMode = "bymonthday";
        monthday = rule.options.bymonthday[0];
      }
      if (rule.options.bysetpos && rule.options.byweekday) {
        monthlyMode = "bysetpos";
        const rawPos: any = rule.options.bysetpos;
        setpos = Array.isArray(rawPos) ? rawPos[0] : rawPos;
        setposWeekday = rule.options.byweekday[0].toString().substring(0, 2);
      }
      if (rule.options.count) count = rule.options.count;
      else count = null;
      if (rule.options.until)
        until = dayjs(rule.options.until).format("YYYY-MM-DD");
      else until = null;
      manualString = rr;
      parseError = null;
    } catch (e) {
      parseError = "Kann vorhandene Regel nicht parsen";
    }
  }

  onMount(() => {
    parseExisting(value);
    buildAndEmit();
  });

  function toggleWeekday(code: string) {
    if (weekdays.includes(code)) {
      weekdays = weekdays.filter((w) => w !== code);
    } else {
      weekdays = [...weekdays, code];
    }
    buildAndEmit();
  }

  function buildRRule(): string {
    const opts: any = { freq: RRule[freq], interval };
    if (startDate) {
      const base = dayjs(startDate + "T09:00:00");
      opts.dtstart = base.toDate();
    }
    if (freq === "WEEKLY") {
      if (weekdays.length === 0 && startDate) {
        const wd = dayjs(startDate).day();
        const map = ["SU", "MO", "TU", "WE", "TH", "FR", "SA"];
        weekdays = [map[wd]];
      }
      if (weekdays.length) {
        opts.byweekday = weekdays.map((w) => (RRule as any)[w]);
      }
    } else if (freq === "MONTHLY") {
      if (monthlyMode === "bymonthday") {
        if (!monthday && startDate) {
          monthday = dayjs(startDate).date();
        }
        if (monthday) opts.bymonthday = [monthday];
      } else {
        opts.bysetpos = setpos;
        opts.byweekday = [(RRule as any)[setposWeekday]];
      }
    }
    if (count) {
      opts.count = count;
    }
    if (until) {
      opts.until = dayjs(until + "T23:59:59").toDate();
    }
    try {
      const rule = new RRule(opts);
      return rule.toString();
    } catch (e) {
      return "";
    }
  }

  function computeDerivedCount() {
    derivedCount = null;
    derivedCountOverflow = false;
    if (!startDate || !until) return;
    try {
      const opts: any = { freq: RRule[freq], interval };
      // dtstart
      const base = dayjs(startDate + "T09:00:00").toDate();
      opts.dtstart = base;
      if (freq === "WEEKLY") {
        if (weekdays.length === 0) {
          const wd = dayjs(startDate).day();
          const map = ["SU", "MO", "TU", "WE", "TH", "FR", "SA"];
          opts.byweekday = [(RRule as any)[map[wd]]];
        } else {
          opts.byweekday = weekdays.map((w) => (RRule as any)[w]);
        }
      } else if (freq === "MONTHLY") {
        if (monthlyMode === "bymonthday") {
          const md = monthday || dayjs(startDate).date();
          opts.bymonthday = [md];
        } else {
          opts.bysetpos = setpos;
          opts.byweekday = [(RRule as any)[setposWeekday]];
        }
      }
      opts.until = dayjs(until + "T23:59:59").toDate();
      // Niemals count setzen – wir wollen maßgeblich UNTIL verwenden
      const rule = new RRule(opts);
      const maxCompute = 1000;
      const all: Date[] = [];
      rule.all((d: Date, i: number) => {
        all.push(d);
        if (i + 1 >= maxCompute) {
          derivedCountOverflow = true;
          return true; // stop early
        }
        return false;
      });
      derivedCount = all.length;
    } catch (e) {
      derivedCount = null;
    }
  }

  // Reaktiv berechnen wenn UNTIL vorhanden und nicht im manuellen Modus
  $: if (!manualEdit && until) {
    computeDerivedCount();
  } else if (!until) {
    derivedCount = null;
    derivedCountOverflow = false;
  }

  function buildAndEmit() {
    if (manualEdit) {
      emitManual();
      return;
    }
    const rr = buildRRule();
    if (rr) {
      value = rr;
      manualString = rr;
      parseError = null;
      occurrences = previewOccurrences(rr);
      summary = summarizeRRule(rr, { occurrences });
      dispatch("change", { value: rr });
    }
  }

  function emitManual() {
    try {
      RRule.fromString(manualString);
      value = manualString;
      parseError = null;
      occurrences = previewOccurrences(value);
      summary = summarizeRRule(value, { occurrences });
      dispatch("change", { value });
    } catch (e) {
      parseError = "Ungültige RRULE";
    }
  }

  function previewOccurrences(rr: string): Date[] {
    try {
      const rule = RRule.fromString(rr);
      const next = rule.all((_d: Date, i: number) => i < 10);
      return next;
    } catch {
      return [];
    }
  }

  $: if (!manualEdit) {
    summary = summarizeRRule(value, { occurrences });
  }
</script>

<div class="space-y-4" aria-disabled={disabled}>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
    <div>
      <label
        for="rb-freq"
        class="block text-xs font-semibold text-gray-700 mb-1">Frequenz</label
      >
      <select
        id="rb-freq"
        bind:value={freq}
        class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm bg-white dark:bg-charcoal-800 text-gray-900 dark:text-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
        on:change={() => buildAndEmit()}
        {disabled}
      >
        <option value="DAILY">Täglich</option>
        <option value="WEEKLY">Wöchentlich</option>
        <option value="MONTHLY">Monatlich</option>
        <option value="YEARLY">Jährlich</option>
      </select>
    </div>
    <div>
      <label
        for="rb-interval"
        class="block text-xs font-semibold text-gray-700 mb-1">Intervall</label
      >
      <input
        id="rb-interval"
        type="number"
        min="1"
        bind:value={interval}
        class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm bg-white dark:bg-charcoal-800 text-gray-900 dark:text-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
        on:input={() => buildAndEmit()}
        {disabled}
      />
    </div>
    <div>
      <label
        for="rb-until"
        class="block text-xs font-semibold text-gray-700 mb-1"
        >Ende (Datum)</label
      >
      <input
        id="rb-until"
        type="date"
        bind:value={until}
        class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm bg-white dark:bg-charcoal-800 text-gray-900 dark:text-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
        on:change={() => buildAndEmit()}
        {disabled}
      />
    </div>
    <div>
      <label
        for="rb-count"
        class="block text-xs font-semibold text-gray-700 mb-1">Anzahl</label
      >
      {#if until && !manualEdit}
        <div class="flex items-center gap-2">
          <input
            id="rb-count"
            type="number"
            min="1"
            value={derivedCount ?? ""}
            disabled
            class="w-28 px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm bg-gray-100 dark:bg-charcoal-700 text-gray-700 dark:text-gray-300"
          />
          <span class="text-[10px] text-gray-600 dark:text-smoke-300">
            {#if derivedCount !== null}
              {derivedCountOverflow
                ? `${derivedCount}+ (gekürzt)`
                : `${derivedCount}`} berechnet aus Start+Ende
            {:else}
              wird berechnet…
            {/if}
          </span>
        </div>
        <div class="text-[10px] text-gray-500 dark:text-smoke-400 mt-0.5">
          Deaktiviert weil Enddatum gesetzt (UNTIL). Entferne Enddatum um feste
          Anzahl zu definieren.
        </div>
      {:else}
        <input
          id="rb-count"
          type="number"
          min="1"
          bind:value={count}
          class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm bg-white dark:bg-charcoal-800 text-gray-900 dark:text-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
          on:input={() => buildAndEmit()}
          {disabled}
        />
      {/if}
    </div>
  </div>

  {#if freq === "WEEKLY"}
    <div>
      <span
        class="block text-xs font-semibold text-gray-700 mb-1"
        id="rb-weekdays-label">Wochentage</span
      >
      <div class="flex flex-wrap gap-1">
        {#each weekOptions as w}
          <button
            type="button"
            aria-pressed={weekdays.includes(w)}
            aria-labelledby="rb-weekdays-label"
            class="px-2 py-1 text-xs border rounded transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 {weekdays.includes(
              w,
            )
              ? 'bg-blue-700 hover:bg-blue-600 text-white border-blue-700'
              : 'bg-white dark:bg-charcoal-800 text-gray-950 dark:text-white border-gray-500 dark:border-gray-500 hover:bg-gray-100 dark:hover:bg-charcoal-700'}"
            on:click={() => toggleWeekday(w)}
            {disabled}
          >
            {w}
          </button>
        {/each}
      </div>
    </div>
  {/if}

  {#if freq === "MONTHLY"}
    <div class="space-y-2">
      <div
        class="flex items-center gap-4 text-xs text-gray-950 dark:text-white font-medium"
      >
        <label class="flex items-center gap-1"
          ><input
            type="radio"
            bind:group={monthlyMode}
            value="bymonthday"
            on:change={() => buildAndEmit()}
            {disabled}
          /> Tag im Monat</label
        >
        <label class="flex items-center gap-1"
          ><input
            type="radio"
            bind:group={monthlyMode}
            value="bysetpos"
            on:change={() => buildAndEmit()}
            {disabled}
          /> N-ter Wochentag</label
        >
      </div>
      {#if monthlyMode === "bymonthday"}
        <div class="flex items-center gap-2 text-gray-950 dark:text-white">
          <label for="rb-monthday" class="text-xs font-medium">Tag:</label>
          <input
            id="rb-monthday"
            type="number"
            min="1"
            max="31"
            bind:value={monthday}
            class="w-24 px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm bg-white dark:bg-charcoal-800 text-gray-900 dark:text-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
            on:input={() => buildAndEmit()}
            {disabled}
          />
        </div>
      {:else}
        <div
          class="flex items-center gap-2 flex-wrap text-gray-950 dark:text-white"
        >
          <label for="rb-setpos" class="text-xs font-medium">Position:</label>
          <select
            id="rb-setpos"
            bind:value={setpos}
            class="px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm bg-white dark:bg-charcoal-800 text-gray-900 dark:text-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
            on:change={() => buildAndEmit()}
            {disabled}
          >
            {#each posOptions as p}
              <option value={p}>{p === -1 ? "letzte" : p + "."}</option>
            {/each}
          </select>
          <select
            aria-label="Wochentag für Position"
            bind:value={setposWeekday}
            class="px-2 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm bg-white dark:bg-charcoal-800 text-gray-900 dark:text-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
            on:change={() => buildAndEmit()}
            {disabled}
          >
            {#each weekOptions as w}
              <option value={w}>{w}</option>
            {/each}
          </select>
        </div>
      {/if}
    </div>
  {/if}

  <div
    class="border border-gray-300 dark:border-charcoal-600 rounded p-3 bg-charcoal-50 dark:bg-charcoal-700 text-xs text-gray-900 dark:text-smoke-50 shadow-sm"
  >
    <div class="flex items-center justify-between mb-2">
      <span class="font-medium">Zusammenfassung:</span>
      <button
        type="button"
        class="text-accent-700 dark:text-accent-400 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-400 rounded"
        on:click={() => (advanced = !advanced)}>Advanced</button
      >
    </div>
    <p
      class="mb-1 text-gray-800 dark:text-smoke-50 font-medium tracking-wide"
      aria-live="polite"
    >
      {summary}
    </p>
    {#if advanced}
      <div class="space-y-2 mt-2">
        <div class="flex items-center gap-2">
          <label class="flex items-center gap-1 text-xs"
            ><input type="checkbox" bind:checked={manualEdit} /> Manuell bearbeiten</label
          >
          {#if parseError}<span class="text-red-600">{parseError}</span>{/if}
        </div>
        <textarea
          class="w-full text-xs border border-gray-300 dark:border-charcoal-600 rounded p-2 font-mono bg-gray-50 dark:bg-charcoal-900 text-gray-800 dark:text-smoke-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-400"
          rows="2"
          bind:value={manualString}
          on:input={emitManual}
          {disabled}
        ></textarea>
        <div class="text-[10px] text-gray-600 dark:text-smoke-300">
          RRULE wird automatisch generiert – manuelle Bearbeitung überschreibt
          die Felder oben.
        </div>
      </div>
    {/if}
  </div>

  <div class="space-y-1">
    <span class="block text-xs font-medium text-gray-700"
      >Vorschau (erste {occurrences.length} Termine)</span
    >
    {#if occurrences.length}
      <ul
        class="text-[11px] grid grid-cols-1 md:grid-cols-2 gap-x-4 list-disc list-inside text-gray-800"
      >
        {#each occurrences as o}
          <li>{dayjs(o).format("DD.MM.YYYY")}</li>
        {/each}
      </ul>
    {:else}
      <p class="text-[11px] text-gray-600 dark:text-smoke-300">
        Keine Vorschau verfügbar.
      </p>
    {/if}
  </div>

  <input type="hidden" name="rrule" {value} />
</div>

<style></style>
