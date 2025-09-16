import User from "../classes/User";
import { UserHelpers } from "../utils/userHelpers";

/**
 * Demonstration der neuen User-Struktur
 * Dieses Script zeigt, wie User-Objekte erstellt und verwendet werden
 */

// Beispiel API-Daten (wie sie vom Backend kommen)
const apiUserData = {
  id: "123e4567-e89b-12d3-a456-426614174000",
  username: "admin_user",
  email: "admin@example.com",
  role: "admin",
  is_active: true,
  last_login: "2024-01-15T10:30:00Z",
  created_at: "2023-12-01T08:00:00Z",
  updated_at: "2024-01-15T10:30:00Z",
};

// Array von API-Benutzerdaten
const apiUsersArray = [
  apiUserData,
  {
    id: 456, // Auch Zahlen werden unterstützt
    username: "moderator_user",
    email: "moderator@example.com",
    role: "moderator",
    is_active: true,
    last_login: null,
    created_at: "2023-12-01T08:00:00Z",
    updated_at: "2023-12-01T08:00:00Z",
  },
];

// Demo-Funktionen für die Verwendung

/**
 * Zeigt, wie ein einzelner User erstellt wird
 */
export function demonstrateUserCreation() {
  console.log("=== User Creation Demo ===");

  // Aus API-Daten erstellen
  const user = User.fromApiData(apiUserData);

  console.log("User erstellt:", {
    id: user.id,
    username: user.username,
    email: user.email,
    role: user.role,
    roleDisplay: user.getRoleDisplayName(),
    badgeClass: user.getRoleBadgeClass(),
    canManageUsers: user.canManageUsers(),
    lastLogin: user.getFormattedLastLogin(),
    createdAt: user.getFormattedCreatedAt(),
  });
}

/**
 * Zeigt, wie ein User-Array erstellt wird
 */
export function demonstrateUserArrayCreation() {
  console.log("=== User Array Creation Demo ===");

  const users = UserHelpers.fromApiArray(apiUsersArray);

  console.log("Users erstellt:", users.length);
  users.forEach((user, index) => {
    console.log(`User ${index + 1}:`, {
      username: user.username,
      role: UserHelpers.getRoleDisplayName(user.role),
      permissions: UserHelpers.getPermissions(user),
    });
  });
}

/**
 * Zeigt Permission-Checks
 */
export function demonstratePermissions() {
  console.log("=== Permissions Demo ===");

  const adminUser = User.fromApiData(apiUserData);
  const moderatorUser = User.fromApiData(apiUsersArray[1]);

  const users = [
    { name: "Admin", user: adminUser },
    { name: "Moderator", user: moderatorUser },
  ];

  users.forEach(({ name, user }) => {
    console.log(`${name} Permissions:`, {
      canManageUsers: user.canManageUsers(),
      canManageEvents: user.canManageEvents(),
      canManageMessages: user.canManageMessages(),
    });
  });
}

/**
 * Zeigt User-Updates
 */
export function demonstrateUserUpdate() {
  console.log("=== User Update Demo ===");

  const user = User.fromApiData(apiUserData);
  console.log("Original username:", user.username);

  // User aktualisieren
  const updatedUser = user.update({
    username: "new_admin_user",
    email: "new.admin@example.com",
  });

  console.log("Updated username:", updatedUser.username);
  console.log("Updated email:", updatedUser.email);
  console.log("Original user unchanged:", user.username);
}

/**
 * Zeigt API-Konvertierung
 */
export function demonstrateApiConversion() {
  console.log("=== API Conversion Demo ===");

  const user = User.fromApiData(apiUserData);
  const apiObject = user.toApiObject();

  console.log("User zu API-Objekt:", apiObject);

  // Und zurück
  const recreatedUser = User.fromApiData(apiObject);
  console.log("Wiederhergestellter User:", recreatedUser.username);
}

/**
 * Zeigt Validierung
 */
export function demonstrateValidation() {
  console.log("=== Validation Demo ===");

  try {
    // Valide Daten
    const validUser = User.fromApiData(apiUserData);
    console.log("Valid user created:", validUser.username);
  } catch (error) {
    console.error("Validation failed:", error);
  }

  try {
    // Invalide Daten
    const invalidData = {
      id: "invalid-id", // Keine gültige UUID oder Nummer
      username: "", // Leerer String
      email: "invalid-email", // Ungültige E-Mail
      role: "unknown_role", // Unbekannte Rolle
      created_at: "invalid-date", // Ungültiges Datum
      updated_at: "invalid-date",
    };

    User.fromApiData(invalidData);
  } catch (error) {
    console.log(
      "Validation correctly failed for invalid data:",
      error instanceof Error ? error.message : String(error),
    );
  }
}

// Alle Demos ausführen (kann in der Browser-Konsole aufgerufen werden)
export function runAllDemos() {
  demonstrateUserCreation();
  demonstrateUserArrayCreation();
  demonstratePermissions();
  demonstrateUserUpdate();
  demonstrateApiConversion();
  demonstrateValidation();
}
