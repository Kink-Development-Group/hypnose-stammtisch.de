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
    const interval = rule.options.interval || 1;
    let basis = "";
    if (interval === 1) {
      basis = freqMap[rule.options.freq];
    } else {
      basis = `alle ${interval}. ${freqMap[rule.options.freq]}`;
    }
    parts.push(basis);

    // Wochen-Tage
    if (rule.options.byweekday && rule.options.byweekday.length) {
      const days = rule.options.byweekday
        .map(
          (d: any) =>
            weekdayShort[d.toString().substring(0, 2)] || d.toString(),
        )
        .join(", ");
      parts.push(`(${days})`);
    }
    // Monatstag
    if (rule.options.bymonthday && rule.options.bymonthday.length) {
      const dayNumbers = rule.options.bymonthday.join("., ");
      parts.push(`am ${dayNumbers}. des Monats`);
    }
    // N-ter Wochentag im Monat
    if (
      rule.options.bysetpos &&
      rule.options.byweekday &&
      rule.options.freq === RRule.MONTHLY
    ) {
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
      const day =
        weekdayNames[rule.options.byweekday[0].toString().substring(0, 2)] ||
        rule.options.byweekday[0].toString();
      parts.push(`(jeden ${posLabel} ${day})`);
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
