// Utility zur Erstellung einer natürlichsprachlichen Zusammenfassung einer RRULE
// Fokus: Deutsch, kompakt, für Admin UI Vorschau.
import { RRule } from "rrule";

const weekdayNames: Record<string, string> = {
  MO: "Montag",
  TU: "Dienstag",
  WE: "Mittwoch",
  TH: "Donnerstag",
  FR: "Freitag",
  SA: "Samstag",
  SU: "Sonntag",
};

const weekdayShort: Record<string, string> = {
  MO: "Mo",
  TU: "Di",
  WE: "Mi",
  TH: "Do",
  FR: "Fr",
  SA: "Sa",
  SU: "So",
};

export function summarizeRRule(
  rruleString: string,
  options?: { occurrences?: Date[]; localeDate?: (d: Date) => string },
): string {
  if (!rruleString) return "";
  try {
    const rule = RRule.fromString(rruleString);
    const parts: string[] = [];
    const freqMap: Record<number, string> = {
      [RRule.DAILY]: "täglich",
      [RRule.WEEKLY]: "wöchentlich",
      [RRule.MONTHLY]: "monatlich",
      [RRule.YEARLY]: "jährlich",
    };
    const freqMapPlural: Record<number, string> = {
      [RRule.DAILY]: "Tage",
      [RRule.WEEKLY]: "Wochen",
      [RRule.MONTHLY]: "Monate",
      [RRule.YEARLY]: "Jahre",
    };
    const interval = rule.options.interval || 1;
    let basis = "";
    if (interval === 1) {
      basis = freqMap[rule.options.freq];
    } else {
      basis = `alle ${interval} ${freqMapPlural[rule.options.freq]}`;
    }
    parts.push(basis);

    // N-ter Wochentag im Monat (BYSETPOS) - prüfen BEVOR generische byweekday Ausgabe
    const hasSetPos =
      rule.options.bysetpos &&
      rule.options.byweekday &&
      rule.options.freq === RRule.MONTHLY;

    if (hasSetPos) {
      const rawPos: any = rule.options.bysetpos;
      const pos = Array.isArray(rawPos) ? rawPos[0] : rawPos;
      const mapPos: Record<string, string> = {
        "1": "ersten",
        "2": "zweiten",
        "3": "dritten",
        "4": "vierten",
        "5": "fünften",
        "-1": "letzten",
        "-2": "vorletzten",
      };
      const posLabel = mapPos[String(pos)] || `${pos}.`;

      // Wochentag aus byweekday extrahieren
      const weekdayObj: number | { toString(): string } | undefined =
        rule.options.byweekday[0];
      let dayCode = "";
      if (typeof weekdayObj === "number") {
        // rrule.js verwendet 0=MO, 1=TU, etc.
        const numToCode: Record<number, string> = {
          0: "MO",
          1: "TU",
          2: "WE",
          3: "TH",
          4: "FR",
          5: "SA",
          6: "SU",
        };
        dayCode = numToCode[weekdayObj] || "";
      } else if (
        weekdayObj &&
        typeof weekdayObj === "object" &&
        typeof weekdayObj.toString === "function"
      ) {
        dayCode = weekdayObj.toString().substring(0, 2).toUpperCase();
      }

      const day = weekdayNames[dayCode] || dayCode || "?";
      parts.push(`(jeden ${posLabel} ${day})`);
    } else if (rule.options.byweekday && rule.options.byweekday.length) {
      // Wochen-Tage (ohne BYSETPOS)
      const days = rule.options.byweekday
        .map((d: any) => {
          if (typeof d === "number") {
            const numToShort: Record<number, string> = {
              0: "Mo",
              1: "Di",
              2: "Mi",
              3: "Do",
              4: "Fr",
              5: "Sa",
              6: "So",
            };
            return numToShort[d] || String(d);
          }
          return weekdayShort[d.toString().substring(0, 2)] || d.toString();
        })
        .join(", ");
      parts.push(`(${days})`);
    }

    // Monatstag
    if (rule.options.bymonthday && rule.options.bymonthday.length) {
      const dayNumbers = rule.options.bymonthday.join("., ");
      parts.push(`am ${dayNumbers}. des Monats`);
    }

    if (rule.options.count) parts.push(`für ${rule.options.count} Termine`);
    if (rule.options.until) {
      const until = options?.localeDate
        ? options.localeDate(rule.options.until)
        : rule.options.until.toLocaleDateString("de-DE");
      parts.push(`bis ${until}`);
    }
    let out = parts.filter(Boolean).join(" ");
    if (options?.occurrences && options.occurrences.length) {
      const first = options.localeDate
        ? options.localeDate(options.occurrences[0])
        : options.occurrences[0].toLocaleDateString("de-DE");
      out += ` • Nächster: ${first}`;
    }
    return out;
  } catch (e) {
    const errMsg =
      e && typeof e === "object" && "message" in e
        ? (e as Error).message
        : String(e);
    console.error("Error summarizing RRULE:", errMsg);
    return "Ungültige Wiederholungsregel";
  }
}
