<script lang="ts">
  import { onMount } from "svelte";
  import { push } from "svelte-spa-router";
  import User from "../../classes/User";
  import AdminLayout from "../../components/admin/AdminLayout.svelte";
  import { Role } from "../../enums/role";
  import { adminAuth } from "../../stores/admin";
  import { UserHelpers } from "../../utils/userHelpers";

  interface UserFormData {
    username: string;
    email: string;
    password?: string;
    role: Role;
    is_active: boolean;
  }

  let currentUser: User | null = null;
  let users: User[] = [];
  let loading = true;
  let error = "";
  let success = "";
  let showCreateForm = false;
  let editingUser: User | null = null;

  let formData: UserFormData = {
    username: "",
    email: "",
    password: "",
    role: Role.ADMIN,
    is_active: true,
  };

  onMount(async () => {
    // Check authentication and permissions
    const status = await adminAuth.checkStatus();
    if (!status.success) {
      push("/admin/login");
      return;
    }

    currentUser = User.fromApiData(status.data);

    // Check if user has permission to manage users
    if (!currentUser.canManageUsers()) {
      error = "Sie haben keine Berechtigung, Admin-Benutzer zu verwalten.";
      push("/admin/messages"); // Redirect to messages instead
      return;
    }

    await loadUsers();
  });

  async function loadUsers() {
    try {
      loading = true;
      error = "";

      console.log("AdminUsers: Loading users...");
      const response = await fetch("/api/admin/users", {
        credentials: "include",
      });

      console.log("AdminUsers: Response status:", response.status);

      if (!response.ok) {
        if (response.status === 403) {
          error = "Sie haben keine Berechtigung, Admin-Benutzer zu verwalten.";
        } else {
          error = `Fehler beim Laden der Benutzer: ${response.status} ${response.statusText}`;
        }
        return;
      }

      const result = await response.json();
      console.log("AdminUsers: API result:", result);

      if (result.success) {
        users = UserHelpers.fromApiArray(result.data || []);
        console.log("AdminUsers: Loaded users:", users);
      } else {
        error = result.message || "Fehler beim Laden der Benutzer";
      }
    } catch (err) {
      console.error("AdminUsers: Error loading users:", err);
      error = "Netzwerkfehler beim Laden der Benutzer";
    } finally {
      loading = false;
    }
  }

  async function createUser() {
    try {
      console.log("AdminUsers: Creating user...", formData);

      const response = await fetch("/api/admin/users", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "include",
        body: JSON.stringify(formData),
      });

      console.log("AdminUsers: Create response status:", response.status);

      if (!response.ok) {
        const errorText = await response.text();
        console.error("AdminUsers: Create error:", errorText);
        try {
          const errorData = JSON.parse(errorText);
          if (errorData.details) {
            const fieldErrors = Object.entries(errorData.details)
              .map(([field, message]) => `${field}: ${message}`)
              .join(", ");
            error = `Validierungsfehler: ${fieldErrors}`;
          } else {
            error =
              errorData.error ||
              `Fehler beim Erstellen des Benutzers: ${response.status}`;
          }
        } catch {
          error = `Fehler beim Erstellen des Benutzers: ${response.status}`;
        }
        return;
      }

      const result = await response.json();
      console.log("AdminUsers: Create result:", result);

      if (result.success) {
        success = `Benutzer "${formData.username}" wurde erfolgreich erstellt.`;
        showCreateForm = false;
        resetForm();
        await loadUsers();
      } else {
        error = result.message || "Fehler beim Erstellen des Benutzers";
      }
    } catch (err) {
      console.error("AdminUsers: Error creating user:", err);
      error = "Netzwerkfehler beim Erstellen des Benutzers";
    }
  }

  async function updateUser() {
    if (!editingUser) return;

    try {
      console.log("AdminUsers: Updating user...", editingUser.id, formData);

      // Prepare the data - exclude password if it's empty
      const updateData = { ...formData };
      if (!updateData.password || updateData.password.trim() === "") {
        delete updateData.password;
      }

      console.log("AdminUsers: Sending update data:", updateData);

      const response = await fetch(`/api/admin/users/${editingUser.id}`, {
        method: "PUT",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "include",
        body: JSON.stringify(updateData),
      });

      console.log("AdminUsers: Update response status:", response.status);

      if (!response.ok) {
        const errorText = await response.text();
        console.error("AdminUsers: Update error:", errorText);
        try {
          const errorData = JSON.parse(errorText);
          if (errorData.details) {
            const fieldErrors = Object.entries(errorData.details)
              .map(([field, message]) => `${field}: ${message}`)
              .join(", ");
            error = `Validierungsfehler: ${fieldErrors}`;
          } else {
            error =
              errorData.error ||
              `Fehler beim Aktualisieren des Benutzers: ${response.status}`;
          }
        } catch {
          error = `Fehler beim Aktualisieren des Benutzers: ${response.status}`;
        }
        return;
      }

      const result = await response.json();
      console.log("AdminUsers: Update result:", result);

      if (result.success) {
        success = `Benutzer "${formData.username}" wurde erfolgreich aktualisiert.`;
        editingUser = null;
        resetForm();
        await loadUsers();
      } else {
        error = result.message || "Fehler beim Aktualisieren des Benutzers";
      }
    } catch (err) {
      console.error("AdminUsers: Error updating user:", err);
      error = "Netzwerkfehler beim Aktualisieren des Benutzers";
    }
  }

  async function deleteUser(user: User) {
    if (
      !confirm(
        `Sind Sie sicher, dass Sie den Benutzer "${user.username}" löschen möchten?`,
      )
    ) {
      return;
    }

    try {
      console.log("AdminUsers: Deleting user...", user.id);

      const response = await fetch(`/api/admin/users/${user.id}`, {
        method: "DELETE",
        credentials: "include",
      });

      console.log("AdminUsers: Delete response status:", response.status);

      if (!response.ok) {
        const errorText = await response.text();
        console.error("AdminUsers: Delete error:", errorText);
        error = `Fehler beim Löschen des Benutzers: ${response.status}`;
        return;
      }

      const result = await response.json();
      console.log("AdminUsers: Delete result:", result);

      if (result.success) {
        await loadUsers();
      } else {
        error = result.message || "Fehler beim Löschen des Benutzers";
      }
    } catch (err) {
      console.error("AdminUsers: Error deleting user:", err);
      error = "Netzwerkfehler beim Löschen des Benutzers";
    }
  }

  function startEdit(user: User) {
    editingUser = user;
    formData = {
      username: user.username,
      email: user.email,
      password: "", // Don't prefill password
      role: user.role,
      is_active: user.is_active,
    };
    showCreateForm = true;
  }

  function resetForm() {
    formData = {
      username: "",
      email: "",
      password: "",
      role: Role.ADMIN,
      is_active: true,
    };
    editingUser = null;
    showCreateForm = false;
    error = "";
    success = "";
  }

  function formatDate(date: Date | string | null): string {
    return UserHelpers.formatDate(date);
  }

  async function handleSubmit() {
    if (editingUser) {
      await updateUser();
    } else {
      await createUser();
    }
  }
</script>

<svelte:head>
  <title>Admin-Benutzer verwalten - Admin</title>
</svelte:head>

<AdminLayout>
  <div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900">Admin-Benutzer</h1>
      <p class="mt-1 text-sm text-gray-600">
        Verwalten Sie Admin-Benutzer, deren Rollen und Berechtigungen
      </p>
    </div>

    {#if success}
      <div class="mb-6 bg-green-50 border border-green-200 rounded-md p-4">
        <div class="flex items-center">
          <svg
            class="w-5 h-5 text-green-400 mr-2"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
          <div class="text-green-800">{success}</div>
        </div>
        <button
          on:click={() => (success = "")}
          class="mt-2 text-green-600 text-sm underline"
        >
          Schließen
        </button>
      </div>
    {/if}

    {#if error}
      <div class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
        <div class="flex items-center">
          <svg
            class="w-5 h-5 text-red-400 mr-2"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
          <div class="text-red-800">{error}</div>
        </div>
        <button
          on:click={() => (error = "")}
          class="mt-2 text-red-600 text-sm underline"
        >
          Schließen
        </button>
      </div>
    {/if}

    <!-- Action Bar -->
    <div class="mb-6 bg-white shadow rounded-lg p-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <h2 class="text-lg font-medium text-gray-900">Benutzer-Übersicht</h2>
          {#if loading}
            <div
              class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"
            ></div>
          {/if}
        </div>

        <button
          on:click={() => {
            showCreateForm = true;
            editingUser = null;
            formData = {
              username: "",
              email: "",
              password: "",
              role: Role.ADMIN,
              is_active: true,
            };
            error = "";
            success = "";
          }}
          class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-medium rounded-xl shadow-lg text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 transform hover:scale-105"
        >
          <svg
            class="-ml-1 mr-2 h-5 w-5"
            viewBox="0 0 20 20"
            fill="currentColor"
          >
            <path
              fill-rule="evenodd"
              d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"
              clip-rule="evenodd"
            />
          </svg>
          Neuen Benutzer erstellen
        </button>
      </div>
    </div>

    <!-- Create/Edit Form -->
    {#if showCreateForm}
      <div
        class="mb-8 bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-100"
      >
        <!-- Form Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
          <div class="flex items-center justify-between">
            <h3 class="text-xl font-semibold text-white flex items-center">
              <svg
                class="w-6 h-6 mr-3"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d={editingUser
                    ? "M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                    : "M12 6v6m0 0v6m0-6h6m-6 0H6"}
                />
              </svg>
              {editingUser ? "Benutzer bearbeiten" : "Neuen Benutzer erstellen"}
            </h3>
            <button
              type="button"
              on:click={() => {
                showCreateForm = false;
                resetForm();
              }}
              aria-label="Schließen"
              class="text-white hover:text-gray-200 transition-colors"
            >
              <svg
                class="w-6 h-6"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
            </button>
          </div>
        </div>

        <!-- Form Content -->
        <div class="p-6">
          <form on:submit|preventDefault={handleSubmit} class="space-y-6">
            <!-- Personal Information Section -->
            <div class="border-b border-gray-200 pb-6">
              <h4
                class="text-lg font-medium text-gray-900 mb-4 flex items-center"
              >
                <svg
                  class="w-5 h-5 mr-2 text-blue-600"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                  />
                </svg>
                Persönliche Informationen
              </h4>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-1">
                  <label
                    for="username"
                    class="block text-sm font-semibold text-gray-700"
                  >
                    Benutzername *
                  </label>
                  <div class="relative">
                    <div
                      class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                    >
                      <svg
                        class="h-5 w-5 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                        />
                      </svg>
                    </div>
                    <input
                      type="text"
                      id="username"
                      bind:value={formData.username}
                      required
                      placeholder="z.B. max.mustermann"
                      class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-400"
                    />
                  </div>
                </div>

                <div class="space-y-1">
                  <label
                    for="email"
                    class="block text-sm font-semibold text-gray-700"
                  >
                    E-Mail-Adresse *
                  </label>
                  <div class="relative">
                    <div
                      class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                    >
                      <svg
                        class="h-5 w-5 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"
                        />
                      </svg>
                    </div>
                    <input
                      type="email"
                      id="email"
                      bind:value={formData.email}
                      required
                      placeholder="max@beispiel.de"
                      class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-400"
                    />
                  </div>
                </div>
              </div>
            </div>

            <!-- Security Section -->
            <div class="border-b border-gray-200 pb-6">
              <h4
                class="text-lg font-medium text-gray-900 mb-4 flex items-center"
              >
                <svg
                  class="w-5 h-5 mr-2 text-blue-600"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                  />
                </svg>
                Sicherheit & Berechtigung
              </h4>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-1">
                  <label
                    for="password"
                    class="block text-sm font-semibold text-gray-700"
                  >
                    {editingUser ? "Neues Passwort" : "Passwort *"}
                  </label>
                  {#if editingUser}
                    <p class="text-xs text-gray-500 mb-2">
                      Leer lassen, um das aktuelle Passwort zu behalten
                    </p>
                  {/if}
                  <div class="relative">
                    <div
                      class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                    >
                      <svg
                        class="h-5 w-5 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                        />
                      </svg>
                    </div>
                    <input
                      type="password"
                      id="password"
                      bind:value={formData.password}
                      required={!editingUser}
                      placeholder="Mindestens 8 Zeichen"
                      class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-400"
                    />
                  </div>
                </div>

                <div class="space-y-1">
                  <label
                    for="role"
                    class="block text-sm font-semibold text-gray-700"
                  >
                    Benutzerrolle *
                  </label>
                  <div class="relative">
                    <div
                      class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                    >
                      <svg
                        class="h-5 w-5 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                        />
                      </svg>
                    </div>
                    <select
                      id="role"
                      bind:value={formData.role}
                      class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-400 appearance-none"
                    >
                      <option value={Role.ADMIN}
                        >Administrator - Vollzugriff auf alle Funktionen</option
                      >
                      <option value={Role.MODERATOR}
                        >Moderator - Eingeschränkte Verwaltungsrechte</option
                      >
                    </select>
                    <div
                      class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none"
                    >
                      <svg
                        class="h-5 w-5 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M19 9l-7 7-7-7"
                        />
                      </svg>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Account Status -->
            <div class="pb-2">
              <h4
                class="text-lg font-medium text-gray-900 mb-4 flex items-center"
              >
                <svg
                  class="w-5 h-5 mr-2 text-blue-600"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
                Kontostatus
              </h4>

              <div
                class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-200"
              >
                <div class="flex items-center">
                  <input
                    type="checkbox"
                    id="is_active"
                    bind:checked={formData.is_active}
                    class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded transition-all duration-200"
                  />
                  <label
                    for="is_active"
                    class="ml-3 block text-sm font-medium text-gray-900"
                  >
                    Benutzer ist aktiv
                  </label>
                </div>
                <div class="text-right">
                  <div class="text-xs text-gray-500">
                    {formData.is_active
                      ? "Benutzer kann sich anmelden"
                      : "Benutzer ist deaktiviert"}
                  </div>
                </div>
              </div>
            </div>

            <!-- Form Actions -->
            <div
              class="flex justify-end space-x-4 pt-6 border-t border-gray-200"
            >
              <button
                type="button"
                on:click={() => {
                  showCreateForm = false;
                  resetForm();
                }}
                class="inline-flex items-center px-6 py-3 border border-gray-300 shadow-sm text-sm font-medium rounded-xl text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200"
              >
                <svg
                  class="w-4 h-4 mr-2"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
                Abbrechen
              </button>
              <button
                type="submit"
                class="inline-flex items-center px-6 py-3 border border-transparent shadow-sm text-sm font-medium rounded-xl text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 transform hover:scale-105"
              >
                <svg
                  class="w-4 h-4 mr-2"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d={editingUser
                      ? "M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                      : "M12 6v6m0 0v6m0-6h6m-6 0H6"}
                  />
                </svg>
                {editingUser ? "Benutzer aktualisieren" : "Benutzer erstellen"}
              </button>
            </div>
          </form>
        </div>
      </div>
    {/if}

    <!-- Users Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
      {#if loading}
        <div class="p-6 text-center">
          <div
            class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"
          ></div>
          <p class="mt-2 text-sm text-gray-500">Lade Benutzer...</p>
        </div>
      {:else if users.length === 0}
        <div class="p-6 text-center">
          <svg
            class="mx-auto h-12 w-12 text-gray-400"
            stroke="currentColor"
            fill="none"
            viewBox="0 0 48 48"
          >
            <path
              d="M34 40h10v-4a6 6 0 00-10.712-3.714M34 40H14m20 0v-4a9.971 9.971 0 00-.712-3.714M14 40H4v-4a6 6 0 0110.713-3.714M14 40v-4c0-1.313.253-2.566.713-3.714m0 0A10.003 10.003 0 0124 26c4.21 0 7.813 2.602 9.288 6.286M30 14a6 6 0 11-12 0 6 6 0 0112 0zm12 6a4 4 0 11-8 0 4 4 0 018 0zm-28 0a4 4 0 11-8 0 4 4 0 018 0z"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">
            Keine Benutzer gefunden
          </h3>
          <p class="mt-1 text-sm text-gray-500">
            Beginnen Sie, indem Sie Ihren ersten Admin-Benutzer erstellen.
          </p>
        </div>
      {:else}
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th
                scope="col"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >Benutzer</th
              >
              <th
                scope="col"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >Rolle</th
              >
              <th
                scope="col"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >Status</th
              >
              <th
                scope="col"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >Letzter Login</th
              >
              <th
                scope="col"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >Erstellt</th
              >
              <th scope="col" class="relative px-6 py-3"
                ><span class="sr-only">Aktionen</span></th
              >
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            {#each users as user (user.id)}
              <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="flex-shrink-0 h-10 w-10">
                      <div
                        class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center"
                      >
                        <span class="text-sm font-medium text-gray-700">
                          {user.username.substring(0, 2).toUpperCase()}
                        </span>
                      </div>
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-medium text-gray-900">
                        {user.username}
                      </div>
                      <div class="text-sm text-gray-500">{user.email}</div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    class="inline-flex px-2 py-1 text-xs font-medium rounded-full {UserHelpers.getRoleBadgeClass(
                      user.role,
                    )}"
                  >
                    {UserHelpers.getRoleDisplayName(user.role)}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span
                    class="inline-flex px-2 py-1 text-xs font-medium rounded-full {user.is_active
                      ? 'bg-green-100 text-green-800'
                      : 'bg-red-100 text-red-800'}"
                  >
                    {user.is_active ? "Aktiv" : "Inaktiv"}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {formatDate(user.last_login)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {formatDate(user.created_at)}
                </td>
                <td
                  class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
                >
                  <div class="flex justify-end space-x-2">
                    <button
                      on:click={() => startEdit(user)}
                      class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-lg text-blue-700 bg-blue-50 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200"
                    >
                      <svg
                        class="w-4 h-4 mr-1"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                        />
                      </svg>
                      Bearbeiten
                    </button>
                    <button
                      on:click={() => deleteUser(user)}
                      class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-lg text-red-700 bg-red-50 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-200"
                    >
                      <svg
                        class="w-4 h-4 mr-1"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                        />
                      </svg>
                      Löschen
                    </button>
                  </div>
                </td>
              </tr>
            {/each}
          </tbody>
        </table>
      {/if}
    </div>
  </div>
</AdminLayout>
