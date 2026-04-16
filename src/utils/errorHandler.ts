// Global error handler für Browser-Erweiterungsfehler
// Diese Datei in main.ts importieren

const isDevelopment =
  typeof window !== "undefined" &&
  ["localhost", "127.0.0.1"].includes(window.location.hostname);
const isProduction = !isDevelopment;

/**
 * Filtert bekannte Browser-Erweiterungsfehler heraus
 */
function isExtensionError(error: any): boolean {
  if (!error || !error.message) return false;

  const extensionErrorPatterns = [
    /extension context invalidated/i,
    /message port closed/i,
    /no tab with id/i,
    /duplicate script id/i,
    /fido2-page-script/i,
    /webpush for notifications/i,
    /runtime\.lastError/i,
    /back\/forward cache/i,
  ];

  return extensionErrorPatterns.some((pattern) => pattern.test(error.message));
}

/**
 * Globaler Error-Handler für unbehandelte Fehler
 */
window.addEventListener("error", (event) => {
  if (isExtensionError(event.error)) {
    // Browser-Erweiterungsfehler unterdrücken
    event.preventDefault();
    return;
  }

  // Nur echte Anwendungsfehler loggen
  if (isDevelopment) {
    console.error("Application Error:", event.error);
  }
});

/**
 * Handler für unbehandelte Promise-Rejections
 */
window.addEventListener("unhandledrejection", (event) => {
  if (isExtensionError(event.reason)) {
    // Browser-Erweiterungsfehler unterdrücken
    event.preventDefault();
    return;
  }

  // Nur echte Anwendungsfehler loggen
  if (isDevelopment) {
    console.error("Unhandled Promise Rejection:", event.reason);
  }
});

/**
 * Console-Filter für Browser-Erweiterungsnachrichten
 */
if (isProduction) {
  const originalLog = console.log;
  const originalError = console.error;
  const originalWarn = console.warn;

  console.log = (...args) => {
    const message = args.join(" ");
    if (!isExtensionError({ message })) {
      originalLog.apply(console, args);
    }
  };

  console.error = (...args) => {
    const message = args.join(" ");
    if (!isExtensionError({ message })) {
      originalError.apply(console, args);
    }
  };

  console.warn = (...args) => {
    const message = args.join(" ");
    if (!isExtensionError({ message })) {
      originalWarn.apply(console, args);
    }
  };
}

export { isExtensionError };
