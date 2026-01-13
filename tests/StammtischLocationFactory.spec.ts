import { expect, test } from "@playwright/test";
import { StammtischLocationFactory } from "../src/classes/StammtischLocationFactory";
import { CountryCode } from "../src/enums/countryCode";

test.describe("StammtischLocationFactory", () => {
  test.describe("uniqueTags method", () => {
    test("should remove duplicate tags", () => {
      const mockData = {
        id: "1",
        name: "Test Location",
        city: "Berlin",
        region: "Berlin",
        country: CountryCode.GERMANY,
        coordinates: { lat: 52.52, lng: 13.405 },
        tags: ["hypnose", "berlin", "hypnose", "stammtisch", "berlin"],
      };

      const result = StammtischLocationFactory.fromApi(mockData);

      expect(result).not.toBeNull();
      expect(result?.tags).toEqual(["berlin", "hypnose", "stammtisch"]);
      expect(result?.tags.length).toBe(3);
    });

    test("should filter out empty strings", () => {
      const mockData = {
        id: "2",
        name: "Test Location",
        city: "Munich",
        region: "Bavaria",
        country: CountryCode.GERMANY,
        coordinates: { lat: 48.1351, lng: 11.582 },
        tags: ["hypnose", "", "munich", "", "stammtisch"],
      };

      const result = StammtischLocationFactory.fromApi(mockData);

      expect(result).not.toBeNull();
      // Empty strings should be filtered out by filter(Boolean)
      expect(result?.tags).toEqual(["hypnose", "munich", "stammtisch"]);
      expect(result?.tags).not.toContain("");
    });

    test("should NOT filter whitespace-only strings (current behavior)", () => {
      const mockData = {
        id: "3",
        name: "Test Location",
        city: "Hamburg",
        region: "Hamburg",
        country: CountryCode.GERMANY,
        coordinates: { lat: 53.5511, lng: 9.9937 },
        tags: ["hypnose", "  ", "hamburg", "stammtisch"],
      };

      const result = StammtischLocationFactory.fromApi(mockData);

      expect(result).not.toBeNull();
      // filter(Boolean) does NOT remove whitespace-only strings (they are truthy)
      // This test documents current behavior
      expect(result?.tags).toContain("  ");
      expect(result?.tags.length).toBe(4);
    });

    test("should sort tags alphabetically", () => {
      const mockData = {
        id: "4",
        name: "Test Location",
        city: "Frankfurt",
        region: "Hesse",
        country: CountryCode.GERMANY,
        coordinates: { lat: 50.1109, lng: 8.6821 },
        tags: ["zebra", "alpha", "munich", "berlin", "charlie"],
      };

      const result = StammtischLocationFactory.fromApi(mockData);

      expect(result).not.toBeNull();
      expect(result?.tags).toEqual([
        "alpha",
        "berlin",
        "charlie",
        "munich",
        "zebra",
      ]);
      // Verify it's actually sorted
      const sortedTags = [...(result?.tags ?? [])].sort();
      expect(result?.tags).toEqual(sortedTags);
    });

    test("should handle empty tags array", () => {
      const mockData = {
        id: "5",
        name: "Test Location",
        city: "Cologne",
        region: "North Rhine-Westphalia",
        country: CountryCode.GERMANY,
        coordinates: { lat: 50.9375, lng: 6.9603 },
        tags: [],
      };

      const result = StammtischLocationFactory.fromApi(mockData);

      expect(result).not.toBeNull();
      expect(result?.tags).toEqual([]);
      expect(result?.tags.length).toBe(0);
    });

    test("should handle missing tags field (default to empty array)", () => {
      const mockData = {
        id: "6",
        name: "Test Location",
        city: "Stuttgart",
        region: "Baden-Württemberg",
        country: CountryCode.GERMANY,
        coordinates: { lat: 48.7758, lng: 9.1829 },
        // tags field is intentionally omitted
      };

      const result = StammtischLocationFactory.fromApi(mockData);

      expect(result).not.toBeNull();
      expect(result?.tags).toEqual([]);
      expect(Array.isArray(result?.tags)).toBe(true);
    });

    test("should handle array with only empty strings", () => {
      const mockData = {
        id: "7",
        name: "Test Location",
        city: "Dresden",
        region: "Saxony",
        country: CountryCode.GERMANY,
        coordinates: { lat: 51.0504, lng: 13.7373 },
        tags: ["", "", ""],
      };

      const result = StammtischLocationFactory.fromApi(mockData);

      expect(result).not.toBeNull();
      expect(result?.tags).toEqual([]);
      expect(result?.tags.length).toBe(0);
    });

    test("should handle mixed valid and empty strings with duplicates", () => {
      const mockData = {
        id: "8",
        name: "Test Location",
        city: "Leipzig",
        region: "Saxony",
        country: CountryCode.GERMANY,
        coordinates: { lat: 51.3397, lng: 12.3731 },
        tags: [
          "hypnose",
          "",
          "berlin",
          "hypnose",
          "stammtisch",
          "",
          "berlin",
          "münchen",
        ],
      };

      const result = StammtischLocationFactory.fromApi(mockData);

      expect(result).not.toBeNull();
      expect(result?.tags).toEqual([
        "berlin",
        "hypnose",
        "münchen",
        "stammtisch",
      ]);
      expect(result?.tags.length).toBe(4);
    });

    test("should handle tags with special characters and maintain sorting", () => {
      const mockData = {
        id: "9",
        name: "Test Location",
        city: "Zürich",
        region: "Zürich",
        country: CountryCode.SWITZERLAND,
        coordinates: { lat: 47.3769, lng: 8.5417 },
        tags: ["zürich", "hypnose", "österreich", "ärzte", "berlin"],
      };

      const result = StammtischLocationFactory.fromApi(mockData);

      expect(result).not.toBeNull();
      expect(result?.tags).toBeTruthy();
      expect(result?.tags.length).toBe(5);
      // Verify alphabetical sorting (note: JavaScript sort handles umlauts)
      const sortedTags = [...(result?.tags ?? [])].sort();
      expect(result?.tags).toEqual(sortedTags);
    });

    test("should be case-sensitive in sorting", () => {
      const mockData = {
        id: "10",
        name: "Test Location",
        city: "Vienna",
        region: "Vienna",
        country: CountryCode.AUSTRIA,
        coordinates: { lat: 48.2082, lng: 16.3738 },
        tags: ["Hypnose", "berlin", "STAMMTISCH", "alpha", "Zebra"],
      };

      const result = StammtischLocationFactory.fromApi(mockData);

      expect(result).not.toBeNull();
      // Standard JavaScript sort is case-sensitive: uppercase comes before lowercase
      expect(result?.tags).toEqual([
        "Hypnose",
        "STAMMTISCH",
        "Zebra",
        "alpha",
        "berlin",
      ]);
    });

    test("should handle very long tags array efficiently", () => {
      const largeTags = Array.from({ length: 1000 }, (_, i) => `tag${i % 100}`);

      const mockData = {
        id: "11",
        name: "Test Location",
        city: "Berlin",
        region: "Berlin",
        country: CountryCode.GERMANY,
        coordinates: { lat: 52.52, lng: 13.405 },
        tags: largeTags,
      };

      const result = StammtischLocationFactory.fromApi(mockData);

      expect(result).not.toBeNull();
      // Should have exactly 100 unique tags (0-99)
      expect(result?.tags.length).toBe(100);
      // Should be sorted
      const sortedTags = [...(result?.tags ?? [])].sort();
      expect(result?.tags).toEqual(sortedTags);
    });
  });

  test.describe("fromApiArray method", () => {
    test("should process multiple locations with various tag scenarios", () => {
      const mockDataArray = [
        {
          id: "1",
          name: "Location 1",
          city: "Berlin",
          region: "Berlin",
          country: CountryCode.GERMANY,
          coordinates: { lat: 52.52, lng: 13.405 },
          tags: ["hypnose", "berlin", "hypnose"],
        },
        {
          id: "2",
          name: "Location 2",
          city: "Munich",
          region: "Bavaria",
          country: CountryCode.GERMANY,
          coordinates: { lat: 48.1351, lng: 11.582 },
          tags: ["munich", "stammtisch"],
        },
      ];

      const results = StammtischLocationFactory.fromApiArray(mockDataArray);

      expect(results.length).toBe(2);
      expect(results[0].tags).toEqual(["berlin", "hypnose"]);
      expect(results[1].tags).toEqual(["munich", "stammtisch"]);
    });

    test("should skip invalid location data but process valid ones", () => {
      const mockDataArray = [
        {
          id: "1",
          name: "Valid Location",
          city: "Berlin",
          region: "Berlin",
          country: CountryCode.GERMANY,
          coordinates: { lat: 52.52, lng: 13.405 },
          tags: ["hypnose", "berlin"],
        },
        {
          // Invalid: missing required fields
          id: "2",
          name: "Invalid Location",
        },
        {
          id: "3",
          name: "Another Valid",
          city: "Munich",
          region: "Bavaria",
          country: CountryCode.GERMANY,
          coordinates: { lat: 48.1351, lng: 11.582 },
          tags: ["munich"],
        },
      ];

      const results = StammtischLocationFactory.fromApiArray(mockDataArray);

      // Should only process the 2 valid locations
      expect(results.length).toBe(2);
      expect(results[0].id).toBe("1");
      expect(results[1].id).toBe("3");
    });
  });
});
