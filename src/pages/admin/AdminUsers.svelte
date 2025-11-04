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

  $: totalUsers = users.length;
  $: activeUsers = users.filter((user) => user.is_active).length;
  $: inactiveUsers = totalUsers - activeUsers;

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

      const response = await fetch("/api/admin/users", {
        credentials: "include",
      });

      if (!response.ok) {
        if (response.status === 403) {
          error = "Sie haben keine Berechtigung, Admin-Benutzer zu verwalten.";
        } else {
          error = `Fehler beim Laden der Benutzer: ${response.status} ${response.statusText}`;
        }
        return;
      }

      const result = await response.json();

      if (result.success) {
        users = UserHelpers.fromApiArray(result.data || []);
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
      const response = await fetch("/api/admin/users", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "include",
        body: JSON.stringify(formData),
      });

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
      // Prepare the data - exclude password if it's empty
      const updateData = { ...formData };
      if (!updateData.password || updateData.password.trim() === "") {
        delete updateData.password;
      }

      const response = await fetch(`/api/admin/users/${editingUser.id}`, {
        method: "PUT",
        headers: {
          "Content-Type": "application/json",
        },
        credentials: "include",
        body: JSON.stringify(updateData),
      });

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
      const response = await fetch(`/api/admin/users/${user.id}`, {
        method: "DELETE",
        credentials: "include",
      });

      if (!response.ok) {
        const errorText = await response.text();
        console.error("AdminUsers: Delete error:", errorText);
        error = `Fehler beim Löschen des Benutzers: ${response.status}`;
        return;
      }

      const result = await response.json();

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

  function clearFormFields() {
    if (editingUser) {
      formData = {
        username: editingUser.username,
        email: editingUser.email,
        password: "",
        role: editingUser.role,
        is_active: editingUser.is_active,
      };
    } else {
      formData = {
        username: "",
        email: "",
        password: "",
        role: Role.ADMIN,
        is_active: true,
      };
    }
  }

  function resetForm() {
    clearFormFields();
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
  <div class="space-y-8">
    <header
      class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
    >
      <div>
        <h1 class="text-3xl font-semibold tracking-tight text-slate-800">
          Admin-Benutzer
        </h1>
        <p class="mt-1 text-sm text-slate-600">
          Verwalten Sie Admin-Benutzer, ihre Rollen und Zugänge.
        </p>
      </div>
      <div class="flex items-center gap-3">
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
          class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow transition hover:bg-blue-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500"
        >
          <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path
              fill-rule="evenodd"
              d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"
              clip-rule="evenodd"
            />
          </svg>
          Neuen Benutzer erstellen
        </button>
      </div>
    </header>

    {#if success}
      <div
        class="rounded-xl border border-green-200 bg-green-50/80 p-4 text-sm text-green-800 shadow-sm"
        role="status"
      >
        <div class="flex items-start gap-2">
          <svg class="mt-0.5 h-5 w-5 text-green-500" viewBox="0 0 24 24">
            <path
              fill="currentColor"
              d="M12 2a10 10 0 1010 10A10.011 10.011 0 0012 2zm-1 14l-4-4 1.41-1.41L11 13.17l5.59-5.59L18 9z"
            />
          </svg>
          <div class="flex-1">
            <p class="font-medium">{success}</p>
            <button
              on:click={() => (success = "")}
              class="mt-2 inline-flex items-center text-xs font-semibold text-green-700 underline-offset-2 hover:underline"
            >
              Ausblenden
            </button>
          </div>
        </div>
      </div>
    {/if}

    {#if error}
      <div
        class="rounded-xl border border-red-200 bg-red-50/90 p-4 text-sm text-red-800 shadow-sm"
        role="alert"
      >
        <div class="flex items-start gap-2">
          <svg class="mt-0.5 h-5 w-5 text-red-500" viewBox="0 0 24 24">
            <path
              fill="currentColor"
              d="M12 2a10 10 0 1010 10A10.011 10.011 0 0012 2zm1 14h-2v-2h2zm0-4h-2V7h2z"
            />
          </svg>
          <div class="flex-1">
            <p class="font-medium">{error}</p>
            <button
              on:click={() => (error = "")}
              class="mt-2 inline-flex items-center text-xs font-semibold text-red-700 underline-offset-2 hover:underline"
            >
              Ausblenden
            </button>
          </div>
        </div>
      </div>
    {/if}

    <!-- Action Bar -->
    <section
      class="rounded-2xl border border-slate-200 bg-white/80 p-4 shadow-sm sm:p-6"
    >
      <div
        class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
      >
        <div>
          <h2 class="text-lg font-semibold text-slate-800">
            Benutzer-Übersicht
          </h2>
          <p class="text-sm text-slate-500">
            Aktuelle Kennzahlen für Rollen- und Zugangsverwaltung.
          </p>
        </div>

        <div class="flex items-center gap-3">
          {#if loading}
            <div
              class="flex items-center gap-2 rounded-xl border border-blue-100 bg-blue-50/60 px-3 py-1.5 text-xs font-medium text-blue-600"
            >
              <span
                class="inline-flex h-3.5 w-3.5 animate-spin rounded-full border-2 border-blue-500 border-t-transparent"
              ></span>
              Lädt Daten...
            </div>
          {/if}
          <button
            on:click={loadUsers}
            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500"
          >
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
              <path
                d="M4.05 11a8 8 0 117.95 9 8 8 0 01-7.95-9z"
                stroke="currentColor"
                stroke-width="1.5"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
              <path
                d="M4 4v4h4"
                stroke="currentColor"
                stroke-width="1.5"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
            Aktualisieren
          </button>
        </div>
      </div>

      <div class="mt-4 grid gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
          <p
            class="text-xs font-semibold uppercase tracking-wide text-slate-500"
          >
            Gesamt
          </p>
          <p class="mt-1 text-2xl font-bold text-slate-800">{totalUsers}</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
          <p
            class="text-xs font-semibold uppercase tracking-wide text-emerald-600"
          >
            Aktiv
          </p>
          <p class="mt-1 text-2xl font-bold text-emerald-700">{activeUsers}</p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
          <p
            class="text-xs font-semibold uppercase tracking-wide text-amber-600"
          >
            Deaktiviert
          </p>
          <p class="mt-1 text-2xl font-bold text-amber-700">{inactiveUsers}</p>
        </div>
      </div>
    </section>

    <!-- Create/Edit Form -->
    {#if showCreateForm}
      <section
        class="space-y-6 rounded-2xl border border-slate-200 bg-white/90 p-4 shadow-lg sm:p-6"
      >
        <!-- Form Header -->
        <div
          class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-b border-slate-200 pb-4"
        >
          <div class="flex items-center gap-3">
            <svg
              class="h-9 w-9 rounded-full bg-blue-100 text-blue-600"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
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
            <div>
              <h3 class="text-xl font-semibold text-slate-800">
                {editingUser
                  ? "Benutzer bearbeiten"
                  : "Neuen Benutzer erstellen"}
              </h3>
              <p class="text-sm text-slate-500">
                {editingUser
                  ? "Aktualisieren Sie Rollen, Status oder Zugangsdaten."
                  : "Legen Sie einen neuen Admin-Zugang mit Rollen und Berechtigungen an."}
              </p>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <button
              type="button"
              on:click={clearFormFields}
              class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:border-slate-300 hover:bg-slate-50"
            >
              Zurücksetzen
            </button>
            <button
              type="button"
              on:click={resetForm}
              aria-label="Schließen"
              class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:border-slate-300 hover:bg-slate-50"
            >
              Schließen
            </button>
          </div>
        </div>

        <!-- Form Content -->
        <div class="space-y-6">
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
                      <option value={Role.EVENTMANAGER}
                        >Event-Manager - Darf Events anlegen & bearbeiten</option
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
                on:click={resetForm}
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
      </section>
    {/if}

    <!-- Users List -->
    <section class="space-y-6">
      {#if loading}
        <div
          class="flex flex-col items-center justify-center rounded-2xl border border-slate-200 bg-white/80 p-10 text-slate-600 shadow-sm"
        >
          <span
            class="inline-flex h-10 w-10 animate-spin rounded-full border-2 border-blue-500 border-t-transparent"
          ></span>
          <p class="mt-3 text-sm font-medium">Lade Benutzer...</p>
        </div>
      {:else if users.length === 0}
        <div
          class="rounded-2xl border border-dashed border-slate-300 bg-white/70 p-10 text-center shadow-sm"
        >
          <svg
            class="mx-auto h-12 w-12 text-slate-300"
            viewBox="0 0 48 48"
            fill="none"
            stroke="currentColor"
          >
            <path
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M34 40h10v-4a6 6 0 00-10.712-3.714M34 40H14m20 0v-4a9.971 9.971 0 00-.712-3.714M14 40H4v-4a6 6 0 0110.713-3.714M14 40v-4c0-1.313.253-2.566.713-3.714m0 0A10.003 10.003 0 0124 26c4.21 0 7.813 2.602 9.288 6.286M30 14a6 6 0 11-12 0 6 6 0 0112 0zm12 6a4 4 0 11-8 0 4 4 0 018 0zm-28 0a4 4 0 11-8 0 4 4 0 018 0z"
            />
          </svg>
          <p class="mt-4 text-base font-semibold text-slate-700">
            Noch keine Admin-Benutzer angelegt
          </p>
          <p class="mt-2 text-sm text-slate-500">
            Legen Sie den ersten Zugang über „Neuen Benutzer erstellen“ an.
          </p>
        </div>
      {:else}
        <div
          class="hidden lg:block overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
        >
          <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
              <tr>
                <th
                  scope="col"
                  class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
                >
                  Benutzer
                </th>
                <th
                  scope="col"
                  class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
                >
                  Rolle
                </th>
                <th
                  scope="col"
                  class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
                >
                  Status
                </th>
                <th
                  scope="col"
                  class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
                >
                  Zuletzt aktiv
                </th>
                <th
                  scope="col"
                  class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"
                >
                  Aktionen
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
              {#each users as user (user.id)}
                <tr class="hover:bg-slate-50">
                  <td class="px-6 py-4">
                    <div class="space-y-1">
                      <p class="text-sm font-semibold text-slate-800">
                        {user.username}
                      </p>
                      <p class="text-sm text-slate-500">{user.email}</p>
                    </div>
                  </td>
                  <td class="px-6 py-4">
                    <span
                      class={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${UserHelpers.getRoleBadgeClass(user.role)}`}
                    >
                      {UserHelpers.getRoleDisplayName(user.role)}
                    </span>
                  </td>
                  <td class="px-6 py-4">
                    <span
                      class={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${
                        user.is_active
                          ? "bg-emerald-100 text-emerald-700"
                          : "bg-amber-100 text-amber-700"
                      }`}
                    >
                      {user.is_active ? "Aktiv" : "Deaktiviert"}
                    </span>
                  </td>
                  <td class="px-6 py-4">
                    <div class="text-sm text-slate-600">
                      {formatDate(user.updated_at)}
                    </div>
                    <div class="text-xs text-slate-400">
                      Login: {formatDate(user.last_login)}
                    </div>
                  </td>
                  <td class="px-6 py-4 text-right">
                    <div class="flex justify-end gap-2">
                      <button
                        on:click={() => startEdit(user)}
                        class="inline-flex items-center gap-2 rounded-lg border border-blue-100 px-3 py-2 text-xs font-semibold text-blue-600 transition hover:border-blue-200 hover:bg-blue-50"
                      >
                        <svg
                          class="h-4 w-4"
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
                        class="inline-flex items-center gap-2 rounded-lg border border-red-100 px-3 py-2 text-xs font-semibold text-red-600 transition hover:border-red-200 hover:bg-red-50"
                      >
                        <svg
                          class="h-4 w-4"
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
        </div>

        <div class="grid gap-4 lg:hidden">
          {#each users as user (user.id)}
            <article
              class="rounded-2xl border border-slate-200 bg-white/90 p-4 shadow-sm"
            >
              <div class="flex items-start justify-between gap-3">
                <div>
                  <h3 class="text-base font-semibold text-slate-800">
                    {user.username}
                  </h3>
                  <p class="text-sm text-slate-500">{user.email}</p>
                  <div class="mt-2 flex flex-wrap items-center gap-2">
                    <span
                      class={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${UserHelpers.getRoleBadgeClass(user.role)}`}
                    >
                      {UserHelpers.getRoleDisplayName(user.role)}
                    </span>
                    <span
                      class={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${
                        user.is_active
                          ? "bg-emerald-100 text-emerald-700"
                          : "bg-amber-100 text-amber-700"
                      }`}
                    >
                      {user.is_active ? "Aktiv" : "Deaktiviert"}
                    </span>
                  </div>
                </div>
                <div class="flex flex-col gap-2">
                  <button
                    on:click={() => startEdit(user)}
                    class="inline-flex items-center justify-center rounded-lg border border-blue-100 px-3 py-1.5 text-xs font-semibold text-blue-600 transition hover:border-blue-200 hover:bg-blue-50"
                  >
                    Bearbeiten
                  </button>
                  <button
                    on:click={() => deleteUser(user)}
                    class="inline-flex items-center justify-center rounded-lg border border-red-100 px-3 py-1.5 text-xs font-semibold text-red-600 transition hover:border-red-200 hover:bg-red-50"
                  >
                    Löschen
                  </button>
                </div>
              </div>
              <div
                class="mt-4 flex flex-wrap items-center gap-3 text-xs text-slate-500"
              >
                <span>Aktualisiert: {formatDate(user.updated_at)}</span>
                <span>Login: {formatDate(user.last_login)}</span>
              </div>
            </article>
          {/each}
        </div>
      {/if}
    </section>
  </div>
</AdminLayout>
